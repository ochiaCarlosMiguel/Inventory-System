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

// For debugging purposes (remove in production)
// error_log("User ID: " . $_SESSION['user_id'] ?? 'not set');
// error_log("User Name: " . $userName);
// error_log("Profile Image: " . $profileImage);

// Add this after the database connection
$group_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch group data
try {
    $stmt = $pdo->prepare("SELECT group_name, group_level, status FROM user_groups WHERE id = ?");
    $stmt->execute([$group_id]);
    $groupData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$groupData) {
        // Redirect if group not found
        header('Location: manageGroups.php');
        exit();
    }
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
}

// Add this after fetching group data
error_log("Group Data: " . print_r($groupData, true));

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get the old values BEFORE making any updates
        $oldStmt = $pdo->prepare("SELECT group_name, group_level, status FROM user_groups WHERE id = ?");
        $oldStmt->execute([$group_id]);
        $oldData = $oldStmt->fetch(PDO::FETCH_ASSOC);

        // Debug: Log the values
        error_log("Old Data: " . print_r($oldData, true));
        error_log("POST Data: " . print_r($_POST, true));

        // Convert status values for comparison
        $oldStatus = $oldData['status'] == 1 ? 'active' : 'inactive';
        $newStatus = $_POST['status'];
        
        // Convert status to integer for database storage
        $statusValue = ($newStatus === 'active') ? 1 : 0;
        
        // Update the group in database
        $stmt = $pdo->prepare("UPDATE user_groups SET status = ? WHERE id = ?");
        $result = $stmt->execute([
            $statusValue,
            $group_id
        ]);

        if ($result) {
            // Log the changes in edit_history table
            $changes = [];
            if ($oldStatus !== $newStatus) {
                $changes['status'] = [
                    'old' => $oldStatus,
                    'new' => $newStatus
                ];
            }
            
            if (!empty($changes)) {
                $changesJson = json_encode($changes);
                $historyStmt = $pdo->prepare("
                    INSERT INTO edit_history 
                    (user_id, action, item_type, item_id, changes, timestamp) 
                    VALUES (?, 'Update', 'group', ?, ?, NOW())
                ");
                
                $historyResult = $historyStmt->execute([
                    $_SESSION['user_id'],
                    $group_id,
                    $changesJson
                ]);
            }
            
            $updateMessage = "Group status updated successfully!";
            
            // Refresh group data after update
            $stmt = $pdo->prepare("SELECT group_name, group_level, status FROM user_groups WHERE id = ?");
            $stmt->execute([$group_id]);
            $groupData = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
    } catch (PDOException $e) {
        error_log("Update Error: " . $e->getMessage());
        $updateMessage = "An error occurred while updating the group status.";
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

        .main-content {
            margin-left: 249px;
            margin-top: 60px;
            padding: 20px;
            min-height: calc(100vh - 60px);
        }

        .form-container {
            background: rgba(45, 45, 45, 0.25);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
            padding: 30px;
            border-radius: 16px;
            max-width: 600px;
            margin: 20px auto;
        }

        h2 {
            color: #F8B83C;
            font-family: 'Montserrat', sans-serif;
            margin-bottom: 30px;
            text-align: center;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            color: #AEB2B7;
            font-family: 'Century Gothic', sans-serif;
            margin-bottom: 8px;
        }

        input, select {
            width: 100%;
            padding: 10px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            background: rgba(64, 62, 62, 0.3);
            color: #AEB2B7;
            font-family: 'Century Gothic', sans-serif;
            backdrop-filter: blur(5px);
            -webkit-backdrop-filter: blur(5px);
        }

        input:focus, select:focus {
            outline: none;
            border-color: rgba(248, 184, 60, 0.5);
            box-shadow: 0 0 10px rgba(248, 184, 60, 0.2);
        }

        .update-btn {
            background: rgba(117, 6, 5, 0.8);
            backdrop-filter: blur(5px);
            -webkit-backdrop-filter: blur(5px);
            color: #F8B83C;
            padding: 12px 24px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            cursor: pointer;
            font-family: 'Century Gothic', sans-serif;
            width: 100%;
            transition: all 0.3s ease;
        }

        .update-btn:hover {
            background: rgba(143, 8, 6, 0.9);
            box-shadow: 0 0 15px rgba(248, 184, 60, 0.3);
        }

        .message {
            margin-top: 20px;
            padding: 10px;
            border-radius: 8px;
            text-align: center;
            font-family: 'Century Gothic', sans-serif;
            display: none;
            backdrop-filter: blur(5px);
            -webkit-backdrop-filter: blur(5px);
        }

        .message.success {
            background: rgba(76, 175, 80, 0.3);
            border: 1px solid rgba(76, 175, 80, 0.5);
            color: #fff;
            display: block;
        }

        .message.error {
            background: rgba(255, 71, 71, 0.3);
            border: 1px solid rgba(255, 71, 71, 0.5);
            color: #fff;
            display: block;
        }

        /* Update the select element styles */
        select {
            width: 100%;
            padding: 10px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            background: rgba(64, 62, 62, 0.3);
            color: #AEB2B7;
            font-family: 'Century Gothic', sans-serif;
            backdrop-filter: blur(5px);
            -webkit-backdrop-filter: blur(5px);
            appearance: none; /* Removes default browser styling */
            cursor: pointer;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23AEB2B7' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 10px center;
            padding-right: 30px;
        }

        /* Style the dropdown options */
        select option {
            background-color: #2d2d2d;
            color: #AEB2B7;
            padding: 12px;
        }

        /* Style for when dropdown is open */
        select:focus {
            outline: none;
            border-color: rgba(248, 184, 60, 0.5);
            box-shadow: 0 0 10px rgba(248, 184, 60, 0.2);
        }

        /* Hover effect for options */
        select option:hover {
            background-color: #403E3E;
        }

        /* Selected option style */
        select option:checked {
            background-color: #750605;
            color: #F8B83C;
        }

        /* Add these styles */
        .header-container {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
            position: relative;
        }

        .back-btn {
            position: absolute;
            left: 0;
            color: #AEB2B7;
            text-decoration: none;
            font-family: 'Century Gothic', sans-serif;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: color 0.3s ease;
        }

        .back-btn:hover {
            color: #F8B83C;
        }

        /* Update existing h2 style */
        .header-container h2 {
            margin-bottom: 0;
            width: 100%;
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
                            <li class="active"><a href="manageGroups.php"><i class="fas fa-users-cog"></i>Manage Groups</a></li>
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
                <div class="form-container">
                    <div class="header-container">
                        <a href="manageGroups.php" class="back-btn">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>
                        <h2>Edit Group</h2>
                    </div>
                    <form id="editGroupForm" method="POST">
                        <div class="form-group">
                            <label for="groupName">Group Name</label>
                            <input type="text" id="groupName" name="groupName" 
                                   value="<?php echo htmlspecialchars($groupData['group_name'] ?? ''); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="groupLevel">Group Level</label>
                            <select id="groupLevel" name="groupLevel" required>
                                <option value="1" <?php echo ($groupData['group_level'] == 1) ? 'selected' : ''; ?>>1</option>
                                <option value="2" <?php echo ($groupData['group_level'] == 2) ? 'selected' : ''; ?>>2</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="status">Status</label>
                            <select id="status" name="status" required>
                                <option value="active" <?php echo ($groupData['status'] == 1) ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo ($groupData['status'] == 0) ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>

                        <button type="submit" class="update-btn">Update</button>
                    </form>
                    <?php if (isset($updateMessage)): ?>
                        <div id="message" class="message <?php echo strpos($updateMessage, 'success') !== false ? 'success' : 'error'; ?>">
                            <?php echo htmlspecialchars($updateMessage); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
    
                <!-- Scripts -->
        <script>
            document.addEventListener('DOMContentLoaded', function() {
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
            });

            // Add this to your existing script
            document.getElementById('editGroupForm').addEventListener('submit', function(e) {
                const groupName = document.getElementById('groupName').value.trim();
                const groupLevel = document.getElementById('groupLevel').value;
                const status = document.getElementById('status').value;
                
                if (!groupName || !groupLevel || !status) {
                    e.preventDefault();
                    const messageDiv = document.getElementById('message');
                    messageDiv.className = 'message error';
                    messageDiv.textContent = 'Please fill in all fields!';
                    messageDiv.style.display = 'block';
                    return;
                }
            });

            // Prevent non-numeric input in group level field
            document.getElementById('groupLevel').addEventListener('input', function(e) {
                const value = this.value;
                if (value !== '1' && value !== '2') {
                    this.value = '1'; // Default to 1 if invalid input
                }
            });
        </script>
</body>
</html>