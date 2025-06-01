<?php
$host = "localhost";
$dbname = "inventory_system";
$username = "root";  // Change this to your database username
$password = "";      // Change this to your database password

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    // Set the PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Set default fetch mode to associative array
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    // Set MySQL timezone to match PHP timezone
    $pdo->exec("SET time_zone = '+08:00'");
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

