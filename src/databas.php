<?php 
$host= "sql307.infinityfree.com"; 
$user = "if0_41069409"; 
$pass = "H4lxKbPj1O5"; 
$db = "f0_41069409_databas";

try {
    $pdo = new PDO("mysql:host=$host; dbname=$db; charset="", $user, $pass,
                   [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);
} catch (PDOException $e) {die("Database connection unsuccessful: " . $e->getMessage());
}
