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
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .search-box:focus-within {
            background: #2d2d2d;
            box-shadow: 0 0 0 2px #F8B83C, 0 4px 8px rgba(0, 0, 0, 0.2);
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

        .highlight {
            background-color: rgba(248, 184, 60, 0.3);
            padding: 2px 4px;
            border-radius: 3px;
            transition: background-color 0.2s ease;
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
                    <!-- Sales Section -->
                    <li><a href="manageSales.php"><i class="fas fa-tasks"></i>Manage Sales</a></li>
                    <li><a href="addSales.php"><i class="fas fa-cart-plus"></i>Add Sales</a></li>
                    <li><a href="dailySales.php"><i class="fas fa-calendar-day"></i>Daily Sales</a></li>
                    
                    <!-- FAQ Link -->
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
                                <h3>How do I use the Daily Sales report?</h3>
                                <i class="fas fa-chevron-down"></i>
                            </div>
                            <div class="faq-answer">
                                <h4>Daily Sales Features:</h4>
                                <ul>
                                    <li><strong>Real-time Tracking:</strong>
                                        <ul>
                                            <li>Real-time tracking of today's sales</li>
                                            <li>Product-wise sales breakdown</li>
                                            <li>Quantity and revenue monitoring</li>
                                            <li>Profit tracking for the current day</li>
                                        </ul>
                                    </li>
                                    <li><strong>Key Features:</strong>
                                        <ul>
                                            <li>Total sales calculation</li>
                                            <li>Total quantity tracking</li>
                                            <li>Gross profit calculations</li>
                                            <li>Product-wise breakdown</li>
                                        </ul>
                                    </li>
                                </ul>

                                <p class="note"><i class="fas fa-info-circle"></i> The Daily Sales report can be accessed from the Sales Report menu in the sidebar. Data updates automatically as new sales are recorded.</p>
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
                            window.location.href = '../admin/logout.php';
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

                    // FAQ functionality
                    const faqItems = document.querySelectorAll('.faq-item');
                    const faqSections = document.querySelectorAll('.faq-section');
                    const faqSearch = document.getElementById('faqSearch');
                    const clearButton = document.getElementById('clearSearch');
                    const searchResults = document.getElementById('searchResults');
                    const resetButton = document.getElementById('resetFAQ');
                    let searchTimeout;

                    // Toggle FAQ items
                    faqItems.forEach(item => {
                        const question = item.querySelector('.faq-question');
                        const answer = item.querySelector('.faq-answer');
                        
                        if (question && answer) {
                            question.addEventListener('click', () => {
                                const isActive = item.classList.contains('active');
                                
                                // Close all other items
                                faqItems.forEach(otherItem => {
                                    if (otherItem !== item && otherItem.classList.contains('active')) {
                                        otherItem.classList.remove('active');
                                        const otherAnswer = otherItem.querySelector('.faq-answer');
                                        otherAnswer.style.maxHeight = '0px';
                                    }
                                });
                                
                                // Toggle current item
                                item.classList.toggle('active');
                                answer.style.maxHeight = !isActive ? `${answer.scrollHeight}px` : '0px';
                            });
                        }
                    });

                    // Search functionality
                    function highlightText(element, query) {
                        if (!element || !query) return;
                        
                        const escapedQuery = query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
                        const regex = new RegExp(`(${escapedQuery})`, 'gi');
                        
                        const walker = document.createTreeWalker(
                            element,
                            NodeFilter.SHOW_TEXT,
                            null,
                            false
                        );

                        const textNodes = [];
                        while (walker.nextNode()) {
                            textNodes.push(walker.currentNode);
                        }

                        textNodes.forEach(textNode => {
                            const text = textNode.textContent;
                            if (text.toLowerCase().includes(query.toLowerCase())) {
                                const span = document.createElement('span');
                                span.innerHTML = text.replace(regex, '<span class="highlight">$1</span>');
                                textNode.parentNode.replaceChild(span, textNode);
                            }
                        });
                    }

                    function resetHighlights(element) {
                        if (!element) return;
                        
                        const highlights = element.querySelectorAll('.highlight');
                        highlights.forEach(highlight => {
                            const textNode = document.createTextNode(highlight.textContent);
                            highlight.parentNode.replaceChild(textNode, highlight);
                        });
                        element.normalize();
                    }

                    function updateSearch() {
                        const query = faqSearch.value.trim().toLowerCase();
                        clearButton.style.display = query ? 'flex' : 'none';
                        
                        if (searchTimeout) {
                            clearTimeout(searchTimeout);
                        }
                        
                        searchTimeout = setTimeout(() => {
                            let visibleCount = 0;
                            
                            // Reset all items and sections
                            faqItems.forEach(item => {
                                const question = item.querySelector('.faq-question h3');
                                const answer = item.querySelector('.faq-answer');
                                resetHighlights(question);
                                resetHighlights(answer);
                                item.classList.remove('active');
                                item.style.display = 'block';
                                answer.style.maxHeight = '0px';
                            });
                            
                            faqSections.forEach(section => {
                                section.style.display = 'block';
                            });
                            
                            if (query) {
                                faqItems.forEach(item => {
                                    const question = item.querySelector('.faq-question h3');
                                    const answer = item.querySelector('.faq-answer');
                                    const questionText = question.textContent.toLowerCase();
                                    const answerText = answer.textContent.toLowerCase();
                                    
                                    if (questionText.includes(query) || answerText.includes(query)) {
                                        item.style.display = 'block';
                                        highlightText(question, query);
                                        highlightText(answer, query);
                                        
                                        // Auto-expand items with matches
                                        item.classList.add('active');
                                        answer.style.maxHeight = `${answer.scrollHeight}px`;
                                        
                                        visibleCount++;
                                    } else {
                                        item.style.display = 'none';
                                    }
                                });
                                
                                // Hide empty sections
                                faqSections.forEach(section => {
                                    const hasVisibleItems = Array.from(section.querySelectorAll('.faq-item'))
                                        .some(item => item.style.display !== 'none');
                                    section.style.display = hasVisibleItems ? 'block' : 'none';
                                });
                            } else {
                                visibleCount = faqItems.length;
                            }
                            
                            searchResults.textContent = query
                                ? `Found ${visibleCount} result${visibleCount !== 1 ? 's' : ''} for "${query}"`
                                : 'Showing all questions';
                            
                        }, 150);
                    }

                    // Event Listeners
                    faqSearch.addEventListener('input', updateSearch);
                    
                    clearButton.addEventListener('click', (e) => {
                        e.preventDefault();
                        faqSearch.value = '';
                        updateSearch();
                        faqSearch.focus();
                    });

                    resetButton.addEventListener('click', (e) => {
                        e.preventDefault();
                        faqSearch.value = '';
                        clearButton.style.display = 'none';
                        
                        faqItems.forEach(item => {
                            item.classList.remove('active');
                            const question = item.querySelector('.faq-question h3');
                            const answer = item.querySelector('.faq-answer');
                            resetHighlights(question);
                            resetHighlights(answer);
                            item.style.display = 'block';
                            answer.style.maxHeight = '0px';
                        });
                        
                        faqSections.forEach(section => {
                            section.style.display = 'block';
                        });
                        
                        searchResults.textContent = 'Showing all questions';
                    });
                });
            </script>
</body>
</html>