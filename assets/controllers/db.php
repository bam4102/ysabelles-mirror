<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
 
$host = 'srv1322.hstgr.io';
$user = 'u171783535_ysabelles_db';
$password = 'zE~njtidu>U,uc9';
$dbname = 'u171783535_ysabelles';

// // // DB 2
// $host = 'srv1322.hstgr.io';
// $user = 'u171783535_ysabelles_db2';
// $password = 'qWE1234TyY';
// $dbname = 'u171783535_ysabelles_db2';

// local DB
// $host = 'localhost';
// $user = 'root';
// $password = '';
// $dbname = 'ysabells local';

// Set default timezone to UTC+8
date_default_timezone_set('Asia/Manila');

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Set MySQL session timezone to UTC+8
    $pdo->exec("SET time_zone = '+08:00'");
    
    // Test the connection with a simple query
    $testQuery = $pdo->query("SELECT 1");
    if ($testQuery) {
        error_log("Database connection established and test query successful");
    } else {
        error_log("Database connection established but test query failed");
    }
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("Connection failed: " . $e->getMessage());
}
?>

