<?php
session_start();
require_once '../connection/connections.php';
require_once '../vendor/autoload.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

try {
    // Get the current month's start and end dates
    $currentMonth = date('Y-m');
    $startDate = $currentMonth . '-01';
    $endDate = date('Y-m-t');

    // Fetch monthly sales data
    $query = "SELECT 
                p.product_title,
                p.buying_price,
                si.price as selling_price,
                si.quantity,
                si.total as total_amount,
                s.sale_date
            FROM sales s
            JOIN sale_items si ON s.id = si.sale_id
            JOIN products p ON si.product_id = p.id
            WHERE DATE(s.sale_date) BETWEEN ? AND ?
            ORDER BY s.sale_date ASC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$startDate, $endDate]);
    $monthlySales = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate totals
    $totalQuantity = 0;
    $totalSales = 0;
    $totalCostOfSales = 0;
    
    foreach ($monthlySales as $sale) {
        $totalQuantity += intval($sale['quantity']);
        $totalSales += floatval($sale['total_amount']);
        $totalCostOfSales += floatval($sale['buying_price']) * intval($sale['quantity']);
    }
    
    $businessTax = $totalSales * 0.03;
    $grossProfit = $totalSales - $businessTax;

    // Create PDF
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('ArtMore Admin');
    $pdf->SetTitle('Monthly Sales Report - ' . date('F Y'));

    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
    $pdf->SetMargins(15, 15, 15);
    $pdf->SetAutoPageBreak(TRUE, 15);

    $pdf->AddPage('L');

    // Header
    $pdf->SetFont('helvetica', 'B', 20);
    $pdf->Cell(0, 15, 'ArtMore Monthly Sales Report - ' . date('F Y'), 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 10, 'Generated on: ' . date('F d, Y h:i:s A'), 0, 1, 'C');
    $pdf->Ln(10);

    // Table Header
    $header = array('#', 'Product', 'Quantity', 'Total Amount', 'Date');
    
    $pdf->SetFillColor(117, 6, 5);
    $pdf->SetTextColor(248, 184, 60);
    $pdf->SetFont('helvetica', 'B', 10);

    $w = array(20, 100, 40, 40, 50);
    
    foreach($header as $i => $col) {
        $pdf->Cell($w[$i], 7, $col, 1, 0, 'C', true);
    }
    $pdf->Ln();

    // Table Data
    $pdf->SetFillColor(224, 235, 255);
    $pdf->SetTextColor(0);
    $pdf->SetFont('helvetica', '', 9);

    foreach($monthlySales as $i => $row) {
        $pdf->Cell($w[0], 6, $i + 1, 1);
        $pdf->Cell($w[1], 6, $row['product_title'], 1);
        $pdf->Cell($w[2], 6, $row['quantity'], 1, 0, 'C');
        $pdf->Cell($w[3], 6, 'PHP ' . number_format($row['total_amount'], 2), 1, 0, 'R');
        $pdf->Cell($w[4], 6, date('Y-m-d h:i A', strtotime($row['sale_date'])), 1);
        $pdf->Ln();
    }

    // Summary
    $pdf->Ln(10);
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(0, 7, 'Summary', 0, 1);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 7, 'Total Quantity: ' . number_format($totalQuantity), 0, 1);
    $pdf->Cell(0, 7, 'Total Sales: PHP ' . number_format($totalSales, 2), 0, 1);
    $pdf->Cell(0, 7, 'Cost of Sales: PHP ' . number_format($totalCostOfSales, 2), 0, 1);
    $pdf->Cell(0, 7, 'Business Tax (3%): PHP ' . number_format($businessTax, 2), 0, 1);
    $pdf->Cell(0, 7, 'Gross Profit: PHP ' . number_format($grossProfit, 2), 0, 1);

    $pdf->Output('Monthly_Sales_Report_' . date('F_Y') . '.pdf', 'D');

} catch(Exception $e) {
    error_log("PDF Generation Error: " . $e->getMessage());
    header("Location: monthlySales.php?error=export_failed");
    exit();
} 