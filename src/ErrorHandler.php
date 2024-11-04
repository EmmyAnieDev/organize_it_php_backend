<?php

class ErrorHandler {

    public static function handleError(int $errno, string $errstr, string $errfile, int $errline) : void {

        throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
    }

    public static function handleExecption(Throwable $execption) : void {

        echo json_encode([

            http_response_code(500),
            "code" => $execption->getCode(),
            "message" => $execption->getMessage(),
            "file" => $execption->getFile(),
            "line" => $execption->getLine(),

        ]);

    }

}