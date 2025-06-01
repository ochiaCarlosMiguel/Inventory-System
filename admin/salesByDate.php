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

// For debugging purposes, you can temporarily add these lines:
// echo "Session ID: " . $_SESSION['user_id'] . "<br>";
// echo "User Name: " . $userName . "<br>";
// echo "Profile Image: " . $profileImage . "<br>";

// Handle AJAX request for report data
if (isset($_GET['action']) && $_GET['action'] == 'getSalesReport') {
    header('Content-Type: application/json');
    
    try {
        $dateFrom = $_GET['dateFrom'];
        $dateTo = $_GET['dateTo'];
        
        // Add one day to dateTo to include the entire day
        $dateToAdjusted = date('Y-m-d', strtotime($dateTo . ' +1 day'));

        $query = "SELECT s.sale_date, p.product_title, p.buying_price, 
                        si.price, si.quantity, si.total
                 FROM sales s
                 JOIN sale_items si ON s.id = si.sale_id
                 JOIN products p ON si.product_id = p.id
                 WHERE s.sale_date BETWEEN :dateFrom AND :dateToAdjusted
                 ORDER BY s.sale_date ASC";

        $stmt = $pdo->prepare($query);
        $stmt->execute([
            ':dateFrom' => $dateFrom,
            ':dateToAdjusted' => $dateToAdjusted
        ]);

        $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Calculate totals
        $totalBuyingPrice = 0;
        $totalSellingPrice = 0;

        foreach ($sales as $sale) {
            $totalBuyingPrice += $sale['buying_price'] * $sale['quantity'];
            $totalSellingPrice += $sale['total'];
            
            // Format numbers to 3 decimal places
            $sale['buying_price'] = number_format((float)$sale['buying_price'], 3, '.', '');
            $sale['price'] = number_format((float)$sale['price'], 3, '.', '');
            $sale['total'] = number_format((float)$sale['total'], 3, '.', '');
        }

        // Update calculations with 3 decimal places
        $costOfSales = number_format($totalBuyingPrice, 3, '.', '');
        $businessTax = number_format($totalSellingPrice * 0.03, 3, '.', ''); // 3% of total sales
        $grossProfit = number_format($totalSellingPrice - floatval($businessTax), 3, '.', ''); // Total Sales - Tax
        $totalSellingPrice = number_format($totalSellingPrice, 3, '.', '');

        echo json_encode([
            'success' => true,
            'sales' => $sales,
            'totals' => [
                'totalPurchases' => $totalBuyingPrice,
                'totalSales' => $totalSellingPrice,
                'businessTax' => $businessTax,
                'costOfSales' => $costOfSales,
                'grossProfit' => $grossProfit
            ]
        ]);
        exit;
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
        exit;
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

        /* Main Content Styles */
        .main-content {
            margin-left: 249px;
            padding: 80px 20px 20px;
        }

        .date-range-container {
            background-color: #363333;
            padding: 25px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .date-range-container h2 {
            color: #F8B83C;
            font-family: 'Montserrat', sans-serif;
            margin-bottom: 20px;
            font-size: 20px;
            font-weight: 700;
        }

        .date-inputs {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .date-field {
            flex: 1;
            min-width: 200px;
        }

        .date-field label {
            display: block;
            color: #AEB2B7;
            margin-bottom: 8px;
            font-family: 'Century Gothic', sans-serif;
            font-size: 14px;
            font-weight: 600;
        }

        .date-field input {
            width: 100%;
            padding: 8px;
            border: 1px solid #4a4848;
            background-color: #2d2d2d;
            color: #AEB2B7;
            border-radius: 4px;
        }

        .generate-btn {
            background-color: #750605;
            color: #F8B83C;
            border: none;
            padding: 12px 25px;
            border-radius: 6px;
            cursor: pointer;
            font-family: 'Century Gothic', sans-serif;
            font-size: 14px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .generate-btn:hover {
            background-color: #8f0806;
            transform: translateY(-2px);
        }

        .generate-btn::before {
            content: '\f1ec';
            font-family: 'Font Awesome 5 Free';
            font-weight: 900;
        }

        /* Report Styles */
        .report-container {
            background-color: #363333;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        /* Report Actions Bar */
        .report-actions {
            margin-bottom: 25px;
            display: flex;
            gap: 15px;
            padding: 0 10px;
        }

        .action-btn {
            background-color: #750605;
            color: #F8B83C;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            font-family: 'Century Gothic', sans-serif;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .action-btn:hover {
            background-color: #8f0806;
            transform: translateY(-2px);
        }

        .action-btn i {
            font-size: 16px;
        }

        /* Report Paper Styles */
        .report-paper {
            background-color: white;
            padding: 40px 50px;
            border-radius: 8px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }

        /* Report Header Styles */
        .report-header {
            text-align: center;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 2px solid #750605;
        }

        .report-header h1 {
            color: #750605;
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 15px;
            font-family: 'Montserrat', sans-serif;
        }

        .report-dates {
            display: flex;
            justify-content: center;
            gap: 30px;
            color: #666;
            font-size: 15px;
            font-family: 'Century Gothic', sans-serif;
        }

        .report-dates p {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .report-dates span {
            font-weight: bold;
            color: #333;
        }

        /* Table Styles */
        .report-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-family: 'Century Gothic', sans-serif;
        }

        .report-table th {
            background-color: #750605;
            color: #F8B83C;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
            white-space: nowrap;
        }

        .report-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
            color: #333;
            font-size: 14px;
        }

        .report-table tbody tr:hover {
            background-color: #f9f9f9;
        }

        /* Table Footer (Totals) Styles */
        .report-table tfoot tr {
            background-color: #f8f8f8;
        }

        .report-table tfoot td {
            padding: 15px;
            font-weight: 600;
        }

        .total-label {
            color: #750605;
            font-size: 14px;
            text-align: right;
        }

        .total-amount {
            color: #333;
            font-size: 14px;
            font-weight: bold;
        }

        /* Last row in tfoot (Gross Profit) special styling */
        .report-table tfoot tr:last-child {
            background-color: #750605;
        }

        .report-table tfoot tr:last-child td {
            color: #F8B83C;
            font-size: 16px;
            font-weight: bold;
        }

        /* Print Styles */
        @media print {
            body {
                background: none;
            }

            .sidebar,
            .topbar,
            .date-range-container,
            .report-actions {
                display: none !important;
            }

            .main-content {
                margin: 0 !important;
                padding: 0 !important;
            }

            .report-container {
                background: none;
                padding: 0;
                box-shadow: none;
            }

            .report-paper {
                box-shadow: none;
                padding: 20px;
            }

            .report-table th {
                background-color: #750605 !important;
                color: #F8B83C !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .report-table tfoot tr:last-child {
                background-color: #750605 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .report-table tfoot tr:last-child td {
                color: #F8B83C !important;
            }
        }

        /* Date Input Styling */
        .date-input-wrapper {
            position: relative;
            width: 100%;
        }

        .date-input-wrapper input[type="date"] {
            width: 100%;
            padding: 10px 35px 10px 15px;
            border: 2px solid #4a4848;
            background-color: #2d2d2d;
            color: #AEB2B7;
            border-radius: 6px;
            font-family: 'Century Gothic', sans-serif;
            font-size: 14px;
            transition: all 0.3s ease;
            cursor: pointer;
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
        }

        .date-input-wrapper input[type="date"]::-webkit-calendar-picker-indicator {
            opacity: 0;
            width: 100%;
            height: 100%;
            position: absolute;
            top: 0;
            left: 0;
            cursor: pointer;
        }

        .date-input-wrapper input[type="date"]:hover,
        .date-input-wrapper input[type="date"]:focus {
            border-color: #750605;
            outline: none;
            box-shadow: 0 0 0 2px rgba(117, 6, 5, 0.2);
        }

        .date-input-wrapper .fa-calendar-alt {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #F8B83C;
            pointer-events: none;
            transition: all 0.3s ease;
        }

        .date-input-wrapper:hover .fa-calendar-alt {
            color: #750605;
        }

        /* Update existing date field styles */
        .date-field {
            flex: 1;
            min-width: 200px;
        }

        .date-field label {
            display: block;
            color: #AEB2B7;
            margin-bottom: 8px;
            font-family: 'Century Gothic', sans-serif;
            font-size: 14px;
            font-weight: 600;
        }

        /* Date inputs container */
        .date-inputs {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        /* Generate button styling update */
        .generate-btn {
            background-color: #750605;
            color: #F8B83C;
            border: none;
            padding: 12px 25px;
            border-radius: 6px;
            cursor: pointer;
            font-family: 'Century Gothic', sans-serif;
            font-size: 14px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .generate-btn:hover {
            background-color: #8f0806;
            transform: translateY(-2px);
        }

        .generate-btn::before {
            content: '\f1ec';
            font-family: 'Font Awesome 5 Free';
            font-weight: 900;
        }

        /* Container styling update */
        .date-range-container {
            background-color: #363333;
            padding: 25px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .date-range-container h2 {
            color: #F8B83C;
            font-family: 'Montserrat', sans-serif;
            margin-bottom: 20px;
            font-size: 20px;
            font-weight: 700;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .date-inputs {
                flex-direction: column;
            }

            .date-field {
                width: 100%;
            }
        }

        /* Add these styles to ensure proper PDF formatting */
        .report-table td,
        .report-table th {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 200px;
        }
        
        .total-label,
        .total-amount {
            white-space: nowrap;
        }
    </style>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
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
                    <li class="dropdown open">
                        <a href="#" class="dropdown-toggle">
                            <i class="fas fa-chart-bar"></i>Sales Report
                            <i class="fas fa-chevron-down arrow"></i>
                        </a>
                        <ul class="submenu">
                            <li class="active"><a href="salesByDate.php"><i class="fas fa-calendar-alt"></i>Sales by Date</a></li>
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
            });
        </script>

    <!-- Main Content -->
    <div class="main-content">
        <div class="date-range-container">
            <h2>Date Range</h2>
            <form id="dateRangeForm">
                <div class="date-inputs">
                    <div class="date-field">
                        <label for="dateFrom">From:</label>
                        <div class="date-input-wrapper">
                            <input type="date" id="dateFrom" name="dateFrom" required>
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                    </div>
                    <div class="date-field">
                        <label for="dateTo">To:</label>
                        <div class="date-input-wrapper">
                            <input type="date" id="dateTo" name="dateTo" required>
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                    </div>
                </div>
                <button type="submit" class="generate-btn">Generate Report</button>
            </form>
        </div>

        <!-- Report Container -->
        <div id="reportContainer" class="report-container" style="display: none;">
            <div class="report-actions">
                <button id="printReport" class="action-btn"><i class="fas fa-print"></i> Print</button>
                <button id="downloadPdf" class="action-btn"><i class="fas fa-download"></i> Download PDF</button>
            </div>
            <div class="report-paper">
                <div class="report-header">
                    <h1>ARTMORE INVENTORY SYSTEM</h1>
                    <div class="report-subtitle">Sales Report</div>
                    <div class="report-dates">
                        <p>From: <span id="reportDateFrom"></span></p>
                        <p>•</p>
                        <p>To: <span id="reportDateTo"></span></p>
                    </div>
                </div>
                <table class="report-table">
                    <thead>
                        <tr>
                            <th>Date & Time</th>
                            <th>Product Title</th>
                            <th>Buying Price</th>
                            <th>Selling Price</th>
                            <th>Total Qty</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody id="reportTableBody">
                        <!-- Data will be populated dynamically -->
                    </tbody>
                    <tfoot id="reportTableFoot">
                        <!-- Totals will be populated dynamically -->
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('dateRangeForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const dateFrom = document.getElementById('dateFrom').value;
            const dateTo = document.getElementById('dateTo').value;
            
            if (!dateFrom || !dateTo) {
                alert('Please select both dates');
                return;
            }

            try {
                // Update the fetch URL to include the action parameter
                const response = await fetch(`salesByDate.php?action=getSalesReport&dateFrom=${dateFrom}&dateTo=${dateTo}`);
                const data = await response.json();

                if (data.success) {
                    // Update report dates
                    document.getElementById('reportDateFrom').textContent = new Date(dateFrom).toLocaleDateString();
                    document.getElementById('reportDateTo').textContent = new Date(dateTo).toLocaleDateString();
                    
                    // Check if there are any sales
                    if (data.sales.length === 0) {
                        alert('No sales found for the selected date range');
                        return;
                    }
                    
                    // Populate table body with sales data
                    const tbody = document.getElementById('reportTableBody');
                    tbody.innerHTML = data.sales.map(sale => `
                        <tr>
                            <td>${new Date(sale.sale_date).toLocaleString()}</td>
                            <td>${sale.product_title}</td>
                            <td>₱${parseFloat(sale.buying_price).toFixed(3)}</td>
                            <td>₱${parseFloat(sale.price).toFixed(3)}</td>
                            <td>${sale.quantity}</td>
                            <td>₱${parseFloat(sale.total).toFixed(3)}</td>
                        </tr>
                    `).join('');

                    // Update totals in footer
                    const tfoot = document.getElementById('reportTableFoot');
                    tfoot.innerHTML = `
                        <tr>
                            <td colspan="5" class="total-label">Total Sales:</td>
                            <td class="total-amount">₱${parseFloat(data.totals.totalSales).toFixed(3)}</td>
                        </tr>
                        <tr>
                            <td colspan="5" class="total-label">Cost of Sales:</td>
                            <td class="total-amount">₱${parseFloat(data.totals.costOfSales).toFixed(3)}</td>
                        </tr>
                        <tr>
                            <td colspan="5" class="total-label">Percentage Tax (3%):</td>
                            <td class="total-amount">₱${parseFloat(data.totals.businessTax).toFixed(3)}</td>
                        </tr>
                        <tr>
                            <td colspan="5" class="total-label">Gross Profit:</td>
                            <td class="total-amount">₱${parseFloat(data.totals.grossProfit).toFixed(3)}</td>
                        </tr>
                    `;

                    // Show report container
                    document.getElementById('reportContainer').style.display = 'block';
                } else {
                    alert('Error generating report: ' + data.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred while generating the report');
            }
        });

        // Print report handler
        document.getElementById('printReport').addEventListener('click', function() {
            window.print();
        });

        // Add this function for PDF generation
        function generatePDF() {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF('p', 'pt', 'a4');
            
            // Get report data
            const dateFrom = document.getElementById('reportDateFrom').textContent;
            const dateTo = document.getElementById('reportDateTo').textContent;
            
            // Add company header
            doc.setFontSize(20);
            doc.setTextColor(117, 6, 5); // #750605
            doc.text('ARTMORE INVENTORY SYSTEM', doc.internal.pageSize.width/2, 40, { align: 'center' });
            
            // Add report title
            doc.setFontSize(14);
            doc.text('Sales Report', doc.internal.pageSize.width/2, 60, { align: 'center' });
            
            // Add date range
            doc.setFontSize(12);
            doc.setTextColor(51, 51, 51); // #333333
            doc.text(`From: ${dateFrom}   To: ${dateTo}`, doc.internal.pageSize.width/2, 80, { align: 'center' });
            
            // Get and format table data
            const tableData = [];
            const rows = document.querySelectorAll('#reportTableBody tr');
            rows.forEach(row => {
                const rowData = [];
                row.querySelectorAll('td').forEach((cell, index) => {
                    // Format currency values (columns 2, 3, and 5)
                    if (index === 2 || index === 3 || index === 5) {
                        // Remove the ₱ symbol and keep only the number
                        const value = cell.textContent.trim().replace('₱', '');
                        rowData.push(`PHP ${value}`);
                    } else {
                        rowData.push(cell.textContent.trim());
                    }
                });
                tableData.push(rowData);
            });
            
            // Get and format totals data
            const totalsRows = [];
            const footerRows = document.querySelectorAll('#reportTableFoot tr');
            footerRows.forEach(row => {
                const label = row.querySelector('.total-label').textContent.replace(':', '');
                const amount = row.querySelector('.total-amount').textContent
                    .trim()
                    .replace('₱', '')
                    .trim();
                totalsRows.push([label, `PHP ${amount}`]);
            });
            
            // Add sales table
            doc.autoTable({
                head: [['Date & Time', 'Product Title', 'Buying Price', 'Selling Price', 'Total Qty', 'Total']],
                body: tableData,
                startY: 100,
                styles: {
                    fontSize: 10,
                    cellPadding: 5,
                },
                headStyles: {
                    fillColor: [117, 6, 5], // #750605
                    textColor: [248, 184, 60], // #F8B83C
                    fontStyle: 'bold',
                },
                alternateRowStyles: {
                    fillColor: [245, 245, 245],
                },
                // Align currency columns to the right
                columnStyles: {
                    2: { halign: 'right' }, // Buying Price
                    3: { halign: 'right' }, // Selling Price
                    5: { halign: 'right' }  // Total
                }
            });
            
            // Add totals section with adjusted positioning
            const finalY = doc.lastAutoTable.finalY + 20;
            const labelX = 40; // Left position for labels
            const valueX = doc.internal.pageSize.width - 60; // Right position for values
            
            totalsRows.forEach((row, index) => {
                const isLastRow = index === totalsRows.length - 1;
                const yPosition = finalY + (index * 25) + 17;
                
                if (isLastRow) {
                    // Special styling for Gross Profit row
                    doc.setFillColor(117, 6, 5);
                    doc.rect(20, finalY + (index * 25), doc.internal.pageSize.width - 40, 25, 'F');
                    doc.setTextColor(248, 184, 60);
                } else {
                    doc.setTextColor(51, 51, 51);
                }
                
                doc.setFontSize(10);
                
                // Draw label (left-aligned)
                doc.text(row[0], labelX, yPosition, { align: 'left' });
                
                // Draw amount (right-aligned)
                doc.text(row[1], valueX, yPosition, { align: 'right' });
            });
            
            // Add footer
            const pageCount = doc.internal.getNumberOfPages();
            doc.setFontSize(10);
            doc.setTextColor(51, 51, 51);
            for(let i = 1; i <= pageCount; i++) {
                doc.setPage(i);
                doc.text(`Page ${i} of ${pageCount}`, doc.internal.pageSize.width/2, doc.internal.pageSize.height - 20, { align: 'center' });
                doc.text(`Generated on: ${new Date().toLocaleString()}`, doc.internal.pageSize.width - 20, doc.internal.pageSize.height - 20, { align: 'right' });
            }
            
            // Save the PDF
            const fileName = `Sales_Report_${dateFrom}_to_${dateTo}.pdf`.replace(/[/\\?%*:|"<>]/g, '-');
            doc.save(fileName);
        }

        // Update the download button click handler
        document.getElementById('downloadPdf').addEventListener('click', generatePDF);
    </script>
</body>
</html>
