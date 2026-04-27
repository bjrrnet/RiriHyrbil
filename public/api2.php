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
        $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $check->execute([$email]);
        if ($check->fetch()) {
            echo json_encode(["success" => false, "message" => "This email is already registered, please login"]);
            break;
        }
        
        try {
            $stmt = $pdo->prepare("INSERT INTO users (full_name, email, phone, password_hash) VALUES (?, ?, ?, ?)");
            $stmt->execute([$full_name, $email, $phone, $hash]);
            echo json_encode(["success" => true]);
        } catch (PDOException $e) {
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
            $_SESSION['national_id'] = $user['national_id'];
            echo json_encode(["success" => true, "user" => $user['full_name']]);
        } else {
            echo json_encode(["success" => false, "message" => "Invalid email or password"]);
        }
    break;
    case 'forgot_password':
    $email = isset($data['email']) ? trim($data['email']) :

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

    $resetLink = "https://bilix.org/html/reset_password.html?token=" . $token;
    
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp-relay.brevo.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'a5a6de001@smtp-brevo.com';
        $mail->Password   = '';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('bilixcars@gmail.com', 'Bilix');
        $mail->addAddress($email, $user['full_name']);

        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = 'Reset your password';
        $mail->Body    = "
            <div style='font-family: Arial; padding: 20px;'>
                <h2>Hello {$user['full_name']}!</h2>
                <p>Click the button below to reset your password:</p>
                <a href='{$resetLink}' style='
                        background-color: #313a6e;
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
            "username" => $_SESSION['full_name'] ?? null,
            "national_id" => $_SESSION['national_id'] ?? null
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

    case 'updateAddress':

    if (!isset($_SESSION['user_id'])) {
        echo json_encode(["success" => false]);
        break;
    }

    $address = $data['address'] ?? '';

    $stmt = $pdo->prepare("UPDATE users SET address = ? WHERE id = ?");
    $result = $stmt->execute([$address, $_SESSION['user_id']]);

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
                     AND return_date > CURDATE()
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

        try {
            $pdo->beginTransaction();

            $lock = $pdo->prepare("SELECT id FROM cars WHERE id = ? FOR UPDATE");
            $lock->execute([$data['car_id']]);

            $check = pdo->prepare("SELECT id FROM bookings WHERE car id = ? AND status != 'cancelled' AND pickup_date < ? AND return_date > ?");
            $check = pdo->execute([$data['car_id'], $data['return_date'], $data['pickup_date']);

            if ($check->fetch()) {
                pdo->rollback();
                echo json_encode(["success" => false, "Message" => "Fel vid beställning, var god försök igen."]);



        if ($national_id) {
            $updateUser = $pdo->prepare("UPDATE users SET national_id = ? WHERE id = ?");
            $updateUser->execute([$national_id, $user_id]);
            $_SESSION['national_id'] = $national_id;
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
        if ($res) {
            $locationAddresses = [
                'Göteborg'  => 'Avenyn 1, 411 36 Göteborg',
                'Malmö'     => 'Stortorget 5, 211 22 Malmö',
                'Stockholm' => 'Drottninggatan 10, 111 51 Stockholm',
            ];

            $stmtCar = $pdo->prepare("SELECT name, brand, model, location FROM cars WHERE id = ?");
            $stmtCar->execute([$data['car_id']]);
            $car = $stmtCar->fetch();

            $location = $car['location'] ?? 'N/A';
            $address  = $locationAddresses[$location] ?? $location;
            $carName  = $car['name'] ?? ($car['brand'] . ' ' . $car['model']);

            $mail2 = new PHPMailer(true);
            try {
                $mail2->isSMTP();
                $mail2->Host       = 'smtp-relay.brevo.com';
                $mail2->SMTPAuth   = true;
                $mail2->Username   = 'a5a6de001@smtp-brevo.com';
                $mail2->Password   = '';
                $mail2->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail2->Port       = 587;
                $mail2->setFrom('bilixcars@gmail.com', 'Bilix');
                $mail2->addAddress($user['email'], $user['full_name']);
                $mail2->isHTML(true);
                $mail2->CharSet = 'UTF-8';
                $mail2->Subject = "Booking Confirmation - $carName";
                $mail2->Body = "
                    <div style='font-family: Arial; padding: 20px; max-width: 600px;'>
                        <h2>Hello {$user['full_name']}! 🚗</h2>
                        <p>Your booking is confirmed! Here are your details:</p>
                        <table style='width:100%; border-collapse: collapse;'>
                            <tr style='background:#f5f5f5;'>
                                <td style='padding:10px; border:1px solid #ddd;'><strong>Car</strong></td>
                                <td style='padding:10px; border:1px solid #ddd;'>$carName</td>
                            </tr>
                            <tr>
                                <td style='padding:10px; border:1px solid #ddd;'><strong>Pickup Location</strong></td>
                                <td style='padding:10px; border:1px solid #ddd;'>$location<br><small>$address</small></td>
                            </tr>
                            <tr style='background:#f5f5f5;'>
                                <td style='padding:10px; border:1px solid #ddd;'><strong>Pickup Date</strong></td>
                                <td style='padding:10px; border:1px solid #ddd;'>{$data['pickup_date']}</td>
                            </tr>
                            <tr>
                                <td style='padding:10px; border:1px solid #ddd;'><strong>Return Date</strong></td>
                                <td style='padding:10px; border:1px solid #ddd;'>{$data['return_date']}</td>
                            </tr>
                            <tr style='background:#f5f5f5;'>
                                <td style='padding:10px; border:1px solid #ddd;'><strong>Total Days</strong></td>
                                <td style='padding:10px; border:1px solid #ddd;'>{$data['total_days']} days</td>
                            </tr>
                            <tr>
                                <td style='padding:10px; border:1px solid #ddd;'><strong>Total Price</strong></td>
                                <td style='padding:10px; border:1px solid #ddd;'><strong>{$data['total_price']} SEK</strong></td>
                            </tr>
                        </table>
                        <br>
                        <p>Thank you for choosing Bilix! 😊</p>
                        <p style='color:gray; font-size:12px;'>Want to cancel? Log in to your profile.</p>
                    </div>
                ";
                $mail2->send();
            } catch (Exception $e) {
            }

            echo json_encode(["success" => true]);
        } else {
            echo json_encode(["success" => false]);
        } 
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

if ($result) {
    $stmtUser = $pdo->prepare("SELECT full_name, email FROM users WHERE id = ?");
    $stmtUser->execute([$_SESSION['user_id']]);
    $sender = $stmtUser->fetch();

    $mail3 = new PHPMailer(true);
    try {
        $mail3->isSMTP();
        $mail3->Host       = 'smtp-relay.brevo.com';
        $mail3->SMTPAuth   = true;
        $mail3->Username   = 'a5a6de001@smtp-brevo.com';
        $mail3->Password   = '';
        $mail3->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail3->Port       = 587;
        $mail3->setFrom('bilixcars@gmail.com', 'Bilix');
        $mail3->addAddress('bilixcars@gmail.com', 'Bilix Support');
        $mail3->addReplyTo($sender['email'], $sender['full_name']);
        $mail3->isHTML(true);
        $mail3->CharSet = 'UTF-8';
        $mail3->Subject = "New Request: $subject";
        $mail3->Body = "
            <div style='font-family: Arial; padding: 20px; max-width: 600px;'>
                <h2>New Support Request 📩</h2>
                <table style='width:100%; border-collapse: collapse;'>
                    <tr style='background:#f5f5f5;'>
                        <td style='padding:10px; border:1px solid #ddd;'><strong>From</strong></td>
                        <td style='padding:10px; border:1px solid #ddd;'>{$sender['full_name']}</td>
                    </tr>
                    <tr>
                        <td style='padding:10px; border:1px solid #ddd;'><strong>Email</strong></td>
                        <td style='padding:10px; border:1px solid #ddd;'>{$sender['email']}</td>
                    </tr>
                    <tr style='background:#f5f5f5;'>
                        <td style='padding:10px; border:1px solid #ddd;'><strong>Subject</strong></td>
                        <td style='padding:10px; border:1px solid #ddd;'>$subject</td>
                    </tr>
                    <tr>
                        <td style='padding:10px; border:1px solid #ddd;'><strong>Message</strong></td>
                        <td style='padding:10px; border:1px solid #ddd;'>$message</td>
                    </tr>
                </table>
                <br>
                <p style='color:gray; font-size:12px;'>You can reply directly to this email to respond to the user.</p>
            </div>
        ";
        $mail3->send();
    } catch (Exception $e) {

    }

    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "message" => "Failed to submit request"]);
}
break;
        break;

    case 'logout':
        session_destroy();
        echo json_encode(["success" => true]);
    break;

    default:
        echo json_encode(["error" => "Action not found"]);
    break;
}
