<?php

require dirname(__DIR__) . "/vendor/autoload.php";

require __DIR__ . "/bootstrap.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__)); 
    $dotenv->load();

    $database = new Database($_ENV['DB_HOST'], $_ENV['DB_NAME'], $_ENV['DB_USER'], $_ENV['DB_PASS']);

    $conn = $database->getConnection();

    $sql = "INSERT INTO user (name, email, password_hash, created_at)
    VALUES (:name, :email, :password_hash, :created_at)";
    
    $stmt = $conn->prepare($sql);
    
    $password_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $created_at = date('Y-m-d H:i:s');
    
    $stmt->bindValue(':name', $_POST['name'], PDO::PARAM_STR);
    $stmt->bindValue(':email', $_POST['email'], PDO::PARAM_STR);
    $stmt->bindValue(':password_hash', $password_hash, PDO::PARAM_STR);
    $stmt->bindValue(':created_at', $created_at, PDO::PARAM_STR);
    
    if ($stmt->execute()) {
        echo "Thank you for registering";
    } else {
        echo "Registration failed: " . implode(", ", $stmt->errorInfo());
    }
    exit;
    
}


?>