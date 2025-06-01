<?php
// Start the session at the very beginning of the file
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

// Get sale ID from URL parameter
$saleId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Initialize variables for sale data
$saleData = null;

// Fetch sale data if ID is provided
if ($saleId > 0) {
    try {
        $stmt = $pdo->prepare("
            SELECT s.id, s.customer_id, s.total_amount, s.sale_date,
                   si.id as sale_item_id, si.quantity as current_quantity, 
                   si.price, si.total as item_total,
                   p.id as product_id, p.product_title, p.image_path, 
                   p.quantity as available_quantity,
                   c.category_name
            FROM sales s
            JOIN sale_items si ON s.id = si.sale_id
            JOIN products p ON si.product_id = p.id
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE si.id = ?
        ");
        $stmt->execute([$_GET['item_id']]); // Use sale_item_id instead of sale_id
        $saleData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Calculate maximum allowed quantity
        $maxQuantity = $saleData['current_quantity'] + $saleData['available_quantity'];
    } catch (PDOException $e) {
        error_log("Error fetching sale data: " . $e->getMessage());
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newQuantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);
    $newPrice = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
    
    if ($newQuantity === false || $newQuantity < 1) {
        die('Invalid quantity');
    }
    
    if ($newPrice === false || $newPrice < 0) {
        die('Invalid price');
    }

    // Check if new quantity exceeds available stock
    $quantityDifference = $newQuantity - $saleData['current_quantity'];
    if ($quantityDifference > $saleData['available_quantity']) {
        die('Insufficient stock. Maximum available quantity is ' . $maxQuantity);
    }
    
    try {
        // Begin transaction
        $pdo->beginTransaction();
        
        // Update only the specific sale_items row
        $stmt = $pdo->prepare("
            UPDATE sale_items 
            SET quantity = ?, 
                price = ?,
                total = ? 
            WHERE id = ?
        ");
        
        $newTotal = $newQuantity * $newPrice;
        $stmt->execute([$newQuantity, $newPrice, $newTotal, $_GET['item_id']]);
        
        // Update products table stock
        $stmt = $pdo->prepare("
            UPDATE products 
            SET quantity = quantity + ? - ?
            WHERE id = ?
        ");
        $stmt->execute([$saleData['current_quantity'], $newQuantity, $saleData['product_id']]);
        
        // Recalculate and update total_amount in sales table
        $stmt = $pdo->prepare("
            UPDATE sales 
            SET total_amount = (
                SELECT SUM(total) 
                FROM sale_items 
                WHERE sale_id = ?
            )
            WHERE id = ?
        ");
        $stmt->execute([$saleData['id'], $saleData['id']]);
        
        // Log the changes
        $logStmt = $pdo->prepare("
            INSERT INTO activity_logs (user_id, action, details, created_at)
            VALUES (?, ?, ?, NOW())
        ");

        $changes = [
            'sale_item_id' => $_GET['item_id'],
            'quantity' => [
                'old' => $saleData['current_quantity'],
                'new' => $newQuantity
            ],
            'price' => [
                'old' => $saleData['price'],
                'new' => $newPrice
            ]
        ];

        $logStmt->execute([
            $_SESSION['user_id'],
            'Sale Item Update',
            json_encode([
                'Sale ID' => $saleData['id'],
                'Changes' => $changes
            ])
        ]);
        
        // Commit transaction
        $pdo->commit();
        
        // Redirect back to manage sales with success message
        header("Location: manageSales.php?success=1");
        exit;
        
    } catch (PDOException $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        error_log("Error updating sale: " . $e->getMessage());
        $error = "Failed to update sale. Please try again.";
    }
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
            flex-wrap: wrap;
        }

        .table-header i.fas.fa-edit {
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
            flex: 1;
            padding: 10px 15px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 5px;
            background-color: rgba(30, 30, 30, 0.8);
            color: #AEB2B7;
            font-family: 'Century Gothic', sans-serif;
        }

        .find-btn {
            background-color: #750605;
            color: #F8B83C;
            border: none;
            border-radius: 5px;
            padding: 10px 20px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .find-btn:hover {
            background-color: #8f0806;
        }

        /* Editable Input Styles */
        .editable-input {
            width: 100%;
            padding: 8px;
            background-color: rgba(30, 30, 30, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 4px;
            color: #AEB2B7;
            font-family: 'Century Gothic', sans-serif;
        }

        /* Date Picker Styles */
        .date-picker-container {
            display: flex;
            gap: 5px;
            align-items: center;
        }

        .date-input {
            width: 120px;
            padding: 8px;
            background-color: rgba(30, 30, 30, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 4px;
            color: #AEB2B7;
            font-family: 'Century Gothic', sans-serif;
        }

        .calendar-btn {
            background-color: rgba(30, 30, 30, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 4px;
            color: #F8B83C;
            cursor: pointer;
            padding: 8px 12px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .calendar-btn:hover {
            background-color: #2d2d2d;
            color: #ffca68;
        }

        .calendar-btn i {
            font-size: 16px;
        }

        /* Add Sale Button */
        .add-sale-btn {
            background-color: #750605;
            color: #F8B83C;
            border: none;
            border-radius: 4px;
            padding: 8px 15px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .add-sale-btn:hover {
            background-color: #8f0806;
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

        .update-sale-btn {
            background-color: #750605;
            color: #F8B83C;
            border: none;
            border-radius: 4px;
            padding: 8px 15px;
            cursor: not-allowed;
            transition: all 0.3s ease;
            opacity: 0.6;
        }

        .update-sale-btn:not([disabled]) {
            cursor: pointer;
            opacity: 1;
        }

        .update-sale-btn:not([disabled]):hover {
            background-color: #8f0806;
        }

        /* Back Button Styles */
        .back {
            background-color: #750605;
            color: #F8B83C;
            border: none;
            border-radius: 4px;
            padding: 8px 15px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .back:hover {
            background-color: #8f0806;
        }

        /* Back Button Styles */
        .back-btn {
            background-color: #403E3E;
            color: #AEB2B7;
            border: none;
            border-radius: 4px;
            padding: 8px 15px;
            font-family: 'Century Gothic', sans-serif;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s ease;
            margin-right: 15px;
        }

        .back-btn:hover {
            background-color: #2d2d2d;
            color: #F8B83C;
        }

        .back-btn i {
            font-size: 14px;
        }

        .editable-input[readonly] {
            background-color: rgba(30, 30, 30, 0.5);
            cursor: not-allowed;
            opacity: 0.7;
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


                        <ul class="submenu">
                            <li><a href="manageSales.php"><i class="fas fa-tasks"></i>Manage Sales</a></li>
                            <li><a href="addSales.php"><i class="fas fa-cart-plus"></i>Add Sales</a></li>


                        <ul class="submenu">

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
                <div class="time-label" id="currentDateTime">November 25, 2024, 2:13pm</div>
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
                <div class="content-card dashboard-table">
                    <!-- Remove search section and replace with Edit Sale Table -->
                    <div id="editSaleSection">
                        <div class="table-header">
                            <a href="manageSales.php" class="back-btn">
                                <i class="fas fa-arrow-left"></i>
                                Back
                            </a>
                            <i class="fas fa-edit"></i>
                            <h2>EDIT SALE</h2>
                        </div>
                        <hr class="header-line">
                        <div class="table-container">
                            <form id="updateSaleForm" method="POST">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Item</th>
                                            <th>Price</th>
                                            <th>Qty</th>
                                            <th>Total</th>
                                            <th>Date</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr id="saleDataRow">
                                            <td>
                                                <input type="text" class="editable-input item-input" 
                                                       value="<?php echo htmlspecialchars($saleData['product_title'] ?? ''); ?>" readonly>
                                            </td>
                                            <td>
                                                <input type="number" class="editable-input price-input" name="price" 
                                                       value="<?php echo htmlspecialchars($saleData['price'] ?? ''); ?>" 
                                                       step="0.01" 
                                                       readonly>
                                            </td>
                                            <td>
                                                <input type="number" 
                                                       class="editable-input qty-input" 
                                                       name="quantity" 
                                                       value="<?php echo htmlspecialchars($saleData['current_quantity'] ?? ''); ?>" 
                                                       min="1"
                                                       max="<?php echo htmlspecialchars($maxQuantity ?? ''); ?>"
                                                       data-current="<?php echo htmlspecialchars($saleData['current_quantity'] ?? ''); ?>"
                                                       data-available="<?php echo htmlspecialchars($saleData['available_quantity'] ?? ''); ?>">
                                            </td>
                                            <td>
                                                <span class="total-amount">₱<?php echo number_format($saleData['item_total'] ?? 0, 2); ?></span>
                                            </td>
                                            <td>
                                                <div class="date-picker-container">
                                                    <input type="text" class="date-input" name="sale_date" 
                                                           value="<?php echo $saleData ? date('Y-m-d', strtotime($saleData['sale_date'])) : ''; ?>"
                                                           readonly>
                                                    <button type="button" class="calendar-btn" disabled>
                                                        <i class="fas fa-calendar-alt"></i>
                                                    </button>
                                                </div>
                                            </td>
                                            <td>
                                                <button type="submit" class="update-sale-btn">Update Sale</button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
    
                <!-- Scripts -->
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Add real-time date and time update
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
                        window.location.href = '../admin/logout.php';
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

                // Add these inside your DOMContentLoaded event listener
                const saleDataRow = document.getElementById('saleDataRow');
                const itemInput = document.querySelector('.item-input');
                const priceInput = document.querySelector('.price-input');
                const qtyInput = document.querySelector('.qty-input');
                const totalAmount = document.querySelector('.total-amount');
                const dateInput = document.querySelector('.date-input');
                const calendarBtn = document.querySelector('.calendar-btn');
                const addSaleBtn = document.querySelector('.add-sale-btn');
                const successMessage = document.getElementById('successMessage');

                // Populate values and enable inputs when Find it button is clicked
                calendarBtn.addEventListener('click', () => {
                    // Enable all inputs and buttons
                    itemInput.removeAttribute('readonly');
                    priceInput.removeAttribute('readonly');
                    qtyInput.removeAttribute('readonly');
                    calendarBtn.removeAttribute('disabled');
                    addSaleBtn.removeAttribute('disabled');

                    // Set default values
                    itemInput.value = 'Art Canvas Set';
                    priceInput.value = '350.00';
                    qtyInput.value = '1';
                    
                    // Set current date
                    const today = new Date();
                    dateInput.value = today.toISOString().split('T')[0];
                    
                    // Calculate initial total
                    calculateTotal();
                });

                // Calculate total amount
                function calculateTotal() {
                    const price = parseFloat(priceInput.value) || 0;
                    const qty = parseInt(qtyInput.value) || 0;
                    const total = price * qty;
                    totalAmount.textContent = `₱${total.toFixed(2)}`;
                }

                priceInput.addEventListener('input', calculateTotal);
                qtyInput.addEventListener('input', calculateTotal);

                // Initialize date picker
                const today = new Date();
                const lastWeek = new Date(today.getTime() - 7 * 24 * 60 * 60 * 1000);

                // Set default date to today
                dateInput.value = today.toISOString().split('T')[0];

                calendarBtn.addEventListener('click', () => {
                    const datePickerInput = document.createElement('input');
                    datePickerInput.type = 'date';
                    datePickerInput.min = lastWeek.toISOString().split('T')[0];
                    datePickerInput.value = dateInput.value;
                    
                    datePickerInput.addEventListener('change', (e) => {
                        dateInput.value = e.target.value;
                        datePickerInput.remove();
                    });
                    
                    datePickerInput.click();
                    document.body.appendChild(datePickerInput);
                    datePickerInput.style.display = 'none';
                });

                addSaleBtn.addEventListener('click', () => {
                    // Show success message
                    successMessage.style.display = 'block';
                    
                    // Reset form values
                    itemInput.value = '';
                    priceInput.value = '';
                    qtyInput.value = '';
                    totalAmount.textContent = '₱0.00';
                    dateInput.value = '';
                    
                    // Disable inputs again
                    itemInput.setAttribute('readonly', true);
                    priceInput.setAttribute('readonly', true);
                    qtyInput.setAttribute('readonly', true);
                    calendarBtn.setAttribute('disabled', true);
                    addSaleBtn.setAttribute('disabled', true);
                    
                    // Hide success message after 3 seconds
                    setTimeout(() => {
                        successMessage.classList.add('hide');
                        setTimeout(() => {
                            successMessage.style.display = 'none';
                            successMessage.classList.remove('hide');
                        }, 500);
                    }, 3000);
                });

                // Add new code for edit functionality
                const inputs = document.querySelectorAll('.editable-input, .date-input');
                const updateBtn = document.querySelector('.update-sale-btn');
                let originalValues = {};

                // Store original values
                inputs.forEach(input => {
                    originalValues[input.className] = input.value;
                });

                // Add change listeners to all inputs
                inputs.forEach(input => {
                    input.addEventListener('input', () => {
                        checkForChanges();
                        if (input.classList.contains('price-input') || input.classList.contains('qty-input')) {
                            calculateTotal();
                        }
                    });
                });

                // Calculate total function
                function calculateTotal() {
                    const price = parseFloat(document.querySelector('.price-input').value) || 0;
                    const qty = parseInt(document.querySelector('.qty-input').value) || 0;
                    const total = price * qty;
                    totalAmount.textContent = `₱${total.toFixed(2)}`;
                }

                // Check for changes function
                function checkForChanges() {
                    let hasChanges = false;
                    inputs.forEach(input => {
                        if (input.value !== originalValues[input.className]) {
                            hasChanges = true;
                        }
                    });
                    updateBtn.disabled = !hasChanges;
                }

                // Update form submission handler
                const updateForm = document.getElementById('updateSaleForm');
                updateForm.addEventListener('submit', (e) => {
                    e.preventDefault();
                    
                    // Create form data
                    const formData = new FormData(updateForm);
                    
                    // Submit form using fetch
                    fetch(window.location.href, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => {
                        if (response.ok) {
                            // Show success message
                            const successMessage = document.querySelector('.success-message');
                            if (successMessage) {
                                successMessage.style.display = 'block';
                                
                                // Hide success message after 3 seconds and redirect
                                setTimeout(() => {
                                    window.location.href = 'manageSales.php?success=1';
                                }, 3000);
                            }
                        } else {
                            throw new Error('Failed to update sale');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Failed to update sale. Please try again.');
                    });
                });

                // Calendar button functionality
                calendarBtn.addEventListener('click', () => {
                    const datePicker = document.createElement('input');
                    datePicker.type = 'date';
                    datePicker.style.display = 'none';
                    datePicker.value = document.querySelector('.date-input').value;
                    
                    datePicker.addEventListener('change', (e) => {
                        document.querySelector('.date-input').value = e.target.value;
                        datePicker.remove();
                    });
                    
                    document.body.appendChild(datePicker);
                    datePicker.click();
                });

                // Calculate total when price or quantity changes
                const editPriceInput = document.querySelector('.price-input');
                const editQtyInput = document.querySelector('.qty-input');
                const editTotalSpan = document.querySelector('.total-amount');
                
                function updateTotalAmount() {
                    const price = parseFloat(editPriceInput.value) || 0;
                    const qty = parseInt(editQtyInput.value) || 0;
                    const total = price * qty;
                    editTotalSpan.textContent = `₱${total.toFixed(2)}`;
                    
                    // Enable/disable update button based on changes
                    const updateBtn = document.querySelector('.update-sale-btn');
                    updateBtn.disabled = false;
                }
                
                editPriceInput.addEventListener('input', updateTotalAmount);
                editQtyInput.addEventListener('input', updateTotalAmount);
                
                // Initialize datepicker
                const editDateInput = document.querySelector('.date-input');
                const editCalendarBtn = document.querySelector('.calendar-btn');
                
                editCalendarBtn.addEventListener('click', () => {
                    const datePicker = document.createElement('input');
                    datePicker.type = 'date';
                    datePicker.style.display = 'none';
                    datePicker.value = editDateInput.value;
                    
                    datePicker.addEventListener('change', (e) => {
                        editDateInput.value = e.target.value;
                        datePicker.remove();
                    });
                    
                    document.body.appendChild(datePicker);
                    datePicker.click();
                });
            });
        </script>

        <!-- Add this right after your main-content div -->
        <div id="successMessage" class="success-message">
            <div class="success-content">
                <i class="fas fa-check-circle"></i>
                <p>Sale updated successfully!</p>
            </div>
        </div>
</body>
</html>