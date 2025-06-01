<?php
session_start();
require_once '../connection/connections.php';
require_once '../vendor/autoload.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

try {
    $today = date('Y-m-d');

    // Fetch daily sales data
    $stmt = $pdo->prepare("
        SELECT 
            si.id,
            p.product_title,
            si.quantity,
            si.total,
            (si.total - (p.buying_price * si.quantity)) as profit,
            s.sale_date
        FROM sales s
        JOIN sale_items si ON s.id = si.sale_id
        JOIN products p ON si.product_id = p.id
        WHERE DATE(s.sale_date) = ?
        ORDER BY s.sale_date DESC
    ");
    
    $stmt->execute([$today]);
    $dailySales = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate totals
    $totalQuantity = 0;
    $totalSales = 0;
    $totalProfit = 0;
    
    foreach ($dailySales as $sale) {
        $totalQuantity += $sale['quantity'];
        $totalSales += $sale['total'];
        $totalProfit += $sale['profit'];
    }
    
    $costOfSales = $totalSales - $totalProfit;
    $businessTax = $totalSales * 0.03;
    $grossProfit = $totalSales - $businessTax;

    // Create PDF
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('ArtMore Admin');
    $pdf->SetTitle('Daily Sales Report - ' . date('F d, Y'));

    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
    $pdf->SetMargins(15, 15, 15);
    $pdf->SetAutoPageBreak(TRUE, 15);

    $pdf->AddPage('L');

    // Header
    $pdf->SetFont('helvetica', 'B', 20);
    $pdf->Cell(0, 15, 'ArtMore Daily Sales Report - ' . date('F d, Y'), 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 10, 'Generated on: ' . date('F d, Y h:i:s A'), 0, 1, 'C');
    $pdf->Ln(10);

    // Table Header
    $header = array('#', 'Product', 'Quantity', 'Total Amount', 'Time');
    
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

    foreach($dailySales as $i => $sale) {
        $pdf->Cell($w[0], 6, $i + 1, 1);
        $pdf->Cell($w[1], 6, $sale['product_title'], 1);
        $pdf->Cell($w[2], 6, $sale['quantity'], 1, 0, 'C');
        $pdf->Cell($w[3], 6, 'PHP ' . number_format($sale['total'], 2), 1, 0, 'R');
        $pdf->Cell($w[4], 6, date('h:i A', strtotime($sale['sale_date'])), 1);
        $pdf->Ln();
    }

    // Summary
    $pdf->Ln(10);
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(0, 7, 'Summary', 0, 1);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 7, 'Total Quantity: ' . number_format($totalQuantity), 0, 1);
    $pdf->Cell(0, 7, 'Total Sales: PHP ' . number_format($totalSales, 2), 0, 1);
    $pdf->Cell(0, 7, 'Cost of Sales: PHP ' . number_format($costOfSales, 2), 0, 1);
    $pdf->Cell(0, 7, 'Business Tax (3%): PHP ' . number_format($businessTax, 2), 0, 1);
    $pdf->Cell(0, 7, 'Gross Profit: PHP ' . number_format($grossProfit, 2), 0, 1);

    $pdf->Output('Daily_Sales_Report_' . date('Y-m-d') . '.pdf', 'D');

} catch(Exception $e) {
    error_log("PDF Generation Error: " . $e->getMessage());
    header("Location: dailySales.php?error=export_failed");
    exit();
} 