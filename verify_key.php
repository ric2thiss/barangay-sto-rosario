<?php
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$type = $input['type'] ?? '';
$username = $input['username'] ?? '';
$password = $input['password'] ?? '';

if (empty($type) || empty($username) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Please provide all credentials.']);
    exit;
}

$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'profiling-system';

try {
    $conn = new mysqli($host, $user, $pass, $dbname);
    if ($conn->connect_error) {
        throw new Exception("Database connection failed.");
    }

    $authenticated = false;
    $errorMessage = 'Invalid username or password.';

    if ($type === 'admin') {
        // 1. Check admin table
        $stmt = $conn->prepare("SELECT password FROM admin WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            if (password_verify($password, $row['password'])) {
                $authenticated = true;
            }
        }
        $stmt->close();

        // 2. If not authenticated in admin table, check barangay_official table
        // We consider officials with certain permissions or all officials as "admins" for this system access
        if (!$authenticated) {
            $stmt = $conn->prepare("SELECT password FROM barangay_official WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                if (password_verify($password, $row['password'])) {
                    $authenticated = true;
                }
            }
            $stmt->close();
        }
    } else if ($type === 'purok_president') {
        // Check residents table with is_purok_president = 1
        $stmt = $conn->prepare("SELECT password FROM residents WHERE username = ? AND is_purok_president = 1 AND is_deleted = 0");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            if (password_verify($password, $row['password'])) {
                $authenticated = true;
            }
        } else {
            $errorMessage = 'Purok President account not found or not authorized.';
        }
        $stmt->close();
    }

    if ($authenticated) {
        // Start session and store user info if needed
        session_start();
        $_SESSION['user_type'] = $type;
        $_SESSION['username'] = $username;
        $_SESSION['logged_in'] = true;
        
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => $errorMessage]);
    }

    $conn->close();

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
