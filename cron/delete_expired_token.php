<?php

require dirname(__DIR__) . "/api/bootstrap.php";

require dirname(__DIR__) . "/vendor/autoload.php";

$database = new Database($_ENV['DB_HOST'], $_ENV['DB_NAME'], $_ENV['DB_USER'], $_ENV['DB_PASS']);

$refreshTokenGateway = new RefreshTokenGateway($database, $_ENV['SECRET_KEY']);

$deletedCount = $refreshTokenGateway->deleteExpiredTokenFromDatabase();

echo "Deleted $deletedCount expired tokens.\n";
