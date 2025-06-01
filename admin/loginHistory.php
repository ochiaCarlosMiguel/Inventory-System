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

// For debugging (remove in production)
// error_log("User ID: " . $_SESSION['user_id']);
// error_log("User Name: " . $userName);
// error_log("Profile Image: " . $profileImage);

// Add this before the HTML
$loginHistory = [];
try {
    $stmt = $pdo->query("
        SELECT 
            lh.name,
            lh.username,
            lh.user_role,
            lh.action,
            lh.timestamp,
            CASE 
                WHEN lh.action = 'logout' THEN (
                    SELECT lh2.timestamp 
                    FROM login_history lh2 
                    WHERE lh2.username = lh.username 
                    AND lh2.action = 'login' 
                    AND lh2.timestamp < lh.timestamp 
                    ORDER BY lh2.timestamp DESC 
                    LIMIT 1
                )
                ELSE NULL 
            END as login_time
        FROM login_history lh
        ORDER BY lh.timestamp DESC
        LIMIT 100
    ");
    $loginHistory = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching login history: " . $e->getMessage());
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
            padding: 80px 20px 20px;
        }

        .content-header {
            margin-bottom: 20px;
        }

        .content-header h2 {
            color: #F8B83C;
            font-family: 'Montserrat', sans-serif;
            font-size: 24px;
            font-weight: 700;
        }

        .content-wrapper {
            background-color: rgba(30, 30, 30, 0.9);
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        /* Table Styles */
        .history-table {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background-color: transparent;
            color: #AEB2B7;
        }

        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #363333;
            font-family: 'Century Gothic', sans-serif;
        }

        th {
            background-color: #750605;
            color: #F8B83C;
            font-weight: 600;
        }

        tr:hover {
            background-color: rgba(54, 51, 51, 0.5);
        }

        /* Status Badges */
        .login-status {
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }

        .login {
            background-color: rgba(76, 175, 80, 0.2);
            color: #4CAF50;
        }

        .logout {
            background-color: rgba(244, 67, 54, 0.2);
            color: #f44336;
        }

        .logout-row {
            background-color: rgba(30, 30, 30, 0.5);
        }
        
        .logout-row td {
            border-top: none;
            padding-top: 0;
        }
        
        .text-right {
            text-align: right;
            color: #888;
            font-style: italic;
        }

        .filters-container {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
            align-items: center;
        }

        .search-container {
            flex: 1;
            position: relative;
        }

        .search-container input {
            width: 100%;
            padding: 10px 35px 10px 15px;
            border: none;
            border-radius: 5px;
            background-color: #363333;
            color: #AEB2B7;
            font-family: 'Century Gothic', sans-serif;
        }

        .search-container input:focus {
            outline: 1px solid #F8B83C;
        }

        .search-icon {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #AEB2B7;
        }

        .date-filter {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .date-filter input[type="date"] {
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            background-color: #363333;
            color: #AEB2B7;
            font-family: 'Century Gothic', sans-serif;
            cursor: pointer;
        }

        .date-filter input[type="date"]:focus {
            outline: 1px solid #F8B83C;
        }

        .date-separator {
            color: #AEB2B7;
            font-family: 'Century Gothic', sans-serif;
        }

        /* Style the date picker calendar icon */
        .date-filter input[type="date"]::-webkit-calendar-picker-indicator {
            filter: invert(0.7);
            cursor: pointer;
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
                            <li><a href="editHistory.php"><i class="fas fa-pen"></i>Edit History</a></li>
                            <li class="active"><a href="loginHistory.php"><i class="fas fa-sign-in-alt"></i>Login History</a></li>
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
    
            <!-- Main Content -->
            <div class="main-content">
                <div class="content-header">
                    <h2>Login History</h2>
                </div>
                <div class="content-wrapper">
                    <div class="filters-container">
                        <div class="search-container">
                            <input type="text" id="searchInput" placeholder="Search by name, username, or role...">
                            <i class="fas fa-search search-icon"></i>
                        </div>
                        <div class="date-filter">
                            <input type="date" id="dateFrom" placeholder="From">
                            <span class="date-separator">to</span>
                            <input type="date" id="dateTo" placeholder="To">
                        </div>
                    </div>
                    <div class="history-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Username</th>
                                    <th>User Role</th>
                                    <th>Action</th>
                                    <th>Date & Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($loginHistory as $entry): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($entry['name']); ?></td>
                                        <td><?php echo htmlspecialchars($entry['username']); ?></td>
                                        <td><?php echo htmlspecialchars($entry['user_role']); ?></td>
                                        <td>
                                            <span class="login-status <?php echo $entry['action']; ?>">
                                                <?php echo ucfirst($entry['action']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($entry['timestamp']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($loginHistory)): ?>
                                    <tr>
                                        <td colspan="5" style="text-align: center;">No login history available</td>
                                    </tr>
                                <?php endif; ?>
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

                const searchInput = document.getElementById('searchInput');
                const dateFrom = document.getElementById('dateFrom');
                const dateTo = document.getElementById('dateTo');
                const tableRows = document.querySelectorAll('tbody tr');

                function filterTable() {
                    const searchTerm = searchInput.value.toLowerCase();
                    const fromDate = dateFrom.value ? new Date(dateFrom.value) : null;
                    const toDate = dateTo.value ? new Date(dateTo.value) : null;
                    
                    // If toDate is set, set it to end of day
                    if (toDate) {
                        toDate.setHours(23, 59, 59, 999);
                    }
                    
                    tableRows.forEach(row => {
                        const name = row.cells[0].textContent.toLowerCase();
                        const username = row.cells[1].textContent.toLowerCase();
                        const role = row.cells[2].textContent.toLowerCase();
                        const timestamp = new Date(row.cells[4].textContent);
                        
                        // Date range filtering
                        let withinDateRange = true;
                        if (fromDate && toDate) {
                            withinDateRange = timestamp >= fromDate && timestamp <= toDate;
                        } else if (fromDate) {
                            withinDateRange = timestamp >= fromDate;
                        } else if (toDate) {
                            withinDateRange = timestamp <= toDate;
                        }
                        
                        // Search term filtering
                        const matchesSearch = name.includes(searchTerm) || 
                                            username.includes(searchTerm) || 
                                            role.includes(searchTerm);
                        
                        // Show/hide row based on both filters
                        row.style.display = (matchesSearch && withinDateRange) ? '' : 'none';
                    });
                }

                // Set default date range (optional)
                const today = new Date();
                const thirtyDaysAgo = new Date();
                thirtyDaysAgo.setDate(today.getDate() - 30);

                dateTo.value = today.toISOString().split('T')[0];
                dateFrom.value = thirtyDaysAgo.toISOString().split('T')[0];

                // Add event listeners
                searchInput.addEventListener('input', filterTable);
                dateFrom.addEventListener('change', filterTable);
                dateTo.addEventListener('change', filterTable);

                // Initial filter
                filterTable();
            });
        </script>
</body>
</html>