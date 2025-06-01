<?php
// Start the session
session_start();

// Include the database connection
require_once '../connection/connections.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if not logged in
    header('Location: index.php');
    exit();
}

// Fetch the logged-in user's data with better error handling
$query = "SELECT id, name, profile_image FROM users WHERE id = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Set default values if no user is found or if profile_image is empty/null
if (!$user) {
    error_log("User not found in database: " . $_SESSION['user_id']);
    // Redirect to login page as this shouldn't happen
    header('Location: login.php');
    exit();
}

// If profile_image is null or empty, use a default image
if (empty($user['profile_image'])) {
    $user['profile_image'] = 'default.jpg';
}

// Verify if the image file exists
if (!file_exists("../upload/profiles/" . $user['profile_image'])) {
    error_log("Profile image not found: " . $user['profile_image']);
    $user['profile_image'] = 'default.jpg'; // Fallback to default
}

// Debug information (remove in production)
if ($_SERVER['SERVER_NAME'] === 'localhost' || $_SERVER['SERVER_NAME'] === '127.0.0.1') {
    echo "Debug: Current user:<br>";
    echo "- User ID: " . htmlspecialchars($_SESSION['user_id']) . "<br>";
    echo "- Name: " . htmlspecialchars($user['name']) . "<br>";
    echo "- Profile Image: " . htmlspecialchars($user['profile_image']) . "<br>";
    echo "- Image Path: ../upload/profiles/" . htmlspecialchars($user['profile_image']) . "<br>";
    echo "- Image Exists: " . (file_exists("../upload/profiles/" . $user['profile_image']) ? "Yes" : "No") . "<br>";
}

// Add these new FAQs to your existing array
$faqs = [
    // ... existing FAQs ...
    
    // Product Management Section
    [
        'category' => 'Product Management',
        'question' => 'How do I export product data to PDF?',
        'answer' => 'To export your product data to PDF:
            <ol>
                <li>Navigate to the Products > Manage Products page</li>
                <li>Look for the "Export PDF" button in the top-right corner</li>
                <li>Click the button to generate and download a PDF containing all product information</li>
                <li>The PDF will include details such as product name, category, stock levels, and pricing</li>
            </ol>'
    ],
    
    // Sales Report Section
    [
        'category' => 'Sales Report',
        'question' => 'How do I export sales reports to PDF?',
        'answer' => 'You can export both Monthly and Daily sales reports to PDF:
            <ol>
                <li>Navigate to either Monthly Sales or Daily Sales under the Sales Report menu</li>
                <li>Look for the "Export to PDF" button in the top-right corner of the table</li>
                <li>Click the button to generate and download the sales report</li>
                <li>The PDF will include all sales data, totals, and financial calculations shown in the table</li>
            </ol>
            <p>This feature is useful for:
            <ul>
                <li>Creating permanent records of sales data</li>
                <li>Sharing reports with stakeholders</li>
                <li>Maintaining business documentation</li>
            </ul></p>'
    ]
];
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

        html, body {
            height: 100%;
        }

        /* Body and Background - Fix opacity overlay */
        body {  
            background: url('../upload/artmoreshop.jpg') no-repeat center center fixed;
            -webkit-background-size: cover;
            -moz-background-size: cover;
            -o-background-size: cover;
            background-size: cover;
            min-height: 100vh;
            position: relative;
            margin: 0;
            padding: 0;
        }

        /* Modify the background overlay to stay fixed */
        body::before {
            content: '';
            position: fixed;
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
            overflow: hidden;
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
            flex-grow: 1;
            overflow-y: auto;
            margin-right: 0;
            padding-right: 15px;
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

        /* FAQ Styles */
        .faq-container {
            background-color: #1E1E1E;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin: 0 auto;
            max-width: 1200px;
            width: 100%;
        }

        .faq-container h1 {
            color: #F8B83C;
            font-family: 'Montserrat', sans-serif;
            font-size: 24px;
            margin-bottom: 30px;
            padding-bottom: 10px;
            border-bottom: 2px solid #750605;
        }

        /* FAQ Section Styles */
        .faq-section {
            background-color: #262626;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .faq-section h2 {
            color: #F8B83C;
            font-family: 'Montserrat', sans-serif;
            font-size: 20px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* FAQ Item Styles */
        .faq-item {
            background-color: #2d2d2d;
            border-radius: 8px;
            margin-bottom: 15px;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .faq-question {
            padding: 20px;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #363636;
            transition: background-color 0.3s ease;
        }

        .faq-question:hover {
            background-color: #404040;
        }

        .faq-question h3 {
            color: #F8B83C;
            font-family: 'Century Gothic', sans-serif;
            font-size: 16px;
            margin: 0;
            flex-grow: 1;
        }

        .faq-question i:last-child {
            color: #F8B83C;
            transition: transform 0.3s ease;
        }

        .faq-item.active .faq-question i:last-child {
            transform: rotate(180deg);
        }

        /* FAQ Answer Styles */
        .faq-answer {
            background-color: #2d2d2d;
            padding: 0;
            max-height: 0;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .faq-item.active .faq-answer {
            padding: 20px;
            max-height: 2000px;
            transition: all 0.5s ease;
        }

        .faq-answer p {
            color: #AEB2B7;
            font-family: 'Century Gothic', sans-serif;
            margin-bottom: 15px;
            line-height: 1.6;
        }

        .faq-answer strong {
            color: #F8B83C;
        }

        .faq-answer ul {
            list-style-type: none;
            padding-left: 20px;
            margin-bottom: 15px;
        }

        .faq-answer ul ul {
            margin-top: 8px;
            margin-bottom: 8px;
        }

        .faq-answer li {
            color: #AEB2B7;
            font-family: 'Century Gothic', sans-serif;
            margin-bottom: 8px;
            position: relative;
            padding-left: 20px;
            line-height: 1.5;
        }

        .faq-answer li:before {
            content: '•';
            color: #F8B83C;
            position: absolute;
            left: 0;
        }

        /* Important Notes Section */
        .faq-answer .note {
            background-color: rgba(248, 184, 60, 0.1);
            border-left: 4px solid #F8B83C;
            padding: 15px;
            margin-top: 15px;
            border-radius: 4px;
        }

        /* Responsive Adjustments */
        @media screen and (max-width: 768px) {
            .faq-container {
                padding: 20px;
                margin: 10px;
            }

            .faq-question h3 {
                font-size: 14px;
            }
        }

        /* Main Content Styles - Remove separate scrolling */
        .main-content {
            margin-left: 249px;
            margin-top: -30px;
            padding: 20px;
        }

        /* FAQ Container Styles - Adjust positioning */
        .faq-container {
            background-color: #1E1E1E;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin: 0 auto;
            max-width: 1200px;
            width: 100%;
        }

        /* FAQ Section Styles */
        .faq-section {
            background-color: #262626;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .faq-section:last-child {
            margin-bottom: 0; /* Remove bottom margin from last section */
        }

        /* Adjust container padding on smaller screens */
        @media screen and (max-width: 1400px) {
            .faq-container {
                margin: 0 15px;
                padding: 20px;
            }
        }

        .faq-answer h4 {
            color: #FFFFFF;
            margin-bottom: 10px;
            font-family: 'Century Gothic', sans-serif;
        }

        /* Scrollbar Styles */
        .nav-links {
            list-style: none;
            padding: 0 15px;
            flex-grow: 1;
            overflow-y: auto;
            margin-right: 0; /* Remove the negative margin */
            padding-right: 15px; /* Adjust padding to match left side */
        }

        /* Add custom scrollbar styling */
        .nav-links::-webkit-scrollbar {
            width: 8px;
        }

        .nav-links::-webkit-scrollbar-track {
            background: #1E1E1E;
        }

        .nav-links::-webkit-scrollbar-thumb {
            background: #363636;
            border-radius: 4px;
        }

        .nav-links::-webkit-scrollbar-thumb:hover {
            background: #404040;
        }

        /* Main content scrollbar styling */
        .main-content::-webkit-scrollbar {
            width: 8px;
        }

        .main-content::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.2);
        }

        .main-content::-webkit-scrollbar-thumb {
            background: #363636;
            border-radius: 4px;
        }

        .main-content::-webkit-scrollbar-thumb:hover {
            background: #404040;
        }

        /* Search Container Styles */
        .search-container {
            margin-bottom: 30px;
        }

        .search-box {
            position: relative;
            display: flex;
            align-items: center;
            background: #262626;
            border-radius: 8px;
            padding: 5px 15px;
            transition: all 0.3s ease;
        }

        .search-box:focus-within {
            background: #2d2d2d;
            box-shadow: 0 0 0 2px #F8B83C;
        }

        .search-icon {
            color: #AEB2B7;
            margin-right: 10px;
        }

        #faqSearch {
            flex: 1;
            background: none;
            border: none;
            color: #FFFFFF;
            padding: 12px 0;
            font-size: 16px;
            font-family: 'Century Gothic', sans-serif;
            width: 100%;
        }

        #faqSearch:focus {
            outline: none;
        }

        #faqSearch::placeholder {
            color: #AEB2B7;
            opacity: 0.7;
        }

        .clear-button {
            background: none;
            border: none;
            color: #AEB2B7;
            cursor: pointer;
            padding: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0.7;
            transition: all 0.2s ease;
        }

        .clear-button:hover {
            opacity: 1;
            color: #F8B83C;
        }

        .search-stats {
            margin-top: 10px;
            color: #AEB2B7;
            font-size: 14px;
            font-family: 'Century Gothic', sans-serif;
        }

        /* Highlight styles */
        .highlight {
            background-color: rgba(248, 184, 60, 0.3);
            padding: 2px 0;
            border-radius: 3px;
        }

        /* Animation for FAQ items */
        .faq-item {
            transition: opacity 0.3s ease, transform 0.3s ease;
        }

        .faq-item.hidden {
            opacity: 0;
            transform: translateY(10px);
        }

        .faq-item.visible {
            opacity: 1;
            transform: translateY(0);
        }

        /* Add or update these styles */
        .faq-item {
            transition: all 0.3s ease-in-out;
            opacity: 1;
            transform: translateY(0);
        }

        .faq-item.hidden {
            opacity: 0;
            transform: translateY(10px);
            pointer-events: none;
        }

        .faq-item.visible {
            opacity: 1;
            transform: translateY(0);
            pointer-events: auto;
        }

        .highlight {
            background-color: rgba(248, 184, 60, 0.3);
            border-radius: 3px;
            padding: 2px 4px;
            transition: background-color 0.2s ease;
        }

        .search-stats {
            transition: opacity 0.3s ease;
        }

        .search-box {
            position: relative;
            display: flex;
            align-items: center;
            background: #262626;
            border-radius: 8px;
            padding: 5px 15px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .search-box:focus-within {
            background: #2d2d2d;
            box-shadow: 0 0 0 2px #F8B83C, 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        #faqSearch {
            transition: all 0.3s ease;
        }

        .clear-button {
            transition: all 0.2s ease;
        }

        /* Reset button styles */
        .search-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 10px;
        }

        .reset-button {
            background: #750605;
            border: none;
            color: #F8B83C;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: all 0.2s ease;
            font-family: 'Century Gothic', sans-serif;
        }

        .reset-button:hover {
            background: #8a0806;
        }

        .reset-button i {
            font-size: 14px;
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
                    <li class="active">
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
                    <img src="<?php echo htmlspecialchars('../upload/profiles/' . $user['profile_image']); ?>" alt="Profile Picture">
                    <span class="profile-name"><?php echo htmlspecialchars($user['name']); ?></span>
                    <i class="fas fa-caret-down"></i>
                    <div class="dropdown-content">
                        <a href="profile.php" id="profileBtn"><i class="fas fa-user"></i>Profile</a>
                        <a href="settings.php" id="settingsBtn"><i class="fas fa-cog"></i>Settings</a>
                        <a id="logoutBtn"><i class="fas fa-sign-out-alt"></i>Logout</a>
                    </div>
                </div>
            </div>

            <!-- Main Content Area -->
            <div class="main-content">
                <div class="faq-container">
                    <h1>Frequently Asked Questions</h1>
                    <div class="search-container">
                        <div class="search-box">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" id="faqSearch" placeholder="Search FAQs...">
                            <button id="clearSearch" class="clear-button" style="display: none;">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <div class="search-controls">
                            <div class="search-stats">
                                <span id="searchResults">Showing all questions</span>
                            </div>
                            <button id="resetFAQ" class="reset-button">
                                <i class="fas fa-undo"></i> Reset View
                            </button>
                        </div>
                    </div>
                    
                    <!-- Dashboard Section -->
                    <div class="faq-section">
                        <h2><i class="fas fa-tachometer-alt"></i> Dashboard</h2>
                        
                        <div class="faq-item">
                            <div class="faq-question">
                                <h3>How do I interpret the dashboard statistics and charts?</h3>
                                <i class="fas fa-chevron-down"></i>
                            </div>
                            <div class="faq-answer">
                                <h4>Overview Cards:</h4>
                                <ul>
                                    <li><strong>Users:</strong> Shows the total number of registered system users</li>
                                    <li><strong>Categories:</strong> Displays the total number of product categories</li>
                                    <li><strong>Products:</strong> Indicates the total number of products in inventory</li>
                                    <li><strong>Items Sold:</strong> Shows the total quantity of items sold</li>
                                </ul>

                                <h4>Charts and Tables:</h4>
                                <ul>
                                    <li><strong>Highest Selling Products:</strong> Lists the top 5 products by sales volume</li>
                                    <li><strong>Latest Sales:</strong> Shows the 5 most recent sales transactions</li>
                                    <li><strong>Yearly Sales Comparison:</strong> Compares current year's sales with previous year</li>
                                    <li><strong>Monthly Sales Comparison:</strong> Compares current month's sales with previous month</li>
                                </ul>

                                <p class="note"><i class="fas fa-info-circle"></i> The green alerts above charts indicate positive growth in sales compared to previous periods.</p>
                            </div>
                        </div>
                    </div>

                    <!-- User Management Section -->
                    <div class="faq-section">
                        <h2><i class="fas fa-users"></i> User Management</h2>
                        
                        <div class="faq-item">
                            <div class="faq-question">
                                <h3>How do I manage users and their accounts?</h3>
                                <i class="fas fa-chevron-down"></i>
                            </div>
                            <div class="faq-answer">
                                <h4>Managing User Accounts:</h4>
                                <ul>
                                    <li><strong>View Users:</strong> Access all user accounts in a table format with details</li>
                                    <li><strong>Edit Users:</strong> Modify user information including:
                                        <ul>
                                            <li>Name and username</li>
                                            <li>Role assignment</li>
                                            <li>Account status (active/inactive)</li>
                                            <li>Password reset options</li>
                                        </ul>
                                    </li>
                                    <li><strong>Delete Users:</strong> Remove user accounts when needed (except admin accounts)</li>
                                </ul>

                                <h4>User Roles and Permissions:</h4>
                                <ul>
                                    <li><strong>Admin Level (1):</strong> Full system access and management rights</li>
                                    <li><strong>Staff Level (2):</strong> Limited access based on assigned permissions</li>
                                </ul>

                                <p class="note"><i class="fas fa-info-circle"></i> All user management actions are logged in the system for security tracking.</p>
                            </div>
                        </div>

                        <div class="faq-item">
                            <div class="faq-question">
                                <h3>How do I manage user groups and access levels?</h3>
                                <i class="fas fa-chevron-down"></i>
                            </div>
                            <div class="faq-answer">
                                <h4>Group Management:</h4>
                                <ul>
                                    <li><strong>View Groups:</strong> See all existing user groups and their settings</li>
                                    <li><strong>Edit Groups:</strong> Modify group properties:
                                        <ul>
                                            <li>Group name and description</li>
                                            <li>Access level assignment</li>
                                            <li>Group status settings</li>
                                        </ul>
                                    </li>
                                    <li><strong>Group Status:</strong> Control group activation and deactivation</li>
                                </ul>

                                <h4>Access Level Configuration:</h4>
                                <ul>
                                    <li><strong>Level 1 (Admin):</strong>
                                        <ul>
                                            <li>Full system management</li>
                                            <li>User and group administration</li>
                                            <li>System configuration access</li>
                                        </ul>
                                    </li>
                                    <li><strong>Level 2 (Staff):</strong>
                                        <ul>
                                            <li>Basic inventory management</li>
                                            <li>Sales and product handling</li>
                                            <li>Report viewing access</li>
                                        </ul>
                                    </li>
                                </ul>

                                <p class="note"><i class="fas fa-info-circle"></i> Changes to group settings affect all users assigned to that group immediately.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Categories Management Section -->
                    <div class="faq-section">
                        <h2><i class="fas fa-tags"></i> Categories Management</h2>
                        
                        <div class="faq-item">
                            <div class="faq-question">
                                <h3>How do I add and manage product categories?</h3>
                                <i class="fas fa-chevron-down"></i>
                            </div>
                            <div class="faq-answer">
                                <h4>Adding New Categories:</h4>
                                <ul>
                                    <li><strong>Access:</strong> Navigate to "Categories" in the sidebar menu</li>
                                    <li><strong>Add Category:</strong>
                                        <ul>
                                            <li>Enter the category name in the "ADD NEW CATEGORY" form</li>
                                            <li>Click the "ADD CATEGORY" button</li>
                                            <li>System automatically generates a unique CTGR-ID</li>
                                        </ul>
                                    </li>
                                </ul>

                                <h4>Managing Existing Categories:</h4>
                                <ul>
                                    <li><strong>View Categories:</strong> All categories are listed in the "ALL CATEGORIES" table</li>
                                    <li><strong>Edit Category:</strong>
                                        <ul>
                                            <li>Click the edit (pencil) icon next to the category</li>
                                            <li>Modify the category name</li>
                                            <li>Save your changes</li>
                                        </ul>
                                    </li>
                                    <li><strong>Delete Category:</strong>
                                        <ul>
                                            <li>Click the delete (trash) icon next to the category</li>
                                            <li>Confirm deletion in the popup modal</li>
                                            <li>Associated products will also be deleted</li>
                                        </ul>
                                    </li>
                                </ul>

                                <p class="note"><i class="fas fa-info-circle"></i> Important: Deleting a category will also remove all products associated with that category. This action cannot be undone.</p>
                            </div>
                        </div>

                        <div class="faq-item">
                            <div class="faq-question">
                                <h3>How are categories used in the system?</h3>
                                <i class="fas fa-chevron-down"></i>
                            </div>
                            <div class="faq-answer">
                                <h4>Category Functions:</h4>
                                <ul>
                                    <li><strong>Product Organization:</strong>
                                        <ul>
                                            <li>Categories help organize products into groups</li>
                                            <li>Makes product management more efficient</li>
                                            <li>Enables better inventory tracking</li>
                                        </ul>
                                    </li>
                                    <li><strong>Inventory Management:</strong>
                                        <ul>
                                            <li>Filter products by category</li>
                                            <li>Generate category-based reports</li>
                                            <li>Track product performance by category</li>
                                        </ul>
                                    </li>
                                </ul>

                                <h4>Best Practices:</h4>
                                <ul>
                                    <li>Use clear, descriptive category names</li>
                                    <li>Avoid duplicate categories</li>
                                    <li>Review categories periodically</li>
                                    <li>Archive unused categories instead of deleting if they have historical data</li>
                                </ul>

                                <p class="note"><i class="fas fa-info-circle"></i> Categories are essential for organizing your inventory and generating accurate reports. Choose category names that are clear and meaningful to your business.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Products Management Section -->
                    <div class="faq-section">
                        <h2><i class="fas fa-box-open"></i> Products Management</h2>
                        
                        <div class="faq-item">
                            <div class="faq-question">
                                <h3>How do I manage products in the system?</h3>
                                <i class="fas fa-chevron-down"></i>
                            </div>
                            <div class="faq-answer">
                                <h4>Adding New Products:</h4>
                                <ul>
                                    <li><strong>Access:</strong> Click "Add Products" in the sidebar menu</li>
                                    <li><strong>Required Information:</strong>
                                        <ul>
                                            <li>Product Title</li>
                                            <li>Category (select from dropdown)</li>
                                            <li>Product Image (JPG, JPEG, PNG, or GIF, max 5MB)</li>
                                            <li>Quantity (whole numbers only)</li>
                                            <li>Buying Price</li>
                                            <li>Selling Price</li>
                                        </ul>
                                    </li>
                                    <li><strong>Image Upload:</strong>
                                        <ul>
                                            <li>Click "Choose Image" button</li>
                                            <li>Select an appropriate image file</li>
                                            <li>System will validate file type and size</li>
                                        </ul>
                                    </li>
                                </ul>

                                <h4>Editing Existing Products:</h4>
                                <ul>
                                    <li><strong>Access:</strong> Go to "Manage Products" and click the edit icon</li>
                                    <li><strong>Editable Fields:</strong>
                                        <ul>
                                            <li>All product details can be modified</li>
                                            <li>Image can be updated or kept unchanged</li>
                                            <li>System validates all changes before saving</li>
                                        </ul>
                                    </li>
                                    <li><strong>Numeric Fields:</strong>
                                        <ul>
                                            <li>Use up/down arrows for precise adjustments</li>
                                            <li>Quantity must be whole numbers</li>
                                            <li>Prices accept up to 2 decimal places</li>
                                        </ul>
                                    </li>
                                </ul>

                                <h4>Important Notes:</h4>
                                <ul>
                                    <li><strong>Validation:</strong>
                                        <ul>
                                            <li>All fields are required</li>
                                            <li>Prices must be greater than 0</li>
                                            <li>Quantity must be a positive whole number</li>
                                            <li>Image size limit: 5MB</li>
                                        </ul>
                                    </li>
                                    <li><strong>Success Messages:</strong>
                                        <ul>
                                            <li>System shows confirmation for successful actions</li>
                                            <li>Error messages display if validation fails</li>
                                        </ul>
                                    </li>
                                    <li><strong>Best Practices:</strong>
                                        <ul>
                                            <li>Use clear, descriptive product titles</li>
                                            <li>Select appropriate categories</li>
                                            <li>Ensure image quality is good but within size limits</li>
                                            <li>Double-check prices before saving</li>
                                        </ul>
                                    </li>
                                </ul>

                                <p class="note"><i class="fas fa-info-circle"></i> Changes to products are immediate and affect inventory tracking. Always verify information before saving.</p>
                            </div>
                        </div>

                        <div class="faq-item">
                            <div class="faq-question">
                                <h3>How do I import products using the import button?</h3>
                                <i class="fas fa-chevron-down"></i>
                            </div>
                            <div class="faq-answer">
                                <h4>Using the Import Function:</h4>
                                <ul>
                                    <li><strong>Step 1: Access Import</strong>
                                        <ul>
                                            <li>Go to "Manage Products" page</li>
                                            <li>Look for the "Import Products" button at the top</li>
                                        </ul>
                                    </li>
                                    <li><strong>Step 2: Prepare Your File</strong>
                                        <ul>
                                            <li>Use Excel format (.xlsx)</li>
                                            <li>Follow the required columns:
                                                <ul>
                                                    <li>Product Title</li>
                                                    <li>Category</li>
                                                    <li>Quantity</li>
                                                    <li>Buying Price</li>
                                                    <li>Selling Price</li>
                                                </ul>
                                            </li>
                                            <li>Download the template for correct formatting</li>
                                        </ul>
                                    </li>
                                    <li><strong>Step 3: Import Process</strong>
                                        <ul>
                                            <li>Click "Choose File" and select your Excel file</li>
                                            <li>System will validate the data</li>
                                            <li>Review any warnings or errors</li>
                                            <li>Click "Import" to confirm</li>
                                        </ul>
                                    </li>
                                </ul>

                                <h4>Important Notes:</h4>
                                <ul>
                                    <li><strong>File Requirements:</strong>
                                        <ul>
                                            <li>Maximum file size: 5MB</li>
                                            <li>Must use the provided template format</li>
                                            <li>All required fields must be filled</li>
                                        </ul>
                                    </li>
                                    <li><strong>Data Validation:</strong>
                                        <ul>
                                            <li>Categories must exist in the system</li>
                                            <li>Prices must be positive numbers</li>
                                            <li>Quantities must be whole numbers</li>
                                        </ul>
                                    </li>
                                </ul>

                                <p class="note"><i class="fas fa-info-circle"></i> To avoid errors, download and use the template file provided in the import section. Make sure all data follows the required format before importing.</p>
                            </div>
                        </div>

                        <!-- New FAQ item for export functionality -->
                        <div class="faq-item">
                            <div class="faq-question">
                                <h3>How do I export product data to PDF?</h3>
                                <i class="fas fa-chevron-down"></i>
                            </div>
                            <div class="faq-answer">
                                <p>To export your product data to PDF:</p>
                                <ol>
                                    <li>Navigate to the Products > Manage Products page</li>
                                    <li>Look for the "Export PDF" button in the top-right corner</li>
                                    <li>Click the button to generate and download a PDF containing all product information</li>
                                    <li>The PDF will include details such as product name, category, stock levels, and pricing</li>
                                </ol>
                            </div>
                        </div>
                    </div>

                    <!-- Sales Management Section -->
                    <div class="faq-section">
                        <h2><i class="fas fa-shopping-cart"></i> Sales Management</h2>
                        
                        <div class="faq-item">
                            <div class="faq-question">
                                <h3>How does the sales management system work?</h3>
                                <i class="fas fa-chevron-down"></i>
                            </div>
                            <div class="faq-answer">
                                <h4>System Components:</h4>
                                <ul>
                                    <li><strong>Manage Sales:</strong> View and filter all sales records</li>
                                    <li><strong>Add Sales:</strong> Create new sales transactions</li>
                                    <li><strong>Edit Sales:</strong> Modify existing sales records</li>
                                </ul>

                                <p class="note"><i class="fas fa-info-circle"></i> All changes are tracked and logged for accountability.</p>
                            </div>
                        </div>

                        <div class="faq-item">
                            <div class="faq-question">
                                <h3>What features are available in the sales system?</h3>
                                <i class="fas fa-chevron-down"></i>
                            </div>
                            <div class="faq-answer">
                                <h4>Key Features:</h4>
                                <ul>
                                    <li><strong>View & Filter:</strong>
                                        <ul>
                                            <li>Tabular display of all sales</li>
                                            <li>Date range filtering</li>
                                            <li>Real-time updates</li>
                                        </ul>
                                    </li>
                                    <li><strong>Sales Management:</strong>
                                        <ul>
                                            <li>Add new sales</li>
                                            <li>Edit existing sales</li>
                                            <li>Delete sales records</li>
                                        </ul>
                                    </li>
                                    <li><strong>Automatic Calculations:</strong>
                                        <ul>
                                            <li>Total amount calculation</li>
                                            <li>Stock level updates</li>
                                            <li>Customer ID generation</li>
                                        </ul>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <div class="faq-item">
                            <div class="faq-question">
                                <h3>How do I add and edit sales?</h3>
                                <i class="fas fa-chevron-down"></i>
                            </div>
                            <div class="faq-answer">
                                <h4>Adding New Sales:</h4>
                                <ul>
                                    <li><strong>Step 1:</strong> Click "Add Sale" button</li>
                                    <li><strong>Step 2:</strong> Select products from inventory</li>
                                    <li><strong>Step 3:</strong> Specify quantities</li>
                                    <li><strong>Step 4:</strong> Review totals</li>
                                    <li><strong>Step 5:</strong> Complete transaction</li>
                                </ul>

                                <h4>Editing Sales:</h4>
                                <ul>
                                    <li><strong>Editable Fields:</strong>
                                        <ul>
                                            <li>Price adjustments</li>
                                            <li>Quantity modifications</li>
                                            <li>Date changes</li>
                                        </ul>
                                    </li>
                                    <li><strong>System Actions:</strong>
                                        <ul>
                                            <li>Automatic total recalculation</li>
                                            <li>Stock level adjustments</li>
                                            <li>Activity logging</li>
                                        </ul>
                                    </li>
                                </ul>

                                <p class="note"><i class="fas fa-info-circle"></i> All changes are tracked in the activity logs for accountability.</p>
                            </div>
                        </div>

                        <div class="faq-item">
                            <div class="faq-question">
                                <h3>What happens during a sale transaction?</h3>
                                <i class="fas fa-chevron-down"></i>
                            </div>
                            <div class="faq-answer">
                                <h4>Transaction Process:</h4>
                                <ul>
                                    <li><strong>Stock Check:</strong>
                                        <ul>
                                            <li>Verifies available inventory</li>
                                            <li>Prevents overselling</li>
                                            <li>Shows warnings if stock is low</li>
                                        </ul>
                                    </li>
                                    <li><strong>Data Recording:</strong>
                                        <ul>
                                            <li>Generates unique sale ID</li>
                                            <li>Records customer information</li>
                                            <li>Logs transaction details</li>
                                        </ul>
                                    </li>
                                    <li><strong>System Updates:</strong>
                                        <ul>
                                            <li>Updates inventory levels</li>
                                            <li>Records transaction history</li>
                                            <li>Generates success message</li>
                                        </ul>
                                    </li>
                                </ul>

                                <p class="note"><i class="fas fa-info-circle"></i> The system uses database transactions to ensure data consistency.</p>
                            </div>
                        </div>

                        <div class="faq-item">
                            <div class="faq-question">
                                <h3>How is data security maintained?</h3>
                                <i class="fas fa-chevron-down"></i>
                            </div>
                            <div class="faq-answer">
                                <h4>Security Measures:</h4>
                                <ul>
                                    <li><strong>Authentication:</strong>
                                        <ul>
                                            <li>Session-based user tracking</li>
                                            <li>Access control checks</li>
                                            <li>Secure login requirements</li>
                                        </ul>
                                    </li>
                                    <li><strong>Data Protection:</strong>
                                        <ul>
                                            <li>Input validation</li>
                                            <li>SQL injection prevention</li>
                                            <li>Data sanitization</li>
                                        </ul>
                                    </li>
                                    <li><strong>Activity Tracking:</strong>
                                        <ul>
                                            <li>Detailed activity logs</li>
                                            <li>Change history recording</li>
                                            <li>Error logging</li>
                                        </ul>
                                    </li>
                                </ul>

                                <p class="note"><i class="fas fa-info-circle"></i> All sensitive operations are protected and logged for security.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Sales Report Section -->
                    <div class="faq-section">
                        <h2><i class="fas fa-chart-bar"></i> Sales Reports</h2>
                        
                        <div class="faq-item">
                            <div class="faq-question">
                                <h3>What are the different types of sales reports available?</h3>
                                <i class="fas fa-chevron-down"></i>
                            </div>
                            <div class="faq-answer">
                                <h4>Available Reports:</h4>
                                <ul>
                                    <li><strong>Sales by Date:</strong>
                                        <ul>
                                            <li>Select custom date range for detailed sales analysis</li>
                                            <li>View sales data with VAT calculations</li>
                                            <li>Generate printable reports and downloadable PDFs</li>
                                            <li>Track buying price, selling price, and profit margins</li>
                                        </ul>
                                    </li>
                                    <li><strong>Monthly Sales:</strong>
                                        <ul>
                                            <li>Overview of all sales within the current month</li>
                                            <li>Product-wise breakdown of quantities sold</li>
                                            <li>Total sales amount and profit calculations</li>
                                            <li>First sale date tracking for each product</li>
                                        </ul>
                                    </li>
                                    <li><strong>Daily Sales:</strong>
                                        <ul>
                                            <li>Real-time tracking of today's sales</li>
                                            <li>Product-wise sales breakdown</li>
                                            <li>Quantity and revenue monitoring</li>
                                            <li>Profit tracking for the current day</li>
                                        </ul>
                                    </li>
                                </ul>

                                <h4>Key Features:</h4>
                                <ul>
                                    <li><strong>VAT Calculations:</strong> Automatic computation of:
                                        <ul>
                                            <li>Input VAT (from purchases)</li>
                                            <li>Output VAT (from sales)</li>
                                            <li>VAT Payable</li>
                                        </ul>
                                    </li>
                                    <li><strong>Profit Analysis:</strong>
                                        <ul>
                                            <li>Gross profit calculations</li>
                                            <li>Net sales after VAT</li>
                                            <li>Product-wise profit margins</li>
                                        </ul>
                                    </li>
                                </ul>

                                <p class="note"><i class="fas fa-info-circle"></i> All reports can be accessed from the Sales Report dropdown menu in the sidebar. The data is updated in real-time as new sales are recorded in the system.</p>
                            </div>
                        </div>

                        <!-- New FAQ item for export functionality -->
                        <div class="faq-item">
                            <div class="faq-question">
                                <h3>How do I export sales reports to PDF?</h3>
                                <i class="fas fa-chevron-down"></i>
                            </div>
                            <div class="faq-answer">
                                <p>You can export both Monthly and Daily sales reports to PDF:</p>
                                <ol>
                                    <li>Navigate to either Monthly Sales or Daily Sales under the Sales Report menu</li>
                                    <li>Look for the "Export to PDF" button in the top-right corner of the table</li>
                                    <li>Click the button to generate and download the sales report</li>
                                    <li>The PDF will include all sales data, totals, and financial calculations shown in the table</li>
                                </ol>
                                <p>This feature is useful for:</p>
                                <ul>
                                    <li>Creating permanent records of sales data</li>
                                    <li>Sharing reports with stakeholders</li>
                                    <li>Maintaining business documentation</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- System Logs Section -->
                    <div class="faq-section">
                        <h2><i class="fas fa-history"></i> System Logs</h2>
                        
                        <div class="faq-item">
                            <div class="faq-question">
                                <h3>How do I track changes and user activities in the system?</h3>
                                <i class="fas fa-chevron-down"></i>
                            </div>
                            <div class="faq-answer">
                                <p>The system provides two comprehensive logging features to track all changes and user activities:</p>
                                
                                <ul>
                                    <li><strong>Edit History:</strong>
                                        <ul>
                                            <li>Tracks all modifications made to products and sales records</li>
                                            <li>Shows before and after values for each change</li>
                                            <li>Records the user who made the changes</li>
                                            <li>Timestamps all modifications</li>
                                            <li>Includes tracking of deleted items</li>
                                        </ul>
                                    </li>
                                    
                                    <li><strong>Login History:</strong>
                                        <ul>
                                            <li>Records all user login and logout activities</li>
                                            <li>Shows user details including name and role</li>
                                            <li>Provides timestamps for each session</li>
                                            <li>Helps monitor system access and security</li>
                                        </ul>
                                    </li>
                                </ul>

                                <h4>Key Features:</h4>
                                <ul>
                                    <li><strong>Filtering Options:</strong>
                                        <ul>
                                            <li>Filter by date range</li>
                                            <li>Filter by specific frames (Products, Sales, etc.)</li>
                                            <li>Search for specific users or actions</li>
                                        </ul>
                                    </li>
                                    <li><strong>Visual Indicators:</strong>
                                        <ul>
                                            <li>Color-coded changes (red for old values, green for new values)</li>
                                            <li>Special formatting for deleted items</li>
                                            <li>Clear status indicators for login/logout events</li>
                                        </ul>
                                    </li>
                                </ul>

                                <p class="note"><i class="fas fa-info-circle"></i> System logs are automatically maintained and cannot be modified by users to ensure data integrity and accountability.</p>
                            </div>
                        </div>
                    </div>

                    <!-- User Profile Section -->
                    <div class="faq-section">
                        <h2><i class="fas fa-user-circle"></i> Profile & Settings</h2>
                        
                        <div class="faq-item">
                            <div class="faq-question">
                                <h3>How do I manage my profile and account settings?</h3>
                                <i class="fas fa-chevron-down"></i>
                            </div>
                            <div class="faq-answer">
                                <p>The system provides two main sections for managing your personal account:</p>
                                
                                <ul>
                                    <li><strong>Profile Page:</strong>
                                        <ul>
                                            <li>View your current profile information</li>
                                            <li>See your profile picture in full size</li>
                                            <li>Quick access to edit profile settings</li>
                                            <li>Display name and account details</li>
                                        </ul>
                                    </li>
                                    
                                    <li><strong>Settings Page:</strong>
                                        <ul>
                                            <li><strong>Photo Management:</strong>
                                                <ul>
                                                    <li>Upload a new profile picture</li>
                                                    <li>Supports JPEG, PNG, and GIF formats</li>
                                                    <li>Automatic image resizing and optimization</li>
                                                </ul>
                                            </li>
                                            <li><strong>Account Settings:</strong>
                                                <ul>
                                                    <li>Update your display name</li>
                                                    <li>Change your username</li>
                                                    <li>Modify your password</li>
                                                </ul>
                                            </li>
                                        </ul>
                                    </li>
                                </ul>

                                <h4>Quick Access:</h4>
                                <ul>
                                    <li>Click your profile picture in the top-right corner to access:
                                        <ul>
                                            <li>Profile page</li>
                                            <li>Settings page</li>
                                            <li>Logout option</li>
                                        </ul>
                                    </li>
                                </ul>

                                <p class="note"><i class="fas fa-info-circle"></i> All profile changes are logged in the system for security purposes and can be viewed in the Edit History section.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

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
                            // Send request to logout script
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

                    // Enhanced FAQ search functionality with smooth filtering
                    const faqSearch = document.getElementById('faqSearch');
                    const clearButton = document.getElementById('clearSearch');
                    const searchResults = document.getElementById('searchResults');
                    const faqItems = document.querySelectorAll('.faq-item');
                    const faqSections = document.querySelectorAll('.faq-section');
                    let searchTimeout;

                    function updateSearch() {
                        const query = faqSearch.value.toLowerCase().trim();
                        let visibleCount = 0;
                        
                        // Show/hide clear button with smooth transition
                        clearButton.style.display = query ? 'flex' : 'none';

                        // Clear previous timeout to prevent multiple rapid searches
                        clearTimeout(searchTimeout);
                        
                        // Add transition class to all items
                        faqItems.forEach(item => {
                            item.style.transition = 'all 0.3s ease-in-out';
                        });

                        searchTimeout = setTimeout(() => {
                            // First pass: Check and mark items
                            faqItems.forEach(item => {
                                const question = item.querySelector('.faq-question h3');
                                const answer = item.querySelector('.faq-answer');
                                const questionText = question.textContent.toLowerCase();
                                const answerText = answer.textContent.toLowerCase();

                                // Reset previous highlights
                                resetHighlights(question);
                                resetHighlights(answer);

                                if (!query) {
                                    // Show all items if no query
                                    requestAnimationFrame(() => {
                                        item.classList.remove('hidden');
                                        item.classList.add('visible');
                                        item.classList.remove('active'); // Close all items
                                    });
                                    visibleCount++;
                                } else if (questionText.includes(query) || answerText.includes(query)) {
                                    // Show and highlight matching items
                                    requestAnimationFrame(() => {
                                        item.classList.remove('hidden');
                                        item.classList.add('visible');
                                        item.classList.add('active'); // Open matching items
                                    });
                                    highlightText(question, query);
                                    highlightText(answer, query);
                                    visibleCount++;
                                } else {
                                    // Hide non-matching items
                                    requestAnimationFrame(() => {
                                        item.classList.add('hidden');
                                        item.classList.remove('visible');
                                        item.classList.remove('active'); // Ensure non-matching items are closed
                                    });
                                }
                            });

                            // Second pass: Check sections and update visibility
                            faqSections.forEach(section => {
                                const visibleItems = section.querySelectorAll('.faq-item.visible');
                                if (visibleItems.length === 0) {
                                    section.style.display = 'none';
                                } else {
                                    section.style.display = 'block';
                                }
                            });

                            // Update search results text with animation
                            const resultsText = !query 
                                ? 'Showing all questions' 
                                : `Found ${visibleCount} matching question${visibleCount !== 1 ? 's' : ''}`;
                            
                            searchResults.style.opacity = '0';
                            setTimeout(() => {
                                searchResults.textContent = resultsText;
                                searchResults.style.opacity = '1';
                            }, 150);

                        }, 150); // Reduced delay for more responsive feel
                    }

                    function highlightText(element, query) {
                        const textNodes = getTextNodes(element);
                        textNodes.forEach(node => {
                            const text = node.nodeValue;
                            const parts = text.split(new RegExp(`(${query})`, 'gi'));
                            if (parts.length > 1) {
                                const fragment = document.createDocumentFragment();
                                parts.forEach(part => {
                                    if (part.toLowerCase() === query.toLowerCase()) {
                                        const highlightSpan = document.createElement('span');
                                        highlightSpan.className = 'highlight';
                                        highlightSpan.textContent = part;
                                        fragment.appendChild(highlightSpan);
                                    } else {
                                        fragment.appendChild(document.createTextNode(part));
                                    }
                                });
                                node.parentNode.replaceChild(fragment, node);
                            }
                        });
                    }

                    function resetHighlights(element) {
                        const highlights = element.querySelectorAll('.highlight');
                        highlights.forEach(highlight => {
                            const parent = highlight.parentNode;
                            parent.replaceChild(document.createTextNode(highlight.textContent), highlight);
                            parent.normalize(); // Merge adjacent text nodes
                        });
                    }

                    function getTextNodes(node) {
                        const textNodes = [];
                        if (node.nodeType === Node.TEXT_NODE) {
                            textNodes.push(node);
                        } else {
                            node.childNodes.forEach(child => {
                                textNodes.push(...getTextNodes(child));
                            });
                        }
                        return textNodes;
                    }

                    // Improved event listeners
                    faqSearch.addEventListener('input', (e) => {
                        e.preventDefault();
                        updateSearch();
                    });
                    
                    clearButton.addEventListener('click', (e) => {
                        e.preventDefault();
                        faqSearch.value = '';
                        updateSearch();
                        faqSearch.focus();
                    });

                    // Add keyboard navigation
                    faqSearch.addEventListener('keydown', (e) => {
                        if (e.key === 'Escape') {
                            e.preventDefault();
                            faqSearch.value = '';
                            updateSearch();
                        }
                    });

                    // FAQ dropdown functionality (keep this part)
                    faqItems.forEach(item => {
                        const question = item.querySelector('.faq-question');
                        question.addEventListener('click', () => {
                            item.classList.toggle('active');
                        });
                    });

                    // Add this to your existing DOMContentLoaded event listener
                    const resetButton = document.getElementById('resetFAQ');

                    resetButton.addEventListener('click', (e) => {
                        e.preventDefault();
                        
                        // Clear search input
                        faqSearch.value = '';
                        
                        // Hide clear button
                        clearButton.style.display = 'none';
                        
                        // Reset all FAQ items
                        faqItems.forEach(item => {
                            // Show all items
                            item.classList.remove('hidden');
                            item.classList.add('visible');
                            
                            // Close all expanded items
                            item.classList.remove('active');
                            
                            // Remove highlights
                            const question = item.querySelector('.faq-question h3');
                            const answer = item.querySelector('.faq-answer');
                            resetHighlights(question);
                            resetHighlights(answer);
                        });
                        
                        // Show all sections
                        faqSections.forEach(section => {
                            section.style.display = 'block';
                        });
                        
                        // Reset search results text
                        searchResults.textContent = 'Showing all questions';
                    });
                });
            </script>
</body>
</html>