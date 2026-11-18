<?php

header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../config/database.php";

requireApiLogin();

try {
    $sql = "
        SELECT id, full_name
        FROM users
        WHERE role = 'Service Person'
          AND status = 'Active'
        ORDER BY full_name
    ";

    $result = $conn->query($sql);

    echo json_encode([
        "success" => true,
        "data" => $result->fetch_all(MYSQLI_ASSOC)
    ]);

} catch (Throwable $e) {
    error_log("Engineers API error: " . $e->getMessage());

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Unable to load service engineers."
    ]);
}