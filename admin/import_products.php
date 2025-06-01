<?php
// Prevent any unwanted output
error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Content-Type: application/json');

require '../vendor/autoload.php';
require '../connection/connections.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

try {
    if (isset($_FILES['excel_file'])) {
        $file = $_FILES['excel_file']['tmp_name'];
        $spreadsheet = IOFactory::load($file);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();
        
        // Skip header row
        array_shift($rows);
        
        $importCount = 0;
        foreach ($rows as $row) {
            if (empty(array_filter($row))) {
                continue;
            }

            if (empty($row[0])) {
                continue;
            }

            $data = [
                'product_title' => $row[0],
                'category_id' => $row[1] ?? null,
                'quantity' => $row[2] ?? 0,
                'buying_price' => $row[3] ?? 0,
                'selling_price' => $row[4] ?? 0,
                'image_path' => $row[5] ?? 'image.jpg',
                'created_at' => date('Y-m-d H:i:s')
            ];

            error_log("Importing product: " . $data['product_title'] . " with image: " . $data['image_path']);

            $sql = "INSERT INTO products (product_title, category_id, quantity, buying_price, selling_price, image_path, created_at) 
                    VALUES (:product_title, :category_id, :quantity, :buying_price, :selling_price, :image_path, :created_at)";
            
            $stmt = $pdo->prepare($sql);
            if ($stmt->execute($data)) {
                $importCount++;
            }
        }

        // Return success response with count
        echo json_encode([
            'success' => true,
            'message' => "Successfully imported $importCount products",
            'count' => $importCount
        ]);
        exit;
    } else {
        throw new Exception('No file uploaded');
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => "Error importing products: " . $e->getMessage()
    ]);
    exit;
} 