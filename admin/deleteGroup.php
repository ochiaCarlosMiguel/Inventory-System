<?php
// Add this at the top of the file
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session and include database connection
session_start();
require_once '../connection/connections.php';

// Set header to return JSON response
header('Content-Type: application/json');

try {
    // Check if ID is provided
    if (!isset($_GET['id'])) {
        throw new Exception('Group ID is required');
    }

    $groupId = (int)$_GET['id'];

    // Begin transaction
    $pdo->beginTransaction();

    // First check if the group exists
    $stmt = $pdo->prepare("SELECT * FROM user_groups WHERE id = ?");
    $stmt->execute([$groupId]);
    $group = $stmt->fetch();

    if (!$group) {
        throw new Exception('Group not found');
    }

    // Delete the group
    $stmt = $pdo->prepare("DELETE FROM user_groups WHERE id = ?");
    $stmt->execute([$groupId]);

    // Log the deletion
    $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, details, created_at) 
                          VALUES (?, ?, ?, NOW())");
    $details = json_encode([
        'action' => 'delete',
        'group_id' => $groupId,
        'group_name' => $group['group_name'],
        'group_level' => $group['group_level'],
        'status' => $group['status']
    ]);
    $stmt->execute([
        $_SESSION['user_id'],
        'Delete Group',
        $details
    ]);

    // Commit transaction
    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Group deleted successfully'
    ]);

} catch (Exception $e) {
    // Rollback transaction if there was an error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Delete Group Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    echo json_encode([
        'success' => false,
        'message' => 'Error deleting group: ' . $e->getMessage(),
        'debug' => [
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ]);
}