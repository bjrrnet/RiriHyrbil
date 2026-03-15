<?php
session_start();
require "../src/databas.php"; 

header("Content-type: application/json");

$action = $_GET['action'] ?? '';
$data   = json_decode(file_get_contents("php://input"), true);

switch ($action) {

    case 'cars':
        $stmt = $pdo->query("SELECT * FROM cars WHERE is_available = 1");
        echo json_encode($stmt->fetchAll());
    break;

    case 'login':
        $email = $data['email'] ? trim($data['email']) : '';
        $password = $data['password'] ?? '';

        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['full_name'] = $user['full_name'];
            echo json_encode(["success" => true, "user" => $user['full_name']]);
        } else {
            echo json_encode(["success" => false, "message" => "Invalid email or password"]);
        }
    break;

    case 'register':
        $full_name = $data['full_name'] ?? '';
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';
        $phone = $data['phone'] ?? '';

        if (!$full_name || !$email || !$password) {
            echo json_encode(["success" => false, "message" => "Please fill in all required fields"]);
            break;
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        
        try {
            $stmt = $pdo->prepare("INSERT INTO users (full_name, email, phone, password_hash) VALUES (?, ?, ?, ?)");
            $stmt->execute([$full_name, $email, $phone, $hash]);
            echo json_encode(["success" => true]);
        } catch (Exception $e) {
            echo json_encode(["success" => false, "message" => "Email is already registered"]);
        }
    break;
    case 'forgot_password':
        $email = isset($data['email']) ? trim($data['email']) : '';

        if (empty($email)) {
            echo json_encode(["success" => false, "message" => "E-post krävs"]);
            break;
        }
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user) {
            echo json_encode(["success" => true, "message" => "Instruktioner har skickats."]);}
        else {echo json_encode(["success" => true, "message" => "Instruktioner har skickats."]);
        }
    break;



    case 'book':
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(["success" => false, "message" => "Please login first"]);
            break;
        }

        $stmt = $pdo->prepare("INSERT INTO bookings (user_id, car_id, customer_name, customer_email, customer_phone, pickup_date, return_date, total_days, total_price) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $res = $stmt->execute([
            $_SESSION['user_id'],
            $data['car_id'],
            $_SESSION['full_name'], 
            $data['email'],         
            $data['phone'],         
            $data['pickup_date'],   
            $data['return_date'],   
            $data['total_days'],
            $data['total_price']
        ]);

        echo json_encode(["success" => $res]);
    break;

    case 'myBookings':
        if (!isset($_SESSION['user_id'])) {
            echo json_encode([]);
            break;
        }

        $stmt = $pdo->prepare("
            SELECT b.*, c.brand, c.model, c.name as car_name 
            FROM bookings b 
            JOIN cars c ON b.car_id = c.id 
            WHERE b.user_id = ?
        ");
        $stmt->execute([$_SESSION['user_id']]);
        echo json_encode($stmt->fetchAll());
    break;

    case 'cancelBooking':
            if (!isset($_SESSION['user_id'])) {
                echo json_encode(["success" => false, "message" => "Not logged in"]);
                break;
            }

            $booking_id = $data['booking_id'] ?? null;
            if (!$booking_id) {
                echo json_encode(["success" => false, "message" => "Missing booking_id"]);
                break;
            }

            $stmt = $pdo->prepare("UPDATE bookings SET status = 'cancelled' 
                                WHERE id = ? AND user_id = ? 
                                AND status IN ('pending', 'confirmed')");
            $stmt->execute([$booking_id, $_SESSION['user_id']]);

            if ($stmt->rowCount() > 0) {
                echo json_encode(["success" => true, "message" => "Booking cancelled successfully"]);
            } else {
                echo json_encode(["success" => false, "message" => "Unable to cancel. Booking may be completed or already cancelled."]);
            }
    break;
    
    case 'submitRequest':
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(["success" => false, "message" => "Please login first"]);
            break;
        }

        $subject = $data['subject'] ?? null;
        $message = $data['message'] ?? null;

        if (!$subject || !$message) {
            echo json_encode(["success" => false, "message" => "All fields are required"]);
            break;
        }

        $stmt = $pdo->prepare("INSERT INTO requests (user_id, SUBJECT, message) VALUES (?, ?, ?)");
        $result = $stmt->execute([$_SESSION['user_id'], $subject, $message]);

        echo json_encode(["success" => $result]);
        break;

    case 'logout':
        session_destroy();
        echo json_encode(["success" => true]);
    break;

    default:
        echo json_encode(["error" => "Action not found"]);
    break;
}