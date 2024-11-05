<?php
require dirname(__DIR__) . "/vendor/autoload.php";
require __DIR__ . "/bootstrap.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__)); 
    $dotenv->load();

    $database = new Database($_ENV['DB_HOST'], $_ENV['DB_NAME'], $_ENV['DB_USER'], $_ENV['DB_PASS']);
    $conn = $database->getConnection();

    $input = json_decode(file_get_contents('php://input'), true);

    if (!isset($input['name'], $input['email'], $input['password'])) {
        http_response_code(400);
        echo json_encode(["error" => "Missing required fields."]);
        exit;
    }

    $checkEmailSql = "SELECT COUNT(*) FROM user WHERE email = :email";
    $checkStmt = $conn->prepare($checkEmailSql);
    $checkStmt->bindValue(':email', $input['email'], PDO::PARAM_STR);
    $checkStmt->execute();
    
    if ($checkStmt->fetchColumn() > 0) {
        http_response_code(409);
        echo json_encode(["error" => "Email already registered"]);
        exit;
    }

    $sql = "INSERT INTO user (name, email, password_hash, created_at)
            VALUES (:name, :email, :password_hash, :created_at)";
    
    $stmt = $conn->prepare($sql);
    
    $password_hash = password_hash($input['password'], PASSWORD_DEFAULT);
    $created_at = date('Y-m-d H:i:s');

    $stmt->bindValue(':name', $input['name'], PDO::PARAM_STR);
    $stmt->bindValue(':email', $input['email'], PDO::PARAM_STR);
    $stmt->bindValue(':password_hash', $password_hash, PDO::PARAM_STR);
    $stmt->bindValue(':created_at', $created_at, PDO::PARAM_STR);

    if ($stmt->execute()) {

        $userId = $conn->lastInsertId();

        $user = [
            "id" => (int)$userId,
            "name" => $input['name'],
            "email" => $input['email'],
            "created_at" => $created_at
        ];

        $codec = new JWTCodec($_ENV['SECRET_KEY']);

        require __DIR__ . "/tokens.php";

        $refresh_token_gateway = new RefreshTokenGateway($database, $_ENV['SECRET_KEY']);
        
        $refresh_token_gateway->addRefreshTokenToDatbase($refresh_token, $refresh_token_expiry);

    } else {
        http_response_code(400);
        echo json_encode(["error" => "Registration failed: " . implode(", ", $stmt->errorInfo())]);
    }
    exit;
}
?>