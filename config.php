<?php
// Database configuration
$host = 'localhost';
$dbname = 'your_database_name'; // Replace with your database name
$username = 'your_database_user'; // Replace with your database username
$password = 'your_database_password'; // Replace with your database password

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>