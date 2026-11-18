<?php

header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../config/database.php";

requireApiLogin();

try {
    $sql = "
        SELECT
            sc.id,
            sc.call_id,
            sc.status,
            sc.call_type,
            sc.problem_reported,
            sc.contact_person,
            sc.logged_by,
            sc.logged_datetime,

            lm.diagnostic_lab_name,

            im.instrument_name,
            im.model_number,
            im.serial_number

        FROM service_calls sc

        INNER JOIN lab_master lm
            ON lm.id = sc.lab_id

        LEFT JOIN instrument_master im
            ON im.id = sc.instrument_id

        WHERE sc.status IS NULL
           OR sc.status <> 'Completed'

        ORDER BY sc.logged_datetime DESC
    ";

    $result = $conn->query($sql);

    echo json_encode([
        "success" => true,
        "data" => $result->fetch_all(MYSQLI_ASSOC)
    ]);

} catch (Throwable $e) {
    error_log("Pending calls API error: " . $e->getMessage());

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Unable to load active service calls."
    ]);
}