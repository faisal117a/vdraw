<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *"); 
header("Access-Control-Allow-Methods: GET");

// Standard Handshake Response
$response = [
    "status" => "OK",
    "app" => "vdraw",
    "ts" => time()
];

echo json_encode($response);
exit;
