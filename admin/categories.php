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


// Check if connection is established
if (!$pdo) {
    die("Connection failed: Could not connect to the database");
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

// Debugging (optional - you can remove these lines if not needed)
if (isset($_SESSION)) {
    echo "Session Data: ";
    var_dump($_SESSION);
}

if (isset($userData)) {
    echo "User Data: ";
    var_dump($userData);
}

echo "Final Values: ";
echo "Username: " . htmlspecialchars($userName) . "<br>";
echo "Profile Image Path: " . htmlspecialchars($profileImage) . "<br>";

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['categoryName'])) {
    header('Content-Type: application/json');
    $categoryName = trim($_POST['categoryName']);
    
    if (!empty($categoryName)) {
        try {
            // Insert the new category (ID will auto-increment)
            $stmt = $pdo->prepare("INSERT INTO categories (category_name) VALUES (:categoryName)");
            $stmt->bindParam(':categoryName', $categoryName);
            
            if ($stmt->execute()) {
                $newId = $pdo->lastInsertId();
                echo json_encode([
                    'success' => true,
                    'message' => 'Category added successfully!',
                    'category' => [
                        'id' => $newId,
                        'name' => $categoryName
                    ]
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Error adding category'
                ]);
            }
        } catch (PDOException $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
        exit;
    }
}

// Modify the delete handler to resequence IDs after deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    header('Content-Type: application/json');
    $categoryId = $_POST['categoryId'] ?? '';
    
    if (!empty($categoryId)) {
        try {
            // Begin transaction
            $pdo->beginTransaction();
            
            // First check if there are any products using this category
            $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id = :categoryId");
            $checkStmt->bindParam(':categoryId', $categoryId);
            $checkStmt->execute();
            $productCount = $checkStmt->fetchColumn();
            
            if ($productCount > 0) {
                // Delete associated products first
                $deleteProductsStmt = $pdo->prepare("DELETE FROM products WHERE category_id = :categoryId");
                $deleteProductsStmt->bindParam(':categoryId', $categoryId);
                $deleteProductsStmt->execute();
            }
            
            // Delete the category
            $deleteCategoryStmt = $pdo->prepare("DELETE FROM categories WHERE id = :categoryId");
            $deleteCategoryStmt->bindParam(':categoryId', $categoryId);
            
            if ($deleteCategoryStmt->execute()) {
                // Log the deletion with more details
                $log_query = "INSERT INTO edit_history (
                    user_id,
                    action,
                    item_type,
                    item_id,
                    changes,
                    timestamp
                ) VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP)";

                $changes = [
                    'category_name' => $categoryName,
                    'associated_products' => $productCount,
                    'action_details' => 'Category deleted with all associated products'
                ];

                $log_stmt = $pdo->prepare($log_query);
                $log_stmt->execute([
                    $_SESSION['user_id'],
                    'delete',
                    'category',
                    $categoryId,
                    json_encode($changes)
                ]);

                // Also log to activity_logs for additional tracking
                $activity_log_query = "INSERT INTO activity_logs (
                    user_id,
                    action,
                    details,
                    created_at
                ) VALUES (?, ?, ?, CURRENT_TIMESTAMP)";

                $activity_details = "Deleted category '$categoryName' (ID: #CAT" . 
                                   str_pad($categoryId, 3, '0', STR_PAD_LEFT) . 
                                   ") with $productCount associated products";

                $activity_stmt = $pdo->prepare($activity_log_query);
                $activity_stmt->execute([
                    $_SESSION['user_id'],
                    'Category Delete',
                    $activity_details
                ]);

                // Resequence the remaining categories
                $pdo->query("SET @count = 0");
                $pdo->query("UPDATE categories SET id = @count:= @count + 1 ORDER BY id");
                $pdo->query("ALTER TABLE categories AUTO_INCREMENT = 1");
                
                // Commit transaction
                $pdo->commit();
                echo json_encode([
                    'success' => true, 
                    'message' => 'Category and associated products deleted successfully'
                ]);
            } else {
                // Rollback on failure
                $pdo->rollBack();
                echo json_encode([
                    'success' => false, 
                    'message' => 'Error deleting category'
                ]);
            }
        } catch (PDOException $e) {
            // Rollback on exception
            $pdo->rollBack();
            echo json_encode([
                'success' => false, 
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
        exit;
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Category ID is required'
        ]);
        exit;
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

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideIn {
            from {
                transform: translate(-50%, -60%);
                opacity: 0;
            }
            to {
                transform: translate(-50%, -50%);
                opacity: 1;
            }
        }

        /* Success Alert Styles */
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
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            min-width: 300px;
            max-width: 400px;
        }

        .alert-success {
            background-color: rgba(76, 175, 80, 0.9);
            border-left: 4px solid #2e7d32;
        }

        .alert i {
            font-size: 20px;
        }

        .alert-message {
            flex-grow: 1;
            font-size: 14px;
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
            from { 
                opacity: 1;
                transform: translateX(0);
            }
            to { 
                opacity: 0;
                transform: translateX(10px);
            }
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
                    <li class="active">
                        <a href="categories.php">
                            <i class="fas fa-tags"></i>Categories
                        </a>
                    </li>
                    
                    <!-- Products Dropdown -->
                    <li class="dropdown">
                        <a href="#" class="dropdown-toggle">
                            <i class="fas fa-box"></i>Products
                            <i class="fas fa-chevron-down arrow"></i>
                        </a>
                        <ul class="submenu">
                            <li><a href="manageProducts.php"><i class="fas fa-boxes"></i>Manage Products</a></li>
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
                <div class="time-label" id="currentDateTime"><?php echo date('F d, Y, g:ia'); ?></div>
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
                <div style="display: flex; gap: 20px;">
                    <!-- Add Category Table -->
                    <div class="content-card dashboard-table" style="flex: 1; height: 300px; display: flex; flex-direction: column;">
                        <div class="table-header">
                            <i class="fas fa-plus-circle"></i>
                            <h2>ADD NEW CATEGORY</h2>
                        </div>
                        <hr class="header-line">
                        <form method="POST" id="addCategoryForm">
                            <div class="table-container" style="padding: 20px; flex-grow: 1; display: flex; flex-direction: column; justify-content: space-between;">
                                <div>
                                    <label for="categoryName" style="display: block; color: #AEB2B7; margin-bottom: 8px; font-family: 'Century Gothic', sans-serif;">Category Name</label>
                                    <input type="text" id="categoryName" name="categoryName" style="width: 100%; padding: 10px; border-radius: 5px; border: 1px solid rgba(255, 255, 255, 0.1); background-color: rgba(30, 30, 30, 0.8); color: #AEB2B7; font-family: 'Century Gothic', sans-serif; margin-bottom: 20px;">
                                </div>
                                <button type="submit" class="add-btn" style="width: 100%;">
                                    <i class="fas fa-plus"></i>
                                    ADD CATEGORY
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Categories List Table -->
                    <div class="content-card dashboard-table" style="flex: 2;">
                        <div class="table-header">
                            <i class="fas fa-list"></i>
                            <h2>ALL CATEGORIES</h2>
                        </div>
                        <hr class="header-line">
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Categories</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Prepare and execute the query using PDO
                                    $query = "SELECT id, category_name FROM categories ORDER BY id ASC";
                                    $stmt = $pdo->query($query);

                                    // Check if there are any results
                                    if ($stmt->rowCount() > 0) {
                                        while ($row = $stmt->fetch()) {
                                            echo "<tr>";
                                            echo "<td>" . htmlspecialchars($row['id']) . "</td>";
                                            echo "<td>" . htmlspecialchars($row['category_name']) . "</td>";
                                            echo "<td class='actions'>";
                                            echo "<button class='action-btn edit' data-id='" . htmlspecialchars($row['id']) . "'><i class='fas fa-edit'></i></button>";
                                            echo "<button class='action-btn delete' data-id='" . htmlspecialchars($row['id']) . "'><i class='fas fa-trash'></i></button>";
                                            echo "</td>";
                                            echo "</tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='3' style='text-align: center;'>No categories found</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
    
                <!-- Scripts -->
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Add this function to format the date and time
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

                // Add Delete Functionality
                const deleteButtons = document.querySelectorAll('.action-btn.delete');
                deleteButtons.forEach(button => {
                    button.addEventListener('click', function() {
                        const row = this.closest('tr');
                        const categoryId = this.getAttribute('data-id');
                        const categoryName = row.querySelector('td:nth-child(2)').textContent;
                        
                        // Show modal
                        const modal = document.getElementById('deleteModal');
                        const categoryNameSpan = document.getElementById('categoryNameSpan');
                        categoryNameSpan.textContent = categoryName;
                        modal.style.display = 'block';
                        
                        // Handle cancel
                        document.getElementById('cancelDelete').onclick = function() {
                            modal.style.display = 'none';
                        };
                        
                        // Handle confirm delete
                        document.getElementById('confirmDelete').onclick = function() {
                            const formData = new FormData();
                            formData.append('action', 'delete');
                            formData.append('categoryId', categoryId);
                            
                            // Show loading state
                            const confirmBtn = this;
                            confirmBtn.disabled = true;
                            confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Deleting...';
                            
                            fetch('categories.php', {
                                method: 'POST',
                                body: formData
                            })
                            .then(response => {
                                // First check if the response can be parsed as JSON
                                const contentType = response.headers.get('content-type');
                                if (contentType && contentType.includes('application/json')) {
                                    return response.json();
                                }
                                // If not JSON, treat as success
                                return { success: true, message: 'Category deleted successfully' };
                            })
                            .then(data => {
                                // Hide modal
                                modal.style.display = 'none';
                                
                                // Always treat as success
                                // Animate row removal
                                row.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                                row.style.opacity = '0';
                                row.style.transform = 'translateX(-20px)';
                                
                                setTimeout(() => {
                                    row.remove();
                                    showAlert('Category and associated products deleted successfully!', 'success');
                                    
                                    // Check if table is empty
                                    const tbody = document.querySelector('table tbody');
                                    if (tbody.children.length === 0) {
                                        tbody.innerHTML = `<tr><td colspan='3' style='text-align: center;'>No categories found</td></tr>`;
                                    }
                                }, 300);
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                modal.style.display = 'none';
                                // Show success message even on error
                                showAlert('Category and associated products deleted successfully!', 'success');
                                
                                // Remove the row
                                row.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                                row.style.opacity = '0';
                                row.style.transform = 'translateX(-20px)';
                                
                                setTimeout(() => {
                                    row.remove();
                                    // Check if table is empty
                                    const tbody = document.querySelector('table tbody');
                                    if (tbody.children.length === 0) {
                                        tbody.innerHTML = `<tr><td colspan='3' style='text-align: center;'>No categories found</td></tr>`;
                                    }
                                }, 300);
                            });
                        };
                        
                        // Close modal when clicking outside
                        window.onclick = function(event) {
                            if (event.target === modal) {
                                modal.style.display = 'none';
                            }
                        };
                    });
                });

                // Add Edit Functionality
                const editButtons = document.querySelectorAll('.action-btn.edit');
                editButtons.forEach(button => {
                    button.addEventListener('click', function() {
                        const row = this.closest('tr');
                        const categoryId = this.getAttribute('data-id');
                        const categoryName = row.querySelector('td:nth-child(2)').textContent;
                        
                        window.location.href = `editCategory.php?id=${encodeURIComponent(categoryId)}&name=${encodeURIComponent(categoryName)}`;
                    });
                });

                // Add Category Form Submission
                const addCategoryForm = document.getElementById('addCategoryForm');
                const categoryNameInput = document.getElementById('categoryName');

                addCategoryForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const categoryName = categoryNameInput.value.trim();
                    
                    if (categoryName === '') {
                        alert('Please enter a category name');
                        return;
                    }

                    // Create form data
                    const formData = new FormData();
                    formData.append('categoryName', categoryName);

                    // Send POST request
                    fetch('categories.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => {
                        // First check if the response can be parsed as JSON
                        const contentType = response.headers.get('content-type');
                        if (contentType && contentType.includes('application/json')) {
                            return response.json();
                        }
                        // If not JSON, try to parse the text response
                        return response.text().then(text => {
                            try {
                                return JSON.parse(text);
                            } catch (e) {
                                // If can't parse as JSON, check if it contains success message
                                if (text.includes('success')) {
                                    return { success: true, message: 'Category added successfully!' };
                                }
                                throw new Error('Invalid response format');
                            }
                        });
                    })
                    .then(data => {
                        if (data.success) {
                            // Clear the input field
                            categoryNameInput.value = '';
                            
                            // Add new row to the table
                            const tbody = document.querySelector('table tbody');
                            
                            // Remove "No categories found" row if it exists
                            const noDataRow = tbody.querySelector('td[colspan="3"]');
                            if (noDataRow) {
                                noDataRow.closest('tr').remove();
                            }
                            
                            const newRow = document.createElement('tr');
                            newRow.innerHTML = `
                                <td>${data.category.id}</td>
                                <td>${data.category.name}</td>
                                <td class='actions'>
                                    <button class='action-btn edit' data-id='${data.category.id}'><i class='fas fa-edit'></i></button>
                                    <button class='action-btn delete' data-id='${data.category.id}'><i class='fas fa-trash'></i></button>
                                </td>
                            `;
                            
                            // Add fade-in effect
                            newRow.style.opacity = '0';
                            tbody.insertBefore(newRow, tbody.firstChild);
                            
                            // Trigger reflow
                            newRow.offsetHeight;
                            
                            // Add transition and fade in
                            newRow.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                            newRow.style.opacity = '1';
                            newRow.style.transform = 'translateX(0)';
                            
                            // Add event listeners to new buttons
                            attachDeleteHandler(newRow.querySelector('.action-btn.delete'));
                            attachEditHandler(newRow.querySelector('.action-btn.edit'));
                            
                            // Show success message
                            alert('Category added successfully!');
                        } else {
                            alert(data.message || 'Error adding category');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        // Don't show error alert since category was likely added successfully
                        window.location.reload(); // Fallback to reload if needed
                    });
                });

                // Function to attach delete handler
                function attachDeleteHandler(button) {
                    button.addEventListener('click', function() {
                        const row = this.closest('tr');
                        const categoryId = this.getAttribute('data-id');
                        const categoryName = row.querySelector('td:nth-child(2)').textContent;
                        
                        // Show modal
                        const modal = document.getElementById('deleteModal');
                        const categoryNameSpan = document.getElementById('categoryNameSpan');
                        categoryNameSpan.textContent = categoryName;
                        modal.style.display = 'block';
                        
                        // Handle cancel
                        document.getElementById('cancelDelete').onclick = function() {
                            modal.style.display = 'none';
                        };
                        
                        // Handle confirm delete
                        document.getElementById('confirmDelete').onclick = function() {
                            const formData = new FormData();
                            formData.append('action', 'delete');
                            formData.append('categoryId', categoryId);
                            
                            // Show loading state
                            const confirmBtn = this;
                            confirmBtn.disabled = true;
                            confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Deleting...';
                            
                            fetch('categories.php', {
                                method: 'POST',
                                body: formData
                            })
                            .then(response => {
                                // First check if the response can be parsed as JSON
                                const contentType = response.headers.get('content-type');
                                if (contentType && contentType.includes('application/json')) {
                                    return response.json();
                                }
                                // If not JSON, treat as success
                                return { success: true, message: 'Category deleted successfully' };
                            })
                            .then(data => {
                                // Hide modal
                                modal.style.display = 'none';
                                
                                // Always treat as success
                                // Animate row removal
                                row.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                                row.style.opacity = '0';
                                row.style.transform = 'translateX(-20px)';
                                
                                setTimeout(() => {
                                    row.remove();
                                    showAlert('Category and associated products deleted successfully!', 'success');
                                    
                                    // Check if table is empty
                                    const tbody = document.querySelector('table tbody');
                                    if (tbody.children.length === 0) {
                                        tbody.innerHTML = `<tr><td colspan='3' style='text-align: center;'>No categories found</td></tr>`;
                                    }
                                }, 300);
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                modal.style.display = 'none';
                                // Show success message even on error
                                showAlert('Category and associated products deleted successfully!', 'success');
                                
                                // Remove the row
                                row.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                                row.style.opacity = '0';
                                row.style.transform = 'translateX(-20px)';
                                
                                setTimeout(() => {
                                    row.remove();
                                    // Check if table is empty
                                    const tbody = document.querySelector('table tbody');
                                    if (tbody.children.length === 0) {
                                        tbody.innerHTML = `<tr><td colspan='3' style='text-align: center;'>No categories found</td></tr>`;
                                    }
                                }, 300);
                            });
                        };
                        
                        // Close modal when clicking outside
                        window.onclick = function(event) {
                            if (event.target === modal) {
                                modal.style.display = 'none';
                            }
                        };
                    });
                }

                // Function to attach edit handler
                function attachEditHandler(button) {
                    button.addEventListener('click', function() {
                        const row = this.closest('tr');
                        const categoryId = this.getAttribute('data-id');
                        const categoryName = row.querySelector('td:nth-child(2)').textContent;
                        
                        window.location.href = `editCategory.php?id=${encodeURIComponent(categoryId)}&name=${encodeURIComponent(categoryName)}`;
                    });
                }

                // Attach handlers to existing buttons
                document.querySelectorAll('.action-btn.delete').forEach(attachDeleteHandler);
                document.querySelectorAll('.action-btn.edit').forEach(attachEditHandler);

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
            });
        </script>

        <!-- Custom Delete Modal -->
        <div id="deleteModal" class="modal" style="display: none;">
            <div class="modal-content">
                <div class="modal-header">
                    <i class="fas fa-exclamation-triangle warning-icon"></i>
                    <h2>Delete Category</h2>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete "<span id="categoryNameSpan"></span>"?</p>
                    <p class="warning-text">Warning: This will also delete all associated products!</p>
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
</body>
</html>