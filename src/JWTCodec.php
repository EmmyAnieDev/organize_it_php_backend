<?php

use Dotenv\Exception\InvalidEncodingException;

class JWTCodec {

    public function __construct(private string $secret_key){}

    public function encode(array $payload) : string {

        $header = json_encode([ "typ" => "JWT", "alg" => "HS256" ]);
        $header = $this->base64urlEncode($header);
        
        $payload = json_encode($payload);
        $payload = $this->base64urlEncode($payload);

        $signature = hash_hmac("sha256", $header . "." . $payload, $this->secret_key, true);
        $signature = $this->base64urlEncode($signature);

        return "$header.$payload.$signature";


    }

    public function decode($token) : array {

        if (preg_match("/^(?<header>.+)\.(?<payload>.+)\.(?<signature>.+)$/", $token, $matches) !==1) {

            throw new InvalidArgumentException("Invalid token format");

        }

        $signature = hash_hmac("sha256", $matches['header'] . "." . $matches['payload'], $this->secret_key, true);
        $signature_from_token = $this->base64urlDecode($matches['signature']);
        
        if ( !hash_equals($signature, $signature_from_token)) {

            throw new InvalidSignatureException;

        }

        $payload = json_decode($this->base64urlDecode($matches['payload']), true);

        if ($payload["exp"] < time()){

            throw new TokenExpiredException;

        }

        return $payload;

    }

    public function base64urlEncode(string $text) : string {

        return str_replace(["+", "/", "="], ["-", "_", ""], base64_encode($text));

    }

    public function base64urlDecode(string $text) : string {

        $base64 = str_replace(["-", "_"], ["+", "/"], $text);
        return base64_decode($base64);

    }



}