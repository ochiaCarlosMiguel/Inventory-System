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

// Initialize message variables
$message = '';
$messageType = '';

// Add this PHP code block near the top of the file, after the database connection
$groupsQuery = $pdo->query("SELECT group_name, group_level FROM user_groups ORDER BY group_level");
$groups = $groupsQuery->fetchAll(PDO::FETCH_ASSOC);

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Get form data and sanitize inputs
        $name = trim($_POST['name']);
        $username = trim($_POST['username']);
        $password = $_POST['password']; // Will be hashed
        $selectedGroup = $_POST['userRole'];
        $status = $_POST['userStatus'];
        
        // Determine role based on group_level
        $roleQuery = $pdo->prepare("SELECT group_level FROM user_groups WHERE group_name = ?");
        $roleQuery->execute([$selectedGroup]);
        $groupLevel = $roleQuery->fetchColumn();
        
        // Set role based on group_level
        $role = ($groupLevel == 1) ? 'admin' : 'staff';
        
        // Handle image upload
        $profileImage = 'ArtMore.jpg'; // Changed default image
        if(isset($_FILES['profileImage']) && $_FILES['profileImage']['error'] === 0) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            $maxSize = 5 * 1024 * 1024; // 5MB
            
            if(!in_array($_FILES['profileImage']['type'], $allowedTypes)) {
                throw new Exception('Invalid file type. Only JPG, PNG and GIF are allowed.');
            }
            
            if($_FILES['profileImage']['size'] > $maxSize) {
                throw new Exception('File size too large. Maximum size is 5MB.');
            }
            
            // Generate unique filename
            $extension = pathinfo($_FILES['profileImage']['name'], PATHINFO_EXTENSION);
            $profileImage = uniqid() . '.' . $extension;
            
            // Upload directory
            $uploadDir = '../upload/profiles/';
            if(!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            // Move uploaded file
            if(!move_uploaded_file($_FILES['profileImage']['tmp_name'], $uploadDir . $profileImage)) {
                throw new Exception('Failed to upload image.');
            }
        }
        
        // Hash the password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Current timestamp for created_at
        $currentTime = date('Y-m-d H:i:s');
        
        // Check if username already exists
        $checkStmt = $pdo->prepare("SELECT username FROM users WHERE username = ?");
        $checkStmt->execute([$username]);
        
        if ($checkStmt->rowCount() > 0) {
            $message = "Username already exists!";
            $messageType = "error";
        } else {
            // Prepare INSERT statement
            $sql = "INSERT INTO users (name, username, password, role, status, profile_image, created_at, updated_at) 
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $name,
                $username,
                $hashedPassword,
                $role,
                $status,
                $profileImage,
                $currentTime,
                $currentTime
            ]);
            
            $message = "User added successfully!";
            $messageType = "success";
        }
    } catch(PDOException $e) {
        $message = "Error: " . $e->getMessage();
        $messageType = "error";
    } catch(Exception $e) {
        $message = "Error: " . $e->getMessage();
        $messageType = "error";
    }
    
    // Return JSON response for AJAX
    header('Content-Type: application/json');
    echo json_encode([
        'message' => $message,
        'type' => $messageType
    ]);
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

        .main-content {
            margin-left: 249px;
            padding: 80px 20px 20px;
        }

        .content-wrapper {
            background: rgba(30, 30, 30, 0.8);
            backdrop-filter: blur(10px);
            border-radius: 10px;
            padding: 30px;
            max-width: 800px;
            margin: 0 auto;
        }

        .content-header {
            margin-bottom: 30px;
        }

        .content-header h2 {
            color: #F8B83C;
            font-family: 'Montserrat', sans-serif;
            display: flex;
            align-items: center;
            gap: 10px;
            padding-bottom: 15px;
        }

        .header-line {
            height: 2px;
            background: #750605;
        }

        .form-container {
            max-width: 500px;
            margin: 30px auto 0;
        }

        .form-group {
            position: relative;
            margin-bottom: 20px;
        }

        .form-group i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #AEB2B7;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px 40px;
            padding-right: 45px;
            background: rgba(64, 62, 62, 0.9);
            border: 1px solid #555;
            border-radius: 5px;
            color: #AEB2B7;
            font-family: 'Century Gothic', sans-serif;
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
            padding: 5px;
            z-index: 1;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .add-user-btn {
            width: 100%;
            padding: 12px;
            background: #750605;
            color: #F8B83C;
            border: none;
            border-radius: 5px;
            font-family: 'Century Gothic', sans-serif;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: background-color 0.3s;
        }

        .add-user-btn:hover {
            background: #8f0807;
        }

        .message {
            padding: 12px;
            border-radius: 5px;
            margin-top: 20px;
            display: none;
            align-items: center;
            gap: 10px;
        }

        .message.error {
            background: rgba(255, 0, 0, 0.2);       
            color: #ff4747;
        }

        .message.success {
            background: rgba(0, 255, 0, 0.2);
            color: #4CAF50;
        }

        .form-group select {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            padding-right: 45px;
        }

        .form-group.select-group {
            position: relative;
        }

        .form-group.select-group .select-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #AEB2B7;
            pointer-events: none;
            z-index: 1;
        }

        .header-top {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            position: relative;
        }

        .back-btn {
            display: flex;
            align-items: center;
            gap: 5px;
            color: #AEB2B7;
            text-decoration: none;
            font-family: 'Century Gothic', sans-serif;
            padding: 8px 15px;
            background: rgba(64, 62, 62, 0.9);
            border-radius: 5px;
            transition: all 0.3s ease;
            position: absolute;
            left: 0;
        }

        .back-btn:hover {
            background: #750605;
            color: #F8B83C;
        }

        .content-header h2 {
            color: #F8B83C;
            font-family: 'Montserrat', sans-serif;
            display: flex;
            align-items: center;
            gap: 10px;
            padding-bottom: 15px;
            margin: 0 auto;
        }

        .image-upload-group {
            text-align: center;
            margin-bottom: 30px;
        }

        .image-preview {
            width: 150px;
            height: 150px;
            margin: 0 auto 15px;
            border-radius: 50%;
            overflow: hidden;
            border: 3px solid #750605;
        }

        .image-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .hidden-input {
            display: none;
        }

        .upload-label {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background: #403E3E;
            color: #AEB2B7;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .upload-label:hover {
            background: #750605;
            color: #F8B83C;
        }

        .image-upload-buttons {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 15px;
        }

        .upload-label {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background: #403E3E;
            color: #AEB2B7;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .upload-label:hover {
            background: #750605;
            color: #F8B83C;
        }

        .camera-label {
            background: #750605;
            color: #F8B83C;
        }

        .camera-label:hover {
            background: #8f0807;
        }

        #videoFeed {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.9);
            z-index: 1000;
            display: none;
            justify-content: center;
            align-items: center;
            flex-direction: column;
        }

        #videoFeed video {
            max-width: 100%;
            max-height: 80vh;
            margin-bottom: 20px;
        }

        .camera-controls {
            display: flex;
            gap: 15px;
            margin-top: 15px;
        }

        .camera-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-family: 'Century Gothic', sans-serif;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .capture-btn {
            background: #750605;
            color: #F8B83C;
        }

        .cancel-btn {
            background: #403E3E;
            color: #AEB2B7;
        }

        .camera-btn:hover {
            opacity: 0.9;
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
                    <img src="../upload/redemption.jpg" alt="Profile Picture">
                    <span class="profile-name">Carlos Miguel B. Ochia</span>
                    <i class="fas fa-caret-down"></i>
                    <div class="dropdown-content">
                        <a href="#" id="profileBtn"><i class="fas fa-user"></i>Profile</a>
                        <a href="#" id="settingsBtn"><i class="fas fa-cog"></i>Settings</a>
                        <a href="#" id="logoutBtn"><i class="fas fa-sign-out-alt"></i>Logout</a>
                    </div>
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

                // Add New User Form Handling
                const form = document.getElementById('addUserForm');
                const errorMessage = document.getElementById('errorMessage');
                const successMessage = document.getElementById('successMessage');
                const togglePassword = document.querySelector('.toggle-password');
                const passwordInput = document.getElementById('password');

                // Toggle password visibility
                togglePassword.addEventListener('click', function() {
                    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordInput.setAttribute('type', type);
                    this.querySelector('i').classList.toggle('fa-eye');
                    this.querySelector('i').classList.toggle('fa-eye-slash');
                });

                // Form submission
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    // Hide any existing messages
                    errorMessage.style.display = 'none';
                    successMessage.style.display = 'none';

                    // Create FormData object
                    const formData = new FormData(this);

                    // Send AJAX request
                    fetch('addNewUser.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        const messageElement = data.type === 'success' ? successMessage : errorMessage;
                        messageElement.textContent = data.message;
                        messageElement.style.display = 'flex';

                        if (data.type === 'success') {
                            form.reset();
                            // Hide success message after 3 seconds
                            setTimeout(() => {
                                messageElement.style.display = 'none';
                            }, 3000);
                        }
                    })
                    .catch(error => {
                        errorMessage.textContent = 'An error occurred. Please try again.';
                        errorMessage.style.display = 'flex';
                    });
                });

                // Update time function with seconds
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

                // Update immediately and then every second
                updateDateTime();
                setInterval(updateDateTime, 1000);

                const profileImage = document.getElementById('profileImage');
                const cameraButton = document.getElementById('cameraButton');
                const imagePreview = document.getElementById('imagePreview');
                const videoFeed = document.getElementById('videoFeed');
                const video = document.getElementById('video');
                const canvas = document.getElementById('canvas');
                const captureBtn = document.getElementById('captureBtn');
                const cancelBtn = document.getElementById('cancelBtn');
                const capturedImage = document.getElementById('capturedImage');
                
                let stream = null;

                // Handle file selection
                profileImage.addEventListener('change', function(e) {
                    const file = e.target.files[0];
                    if(file) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            imagePreview.src = e.target.result;
                        }
                        reader.readAsDataURL(file);
                    }
                });

                // Camera functionality
                async function startCamera() {
                    try {
                        stream = await navigator.mediaDevices.getUserMedia({ 
                            video: { 
                                facingMode: 'user',
                                width: { ideal: 1280 },
                                height: { ideal: 720 }
                            }, 
                            audio: false 
                        });
                        video.srcObject = stream;
                        videoFeed.style.display = 'flex';
                    } catch (err) {
                        console.error('Error accessing camera:', err);
                        alert('Unable to access camera. Please make sure you have granted camera permissions.');
                    }
                }

                function stopCamera() {
                    if (stream) {
                        stream.getTracks().forEach(track => track.stop());
                        video.srcObject = null;
                        videoFeed.style.display = 'none';
                    }
                }

                // Camera button click handler
                cameraButton.addEventListener('click', startCamera);

                // Capture button click handler
                captureBtn.addEventListener('click', () => {
                    canvas.width = video.videoWidth;
                    canvas.height = video.videoHeight;
                    canvas.getContext('2d').drawImage(video, 0, 0);
                    
                    // Convert canvas to blob
                    canvas.toBlob((blob) => {
                        // Create a File object from the blob
                        const file = new File([blob], "captured-image.jpg", { type: "image/jpeg" });
                        
                        // Create a FileList-like object
                        const dataTransfer = new DataTransfer();
                        dataTransfer.items.add(file);
                        
                        // Assign the FileList to profileImage input
                        profileImage.files = dataTransfer.files;
                        
                        // Update preview
                        imagePreview.src = canvas.toDataURL('image/jpeg');
                        
                        // Stop camera and hide video feed
                        stopCamera();
                    }, 'image/jpeg', 0.8);
                });

                // Cancel button click handler
                cancelBtn.addEventListener('click', stopCamera);

                // Check if device has camera capability
                if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                    cameraButton.style.display = 'none';
                }
            });
        </script>

    <!-- Add New User Form Section -->
    <div class="main-content">

                <div class="content-wrapper">
            <!-- Header -->
            <div class="content-header">
                <div class="header-top">
                    <a href="manageUsers.php" class="back-btn">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                    <h2><i class="fas fa-user-plus"></i> ADD NEW USER</h2>
                </div>
                <div class="header-line"></div>
            </div>

            <!-- Form Container -->
            <div class="form-container">
                <form id="addUserForm" method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <i class="fas fa-user"></i>
                        <input type="text" id="name" name="name" placeholder="Name" required>
                    </div>

                    <div class="form-group">
                        <i class="fas fa-user-circle"></i>
                        <input type="text" id="username" name="username" placeholder="Username" required>
                    </div>

                    <div class="form-group">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="password" name="password" placeholder="Password" required>
                        <button type="button" class="toggle-password">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>

                    <div class="form-group">
                        <i class="fas fa-user-tag"></i>
                        <select id="userRole" name="userRole" required>
                            <option value="">Select User Group</option>
                            <?php foreach ($groups as $group): ?>
                                <option value="<?php echo htmlspecialchars($group['group_name']); ?>">
                                    <?php echo htmlspecialchars($group['group_name']); ?> 
                                    (<?php echo $group['group_level'] == 1 ? 'Admin Access' : 'Staff Access'; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <i class="fas fa-toggle-on"></i>
                        <select id="userStatus" name="userStatus" required>
                            <option value="">Select Status</option>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>

                    <div class="form-group image-upload-group">
                        <div class="image-preview">
                            <img id="imagePreview" src="../upload/ArtMore.jpg" alt="Profile Preview">
                        </div>
                        <div class="image-upload-buttons">
                            <input type="file" id="profileImage" name="profileImage" accept="image/*" class="hidden-input">
                            <label for="profileImage" class="upload-label">
                                <i class="fas fa-image"></i>
                                Choose Image
                            </label>
                            
                            <button type="button" id="cameraButton" class="upload-label camera-label">
                                <i class="fas fa-camera"></i>
                                Take Photo
                            </button>
                            
                            <canvas id="canvas" style="display:none;"></canvas>
                            <input type="hidden" id="capturedImage" name="profileImage">
                        </div>
                    </div>

                    <button type="submit" class="add-user-btn">
                        <i class="fas fa-plus-circle"></i> Add User
                    </button>
                </form>

                <!-- Messages -->
                <div class="message error" id="errorMessage">
                    <i class="fas fa-exclamation-circle"></i>
                    Please fill in all required fields
                </div>
                <div class="message success" id="successMessage">
                    <i class="fas fa-check-circle"></i>
                    User added successfully!
                </div>
            </div>
        </div>
    </div>
    <div id="videoFeed">
        <video id="video" autoplay playsinline></video>
        <div class="camera-controls">
            <button type="button" class="camera-btn capture-btn" id="captureBtn">
                <i class="fas fa-camera"></i> Capture
            </button>
            <button type="button" class="camera-btn cancel-btn" id="cancelBtn">
                <i class="fas fa-times"></i> Cancel
            </button>
        </div>
    </div>
</body>
</html>