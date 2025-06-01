<?php

// Start the session
session_start();
// Prevent any unwanted output
ob_start();


// Include the database connection
require_once '../connection/connections.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    // User is not logged in, redirect to index.php
    header("Location: index.php");
    exit(); // Ensure no further code is executed
}

// Add this after your database connection
try {
    $createTableSQL = "CREATE TABLE IF NOT EXISTS activity_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        action VARCHAR(255) NOT NULL,
        details TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )";
    $pdo->exec($createTableSQL);
} catch (PDOException $e) {
    error_log("Error creating activity_logs table: " . $e->getMessage());
}

// Get user ID from URL parameter and validate it
$edit_user_id = isset($_GET['id']) ? (int)$_GET['id'] : null;

// Initialize variables
$editUserData = null;
$error_message = '';
$userGroups = [];  // Initialize userGroups array

// Fetch user data if ID is provided
if ($edit_user_id) {
    try {
        // Updated query to match your actual table structure
        $stmt = $pdo->prepare("SELECT id, name, username, role, status, profile_image FROM users WHERE id = ?");
        $stmt->execute([$edit_user_id]);
        $editUserData = $stmt->fetch();
        
        if (!$editUserData) {
            $error_message = "User not found.";
            // Redirect to manage users page if user not found
            header("Location: manageUsers.php?error=usernotfound");
            exit;
        }

        // Fetch all groups
        $stmt = $pdo->prepare("SELECT group_name FROM user_groups ORDER BY group_name");
        $stmt->execute();
        $userGroups = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
    } catch (PDOException $e) {
        $error_message = "Database error: " . $e->getMessage();
        error_log($error_message);
    }
} else {
    // Redirect to manage users page if no ID provided
    header("Location: manageUsers.php");
    exit;
}

// Function to send JSON response and exit
function sendJsonResponse($success, $message = '') {
    ob_clean(); // Clear any output buffers
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message
    ]);
    exit;
}

// Handle form submissions first, before any HTML output
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_user'])) {
        // Handle user details update
        try {
            // Start transaction
            $pdo->beginTransaction();
            
            // Get old user data for logging
            $stmt = $pdo->prepare("SELECT name, username, role, status FROM users WHERE id = ?");
            $stmt->execute([$edit_user_id]);
            $oldData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Validate input data
            $name = trim($_POST['name']);
            $username = trim($_POST['username']);
            $role = trim($_POST['role']);
            $status = isset($_POST['status']) ? (int)$_POST['status'] : 0;

            // Basic validation
            if (empty($name) || empty($username)) {
                throw new Exception("Name and username are required fields.");
            }

            // Validate role against available groups
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_groups WHERE group_name = ?");
            $stmt->execute([$role]);
            if ($stmt->fetchColumn() == 0) {
                throw new Exception("Invalid role selected.");
            }

            // Check if username already exists (excluding current user)
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? AND id != ?");
            $stmt->execute([$username, $edit_user_id]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception("Username already exists.");
            }
            
            // Update user
            $stmt = $pdo->prepare("UPDATE users SET 
                name = ?, 
                username = ?, 
                role = ?, 
                status = ?
                WHERE id = ?");
            
            $result = $stmt->execute([
                $name,
                $username,
                $role,
                $status,
                $edit_user_id
            ]);

            if (!$result) {
                throw new Exception("Failed to update user.");
            }
            
            // Log the changes
            $changes = [];
            if ($oldData['name'] !== $name) {
                $changes[] = "Name changed from '{$oldData['name']}' to '{$name}'";
            }
            if ($oldData['username'] !== $username) {
                $changes[] = "Username changed from '{$oldData['username']}' to '{$username}'";
            }
            if ($oldData['role'] !== $role) {
                $changes[] = "Role changed from '{$oldData['role']}' to '{$role}'";
            }
            if ((int)$oldData['status'] !== $status) {
                $oldStatus = $oldData['status'] ? 'Active' : 'Inactive';
                $newStatus = $status ? 'Active' : 'Inactive';
                $changes[] = "Status changed from '{$oldStatus}' to '{$newStatus}'";
            }
            
            if (!empty($changes)) {
                $changeLog = implode(", ", $changes);
                $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, details, created_at) VALUES (?, ?, ?, CURRENT_TIMESTAMP)");
                $logResult = $stmt->execute([
                    $_SESSION['user_id'], 
                    'User Update', 
                    "Updated user ID {$_GET['id']}: " . $changeLog
                ]);

                if (!$logResult) {
                    throw new Exception("Failed to log changes.");
                }
            }
            
            $pdo->commit();
            sendJsonResponse(true);
            
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("Update Error: " . $e->getMessage());
            sendJsonResponse(false, $e->getMessage());
        }
    }
    
    // Add this new block to handle password changes
    if (isset($_POST['change_password'])) {
        try {
            // Start transaction
            $pdo->beginTransaction();
            
            $old_password = $_POST['old_password'];
            $new_password = $_POST['new_password'];
            
            // Verify current password
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$edit_user_id]);
            $current_hash = $stmt->fetchColumn();
            
            if (!password_verify($old_password, $current_hash)) {
                throw new Exception("Current password is incorrect");
            }
            
            // Validate new password
            if (strlen($new_password) < 8 || 
                !preg_match('/[A-Z]/', $new_password) || 
                !preg_match('/[a-z]/', $new_password) || 
                !preg_match('/[0-9]/', $new_password)) {
                throw new Exception("New password does not meet requirements");
            }
            
            // Hash new password
            $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
            
            // Update password
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $result = $stmt->execute([$new_hash, $edit_user_id]);
            
            if (!$result) {
                throw new Exception("Failed to update password");
            }
            
            // Modified log entry to include more detailed information
            $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, details, created_at) VALUES (?, ?, ?, CURRENT_TIMESTAMP)");
            $logResult = $stmt->execute([
                $_SESSION['user_id'],
                'Password Change',
                "Changed password for user ID {$edit_user_id} (Password changed from '" . substr($old_password, 0, 1) . "***' to '" . substr($new_password, 0, 1) . "***')"
            ]);
            
            if (!$logResult) {
                throw new Exception("Failed to log password change");
            }
            
            $pdo->commit();
            sendJsonResponse(true, "Password updated successfully");
            
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("Password Change Error: " . $e->getMessage());
            sendJsonResponse(false, $e->getMessage());
        }
    }
}

// Add this code block after your database connection and before the HTML output
// Fetch the logged-in user's details
try {
    $stmt = $pdo->prepare("SELECT u.name, u.profile_image FROM users u WHERE u.id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $loggedInUser = $stmt->fetch();
    $userName = $loggedInUser['name'] ?? 'User';
    
    // Check if profile_image exists and is not empty
    if (!empty($loggedInUser['profile_image']) && file_exists("../upload/profiles/" . $loggedInUser['profile_image'])) {
        $profileImage = "../upload/profiles/" . $loggedInUser['profile_image'];
    } else {
        // Use default image if no profile image exists or file is missing
        $profileImage = "../upload/default-profile.jpg";
    }
} catch (PDOException $e) {
    error_log("Error fetching user details: " . $e->getMessage());
    $userName = 'User';
    $profileImage = "../upload/default-profile.jpg";
}

// Continue with the rest of your PHP code for the page
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

        .content-wrapper {
            margin-left: 249px;
            margin-top: 60px;
            padding: 20px;
            min-height: calc(100vh - 60px);
        }

        .settings-container {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            gap: 20px;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        }

        .settings-table {
            background: rgba(30, 30, 30, 0.7); /* Semi-transparent background */
            backdrop-filter: blur(10px); /* Glass blur effect */
            -webkit-backdrop-filter: blur(10px); /* For Safari support */
            border: 1px solid rgba(255, 255, 255, 0.1); /* Subtle border */
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
        }

        /* Optional: Add subtle hover effect */
        .settings-table:hover {
            background: rgba(30, 30, 30, 0.75);
            border: 1px solid rgba(255, 255, 255, 0.15);
            transform: translateY(-2px);
            transition: all 0.3s ease;
        }

        .table-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }

        .table-header i {
            color: #F8B83C;
            font-size: 1.2em;
        }

        .table-header h2 {
            color: #AEB2B7;
            font-family: 'Century Gothic', sans-serif;
            font-size: 1.2em;
            margin: 0;
        }

        .divider {
            height: 1px;
            background: linear-gradient(
                90deg,
                rgba(255, 255, 255, 0) 0%,
                rgba(255, 255, 255, 0.1) 50%,
                rgba(255, 255, 255, 0) 100%
            );
            margin: 15px 0;
        }

        .account-content {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .input-group {
            display: flex;
            flex-direction: column-reverse;
        }

        .input-group input {
            background: rgba(54, 51, 51, 0.5);
            backdrop-filter: blur(5px);
            -webkit-backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 10px;
            border-radius: 4px;
            color: #AEB2B7;
            font-family: 'Century Gothic', sans-serif;
            transition: all 0.3s ease;
        }

        .input-group input:focus {
            background: rgba(54, 51, 51, 0.7);
            border: 1px solid rgba(248, 184, 60, 0.5);
            outline: none;
        }

        .input-group label {
            color: #AEB2B7;
            font-family: 'Century Gothic', sans-serif;
            font-size: 0.9em;
            margin-bottom: 5px;
        }

        .button-group {
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
        }

        button {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-family: 'Century Gothic', sans-serif;
            transition: all 0.3s ease;
        }

        .btn-update {
            background-color: #4CAF50;
            color: white;
        }

        .btn-change-password {
            background-color: #2196F3;
            color: white;
        }

        button:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }

        /* Add these new styles */
        .password-wrapper {
            position: relative;
            width: 100%;
        }

        .toggle-password {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #AEB2B7;
            cursor: pointer;
        }

        .password-requirements {
            margin: 15px 0;
            font-size: 0.9em;
            color: #AEB2B7;
        }

        .requirement {
            margin: 5px 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .requirement i {
            font-size: 0.8em;
            color: #666;
        }

        .requirement.valid i {
            color: #4CAF50;
        }

        .btn-update:disabled, .btn-change-password:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .message {
            margin-top: 15px;
            padding: 10px;
            border-radius: 4px;
            display: none;
            align-items: center;
            gap: 8px;
        }

        .message.success {
            background: rgba(76, 175, 80, 0.1);
            color: #4CAF50;
        }

        /* Add animation for messages */
        @keyframes slideIn {
            from { transform: translateY(-10px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .message.show {
            display: flex;
            animation: slideIn 0.3s ease;
        }

        /* Simplified back button styles */
        .back-button-container {
            margin-bottom: 20px;
        }

        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            background-color: #403E3E;
            color: #AEB2B7;
            text-decoration: none;
            border-radius: 4px;
            font-family: 'Century Gothic', sans-serif;
            font-size: 14px;
            transition: all 0.2s ease;
        }

        .back-button:hover {
            background-color: #2d2d2d;
            color: #F8B83C;
        }

        .back-button i {
            font-size: 12px;
        }

        /* Modify the select input styles */
        .input-group select {
            background: rgba(54, 51, 51, 0.5);
            backdrop-filter: blur(5px);
            -webkit-backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 10px;
            border-radius: 4px;
            color: #AEB2B7;
            font-family: 'Century Gothic', sans-serif;
            transition: all 0.3s ease;
            width: 100%;
            cursor: pointer;
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
        }

        /* Modify the dropdown arrow to only appear for select elements */
        .input-group:has(select)::after {
            content: '\f107';
            font-family: 'Font Awesome 5 Free';
            font-weight: 900;
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #AEB2B7;
            pointer-events: none;
        }

        /* Style for focus state */
        .input-group select:focus {
            background: rgba(54, 51, 51, 0.7);
            border: 1px solid rgba(248, 184, 60, 0.5);
            outline: none;
        }

        /* Style for select options */
        .input-group select option {
            background: #363333;
            color: #AEB2B7;
            padding: 10px;
        }

        /* Remove the dropdown arrow from select elements */
        .input-group:has(select)::after {
            display: none; /* Hide the custom dropdown arrow */
        }
        
        /* Remove default dropdown arrow from select elements */
        .input-group select {
            background-image: none;
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
                    <li class="dropdown open">
                        <a href="#" class="dropdown-toggle">
                            <i class="fas fa-users"></i>User Management
                            <i class="fas fa-chevron-down arrow"></i>
                        </a>
                        <ul class="submenu">
                            <li><a href="manageGroups.php"><i class="fas fa-users-cog"></i>Manage Groups</a></li>
                            <li class="active"><a href="manageUsers.php"><i class="fas fa-user-cog"></i>Manage Users</a></li>
                        </ul>
                    </li>
                    
                    <!-- Categories Link -->
                    <li>
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
    
            <!-- Content Wrapper -->
            <div class="content-wrapper">
                <!-- Simplified back button -->
                <div class="back-button-container">
                    <a href="manageUsers.php" class="back-button">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                </div>
                
                <div class="settings-container">
                    <!-- First Table -->
                    <div class="settings-table">
                        <div class="table-header">
                            <i class="fas fa-user-edit"></i>
                            <h2>UPDATE <?php echo ($editUserData['name'] ?? 'USER') ?> ACCOUNT</h2>
                        </div>
                        <div class="divider"></div>
                        <form id="updateUserForm">
                            <div class="account-content">
                                <div class="input-group">
                                    <input type="text" id="updateName" name="name" 
                                        value="<?php echo htmlspecialchars($editUserData['name'] ?? ''); ?>" 
                                        data-original="<?php echo htmlspecialchars($editUserData['name'] ?? ''); ?>">
                                    <label>Name</label>
                                </div>
                                <div class="input-group">
                                    <input type="text" id="updateUsername" name="username" 
                                        value="<?php echo htmlspecialchars($editUserData['username'] ?? ''); ?>" 
                                        data-original="<?php echo htmlspecialchars($editUserData['username'] ?? ''); ?>">
                                    <label>Username</label>
                                </div>
                                <div class="input-group">
                                    <select id="updateRole" name="role" 
                                        data-original="<?php echo htmlspecialchars($editUserData['role'] ?? ''); ?>">
                                        <?php if (!empty($userGroups)): ?>
                                            <?php foreach ($userGroups as $group): ?>
                                                <option value="<?php echo htmlspecialchars($group); ?>" 
                                                    <?php echo ($editUserData['role'] ?? '') === $group ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($group); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <option value="admin" <?php echo ($editUserData['role'] ?? '') === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                            <option value="staff" <?php echo ($editUserData['role'] ?? '') === 'staff' ? 'selected' : ''; ?>>Staff</option>
                                        <?php endif; ?>
                                    </select>
                                    <label>User Role</label>
                                </div>
                                <div class="input-group">
                                    <select id="updateStatus" name="status" 
                                        data-original="<?php echo $editUserData['status'] ?? '0'; ?>">
                                        <option value="1" <?php echo ($editUserData['status'] ?? '0') == 1 ? 'selected' : ''; ?>>Active</option>
                                        <option value="0" <?php echo ($editUserData['status'] ?? '0') == 0 ? 'selected' : ''; ?>>Deactive</option>
                                    </select>
                                    <label>Status</label>
                                </div>
                                <button type="submit" class="btn-update" disabled>
                                    <i class="fas fa-save"></i> Update
                                </button>
                            </div>
                        </form>
                        <div class="message success" id="updateSuccessMessage">
                            <i class="fas fa-check-circle"></i>
                            Updated Successfully!
                        </div>
                    </div>

                    <!-- Second Table -->
                    <div class="settings-table">
                        <div class="table-header">
                            <i class="fas fa-key"></i>
                            <h2>CHANGE <?php echo ($editUserData['name'] ?? 'USER') ?> PASSWORD</h2>
                        </div>
                        <div class="divider"></div>
                        <form id="changePasswordForm">
                            <div class="account-content">
                                <div class="input-group">
                                    <div class="password-wrapper">
                                        <input type="password" id="oldPassword" placeholder="Type your current password">
                                        <button type="button" class="toggle-password" data-target="oldPassword">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <label>Current Password</label>
                                </div>
                                <div class="input-group">
                                    <div class="password-wrapper">
                                        <input type="password" id="newPassword" placeholder="Type your new password">
                                        <button type="button" class="toggle-password" data-target="newPassword">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <label>New Password</label>
                                </div>
                                <div class="password-requirements">
                                    <p class="requirement" data-requirement="length">
                                        <i class="fas fa-circle"></i> At least 8 characters
                                    </p>
                                    <p class="requirement" data-requirement="uppercase">
                                        <i class="fas fa-circle"></i> One uppercase letter
                                    </p>
                                    <p class="requirement" data-requirement="lowercase">
                                        <i class="fas fa-circle"></i> One lowercase letter
                                    </p>
                                    <p class="requirement" data-requirement="number">
                                        <i class="fas fa-circle"></i> One number
                                    </p>
                                </div>
                                <button type="submit" class="btn-change-password" disabled>
                                    <i class="fas fa-key"></i> Change
                                </button>
                            </div>
                        </form>
                        <div class="message success" id="passwordSuccessMessage">
                            <i class="fas fa-check-circle"></i>
                            Changed Successfully!
                        </div>
                    </div>
                </div>
            </div>
    
                <!-- Scripts -->
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Add real-time date and time update function
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

                // Form change detection for update form
                const updateForm = document.getElementById('updateUserForm');
                const passwordForm = document.getElementById('changePasswordForm');
                const updateBtn = updateForm.querySelector('.btn-update');
                const updateInputs = updateForm.querySelectorAll('input, select');

                updateInputs.forEach(input => {
                    input.addEventListener('input', () => {
                        let hasChanges = false;
                        updateInputs.forEach(field => {
                            if (field.value !== field.dataset.original) {
                                hasChanges = true;
                            }
                        });
                        updateBtn.disabled = !hasChanges;
                    });
                });

                // Password validation and requirements
                const oldPasswordInput = document.getElementById('oldPassword');
                const newPasswordInput = document.getElementById('newPassword');
                const passwordBtn = document.querySelector('.btn-change-password');
                const requirements = {
                    length: str => str.length >= 8,
                    uppercase: str => /[A-Z]/.test(str),
                    lowercase: str => /[a-z]/.test(str),
                    number: str => /[0-9]/.test(str)
                };

                function validatePasswordForm() {
                    const newValue = newPasswordInput.value;
                    let validRequirements = true;

                    // Check password requirements
                    Object.keys(requirements).forEach(req => {
                        const element = document.querySelector(`[data-requirement="${req}"]`);
                        const isValid = requirements[req](newValue);
                        element.classList.toggle('valid', isValid);
                        if (!isValid) validRequirements = false;
                    });

                    // Enable button only if old password is filled and new password meets requirements
                    passwordBtn.disabled = !(oldPasswordInput.value && validRequirements);
                }

                oldPasswordInput.addEventListener('input', validatePasswordForm);
                newPasswordInput.addEventListener('input', validatePasswordForm);

                // Toggle password visibility for both password fields
                document.querySelectorAll('.toggle-password').forEach(button => {
                    button.addEventListener('click', () => {
                        const targetId = button.getAttribute('data-target');
                        const input = document.getElementById(targetId);
                        const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                        input.setAttribute('type', type);
                        button.querySelector('i').classList.toggle('fa-eye');
                        button.querySelector('i').classList.toggle('fa-eye-slash');
                    });
                });

                // Modified password form submission with requirements reset
                passwordForm.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    
                    const formData = new FormData();
                    formData.append('change_password', '1');
                    formData.append('old_password', document.getElementById('oldPassword').value);
                    formData.append('new_password', document.getElementById('newPassword').value);

                    try {
                        const response = await fetch(window.location.href, {
                            method: 'POST',
                            body: formData
                        });
                        
                        const result = await response.json();
                        
                        if (result.success) {
                            const successMessage = document.getElementById('passwordSuccessMessage');
                            successMessage.classList.add('show');
                            setTimeout(() => {
                                successMessage.classList.remove('show');
                            }, 3000);
                            
                            // Clear password fields and reset requirements
                            document.getElementById('oldPassword').value = '';
                            document.getElementById('newPassword').value = '';
                            document.querySelectorAll('.requirement').forEach(req => {
                                req.classList.remove('valid');
                            });
                            passwordBtn.disabled = true;
                        } else {
                            alert('Error: ' + result.message);
                        }
                    } catch (error) {
                        alert('Error changing password');
                    }
                });

                // Update form submission
                updateForm.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    
                    try {
                        const formData = new FormData();
                        formData.append('update_user', '1');
                        formData.append('name', document.getElementById('updateName').value);
                        formData.append('username', document.getElementById('updateUsername').value);
                        formData.append('role', document.getElementById('updateRole').value);
                        formData.append('status', document.getElementById('updateStatus').value);

                        const response = await fetch(window.location.href, {
                            method: 'POST',
                            body: formData
                        });
                        
                        // Check if response is JSON
                        const contentType = response.headers.get('content-type');
                        if (!contentType || !contentType.includes('application/json')) {
                            throw new Error('Server returned non-JSON response');
                        }
                        
                        const result = await response.json();
                        
                        if (result.success) {
                            const successMessage = document.getElementById('updateSuccessMessage');
                            successMessage.classList.add('show');
                            setTimeout(() => {
                                successMessage.classList.remove('show');
                            }, 3000);
                            
                            // Update data-original attributes
                            updateInputs.forEach(input => {
                                input.dataset.original = input.value;
                            });
                            updateBtn.disabled = true;
                        } else {
                            alert('Error: ' + (result.message || 'Unknown error occurred'));
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        alert('Error updating user details. Please try again.');
                    }
                });
            });
        </script>
</body>
</html>