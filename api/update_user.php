<?php
require dirname(__DIR__) . "/vendor/autoload.php";
require __DIR__ . "/bootstrap.php";

if ($_SERVER['REQUEST_METHOD'] === 'PUT') {

    $dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__)); 
    $dotenv->load();

    $database = new Database($_ENV['DB_HOST'], $_ENV['DB_NAME'], $_ENV['DB_USER'], $_ENV['DB_PASS']);
    $conn = $database->getConnection();

    $input = json_decode(file_get_contents('php://input'), true);

    if (!isset($input['id'], $input['name'], $input['email'])) {
        http_response_code(400);
        echo json_encode(["error" => "Missing required fields."]);
        exit;
    }

    // Check if the user exists
    $checkIdSql = "SELECT COUNT(*) FROM user WHERE id = :id";
    $checkStmt = $conn->prepare($checkIdSql);
    $checkStmt->bindValue(':id', $input['id'], PDO::PARAM_INT);
    $checkStmt->execute();
    
    if ($checkStmt->fetchColumn() == 0) {
        http_response_code(404);
        echo json_encode(["error" => "User with id: {$input['id']} does not exist!"]);
        exit;
    }

    // Start building the SQL update statement
    $sql = "UPDATE user SET name = :name, email = :email";

    // If password is provided and not null, update it
    if (!empty($input['password'])) {
        $sql .= ", password_hash = :password_hash";
        $password_hash = password_hash($input['password'], PASSWORD_DEFAULT);
    }

    $sql .= " WHERE id = :id";

    $stmt = $conn->prepare($sql);
    
    $stmt->bindValue(':name', $input['name'], PDO::PARAM_STR);
    $stmt->bindValue(':email', $input['email'], PDO::PARAM_STR);
    $stmt->bindValue(':id', $input['id'], PDO::PARAM_INT);

    // Bind password hash if it's set
    if (!empty($input['password'])) {
        $stmt->bindValue(':password_hash', $password_hash, PDO::PARAM_STR);
    }

    if ($stmt->execute()) {

        $user = [
            "id" => $input['id'],
            "name" => $input['name'],
            "email" => $input['email'],
        ];
        
        // Include password only if it was updated
        if (!empty($input['password'])) {
            $user["password"] = $input['password'];
        }
        
        $response = [
            "status" => "success",
            "user" => $user,
        ];
        
        echo json_encode($response);        
        exit;
    } else {
        http_response_code(400);
        echo json_encode(["error" => "Update failed: " . implode(", ", $stmt->errorInfo())]);
    }
    exit;
}
?>
