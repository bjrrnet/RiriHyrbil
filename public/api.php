<?php
session_start();

require "../src/databas.php";

header("Content-type: application/json");

$action = $_GET['action'] ?? '';
$data   = json_decode(file_get_contents("php://input"), true);

switch ($action) {

    // GET /api.php?action=bilar
    case 'bilar' :
    	$stmt = $pdo->query("SELECT * FROM cars");
    	$bilar = $stmt->fetchAll(PDO::FETCH_ASSOC);	
//      https://www.php.net/manual/en/pdo.constants.fetch-modes.php
        echo json_encode($bilar);
    break;

    case 'availableCars':
    //GET /api.php?action=availableCars&startYYYY-MM-DD&end=YYYY-MM-DD
    //ger alla bilar som är tillgängliga i givet spann
        $start = $_GET['start'] ?? null;
        $end = $_GET['end'] ?? null;

        if (!$start || !$end) {
            http_response_code(400); // https://www.w3schools.com/tags/ref_httpmessages.asp   --- OBS! Lite inkonsekvent använt i projektet, ändras om tid finnes.
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
            $_SESSION['user_id']  = $user['id']; // Lagrar inloggning
            $_SESSION['username'] = $user['username'];

            echo json_encode(["success" => true]);
        }  else  {
            echo json_encode(["success" => false, "message" => "Invalid username or password"]);
        }
    break;


    case 'checkLogin':
        echo json_encode([
            "loggedIn"=> isset($_SESSION['user_id']), // "isset" som i is set, kollar om en variabel "is set" dvs deklarerad och not null. https://www.w3schools.com/php/func_var_isset.asp
            "username" => $_SESSION['username'] ?? null
        ]);
    break;

    case 'logout' :
        session_destroy();
        echo json_encode(["success" => true]);
    break;

    case 'updateUser':
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(["success" => false, "message" => "Not logged in."]);
            break;
        }

        $updateable = ['first_name', 'last_name', 'email', 'phone_number']; // Säkerhetsåtgärd, tänker jag mig? Kolla upp om jag ens tänkt rätt om tid finnes.
        $fields     = [];
        $val        = [];

        // För att kunna pussla ihop ett stmt senare, med den datan vi får, så allt ska få plats i ett case, bygger vi upp en lista. Funkar bäst på engelska, fields och field. Svenska blir fält och fält.
        foreach ($updateable as $field) {
            if (isset($data[$field]) && $data[field] !== '') {      // Så vi skippar tomma fält
                $fields[] = "$field = ?";
                $val[]    = $data[field];
            }
        }

        //Lösenordshantering separat
        if (!empty($data['password'])) {
            $fields[] = "password = ?";
            $val[]    = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        $val[]     = $_SESSION['user_id'];
        $stmt      = $pdo->prepare("UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?");     // Vi använder implode för att pussla ihop den, med kommatecken
        $result    = $stmt->execute($val);
        echo json_encode(["success", $result]);
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
        }

        $checkAvailable = $pdo->prepare("
                SELECT id FROM rentals WHERE car_id = ? AND start_date < ? AND end_date > ?
        ");
        $checkAvailable->execute([$car_id, $end_date, $start_date]); // Detta ÄR rätt ordning, pga ordningen i våran query i preparen.

        // Vet ej vad som händer om startdatum är större än slutdatum, undersök om tid finnes.
        $stmt = $pdo->prepare("
            INSERT INTO rentals (user_id, car_id, start_date, end_date) VALUES (?, ?, ?, ?)
        ");
        $result = $stmt->execute([$_SESSION['user_id'], $car_id, $start_date, $end_date]);
        echo json_encode(["success" => $result, "booking_id" => $pdo->lastInsertId()]);
        break;

    case 'myBookings':
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(["success" => false, "message" => "Not logged in"]);
            break;
        }
    
        $stmt = $pdo->prepare("
            SELECT b.*, c.model, c.brand FROM rentals b
            JOIN cars c ON b.car_id = c.id WHERE b.user_id = ?
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

        $stmt = $pdo->prepare("DELETE FROM rentals WHERE id = ? AND user_id = ?");
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
        $password_hash = password_hash($data['password'], PASSWORD_DEFAULT); 

        // Kolla efter existerande mail eller användarnamn.
        $tjeck = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $tjeck->execute([$username]);
        if ($tjeck->fetch()) {
            echo json_encode(["success" => false, "message" => "Username already in use."]);
            break;
        }

        $tjeck = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $tjeck->execute([$email]);
        if ($tjeck->fetch()) {
            echo json_encode(["success" => false, "message" => "Email already in use."]);
            break;
        }
        $stmt = $pdo->prepare("INSERT INTO users (username, first_name, last_name, email, password_hash) VALUES (?, ?, ?, ?, ?)");
        $result = $stmt->execute([$username, $firstName, $lastName, $email, $password_hash]);

        echo json_encode(["success" => $result]);
        break;

    default:
        http_response_code(400);
        echo json_encode(["error" => "Unknown action: '$action'"]);
        break;
    }
