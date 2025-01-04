<?php
$host = 'sql107.infinityfree.com';
$dbname = 'if0_37977254_1';
$username = 'if0_37977254';
$password = 'sch22304';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>