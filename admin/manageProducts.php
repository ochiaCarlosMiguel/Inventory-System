<?php
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
                }
            }
        }
    } catch (PDOException $e) {
        error_log("Database Error: " . $e->getMessage());
    }
}

// Add this at the top of the file, after the database connection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    header('Content-Type: application/json');
    try {
        // Start transaction
        $pdo->beginTransaction();

        // Get the product info first
        $stmt = $pdo->prepare("SELECT product_title, image_path FROM products WHERE id = ?");
        $stmt->execute([$_POST['delete_id']]);
        $product = $stmt->fetch();

        if (!$product) {
            throw new Exception('Product not found');
        }

        // Delete the product
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $success = $stmt->execute([$_POST['delete_id']]);

        if ($success) {
            // Log the deletion in edit_history
            $stmt = $pdo->prepare("
                INSERT INTO edit_history (
                    item_type, 
                    item_id, 
                    action,
                    changes, 
                    user_id, 
                    timestamp
                ) VALUES (
                    'product',
                    ?,
                    'delete',
                    ?,
                    ?,
                    NOW()
                )
            ");

            // Create changes array
            $changes = [
                'product_title' => $product['product_title'],
                'action' => 'Product Deleted',
                'deleted_at' => date('Y-m-d H:i:s')
            ];

            $stmt->execute([
                $_POST['delete_id'],
                json_encode($changes),
                $_SESSION['user_id'] // Make sure you have user's session
            ]);

            // Delete image if exists
            if ($product['image_path']) {
                $imagePath = "../upload/" . $product['image_path'];
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }

            // Commit the transaction
            $pdo->commit();
            
            echo json_encode([
                'success' => true,
                'message' => 'Product deleted successfully',
                'deleted_id' => $_POST['delete_id']
            ]);
        } else {
            throw new Exception('Failed to delete product');
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
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
            justify-content: space-between;
            gap: 15px;
            margin-bottom: 20px;
        }

        .import-btn {
            background-color: #2d2d2d;
            color: #AEB2B7;
            border: none;
            border-radius: 5px;
            padding: 10px 20px;
            font-family: 'Century Gothic', sans-serif;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .import-btn input[type="file"] {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }

        .import-btn:hover {
            background-color: #3d3d3d;
            color: #F8B83C;
            transform: translateY(-2px);
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
            position: relative;
            padding-right: 25px;
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

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            animation: fadeIn 0.3s ease;
        }

        .modal-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: #1E1E1E;
            padding: 20px;
            border-radius: 8px;
            width: 90%;
            max-width: 400px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            animation: slideIn 0.3s ease;
        }

        .modal-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #333;
        }

        .modal-header h2 {
            color: #F8B83C;
            font-family: 'Century Gothic', sans-serif;
            font-size: 20px;
            margin: 0;
        }

        .warning-icon {
            color: #ff4747;
            font-size: 24px;
        }

        .modal-body {
            margin-bottom: 20px;
        }

        .modal-body p {
            color: #AEB2B7;
            font-family: 'Century Gothic', sans-serif;
            margin: 0 0 10px 0;
        }

        .warning-text {
            color: #ff4747 !important;
            font-size: 14px;
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .modal-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            font-family: 'Century Gothic', sans-serif;
            font-size: 14px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .cancel-btn {
            background-color: #2d2d2d;
            color: #AEB2B7;
        }

        .cancel-btn:hover {
            background-color: #3d3d3d;
        }

        .delete-btn {
            background-color: #750605;
            color: #F8B83C;
        }

        .delete-btn:hover {
            background-color: #8f0806;
        }

        /* Alert Styles */
        .alert {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 25px;
            border-radius: 8px;
            color: #fff;
            font-family: 'Century Gothic', sans-serif;
            display: flex;
            align-items: center;
            gap: 10px;
            z-index: 1000;
            animation: slideInRight 0.5s ease, fadeOut 0.5s ease 2.5s forwards;
        }

        .alert-success {
            background-color: rgba(76, 175, 80, 0.9);
        }

        .alert-error {
            background-color: rgba(244, 67, 54, 0.9);
        }

        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes fadeOut {
            from { opacity: 1; }
            to { opacity: 0; }
        }

        /* Image Modal Specific Styles */
        .image-modal {
            background-color: rgba(0, 0, 0, 0.9);
        }

        .image-modal-content {
            background-color: transparent;
            max-width: 90%;
            width: auto;
            padding: 0;
            box-shadow: none;
        }

        .image-modal-content img {
            max-width: 100%;
            max-height: 90vh;
            display: block;
            margin: 0 auto;
            border-radius: 8px;
        }

        .close-modal {
            position: absolute;
            right: -30px;
            top: -30px;
            color: #fff;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            transition: color 0.3s ease;
        }

        .close-modal:hover {
            color: #F8B83C;
        }

        /* Add this style to show pointer cursor on product images */
        .product-image {
            cursor: pointer;
            transition: transform 0.2s ease;
        }

        .product-image:hover {
            transform: scale(1.05);
        }

        /* Import Alert Styles */
        .import-alert {
            position: fixed;
            top: 20px;
            right: -400px; /* Start off-screen */
            padding: 20px;
            border-radius: 8px;
            color: #fff;
            font-family: 'Century Gothic', sans-serif;
            display: flex;
            align-items: center;
            gap: 15px;
            z-index: 2000;
            transition: all 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            max-width: 400px;
            backdrop-filter: blur(10px);
        }

        .import-alert.success {
            background-color: rgba(76, 175, 80, 0.9);
        }

        .import-alert.error {
            background-color: rgba(244, 67, 54, 0.9);
        }

        .import-alert i {
            font-size: 24px;
        }

        .import-alert .content {
            flex-grow: 1;
        }

        .import-alert .title {
            font-weight: bold;
            margin-bottom: 5px;
        }

        .import-alert .message {
            font-size: 14px;
            opacity: 0.9;
        }

        .import-alert .close-alert {
            cursor: pointer;
            padding: 5px;
            transition: transform 0.3s ease;
        }

        .import-alert .close-alert:hover {
            transform: rotate(90deg);
        }

        /* Progress animation */
        .import-alert .progress {
            position: absolute;
            bottom: 0;
            left: 0;
            height: 3px;
            background-color: rgba(255, 255, 255, 0.7);
            width: 100%;
            transform-origin: left;
            animation: progress 3s linear forwards;
        }

        @keyframes progress {
            from { transform: scaleX(1); }
            to { transform: scaleX(0); }
        }

        @keyframes slideIn {
            from { transform: translateX(100%) scale(0.5); opacity: 0; }
            to { transform: translateX(0) scale(1); opacity: 1; }
        }

        @keyframes slideOut {
            from { transform: translateX(0) scale(1); opacity: 1; }
            to { transform: translateX(100%) scale(0.5); opacity: 0; }
        }

        .export-btn {
            background-color: #2d2d2d;
            color: #AEB2B7;
            border: none;
            border-radius: 5px;
            padding: 10px 20px;
            font-family: 'Century Gothic', sans-serif;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .export-btn:hover {
            background-color: #3d3d3d;
            color: #F8B83C;
            transform: translateY(-2px);
        }

        /* Adjust spacing between buttons */
        .header-actions {
            gap: 15px;
        }

        th i.fas {
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 12px;
        }

        th:hover {
            background-color: rgba(40, 40, 40, 0.8);
        }

        /* Search Container Styles */
        .search-container {
            display: flex;
            gap: 10px;
            flex-grow: 1;
            max-width: 500px;
            margin-right: auto;
        }

        #searchInput {
            flex-grow: 1;
            padding: 8px 15px;
            border: 1px solid #333;
            border-radius: 5px;
            background-color: #2d2d2d;
            color: #AEB2B7;
            font-family: 'Century Gothic', sans-serif;
            transition: all 0.3s ease;
        }

        #searchInput:focus {
            outline: none;
            border-color: #F8B83C;
            background-color: #363636;
        }

        #searchCategory {
            padding: 8px 15px;
            border: 1px solid #333;
            border-radius: 5px;
            background-color: #2d2d2d;
            color: #AEB2B7;
            font-family: 'Century Gothic', sans-serif;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        #searchCategory:focus {
            outline: none;
            border-color: #F8B83C;
            background-color: #363636;
        }

        #searchCategory option {
            background-color: #2d2d2d;
            color: #AEB2B7;
        }

        /* Group the action buttons together */
        .header-actions > :not(.search-container) {
            display: flex;
            gap: 15px;
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
                            <li><a href="manageGroups.php"><i class="fas fa-users-cog"></i>Manage Groups</a></li>
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
                    <li class="dropdown open">  <!-- Add 'open' class here -->
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
                    <div class="table-header">
                        <i class="fas fa-boxes"></i>
                        <h2>PRODUCTS</h2>
                    </div>
                    <div class="header-actions">
                        <!-- Add search bar here -->
                        <div class="search-container">
                            <input type="text" id="searchInput" placeholder="Search products...">
                            <select id="searchCategory">
                                <option value="">All Categories</option>
                                <?php
                                try {
                                    $stmt = $pdo->query("SELECT id, category_name FROM categories ORDER BY category_name");
                                    while ($category = $stmt->fetch()) {
                                        echo "<option value='" . htmlspecialchars($category['category_name']) . "'>" . 
                                             htmlspecialchars($category['category_name']) . "</option>";
                                    }
                                } catch(PDOException $e) {
                                    error_log("Error fetching categories: " . $e->getMessage());
                                }
                                ?>
                            </select>
                        </div>
                        <a href="exportProducts.php" class="export-btn">
                            <i class="fas fa-file-export"></i>
                            Export PDF
                        </a>
                        <form class="import-form" onsubmit="return false;">
                            <label class="import-btn">
                                <i class="fas fa-file-import"></i>
                                Import Excel
                                <input type="file" name="excel_file" accept=".xlsx,.xls,.csv" id="importFile">
                            </label>
                        </form>
                        <a href="addProducts.php" class="add-btn">
                            <i class="fas fa-plus"></i>
                            ADD NEW PRODUCT
                        </a>
                    </div>
                    <hr class="header-line">
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Photo</th>
                                    <th>Product Title</th>
                                    <th>Categories</th>
                                    <th>In-Stock</th>
                                    <th>Buying Price</th>
                                    <th>Selling Price</th>
                                    <th>Product Added</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                try {
                                    $query = "SELECT p.*, c.category_name 
                                             FROM products p 
                                             LEFT JOIN categories c ON p.category_id = c.id 
                                             ORDER BY p.id ASC";
                                    $stmt = $pdo->prepare($query);
                                    $stmt->execute();

                                    while ($row = $stmt->fetch()) {
                                        // Construct image path
                                        $dbImagePath = $row['image_path'] ?? 'image.jpg';
                                        $imageUrl = "../upload/" . $dbImagePath;
                                        
                                        // Fallback if file doesn't exist
                                        if (!file_exists($imageUrl)) {
                                            $imageUrl = "../upload/image.jpg";
                                        }

                                        echo "<tr>";
                                        echo "<td>" . htmlspecialchars($row['id']) . "</td>";
                                        echo "<td>";
                                        echo "<img src='" . $imageUrl . "' 
                                                      alt='Product Image' 
                                                      class='product-image' 
                                                      style='width: 50px; height: 50px; border-radius: 5px; object-fit: cover;'
                                                      onerror=\"this.src='../upload/image.jpg'\">";
                                        echo "</td>";
                                        echo "<td>" . htmlspecialchars($row['product_title']) . "</td>";
                                        echo "<td>" . (isset($row['category_name']) ? htmlspecialchars($row['category_name']) : 'No Category') . "</td>";
                                        echo "<td>" . htmlspecialchars($row['quantity']) . "</td>";
                                        echo "<td>₱" . number_format($row['buying_price'], 2) . "</td>";
                                        echo "<td>₱" . number_format($row['selling_price'], 2) . "</td>";
                                        echo "<td>" . date('Y-m-d H:i', strtotime($row['created_at'])) . "</td>";
                                        echo "<td class='actions'>
                                                <a href='editProduct.php?id=" . $row['id'] . "' class='action-btn edit'><i class='fas fa-edit'></i></a>
                                                <button class='action-btn delete' data-id='" . $row['id'] . "'><i class='fas fa-trash'></i></button>
                                              </td>";
                                        echo "</tr>";
                                    }
                                } catch(PDOException $e) {
                                    error_log("Database Error: " . $e->getMessage());
                                    echo "Error: " . $e->getMessage();
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
                // Add datetime update function
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

                // Add event listeners for edit and delete buttons
                const tableBody = document.querySelector('table tbody');
                
                tableBody.addEventListener('click', async function(e) {
                    const target = e.target.closest('.action-btn.delete');
                    if (!target) return;

                    e.preventDefault();
                    
                    const row = target.closest('tr');
                    const productId = target.dataset.id;
                    const productName = row.querySelector('td:nth-child(3)').textContent;

                    // Show confirmation modal
                    const modal = document.getElementById('deleteModal');
                    const productNameSpan = document.getElementById('productNameSpan');
                    productNameSpan.textContent = productName;
                    modal.style.display = 'block';

                    // Handle delete confirmation
                    document.getElementById('confirmDelete').onclick = async function() {
                        try {
                            const confirmBtn = this;
                            confirmBtn.disabled = true;
                            confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Deleting...';

                            const formData = new FormData();
                            formData.append('delete_id', productId);

                            const response = await fetch(window.location.href, {
                                method: 'POST',
                                body: formData
                            });

                            const data = await response.json();

                            // Hide modal first
                            modal.style.display = 'none';

                            if (data.success) {
                                // Remove the row with animation
                                row.style.transition = 'all 0.3s ease';
                                row.style.opacity = '0';
                                row.style.transform = 'translateX(-20px)';

                                setTimeout(() => {
                                    row.remove();
                                    
                                    // Update row numbers
                                    const rows = tableBody.querySelectorAll('tr');
                                    rows.forEach((row, index) => {
                                        const idCell = row.querySelector('td:first-child');
                                        if (idCell) {
                                            idCell.textContent = index + 1;
                                        }
                                    });

                                    // Show empty message if no rows left
                                    if (rows.length === 0) {
                                        tableBody.innerHTML = `
                                            <tr>
                                                <td colspan="9" style="text-align: center;">No products found</td>
                                            </tr>
                                        `;
                                    }

                                    // Show success message
                                    showAlert('Product deleted successfully', 'success');
                                }, 300);
                            } else {
                                showAlert(data.message || 'Failed to delete product', 'error');
                            }
                        } catch (error) {
                            console.error('Delete error:', error);
                            showAlert('Failed to delete product', 'error');
                        } finally {
                            const confirmBtn = document.getElementById('confirmDelete');
                            confirmBtn.disabled = false;
                            confirmBtn.innerHTML = '<i class="fas fa-trash"></i> Delete';
                        }
                    };

                    // Handle cancel
                    document.getElementById('cancelDelete').onclick = function() {
                        modal.style.display = 'none';
                    };

                    // Close modal when clicking outside
                    window.onclick = function(event) {
                        if (event.target === modal) {
                            modal.style.display = 'none';
                        }
                    };
                });

                // Image Modal Functionality
                const imageModal = document.getElementById('imageModal');
                const modalImage = document.getElementById('modalImage');
                const closeModal = document.querySelector('.close-modal');

                // Add click event to all product images
                document.querySelectorAll('.product-image').forEach(img => {
                    img.addEventListener('click', function() {
                        modalImage.src = this.src;
                        imageModal.style.display = 'block';
                        document.body.style.overflow = 'hidden'; // Prevent scrolling when modal is open
                    });
                });

                // Close modal when clicking the close button
                closeModal.addEventListener('click', function() {
                    imageModal.style.display = 'none';
                    document.body.style.overflow = 'auto'; // Restore scrolling
                });

                // Close modal when clicking outside the image
                imageModal.addEventListener('click', function(e) {
                    if (e.target === imageModal) {
                        imageModal.style.display = 'none';
                        document.body.style.overflow = 'auto'; // Restore scrolling
                    }
                });

                // Close modal with Escape key
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape' && imageModal.style.display === 'block') {
                        imageModal.style.display = 'none';
                        document.body.style.overflow = 'auto'; // Restore scrolling
                    }
                });

                // Function to show import alert
                function showImportAlert(success, message) {
                    const alert = document.createElement('div');
                    alert.className = `import-alert ${success ? 'success' : 'error'}`;
                    
                    alert.innerHTML = `
                        <i class="fas ${success ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
                        <div class="content">
                            <div class="title">${success ? 'Import Successful' : 'Import Failed'}</div>
                            <div class="message">${message}</div>
                        </div>
                        <div class="close-alert">
                            <i class="fas fa-times"></i>
                        </div>
                        <div class="progress"></div>
                    `;
                    
                    document.body.appendChild(alert);
                    
                    // Trigger animation
                    setTimeout(() => {
                        alert.style.right = '20px';
                    }, 100);
                    
                    // Close button handler
                    const closeBtn = alert.querySelector('.close-alert');
                    closeBtn.onclick = () => {
                        alert.style.right = '-400px';
                        setTimeout(() => alert.remove(), 500);
                    };
                    
                    // Auto close after 3 seconds
                    setTimeout(() => {
                        if (alert.parentElement) {
                            alert.style.right = '-400px';
                            setTimeout(() => alert.remove(), 500);
                        }
                    }, 3000);
                }

                // Add this event listener for the file input
                document.getElementById('importFile').addEventListener('change', function(e) {
                    const formData = new FormData();
                    formData.append('excel_file', this.files[0]);
                    
                    fetch('import_products.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        showImportAlert(data.success, data.message);
                        if (data.success) {
                            // Refresh the table after successful import
                            setTimeout(() => {
                                window.location.reload();
                            }, 3500);
                        }
                    })
                    .catch(error => {
                        showImportAlert(false, 'Error importing products: ' + error.message);
                    });
                });

                // Add this helper function to find elements by text content
                Element.prototype.contains = function(text) {
                    return this.textContent.trim() === text.toString().trim();
                };

                // Add search functionality
                const searchInput = document.getElementById('searchInput');
                const searchCategory = document.getElementById('searchCategory');
                const tableRows = document.querySelectorAll('table tbody tr');

                function filterTable() {
                    const searchTerm = searchInput.value.toLowerCase();
                    const selectedCategory = searchCategory.value.toLowerCase();

                    tableRows.forEach(row => {
                        const productName = row.querySelector('td:nth-child(3)').textContent.toLowerCase();
                        const category = row.querySelector('td:nth-child(4)').textContent.toLowerCase();
                        
                        const matchesSearch = productName.includes(searchTerm);
                        const matchesCategory = !selectedCategory || category === selectedCategory;

                        if (matchesSearch && matchesCategory) {
                            row.style.display = '';
                        } else {
                            row.style.display = 'none';
                        }
                    });
                }

                searchInput.addEventListener('input', filterTable);
                searchCategory.addEventListener('change', filterTable);
            });

            // Function to show alert
            function showAlert(message, type = 'success') {
                const alert = document.createElement('div');
                alert.className = `alert alert-${type}`;
                alert.innerHTML = `
                    <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
                    ${message}
                `;
                document.body.appendChild(alert);
                
                // Remove alert after animation
                setTimeout(() => {
                    alert.remove();
                }, 3000);
            }
        </script>

        <!-- Custom Delete Modal -->
        <div id="deleteModal" class="modal" style="display: none;">
            <div class="modal-content">
                <div class="modal-header">
                    <i class="fas fa-exclamation-triangle warning-icon"></i>
                    <h2>Delete Product</h2>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete "<span id="productNameSpan"></span>"?</p>
                    <p class="warning-text">This action cannot be undone!</p>
                </div>
                <div class="modal-footer">
                    <button id="cancelDelete" class="modal-btn cancel-btn">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button id="confirmDelete" class="modal-btn delete-btn">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                </div>
            </div>
        </div>

        <!-- Add this modal for product image -->
        <div id="imageModal" class="modal image-modal" style="display: none;">
            <div class="modal-content image-modal-content">
                <span class="close-modal">&times;</span>
                <img id="modalImage" src="" alt="Product Image">
            </div>
        </div>
</body>
</html>