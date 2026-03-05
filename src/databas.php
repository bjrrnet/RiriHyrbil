<?php
// Unable to connect, host verkar vara felet, localhost funkade inte heller.
$host= "sql307.infinityfree.com"; 
$user = "if0_41069409"; 
$port = "3306";
$pass = "H4lxKbPj1O5"; 
$db = "if0_41069409_databas";

try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$db;charset=utf8",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
);
} catch (PDOException $e) {die("Database connection unsuccessful: " . $e->getMessage());
}
