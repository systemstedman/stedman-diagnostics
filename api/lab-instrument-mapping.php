<?php

header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/../includes/auth.php";
requireApiLogin();

require_once __DIR__ . "/../config/database.php";

$isAdmin = ($_SESSION["role"] ?? "") === "Admin";

/*
|--------------------------------------------------------------------------
| Admin-only mapping view
|--------------------------------------------------------------------------
*/

if (!$isAdmin) {
    http_response_code(403);

    echo json_encode([
        "error" => "Admin access required."
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| GET: List Lab-Instrument Mappings
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "GET") {

    $sql = "
        SELECT
            l.id AS lab_id,
            l.diagnostic_lab_name AS lab_name,
            i.id AS instrument_id,
            i.instrument_name,
            i.model_number,
            i.serial_number,
            i.installation_date,
            i.warranty_status,
            i.warranty_expiry_date,
            i.status AS instrument_status,
            l.status AS lab_status
        FROM instrument_master i
        INNER JOIN lab_master l
            ON l.id = i.lab_id
        ORDER BY
            l.diagnostic_lab_name,
            i.instrument_name
    ";

    $result = $conn->query($sql);

    if (!$result) {
        http_response_code(500);

        echo json_encode([
            "error" => "Unable to retrieve mappings."
        ]);

        exit;
    }

    echo json_encode(
        $result->fetch_all(MYSQLI_ASSOC)
    );

    exit;
}

/*
|--------------------------------------------------------------------------
| POST: Map Instrument to Lab
|--------------------------------------------------------------------------
| This updates instrument_master.lab_id.
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $data = json_decode(
        file_get_contents("php://input"),
        true
    );

    if (!is_array($data)) {
        $data = $_POST;
    }

    $instrumentId = filter_var(
        $data["instrument_id"] ?? null,
        FILTER_VALIDATE_INT
    );

    $labId = filter_var(
        $data["lab_id"] ?? null,
        FILTER_VALIDATE_INT
    );

    if (!$instrumentId || !$labId) {
        http_response_code(400);

        echo json_encode([
            "error" => "Instrument ID and Lab ID are required."
        ]);

        exit;
    }

    /*
     * Verify the lab is active.
     */

    $labCheck = $conn->prepare("
        SELECT id
        FROM lab_master
        WHERE id = ?
          AND status = 'Active'
    ");

    $labCheck->bind_param("i", $labId);
    $labCheck->execute();

    $lab = $labCheck->get_result()->fetch_assoc();

    $labCheck->close();

    if (!$lab) {
        http_response_code(400);

        echo json_encode([
            "error" => "The selected lab does not exist or is inactive."
        ]);

        exit;
    }

    /*
     * Verify the instrument exists.
     */

    $instrumentCheck = $conn->prepare("
        SELECT id
        FROM instrument_master
        WHERE id = ?
    ");

    $instrumentCheck->bind_param("i", $instrumentId);
    $instrumentCheck->execute();

    $instrument = $instrumentCheck->get_result()->fetch_assoc();

    $instrumentCheck->close();

    if (!$instrument) {
        http_response_code(404);

        echo json_encode([
            "error" => "Instrument not found."
        ]);

        exit;
    }

    /*
     * Update the instrument's assigned lab.
     */

    $update = $conn->prepare("
        UPDATE instrument_master
        SET lab_id = ?
        WHERE id = ?
    ");

    $update->bind_param(
        "ii",
        $labId,
        $instrumentId
    );

    if ($update->execute()) {

        echo json_encode([
            "success" => true,
            "message" => "Instrument mapped to lab successfully."
        ]);

    } else {

        http_response_code(500);

        echo json_encode([
            "error" => "Unable to map instrument to lab."
        ]);

    }

    $update->close();

    exit;
}

http_response_code(405);

echo json_encode([
    "error" => "Method not allowed."
]);