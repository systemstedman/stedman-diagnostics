<?php

header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/../includes/auth.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION["user_id"])) {
    http_response_code(401);

    echo json_encode([
        "success" => false,
        "message" => "You are not logged in. Please log in again."
    ]);

    exit;
}

$role = trim($_SESSION["role"] ?? "");

echo json_encode([
    "success" => true,
    "user_id" => $_SESSION["user_id"],
    "username" => $_SESSION["username"] ?? "",
    "full_name" => $_SESSION["full_name"] ?? "",
    "role" => $role
]);

exit;