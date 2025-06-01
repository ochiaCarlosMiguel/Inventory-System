<?php
session_start();
require_once '../connection/connections.php';

// Record the logout action before destroying the session
if (isset($_SESSION['user_id'])) {
    try {
        $stmt = $pdo->prepare("INSERT INTO login_history (user_id, username, name, user_role, action) 
                              VALUES (?, ?, ?, ?, 'logout')");
        $stmt->execute([
            $_SESSION['user_id'],
            $_SESSION['username'],
            $_SESSION['name'],
            $_SESSION['user_role']
        ]);
    } catch (PDOException $e) {
        error_log("Error recording logout: " . $e->getMessage());
    }
}

// Continue with your existing logout code
session_destroy();
header("Location: ../index.php");
exit();
?> 