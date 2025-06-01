<?php
// Start the session at the very beginning of the file
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
error_reporting(E_ALL);
ini_set('display_errors', 1);
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
            margin-top: 60px;
            padding: 30px 40px;
            position: relative;
        }

        .content-card {
            background-color: rgba(30, 30, 30, 0.7);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .content-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.3);
            background-color: rgba(30, 30, 30, 0.9);
        }

        .table-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }

        .table-header i {
            color: #F8B83C;
            font-size: 24px;
        }

        .table-header h2 {
            color: #AEB2B7;
            font-family: 'Century Gothic', sans-serif;
            font-size: 20px;
            font-weight: bold;
        }

        .header-actions {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 20px;
        }

        .add-btn {
            background-color: #750605;
            color: #F8B83C;
            border: none;
            border-radius: 5px;
            padding: 10px 20px;
            font-family: 'Century Gothic', sans-serif;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .add-btn:hover {
            background-color: #8f0806;
            transform: translateY(-2px);
        }

        .header-line {
            border: none;
            border-bottom: 1px solid #333;
            margin: 10px 0 20px 0;
        }

        /* Table Styles */
        .table-container {
            overflow-x: auto;
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        th {
            color: #F8B83C;
            font-weight: bold;
            font-size: 14px;
            padding: 15px 12px;
            text-align: left;
            font-family: 'Century Gothic', sans-serif;
            background-color: rgba(30, 30, 30, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-top: none;
            border-right: none;
        }

        th:last-child {
            border-right: none;
        }

        td {
            padding: 15px 12px;
            color: #AEB2B7;
            font-family: 'Century Gothic', sans-serif;
            font-size: 14px;
            border: 1px solid #333;
            border-right: none;
            border-bottom: none;
        }

        /* Add border-right to last column */
        th:last-child, td:last-child {
            border-right: none;
        }

        /* Add border-bottom to last row */
        tr:last-child td {
            border-bottom: none;
        }

        tr:hover td {
            background-color: rgba(45, 45, 45, 0.6);
        }

        /* First column styles */
        th:first-child, td:first-child {
            border-left: none;
        }

        /* Status cell padding adjustment for badges */
        td .status-active,
        td .status-inactive {
            display: inline-block;
            margin: -3px 0;
        }

        /* Action column alignment */
        .actions {
            display: flex;
            gap: 10px;
            justify-content: center;
        }

        .action-btn {
            background: none;
            border: none;
            color: #AEB2B7;
            cursor: pointer;
            padding: 8px;
            border-radius: 4px;
            transition: all 0.2s ease;
        }

        .action-btn.edit:hover {
            color: #2196F3;
            background-color: rgba(33, 150, 243, 0.1);
        }

        .action-btn.delete:hover {
            color: #F44336;
            background-color: rgba(244, 67, 54, 0.1);
        }

        /* Responsive Design */
        @media screen and (max-width: 1400px) {
            .main-content {
                padding: 20px;
            }
        }

        /* Add these styles to your existing CSS */
        .product-image {
            transition: transform 0.3s ease;
            cursor: pointer;
        }

        .product-image:hover {
            transform: scale(1.2);
        }

        td {
            vertical-align: middle;
        }

        /* Price and Total columns alignment */
        td:nth-child(6),
        td:nth-child(8) {
            text-align: right;
        }

        /* Quantity column alignment */
        td:nth-child(7) {
            text-align: center;
        }

        .date-filter {
            margin: 20px 0;
            padding: 0 20px;
        }

        .date-inputs {
            display: flex;
            gap: 20px;
            align-items: flex-end;
        }

        .date-field {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .date-field label {
            color: #AEB2B7;
            font-family: 'Century Gothic', sans-serif;
            font-size: 14px;
        }

        .date-field input {
            padding: 8px;
            border: 1px solid #4a4848;
            background-color: #2d2d2d;
            color: #AEB2B7;
            border-radius: 4px;
            font-family: 'Century Gothic', sans-serif;
        }

        .filter-btn {
            background-color: #750605;
            color: #F8B83C;
            border: none;
            padding: 8px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-family: 'Century Gothic', sans-serif;
            transition: background-color 0.3s;
            height: 35px;
        }

        .filter-btn:hover {
            background-color: #8f0806;
        }

        .filter-buttons {
            display: flex;
            gap: 10px;
        }

        .reset-btn {
            background-color: #4a4848;
            color: #AEB2B7;
            border: none;
            padding: 8px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-family: 'Century Gothic', sans-serif;
            transition: all 0.3s ease;
            height: 35px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .reset-btn:hover {
            background-color: #5a5858;
            color: #F8B83C;
        }

        .reset-btn i {
            font-size: 12px;
        }

        /* Date input and calendar icon styles */
        .date-field input[type="date"] {
            padding: 8px 30px 8px 8px;  /* Add right padding for the calendar icon */
            border: 1px solid #4a4848;
            background-color: #2d2d2d;
            color: #AEB2B7;
            border-radius: 4px;
            font-family: 'Century Gothic', sans-serif;
            position: relative;
            cursor: pointer;
        }

        /* Style the calendar icon button */
        .date-field input[type="date"]::-webkit-calendar-picker-indicator {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%23F8B83C' class='bi bi-calendar' viewBox='0 0 16 16'%3E%3Cpath d='M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5zM1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4H1z'/%3E%3C/svg%3E");
            cursor: pointer;
            opacity: 0.8;
            width: 20px;
            height: 20px;
            padding: 4px;
            border-radius: 4px;
            transition: all 0.3s ease;
        }

        .date-field input[type="date"]::-webkit-calendar-picker-indicator:hover {
            opacity: 1;
            background-color: rgba(248, 184, 60, 0.1);
        }

        /* For Firefox */
        .date-field input[type="date"] {
            -moz-appearance: textfield;
            background-color: #2d2d2d;
            color: #AEB2B7;
            padding: 8px;
        }

        /* Style the dropdown arrow for Firefox */
        .date-field input[type="date"]::-moz-calendar-picker-indicator {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%23F8B83C' class='bi bi-calendar' viewBox='0 0 16 16'%3E%3Cpath d='M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5zM1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4H1z'/%3E%3C/svg%3E");
            cursor: pointer;
            opacity: 0.8;
            width: 20px;
            height: 20px;
            padding: 4px;
        }

        /* For Edge */
        .date-field input[type="date"]::-ms-clear,
        .date-field input[type="date"]::-ms-reveal {
            display: none;
        }

        /* Add this CSS in the <style> section */
        .alert-message {
            position: fixed;
            top: 20px;
            left: 270px;
            padding: 15px 20px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 300px;
            max-width: 450px;
            z-index: 9999;
            font-family: 'Century Gothic', sans-serif;
            transform: translateX(-100%);
            transition: transform 0.5s ease-in-out;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .alert-message.show {
            transform: translateX(0);
        }

        .alert-message.success {
            background-color: rgba(25, 135, 84, 0.95);
            color: #fff;
            border-left: 4px solid #198754;
        }

        .alert-message.error {
            background-color: rgba(220, 53, 69, 0.95);
            color: #fff;
            border-left: 4px solid #dc3545;
        }

        .alert-message i {
            font-size: 20px;
        }

        .alert-message .message-content {
            flex-grow: 1;
            font-size: 14px;
        }

        .alert-message .close-btn {
            background: none;
            border: none;
            color: #fff;
            cursor: pointer;
            padding: 0;
            font-size: 18px;
            opacity: 0.8;
            transition: opacity 0.2s;
        }

        .alert-message .close-btn:hover {
            opacity: 1;
        }

        /* Add this CSS to your existing styles */
        .modal-backdrop {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }

        .confirm-modal {
            background-color: #1E1E1E;
            border-radius: 8px;
            padding: 25px;
            max-width: 400px;
            width: 90%;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        }

        .confirm-modal .modal-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }

        .confirm-modal .modal-header i {
            color: #dc3545;
            font-size: 24px;
        }

        .confirm-modal .modal-header h3 {
            color: #F8B83C;
            font-family: 'Century Gothic', sans-serif;
            font-size: 18px;
            margin: 0;
        }

        .confirm-modal .modal-body {
            color: #AEB2B7;
            font-family: 'Century Gothic', sans-serif;
            font-size: 14px;
            line-height: 1.5;
            margin-bottom: 25px;
        }

        .confirm-modal .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .modal-btn {
            padding: 8px 20px;
            border-radius: 5px;
            font-family: 'Century Gothic', sans-serif;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
        }

        .modal-btn.cancel {
            background-color: #4a4848;
            color: #AEB2B7;
        }

        .modal-btn.cancel:hover {
            background-color: #5a5858;
        }

        .modal-btn.delete {
            background-color: #dc3545;
            color: white;
        }

        .modal-btn.delete:hover {
            background-color: #bb2d3b;
        }

        /* Add these new styles for the image modal */
        .image-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.9);
            z-index: 10000;
            justify-content: center;
            align-items: center;
        }

        .modal-image {
            max-width: 90%;
            max-height: 90vh;
            object-fit: contain;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            transform: scale(0.9);
            transition: transform 0.3s ease;
        }

        .image-modal.active .modal-image {
            transform: scale(1);
        }

        .close-image-modal {
            position: absolute;
            top: 20px;
            right: 20px;
            color: #fff;
            font-size: 24px;
            cursor: pointer;
            background: rgba(0, 0, 0, 0.5);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            transition: all 0.3s ease;
        }

        .close-image-modal:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: rotate(90deg);
        }
    </style>

</head>
<body>
    <!-- Add this div for alerts -->
    <div id="alertContainer"></div>

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
            <!-- Sales Dropdown -->
                    <li class="active"><a href="manageSales.php"><i class="fas fa-tasks"></i>Manage Sales</a></li>
                    <li><a href="addSales.php"><i class="fas fa-cart-plus"></i>Add Sales</a></li>


                    <li><a href="dailySales.php"><i class="fas fa-calendar-day"></i>Daily Sales</a></li>
                    <!-- FAQ Link (New) -->
                    <li>
                        <a href="faq.php">
                            <i class="fas fa-question-circle"></i>FAQ
                        </a>
                    </li>

                                    <!-- System Logs Dropdown -->
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
            <div class="content-card dashboard-table">
                <div class="table-header">
                    <i class="fas fa-shopping-cart"></i>
                    <h2>ALL SALES</h2>
                </div>
                <!-- Add date filter form -->
                <div class="date-filter">
                    <form id="dateFilterForm" class="date-inputs">
                        <div class="date-field">
                            <label for="dateFrom">From:</label>
                            <input type="date" id="dateFrom" name="dateFrom">
                        </div>
                        <div class="date-field">
                            <label for="dateTo">To:</label>
                            <input type="date" id="dateTo" name="dateTo">
                        </div>
                        <div class="filter-buttons">
                            <button type="submit" class="filter-btn">Filter Sales</button>
                            <button type="button" id="resetFilter" class="reset-btn">
                                <i class="fas fa-undo"></i> Reset
                            </button>
                        </div>
                    </form>
                </div>
                <div class="header-actions">
                    <a href="addSales.php" class="add-btn">
                        <i class="fas fa-plus"></i>
                        ADD SALE
                    </a>
                </div>
                <hr class="header-line">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Customer ID</th>
                                <th>Product Name</th>
                                <th>Photo</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Total</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            try {
                                // First, create a temporary table with row numbers
                                $query = "SET @row_number = 0;";  // Initialize row counter
                                $stmt = $pdo->prepare($query);
                                $stmt->execute();

                                // Modify the main query to include row numbers and proper ordering
                                $query = "
                                    SELECT 
                                        (@row_number:=@row_number + 1) AS display_id,
                                        s.id, 
                                        s.customer_id, 
                                        s.total_amount, 
                                        s.sale_date,
                                        si.id as sale_item_id, 
                                        si.quantity, 
                                        si.price, 
                                        si.total as item_total,
                                        p.product_title, 
                                        p.image_path,
                                        c.category_name
                                    FROM sales s
                                    JOIN sale_items si ON s.id = si.sale_id
                                    JOIN products p ON si.product_id = p.id
                                    LEFT JOIN categories c ON p.category_id = c.id
                                    WHERE 1=1";

                                $params = [];

                                // Add date filtering if dates are set
                                if (isset($_GET['dateFrom']) && !empty($_GET['dateFrom'])) {
                                    $query .= " AND DATE(s.sale_date) >= ?";
                                    $params[] = $_GET['dateFrom'];
                                }
                                if (isset($_GET['dateTo']) && !empty($_GET['dateTo'])) {
                                    $query .= " AND DATE(s.sale_date) <= ?";
                                    $params[] = $_GET['dateTo'];
                                }

                                // Order by sale_id in descending order to show newest sales first
                                $query .= " ORDER BY s.id DESC, s.sale_date DESC";

                                $stmt = $pdo->prepare($query);
                                $stmt->execute($params);

                                while ($row = $stmt->fetch()) {
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($row['display_id']) . "</td>";  // Use display_id instead of id
                                    echo "<td>" . htmlspecialchars($row['customer_id']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['product_title']) . "</td>";
                                    echo "<td><img src='../upload/" . htmlspecialchars($row['image_path']) . 
                                         "' alt='Product' class='product-image' style='width: 50px; height: 50px; border-radius: 5px; object-fit: cover;'></td>";
                                    echo "<td>" . htmlspecialchars($row['category_name']) . "</td>";
                                    echo "<td>₱" . number_format($row['price'], 2) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['quantity']) . "</td>";
                                    echo "<td>₱" . number_format($row['item_total'], 2) . "</td>";
                                    echo "<td>" . date('Y-m-d H:i', strtotime($row['sale_date'])) . "</td>";
                                    echo "<td class='actions'>
                                            <a href='editSales.php?id=" . $row['id'] . "&item_id=" . $row['sale_item_id'] . "' class='action-btn edit'>
                                                <i class='fas fa-edit'></i>
                                            </a>
                                            <button class='action-btn delete' data-id='" . $row['id'] . "' data-item-id='" . $row['sale_item_id'] . "'>
                                                <i class='fas fa-trash'></i>
                                            </button>
                                          </td>";
                                    echo "</tr>";
                                }
                            } catch(PDOException $e) {
                                echo "Error: " . $e->getMessage();
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    
            <!-- Scripts -->
    <script>
        function showAlert(message, type = 'success') {
            const alertContainer = document.getElementById('alertContainer');
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert-message ${type}`;
            
            const icon = type === 'success' ? 'check-circle' : 'exclamation-circle';
            
            alertDiv.innerHTML = `
                <i class="fas fa-${icon}"></i>
                <div class="message-content">${message}</div>
                <button class="close-btn" onclick="this.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            `;
            
            alertContainer.appendChild(alertDiv);
            
            // Trigger reflow to enable animation
            alertDiv.offsetHeight;
            
            // Show the alert
            setTimeout(() => alertDiv.classList.add('show'), 10);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                alertDiv.classList.remove('show');
                setTimeout(() => alertDiv.remove(), 300);
            }, 5000);
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Show welcome message if user is logged in
            <?php if (isset($_SESSION['user_id'])): ?>
                showAlert('Welcome, <?php echo htmlspecialchars($userName); ?>!', 'success');
            <?php endif; ?>

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

            // Add Delete and Edit functionality
            const deleteButtons = document.querySelectorAll('.action-btn.delete');
            const editButtons = document.querySelectorAll('.action-btn.edit');

            // Function to update CTM IDs
            function updateCTMIDs() {
                const rows = document.querySelectorAll('tbody tr');
                rows.forEach((row, index) => {
                    const ctmIdCell = row.cells[1]; // CTM ID is in the second column
                    ctmIdCell.textContent = `CTM_ID${index}`;
                });
            }

            // Modify the delete button handler
            deleteButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const saleId = this.dataset.id;
                    const saleItemId = this.dataset.itemId;
                    const row = this.closest('tr');
                    
                    // Get the product details from the row for logging
                    const productName = row.cells[2].textContent; // Product Name column
                    const quantity = row.cells[6].textContent; // Quantity column
                    const price = row.cells[5].textContent.replace('₱', '').trim(); // Price column
                    const total = row.cells[7].textContent.replace('₱', '').trim(); // Total column
                    
                    showDeleteConfirmation(async () => {
                        try {
                            const response = await fetch('../admin/deleteSale.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                },
                                body: JSON.stringify({ 
                                    saleId: saleId,
                                    saleItemId: saleItemId,
                                    logDetails: {
                                        'Sale ID': saleId,
                                        'Product': productName,
                                        'Details': {
                                            'quantity': quantity,
                                            'price': price,
                                            'total': total
                                        }
                                    }
                                })
                            });

                            const result = await response.json();
                            if (result.success) {
                                row.remove();
                                showAlert('Sale item deleted successfully');
                            } else {
                                showAlert(result.message || 'Error deleting sale item', 'error');
                            }
                        } catch (error) {
                            console.error('Error:', error);
                            showAlert('Error deleting sale item: ' + error.message, 'error');
                        }
                    });
                });
            });

            // Edit button handler
            editButtons.forEach(button => {
                button.addEventListener('click', function() {
                    window.location.href = 'editSales.php';
                });
            });

            // Add date filter form handler
            const dateFilterForm = document.getElementById('dateFilterForm');
            dateFilterForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const dateFrom = document.getElementById('dateFrom').value;
                const dateTo = document.getElementById('dateTo').value;
                
                // Redirect to the same page with date parameters
                const url = new URL(window.location.href);
                url.searchParams.set('dateFrom', dateFrom);
                url.searchParams.set('dateTo', dateTo);
                window.location.href = url.toString();
            });

            // Set form values from URL parameters if they exist
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('dateFrom')) {
                document.getElementById('dateFrom').value = urlParams.get('dateFrom');
            }
            if (urlParams.has('dateTo')) {
                document.getElementById('dateTo').value = urlParams.get('dateTo');
            }

            // Add reset button handler
            const resetButton = document.getElementById('resetFilter');
            resetButton.addEventListener('click', function() {
                // Clear the date inputs
                document.getElementById('dateFrom').value = '';
                document.getElementById('dateTo').value = '';
                
                // Remove date parameters from URL and reload
                const url = new URL(window.location.href);
                url.searchParams.delete('dateFrom');
                url.searchParams.delete('dateTo');
                window.location.href = url.toString();
            });

            // Image Modal Functionality
            const imageModal = document.getElementById('imageModal');
            const modalImage = document.getElementById('modalImage');
            const closeImageModal = document.querySelector('.close-image-modal');
            const productImages = document.querySelectorAll('.product-image');

            productImages.forEach(img => {
                img.addEventListener('click', function() {
                    modalImage.src = this.src;
                    imageModal.style.display = 'flex';
                    setTimeout(() => {
                        imageModal.classList.add('active');
                    }, 10);
                });
            });

            function closeModal() {
                imageModal.classList.remove('active');
                setTimeout(() => {
                    imageModal.style.display = 'none';
                }, 300);
            }

            closeImageModal.addEventListener('click', closeModal);

            imageModal.addEventListener('click', function(e) {
                if (e.target === imageModal) {
                    closeModal();
                }
            });

            // Close image modal on ESC key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && imageModal.style.display === 'flex') {
                    closeModal();
                }
            });
        });

        function showDeleteConfirmation(callback) {
            const modal = document.getElementById('deleteModal');
            const cancelBtn = document.getElementById('cancelDelete');
            const confirmBtn = document.getElementById('confirmDelete');
            
            modal.style.display = 'flex';
            
            const handleCancel = () => {
                modal.style.display = 'none';
                cancelBtn.removeEventListener('click', handleCancel);
                confirmBtn.removeEventListener('click', handleConfirm);
            };
            
            const handleConfirm = () => {
                modal.style.display = 'none';
                cancelBtn.removeEventListener('click', handleCancel);
                confirmBtn.removeEventListener('click', handleConfirm);
                callback();
            };
            
            cancelBtn.addEventListener('click', handleCancel);
            confirmBtn.addEventListener('click', handleConfirm);
            
            // Close on backdrop click
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    handleCancel();
                }
            });
            
            // Close on ESC key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && modal.style.display === 'flex') {
                    handleCancel();
                }
            });
        }
    </script>

    <!-- Add this HTML right after your alertContainer -->
    <div class="modal-backdrop" id="deleteModal">
        <div class="confirm-modal">
            <div class="modal-header">
                <i class="fas fa-exclamation-triangle"></i>
                <h3>Confirm Deletion</h3>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this sale item? This action cannot be undone.
            </div>
            <div class="modal-footer">
                <button class="modal-btn cancel" id="cancelDelete">Cancel</button>
                <button class="modal-btn delete" id="confirmDelete">Delete</button>
            </div>
        </div>
    </div>

    <!-- Add this HTML right before the closing body tag -->
    <div class="image-modal" id="imageModal">
        <div class="close-image-modal">
            <i class="fas fa-times"></i>
        </div>
        <img src="" alt="Product Image" class="modal-image" id="modalImage">
    </div>
</body>
</html>