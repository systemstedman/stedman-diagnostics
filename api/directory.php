<?php

header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../config/database.php";

requireApiLogin();


$type = $_GET["type"] ?? "labs";


if ($type === "instruments") {

    $sql = "
        SELECT
            im.id,
            im.instrument_name,
            im.model_number,
            im.serial_number,
            im.installation_date,
            im.warranty_status,
            im.warranty_expiry_date,
            im.status,
            lm.diagnostic_lab_name

        FROM instrument_master im

        INNER JOIN lab_master lm
            ON lm.id = im.lab_id

        ORDER BY
            lm.diagnostic_lab_name,
            im.instrument_name
    ";

} elseif ($type === "history") {

    $sql = "
        SELECT
            sc.call_id,
            sc.status,
            sc.call_type,
            sc.logged_datetime,
            sc.visit_in_time,
            sc.visit_out_time,
            sc.assigned_engineer,
            sc.closed_by,
            sc.observations,
            sc.actions_taken,
            sc.spares_status,
            sc.spares_details,
            sc.signed_report_url,
            lm.diagnostic_lab_name,
            im.instrument_name,
            im.serial_number

        FROM service_calls sc

        INNER JOIN lab_master lm
            ON lm.id = sc.lab_id

        LEFT JOIN instrument_master im
            ON im.id = sc.instrument_id

        ORDER BY sc.created_at DESC
    ";

} else {

    $sql = "
        SELECT
            id,
            diagnostic_lab_name,
            lab_incharge_contact,
            mobile_number,
            email_address,
            city_location,
            full_address,
            status

        FROM lab_master

        ORDER BY diagnostic_lab_name
    ";

}


$result = $conn->query($sql);

echo json_encode(
    $result->fetch_all(MYSQLI_ASSOC)
);

?>