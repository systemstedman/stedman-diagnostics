<?php

header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../config/database.php";

requireApiLogin();

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" => "GET method required."
    ]);

    exit;
}

$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id) {
    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "A valid service call ID is required."
    ]);

    exit;
}

try {
    $stmt = $conn->prepare("
        SELECT
            sc.*,

            lm.diagnostic_lab_name,
            lm.lab_incharge_contact,
            lm.mobile_number AS lab_mobile,
            lm.email_address,
            lm.city_location,
            lm.full_address,

            im.instrument_name,
            im.model_number,
            im.serial_number,
            im.warranty_status,
            im.warranty_expiry_date

        FROM service_calls sc

        INNER JOIN lab_master lm
            ON lm.id = sc.lab_id

        LEFT JOIN instrument_master im
            ON im.id = sc.instrument_id

        WHERE sc.id = ?
    ");

    $stmt->bind_param("i", $id);
    $stmt->execute();

    $service = $stmt->get_result()->fetch_assoc();

    if (!$service) {
        http_response_code(404);

        echo json_encode([
            "success" => false,
            "message" => "Service call not found."
        ]);

        exit;
    }

    echo json_encode([
        "success" => true,
        "service" => $service
    ]);

} catch (Throwable $e) {
    error_log("Service details API error: " . $e->getMessage());

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Unable to load service call details."
    ]);
}