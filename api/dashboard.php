<?php

header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/../includes/auth.php";

requireApiLogin();

require_once __DIR__ . "/../config/database.php";

/*
|--------------------------------------------------------------------------
| Allow GET requests only
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" => "Method not allowed."
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| Count helper
|--------------------------------------------------------------------------
*/

function countValue($conn, $sql)
{
    $result = $conn->query($sql);

    if (!$result) {
        throw new RuntimeException("Dashboard query failed.");
    }

    $row = $result->fetch_assoc();

    return (int) ($row["total"] ?? 0);
}

/*
|--------------------------------------------------------------------------
| Dashboard data
|--------------------------------------------------------------------------
*/

try {

    $data = [

        // All calls

        "total_calls" => countValue(
            $conn,
            "SELECT COUNT(*) AS total
             FROM service_calls"
        ),

        // Completed calls

        "completed_calls" => countValue(
            $conn,
            "SELECT COUNT(*) AS total
             FROM service_calls
             WHERE status = 'Completed'"
        ),

        // Pending calls

        "pending_calls" => countValue(
            $conn,
            "SELECT COUNT(*) AS total
             FROM service_calls
             WHERE status IS NULL
                OR status <> 'Completed'"
        ),

        // Total service calls

        "total_service_calls" => countValue(
            $conn,
            "SELECT COUNT(*) AS total
             FROM service_calls
             WHERE call_type = 'Service Call'"
        ),

        // Completed service calls

        "completed_service_calls" => countValue(
            $conn,
            "SELECT COUNT(*) AS total
             FROM service_calls
             WHERE call_type = 'Service Call'
               AND status = 'Completed'"
        ),

        // Pending service calls

        "pending_service_calls" => countValue(
            $conn,
            "SELECT COUNT(*) AS total
             FROM service_calls
             WHERE call_type = 'Service Call'
               AND (
                    status IS NULL
                    OR status <> 'Completed'
               )"
        ),

        // Total application calls

        "total_application_calls" => countValue(
            $conn,
            "SELECT COUNT(*) AS total
             FROM service_calls
             WHERE call_type = 'Application Call'"
        ),

        // Completed application calls

        "completed_application_calls" => countValue(
            $conn,
            "SELECT COUNT(*) AS total
             FROM service_calls
             WHERE call_type = 'Application Call'
               AND status = 'Completed'"
        ),

        // Pending application calls

        "pending_application_calls" => countValue(
            $conn,
            "SELECT COUNT(*) AS total
             FROM service_calls
             WHERE call_type = 'Application Call'
               AND (
                    status IS NULL
                    OR status <> 'Completed'
               )"
        ),

        // Active laboratories

        "total_labs" => countValue(
            $conn,
            "SELECT COUNT(*) AS total
             FROM lab_master
             WHERE status = 'Active'"
        ),

        // Active instruments

        "total_instruments" => countValue(
            $conn,
            "SELECT COUNT(*) AS total
             FROM instrument_master
             WHERE status = 'Active'"
        ),

        // Calls logged today

        "today_calls" => countValue(
            $conn,
            "SELECT COUNT(*) AS total
             FROM service_calls
             WHERE logged_datetime >= CURDATE()
               AND logged_datetime < CURDATE() + INTERVAL 1 DAY"
        ),

        // Calls logged this month

        "monthly_calls" => countValue(
            $conn,
            "SELECT COUNT(*) AS total
             FROM service_calls
             WHERE logged_datetime >= DATE_FORMAT(
                    CURDATE(), '%Y-%m-01'
               )
               AND logged_datetime < DATE_FORMAT(
                    CURDATE() + INTERVAL 1 MONTH, '%Y-%m-01'
               )"

        )

    ];

    echo json_encode([
        "success" => true,
        "data" => $data
    ]);

} catch (Throwable $e) {

    error_log("Dashboard API error: " . $e->getMessage());

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Unable to load dashboard information."
    ]);

}

?>