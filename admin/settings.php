<?php
// Include the database connection
require_once '../connection/connections.php';

// Start the session if not already started
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    // User is not logged in, redirect to index.php
    header("Location: index.php");
    exit(); // Ensure no further code is executed
}

// Fetch user data (assuming you have a user_id in session)
$userId = $_SESSION['user_id']; // Make sure you have user_id in session
try {
    $stmt = $pdo->prepare("SELECT name, username, profile_image FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $userData = $stmt->fetch();

    // Set default profile image if none exists
    if (empty($userData['profile_image'])) {
        $userData['profile_image'] = 'default.jpg';
    }

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['update_account'])) {
            $newName = $_POST['name'];
            $newUsername = $_POST['username'];
            
            try {
                // Start transaction
                $pdo->beginTransaction();
                
                // Get old values before update
                $oldDataStmt = $pdo->prepare("SELECT name, username FROM users WHERE id = ?");
                $oldDataStmt->execute([$userId]);
                $oldData = $oldDataStmt->fetch();
                
                // Update the database
                $updateStmt = $pdo->prepare("UPDATE users SET name = ?, username = ? WHERE id = ?");
                if ($updateStmt->execute([$newName, $newUsername, $userId])) {
                    // Prepare changes array
                    $changes = [];
                    
                    if ($oldData['name'] !== $newName) {
                        $changes['name'] = [
                            'old' => $oldData['name'],
                            'new' => $newName
                        ];
                    }
                    
                    if ($oldData['username'] !== $newUsername) {
                        $changes['username'] = [
                            'old' => $oldData['username'],
                            'new' => $newUsername
                        ];
                    }
                    
                    // If there are changes, record them in edit_history
                    if (!empty($changes)) {
                        $historyStmt = $pdo->prepare("
                            INSERT INTO edit_history (
                                item_type,
                                item_id,
                                user_id,
                                changes,
                                timestamp
                            ) VALUES (
                                'profile',
                                ?,
                                ?,
                                ?,
                                NOW()
                            )
                        ");
                        
                        $historyStmt->execute([
                            $userId,
                            $userId,
                            json_encode($changes)
                        ]);
                    }
                    
                    // Commit transaction
                    $pdo->commit();
                    
                    $successMessage = "Account updated successfully!";
                    // Update the userData array with new values
                    $userData['name'] = $newName;
                    $userData['username'] = $newUsername;
                } else {
                    $pdo->rollBack();
                    $errorMessage = "Error updating account.";
                }
            } catch (PDOException $e) {
                $pdo->rollBack();
                error_log("Database error: " . $e->getMessage());
                $errorMessage = "Error updating account.";
            }
        } elseif (isset($_POST['update_photo'])) {
            // Handle photo upload
            if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
                try {
                    // Start transaction
                    $pdo->beginTransaction();
                    
                    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                    $fileType = $_FILES['profile_image']['type'];
                    
                    if (in_array($fileType, $allowedTypes)) {
                        // Get old image filename
                        $oldImageStmt = $pdo->prepare("SELECT profile_image FROM users WHERE id = ?");
                        $oldImageStmt->execute([$userId]);
                        $oldImage = $oldImageStmt->fetchColumn();
                        
                        $fileName = time() . '_' . $_FILES['profile_image']['name'];
                        $uploadPath = "../upload/profiles/" . $fileName;
                        
                        if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $uploadPath)) {
                            // Update database with new image filename
                            $updatePhotoStmt = $pdo->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
                            if ($updatePhotoStmt->execute([$fileName, $userId])) {
                                // Record in edit_history
                                $changes = [
                                    'profile_image' => [
                                        'old' => $oldImage,
                                        'new' => $fileName
                                    ]
                                ];
                                
                                $historyStmt = $pdo->prepare("
                                    INSERT INTO edit_history (
                                        item_type,
                                        item_id,
                                        user_id,
                                        changes,
                                        timestamp
                                    ) VALUES (
                                        'profile',
                                        ?,
                                        ?,
                                        ?,
                                        NOW()
                                    )
                                ");
                                
                                $historyStmt->execute([
                                    $userId,
                                    $userId,
                                    json_encode($changes)
                                ]);
                                
                                // Commit transaction
                                $pdo->commit();
                                
                                $successMessage = "Profile photo updated successfully!";
                                $userData['profile_image'] = $fileName;
                            } else {
                                $pdo->rollBack();
                                $errorMessage = "Error updating profile photo in database.";
                            }
                        } else {
                            $pdo->rollBack();
                            $errorMessage = "Error uploading file.";
                        }
                    } else {
                        $errorMessage = "Invalid file type. Please upload a JPEG, PNG, or GIF.";
                    }
                } catch (PDOException $e) {
                    $pdo->rollBack();
                    error_log("Database error: " . $e->getMessage());
                    $errorMessage = "Error updating profile photo.";
                }
            }
        }
    }
} catch(PDOException $e) {
    $errorMessage = "Database error: " . $e->getMessage();
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

        .photo-content {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 15px;
        }

        .photo-content img {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 8px;
        }

        .photo-actions {
            display: flex;
            gap: 10px;
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

        .btn-choose, .btn-change {
            background-color: #750605;
            color: #F8B83C;
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

        .alert {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
            font-family: 'Century Gothic', sans-serif;
        }

        .alert-success {
            background-color: rgba(76, 175, 80, 0.1);
            border: 1px solid rgba(76, 175, 80, 0.2);
            color: #4CAF50;
        }

        .alert-danger {
            background-color: rgba(244, 67, 54, 0.1);
            border: 1px solid rgba(244, 67, 54, 0.2);
            color: #f44336;
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
                <div class="time-label" id="currentDateTime"></div>
                <div class="profile-container">
                    <img src="<?php echo htmlspecialchars('../upload/profiles/' . $userData['profile_image']); ?>" alt="Profile Picture">
                    <span class="profile-name"><?php echo htmlspecialchars($userData['name']); ?></span>
                    <i class="fas fa-caret-down"></i>
                    <div class="dropdown-content">
                        <a href="#" id="profileBtn"><i class="fas fa-user"></i>Profile</a>
                        <a href="#" id="settingsBtn"><i class="fas fa-cog"></i>Settings</a>
                        <a href="#" id="logoutBtn"><i class="fas fa-sign-out-alt"></i>Logout</a>
                    </div>
                </div>
            </div>
    
            <!-- Content Wrapper -->
            <div class="content-wrapper">
                <div class="settings-container">
                    <!-- Photo Change Table -->
                    <div class="settings-table">
                        <div class="table-header">
                            <i class="fas fa-camera"></i>
                            <h2>CHANGE MY PHOTO</h2>
                        </div>
                        <div class="divider"></div>
                        <div class="photo-content">
                            <img src="<?php echo htmlspecialchars('../upload/profiles/' . $userData['profile_image']); ?>" alt="Current Photo" id="currentPhoto">
                            <form method="POST" enctype="multipart/form-data" action="">
                                <div class="photo-actions">
                                    <input type="file" name="profile_image" id="photoInput" accept="image/*" style="display: none;">
                                    <button type="button" class="btn-choose" onclick="document.getElementById('photoInput').click()">
                                        Choose Image
                                    </button>
                                    <button type="submit" name="update_photo" class="btn-change">Change</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Account Edit Table -->
                    <div class="settings-table">
                        <div class="table-header">
                            <i class="fas fa-user-edit"></i>
                            <h2>EDIT MY ACCOUNT</h2>
                        </div>
                        <div class="divider"></div>
                        <?php if (isset($successMessage)): ?>
                            <div class="alert alert-success"><?php echo $successMessage; ?></div>
                        <?php endif; ?>
                        <?php if (isset($errorMessage)): ?>
                            <div class="alert alert-danger"><?php echo $errorMessage; ?></div>
                        <?php endif; ?>
                        <form method="POST" action="">
                            <div class="account-content">
                                <div class="input-group">
                                    <input type="text" name="name" value="<?php echo htmlspecialchars($userData['name']); ?>">
                                    <label>Name</label>
                                </div>
                                <div class="input-group">
                                    <input type="text" name="username" value="<?php echo htmlspecialchars($userData['username']); ?>">
                                    <label>Username</label>
                                </div>
                                <div class="button-group">
                                    <button type="submit" name="update_account" class="btn-update">Update</button>
                                    <button type="button" class="btn-change-password" onclick="window.location.href='changePassword.php'">Change Password</button>
                                </div>
                            </div>
                        </form>
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

                // Change Password Button Handler
                const changePasswordBtn = document.querySelector('.btn-change-password');
                changePasswordBtn.addEventListener('click', function() {
                    window.location.href = 'changePassword.php';
                });

                document.getElementById('photoInput').addEventListener('change', function(e) {
                    if (this.files && this.files[0]) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            document.getElementById('currentPhoto').src = e.target.result;
                        };
                        reader.readAsDataURL(this.files[0]);
                    }
                });
            });
        </script>
</body>
</html>