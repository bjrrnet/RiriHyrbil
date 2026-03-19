<?php
session_start();
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require "../src/databas.php";
require '../src/Exception.php';
require '../src/PHPMailer.php';
require '../src/SMTP.php';

header("Content-type: application/json");

$action = $_GET['action'] ?? '';
$data   = json_decode(file_get_contents("php://input"), true);

switch ($action) {

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
    case 'forgot_password':
        $email = isset($data['email']) ? trim($data['email']) : '';

        if (empty($email)) {
            echo json_encode(["success" => false, "message" => "Email is required"]);
            break;
        }

        $stmt = $pdo->prepare("SELECT id, full_name FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            echo json_encode(["success" => false, "message" => "Email not found"]);
            break;
        }

        $token = bin2hex(random_bytes(32));
        $expires = date("Y-m-d H:i:s", strtotime('+1 hour'));

        $update = $pdo->prepare("UPDATE users SET reset_token = ?, token_expires = ? WHERE email = ?");
        $update->execute([$token, $expires, $email]);

        $resetLink = "https://hyrabil.rf.gd/html/reset_password.html?token=" . $token;

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'bilixcars@gmail.com';
            $mail->Password   = 'tqwuujjmuaddwwgb';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            $mail->setFrom('bilixcars@gmail.com', 'HyraBil');
            $mail->addAddress($email, $user['full_name']);

            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            $mail->Subject = 'Reset your password';
            $mail->Body    = "
                <div style='font-family: Arial; padding: 20px;'>
                    <h2>Hello {$user['full_name']}!</h2>
                    <p>Click the button below to reset your password:</p>
                    <a href='{$resetLink}' style='
                        background-color: #4CAF50;
                        color: white;
                        padding: 12px 24px;
                        text-decoration: none;
                        border-radius: 5px;
                        display: inline-block;
                    '>Reset Password</a>
                    <p style='color: gray; margin-top: 20px;'>
                        This link is valid for 1 hour only.
                    </p>
                </div>
            ";

            $mail->send();
            echo json_encode(["success" => true]);

        } catch (Exception $e) {
            echo json_encode(["success" => false, "message" => "Email error: " . $mail->ErrorInfo]);
        }
    break;

    case 'reset_password_submit':
        $token = $data['token'] ?? '';
        $password = $data['password'] ?? '';

        if (!$token || !$password) {
            echo json_encode(["success" => false, "message" => "Missing fields"]);
            break;
        }

        if (strlen($password) < 6) {
            echo json_encode(["success" => false, "message" => "Password too short"]);
            break;
        }

        $stmt = $pdo->prepare("SELECT id FROM users WHERE reset_token = ? AND token_expires > NOW()");
        $stmt->execute([$token]);
        $user = $stmt->fetch();

        if (!$user) {
            echo json_encode(["success" => false, "message" => "Invalid or expired link"]);
            break;
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password_hash = ?, reset_token = NULL, token_expires = NULL WHERE id = ?");
        $stmt->execute([$hash, $user['id']]);

        echo json_encode(["success" => true, "message" => "Password updated successfully!"]);
    break;
        
    case 'deleteAccount':
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(["success" => false, "message" => "Unauthorized access"]);
            break;
        }

        $current_user_id = $_SESSION['user_id'];

        try {
            $pdo->beginTransaction();

            $stmt1 = $pdo->prepare("DELETE FROM requests WHERE user_id = ?");
            $stmt1->execute([$current_user_id]);

            $stmt2 = $pdo->prepare("DELETE FROM bookings WHERE user_id = ?");
            $stmt2->execute([$current_user_id]);

            $stmt3 = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt3->execute([$current_user_id]);

            $pdo->commit();

            session_destroy();
            
            echo json_encode(["success" => true]);

        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
        }
    break;
        
        
    case 'checkLogin':
        echo json_encode([
            "loggedIn" => isset($_SESSION['user_id']),
            "username" => $_SESSION['full_name'] ?? null
            ]);
        break;        
    case 'getUserDetails':

    if (!isset($_SESSION['user_id'])) {
        echo json_encode(["success" => false]);
        break;
    }

    $stmt = $pdo->prepare("SELECT full_name, email, phone FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

   $stmt = $pdo->prepare("SELECT full_name, email, phone, national_id FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    echo json_encode([
        "success" => true,
        "data" => $user
    ]);
break;

    break;
    case 'updatePhone':

        if (!isset($_SESSION['user_id'])) {
            echo json_encode(["success" => false]);
            break;
        }

        $phone = $data['phone'] ?? '';

        $stmt = $pdo->prepare("UPDATE users SET phone = ? WHERE id = ?");
        $result = $stmt->execute([$phone, $_SESSION['user_id']]);

        echo json_encode(["success" => $result]);

    break;      
    case 'getMyRequests':

        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode([]);
            break;
        }

        $stmt = $pdo->prepare("SELECT subject, message, created_at 
                            FROM requests 
                            WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);

        echo json_encode($stmt->fetchAll());

    break;
    
    case 'cars':
        try {
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $query = "
                SELECT 
                    c.*, 
                    (SELECT MAX(return_date) 
                     FROM bookings 
                     WHERE car_id = c.id 
                     AND status NOT IN ('cancelled') 
                     AND return_date >= CURDATE()
                    ) AS next_available_date
                FROM cars c
                WHERE c.is_available = 1
            ";

            $stmt = $pdo->query($query);
            $cars = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($cars);

        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                "success" => false, 
                "error" => "Database Error: " . $e->getMessage()
            ]);
        }
    break;
  
    case 'book':
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(["success" => false, "message" => "Please login first"]);
            break;
        }

        $user_id = $_SESSION['user_id'];
        $national_id = $data['national_id'] ?? null;

        if ($national_id) {
            $updateUser = $pdo->prepare("UPDATE users SET national_id = ? WHERE id = ?");
            $updateUser->execute([$national_id, $user_id]);
        }

        $stmtUser = $pdo->prepare("SELECT full_name, email, phone FROM users WHERE id = ?");
        $stmtUser->execute([$user_id]);
        $user = $stmtUser->fetch();

        $stmt = $pdo->prepare("
            INSERT INTO bookings 
            (user_id, car_id, customer_name, customer_email, customer_phone, pickup_date, return_date, total_days, total_price, national_id) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $res = $stmt->execute([
            $user_id,
            $data['car_id'],
            $user['full_name'],
            $user['email'],
            $user['phone'],
            $data['pickup_date'],
            $data['return_date'],
            $data['total_days'],
            $data['total_price'],
            $data['national_id']
        ]);

        echo json_encode(["success" => $res]);
    break;

    case 'myBookings':
        if (!isset($_SESSION['user_id'])) {
            echo json_encode([]);
            break;
        }

        $stmt = $pdo->prepare("
            SELECT b.*, c.brand, c.model, c.name as car_name, c.location  
            FROM bookings b 
            JOIN cars c ON b.car_id = c.id 
            WHERE b.user_id = ? AND (b.status IS NULL OR b.status != 'cancelled')
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
                               WHERE id = ? AND user_id = ?");
        $stmt->execute([$booking_id, $_SESSION['user_id']]);

        if ($stmt->rowCount() > 0) {
            echo json_encode(["success" => true, "message" => "Booking cancelled successfully"]);
        } else {
            echo json_encode(["success" => false, "message" => "Booking not found or already cancelled."]);
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