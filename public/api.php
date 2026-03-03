<?php
session_start();

require "src/databas.php";

header("Content-type: application/json");

$action = $_GET['action'] ?? '';
$data   = json_decode(file_get_contents("php://input"), true);

switch ($action) {

    // GET /api.php?action=bilar
    case 'bilar' :
    	$stmt = $pdo->query("SELECT * FROM bilar");
    	$bilar = $stmt->fetchAll(PDO::FETCH_ASSOC);	
    	echo json_encode($bilar);
    break;

    case 'availableCars':
    //GET /api.php?action=availableCars&startYYYY-MM-DD&end=YYYY-MM-DD
    //ger alla bilar som är tillgängliga i givet spann
        $start = $_GET['start'] ?? null;
        $end = $_GET['end'] ?? null;

        if (!$start || !$end) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Missing date"]);
            break;
        }

        $stmt = $pdo->prepare("
            SELECT c.id, c.plate, c.mileage, c.price_category, t.name AS car_type, t.description AS car_type_description FROM cars c
            JOIN car_type t ON c.car_type = t.id WHERE c.status = 'available' AND c.id NOT IN (SELECT car_id FROM rentals WHERE start_date <= ? AND end_date >= ?)
        ");

        $stmt->execute([$end, $start]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        break;


    case 'login':
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
    break;


    case 'checkLogin':
        echo json_encode([
            "loggedIn"=> isset($_SESSION['user_id']),
            "username" => $_SESSION['username'] ?? null
        ]);
    break;

    case 'logout' :
        session_destroy();
        echo json_encode(["success" => true]);
    break;

    case 'book':
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(["success" => false, "message" => "Not logged in"]);
            break;
        }
        
        $car_id = $data['car_id'] ?? null;
        $start_date = $data['start_date'] ?? null;
        $end_date = $data['end_date'] ?? null;

        if (!$car_id || !$start_date || !$end_date) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Missing required fields"]);
            break;

        $stmt = $pdo->prepare("
            INSERT INTO bookings (user_id, car_id, start_date, end_date) VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$_SESSION['user_id'], $car_id, $start_date, $end_date]);
        echo json_encode(["success" => true, "booking_id" => $pdo->lastInsertId()]);
        break;

    case 'myBookings':
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(["success" => false, "message" => "Not logged in"]);
            break;
        }
    
        $stmt = $pdo->prepare("
            SELECT b.*, c.model, c.brand FROM bookings b
            JOIN bilar c ON b.car_id = c.id WHERE b.user_id = ?
        ");
        $stmt->execute([$_SESSION['user_id']]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        break;

    case 'cancelBooking':
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(["success" => false, "message" => "Not logged in"]);
            break;
        }

        $booking_id = $data['booking_id'] ?? null;
        if (!$booking_id) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Missing booking_id"]);
            break;
        }

        $stmt = $pdo->prepare("DELETE FROM bookings WHERE id = ? AND user_id = ?");
        $stmt->execute([$booking_id, $_SESSION['user_id']]);

        if ($stmt->rowCount() > 0) {
            echo json_encode(["success" => true]);
        } else {
            http_response_code(403);
            echo json_encode(["success" => false, "message" => "Booking not found"]);
        }
        break;

    case 'register':
        $username = $data['username'];
        $firstName = $data['first_name'];
        $lastName = $data['last_name'];
        $email = $data['email'];
        $password = password_hash($data['password'], PASSWORD_DEFAULT); 
        
        $stmt = $pdo->prepare("INSERT INTO users (username, first_name, last_name, email, password) VALUES (?, ?, ?, ?, ?)");
        $result = $stmt->execute([$username, $firstName, $lastName, $email, $password]);

        echo json_encode(["success" => $result]);
        exit;

    default:
        http_response_code(400);
        echo json_encode(["error" => "Unknown action: '$action'"]);
        break;
    }
