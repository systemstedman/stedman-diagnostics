<?php

header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../config/database.php";

requireApiLogin();

function instrumentResponse(
    $success,
    $message,
    $data = null,
    $statusCode = 200
) {
    http_response_code($statusCode);

    echo json_encode([
        "success" => $success,
        "message" => $message,
        "data" => $data
    ]);

    exit;
}

$cachedInstrumentRequestData = null;

function instrumentRequestData(): array
{
    global $cachedInstrumentRequestData;

    if ($cachedInstrumentRequestData !== null) {
        return $cachedInstrumentRequestData;
    }

    $contentType = $_SERVER["CONTENT_TYPE"] ?? "";

    if (stripos($contentType, "application/json") !== false) {
        $data = json_decode(file_get_contents("php://input"), true);

        $cachedInstrumentRequestData = is_array($data) ? $data : [];
    } else {
        $cachedInstrumentRequestData = $_POST;
    }

    return $cachedInstrumentRequestData;
}

$method = $_SERVER["REQUEST_METHOD"];
if ($method === "POST") {
    $override = strtoupper(trim((string)($_SERVER["HTTP_X_HTTP_METHOD_OVERRIDE"] ?? "")));
    if ($override === "") {
        $bodyData = instrumentRequestData();
        $override = strtoupper(trim((string)($bodyData["_method"] ?? "")));
    }
    if ($override === "PUT") {
        $method = "PUT";
    }
}

$isAdmin = (($_SESSION["role"] ?? "") === "Admin");

/*
|--------------------------------------------------------------------------
| GET: Retrieve instruments
|--------------------------------------------------------------------------
*/

if ($method === "GET") {

    $labId = filter_var(
        $_GET["lab_id"] ?? null,
        FILTER_VALIDATE_INT
    );

    $all = isset($_GET["all"]) && $_GET["all"] === "1";

    if ($all && !$isAdmin) {
        instrumentResponse(false, "Admin access required.", null, 403);
    }

    $sql = "
        SELECT
            i.id,
            i.lab_id,
            l.diagnostic_lab_name,
            i.instrument_name,
            i.manufacturer,
            i.model_number,
            i.serial_number,
            i.installation_date,
            i.warranty_status,
            i.warranty_expiry_date,
            i.status
        FROM instrument_master i
        INNER JOIN lab_master l
            ON l.id = i.lab_id
        WHERE 1 = 1
    ";

    $types = "";
    $params = [];

    if ($labId) {
        $sql .= " AND i.lab_id = ?";
        $types .= "i";
        $params[] = $labId;
    }

    if (!$all) {
        $sql .= "
            AND i.status = 'Active'
            AND l.status = 'Active'
        ";
    }

    $sql .= " ORDER BY i.instrument_name ASC";

    $stmt = $conn->prepare($sql);

    if ($params) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();

    instrumentResponse(
        true,
        "Instruments retrieved successfully.",
        $stmt->get_result()->fetch_all(MYSQLI_ASSOC)
    );
}

/*
|--------------------------------------------------------------------------
| POST: Add instrument
|--------------------------------------------------------------------------
*/

if ($method === "POST") {

    if (!$isAdmin) {
        instrumentResponse(false, "Admin access required.", null, 403);
    }

    $data = instrumentRequestData();

    $labId = filter_var(
        $data["lab_id"] ?? null,
        FILTER_VALIDATE_INT
    );

    $name = trim($data["instrument_name"] ?? "");
    $manufacturer = trim($data["manufacturer"] ?? "");
    $model = trim($data["model_number"] ?? "");
    $serial = trim($data["serial_number"] ?? "");

    $installationDate = trim($data["installation_date"] ?? "");
    $warrantyStatus = trim($data["warranty_status"] ?? "");
    $warrantyExpiry = trim($data["warranty_expiry_date"] ?? "");

    $status = trim($data["status"] ?? "Active");

    if (!$labId || $name === "" || $serial === "") {
        instrumentResponse(
            false,
            "Lab, instrument name, and serial number are required.",
            null,
            400
        );
    }

    if (!in_array($status, ["Active", "Inactive"], true)) {
        instrumentResponse(false, "Invalid instrument status.", null, 400);
    }

    foreach ([
        $installationDate,
        $warrantyExpiry
    ] as $date) {

        if (
            $date !== "" &&
            !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)
        ) {
            instrumentResponse(
                false,
                "Dates must use YYYY-MM-DD format.",
                null,
                400
            );
        }
    }

    $check = $conn->prepare("
        SELECT id
        FROM lab_master
        WHERE id = ?
    ");

    $check->bind_param("i", $labId);
    $check->execute();

    if (!$check->get_result()->fetch_assoc()) {
        instrumentResponse(false, "Selected lab does not exist.", null, 400);
    }

    $installationDate = $installationDate !== ""
        ? $installationDate
        : null;

    $warrantyExpiry = $warrantyExpiry !== ""
        ? $warrantyExpiry
        : null;

    try {

        $stmt = $conn->prepare("
            INSERT INTO instrument_master (
                lab_id,
                instrument_name,
                manufacturer,
                model_number,
                serial_number,
                installation_date,
                warranty_status,
                warranty_expiry_date,
                status
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            "issssssss",
            $labId,
            $name,
            $manufacturer,
            $model,
            $serial,
            $installationDate,
            $warrantyStatus,
            $warrantyExpiry,
            $status
        );

        $stmt->execute();

        instrumentResponse(
            true,
            "Instrument added and mapped to the selected lab.",
            ["id" => $stmt->insert_id]
        );

    } catch (mysqli_sql_exception $e) {

        error_log("Instrument insert error: " . $e->getMessage());

        if ((int)$e->getCode() === 1062) {
            instrumentResponse(
                false,
                "This serial number already exists.",
                null,
                409
            );
        }

        instrumentResponse(
            false,
            "Unable to save the instrument.",
            null,
            500
        );
    }
}

/*
|--------------------------------------------------------------------------
| PUT: Update instrument
|--------------------------------------------------------------------------
*/

if ($method === "PUT") {

    if (!$isAdmin) {
        instrumentResponse(false, "Admin access required.", null, 403);
    }

    $data = instrumentRequestData();

    $id = filter_var($data["id"] ?? null, FILTER_VALIDATE_INT);
    $labId = filter_var($data["lab_id"] ?? null, FILTER_VALIDATE_INT);

    $name = trim($data["instrument_name"] ?? "");
    $manufacturer = trim($data["manufacturer"] ?? "");
    $model = trim($data["model_number"] ?? "");
    $serial = trim($data["serial_number"] ?? "");

    $installationDate = trim($data["installation_date"] ?? "");
    $warrantyStatus = trim($data["warranty_status"] ?? "");
    $warrantyExpiry = trim($data["warranty_expiry_date"] ?? "");

    $status = trim($data["status"] ?? "Active");

    if (!$id || !$labId || $name === "" || $serial === "") {
        instrumentResponse(
            false,
            "Instrument ID, lab, name, and serial number are required.",
            null,
            400
        );
    }

    if (!in_array($status, ["Active", "Inactive"], true)) {
        instrumentResponse(false, "Invalid instrument status.", null, 400);
    }

    foreach ([
        $installationDate,
        $warrantyExpiry
    ] as $date) {

        if (
            $date !== "" &&
            !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)
        ) {
            instrumentResponse(
                false,
                "Dates must use YYYY-MM-DD format.",
                null,
                400
            );
        }
    }

    $installationDate = $installationDate !== ""
        ? $installationDate
        : null;

    $warrantyExpiry = $warrantyExpiry !== ""
        ? $warrantyExpiry
        : null;

    try {

        $stmt = $conn->prepare("
            UPDATE instrument_master
            SET
                lab_id = ?,
                instrument_name = ?,
                manufacturer = ?,
                model_number = ?,
                serial_number = ?,
                installation_date = ?,
                warranty_status = ?,
                warranty_expiry_date = ?,
                status = ?
            WHERE id = ?
        ");

        $stmt->bind_param(
            "issssssssi",
            $labId,
            $name,
            $manufacturer,
            $model,
            $serial,
            $installationDate,
            $warrantyStatus,
            $warrantyExpiry,
            $status,
            $id
        );

        $stmt->execute();

        instrumentResponse(true, "Instrument updated successfully.");

    } catch (mysqli_sql_exception $e) {

        error_log("Instrument update error: " . $e->getMessage());

        if ((int)$e->getCode() === 1062) {
            instrumentResponse(
                false,
                "This serial number already exists.",
                null,
                409
            );
        }

        instrumentResponse(
            false,
            "Unable to update the instrument.",
            null,
            500
        );
    }
}

instrumentResponse(false, "Method not allowed.", null, 405);