<?php

$hostDB = getenv('DB_HOST') ?: '127.0.0.1';
$portDB = getenv('DB_PORT') ?: '3306';
$nameDB = getenv('DB_NAME') ?: 'escuela_db';
$userDB = getenv('DB_USER') ?: 'escuela_user';
$passDB = getenv('DB_PASS') ?: '123456';

try {
    $dsn = "mysql:host=$hostDB;port=$portDB;dbname=$nameDB;charset=utf8";
    $pdo = new PDO($dsn, $userDB, $passDB);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

?>
