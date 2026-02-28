<?php
session_start();

require "../src/databas.php";

header("Content-type: application/json");

$action = $_GET['action'] ?? '';

if ($action === 'bilar') {
	$stmt = $pdo->query("SELECT * FROM bilar");
	$cars = $stmt->fetchAll(PDO::FETCH_ASSOC);	
	echo json_encode($cars);
	exit;
}

//  -- DETTA BORDE NOG VARA EN SWITCH, https://www.php.net/manual/en/control-structures.switch.php

if ($action === 'login') {
    $data = json_decode(file_get_contents("php://input"), true);

    $username = $data['username'] ?? '';
    $password = $data['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id']; // Lagrar inloggning
        $_SESSION['username'] = $user['username'];

        echo json_encode(["success" => true]);
    }  else  {
        echo json_encode(["success" => false]);
    }
    exit;
}

if ($action === 'register') {
    $data = json_decode(file_get_contents("php://input"), true);
    
    $firstName = $data['first_name'];
    $lastName = $data['last_name'];
    $email = $data['email'];
    $password = password_hash($data['password'], PASSWORD_DEFAULT); 
    
    $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, password) VALUES (?, ?, ?, ?)");
    $result = $stmt->execute([$firstName, $lastName, $email, $password]);

    echo json_encode(["success" => $result]);
    exit;
}

if ($action === 'checkLogin') {
    echo json_encode([
        "loggedIn"=> isset($_SESSION['user_id']),
        "username" => $_SESSION['username'] ?? null
    ]);
    exit;
}

if ($action === 'logout') {
    session_destroy();
    echo json_encode(["success" => true]);
    exit;
}

echo json_encode(["error" => "Unknown Action"]);
