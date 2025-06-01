<?php
require_once '../connection/connections.php';
session_start();

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['saleId']) || !isset($data['saleItemId'])) {
        throw new Exception('Missing required parameters');
    }

    $pdo->beginTransaction();

    // Get the sale item details first
    $stmt = $pdo->prepare("SELECT product_id, quantity FROM sale_items WHERE id = ? AND sale_id = ?");
    $stmt->execute([$data['saleItemId'], $data['saleId']]);
    $saleItem = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$saleItem) {
        throw new Exception('Sale item not found');
    }

    // Update the product quantity (add back the sold quantity)
    $stmt = $pdo->prepare("UPDATE products SET quantity = quantity + ? WHERE id = ?");
    $stmt->execute([$saleItem['quantity'], $saleItem['product_id']]);

    // Delete the specific sale item
    $stmt = $pdo->prepare("DELETE FROM sale_items WHERE id = ? AND sale_id = ?");
    $stmt->execute([$data['saleItemId'], $data['saleId']]);

    // Check if this was the last item in the sale
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM sale_items WHERE sale_id = ?");
    $stmt->execute([$data['saleId']]);
    $remainingItems = $stmt->fetchColumn();

    // If no items remain, delete the parent sale record
    if ($remainingItems == 0) {
        $stmt = $pdo->prepare("DELETE FROM sales WHERE id = ?");
        $stmt->execute([$data['saleId']]);
    } else {
        // Update the total amount in the sales table
        $stmt = $pdo->prepare("UPDATE sales SET total_amount = (
            SELECT SUM(total) FROM sale_items WHERE sale_id = ?
        ) WHERE id = ?");
        $stmt->execute([$data['saleId'], $data['saleId']]);
    }

    // Log the deletion in activity_logs
    if (isset($_SESSION['user_id'])) {
        $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, details, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([
            $_SESSION['user_id'],
            'Sale Deleted',
            json_encode($data['logDetails'])
        ]);
    }

    $pdo->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 