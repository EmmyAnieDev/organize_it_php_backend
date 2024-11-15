<?php

declare(strict_types = 1); 

require __DIR__ . "/bootstrap.php";

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$parts = explode("/", $path);

$resource = $parts[3];

$id = $parts[4] ?? null;

// check if the $resource is not equal to 'tasks'
if ($resource != 'tasks'){

    http_response_code(404);
    exit;

}

$database = new Database($_ENV['DB_HOST'], $_ENV['DB_NAME'], $_ENV['DB_USER'], $_ENV['DB_PASS']);

$codec = new JWTCodec($_ENV['SECRET_KEY']);

$userGateway = new UserGateway($database);

$auth = new Auth($userGateway, $codec);


if ( ! $auth->authenticationAccessToken()){
    exit;
}

$user_id = $auth->getUserId();

$taskGateway = new TaskGateway($database);

$controller = new TaskController($taskGateway, $user_id);

$controller->processRequest($_SERVER['REQUEST_METHOD'], $id);
