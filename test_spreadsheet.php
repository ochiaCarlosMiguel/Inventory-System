<?php
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

try {
    // Enable error reporting for debugging
    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    // Attempt to load the Excel file
    $inputFileName = 'path/to/your/excel/file.xlsx'; // Replace with your actual file path
    $spreadsheet = IOFactory::load($inputFileName);
    
    // Get the first worksheet
    $worksheet = $spreadsheet->getActiveSheet();
    
    // Debug output
    echo "File loaded successfully!\n";
    echo "Worksheet name: " . $worksheet->getTitle() . "\n";
    echo "Highest row: " . $worksheet->getHighestRow() . "\n";
    echo "Highest column: " . $worksheet->getHighestColumn() . "\n";
    
    // Read some data
    $value = $worksheet->getCell('A1')->getValue();
    echo "Value in A1: " . $value . "\n";
    
} catch (\PhpOffice\PhpSpreadsheet\Reader\Exception $e) {
    echo "Error loading file: " . $e->getMessage();
} catch (\Exception $e) {
    echo "General error: " . $e->getMessage();
} 