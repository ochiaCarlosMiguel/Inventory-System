<?php
// Set timezone to Philippines (Asia/Manila)
date_default_timezone_set('Asia/Manila');

session_start();
require_once '../connection/connections.php';

// Set MySQL timezone to match PHP timezone
try {
    $pdo->exec("SET time_zone = '+08:00'");  // Philippines is UTC+8
} catch (PDOException $e) {
    error_log("Error setting MySQL timezone: " . $e->getMessage());
}

// Debug logging for session variables
error_log("Session variables at logout:");
error_log("user_id: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'not set'));
error_log("username: " . (isset($_SESSION['username']) ? $_SESSION['username'] : 'not set'));
error_log("name: " . (isset($_SESSION['name']) ? $_SESSION['name'] : 'not set'));
error_log("role: " . (isset($_SESSION['role']) ? $_SESSION['role'] : 'not set'));

if (isset($_SESSION['user_id'])) {
    try {
        error_log("Attempting to log logout for user: " . $_SESSION['username']);
        
        // Debug: Print the SQL query with values
        $query = "INSERT INTO login_history (user_id, username, name, user_role, action, timestamp) 
                 VALUES (?, ?, ?, ?, 'logout', CONVERT_TZ(NOW(), @@session.time_zone, '+08:00'))";
        error_log("SQL Query: " . $query);
        error_log("Values: " . json_encode([
            $_SESSION['user_id'],
            $_SESSION['username'],
            $_SESSION['name'],
            $_SESSION['role']
        ]));
        
        $stmt = $pdo->prepare($query);
        $result = $stmt->execute([
            $_SESSION['user_id'],
            $_SESSION['username'],
            $_SESSION['name'],
            $_SESSION['role']
        ]);
        
        if ($result) {
            error_log("Successfully logged logout action");
        } else {
            error_log("Failed to insert logout record. PDO Error Info: " . json_encode($stmt->errorInfo()));
        }
        
    } catch (PDOException $e) {
        error_log("Error logging logout: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
    } catch (Exception $e) {
        error_log("Unexpected error during logout: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
    }
} else {
    error_log("No user session found during logout");
}

// Clear all session variables
$_SESSION = array();

// Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}

// Destroy the session
session_destroy();

// Redirect to login page
header('Location: index.php');
exit();
?> 