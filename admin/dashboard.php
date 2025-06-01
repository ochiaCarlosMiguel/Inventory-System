<?php
// Start the session at the very beginning

session_start(); // Start the session
$user_id = $_SESSION['user_id'];
echo $user_id;

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    // User is not logged in, redirect to index.php
    header("Location: index.php");
    exit(); // Ensure no further code is executed
}

// Include the database connection
require_once '../connection/connections.php';

// Enhanced session security check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    // User is not logged in, redirect to login page
    header("Location: index.php");
    exit();
}

// Check if user has admin privileges
if ($_SESSION['role'] !== 'admin') {
    // User doesn't have admin privileges, redirect to appropriate page
    header("Location: ../staff/manageSales.php");
    exit();
}

// Initialize default values (removed Guest User default)
$userName = '';
$profileImage = "../upload/default-profile.jpg";

// Initialize counters
$userCount = 0;
$categoryCount = 0;
$productCount = 0;
$salesCount = 0;

// Fetch counts from database
try {
    // Count users
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $userCount = $stmt->fetchColumn();

    // Count categories
    $stmt = $pdo->query("SELECT COUNT(*) FROM categories");
    $categoryCount = $stmt->fetchColumn();

    // Count products
    $stmt = $pdo->query("SELECT COUNT(*) FROM products");
    $productCount = $stmt->fetchColumn();

    // Count total items sold from sale_items
    $stmt = $pdo->query("SELECT SUM(quantity) as total_items FROM sale_items");
    $salesCount = $stmt->fetchColumn() ?? 0;

} catch (PDOException $e) {
    error_log("Error fetching counts: " . $e->getMessage());
}

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
                }
            }
        }
    } catch (PDOException $e) {
        error_log("Database Error: " . $e->getMessage());
    }
}

// Fetch highest selling products (only sold products)
try {
    $stmt = $pdo->query("
        SELECT 
            p.product_title,
            SUM(si.quantity) as total_sold,
            p.quantity as total_quantity
        FROM products p
        INNER JOIN sale_items si ON p.id = si.product_id
        GROUP BY p.id, p.product_title, p.quantity
        HAVING SUM(si.quantity) > 0
        ORDER BY total_sold DESC
        LIMIT 5
    ");
    $topProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching top products: " . $e->getMessage());
    $topProducts = [];
}

// Fetch latest sales
try {
    $stmt = $pdo->query("
        SELECT 
            s.id,
            p.product_title,
            s.sale_date,
            si.total as total_sale
        FROM sales s
        JOIN sale_items si ON s.id = si.sale_id
        JOIN products p ON si.product_id = p.id
        ORDER BY s.sale_date DESC
        LIMIT 5
    ");
    $latestSales = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching latest sales: " . $e->getMessage());
    $latestSales = [];
}

// Add this new code after the latest sales query
try {
    // Get current year
    $currentYear = date('Y');
    
    // Fetch monthly sales for current year
    $stmt = $pdo->prepare("
        SELECT 
            MONTH(s.sale_date) as month,
            SUM(si.total) as monthly_total
        FROM sales s
        JOIN sale_items si ON s.id = si.sale_id
        WHERE YEAR(s.sale_date) = ?
        GROUP BY MONTH(s.sale_date)
        ORDER BY month
    ");
    $stmt->execute([$currentYear]);
    $currentYearSales = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Initialize arrays with zeros for all months
    $monthlyData = array_fill(1, 12, 0);
    
    // Fill in actual sales data
    foreach ($currentYearSales as $sale) {
        $monthlyData[$sale['month']] = floatval($sale['monthly_total']);
    }
    
    // Convert to JSON for JavaScript
    $currentYearDataJSON = json_encode(array_values($monthlyData));
    
    // Fetch last year's data if exists
    $stmt = $pdo->prepare("
        SELECT 
            MONTH(s.sale_date) as month,
            SUM(si.total) as monthly_total
        FROM sales s
        JOIN sale_items si ON s.id = si.sale_id
        WHERE YEAR(s.sale_date) = ?
        GROUP BY MONTH(s.sale_date)
        ORDER BY month
    ");
    $stmt->execute([$currentYear - 1]);
    $lastYearSales = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Initialize last year's data with zeros
    $lastYearData = array_fill(1, 12, 0);
    
    // Fill in actual last year's sales if any exist
    foreach ($lastYearSales as $sale) {
        $lastYearData[$sale['month']] = floatval($sale['monthly_total']);
    }
    
    $lastYearDataJSON = json_encode(array_values($lastYearData));
    
    // Check if there's any sales data
    $hasLastYearData = !empty($lastYearSales);
    
} catch (PDOException $e) {
    error_log("Error fetching sales data: " . $e->getMessage());
    $currentYearDataJSON = json_encode(array_fill(0, 12, 0));
    $lastYearDataJSON = json_encode(array_fill(0, 12, 0));
    $hasLastYearData = false;
}

// Add this new code after the existing sales comparison query
try {
    // Get current month and previous month
    $currentMonth = date('m');
    $currentYear = date('Y');
    $previousMonth = date('m', strtotime('-1 month'));
    $previousMonthYear = date('Y', strtotime('-1 month'));
    
    // Fetch daily sales for current month
    $stmt = $pdo->prepare("
        SELECT 
            DAY(s.sale_date) as day,
            SUM(si.total) as daily_total
        FROM sales s
        JOIN sale_items si ON s.id = si.sale_id
        WHERE MONTH(s.sale_date) = ? AND YEAR(s.sale_date) = ?
        GROUP BY DAY(s.sale_date)
        ORDER BY day
    ");
    $stmt->execute([$currentMonth, $currentYear]);
    $currentMonthSales = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Initialize arrays with zeros for all days
    $daysInMonth = date('t');
    $dailyData = array_fill(1, $daysInMonth, 0);
    
    // Fill in actual sales data
    foreach ($currentMonthSales as $sale) {
        $dailyData[$sale['day']] = floatval($sale['daily_total']);
    }
    
    // Convert to JSON for JavaScript
    $currentMonthDataJSON = json_encode(array_values($dailyData));
    
    // Fetch previous month's data
    $stmt = $pdo->prepare("
        SELECT 
            DAY(s.sale_date) as day,
            SUM(si.total) as daily_total
        FROM sales s
        JOIN sale_items si ON s.id = si.sale_id
        WHERE MONTH(s.sale_date) = ? AND YEAR(s.sale_date) = ?
        GROUP BY DAY(s.sale_date)
        ORDER BY day
    ");
    $stmt->execute([$previousMonth, $previousMonthYear]);
    $previousMonthSales = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Initialize previous month's data with zeros
    $daysInPreviousMonth = date('t', strtotime('-1 month'));
    $previousMonthData = array_fill(1, $daysInPreviousMonth, 0);
    
    // Fill in actual previous month's sales
    foreach ($previousMonthSales as $sale) {
        $previousMonthData[$sale['day']] = floatval($sale['daily_total']);
    }
    
    $previousMonthDataJSON = json_encode(array_values($previousMonthData));
    
    // Check if there's any sales data
    $hasPreviousMonthData = !empty($previousMonthSales);
    
} catch (PDOException $e) {
    error_log("Error fetching monthly sales data: " . $e->getMessage());
    $currentMonthDataJSON = json_encode(array_fill(0, date('t'), 0));
    $previousMonthDataJSON = json_encode(array_fill(0, date('t', strtotime('-1 month')), 0));
    $hasPreviousMonthData = false;
}
?>

<!DOCTYPE html>
<html lang="en">
    <!-- Head Section -->
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Dashboard</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    </head>

    <body>
        <!-- Welcome Message -->
        <div id="welcomeMessage" class="welcome-message" style="display: none;">
            Welcome, <?php echo htmlspecialchars($userName); ?>!
        </div>

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
                <li class="active">
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
                                
                <!-- Professor Comments (New) -->
                <li>
                    <a href="https://docs.google.com/document/d/1goWDCdj7-0xLrAn3IzMyrBVnFQwam9tuSlnhFdgwKeg/edit" target="_blank">
                        <i class="fas fa-comments"></i>Panel Comments
                        <i class="fas fa-external-link-alt" style="font-size: 0.8em; margin-left: 5px;"></i>
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

        <!-- Main Content Area -->
        <div class="main-content">
            <!-- Stats Cards Row -->
            <div class="stats-container">
                <!-- Users Stats -->
                <div class="stat-card">
                    <div class="icon-holder" style="background-color: #750605;">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <h2 class="stat-count"><?php echo htmlspecialchars($userCount); ?></h2>
                        <p class="stat-label">Users</p>
                    </div>
                </div>

                <!-- Categories Stats -->
                <div class="stat-card">
                    <div class="icon-holder" style="background-color: #F8B83C;">
                        <i class="fas fa-tags"></i>
                    </div>
                    <div class="stat-info">
                        <h2 class="stat-count"><?php echo htmlspecialchars($categoryCount); ?></h2>
                        <p class="stat-label">Categories</p>
                    </div>
                </div>

                <!-- Products Stats -->
                <div class="stat-card">
                    <div class="icon-holder" style="background-color: #2C3E50;">
                        <i class="fas fa-box"></i>
                    </div>
                    <div class="stat-info">
                        <h2 class="stat-count"><?php echo htmlspecialchars($productCount); ?></h2>
                        <p class="stat-label">Products</p>
                    </div>
                </div>

                <!-- Sales Stats -->
                <div class="stat-card">
                    <div class="icon-holder" style="background-color: #27AE60;">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stat-info">
                        <h2 class="stat-count"><?php echo htmlspecialchars($salesCount); ?></h2>
                        <p class="stat-label">Items Sold</p>
                    </div>
                </div>
            </div>

            <!-- Tables Section -->
            <div class="table-container">
                <div class="table-row">
                    <!-- Highest Selling Products -->
                    <div class="dashboard-table">
                        <div class="table-header">
                            <i class="fas fa-crown"></i>
                            <h2>HIGHEST SELLING PRODUCTS</h2>
                        </div>
                        <hr class="header-line">
                        <table>
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Total Sold</th>
                                    <th>Total Quantity</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($topProducts as $product): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($product['product_title']); ?></td>
                                        <td><?php echo htmlspecialchars($product['total_sold']); ?></td>
                                        <td><?php echo htmlspecialchars($product['total_quantity']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($topProducts)): ?>
                                    <tr>
                                        <td colspan="3" style="text-align: center;">No products have been sold yet</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Latest Sales -->
                    <div class="dashboard-table">
                        <div class="table-header">
                            <i class="fas fa-shopping-cart"></i>
                            <h2>LATEST SALES</h2>
                        </div>
                        <hr class="header-line">
                        <table>
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Product Name</th>
                                    <th>Date</th>
                                    <th>Total Sale</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($latestSales as $sale): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($sale['id']); ?></td>
                                        <td><?php echo htmlspecialchars($sale['product_title']); ?></td>
                                        <td><?php echo date('Y-m-d H:i', strtotime($sale['sale_date'])); ?></td>
                                        <td>₱<?php echo number_format($sale['total_sale'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($latestSales)): ?>
                                    <tr>
                                        <td colspan="4" style="text-align: center;">No sales found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Sales Chart Row -->
                <div class="table-row">
                    <!-- Yearly Sales Chart -->
                    <div class="dashboard-table" style="width: 50%;">
                        <div class="table-header">
                            <i class="fas fa-chart-line"></i>
                            <h2>YEARLY SALES COMPARISON</h2>
                        </div>
                        <hr class="header-line">
                        <div id="salesAlert" class="sales-alert" style="display: none;"></div>
                        <canvas id="salesChart"></canvas>
                    </div>

                    <!-- Monthly Sales Chart -->
                    <div class="dashboard-table" style="width: 50%;">
                        <div class="table-header">
                            <i class="fas fa-chart-bar"></i>
                            <h2>MONTHLY SALES COMPARISON</h2>
                        </div>
                        <hr class="header-line">
                        <div id="monthlySalesAlert" class="sales-alert" style="display: none;"></div>
                        <canvas id="monthlySalesChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Styles -->
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
                background-color: #750605;
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

            /* Main Content Styles */
            .main-content {
                margin-left: 249px;
                margin-top: 60px;
                padding: 15px 15px;
                position: relative;
            }

            /* Stats cards container */
            .stats-container {
                display: flex;
                gap: 15;
                flex-wrap: wrap;
                margin-bottom: 15px;
                padding: 15px 10px;
            }

            .stat-card {
                width: calc(25% - 12px); /* Equal width for 4 cards with gap consideration */
                min-width: 220px;
                height: 100px; /* Slightly reduced height */
                margin: 0;
            }

            /* Tables layout */
            .table-container {
                display: flex;
                flex-direction: column;
                gap: 15px;
            }

            .table-row {
                display: flex;
                gap: 15px;
                width: 100%;
                padding: 0 10px;
            }

            .tables-container {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 15px;
            }

            .dashboard-table {
                background-color: #1E1E1E;
                border-radius: 8px;
                padding: 15px;
                height: fit-content;
            }

            /* Chart container */
            #salesChart {
                height: 300px;
                margin-top: 15px;
            }

            /* Responsive adjustments */
            @media screen and (max-width: 1600px) {
                .stat-card {
                    width: calc(50% - 8px);
                }
                
                .tables-container {
                    grid-template-columns: 1fr;
                }
            }

            @media screen and (max-width: 1200px) {
                .stat-card {
                    width: 100%;
                }
            }

            /* Card and Table Styles */
            .stats-container {
                display: flex;
                gap: 83px;
                flex-wrap: wrap;
                margin-left: 15px;
                margin-bottom: 20px;
                justify-content: flex-start;
            }

            .stat-card {
                width: 240px;
                height: 112px;
                background-color: #1E1E1E;
                border-radius: 8px;
                display: flex;
                overflow: hidden;
                box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
                transition: transform 0.3s ease, box-shadow 0.3s ease;
                margin: 0;
            }

            .stat-card:hover {
                transform: translateY(-5px);
                box-shadow: 0 6px 12px rgba(0, 0, 0, 0.4);
            }

            .icon-holder {
                width: 99.46px;
                height: 112px;
                display: flex;
                align-items: center;
                justify-content: center;
                position: relative;
            }

            .icon-holder i {
                font-size: 2.5rem;
                color: rgba(255, 255, 255, 0.9);
            }

            .stat-info {
                flex-grow: 1;
                padding: 20px;
                display: flex;
                flex-direction: column;
                justify-content: center;
                background-color: #1E1E1E;
                border-left: 1px solid rgba(255, 255, 255, 0.1);
            }

            .stat-count {
                font-family: 'Century Gothic', sans-serif;
                font-size: 32px;
                color: #ffffff;
                margin-bottom: 5px;
                font-weight: bold;
            }

            .stat-label {
                font-family: 'Century Gothic', sans-serif;
                font-size: 16px;
                color: #AEB2B7;
                text-transform: uppercase;
                letter-spacing: 1px;
            }

            .icon-holder i {
                transition: transform 0.3s ease;
            }

            .stat-card:hover .icon-holder i {
                transform: scale(1.1);
            }

            /* Responsive Styles */
            @media screen and (max-width: 1400px) {
                .stats-container {
                    gap: 35px;
                }
                
                .stat-card {
                    margin-bottom: 0;
                }
            }

            @media screen and (min-width: 1401px) {
                .stats-container {
                    padding: 0;
                }
            }

            .tables-container {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 15px;
                padding: 0;
                margin-top: 15px;
            }

            .dashboard-table {
                flex: 1;
                background-color: #1E1E1E;
                border-radius: 8px;
                padding: 20px;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                margin-bottom: 20px;
            }

            .table-header {
                display: flex;
                align-items: center;
                gap: 10px;
                margin-bottom: 10px;
            }

            .table-header i {
                color: #F8B83C;
                font-size: 20px;
            }

            .table-header h2 {
                color: #AEB2B7;
                font-family: 'Century Gothic', sans-serif;
                font-size: 16px;
                font-weight: bold;
            }

            .header-line {
                border: none;
                border-bottom: 1px solid #333;
                margin: 10px 0 20px 0;
            }

            table {
                width: 100%;
                border-collapse: collapse;
            }

            th, td {
                padding: 12px;
                text-align: left;
                font-family: 'Century Gothic', sans-serif;
                border-bottom: 1px solid #333;
            }

            th {
                color: #F8B83C;
                font-weight: bold;
                font-size: 14px;
            }

            td {
                color: #AEB2B7;
                font-size: 14px;
            }

            tr:hover td {
                background-color: #2d2d2d;
            }

            .product-list {
                margin-top: 10px;
            }

            .product-card {
                display: flex;
                gap: 15px;
                padding: 10px;
                border-radius: 6px;
                transition: background-color 0.3s ease;
            }

            .product-card:hover {
                background-color: #2d2d2d;
            }

            .product-image {
                width: 80px;
                height: 80px;
                border-radius: 6px;
                overflow: hidden;
            }

            .product-image img {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }

            .product-details {
                flex: 1;
                position: relative;
            }

            .price {
                position: absolute;
                top: 0;
                right: 0;
                background-color: #F8B83C;
                color: #1E1E1E;
                padding: 5px 10px;
                border-radius: 4px;
                font-family: 'Century Gothic', sans-serif;
                font-weight: bold;
                font-size: 14px;
            }

            .product-name {
                color: #AEB2B7;
                font-family: 'Century Gothic', sans-serif;
                font-size: 16px;
                margin-top: 5px;
                margin-bottom: 10px;
            }

            .category {
                color: #666;
                font-family: 'Century Gothic', sans-serif;
                font-size: 14px;
            }

            /* Add hover effects */
            .dashboard-table {
                transition: transform 0.3s ease, box-shadow 0.3s ease;
            }

            .dashboard-table:hover {
                transform: translateY(-5px);
                box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2);
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
                background-color: #363636;
                padding-left: 20px;
                transition: all 0.3s ease;
            }

            #salesChart {
                height: 300px;
                width: 100%;
                margin-top: 20px;
            }

            .sales-alert {
                background-color: #27AE60;
                color: white;
                padding: 10px 15px;
                border-radius: 5px;
                margin: 10px 0;
                font-family: 'Century Gothic', sans-serif;
                display: flex;
                align-items: center;
                gap: 10px;
            }

            .sales-alert i {
                color: white;
            }

            .table-container {
                display: flex;
                flex-direction: column;
                gap: 15px;
                padding: 0;
            }

            .table-row {
                display: flex;
                gap: 15px;
                width: 100%;
            }

            .dashboard-table {
                flex: 1;
                background-color: #1E1E1E;
                border-radius: 8px;
                padding: 20px;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            }

            #salesChart {
                height: 300px;
                width: 100%;
                margin-top: 20px;
            }

            .sales-alert {
                background-color: #27AE60;
                color: white;
                padding: 10px 15px;
                border-radius: 5px;
                margin: 10px 0;
                font-family: 'Century Gothic', sans-serif;
                display: flex;
                align-items: center;
                gap: 10px;
            }

            .items-sold {
                font-size: 14px;
                color: #AEB2B7;
                margin-left: 5px;
            }

            .welcome-message {
                position: fixed;
                top: 80px;
                left: 269px;
                background-color: #27AE60;
                color: white;
                padding: 10px 20px;
                border-radius: 5px;
                box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
                font-family: 'Century Gothic', sans-serif;
                z-index: 1000;
                animation: slideIn 0.5s ease-out;
            }

            @keyframes slideIn {
                from {
                    transform: translateX(-20px);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
        </style>

        <!-- Scripts -->
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Show welcome message
                const welcomeMessage = document.getElementById('welcomeMessage');
                welcomeMessage.style.display = 'block';

                // Hide welcome message after 5 seconds
                setTimeout(() => {
                    welcomeMessage.style.display = 'none';
                }, 5000);

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

                // Time update function
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
            });
        </script>

        <!-- Add before the closing body tag -->
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Get the sales data from PHP
                const currentYearData = <?php echo $currentYearDataJSON; ?>;
                const lastYearData = <?php echo $lastYearDataJSON; ?>;
                const hasLastYearData = <?php echo json_encode($hasLastYearData); ?>;
                
                // Calculate totals
                const currentYearTotal = currentYearData.reduce((a, b) => a + b, 0);
                const lastYearTotal = lastYearData.reduce((a, b) => a + b, 0);
                
                // Show sales alert
                const alertDiv = document.getElementById('salesAlert');
                if (hasLastYearData && currentYearTotal > lastYearTotal) {
                    const increase = ((currentYearTotal - lastYearTotal) / lastYearTotal * 100).toFixed(1);
                    alertDiv.style.display = 'block';
                    alertDiv.innerHTML = `<i class="fas fa-arrow-up"></i> Sales have increased by ${increase}% compared to last year!`;
                } else if (!hasLastYearData) {
                    alertDiv.style.display = 'block';
                    alertDiv.style.backgroundColor = '#2C3E50';
                    alertDiv.innerHTML = `<i class="fas fa-info-circle"></i> Last year's sales data will be available starting next year.`;
                }

                // Create the chart
                const ctx = document.getElementById('salesChart');
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                        datasets: [{
                            label: 'Current Year Sales',
                            data: currentYearData,
                            borderColor: '#F8B83C',
                            backgroundColor: 'rgba(248, 184, 60, 0.1)',
                            tension: 0.4,
                            fill: true
                        }, {
                            label: 'Last Year Sales',
                            data: lastYearData,
                            borderColor: '#750605',
                            backgroundColor: 'rgba(117, 6, 5, 0.1)',
                            tension: 0.4,
                            fill: true,
                            hidden: !hasLastYearData // Hide last year's data if none exists
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: {
                                position: 'top',
                                labels: {
                                    color: '#AEB2B7',
                                    font: {
                                        family: "'Century Gothic', sans-serif"
                                    }
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        let label = context.dataset.label || '';
                                        if (label) {
                                            label += ': ';
                                        }
                                        if (context.parsed.y !== null) {
                                            label += '₱' + context.parsed.y.toFixed(2);
                                        }
                                        return label;
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: {
                                    color: 'rgba(174, 178, 183, 0.1)'
                                },
                                ticks: {
                                    color: '#AEB2B7',
                                    font: {
                                        family: "'Century Gothic', sans-serif"
                                    },
                                    callback: function(value) {
                                        return '₱' + value.toFixed(2);
                                    }
                                }
                            },
                            x: {
                                grid: {
                                    color: 'rgba(174, 178, 183, 0.1)'
                                },
                                ticks: {
                                    color: '#AEB2B7',
                                    font: {
                                        family: "'Century Gothic', sans-serif"
                                    }
                                }
                            }
                        }
                    }
                });

                // Monthly Sales Chart
                const currentMonthData = <?php echo $currentMonthDataJSON; ?>;
                const previousMonthData = <?php echo $previousMonthDataJSON; ?>;
                const hasPreviousMonthData = <?php echo json_encode($hasPreviousMonthData); ?>;
                
                // Calculate totals for monthly comparison
                const currentMonthTotal = currentMonthData.reduce((a, b) => a + b, 0);
                const previousMonthTotal = previousMonthData.reduce((a, b) => a + b, 0);
                
                // Show monthly sales alert
                const monthlyAlertDiv = document.getElementById('monthlySalesAlert');
                if (hasPreviousMonthData && currentMonthTotal > previousMonthTotal) {
                    const increase = ((currentMonthTotal - previousMonthTotal) / previousMonthTotal * 100).toFixed(1);
                    monthlyAlertDiv.style.display = 'block';
                    monthlyAlertDiv.innerHTML = `<i class="fas fa-arrow-up"></i> Sales have increased by ${increase}% compared to last month!`;
                } else if (!hasPreviousMonthData) {
                    monthlyAlertDiv.style.display = 'block';
                    monthlyAlertDiv.style.backgroundColor = '#2C3E50';
                    monthlyAlertDiv.innerHTML = `<i class="fas fa-info-circle"></i> Previous month's sales data will be available next month.`;
                }

                // Generate labels for days of the month
                const daysInMonth = currentMonthData.length;
                const dayLabels = Array.from({length: daysInMonth}, (_, i) => i + 1);

                // Create the monthly sales chart
                const monthlyCtx = document.getElementById('monthlySalesChart');
                new Chart(monthlyCtx, {
                    type: 'line',
                    data: {
                        labels: dayLabels,
                        datasets: [{
                            label: 'Current Month Sales',
                            data: currentMonthData,
                            borderColor: '#F8B83C',
                            backgroundColor: 'rgba(248, 184, 60, 0.1)',
                            tension: 0.4,
                            fill: true
                        }, {
                            label: 'Previous Month Sales',
                            data: previousMonthData,
                            borderColor: '#750605',
                            backgroundColor: 'rgba(117, 6, 5, 0.1)',
                            tension: 0.4,
                            fill: true,
                            hidden: !hasPreviousMonthData
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: {
                                position: 'top',
                                labels: {
                                    color: '#AEB2B7',
                                    font: {
                                        family: "'Century Gothic', sans-serif"
                                    }
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        let label = context.dataset.label || '';
                                        if (label) {
                                            label += ': ';
                                        }
                                        if (context.parsed.y !== null) {
                                            label += '₱' + context.parsed.y.toFixed(2);
                                        }
                                        return label;
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: {
                                    color: 'rgba(174, 178, 183, 0.1)'
                                },
                                ticks: {
                                    color: '#AEB2B7',
                                    font: {
                                        family: "'Century Gothic', sans-serif"
                                    },
                                    callback: function(value) {
                                        return '₱' + value.toFixed(2);
                                    }
                                }
                            },
                            x: {
                                grid: {
                                    color: 'rgba(174, 178, 183, 0.1)'
                                },
                                ticks: {
                                    color: '#AEB2B7',
                                    font: {
                                        family: "'Century Gothic', sans-serif"
                                    }
                                }
                            }
                        }
                    }
                });
            });
        </script>
    </body>
</html>