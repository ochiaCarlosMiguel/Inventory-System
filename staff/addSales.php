<?php
// Start the session
session_start();

// Include the database connection
require_once '../connection/connections.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    // User is not logged in, redirect to index.php
    header("Location: index.php");
    exit(); // Ensure no further code is executed
}

// Initialize default values
$userName = '';
$profileImage = "../upload/default-profile.jpg";

// Fetch user data if session exists
if (isset($_SESSION['user_id'])) {
    try {
        // Updated query to match profile.php table structure
        $stmt = $pdo->prepare("SELECT id, name, profile_image FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($userData) {
            // Set user name
            if (!empty($userData['name'])) {
                $userName = $userData['name'];
            }
            
            // Set profile image
            if (!empty($userData['profile_image'])) {
                $tempProfileImage = "../upload/profiles/" . $userData['profile_image'];
                // Use default image if file doesn't exist
                if (file_exists($tempProfileImage)) {
                    $profileImage = $tempProfileImage;
                } else {
                    $profileImage = "../upload/default-profile.jpg";
                }
            }
        }
    } catch (PDOException $e) {
        error_log("Database Error: " . $e->getMessage());
    }
}

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Set to 0 in production
ini_set('log_errors', 1);
ini_set('error_log', '../error.log');

// Prevent any output before JSON response
ob_start();

// Modify the POST handler for saving sales
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Clear any previous output
    ob_clean();
    
    try {
        // Get the raw POST data
        $jsonData = file_get_contents('php://input');
        if (!$jsonData) {
            throw new Exception('No data received');
        }

        $data = json_decode($jsonData, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON: ' . json_last_error_msg());
        }

        // Check if this is a saveSale action
        if (!isset($data['action']) || $data['action'] !== 'saveSale') {
            throw new Exception('Invalid action specified');
        }

        // Validate the received data
        if (!isset($data['customerId']) || !isset($data['items']) || empty($data['items'])) {
            throw new Exception('Missing required sale data');
        }

        // Start transaction
        $pdo->beginTransaction();

        // Insert into sales table
        $saleQuery = "INSERT INTO sales (customer_id, total_amount, sale_date) VALUES (?, ?, NOW())";
        $saleStmt = $pdo->prepare($saleQuery);
        $saleStmt->execute([$data['customerId'], $data['grandTotal']]);
        $saleId = $pdo->lastInsertId();

        // Insert sale items and update product quantities
        $itemQuery = "INSERT INTO sale_items (sale_id, product_id, quantity, price, total) VALUES (?, ?, ?, ?, ?)";
        $itemStmt = $pdo->prepare($itemQuery);

        $updateStockQuery = "UPDATE products SET quantity = quantity - ? WHERE id = ? AND quantity >= ?";
        $updateStockStmt = $pdo->prepare($updateStockQuery);

        foreach ($data['items'] as $item) {
            // First check if there's enough stock
            $checkStockQuery = "SELECT quantity FROM products WHERE id = ?";
            $checkStockStmt = $pdo->prepare($checkStockQuery);
            $checkStockStmt->execute([$item['productId']]);
            $currentStock = $checkStockStmt->fetchColumn();

            if ($currentStock < $item['quantity']) {
                throw new Exception("Insufficient stock for product ID: " . $item['productId']);
            }

            // Insert sale item
            $itemStmt->execute([
                $saleId,
                $item['productId'],
                $item['quantity'],
                $item['price'],
                $item['total']
            ]);

            // Update product stock
            $updateStockStmt->execute([
                $item['quantity'],
                $item['productId'],
                $item['quantity']
            ]);

            // Verify the stock update was successful
            if ($updateStockStmt->rowCount() === 0) {
                throw new Exception("Failed to update stock for product ID: " . $item['productId']);
            }
        }

        // Log the sale activity
        if (isset($_SESSION['user_id'])) {
            $logQuery = "INSERT INTO activity_logs (user_id, action, details, created_at) VALUES (?, ?, ?, NOW())";
            $logStmt = $pdo->prepare($logQuery);
            $logStmt->execute([
                $_SESSION['user_id'],
                'SALE_COMPLETED',
                "Sale ID: $saleId, Customer ID: " . $data['customerId'] . ", Total: ₱" . number_format($data['grandTotal'], 2)
            ]);
        }

        // Commit transaction
        $pdo->commit();

        // Send success response
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Sale completed successfully',
            'saleId' => $saleId
        ]);
        exit;

    } catch (Exception $e) {
        // Rollback transaction on error
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        // Log the error
        error_log("Sale Error: " . $e->getMessage());
        
        // Send error response
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Error processing sale: ' . $e->getMessage()
        ]);
        exit;
    }
}

// Handle search request
if (isset($_GET['search'])) {
    $search = $_GET['search'];
    $query = "SELECT p.*, c.category_name 
              FROM products p 
              LEFT JOIN categories c ON p.category_id = c.id 
              WHERE p.product_title LIKE :term 
              OR p.id LIKE :term 
              OR c.category_name LIKE :term";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute(['term' => "%$search%"]);
    
    while ($row = $stmt->fetch()) {
        echo "<tr>";
        echo "<td>CTM_" . rand(1000, 9999) . "</td>";
        echo "<td class='product-id'>" . htmlspecialchars($row['id']) . "</td>";
        echo "<td><img src='../upload/" . htmlspecialchars($row['image_path']) . "' alt='Product' class='product-image' style='width: 50px; height: 50px; object-fit: cover;'></td>";
        echo "<td>" . htmlspecialchars($row['product_title']) . "</td>";
        echo "<td>" . htmlspecialchars($row['category_name']) . "</td>";
        echo "<td><input type='number' class='quantity-input' value='1' min='1' max='" . $row['quantity'] . "'></td>";
        echo "<td class='price'>₱" . number_format($row['selling_price'], 2) . "</td>";
        echo "<td class='total'>₱" . number_format($row['selling_price'], 2) . "</td>";
        echo "<td><button class='add-sale-btn'><i class='fas fa-plus'></i> Add</button></td>";
        echo "</tr>";
    }
    exit;
}

// Handle add sale request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {
    try {
        $pdo->beginTransaction();
        
        // ... rest of your existing add sale code ...
    }
    catch(PDOException $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <title>Document</title>
    <style>
        /* Base Styles */
        @import url('https://fonts.cdnfonts.com/css/century-gothic');
        @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800;900&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* Body and Background */
        body {  
            background: url('../upload/artmoreshop.jpg') no-repeat center center fixed;
            -webkit-background-size: cover;
            -moz-background-size: cover;
            -o-background-size: cover;
            background-size: cover;
            min-height: 100vh;
            position: relative;
        }

        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.75);
            z-index: -1;
        }

        /* Sidebar Styles */
        .sidebar {
            width: 249px;
            height: 100vh;
            background-color: #1E1E1E;
            position: fixed;
            left: 0;
            top: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            z-index: 1;
        }

        .logo-container {
            background-color: #750605;
            padding: 15px;
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 30px;
            min-height: 80px;
        }

        .logo-container img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
        }

        .logo-container h1 {
            color: #F8B83C;
            font-family: 'Montserrat', sans-serif;
            font-size: 14px;
            font-weight: 700;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 180px;
            line-height: 1.2;
        }

        /* Navigation Styles */
        .nav-links {
            list-style: none;
            padding: 0 15px;
            overflow-y: auto;
            flex-grow: 1;
        }

        .nav-links li {
            margin-bottom: 5px;
        }

        .nav-links a {
            display: flex;
            align-items: center;
            color: #AEB2B7;
            text-decoration: none;
            padding: 12px 15px;
            border-radius: 5px;
            font-family: 'Century Gothic', sans-serif;
            transition: all 0.3s ease;
        }

        .nav-links a:hover {
            background-color: #2d2d2d;
            color: #F8B83C;
        }

        .nav-links i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        /* Topbar Styles */
        .topbar {
            position: fixed;
            top: 0;
            left: 249px; /* Same as sidebar width */
            right: 0;
            height: 60px;
            background-color: #363333;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
            z-index: 1;
        }

        .time-label {
            color: #AEB2B7;
            font-family: 'Century Gothic', sans-serif;
        }

        .profile-container {
            position: relative;
            background-color: #403E3E;
            width: 200px;
            height: 36px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            padding: 0 15px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .profile-container:hover {
            background-color: #4a4848;
        }

        .profile-container img {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            object-fit: cover;
        }

        .profile-name {
            color: #AEB2B7;
            font-family: 'Century Gothic', sans-serif;
            margin-left: 10px;
            font-size: 14px;
            flex-grow: 1;
        }

        .dropdown-content {
            display: none;
            position: absolute;
            top: calc(100% + 5px);
            right: 0;
            background-color: #403E3E;
            min-width: 200px;
            border-radius: 8px;
            opacity: 0;
            transform: translateY(-10px);
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            z-index: 1000;
        }

        .dropdown-content.show-dropdown {
            display: block;
            opacity: 1;
            transform: translateY(0);
        }

        .dropdown-content a {
            color: #AEB2B7;
            font-family: 'Century Gothic', sans-serif;
            padding: 12px 16px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.2s ease;
        }

        .dropdown-content a:first-child {
            border-radius: 8px 8px 0 0;
        }

        .dropdown-content a:last-child {
            border-radius: 0 0 8px 8px;
        }

        #profileBtn:hover {
            background-color: #2d2d2d;
            color: #4CAF50;
        }

        #settingsBtn:hover {
            background-color: #2d2d2d;
            color: #2196F3;
        }

        #logoutBtn:hover {
            background-color: #2d2d2d;
            color: #ff4747;
        }

        .fa-caret-down {
            transition: transform 0.3s ease;
            color: #AEB2B7;
        }

        .profile-container.active .fa-caret-down {
            transform: rotate(180deg);
        }

        /* Active state */
        .nav-links li.active > a {
            background-color: #750605;
            color: #F8B83C;
        }

        /* Dropdown styles */
        .dropdown .submenu {
            display: none;
            list-style: none;
            padding-left: 30px;
            margin-top: 5px;
        }

        .dropdown.open .submenu {
            display: block;
        }

        .submenu a {
            padding: 10px 15px;
            font-size: 0.9em;
        }

        .dropdown-toggle {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .arrow {
            transition: transform 0.3s ease;
        }

        .dropdown.open .arrow {
            transform: rotate(180deg);
        }

        /* Hover effects */
        .nav-links a:hover {
            background-color: #2d2d2d;
            color: #F8B83C;
        }

        .submenu a:hover {
            padding-left: 20px;
        }

        /* Main Content Styles */
        .main-content {
            margin-left: 249px;
            padding: 20px;
            padding-top: 80px;
        }

        .content-card {
            background-color: rgba(30, 30, 30, 0.7);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .table-container {
            overflow-x: auto;
            margin-top: 20px;
            background-color: rgba(30, 30, 30, 0.7);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px; /* Minimum width to prevent squishing */
        }

        th, td {
            padding: 12px;
            text-align: left;
            white-space: nowrap; /* Prevent text wrapping */
        }

        /* Responsive adjustments */
        @media screen and (max-width: 1400px) {
            .content-card {
                max-width: 1000px;
            }
        }

        @media screen and (max-width: 1200px) {
            .content-card {
                max-width: 800px;
            }
        }

        /* Add horizontal scroll for smaller screens */
        .table-container::-webkit-scrollbar {
            height: 8px;
        }

        .table-container::-webkit-scrollbar-track {
            background: #1E1E1E;
        }

        .table-container::-webkit-scrollbar-thumb {
            background: #750605;
            border-radius: 4px;
        }

        .table-container::-webkit-scrollbar-thumb:hover {
            background: #8f0806;
        }

        .table-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }

        .table-header i {
            color: #F8B83C;
            font-size: 24px;
        }

        .table-header h2 {
            color: #AEB2B7;
            font-family: 'Century Gothic', sans-serif;
            font-size: 20px;
            font-weight: bold;
        }

        .header-actions {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 20px;
        }

        .add-btn {
            background-color: #750605;
            color: #F8B83C;
            border: none;
            border-radius: 5px;
            padding: 10px 20px;
            font-family: 'Century Gothic', sans-serif;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .add-btn:hover {
            background-color: #8f0806;
            transform: translateY(-2px);
        }

        .header-line {
            border: none;
            border-bottom: 1px solid #333;
            margin: 10px 0 20px 0;
        }

        /* Table Styles */
        .table-container {
            overflow-x: auto;
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        th {
            color: #F8B83C;
            font-weight: bold;
            font-size: 14px;
            padding: 15px 12px;
            text-align: left;
            font-family: 'Century Gothic', sans-serif;
            background-color: rgba(30, 30, 30, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-top: none;
            border-right: none;
        }

        th:last-child {
            border-right: none;
        }

        td {
            padding: 15px 12px;
            color: #AEB2B7;
            font-family: 'Century Gothic', sans-serif;
            font-size: 14px;
            border: 1px solid #333;
            border-right: none;
            border-bottom: none;
        }

        /* Add border-right to last column */
        th:last-child, td:last-child {
            border-right: none;
        }

        /* Add border-bottom to last row */
        tr:last-child td {
            border-bottom: none;
        }

        tr:hover td {
            background-color: rgba(45, 45, 45, 0.6);
        }

        /* First column styles */
        th:first-child, td:first-child {
            border-left: none;
        }

        /* Status cell padding adjustment for badges */
        td .status-active,
        td .status-inactive {
            display: inline-block;
            margin: -3px 0;
        }

        /* Action column alignment */
        .actions {
            display: flex;
            gap: 10px;
            justify-content: center;
        }

        .action-btn {
            background: none;
            border: none;
            color: #AEB2B7;
            cursor: pointer;
            padding: 8px;
            border-radius: 4px;
            transition: all 0.2s ease;
        }

        .action-btn.edit:hover {
            color: #2196F3;
            background-color: rgba(33, 150, 243, 0.1);
        }

        .action-btn.delete:hover {
            color: #F44336;
            background-color: rgba(244, 67, 54, 0.1);
        }

        /* Responsive Design */
        @media screen and (max-width: 1400px) {
            .main-content {
                padding: 20px;
            }
        }

        /* Search Section Styles */
        .search-section {
            margin-bottom: 20px;
        }

        .search-container {
            display: flex;
            gap: 10px;
            max-width: 500px;
        }

        #productSearch {
            flex-grow: 1;
            padding: 10px;
            border: 1px solid #333;
            border-radius: 5px;
            background-color: rgba(30, 30, 30, 0.7);
            color: #AEB2B7;
        }

        .search-btn {
            background-color: #750605;
            color: #F8B83C;
            border: none;
            border-radius: 5px;
            padding: 10px 20px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .search-btn:hover {
            background-color: #8f0806;
        }

        .quantity-input {
            width: 60px;
            padding: 5px;
            border: 1px solid #333;
            border-radius: 4px;
            background-color: rgba(30, 30, 30, 0.7);
            color: #AEB2B7;
        }

        .add-sale-btn {
            background-color: #750605;
            color: #F8B83C;
            border: none;
            border-radius: 4px;
            padding: 5px 10px;
            cursor: pointer;
        }

        /* Success Message Styles */
        .success-message {
            display: none;
            position: fixed;
            top: 20px;
            right: 20px;
            background-color: rgba(76, 175, 80, 0.9);
            color: white;
            padding: 15px 25px;
            border-radius: 5px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            animation: slideIn 0.5s ease-out;
        }

        .success-content {
            display: flex;
            align-items: center;
            gap: 10px;
            font-family: 'Century Gothic', sans-serif;
        }

        .success-content i {
            font-size: 20px;
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes slideOut {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }

        .success-message.hide {
            animation: slideOut 0.5s ease-out forwards;
        }

        /* Date/Time Picker Styles */
        .time-input {
            width: 100px;
            padding: 8px;
            background-color: rgba(30, 30, 30, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 4px;
            color: #AEB2B7;
            font-family: 'Century Gothic', sans-serif;
            margin-left: 5px;
        }

        /* Customer ID input styles */
        .customer-id-input {
            background-color: rgba(30, 30, 30, 0.8);
            color: #F8B83C;  /* Make it stand out */
            font-weight: bold;
        }

        .filter-section {
            margin-bottom: 20px;
            padding: 15px;
            background-color: rgba(30, 30, 30, 0.7);
            border-radius: 5px;
        }

        .filter-section label {
            color: #F8B83C;
            margin-right: 10px;
            font-family: 'Century Gothic', sans-serif;
        }

        .filter-section select {
            padding: 8px;
            border-radius: 4px;
            background-color: #2d2d2d;
            color: #AEB2B7;
            border: 1px solid #333;
        }

        .product-image {
            width: 50px;
            height: 50px;
            border-radius: 5px;
            object-fit: cover;
        }

        .select-btn {
            background-color: #750605;
            color: #F8B83C;
            border: none;
            border-radius: 4px;
            padding: 5px 10px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .select-btn:hover {
            background-color: #8f0806;
        }

        /* Add to your existing styles */
        .search-section {
            margin-bottom: 20px;
        }

        .search-container {
            display: flex;
            gap: 10px;
            max-width: 500px;
        }

        #productSearch {
            flex-grow: 1;
            padding: 10px;
            border: 1px solid #333;
            border-radius: 5px;
            background-color: rgba(30, 30, 30, 0.7);
            color: #AEB2B7;
        }

        .search-btn {
            background-color: #750605;
            color: #F8B83C;
            border: none;
            border-radius: 5px;
            padding: 10px 20px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .search-btn:hover {
            background-color: #8f0806;
        }

        .quantity-input {
            width: 60px;
            padding: 5px;
            border: 1px solid #333;
            border-radius: 4px;
            background-color: rgba(30, 30, 30, 0.7);
            color: #AEB2B7;
        }

        .add-sale-btn {
            background-color: #750605;
            color: #F8B83C;
            border: none;
            border-radius: 4px;
            padding: 5px 10px;
            cursor: pointer;
        }

        /* Content Layout Fixes */
        .content-wrapper {
            margin-left: 249px; /* Same as sidebar width */
            padding: 20px;
            padding-top: 80px; /* Adjust based on your topbar height */
        }

        .content-card {
            background-color: rgba(30, 30, 30, 0.7);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            width: calc(100% - 40px); /* Account for padding */
            max-width: 1200px; /* Adjust this value as needed */
        }

        /* Table Container Styles */
        .table-container {
            overflow-x: auto;
            margin-top: 20px;
            width: 100%;
        }

        /* Table Styles */
        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px; /* Minimum width to prevent squishing */
        }

        th, td {
            padding: 12px;
            text-align: left;
            white-space: nowrap; /* Prevent text wrapping */
        }

        /* Responsive adjustments */
        @media screen and (max-width: 1400px) {
            .content-card {
                max-width: 1000px;
            }
        }

        @media screen and (max-width: 1200px) {
            .content-card {
                max-width: 800px;
            }
        }

        /* Add horizontal scroll for smaller screens */
        .table-container::-webkit-scrollbar {
            height: 8px;
        }

        .table-container::-webkit-scrollbar-track {
            background: #1E1E1E;
        }

        .table-container::-webkit-scrollbar-thumb {
            background: #750605;
            border-radius: 4px;
        }

        .table-container::-webkit-scrollbar-thumb:hover {
            background: #8f0806;
        }

        /* Add or update these styles in your CSS */
        .editable-input {
            width: 100%;
            padding: 8px;
            background-color: rgba(30, 30, 30, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 4px;
            color: #AEB2B7;
            font-family: 'Century Gothic', sans-serif;
        }

        /* Specific styles for each input type */
        .customer-id-input {
            background-color: rgba(30, 30, 30, 0.9);
            color: #F8B83C;
            font-weight: bold;
            width: 120px;
        }

        .item-input {
            width: 200px;
        }

        .price-input, .qty-input {
            width: 100px;
            text-align: right;
        }

        .total-amount {
            color: #F8B83C;
            font-weight: bold;
            font-size: 16px;
        }

        .date-picker-container {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .date-input {
            width: 120px;
            padding: 8px;
            background-color: rgba(30, 30, 30, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 4px;
            color: #AEB2B7;
            font-family: 'Century Gothic', sans-serif;
        }

        .time-input {
            width: 100px;
            padding: 8px;
            background-color: rgba(30, 30, 30, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 4px;
            color: #AEB2B7;
            font-family: 'Century Gothic', sans-serif;
        }

        /* Style for readonly inputs */
        .editable-input[readonly], 
        .date-input[readonly], 
        .time-input[readonly] {
            background-color: rgba(30, 30, 30, 0.5);
            cursor: not-allowed;
        }

        /* Add Sale button styles */
        .add-sale-btn {
            background-color: #750605;
            color: #F8B83C;
            border: none;
            border-radius: 4px;
            padding: 8px 16px;
            cursor: pointer;
            font-family: 'Century Gothic', sans-serif;
            transition: all 0.3s ease;
        }

        .add-sale-btn:hover:not([disabled]) {
            background-color: #8f0806;
            transform: translateY(-2px);
        }

        .add-sale-btn[disabled] {
            background-color: #4a4848;
            cursor: not-allowed;
            opacity: 0.7;
        }

        /* Table cell padding */
        #saleDataRow td {
            padding: 12px;
            vertical-align: middle;
        }

        /* Add hover effect to the row */
        #saleDataRow:hover {
            background-color: rgba(45, 45, 45, 0.6);
        }

        /* Add to your existing styles */
        .select-btn {
            background-color: #750605;
            color: #F8B83C;
            border: none;
            border-radius: 4px;
            padding: 6px 12px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .select-btn:hover {
            background-color: #8f0806;
            transform: translateY(-2px);
        }

        .select-btn i {
            font-size: 12px;
        }

        .product-image {
            width: 50px;
            height: 50px;
            border-radius: 4px;
            object-fit: cover;
        }

        #products-table td {
            vertical-align: middle;
        }

        .filter-section select {
            padding: 8px;
            border-radius: 4px;
            background-color: rgba(30, 30, 30, 0.7);
            color: #AEB2B7;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        /* Update spacing between sections */
        #newSaleSection {
            margin-bottom: 30px;  /* Add space between sale section and filter */
        }

        .filter-section {
            margin: 20px 0;  /* Maintain consistent spacing */
        }

        /* Optional: Add a subtle separator */
        #newSaleSection:after {
            content: '';
            display: block;
            height: 1px;
            background: rgba(255, 255, 255, 0.1);
            margin: 30px 0;
        }

        /* Search Section Styles */
        .search-section {
            margin-bottom: 20px;
        }

        .search-container {
            width: 100%;
        }

        .search-input-wrapper {
            position: relative;
            max-width: 600px;
        }

        .search-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #AEB2B7;
        }

        #searchInput {
            width: 100%;
            padding: 12px 12px 12px 40px;
            background-color: rgba(30, 30, 30, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 6px;
            color: #AEB2B7;
            font-family: 'Century Gothic', sans-serif;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        #searchInput:focus {
            outline: none;
            border-color: #750605;
            background-color: rgba(30, 30, 30, 0.9);
            box-shadow: 0 0 0 2px rgba(117, 6, 5, 0.2);
        }

        #searchInput::placeholder {
            color: #666;
        }

        .find-btn {
            background-color: #750605;
            color: #F8B83C;
            border: none;
            border-radius: 4px;
            padding: 8px 16px;
            cursor: pointer;
            margin-left: auto;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .find-btn:hover {
            background-color: #8f0806;
            transform: translateY(-2px);
        }

        .calendar-btn {
            background-color: #750605;
            color: #F8B83C;
            border: none;
            border-radius: 4px;
            padding: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .calendar-btn:hover:not([disabled]) {
            background-color: #8f0806;
        }

        .calendar-btn[disabled] {
            background-color: #4a4848;
            cursor: not-allowed;
            opacity: 0.7;
        }

        .table-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }

        .complete-sale-btn {
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 12px 24px;
            cursor: pointer;
            font-family: 'Century Gothic', sans-serif;
            font-size: 16px;
            font-weight: bold;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .complete-sale-btn:hover:not([disabled]) {
            background-color: #218838;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .complete-sale-btn[disabled] {
            background-color: #6c757d;
            cursor: not-allowed;
            opacity: 0.7;
        }

        .complete-sale-btn i {
            font-size: 18px;
        }

        /* Success Message Styles */
        .success-message {
            display: none;
            position: fixed;
            top: 20px;
            right: 20px;
            background-color: rgba(40, 167, 69, 0.9);
            color: white;
            padding: 15px 25px;
            border-radius: 5px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            animation: slideIn 0.5s ease-out;
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes slideOut {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }

        .success-message.hide {
            animation: slideOut 0.5s ease-out forwards;
        }

        /* Quantity Control Styles */
        .quantity-cell {
            min-width: 120px;
        }

        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 5px;
            background-color: rgba(30, 30, 30, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 4px;
            padding: 2px;
            width: fit-content;
        }

        .qty-btn {
            background-color: #750605;
            color: #F8B83C;
            border: none;
            border-radius: 3px;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .qty-btn:hover {
            background-color: #8f0806;
        }

        .qty-btn:active {
            transform: scale(0.95);
        }

        .qty-btn i {
            font-size: 10px;
        }

        .qty-input {
            width: 40px;
            text-align: center;
            border: none;
            background-color: transparent;
            color: #AEB2B7;
            font-family: 'Century Gothic', sans-serif;
            font-size: 14px;
            padding: 2px;
            
            /* Remove spinner buttons */
            appearance: textfield; /* Standard */
            -webkit-appearance: textfield; /* Safari/Chrome */
            -moz-appearance: textfield; /* Firefox */
        }

        /* Additional way to remove spinner for Webkit browsers */
        .qty-input::-webkit-outer-spin-button,
        .qty-input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        .qty-input:focus {
            outline: none;
            color: #F8B83C;
        }

        /* Disabled state styles */
        .qty-btn:disabled {
            background-color: #4a4848;
            cursor: not-allowed;
            opacity: 0.7;
        }

        /* Optional: Add hover tooltip for max quantity */
        .quantity-controls {
            position: relative;
        }

        .quantity-controls::after {
            content: attr(data-max);
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            background-color: rgba(0, 0, 0, 0.8);
            color: #fff;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            opacity: 0;
            visibility: hidden;
            transition: all 0.2s ease;
        }

        .quantity-controls:hover::after {
            opacity: 1;
            visibility: visible;
        }

        /* Modify the remove button style to match */
        .remove-item-btn {
            background-color: #750605;
            color: #F8B83C;
            border: none;
            border-radius: 4px;
            padding: 6px 10px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .remove-item-btn:hover {
            background-color: #8f0806;
        }

        .remove-item-btn i {
            font-size: 12px;
        }

        /* Complete Sale Button Styles */
        .complete-sale-container {
            margin-top: 20px;
            text-align: right;
            padding: 10px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .complete-sale-btn {
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 12px 24px;
            cursor: pointer;
            font-family: 'Century Gothic', sans-serif;
            font-size: 16px;
            font-weight: bold;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .complete-sale-btn:hover:not([disabled]) {
            background-color: #218838;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .complete-sale-btn[disabled] {
            background-color: #6c757d;
            cursor: not-allowed;
            opacity: 0.7;
        }

        .complete-sale-btn i {
            font-size: 18px;
        }

        /* Custom Notification Styles */
        .notification {
            display: none;
            position: fixed;
            top: 20px;
            right: 20px;
            background-color: #f8d7da;
            color: #721c24;
            padding: 15px 25px;
            border-radius: 5px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            animation: slideIn 0.5s ease-out;
        }

        .notification.warning {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes slideOut {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }

        .notification.hide {
            animation: slideOut 0.5s ease-out forwards;
        }

        /* Image Modal Styles */
        .product-image {
            cursor: pointer;
            transition: transform 0.2s ease;
        }

        .product-image:hover {
            transform: scale(1.1);
        }

        .image-modal {
            display: none;
            position: fixed;
            z-index: 1000;
            padding-top: 50px;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.9);
            overflow: auto;
        }

        .modal-content {
            margin: auto;
            display: block;
            max-width: 80%;
            max-height: 80vh;
            object-fit: contain;
            animation: zoom 0.6s;
        }

        @keyframes zoom {
            from {
                transform: scale(0);
                opacity: 0;
            }
            to {
                transform: scale(1);
                opacity: 1;
            }
        }

        .close-modal {
            position: absolute;
            right: 35px;
            top: 15px;
            color: #f1f1f1;
            font-size: 40px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
        }

        .close-modal:hover {
            color: #F8B83C;
        }

        #modalCaption {
            margin: auto;
            display: block;
            width: 80%;
            max-width: 700px;
            text-align: center;
            color: #ccc;
            padding: 10px 0;
            height: 150px;
        }

        .success-message {
            position: fixed;
            top: 20px;
            right: 20px;
            background-color: #4CAF50;
            color: white;
            padding: 15px 25px;
            border-radius: 5px;
            display: none;
            z-index: 1000;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            animation: slideIn 0.5s ease-out;
        }

        .warning-message {
            background-color: #f44336;
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        /* Update the select button styling for disabled state */
        .select-btn:disabled {
            background-color: #4a4848;
            cursor: not-allowed;
            opacity: 0.7;
            transform: none;
        }

        .select-btn:disabled:hover {
            background-color: #4a4848;
            transform: none;
        }

        /* Search Section Styles */
        .filter-section {
            background-color: rgba(30, 30, 30, 0.7);
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .search-section {
            margin-bottom: 15px;
        }

        .search-container {
            width: 100%;
            max-width: 600px;
        }

        .search-input-wrapper {
            position: relative;
            width: 100%;
        }

        .search-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #AEB2B7;
        }

        .search-input {
            width: 100%;
            padding: 12px 12px 12px 40px;
            background-color: rgba(30, 30, 30, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 6px;
            color: #AEB2B7;
            font-family: 'Century Gothic', sans-serif;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .search-input:focus {
            outline: none;
            border-color: #750605;
            background-color: rgba(30, 30, 30, 0.9);
            box-shadow: 0 0 0 2px rgba(117, 6, 5, 0.2);
        }

        .search-input::placeholder {
            color: #666;
        }

        /* Category Filter Styles */
        .category-filter {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .category-filter label {
            color: #F8B83C;
            font-family: 'Century Gothic', sans-serif;
            font-size: 14px;
        }

        .category-filter select {
            padding: 8px 12px;
            background-color: rgba(30, 30, 30, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 6px;
            color: #AEB2B7;
            font-family: 'Century Gothic', sans-serif;
            font-size: 14px;
            min-width: 200px;
        }

        .category-filter select:focus {
            outline: none;
            border-color: #750605;
            background-color: rgba(30, 30, 30, 0.9);
            box-shadow: 0 0 0 2px rgba(117, 6, 5, 0.2);
        }
    </style>

</head>
<body>
            <!-- Sidebar Navigation -->
            <div class="sidebar">
                <!-- Logo Section -->
                <div class="logo-container">
                    <img src="../upload/ArtMore.jpg" alt="ArtMore Logo">
                    <h1>INVENTORY SYSTEM</h1>
                </div>
    
                <!-- Navigation Links -->
                <ul class="nav-links">
                    <!-- Sales Section -->
                    <li><a href="manageSales.php"><i class="fas fa-tasks"></i>Manage Sales</a></li>
                    <li class="active"><a href="addSales.php"><i class="fas fa-cart-plus"></i>Add Sales</a></li>

                  
                    <li><a href="dailySales.php"><i class="fas fa-calendar-day"></i>Daily Sales</a></li>
                    <!-- FAQ Link (New) -->
                    <li>
                        <a href="faq.php">
                            <i class="fas fa-question-circle"></i>FAQ
                        </a>
                    </li>
                </ul>
            </div>
    
            <!-- Top Navigation Bar -->
            <div class="topbar">
                <div class="time-label" id="currentDateTime"></div>
                <div class="profile-container">
                    <img src="<?php echo htmlspecialchars($profileImage); ?>" alt="Profile Picture">
                    <span class="profile-name"><?php echo htmlspecialchars($userName); ?></span>
                    <i class="fas fa-caret-down"></i>
                    <div class="dropdown-content">
                        <a id="logoutBtn"><i class="fas fa-sign-out-alt"></i>Logout</a>
                    </div>
                </div>
            </div>
    
            <!-- Main Content -->
            <div class="main-content">
                <div class="content-card dashboard-table">
                    <!-- Add New Sale Section -->
                    <div id="newSaleSection">
                        <div class="table-header">
                            <i class="fas fa-cart-plus"></i>
                            <h2>ADD NEW SALE</h2>
                        </div>
                        <hr class="header-line">
                        
                        <!-- Sales Table -->
                        <div class="table-container">
                            <table id="salesTable">
                                <thead>
                                    <tr>
                                        <th>Customer ID</th>
                                        <th>Item</th>
                                        <th>Price</th>
                                        <th>Qty</th>
                                        <th>Total</th>
                                        <th>Date</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody id="salesTableBody">
                                    <!-- Sales rows will be added here dynamically -->
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="4" style="text-align: right;"><strong>Grand Total:</strong></td>
                                        <td id="grandTotal" class="total-amount">₱0.00</td>
                                        <td colspan="2"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        
                        <!-- This is the only complete sale button that should exist -->
                        <div class="complete-sale-container">
                            <button id="completeSaleBtn" class="complete-sale-btn" disabled>
                                <i class="fas fa-check-circle"></i> Complete Sale
                            </button>
                        </div>
                    </div>

                    <!-- Search and Filter Section -->
                    <div class="filter-section">
                        <!-- Search Bar -->
                        <div class="search-section">
                            <div class="search-container">
                                <div class="search-input-wrapper">
                                    <i class="fas fa-search search-icon"></i>
                                    <input type="text" id="searchInput" class="search-input" placeholder="Search for products by name...">
                                </div>
                            </div>
                        </div>

                        <!-- Category Filter -->
                        <div class="category-filter">
                            <label for="category-filter">Filter by Category:</label>
                            <select id="category-filter" class="form-control">
                                <option value="">All Categories</option>
                                <?php
                                try {
                                    $stmt = $pdo->query("SELECT * FROM categories ORDER BY category_name");
                                    while ($category = $stmt->fetch()) {
                                        echo "<option value='" . htmlspecialchars($category['id']) . "'>" . 
                                             htmlspecialchars($category['category_name']) . "</option>";
                                    }
                                } catch(PDOException $e) {
                                    error_log("Error: " . $e->getMessage());
                                }
                                ?>
                            </select>
                        </div>
                    </div>

                    <!-- Products Table -->
                    <div class="table-container">
                        <table id="products-table">
                            <thead>
                                <tr>
                                    <th>Product ID</th>
                                    <th>Photo</th>
                                    <th>Product Name</th>
                                    <th>Category</th>
                                    <th>In Stock</th>
                                    <th>Price</th>
                                    <th>Select</th>
                                </tr>
                            </thead>
                            <tbody id="products-body">
                                <?php
                                try {
                                    $query = "SELECT p.*, c.category_name, c.id as category_id 
                                             FROM products p
                                             LEFT JOIN categories c ON p.category_id = c.id
                                             ORDER BY CAST(p.id AS UNSIGNED) ASC";

                                    $stmt = $pdo->prepare($query);
                                    $stmt->execute();

                                    while ($row = $stmt->fetch()) {
                                        echo "<tr data-category='" . htmlspecialchars($row['category_id']) . "'>";
                                        echo "<td>" . htmlspecialchars($row['id']) . "</td>";
                                        echo "<td><img src='../upload/" . htmlspecialchars($row['image_path']) . 
                                             "' alt='" . htmlspecialchars($row['product_title']) . 
                                             "' class='product-image' style='width: 50px; height: 50px; object-fit: cover;'></td>";
                                        echo "<td>" . htmlspecialchars($row['product_title']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['category_name']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['quantity']) . "</td>";
                                        echo "<td>₱" . number_format($row['selling_price'], 2) . "</td>";
                                        echo "<td><button class='select-btn' " . 
                                             ($row['quantity'] <= 0 ? 'disabled ' : '') . 
                                             "data-id='" . htmlspecialchars($row['id']) . 
                                             "' data-name='" . htmlspecialchars($row['product_title']) . 
                                             "' data-price='" . $row['selling_price'] . 
                                             "' data-stock='" . $row['quantity'] . 
                                             "'><i class='fas fa-plus'></i> " . 
                                             ($row['quantity'] <= 0 ? 'Out of Stock' : 'Select') . 
                                             "</button></td>";
                                        echo "</tr>";
                                    }
                                } catch(PDOException $e) {
                                    error_log("Error: " . $e->getMessage());
                                    echo "<tr><td colspan='7'>Error loading products. Please try again.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
    
                <!-- Scripts -->
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Initialize variables
                let currentCustomerId = null;
                let salesItems = [];

                // Function to handle product selection
                function handleProductSelection(button) {
                    const productId = button.dataset.id;
                    
                    // Check if product is already in the sale table
                    const existingProduct = document.querySelector(`#salesTableBody tr[data-product-id="${productId}"]`);
                    
                    if (existingProduct) {
                        showNotification('This product is already in your sale list. Please adjust the quantity instead.');
                        return;
                    }

                    if (!currentCustomerId) {
                        currentCustomerId = generateCustomerId();
                    }

                    const productData = {
                        id: productId,
                        name: button.dataset.name,
                        price: button.dataset.price,
                        stock: button.dataset.stock
                    };

                    const newRow = createSaleRow(productData);
                    document.getElementById('salesTableBody').appendChild(newRow);
                    updateTotals();
                }

                // Add click event listeners to all select buttons
                function initializeSelectButtons() {
                    document.querySelectorAll('.select-btn').forEach(button => {
                        button.onclick = () => handleProductSelection(button);
                    });
                }

                // Initialize select buttons
                initializeSelectButtons();

                // Category filter functionality
                const categoryFilter = document.getElementById('category-filter');
                categoryFilter.addEventListener('change', function() {
                    const selectedCategory = this.value;
                    const rows = document.querySelectorAll('#products-body tr');

                    rows.forEach(row => {
                        const rowCategory = row.getAttribute('data-category');
                        if (!selectedCategory || rowCategory === selectedCategory) {
                            row.style.display = '';
                        } else {
                            row.style.display = 'none';
                        }
                    });
                });

                // Search functionality
                const searchInput = document.getElementById('searchInput');
                searchInput.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase();
                    const categoryFilter = document.getElementById('category-filter').value;
                    const rows = document.querySelectorAll('#products-table tbody tr');

                    rows.forEach(row => {
                        const productName = row.querySelector('td:nth-child(3)').textContent.toLowerCase();
                        const rowCategory = row.getAttribute('data-category');
                        
                        // Check both search term and category filter
                        const matchesSearch = productName.includes(searchTerm);
                        const matchesCategory = !categoryFilter || rowCategory === categoryFilter;

                        if (matchesSearch && matchesCategory) {
                            row.style.display = '';
                        } else {
                            row.style.display = 'none';
                        }
                    });
                });

                // Update category filter to work with search
                document.getElementById('category-filter').addEventListener('change', function() {
                    const selectedCategory = this.value;
                    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
                    const rows = document.querySelectorAll('#products-table tbody tr');

                    rows.forEach(row => {
                        const productName = row.querySelector('td:nth-child(3)').textContent.toLowerCase();
                        const rowCategory = row.getAttribute('data-category');
                        
                        const matchesSearch = productName.includes(searchTerm);
                        const matchesCategory = !selectedCategory || rowCategory === selectedCategory;

                        if (matchesSearch && matchesCategory) {
                            row.style.display = '';
                        } else {
                            row.style.display = 'none';
                        }
                    });
                });

                // Function to create a new sale row
                function createSaleRow(productData) {
                    const now = new Date();
                    const dateTimeStr = formatDateTime(now);
                    
                    const row = document.createElement('tr');
                    // Add data-product-id attribute to the row
                    row.setAttribute('data-product-id', productData.id);
                    
                    row.innerHTML = `
                        <td>${currentCustomerId}</td>
                        <td>${productData.name}</td>
                        <td>₱${parseFloat(productData.price).toFixed(2)}</td>
                        <td class="quantity-cell">
                            <div class="quantity-controls">
                                <button type="button" class="qty-btn minus" onclick="decrementQty(this)">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <input type="number" class="qty-input" value="1" min="1" max="${productData.stock}" 
                                    onchange="updateTotal(this)" onkeypress="return event.charCode >= 48 && event.charCode <= 57">
                                <button type="button" class="qty-btn plus" onclick="incrementQty(this)">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </td>
                        <td class="item-total">₱${parseFloat(productData.price).toFixed(2)}</td>
                        <td>${dateTimeStr}</td>
                        <td><button class="remove-item-btn" onclick="removeItem(this)"><i class="fas fa-trash"></i></button></td>
                    `;
                    return row;
                }

                // Add this function to format the date and time
                function formatDateTime(date) {
                    const month = String(date.getMonth() + 1).padStart(2, '0');
                    const day = String(date.getDate()).padStart(2, '0');
                    const year = date.getFullYear();
                    const hours = String(date.getHours()).padStart(2, '0');
                    const minutes = String(date.getMinutes()).padStart(2, '0');
                    const seconds = String(date.getSeconds()).padStart(2, '0');
                    
                    return `${month}/${day}/${year} ${hours}:${minutes}:${seconds}`;
                }

                // Optional: Add this if you want to update the time continuously for existing rows
                function updateExistingTimes() {
                    const now = new Date();
                    const dateTimeStr = formatDateTime(now);
                    const dateColumns = document.querySelectorAll('#salesTableBody tr td:nth-child(6)');
                    dateColumns.forEach(td => {
                        td.textContent = dateTimeStr;
                    });
                }

                // Optional: Update times every second
                // setInterval(updateExistingTimes, 1000);

                // Add these functions to window scope
                window.updateTotal = function(input) {
                    const row = input.closest('tr');
                    const price = parseFloat(row.cells[2].textContent.replace('₱', ''));
                    const quantity = parseInt(input.value);
                    const max = parseInt(input.getAttribute('max'));
                    
                    // Ensure quantity is within valid range
                    if (quantity < 1) input.value = 1;
                    if (quantity > max) input.value = max;
                    
                    const actualQty = parseInt(input.value);
                    const total = price * actualQty;
                    row.querySelector('.item-total').textContent = `₱${total.toFixed(2)}`;
                    updateTotals();
                };

                window.removeItem = function(button) {
                    const row = button.closest('tr');
                    row.remove();
                    if (document.getElementById('salesTableBody').children.length === 0) {
                        currentCustomerId = null;
                    }
                    updateTotals();
                };

                // Function to update totals
                function updateTotals() {
                    const itemTotals = document.querySelectorAll('.item-total');
                    let grandTotal = 0;
                    itemTotals.forEach(total => {
                        grandTotal += parseFloat(total.textContent.replace('₱', ''));
                    });
                    document.getElementById('grandTotal').textContent = `₱${grandTotal.toFixed(2)}`;
                }

                // Function to generate customer ID
                function generateCustomerId() {
                    // Generate a random 5-character string (numbers and uppercase letters)
                    const chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
                    let result = 'CTM_';
                    for (let i = 0; i < 5; i++) {
                        result += chars.charAt(Math.floor(Math.random() * chars.length));
                    }
                    return result;
                }

                // Add these new functions to handle quantity changes
                window.incrementQty = function(button) {
                    const input = button.parentElement.querySelector('.qty-input');
                    const max = parseInt(input.getAttribute('max'));
                    const currentValue = parseInt(input.value);
                    if (currentValue < max) {
                        input.value = currentValue + 1;
                        updateTotal(input);
                    }
                };

                window.decrementQty = function(button) {
                    const input = button.parentElement.querySelector('.qty-input');
                    const currentValue = parseInt(input.value);
                    if (currentValue > 1) {
                        input.value = currentValue - 1;
                        updateTotal(input);
                    }
                };

                const completeSaleBtn = document.getElementById('completeSaleBtn');

                // Function to update button state
                function updateCompleteSaleButton() {
                    const hasItems = document.getElementById('salesTableBody').children.length > 0;
                    completeSaleBtn.disabled = !hasItems;
                }

                // Modify your existing updateTotals function
                function updateTotals() {
                    const itemTotals = document.querySelectorAll('.item-total');
                    let grandTotal = 0;
                    itemTotals.forEach(total => {
                        grandTotal += parseFloat(total.textContent.replace('₱', ''));
                    });
                    document.getElementById('grandTotal').textContent = `₱${grandTotal.toFixed(2)}`;
                    
                    // Update complete sale button state
                    updateCompleteSaleButton();
                }

                // Add click handler for complete sale button
                completeSaleBtn.addEventListener('click', async function() {
                    if (!confirm('Are you sure you want to complete this sale?')) {
                        return;
                    }

                    try {
                        // Gather all sale items
                        const items = [];
                        const rows = document.querySelectorAll('#salesTableBody tr');
                        
                        rows.forEach(row => {
                            const productId = row.getAttribute('data-product-id');
                            const quantity = parseInt(row.querySelector('.qty-input').value);
                            const price = parseFloat(row.cells[2].textContent.replace('₱', ''));
                            const total = parseFloat(row.querySelector('.item-total').textContent.replace('₱', ''));
                            
                            items.push({
                                productId: productId,
                                quantity: quantity,
                                price: price,
                                total: total
                            });
                        });

                        // Get grand total
                        const grandTotal = parseFloat(document.getElementById('grandTotal').textContent.replace('₱', ''));

                        // Prepare data for server
                        const saleData = {
                            action: 'saveSale',
                            customerId: currentCustomerId,
                            items: items,
                            grandTotal: grandTotal
                        };

                        // Update the fetch URL to point to the current file
                        const response = await fetch('addSales.php', {  // Changed from manageSales.php
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify(saleData)
                        });

                        // Check if response is JSON
                        const contentType = response.headers.get('content-type');
                        if (!contentType || !contentType.includes('application/json')) {
                            throw new Error('Server returned non-JSON response');
                        }

                        const result = await response.json();

                        if (result.success) {
                            // Show success message
                            const successMessage = document.createElement('div');
                            successMessage.className = 'success-message';
                            successMessage.innerHTML = `
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <i class="fas fa-check-circle"></i>
                                    <span>Sale completed successfully!</span>
                                </div>
                            `;
                            document.body.appendChild(successMessage);
                            successMessage.style.display = 'block';

                            // Remove the message after 3 seconds
                            setTimeout(() => {
                                successMessage.style.opacity = '0';
                                successMessage.style.transform = 'translateX(100%)';
                                setTimeout(() => {
                                    successMessage.remove();
                                }, 500);
                            }, 3000);
                            
                            // Clear the sales table
                            document.getElementById('salesTableBody').innerHTML = '';
                            
                            // Reset customer ID
                            currentCustomerId = null;
                            
                            // Update totals
                            updateTotals();

                            // Refresh the products table to show updated quantities
                            setTimeout(() => {
                                location.reload();
                            }, 3000);
                        } else {
                            throw new Error(result.message);
                        }

                    } catch (error) {
                        // Show error message
                        const errorMessage = document.createElement('div');
                        errorMessage.className = 'success-message warning-message';
                        errorMessage.innerHTML = `
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <i class="fas fa-exclamation-circle"></i>
                                <span>Error: ${error.message}</span>
                            </div>
                        `;
                        document.body.appendChild(errorMessage);
                        errorMessage.style.display = 'block';

                        // Remove the error message after 3 seconds
                        setTimeout(() => {
                            errorMessage.style.opacity = '0';
                            errorMessage.style.transform = 'translateX(100%)';
                            setTimeout(() => {
                                errorMessage.remove();
                            }, 500);
                        }, 3000);

                        console.error('Error details:', error);
                    }
                });

                // Add this function for different types of notifications
                function showNotification(message, type = 'success') {
                    const notification = document.getElementById('notification');
                    const messageElement = document.getElementById('notification-message');
                    
                    // Set notification type
                    notification.className = 'notification';
                    notification.classList.add(type === 'success' ? 'success' : 'warning');
                    
                    messageElement.textContent = message;
                    notification.style.display = 'block';
                    
                    setTimeout(() => {
                        notification.classList.add('hide');
                        setTimeout(() => {
                            notification.style.display = 'none';
                            notification.classList.remove('hide');
                        }, 500);
                    }, 3000);
                }

                // Image Modal functionality
                const modal = document.getElementById('imageModal');
                const modalImg = document.getElementById('modalImage');
                const captionText = document.getElementById('modalCaption');
                const closeBtn = document.getElementsByClassName('close-modal')[0];

                // Add click event to all product images
                document.querySelectorAll('.product-image').forEach(img => {
                    img.onclick = function() {
                        modal.style.display = "block";
                        modalImg.src = this.src;
                        captionText.innerHTML = this.alt;
                        
                        // Prevent scrolling of the background
                        document.body.style.overflow = 'hidden';
                    }
                });

                // Close modal when clicking the × button
                closeBtn.onclick = function() {
                    closeModal();
                }

                // Close modal when clicking outside the image
                modal.onclick = function(event) {
                    if (event.target === modal) {
                        closeModal();
                    }
                }

                // Close modal when pressing ESC key
                document.addEventListener('keydown', function(event) {
                    if (event.key === 'Escape' && modal.style.display === 'block') {
                        closeModal();
                    }
                });

                function closeModal() {
                    modal.style.display = "none";
                    document.body.style.overflow = 'auto';
                }
            });
        </script>

        <!-- Add this right after your main-content div -->
        <div id="successMessage" class="success-message">
            <div class="success-content">
                <i class="fas fa-check-circle"></i>
                <p>Sale added successfully!</p>
            </div>
        </div>

        <!-- Add this JavaScript for category filtering -->
        <script>
        document.getElementById('category-filter').addEventListener('change', function() {
            const selectedCategory = this.value;
            const rows = document.querySelectorAll('#products-table tbody tr');

            rows.forEach(row => {
                if (!selectedCategory || row.dataset.category === selectedCategory) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
        </script>

        <div id="notification" class="notification warning">
            <div class="notification-content">
                <i class="fas fa-exclamation-circle"></i>
                <span id="notification-message"></span>
            </div>
        </div>

        <!-- Add this HTML for the image modal at the end of your body tag -->
        <div id="imageModal" class="image-modal">
            <span class="close-modal">&times;</span>
            <img id="modalImage" class="modal-content" src="" alt="Product Image">
            <div id="modalCaption"></div>
        </div>

        <!-- Add this before closing body tag -->
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Initialize Variables
                const profileContainer = document.querySelector('.profile-container');
                const dropdownContent = document.querySelector('.dropdown-content');
                let isDropdownOpen = false;

                // Profile Dropdown Functions
                function openProfileDropdown() {
                    dropdownContent.classList.add('show-dropdown');
                    profileContainer.classList.add('active');
                    isDropdownOpen = true;
                }

                function closeProfileDropdown() {
                    dropdownContent.classList.remove('show-dropdown');
                    profileContainer.classList.remove('active');
                    isDropdownOpen = false;
                }

                // Profile Container Click Handler
                profileContainer.addEventListener('click', function(e) {
                    e.stopPropagation();
                    if (isDropdownOpen) {
                        closeProfileDropdown();
                    } else {
                        openProfileDropdown();
                    }
                });

                // Close profile dropdown when clicking outside
                document.addEventListener('click', function(e) {
                    if (isDropdownOpen && !dropdownContent.contains(e.target)) {
                        closeProfileDropdown();
                    }
                });

                // Sidebar Dropdowns
                const dropdowns = document.querySelectorAll('.nav-links .dropdown');
                
                dropdowns.forEach(dropdown => {
                    const toggleBtn = dropdown.querySelector('.dropdown-toggle');
                    
                    toggleBtn.addEventListener('click', (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        
                        // Close other dropdowns
                        dropdowns.forEach(other => {
                            if (other !== dropdown && other.classList.contains('open')) {
                                other.classList.remove('open');
                            }
                        });
                        
                        // Toggle current dropdown
                        dropdown.classList.toggle('open');
                    });
                });

                // Close dropdowns when clicking outside
                document.addEventListener('click', function(e) {
                    if (!e.target.closest('.dropdown')) {
                        dropdowns.forEach(dropdown => {
                            if (!dropdown.contains(e.target)) {
                                dropdown.classList.remove('open');
                            }
                        });
                    }
                });

                // Time update function
                function updateDateTime() {
                    const now = new Date();
                    const options = { 
                        year: 'numeric', 
                        month: 'long', 
                        day: 'numeric',
                        hour: '2-digit', 
                        minute: '2-digit',
                        second: '2-digit',
                        hour12: true 
                    };
                    document.getElementById('currentDateTime').textContent = now.toLocaleDateString('en-US', options);
                }

                // Update time immediately and then every second
                updateDateTime();
                setInterval(updateDateTime, 1000);

                // Logout confirmation
                const logoutBtn = document.getElementById('logoutBtn');
                if (logoutBtn) {
                    logoutBtn.addEventListener('click', function(e) {
                        e.preventDefault();
                        if (confirm('Are you sure you want to logout?')) {
                            window.location.href = '../admin/logout.php';
                        }
                    });
                }
            });
        </script>
</body>
</html>
