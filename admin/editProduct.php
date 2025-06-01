<?php
// Add these lines at the very top of your file, after the error reporting
session_start(); // Make sure this is at the very top
// Add these lines at the very top of your file
error_reporting(E_ALL);
ini_set('display_errors', 0); // Disable HTML error output

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

// Check if product ID is provided
if (!isset($_GET['id'])) {
    header('Location: manageProducts.php');
    exit();
}

$product_id = $_GET['id'];

// Fetch product details from database
try {
    // Modified query to include category information
    $query = "SELECT p.*, c.category_name 
              FROM products p 
              LEFT JOIN categories c ON p.category_id = c.id 
              WHERE p.id = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$product_id]);
    
    if ($stmt->rowCount() === 0) {
        header('Location: manageProducts.php');
        exit();
    }

    $product = $stmt->fetch();

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            header('Content-Type: application/json');
            
            $title = trim($_POST['productTitle']);
            $category_id = $_POST['productCategory'];
            $quantity = $_POST['quantity'];
            $buying_price = $_POST['buyingPrice'];
            $selling_price = $_POST['sellingPrice'];
            
            // Handle image upload if new image is provided
            $image_path = $product['image_path']; // Keep existing image by default
            
            if (isset($_FILES['productImage']) && $_FILES['productImage']['error'] === 0) {
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                $file_type = $_FILES['productImage']['type'];
                
                if (!in_array($file_type, $allowed_types)) {
                    throw new Exception("Invalid file type. Only JPG, PNG and GIF are allowed.");
                }
                
                $upload_dir = '../upload/products/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $file_extension = pathinfo($_FILES['productImage']['name'], PATHINFO_EXTENSION);
                $new_filename = uniqid() . '.' . $file_extension;
                $upload_path = $upload_dir . $new_filename;
                
                if (!move_uploaded_file($_FILES['productImage']['tmp_name'], $upload_path)) {
                    throw new Exception("Failed to upload image");
                }
                
                if ($product['image_path'] && 
                    file_exists($product['image_path']) && 
                    $product['image_path'] !== '../upload/default-product.jpg') {
                    unlink($product['image_path']);
                }
                
                $image_path = $upload_path;
            }
            
            // Start transaction
            $pdo->beginTransaction();
            
            // Update product in database
            $update_query = "UPDATE products SET 
                            product_title = ?, 
                            category_id = ?, 
                            quantity = ?, 
                            buying_price = ?, 
                            selling_price = ?, 
                            image_path = ?,
                            updated_at = CURRENT_TIMESTAMP
                            WHERE id = ?";
            
            $update_stmt = $pdo->prepare($update_query);
            $update_success = $update_stmt->execute([
                $title, 
                $category_id, 
                $quantity, 
                $buying_price, 
                $selling_price, 
                $image_path,
                $product_id
            ]);
            
            if ($update_success) {
                // Create changes array to track actual modifications
                $changes = [];
                
                // Only log fields that actually changed
                if ($product['product_title'] !== $title) {
                    $changes['product_title'] = [
                        'old' => $product['product_title'],
                        'new' => $title
                    ];
                }
                
                if ($product['category_id'] !== $category_id) {
                    $changes['category_id'] = [
                        'old' => $product['category_id'],
                        'new' => $category_id
                    ];
                }
                
                if ($product['quantity'] !== $quantity) {
                    $changes['quantity'] = [
                        'old' => $product['quantity'],
                        'new' => $quantity
                    ];
                }
                
                if ($product['buying_price'] !== $buying_price) {
                    $changes['buying_price'] = [
                        'old' => $product['buying_price'],
                        'new' => $buying_price
                    ];
                }
                
                if ($product['selling_price'] !== $selling_price) {
                    $changes['selling_price'] = [
                        'old' => $product['selling_price'],
                        'new' => $selling_price
                    ];
                }
                
                if ($product['image_path'] !== $image_path) {
                    $changes['image_path'] = [
                        'old' => $product['image_path'],
                        'new' => $image_path
                    ];
                }
                
                // Only log if there were actual changes
                if (!empty($changes)) {
                    $log_query = "INSERT INTO edit_history (
                        user_id,
                        action,
                        item_type,
                        item_id,
                        changes,
                        timestamp
                    ) VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP)";

                $log_stmt = $pdo->prepare($log_query);
                $log_stmt->execute([
                    1, // Replace with actual user ID from session
                    'edit',
                    'product',
                    $product_id,
                    json_encode($changes)
                ]);
            }

                $pdo->commit();
                echo json_encode(['success' => true]);
                exit();
            }
            
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("Error in product update: " . $e->getMessage());
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit();
        }
    }
} catch(PDOException $e) {
    // Handle any database errors
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    exit();
}
?><!DOCTYPE html>
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
            margin-top: 60px;
            padding: 30px 40px;
            position: relative;
        }

        .content-card {
            background-color: rgba(30, 30, 30, 0.7);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .content-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.3);
            background-color: rgba(30, 30, 30, 0.8);
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

        .form-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }

        .form-header i {
            color: #F8B83C;
            font-size: 24px;
        }

        .form-header h2 {
            color: #AEB2B7;
            font-family: 'Century Gothic', sans-serif;
            font-size: 20px;
            font-weight: bold;
        }

        .product-form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .form-row {
            display: flex;
            gap: 20px;
            align-items: flex-start;
        }

        .input-group {
            display: flex;
            align-items: center;
            background: #2d2d2d;
            border-radius: 5px;
            padding: 5px;
            flex: 1;
        }

        .icon-placeholder {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #F8B83C;
        }

        .input-group input,
        .input-group select {
            flex: 1;
            background: transparent;
            border: none;
            color: #AEB2B7;
            padding: 10px;
            font-family: 'Century Gothic', sans-serif;
        }

        .input-group input:focus,
        .input-group select:focus {
            outline: none;
        }

        .spinner-buttons {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .spinner-buttons button {
            background: none;
            border: none;
            color: #AEB2B7;
            cursor: pointer;
            padding: 2px 8px;
        }

        .spinner-buttons button:hover {
            color: #F8B83C;
        }

        .file-input-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        #chooseImageBtn {
            background: #750605;
            color: #F8B83C;
            border: none;
            padding: 12px 20px;
            border-radius: 5px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            font-family: 'Century Gothic', sans-serif;
        }

        #fileName {
            color: #AEB2B7;
            font-family: 'Century Gothic', sans-serif;
        }

        .submit-btn {
            background: #750605;
            color: #F8B83C;
            border: none;
            padding: 15px;
            border-radius: 5px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-family: 'Century Gothic', sans-serif;
            margin-top: 20px;
            transition: all 0.3s ease;
        }

        .submit-btn:hover {
            background: #8f0806;
            transform: translateY(-2px);
        }

        .message-box {
            display: none;
            align-items: center;
            gap: 10px;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
            font-family: 'Century Gothic', sans-serif;
        }

        .message-box.success {
            background: rgba(76, 175, 80, 0.1);
            color: #4CAF50;
            border: 1px solid #4CAF50;
        }

        .message-box i {
            font-size: 20px;
        }

        .message-box span {
            font-size: 14px;
        }

        .input-group.error input {
            border: 1px solid #F44336;
        }

        .input-group.error .error-message {
            color: #F44336;
            font-size: 12px;
            margin-top: 5px;
        }

        .input-group input[type="number"] {
            -moz-appearance: textfield; /* Firefox */
            appearance: textfield; /* Standard syntax */
        }

        .input-group input[type="number"]::-webkit-outer-spin-button,
        .input-group input[type="number"]::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        .input-group.error::after {
            content: attr(data-error);
            color: #F44336;
            font-size: 12px;
            position: absolute;
            bottom: -20px;
            left: 0;
            font-family: 'Century Gothic', sans-serif;
        }

        /* Add these styles */
        .toast-message {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 25px;
            background: rgba(76, 175, 80, 0.9);
            color: white;
            border-radius: 5px;
            font-family: 'Century Gothic', sans-serif;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            z-index: 1000;
            animation: slideIn 0.5s ease forwards;
        }

        .toast-message i {
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

        .back-btn {
            background-color: #2d2d2d;
            color: #AEB2B7;
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
            margin-bottom: 20px;
        }

        .back-btn:hover {
            background-color: #363636;
            color: #F8B83C;
            transform: translateY(-2px);
        }

        .submit-btn:disabled {
            background-color: #4a4a4a;
            cursor: not-allowed;
            transform: none;
        }

        .submit-btn:disabled:hover {
            background-color: #4a4a4a;
            transform: none;
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
                    <!-- Dashboard Link -->
                    <li>
                        <a href="dashboard.php">
                            <i class="fas fa-tachometer-alt"></i>Dashboard
                        </a>
                    </li>
                    
                    <!-- User Management Dropdown -->
                    <li class="dropdown">
                        <a href="#" class="dropdown-toggle">
                            <i class="fas fa-users"></i>User Management
                            <i class="fas fa-chevron-down arrow"></i>
                        </a>
                        <ul class="submenu">
                            <li><a href="manageGroups.php><i class="fas fa-users-cog"></i>Manage Groups</a></li>
                            <li><a href="manageUsers.php"><i class="fas fa-user-cog"></i>Manage Users</a></li>
                        </ul>
                    </li>
                    
                    <!-- Categories Link -->
                    <li>
                        <a href="categories.php">
                            <i class="fas fa-tags"></i>Categories
                        </a>
                    </li>
                    
                    <!-- Products Dropdown -->
                    <li class="dropdown open">
                        <a href="#" class="dropdown-toggle">
                            <i class="fas fa-box"></i>Products
                            <i class="fas fa-chevron-down arrow"></i>
                        </a>
                        <ul class="submenu">
                            <li class="active"><a href="manageProducts.php"><i class="fas fa-boxes"></i>Manage Products</a></li>
                            <li><a href="addProducts.php"><i class="fas fa-plus-circle"></i>Add Products</a></li>
                        </ul>
                    </li>
                    
                    <!-- Sales Dropdown -->
                    <li class="dropdown">
                        <a href="#" class="dropdown-toggle">
                            <i class="fas fa-shopping-cart"></i>Sales
                            <i class="fas fa-chevron-down arrow"></i>
                        </a>
                        <ul class="submenu">
                            <li><a href="manageSales.php"><i class="fas fa-tasks"></i>Manage Sales</a></li>
                            <li><a href="addSales.php"><i class="fas fa-cart-plus"></i>Add Sales</a></li>
                        </ul>
                    </li>
                    
                    <!-- Sales Report Dropdown -->
                    <li class="dropdown">
                        <a href="#" class="dropdown-toggle">
                            <i class="fas fa-chart-bar"></i>Sales Report
                            <i class="fas fa-chevron-down arrow"></i>
                        </a>
                        <ul class="submenu">
                            <li><a href="salesByDate.php"><i class="fas fa-calendar-alt"></i>Sales by Date</a></li>
                            <li><a href="monthlySales.php"><i class="fas fa-calendar-check"></i>Monthly Sales</a></li>
                            <li><a href="dailySales.php"><i class="fas fa-calendar-day"></i>Daily Sales</a></li>
                        </ul>
                    </li>
                                    <!-- System Logs Dropdown -->
                <li class="dropdown">
                    <a href="#" class="dropdown-toggle">
                        <i class="fas fa-history"></i>System Logs
                        <i class="fas fa-chevron-down arrow"></i>
                    </a>
                    <ul class="submenu">
                        <li><a href="editHistory.php"><i class="fas fa-pen"></i>Edit History</a></li>
                        <li><a href="loginHistory.php"><i class="fas fa-sign-in-alt"></i>Login History</a></li>
                        </ul>
                    </li>
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
                <div class="time-label" id="currentDateTime">Loading...</div>
                <div class="profile-container">
                    <img src="<?php echo htmlspecialchars($profileImage); ?>" alt="Profile Picture">
                    <span class="profile-name"><?php echo htmlspecialchars($userName); ?></span>
                    <i class="fas fa-caret-down"></i>
                    <div class="dropdown-content">
                        <a href="profile.php" id="profileBtn"><i class="fas fa-user"></i>Profile</a>
                        <a href="settings.php" id="settingsBtn"><i class="fas fa-cog"></i>Settings</a>
                        <a id="logoutBtn"><i class="fas fa-sign-out-alt"></i>Logout</a>
                    </div>
                </div>
            </div>
    
            <!-- Main Content -->
            <div class="main-content">
                <div class="content-card">
                    <div class="form-header">
                        <i class="fas fa-edit"></i>
                        <h2>UPDATE THE PRODUCT</h2>
                    </div>
                    <hr class="header-line">
                    
                    <div class="header-actions">
                        <a href="manageProducts.php" class="back-btn">
                            <i class="fas fa-arrow-left"></i>
                            Back to Products
                        </a>
                    </div>

                    <form id="editProductForm" class="product-form" method="post" enctype="multipart/form-data">
                        <div class="form-row">
                            <div class="input-group">
                                <div class="icon-placeholder">
                                    <i class="fas fa-cube"></i>
                                </div>
                                <input type="text" id="productTitle" name="productTitle" 
                                       value="<?php echo htmlspecialchars($product['product_title']); ?>" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="input-group">
                                <div class="icon-placeholder">
                                    <i class="fas fa-tags"></i>
                                </div>
                                <select id="productCategory" name="productCategory" required>
                                    <option value="">Select Product Category</option>
                                    <?php
                                    // Fetch categories from database
                                    $cat_query = "SELECT * FROM categories ORDER BY category_name";
                                    $cat_stmt = $pdo->query($cat_query);
                                    while ($category = $cat_stmt->fetch()) {
                                        $selected = ($category['id'] == $product['category_id']) ? 'selected' : '';
                                        echo "<option value='{$category['id']}' {$selected}>" . 
                                             htmlspecialchars($category['category_name']) . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="file-input-group">
                                <input type="file" id="productImage" name="productImage" accept="image/*" hidden>
                                <button type="button" id="chooseImageBtn">
                                    <i class="fas fa-image"></i>
                                    Choose Image
                                </button>
                                <span id="fileName"><?php echo basename($product['image_path'] ?? 'No image selected'); ?></span>
                            </div>
                        </div>

                        <div class="numeric-inputs">
                            <div class="input-group">
                                <div class="icon-placeholder">
                                    <i class="fas fa-boxes"></i>
                                </div>
                                <input type="number" id="quantity" name="quantity" 
                                       value="<?php echo htmlspecialchars($product['quantity']); ?>" 
                                       min="1" step="1" required>
                                <div class="spinner-buttons">
                                    <button type="button" class="spinner-up"><i class="fas fa-chevron-up"></i></button>
                                    <button type="button" class="spinner-down"><i class="fas fa-chevron-down"></i></button>
                                </div>
                            </div>

                            <div class="input-group">
                                <div class="icon-placeholder">
                                    <i class="fas fa-tags"></i>
                                </div>
                                <input type="number" id="buyingPrice" name="buyingPrice" 
                                       value="<?php echo htmlspecialchars($product['buying_price']); ?>" 
                                       min="0.01" step="0.01" required>
                                <div class="spinner-buttons">
                                    <button type="button" class="spinner-up"><i class="fas fa-chevron-up"></i></button>
                                    <button type="button" class="spinner-down"><i class="fas fa-chevron-down"></i></button>
                                </div>
                            </div>

                            <div class="input-group">
                                <div class="icon-placeholder">
                                    <i class="fas fa-dollar-sign"></i>
                                </div>
                                <input type="number" id="sellingPrice" name="sellingPrice" 
                                       value="<?php echo htmlspecialchars($product['selling_price']); ?>" 
                                       min="0.01" step="0.01" required>
                                <div class="spinner-buttons">
                                    <button type="button" class="spinner-up"><i class="fas fa-chevron-up"></i></button>
                                    <button type="button" class="spinner-down"><i class="fas fa-chevron-down"></i></button>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="submit-btn" disabled>
                            <i class="fas fa-save"></i>
                            Update Product
                        </button>
                    </form>

                    <div id="successMessage" class="message-box success" style="display: none;">
                        <i class="fas fa-check-circle"></i>
                        <span>Successfully updated the product information!</span>
                    </div>
                </div>
            </div>
    
                <!-- Scripts -->
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Add datetime update function at the start
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
                    const formattedDateTime = now.toLocaleDateString('en-US', options);
                    document.getElementById('currentDateTime').textContent = formattedDateTime;
                }

                // Update immediately and then every second
                updateDateTime();
                setInterval(updateDateTime, 1000);

                // Initialize Variables
                const profileContainer = document.querySelector('.profile-container');
                const dropdownContent = document.querySelector('.dropdown-content');
                const profileBtn = document.getElementById('profileBtn');
                const settingsBtn = document.getElementById('settingsBtn');
                const logoutBtn = document.getElementById('logoutBtn');
                let isDropdownOpen = false;

                // Dropdown Functions
                function openDropdown() {
                    dropdownContent.classList.add('show-dropdown');
                    profileContainer.classList.add('active');
                    isDropdownOpen = true;
                }

                function closeDropdown() {
                    dropdownContent.classList.remove('show-dropdown');
                    profileContainer.classList.remove('active');
                    isDropdownOpen = false;
                }

                // Event Listeners
                profileContainer.addEventListener('click', function(e) {
                    e.stopPropagation();
                    if (isDropdownOpen) {
                        closeDropdown();
                    } else {
                        openDropdown();
                    }
                });

                document.addEventListener('click', function(e) {
                    if (isDropdownOpen && !dropdownContent.contains(e.target)) {
                        closeDropdown();
                    }
                });

                // Navigation Handlers
                profileBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    window.location.href = 'profile.php';
                });

                settingsBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    window.location.href = 'settings.php';
                });

                logoutBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const confirmLogout = confirm('Are you sure you want to logout?');
                    
                    if (confirmLogout) {
                        window.location.href = 'logout.php';
                    }
                });

                // Keyboard Accessibility
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape' && isDropdownOpen) {
                        closeDropdown();
                    }
                });

                // Sidebar Dropdowns
                const dropdowns = document.querySelectorAll('.dropdown');
                dropdowns.forEach(dropdown => {
                    const toggleBtn = dropdown.querySelector('.dropdown-toggle');
                    
                    toggleBtn.addEventListener('click', (e) => {
                        e.preventDefault();
                        
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

                // Form handling
                const form = document.getElementById('editProductForm');
                const messageBox = document.getElementById('messageBox');
                const submitBtn = form.querySelector('.submit-btn');
                const numericInputs = ['quantity', 'buyingPrice', 'sellingPrice'];
                const originalValues = {};

                // Add this new function to validate prices
                function validatePrices() {
                    const buyingPrice = parseFloat(document.getElementById('buyingPrice').value);
                    const sellingPrice = parseFloat(document.getElementById('sellingPrice').value);
                    const buyingPriceGroup = document.getElementById('buyingPrice').parentElement;
                    const sellingPriceGroup = document.getElementById('sellingPrice').parentElement;

                    if (buyingPrice >= sellingPrice) {
                        buyingPriceGroup.classList.add('error');
                        sellingPriceGroup.classList.add('error');
                        buyingPriceGroup.setAttribute('data-error', 'Buying price must be less than selling price');
                        sellingPriceGroup.setAttribute('data-error', 'Selling price must be greater than buying price');
                        return false;
                    }

                    buyingPriceGroup.classList.remove('error');
                    sellingPriceGroup.classList.remove('error');
                    buyingPriceGroup.removeAttribute('data-error');
                    sellingPriceGroup.removeAttribute('data-error');
                    return true;
                }

                // Modify the validateForm function
                function validateForm() {
                    let isValid = true;
                    const requiredFields = ['productTitle', 'productCategory', 'quantity', 'buyingPrice', 'sellingPrice'];
                    
                    requiredFields.forEach(fieldId => {
                        const input = document.getElementById(fieldId);
                        const value = input.value.trim();
                        
                        if (!value) {
                            isValid = false;
                            input.parentElement.classList.add('error');
                            input.parentElement.setAttribute('data-error', 'This field is required');
                        } else {
                            input.parentElement.classList.remove('error');
                            input.parentElement.removeAttribute('data-error');
                        }
                    });
                    
                    // Add price validation
                    if (!validatePrices()) {
                        isValid = false;
                    }
                    
                    return isValid;
                }

                // Add this function to check if the form has changes
                function checkFormChanges() {
                    const inputs = form.querySelectorAll('input, select');
                    let hasChanges = false;

                    inputs.forEach(input => {
                        const originalValue = originalValues[input.id];
                        if (input.type === 'file') {
                            if (input.files.length > 0) {
                                hasChanges = true;
                            }
                        } else if (input.value !== originalValue) {
                            hasChanges = true;
                        }
                    });

                    // Enable the submit button if there are changes and the form is valid
                    submitBtn.disabled = !(hasChanges && validateForm());
                }

                // Store original values of the form fields
                function storeOriginalValues() {
                    const inputs = form.querySelectorAll('input, select');
                    inputs.forEach(input => {
                        originalValues[input.id] = input.type === 'file' ? '' : input.value;
                    });
                }

                // Initialize form
                storeOriginalValues();

                // Add change event listeners to all inputs
                const inputs = form.querySelectorAll('input, select');
                inputs.forEach(input => {
                    input.addEventListener('input', checkFormChanges);
                    input.addEventListener('change', checkFormChanges);
                });

                // Form submission
                form.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    console.log('Form submitted'); // Debug log
                    
                    if (validateForm()) {
                        const confirmUpdate = confirm('Are you sure you want to update this product?');
                        
                        if (confirmUpdate) {
                            const formData = new FormData(form);
                            
                            try {
                                const response = await fetch(window.location.href, {
                                    method: 'POST',
                                    body: formData
                                });
                                
                                console.log('Response status:', response.status);
                                const responseText = await response.text();
                                console.log('Raw response:', responseText);
                                
                                let result;
                                try {
                                    result = JSON.parse(responseText);
                                } catch (e) {
                                    console.error('Failed to parse JSON:', e);
                                    alert('Server returned invalid response. Check console for details.');
                                    return;
                                }
                                
                                if (result.success) {
                                    // Show success message
                                    const successMessage = document.getElementById('successMessage');
                                    successMessage.style.display = 'flex';

                                    // Create and show toast
                                    const toast = document.createElement('div');
                                    toast.className = 'toast-message';
                                    toast.innerHTML = `
                                        <i class="fas fa-check-circle"></i>
                                        <span>Product successfully updated!</span>
                                    `;
                                    document.body.appendChild(toast);

                                    // Redirect after delay
                                    setTimeout(() => {
                                        window.location.href = 'manageProducts.php';
                                    }, 2000);
                                } else {
                                    alert('Error updating product: ' + (result.error || 'Unknown error'));
                                }
                            } catch (error) {
                                console.error('Error:', error);
                                alert('Error updating product: ' + error.message);
                            }
                        }
                    }
                });

                // File input handling
                const chooseImageBtn = document.getElementById('chooseImageBtn');
                const productImage = document.getElementById('productImage');
                const fileName = document.getElementById('fileName');

                chooseImageBtn.addEventListener('click', () => {
                    productImage.click();
                });

                productImage.addEventListener('change', (e) => {
                    if (e.target.files.length > 0) {
                        fileName.textContent = e.target.files[0].name;
                        // Enable submit button when new image is selected
                        submitBtn.disabled = false;
                    } else {
                        fileName.textContent = 'No file chosen';
                    }
                });

                // Numeric input handling
                numericInputs.forEach(id => {
                    const input = document.getElementById(id);
                    const group = input.parentElement;
                    const up = group.querySelector('.spinner-up');
                    const down = group.querySelector('.spinner-down');

                    function validateNumber(value) {
                        const num = parseFloat(value);
                        if (id === 'quantity') {
                            return !isNaN(num) && Number.isInteger(num) && num > 0;
                        } else {
                            return !isNaN(num) && num > 0 && /^\d+(\.\d{0,2})?$/.test(value);
                        }
                    }

                    function formatNumber(value) {
                        const num = parseFloat(value);
                        if (id === 'quantity') {
                            return Math.floor(num); // Ensure whole number for quantity
                        } else {
                            return num.toFixed(2); // Two decimal places for prices
                        }
                    }

                    input.addEventListener('input', (e) => {
                        let value = e.target.value;
                        
                        // Remove any non-numeric characters except decimal point
                        value = value.replace(/[^\d.]/g, '');
                        
                        // Ensure only one decimal point
                        const parts = value.split('.');
                        if (parts.length > 2) {
                            value = parts[0] + '.' + parts.slice(1).join('');
                        }

                        // Update input value
                        e.target.value = value;

                        if (value && validateNumber(value)) {
                            e.target.value = formatNumber(value);
                            group.classList.remove('error');
                            group.removeAttribute('data-error');
                            
                            // Add price validation on input
                            if (id === 'buyingPrice' || id === 'sellingPrice') {
                                validatePrices();
                            }
                        } else {
                            group.classList.add('error');
                            if (id === 'quantity') {
                                group.setAttribute('data-error', 'Please enter a whole number greater than 0');
                            } else {
                                group.setAttribute('data-error', 'Please enter a valid price (0.01 or greater)');
                            }
                        }
                    });

                    // Handle spinner buttons
                    up.addEventListener('click', () => {
                        const current = parseFloat(input.value) || 0;
                        const increment = id === 'quantity' ? 1 : 0.01;
                        input.value = formatNumber(current + increment);
                        group.classList.remove('error');
                        group.removeAttribute('data-error');
                    });

                    down.addEventListener('click', () => {
                        const current = parseFloat(input.value) || 0;
                        const increment = id === 'quantity' ? 1 : 0.01;
                        if (current > increment) {
                            input.value = formatNumber(current - increment);
                            group.classList.remove('error');
                            group.removeAttribute('data-error');
                        }
                    });

                    // Prevent non-numeric input
                    input.addEventListener('keypress', (e) => {
                        const allowedChars = id === 'quantity' ? /[0-9]/ : /[0-9.]/;
                        if (!allowedChars.test(e.key)) {
                            e.preventDefault();
                        }
                        // Prevent multiple decimal points
                        if (e.key === '.' && input.value.includes('.')) {
                            e.preventDefault();
                        }
                    });

                    // Handle paste events
                    input.addEventListener('paste', (e) => {
                        e.preventDefault();
                        const pastedText = (e.clipboardData || window.clipboardData).getData('text');
                        const allowedChars = id === 'quantity' ? /^\d+$/ : /^\d*\.?\d*$/;
                        if (allowedChars.test(pastedText)) {
                            const newValue = parseFloat(pastedText);
                            if (!isNaN(newValue) && newValue > 0) {
                                input.value = formatNumber(newValue);
                            }
                        }
                    });
                });
            });
        </script>
</body>
</html>
