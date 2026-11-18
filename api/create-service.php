<?php

header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../config/database.php";

requireApiLogin();

function createCallResponse(
    bool $success,
    string $message,
    array $extra = [],
    int $statusCode = 200
): void {
    http_response_code($statusCode);

    echo json_encode(array_merge([
        "success" => $success,
        "message" => $message
    ], $extra));

    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    createCallResponse(false, "POST method required.", [], 405);
}

$labId = filter_var(
    $_POST["lab_id"] ?? null,
    FILTER_VALIDATE_INT
);

$instrumentRaw = trim($_POST["instrument_id"] ?? "");
$instrumentId = null;

if ($instrumentRaw !== "") {
    $instrumentId = filter_var(
        $instrumentRaw,
        FILTER_VALIDATE_INT
    );

    if (!$instrumentId) {
        createCallResponse(false, "Invalid instrument selected.", [], 400);
    }
}

$callType = trim($_POST["call_type"] ?? "");
$problem = trim($_POST["problem_reported"] ?? "");
$contactPerson = trim($_POST["contact_person"] ?? "");
$mobile = trim($_POST["mobile"] ?? "");

if (!$labId) {
    createCallResponse(false, "Please select a laboratory.", [], 400);
}

if ($contactPerson === "") {
    createCallResponse(false, "Contact person is required.", [], 400);
}

if ($mobile === "") {
    createCallResponse(false, "Contact mobile is required.", [], 400);
}

if ($problem === "") {
    createCallResponse(false, "Problem reported is required.", [], 400);
}

if (!in_array(
    $callType,
    ["Application Call", "Service Call"],
    true
)) {
    createCallResponse(false, "Invalid call type.", [], 400);
}

/*
|--------------------------------------------------------------------------
| Validate active lab
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT id
    FROM lab_master
    WHERE id = ?
      AND status = 'Active'
");

$stmt->bind_param("i", $labId);
$stmt->execute();

$lab = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$lab) {
    createCallResponse(
        false,
        "The selected laboratory is missing or inactive.",
        [],
        400
    );
}

/*
|--------------------------------------------------------------------------
| Validate selected instrument
|--------------------------------------------------------------------------
*/

if ($instrumentId !== null) {
    $stmt = $conn->prepare("
        SELECT id
        FROM instrument_master
        WHERE id = ?
          AND lab_id = ?
          AND status = 'Active'
    ");

    $stmt->bind_param("ii", $instrumentId, $labId);
    $stmt->execute();

    $instrument = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$instrument) {
        createCallResponse(
            false,
            "The selected instrument does not belong to this active laboratory.",
            [],
            400
        );
    }
}

/*
|--------------------------------------------------------------------------
| Create unique call ID
|--------------------------------------------------------------------------
*/

$callId = "SD-" . date("Ymd-His") . "-" .
    strtoupper(bin2hex(random_bytes(3)));

$status = "Pending";
$loggedDatetime = date("Y-m-d H:i:s");
$loggedBy = trim((string)($_SESSION["username"] ?? ""));

if ($loggedBy === "") {
    createCallResponse(false, "Unable to identify the logged-in user.", [], 401);
}

try {
    $stmt = $conn->prepare("
        INSERT INTO service_calls (
            call_id,
            status,
            lab_id,
            instrument_id,
            call_type,
            problem_reported,
            contact_person,
            mobile,
            logged_datetime,
            logged_by
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "ssiissssss",
        $callId,
        $status,
        $labId,
        $instrumentId,
        $callType,
        $problem,
        $contactPerson,
        $mobile,
        $loggedDatetime,
        $loggedBy
    );

    $stmt->execute();

    $newId = $stmt->insert_id;

    echo json_encode([
        "success" => true,
        "message" => "Service call created successfully.",
        "id" => $newId,
        "call_id" => $callId
    ]);

} catch (Throwable $e) {
    error_log("Create service call error: " . $e->getMessage());

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Unable to save the service call. Check the PHP error log."
    ]);
}