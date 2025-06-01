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

// For debugging purposes
error_log("Session ID: " . $_SESSION['user_id'] ?? 'Not set');
error_log("User Name: " . $userName);
error_log("Profile Image: " . $profileImage);
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

        .main-content {
            margin-left: 249px;
            margin-top: 60px;
            padding: 20px;
            min-height: calc(100vh - 60px);
        }

        .content-header {
            background-color: #363333;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .content-header h2 {
            color: #F8B83C;
            font-family: 'Montserrat', sans-serif;
            font-size: 24px;
            margin: 0;
        }

        .filters {
            display: flex;
            gap: 15px;
        }

        .filters select,
        .filters input {
            padding: 8px 12px;
            border: none;
            border-radius: 4px;
            background-color: #403E3E;
            color: #AEB2B7;
            font-family: 'Century Gothic', sans-serif;
        }

        .search-btn {
            background-color: #750605;
            color: #F8B83C;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .search-btn:hover {
            background-color: #8f0806;
        }

        .history-table {
            background-color: #363333;
            border-radius: 8px;
            padding: 20px;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            color: #AEB2B7;
            font-family: 'Century Gothic', sans-serif;
        }

        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #403E3E;
        }

        th {
            background-color: #1E1E1E;
            color: #F8B83C;
            font-weight: bold;
        }

        tr:hover {
            background-color: #403E3E;
        }

        .pagination {
            margin-top: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 15px;
            color: #AEB2B7;
            font-family: 'Century Gothic', sans-serif;
        }

        .page-btn {
            background-color: #403E3E;
            border: none;
            color: #AEB2B7;
            padding: 8px 12px;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .page-btn:hover {
            background-color: #750605;
            color: #F8B83C;
        }

        /* Updated Styles */
        .history-section {
            margin-bottom: 30px;
            scroll-margin-top: 100px;
        }

        .section-header {
            background-color: #1E1E1E;
            padding: 15px 20px;
            border-radius: 8px 8px 0 0;
            border-bottom: 2px solid #750605;
        }

        .section-header h3 {
            color: #F8B83C;
            font-family: 'Montserrat', sans-serif;
            font-size: 18px;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .history-table {
            background-color: #363333;
            border-radius: 0 0 8px 8px;
            padding: 20px;
            overflow-x: auto;
        }

        /* Value change indicators */
        .old-value {
            color: #ff4747;
            text-decoration: line-through;
            margin-right: 5px;
        }

        .new-value {
            color: #4CAF50;
            font-weight: 500;
        }

        .password-changed, .image-changed {
            background-color: #750605;
            color: #F8B83C;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 12px;
        }

        /* Smooth scrolling */
        html {
            scroll-behavior: smooth;
        }

        /* Enhanced table styles */
        table th {
            background-color: #1E1E1E;
            color: #F8B83C;
            font-weight: bold;
            white-space: nowrap;
        }

        table td {
            white-space: nowrap;
        }

        /* Responsive table */
        .history-table {
            max-width: 100%;
            overflow-x: auto;
        }

        /* Frame filter dropdown enhancement */
        #frameFilter {
            min-width: 200px;
            cursor: pointer;
        }

        #frameFilter option {
            padding: 8px;
        }

        .deleted-value {
            color: #ff4747;
            font-style: italic;
        }

        /* Add these styles to make the changes more readable */
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background: rgba(45, 45, 45, 0.25);
            backdrop-filter: blur(10px);
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            color: #AEB2B7;
            font-family: 'Century Gothic', sans-serif;
        }

        th {
            background: rgba(117, 6, 5, 0.8);
            color: #F8B83C;
            font-weight: bold;
        }

        tr:hover {
            background: rgba(64, 62, 62, 0.3);
        }

        /* Style for the changes column */
        td:last-child {
            color: #F8B83C;
            font-style: italic;
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
                    <li class="dropdown open">
                        <a href="#" class="dropdown-toggle">
                            <i class="fas fa-history"></i>System Logs
                            <i class="fas fa-chevron-down arrow"></i>
                        </a>
                        <ul class="submenu">
                            <li class="active"><a href="editHistory.php"><i class="fas fa-pen"></i>Edit History</a></li>
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
                <div class="content-header">
                    <h2>Edit History</h2>
                    <div class="filters">
                        <select id="frameFilter" onchange="scrollToFrame(this.value)">
                            <option value="all">Jump to Frame</option>
                            <option value="groups">Groups History</option>
                            <option value="users">Users History</option>
                            <option value="categories">Categories History</option>
                            <option value="products">Products History</option>
                            <option value="sales">Sales History</option>
                            <option value="profile">Profile History</option>
                        </select>
                        <input type="date" id="dateFilter">
                        <button class="search-btn"><i class="fas fa-search"></i> Search</button>
                    </div>
                </div>

                <!-- Groups History -->
                <div id="groups" class="history-section">
                    <div class="section-header">
                        <h3><i class="fas fa-users-cog"></i> Groups Edit History</h3>
                    </div>
                    <div class="history-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>Date & Time</th>
                                    <th>Group ID</th>
                                    <th>Action</th>
                                    <th>Details</th>
                                    <th>Modified By</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Fetch group history from both tables
                                $group_history_query = "
                                    (SELECT 
                                        h.created_at as timestamp,
                                        'activity_logs' as source,
                                        h.action,
                                        h.details,
                                        u.username as modifier_name,
                                        NULL as item_id
                                    FROM activity_logs h
                                    LEFT JOIN users u ON h.user_id = u.id
                                    WHERE h.action LIKE '%group%')
                                    
                                    UNION ALL
                                    
                                    (SELECT 
                                        eh.timestamp,
                                        'edit_history' as source,
                                        eh.action,
                                        eh.changes as details,
                                        u.username as modifier_name,
                                        eh.item_id
                                    FROM edit_history eh
                                    LEFT JOIN users u ON eh.user_id = u.id
                                    WHERE eh.item_type = 'group')
                                    
                                    ORDER BY timestamp DESC
                                ";

                                try {
                                    $group_stmt = $pdo->query($group_history_query);
                                    $rowCount = $group_stmt->rowCount();

                                    if ($rowCount > 0) {
                                        while ($history = $group_stmt->fetch(PDO::FETCH_ASSOC)) {
                                            echo "<tr>";
                                            echo "<td>" . htmlspecialchars($history['timestamp']) . "</td>";
                                            
                                            // Group ID column
                                            echo "<td>";
                                            if ($history['item_id']) {
                                                echo "#GRP" . str_pad($history['item_id'], 3, '0', STR_PAD_LEFT);
                                            } else {
                                                echo "-";
                                            }
                                            echo "</td>";
                                            
                                            // Action column
                                            echo "<td>" . htmlspecialchars($history['action']) . "</td>";
                                            
                                            // Details column
                                            echo "<td>";
                                            if ($history['source'] === 'edit_history') {
                                                // Handle structured changes data
                                                $changes = json_decode($history['details'], true);
                                                if (json_last_error() === JSON_ERROR_NONE && !empty($changes)) {
                                                    foreach ($changes as $field => $change) {
                                                        if (is_array($change) && isset($change['old']) && isset($change['new'])) {
                                                            echo htmlspecialchars($field) . ": ";
                                                            echo "<span class='old-value'>" . htmlspecialchars($change['old']) . "</span> → ";
                                                            echo "<span class='new-value'>" . htmlspecialchars($change['new']) . "</span><br>";
                                                        }
                                                    }
                                                } else {
                                                    echo htmlspecialchars($history['details'] ?? '-');
                                                }
                                            } else {
                                                // Display regular details
                                                echo htmlspecialchars($history['details'] ?? '-');
                                            }
                                            echo "</td>";
                                            
                                            // Modified By column
                                            echo "<td>" . htmlspecialchars($history['modifier_name'] ?? 'Unknown') . "</td>";
                                            echo "</tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='5' style='text-align: center;'>No group history records found</td></tr>";
                                    }
                                } catch (PDOException $e) {
                                    error_log("Database error in groups history: " . $e->getMessage());
                                    echo "<tr><td colspan='5' style='text-align: center;'>Error fetching history data</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Users History -->
                <div id="users" class="history-section">
                    <div class="section-header">
                        <h3><i class="fas fa-user-edit"></i> Users Edit History</h3>
                    </div>
                    <div class="history-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>Date & Time</th>
                                    <th>User ID</th>
                                    <th>Name</th>
                                    <th>Username</th>
                                    <th>User Role</th>
                                    <th>Status</th>
                                    <th>Password</th>
                                    <th>Modified By</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Modified query to include password reset actions
                                $user_history_query = "
                                    SELECT 
                                        al.*,
                                        u.username as modifier_name,
                                        u2.id as edited_user_id,
                                        u2.name as edited_user_name
                                    FROM activity_logs al
                                    LEFT JOIN users u ON al.user_id = u.id
                                    LEFT JOIN users u2 ON SUBSTRING_INDEX(SUBSTRING_INDEX(al.details, 'ID ', -1), ':', 1) = u2.id
                                    WHERE al.action = 'User Update' 
                                    OR al.action = 'Password Reset'
                                    OR al.action = 'Reset Password'
                                    ORDER BY al.created_at DESC
                                ";

                                try {
                                    $history_stmt = $pdo->query($user_history_query);
                                    while ($history = $history_stmt->fetch()) {
                                        // Parse the details string to extract changes
                                        $details = $history['details'];
                                        preg_match('/ID (\d+): (.+)/', $details, $matches);
                                        
                                        if (empty($matches)) {
                                            continue;
                                        }

                                        $changes = explode(", ", $matches[2]);
                                        $changeData = [
                                            'name' => '',
                                            'username' => '',
                                            'role' => '',
                                            'status' => '',
                                            'password' => ''
                                        ];

                                        // Parse each change
                                        foreach ($changes as $change) {
                                            if (strpos($change, 'Name changed from') !== false) {
                                                preg_match("/Name changed from '(.+)' to '(.+)'/", $change, $nameMatches);
                                                $changeData['name'] = [
                                                    'old' => $nameMatches[1] ?? '',
                                                    'new' => $nameMatches[2] ?? ''
                                                ];
                                            } elseif (strpos($change, 'Username changed from') !== false) {
                                                preg_match("/Username changed from '(.+)' to '(.+)'/", $change, $userMatches);
                                                $changeData['username'] = [
                                                    'old' => $userMatches[1] ?? '',
                                                    'new' => $userMatches[2] ?? ''
                                                ];
                                            } elseif (strpos($change, 'Role changed from') !== false) {
                                                preg_match("/Role changed from '(.+)' to '(.+)'/", $change, $roleMatches);
                                                $changeData['role'] = [
                                                    'old' => $roleMatches[1] ?? '',
                                                    'new' => $roleMatches[2] ?? ''
                                                ];
                                            } elseif (strpos($change, 'Status changed from') !== false) {
                                                preg_match("/Status changed from '(.+)' to '(.+)'/", $change, $statusMatches);
                                                $changeData['status'] = [
                                                    'old' => $statusMatches[1] ?? '',
                                                    'new' => $statusMatches[2] ?? ''
                                                ];
                                            } elseif (strpos($change, 'Password reset') !== false || 
                                                     $history['action'] == 'Password Reset' || 
                                                     $history['action'] == 'Reset Password') {
                                                $changeData['password'] = [
                                                    'action' => 'reset'
                                                ];
                                            }
                                        }

                                        echo "<tr>";
                                        echo "<td>" . htmlspecialchars($history['created_at']) . "</td>";
                                        echo "<td>#USR" . str_pad($history['edited_user_id'], 3, '0', STR_PAD_LEFT) . "</td>";
                                        
                                        // Name column
                                        echo "<td>";
                                        if (!empty($changeData['name'])) {
                                            echo "<span class='old-value' style='text-decoration: line-through;'>" . htmlspecialchars($changeData['name']['old']) . "</span> → ";
                                            echo "<span class='new-value'>" . htmlspecialchars($changeData['name']['new']) . "</span>";
                                        } else {
                                            echo htmlspecialchars($history['edited_user_name']);
                                        }
                                        echo "</td>";
                                        
                                        // Username column
                                        echo "<td>";
                                        if (!empty($changeData['username'])) {
                                            echo "<span class='old-value' style='text-decoration: line-through;'>" . htmlspecialchars($changeData['username']['old']) . "</span> → ";
                                            echo "<span class='new-value'>" . htmlspecialchars($changeData['username']['new']) . "</span>";
                                        } else {
                                            echo "-";
                                        }
                                        echo "</td>";
                                        
                                        // Role column
                                        echo "<td>";
                                        if (!empty($changeData['role'])) {
                                            echo "<span class='old-value' style='text-decoration: line-through;'>" . htmlspecialchars($changeData['role']['old']) . "</span> → ";
                                            echo "<span class='new-value'>" . htmlspecialchars($changeData['role']['new']) . "</span>";
                                        } else {
                                            echo "-";
                                        }
                                        echo "</td>";
                                        
                                        // Status column
                                        echo "<td>";
                                        if (!empty($changeData['status'])) {
                                            echo "<span class='old-value' style='text-decoration: line-through;'>" . htmlspecialchars($changeData['status']['old']) . "</span> → ";
                                            echo "<span class='new-value'>" . htmlspecialchars($changeData['status']['new']) . "</span>";
                                        } else {
                                            echo "-";
                                        }
                                        echo "</td>";
                                        
                                        // Password column
                                        echo "<td>";
                                        if (!empty($changeData['password']) || 
                                            $history['action'] == 'Password Reset' || 
                                            $history['action'] == 'Reset Password') {
                                            echo "<span class='password-changed'>Reset to Default</span>";
                                        } else {
                                            echo "-";
                                        }
                                        echo "</td>";
                                        
                                        // Modified By column
                                        echo "<td>" . htmlspecialchars($history['modifier_name']) . "</td>";
                                        echo "</tr>";
                                    }
                                } catch (PDOException $e) {
                                    error_log("Database error in users history: " . $e->getMessage());
                                    echo "<tr><td colspan='8'>Error fetching history data</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Categories History -->
                <div id="categories" class="history-section">
                    <div class="section-header">
                        <h3><i class="fas fa-tags"></i> Categories Edit History</h3>
                    </div>
                    <div class="history-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>Date & Time</th>
                                    <th>Category ID</th>
                                    <th>Action</th>
                                    <th>Details</th>
                                    <th>Modified By</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Fetch category history from both tables
                                $cat_history_query = "
                                    (SELECT 
                                        h.created_at as timestamp,
                                        'activity_logs' as source,
                                        h.action,
                                        h.details,
                                        u.username as modifier_name,
                                        NULL as item_id
                                    FROM activity_logs h
                                    LEFT JOIN users u ON h.user_id = u.id
                                    WHERE h.action LIKE '%category%')
                                    
                                    UNION ALL
                                    
                                    (SELECT 
                                        eh.timestamp,
                                        'edit_history' as source,
                                        eh.action,
                                        eh.changes as details,
                                        u.username as modifier_name,
                                        eh.item_id
                                    FROM edit_history eh
                                    LEFT JOIN users u ON eh.user_id = u.id
                                    WHERE eh.item_type = 'category')
                                    
                                    ORDER BY timestamp DESC
                                ";
                                
                                try {
                                    $cat_history_stmt = $pdo->query($cat_history_query);
                                    while ($history = $cat_history_stmt->fetch()) {
                                        echo "<tr>";
                                        echo "<td>" . htmlspecialchars($history['timestamp']) . "</td>";
                                        
                                        // Category ID column
                                        echo "<td>";
                                        if ($history['item_id']) {
                                            echo "#CAT" . str_pad($history['item_id'], 3, '0', STR_PAD_LEFT);
                                        } else {
                                            echo "-";
                                        }
                                        echo "</td>";
                                        
                                        // Action column
                                        echo "<td>";
                                        $action = strtolower($history['action']);
                                        if (strpos($action, 'delete') !== false) {
                                            echo "<span class='deleted-value' style='color: #ff4747;'>Category Deleted</span>";
                                        } elseif (strpos($action, 'edit') !== false) {
                                            echo "<span class='edit-value' style='color: #2196F3;'>Category Updated</span>";
                                        } else {
                                            echo htmlspecialchars($history['action']);
                                        }
                                        echo "</td>";
                                        
                                        // Details column
                                        echo "<td>";
                                        if ($history['source'] === 'edit_history') {
                                            $changes = json_decode($history['details'], true);
                                            if (json_last_error() === JSON_ERROR_NONE && !empty($changes)) {
                                                if ($action === 'delete') {
                                                    echo "<span class='deleted-value' style='color: #ff4747;'>";
                                                    echo "Deleted category: " . htmlspecialchars($changes['category_name']);
                                                    if (isset($changes['associated_products'])) {
                                                        echo "<br>Associated products deleted: " . $changes['associated_products'];
                                                    }
                                                    echo "</span>";
                                                } else {
                                                    foreach ($changes as $field => $change) {
                                                        if (is_array($change) && isset($change['old']) && isset($change['new'])) {
                                                            echo htmlspecialchars($field) . ": ";
                                                            echo "<span class='old-value' style='text-decoration: line-through;'>" . 
                                                                 htmlspecialchars($change['old']) . "</span> → ";
                                                            echo "<span class='new-value'>" . 
                                                                 htmlspecialchars($change['new']) . "</span><br>";
                                                        }
                                                    }
                                                }
                                            } else {
                                                echo htmlspecialchars($history['details'] ?? '-');
                                            }
                                        } else {
                                            // Display regular details from activity_logs
                                            echo htmlspecialchars($history['details'] ?? '-');
                                        }
                                        echo "</td>";
                                        
                                        // Modified By column
                                        echo "<td>" . htmlspecialchars($history['modifier_name'] ?? 'Unknown') . "</td>";
                                        echo "</tr>";
                                    }
                                } catch (PDOException $e) {
                                    error_log("Database error in categories history: " . $e->getMessage());
                                    echo "<tr><td colspan='5'>Error fetching history data</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Products History -->
                <div id="products" class="history-section">
                    <div class="section-header">
                        <h3><i class="fas fa-box"></i> Products Edit History</h3>
                    </div>
                    <div class="history-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>Date & Time</th>
                                    <th>Product ID</th>
                                    <th>Name</th>
                                    <th>Category</th>
                                    <th>Quantity</th>
                                    <th>Buying Price</th>
                                    <th>Selling Price</th>
                                    <th>Image</th>
                                    <th>Modified By</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Debug function
                                function debugValue($value) {
                                    error_log("Value type: " . gettype($value));
                                    error_log("Value content: " . print_r($value, true));
                                }

                                // Fetch product edit history
                                $history_query = "
                                    SELECT 
                                        eh.*,
                                        u.username as modifier_name,
                                        p.product_title
                                    FROM edit_history eh
                                    LEFT JOIN users u ON eh.user_id = u.id
                                    LEFT JOIN products p ON eh.item_id = p.id
                                    WHERE eh.item_type = 'product'
                                    ORDER BY eh.timestamp DESC
                                ";
                                try {
                                    $history_stmt = $pdo->query($history_query);
                                    while ($history = $history_stmt->fetch()) {
                                        $changes = json_decode($history['changes'], true);
                                        
                                        echo "<tr>";
                                        echo "<td>" . htmlspecialchars($history['timestamp']) . "</td>";
                                        echo "<td>#PRD" . str_pad($history['item_id'], 3, '0', STR_PAD_LEFT) . "</td>";
                                        
                                        // Name column
                                        echo "<td>";
                                        if ($history['action'] === 'delete') {
                                            echo "<span class='deleted-value' style='color: #ff4747;'>" . 
                                                 htmlspecialchars($changes['product_title']) . 
                                                 " (Deleted)</span>";
                                        } else if (isset($changes['product_title'])) {
                                            echo "<span class='old-value' style='text-decoration: line-through;'>" . 
                                                 htmlspecialchars($changes['product_title']['old']) . "</span> → ";
                                            echo "<span class='new-value'>" . 
                                                 htmlspecialchars($changes['product_title']['new']) . "</span>";
                                        } else {
                                            echo htmlspecialchars($history['product_title'] ?? '-');
                                        }
                                        echo "</td>";
                                        
                                        // Category column
                                        echo "<td>";
                                        if ($history['action'] === 'delete') {
                                            echo "<span class='deleted-value'>-</span>";
                                        } else if (isset($changes['category_id'])) {
                                            // ... existing category code ...
                                        }
                                        echo "</td>";
                                        
                                        // Quantity column
                                        echo "<td>";
                                        if ($history['action'] === 'delete') {
                                            echo "<span class='deleted-value'>-</span>";
                                        } else if (isset($changes['quantity'])) {
                                            echo "<span class='old-value' style='text-decoration: line-through;'>" . 
                                                 htmlspecialchars($changes['quantity']['old']) . "</span> → ";
                                            echo "<span class='new-value'>" . 
                                                 htmlspecialchars($changes['quantity']['new']) . "</span>";
                                        } else {
                                            echo "-";
                                        }
                                        echo "</td>";
                                        
                                        // Buying Price column
                                        echo "<td>";
                                        if ($history['action'] === 'delete') {
                                            echo "<span class='deleted-value'>-</span>";
                                        } else if (isset($changes['buying_price'])) {
                                            echo "<span class='old-value' style='text-decoration: line-through;'>₱" . 
                                                 number_format($changes['buying_price']['old'], 2) . "</span> → ";
                                            echo "<span class='new-value'>₱" . 
                                                 number_format($changes['buying_price']['new'], 2) . "</span>";
                                        } else {
                                            echo "-";
                                        }
                                        echo "</td>";
                                        
                                        // Selling Price column
                                        echo "<td>";
                                        if ($history['action'] === 'delete') {
                                            echo "<span class='deleted-value'>-</span>";
                                        } else if (isset($changes['selling_price'])) {
                                            echo "<span class='old-value' style='text-decoration: line-through;'>₱" . 
                                                 number_format($changes['selling_price']['old'], 2) . "</span> → ";
                                            echo "<span class='new-value'>₱" . 
                                                 number_format($changes['selling_price']['new'], 2) . "</span>";
                                        } else {
                                            echo "-";
                                        }
                                        echo "</td>";
                                        
                                        // Image column
                                        echo "<td>";
                                        if ($history['action'] === 'delete') {
                                            echo "<span class='deleted-value'>-</span>";
                                        } else if (isset($changes['image_path'])) {
                                            echo "<span class='old-value' style='text-decoration: line-through;'>" . 
                                                 htmlspecialchars($changes['image_path']['old']) . "</span> → ";
                                            echo "<span class='new-value'>" . 
                                                 htmlspecialchars($changes['image_path']['new']) . "</span>";
                                        } else {
                                            echo "-";
                                        }
                                        echo "</td>";
                                        
                                        // Modified By column
                                        echo "<td>" . htmlspecialchars($history['modifier_name'] ?? 'Unknown') . "</td>";
                                        echo "</tr>";
                                    }
                                } catch (PDOException $e) {
                                    error_log("Database error: " . $e->getMessage());
                                    echo "<tr><td colspan='9'>Error fetching history data</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Sales History -->
                <div id="sales" class="history-section">
                    <div class="section-header">
                        <h3><i class="fas fa-shopping-cart"></i> Sales Edit History</h3>
                    </div>
                    <div class="history-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>Date & Time</th>
                                    <th>Sale ID</th>
                                    <th>Action</th>
                                    <th>Product</th>
                                    <th>Quantity</th>
                                    <th>Price</th>
                                    <th>Total</th>
                                    <th>Modified By</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $sales_history_query = "
                                    SELECT 
                                        al.*,
                                        u.username as modifier_name
                                    FROM activity_logs al
                                    LEFT JOIN users u ON al.user_id = u.id
                                    WHERE al.action LIKE '%sale%'
                                    OR al.action LIKE '%Sale%'
                                    ORDER BY al.created_at DESC
                                ";

                                try {
                                    $sales_stmt = $pdo->query($sales_history_query);
                                    
                                    if ($sales_stmt->rowCount() > 0) {
                                        while ($history = $sales_stmt->fetch(PDO::FETCH_ASSOC)) {
                                            $details = json_decode($history['details'], true);
                                            
                                            echo "<tr>";
                                            // Date & Time
                                            echo "<td>" . htmlspecialchars($history['created_at']) . "</td>";
                                            
                                            // Sale ID
                                            echo "<td>";
                                            if (isset($details['Sale ID'])) {
                                                echo "#SLS" . str_pad($details['Sale ID'], 3, '0', STR_PAD_LEFT);
                                            } else {
                                                echo "-";
                                            }
                                            echo "</td>";
                                            
                                            // Action
                                            echo "<td>";
                                            if ($history['action'] == 'Sale Deleted') {
                                                echo "<span style='color: #ff4747;'>" . htmlspecialchars($history['action']) . "</span>";
                                            } else {
                                                echo htmlspecialchars($history['action']);
                                            }
                                            echo "</td>";

                                            // Product Name
                                            echo "<td>";
                                            if (isset($details['Product'])) {
                                                echo htmlspecialchars($details['Product']);
                                            } else {
                                                echo "-";
                                            }
                                            echo "</td>";
                                            
                                            // Quantity column
                                            echo "<td>";
                                            if (isset($details['Changes']['quantity'])) {
                                                if ($details['Changes']['quantity']['old'] !== $details['Changes']['quantity']['new']) {
                                                    echo "<span class='old-value' style='text-decoration: line-through;'>" . 
                                                         htmlspecialchars($details['Changes']['quantity']['old']) . "</span> → ";
                                                    echo "<span class='new-value'>" . 
                                                         htmlspecialchars($details['Changes']['quantity']['new']) . "</span>";
                                                } else {
                                                    echo htmlspecialchars($details['Changes']['quantity']['new']);
                                                }
                                            } elseif ($history['action'] == 'Sale Deleted' && isset($details['Details']['quantity'])) {
                                                echo "<span class='deleted-value'>" . htmlspecialchars($details['Details']['quantity']) . "</span>";
                                            } else {
                                                echo "-";
                                            }
                                            echo "</td>";
                                            
                                            // Price column
                                            echo "<td>";
                                            if (isset($details['Changes']['price'])) {
                                                if ($details['Changes']['price']['old'] !== $details['Changes']['price']['new']) {
                                                    echo "<span class='old-value' style='text-decoration: line-through;'>₱" . 
                                                         number_format($details['Changes']['price']['old'], 2) . "</span> → ";
                                                    echo "<span class='new-value'>₱" . 
                                                         number_format($details['Changes']['price']['new'], 2) . "</span>";
                                                } else {
                                                    echo "₱" . number_format($details['Changes']['price']['new'], 2);
                                                }
                                            } elseif ($history['action'] == 'Sale Deleted' && isset($details['Details']['price'])) {
                                                echo "<span class='deleted-value'>₱" . htmlspecialchars($details['Details']['price']) . "</span>";
                                            } else {
                                                echo "-";
                                            }
                                            echo "</td>";
                                            
                                            // Total column
                                            echo "<td>";
                                            if (isset($details['Changes']['price']) && isset($details['Changes']['quantity'])) {
                                                $oldTotal = $details['Changes']['price']['old'] * $details['Changes']['quantity']['old'];
                                                $newTotal = $details['Changes']['price']['new'] * $details['Changes']['quantity']['new'];
                                                if ($oldTotal !== $newTotal) {
                                                    echo "<span class='old-value' style='text-decoration: line-through;'>₱" . 
                                                         number_format($oldTotal, 2) . "</span> → ";
                                                    echo "<span class='new-value'>₱" . 
                                                         number_format($newTotal, 2) . "</span>";
                                                } else {
                                                    echo "₱" . number_format($newTotal, 2);
                                                }
                                            } elseif ($history['action'] == 'Sale Deleted' && isset($details['Details']['total'])) {
                                                echo "<span class='deleted-value'>₱" . htmlspecialchars($details['Details']['total']) . "</span>";
                                            } else {
                                                echo "-";
                                            }
                                            echo "</td>";
                                            
                                            // Modified By
                                            echo "<td>" . htmlspecialchars($history['modifier_name'] ?? 'Unknown') . "</td>";
                                            
                                            echo "</tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='8' style='text-align: center;'>No sales history records found</td></tr>";
                                    }
                                } catch (PDOException $e) {
                                    error_log("Database error in sales history: " . $e->getMessage());
                                    echo "<tr><td colspan='8' style='text-align: center;'>Error fetching history data: " . $e->getMessage() . "</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Profile History -->
                <div id="profile" class="history-section">
                    <div class="section-header">
                        <h3><i class="fas fa-user-circle"></i> Profile Edit History</h3>
                    </div>
                    <div class="history-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>Date & Time</th>
                                    <th>Profile ID</th>
                                    <th>Photo</th>
                                    <th>Name</th>
                                    <th>Username</th>
                                    <th>Password</th>
                                    <th>Modified By</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Modify the profile history query to use edit_history table
                                $profile_history_query = "
                                    SELECT 
                                        eh.*,
                                        u.username as modifier_name,
                                        u2.name as edited_user_name
                                    FROM edit_history eh
                                    LEFT JOIN users u ON eh.user_id = u.id
                                    LEFT JOIN users u2 ON eh.item_id = u2.id
                                    WHERE eh.item_type = 'profile'
                                    AND (eh.action = 'Profile Update' OR eh.action = 'Password Reset')
                                    ORDER BY eh.timestamp DESC
                                ";

                                try {
                                    $profile_stmt = $pdo->query($profile_history_query);
                                    while ($history = $profile_stmt->fetch()) {
                                        $changes = json_decode($history['changes'], true);
                                        
                                        echo "<tr>";
                                        echo "<td>" . htmlspecialchars($history['timestamp']) . "</td>";
                                        echo "<td>#PRF" . str_pad($history['item_id'], 3, '0', STR_PAD_LEFT) . "</td>";
                                        
                                        // Photo column
                                        echo "<td>";
                                        if (isset($changes['profile_image'])) {
                                            echo "<span class='image-changed'>Updated</span>";
                                        } else {
                                            echo "-";
                                        }
                                        echo "</td>";
                                        
                                        // Name column
                                        echo "<td>" . htmlspecialchars($history['edited_user_name'] ?? '-') . "</td>";
                                        
                                        // Username column
                                        echo "<td>";
                                        if (isset($changes['username'])) {
                                            echo "<span class='old-value' style='text-decoration: line-through;'>" . 
                                                 htmlspecialchars($changes['username']['old']) . "</span> → ";
                                            echo "<span class='new-value'>" . 
                                                 htmlspecialchars($changes['username']['new']) . "</span>";
                                        } else {
                                            echo "-";
                                        }
                                        echo "</td>";
                                        
                                        // Password column
                                        echo "<td>";
                                        if ($history['action'] == 'Password Reset' || 
                                            (isset($changes['password']) && isset($changes['password']['action']) && 
                                             $changes['password']['action'] == 'reset')) {
                                            echo "<span class='password-changed' style='color: #F8B83C;'>Reset to Default</span>";
                                        } else if (isset($changes['password'])) {
                                            echo "<span class='password-changed'>Changed</span>";
                                        } else {
                                            echo "-";
                                        }
                                        echo "</td>";
                                        
                                        // Modified By column
                                        echo "<td>" . htmlspecialchars($history['modifier_name'] ?? 'Unknown') . "</td>";
                                        echo "</tr>";
                                    }
                                } catch (PDOException $e) {
                                    error_log("Database error in profile history: " . $e->getMessage());
                                    echo "<tr><td colspan='7'>Error fetching history data</td></tr>";
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
                // Add datetime update functionality
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

                // Add date filter functionality
                const dateFilter = document.getElementById('dateFilter');
                const searchBtn = document.querySelector('.search-btn');
                
                searchBtn.addEventListener('click', function() {
                    const selectedDate = dateFilter.value;
                    if (!selectedDate) {
                        alert('Please select a date to filter');
                        return;
                    }

                    // Convert selected date to comparable format (YYYY-MM-DD)
                    const filterDate = new Date(selectedDate).toISOString().split('T')[0];
                    let firstMatch = null;

                    // Process each history section separately
                    const historySections = document.querySelectorAll('.history-section');
                    
                    historySections.forEach(section => {
                        const tbody = section.querySelector('table tbody');
                        const rows = tbody.querySelectorAll('tr:not(.no-results)');
                        let hasVisibleRows = false;

                        // Check each row in this section
                        rows.forEach(row => {
                            const dateCell = row.querySelector('td:first-child');
                            const cellDate = new Date(dateCell.textContent).toISOString().split('T')[0];
                            
                            if (cellDate === filterDate) {
                                row.style.display = '';
                                hasVisibleRows = true;
                                if (!firstMatch) {
                                    firstMatch = section;
                                }
                            } else {
                                row.style.display = 'none';
                            }
                        });

                        // Only handle "no results" message if the section had data to begin with
                        if (rows.length > 0) {
                            let noResultsRow = tbody.querySelector('.no-results');
                            
                            if (!hasVisibleRows) {
                                if (!noResultsRow) {
                                    noResultsRow = document.createElement('tr');
                                    noResultsRow.className = 'no-results';
                                    noResultsRow.innerHTML = `
                                        <td colspan="100%" style="text-align: center; padding: 20px;">
                                            No records found for ${selectedDate}
                                        </td>
                                    `;
                                    tbody.appendChild(noResultsRow);
                                }
                            } else if (noResultsRow) {
                                noResultsRow.remove();
                            }
                        }
                    });

                    // Scroll to first match if found
                    if (firstMatch) {
                        firstMatch.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                });

                // Add clear filter functionality when date is cleared
                dateFilter.addEventListener('change', function() {
                    if (!this.value) {
                        // Show all rows except "no results" messages
                        const allRows = document.querySelectorAll('table tbody tr:not(.no-results)');
                        allRows.forEach(row => {
                            row.style.display = '';
                        });
                        
                        // Remove any "No results" messages
                        const noResultsRows = document.querySelectorAll('.no-results');
                        noResultsRows.forEach(row => row.remove());
                    }
                });
            });

            function scrollToFrame(frameId) {
                if (frameId === 'all') return;
                
                const element = document.getElementById(frameId);
                if (element) {
                    element.scrollIntoView({ behavior: 'smooth' });
                }
            }
        </script>
</body>
</html>