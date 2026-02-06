<?php
require "../src/databas.php";

header("Content-type: application/json");

$action = $_GET['action'] ?? '';

if ($action === 'bilar') {
	$stmt = $pdo->query("SELECT * FROM bilar");
	$cars = $stmt->fetchAll(PDO::FETCH_ASSOC);	
	echo json_encode($cars);
	exit;
}

echo json_encode(["error" => "Unknown Action"]);
