<?php

require dirname(__DIR__) . "/vendor/autoload.php";

set_error_handler("ErrorHandler::handleError");
set_exception_handler("ErrorHandler::handleExecption");

// Create an immutable instance of Dotenv
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__)); 
$dotenv->load();

//header("Access-Control-Allow-Origin: http://localhost:60554"); // Replace with your actual flutter port
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-type: application/json; charset=UTF-8');