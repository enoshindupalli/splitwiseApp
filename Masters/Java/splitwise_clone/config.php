<?php
session_start();

$host = 'localhost';
$db = 'splitwise_clone';
$user = 'root';  // Your MySQL username
$pass = '';      // Your MySQL password

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
