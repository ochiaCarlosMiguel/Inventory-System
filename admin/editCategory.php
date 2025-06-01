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
                } else {
                    $profileImage = "../upload/default-profile.jpg";
                }
            }
        }
    } catch (PDOException $e) {
        error_log("Database Error: " . $e->getMessage());
    }
}

// Get category ID and name from URL parameters
$categoryId = isset($_GET['id']) ? $_GET['id'] : '';
$categoryName = isset($_GET['name']) ? $_GET['name'] : '';

// If either parameter is missing, redirect back to categories page
if (empty($categoryId) || empty($categoryName)) {
    header("Location: categories.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newCategoryName = trim($_POST['categoryName']);
    $categoryId = trim($_POST['categoryId']);
    
    if (!empty($newCategoryName) && !empty($categoryId)) {
        try {
            $pdo->beginTransaction();

            // Get the original category name before update
            $stmt = $pdo->prepare("SELECT category_name FROM categories WHERE id = ?");
            $stmt->execute([$categoryId]);
            $originalCategory = $stmt->fetch();
            $originalCategoryName = $originalCategory['category_name'];

            // Update the category
            $updateStmt = $pdo->prepare("UPDATE categories SET category_name = :name WHERE id = :id");
            
            if ($updateStmt->execute([
                ':name' => $newCategoryName,
                ':id' => $categoryId
            ])) {
                // Log the changes
                $changes = [
                    'category_name' => [
                        'old' => $originalCategoryName,
                        'new' => $newCategoryName
                    ]
                ];

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
                    $_SESSION['user_id'],
                    'edit',
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

                $activity_details = "Updated category name from '$originalCategoryName' to '$newCategoryName'";

                $activity_stmt = $pdo->prepare($activity_log_query);
                $activity_stmt->execute([
                    $_SESSION['user_id'],
                    'Category Edit',
                    $activity_details
                ]);

                $pdo->commit();
                echo json_encode(['success' => true]);
                exit;
            }
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Database error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
            exit;
        }
    }
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

        .main-content {
            margin-left: 249px;
            margin-top: 60px;
            padding: 20px;
            min-height: calc(100vh - 60px);
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .password-container {
            background-color: #1E1E1E;
            padding: 30px;
            border-radius: 10px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .password-container h2 {
            color: #F8B83C;
            font-family: 'Montserrat', sans-serif;
            text-align: center;
            margin-bottom: 30px;
        }

        .input-group {
            position: relative;
            margin-bottom: 20px;
        }

        .input-group input {
            width: 100%;
            padding: 12px 40px 12px 15px;
            border: 1px solid #403E3E;
            border-radius: 5px;
            background-color: #2d2d2d;
            color: #AEB2B7;
            font-family: 'Century Gothic', sans-serif;
            font-size: 14px;
        }

        .input-group input:focus {
            outline: none;
            border-color: #750605;
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
        }

        .toggle-password:hover {
            color: #F8B83C;
        }

        .change-btn {
            width: 100%;
            padding: 12px;
            background-color: #750605;
            color: #F8B83C;
            border: none;
            border-radius: 5px;
            font-family: 'Century Gothic', sans-serif;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .change-btn:hover {
            background-color: #8f0807;
        }

        .header-container {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-bottom: 15px;
        }

        .header-container i {
            color: #F8B83C;
            font-size: 24px;
        }

        .divider {
            height: 1px;
            background-color: #403E3E;
            margin-bottom: 30px;
        }

        .update-btn {
            width: 100%;
            padding: 12px;
            background-color: #750605;
            color: #F8B83C;
            border: none;
            border-radius: 5px;
            font-family: 'Century Gothic', sans-serif;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .update-btn:disabled {
            background-color: #4a4848;
            color: #AEB2B7;
            cursor: not-allowed;
        }

        .update-btn:not(:disabled):hover {
            background-color: #8f0807;
        }

        .success-message {
            margin-top: 15px;
            padding: 10px;
            background-color: rgba(76, 175, 80, 0.1);
            border: 1px solid #4CAF50;
            color: #4CAF50;
            border-radius: 5px;
            text-align: center;
            font-family: 'Century Gothic', sans-serif;
        }

        .input-group input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #403E3E;
            border-radius: 5px;
            background-color: #2d2d2d;
            color: #AEB2B7;
            font-family: 'Century Gothic', sans-serif;
            font-size: 14px;
        }

        .back-btn {
            position: fixed;
            top: 80px;  /* Position below the topbar */
            left: 269px; /* Position to the right of the sidebar */
            display: flex;
            align-items: center;
            gap: 5px;
            color: #AEB2B7;
            text-decoration: none;
            font-family: 'Century Gothic', sans-serif;
            transition: color 0.3s ease;
            z-index: 1;
        }

        .back-btn:hover {
            color: #F8B83C;
        }

        /* Add these new styles for error message */
        .error-message {
            margin-top: 15px;
            padding: 10px;
            background-color: rgba(255, 71, 71, 0.1);
            border: 1px solid #ff4747;
            color: #ff4747;
            border-radius: 5px;
            text-align: center;
            font-family: 'Century Gothic', sans-serif;
            display: none;
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
    
            <!-- Back Button -->
            <a href="categories.php" class="back-btn">
                <i class="fas fa-arrow-left"></i>
                Back
            </a>
    
            <!-- Main Content -->
            <div class="main-content">
                <div class="password-container">
                    <div class="header-container">
                        <i class="fas fa-edit"></i>
                        <h2>EDITING <?php echo htmlspecialchars(strtoupper($categoryName)); ?></h2>
                    </div>
                    <div class="divider"></div>
                    <div class="input-group">
                        <input type="text" id="categoryName" value="<?php echo htmlspecialchars($categoryName); ?>">
                    </div>
                    <button class="update-btn" disabled>Update Category</button>
                    <div class="success-message" style="display: none;">
                        Category updated successfully!
                    </div>
                    <div class="error-message" style="display: none;">
                        <!-- Error messages will be inserted here -->
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

                // Category update functionality
                const categoryInput = document.getElementById('categoryName');
                const updateBtn = document.querySelector('.update-btn');
                const successMessage = document.querySelector('.success-message');
                const errorMessage = document.querySelector('.error-message');
                let originalValue = categoryInput.value;

                categoryInput.addEventListener('input', function() {
                    updateBtn.disabled = this.value.trim() === originalValue;
                });

                updateBtn.addEventListener('click', function() {
                    const newCategoryName = categoryInput.value.trim();
                    const urlParams = new URLSearchParams(window.location.search);
                    const categoryId = urlParams.get('id');
                    
                    if (!newCategoryName || !categoryId) {
                        errorMessage.textContent = 'Category name and ID are required';
                        errorMessage.style.display = 'block';
                        return;
                    }

                    // Debug: Log values being sent (remove in production)
                    console.log('Sending update:', {
                        categoryId: categoryId,
                        newCategoryName: newCategoryName
                    });

                    const formData = new FormData();
                    formData.append('categoryName', newCategoryName);
                    formData.append('categoryId', categoryId);

                    fetch(window.location.href, {  // Use current URL to ensure we hit the same script
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        console.log('Server response:', data); // Debug response

                        if (data.success) {
                            successMessage.style.display = 'block';
                            errorMessage.style.display = 'none';
                            
                            // Update the original value
                            originalValue = newCategoryName;
                            updateBtn.disabled = true;

                            // Redirect after 2 seconds
                            setTimeout(() => {
                                window.location.href = 'categories.php';
                            }, 2000);
                        } else {
                            errorMessage.textContent = data.message || 'Error updating category';
                            errorMessage.style.display = 'block';
                            successMessage.style.display = 'none';
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        errorMessage.textContent = 'An error occurred while updating the category';
                        errorMessage.style.display = 'block';
                        successMessage.style.display = 'none';
                    });
                });
            });
        </script>
</body>
</html>
