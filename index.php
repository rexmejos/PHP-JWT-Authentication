<?php
require 'vendor/autoload.php';
use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

header("Content-Type: application/json");
include_once 'Database.php';

$database = new Database();
$db = $database->getConnection();

$key = "this_is_a_very_long_and_super_secure_secret_key_12345";
$data = json_decode(file_get_contents("php://input"));
$action = $_GET['action'] ?? '';

// --- 1. REGISTER ---
if ($action == 'register') {
    $query = "INSERT INTO users (username, password) VALUES (?, ?)";
    $stmt = $db->prepare($query);
    $hashed_password = password_hash($data->password, PASSWORD_BCRYPT);
    
    if($stmt->execute([$data->username, $hashed_password])) {
        echo json_encode(["message" => "User registered successfully."]);
    }
}

// --- 2. LOGIN ---
elseif ($action == 'login') {
    $query = "SELECT id, username, password FROM users WHERE username = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$data->username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($data->password, $user['password'])) {
        $payload = [
            "iss" => "localhost",
            "iat" => time(),
            "exp" => time() + 3600, // Token expires in 1 hour
            "data" => ["id" => $user['id'], "username" => $user['username']]
        ];
        $jwt = JWT::encode($payload, $key, 'HS256');
        echo json_encode(["message" => "Login successful", "jwt" => $jwt]);
    } else {
        http_response_code(401);
        echo json_encode(["message" => "Invalid credentials."]);
    }
}

// --- 3. PROTECTED ROUTE ---
elseif ($action == 'protected') {
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? '';

    if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        try {
            $decoded = JWT::decode($matches[1], new Key($key, 'HS256'));
            echo json_encode(["message" => "Access granted", "user" => $decoded->data]);
        } catch (Exception $e) {
            http_response_code(401);
            echo json_encode(["message" => "Access denied", "error" => $e->getMessage()]);
        }
    } else {
        http_response_code(401);
        echo json_encode(["message" => "Token not found."]);
    }
}

// --- 4. LOGOUT ---
elseif ($action == 'logout') {
    // JWT is stateless, so "logging out" on the server usually means 
    // telling the client to delete the token.
    echo json_encode(["message" => "Logged out successfully. Please delete your token."]);
}
?>