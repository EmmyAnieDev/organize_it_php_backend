<?php

$payload = [
    "sub" => $user['id'],
    "name" => $user['name'],
    "exp" => time() + 600
];

$refresh_token_expiry = time() + 1800;

$refresh_token_payload = [
    "sub" => $user['id'],
    "exp" => $refresh_token_expiry
];

$access_token = $codec->encode($payload);
$refresh_token = $codec->encode($refresh_token_payload);

echo json_encode(["access token" => $access_token, "refresh token" => $refresh_token]);