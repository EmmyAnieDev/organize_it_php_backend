<?php

$payload = [
    "sub" => $user['id'],
    "name" => $user['name'],
    "exp" => time() + 600
];

$refresh_token_expiry = time() + 423800;

$refresh_token_payload = [
    "sub" => $user['id'],
    "exp" => $refresh_token_expiry
];

$access_token = $codec->encode($payload);
$refresh_token = $codec->encode($refresh_token_payload);

// // Add this line for debugging the response
// file_put_contents('response_log.txt', json_encode([
//     "user" => $user,
//     "access_token" => $access_token,
//     "refresh_token" => $refresh_token
// ]));

echo json_encode([
    "user" => $user,
    "access_token" => $access_token,
    "refresh_token" => $refresh_token
]);