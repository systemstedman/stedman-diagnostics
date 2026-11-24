<?php

header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../config/database.php";

requireApiLogin();


/*
|--------------------------------------------------------------------------
| JSON RESPONSE
|--------------------------------------------------------------------------
*/

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


/*
|--------------------------------------------------------------------------
| REQUEST METHOD VALIDATION
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    createCallResponse(
        false,
        "POST method required.",
        [],
        405
    );
}


/*
|--------------------------------------------------------------------------
| GET FORM DATA
|--------------------------------------------------------------------------
*/

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

    if (!$instrumentId || $instrumentId <= 0) {

        createCallResponse(
            false,
            "Invalid instrument selected.",
            [],
            400
        );
    }
}

$callType = trim($_POST["call_type"] ?? "");

$problem = trim($_POST["problem_reported"] ?? "");


/*
|--------------------------------------------------------------------------
| VALIDATE REQUIRED FIELDS
|--------------------------------------------------------------------------
*/

if (!$labId || $labId <= 0) {

    createCallResponse(
        false,
        "Please select a laboratory.",
        [],
        400
    );
}

if ($problem === "") {

    createCallResponse(
        false,
        "Problem reported is required.",
        [],
        400
    );
}

if (!in_array(
    $callType,
    ["Application Call", "Service Call"],
    true
)) {

    createCallResponse(
        false,
        "Invalid call type.",
        [],
        400
    );
}


/*
|--------------------------------------------------------------------------
| VALIDATE LOGGED-IN USER
|--------------------------------------------------------------------------
*/

$loggedBy = trim(
    (string)($_SESSION["username"] ?? "")
);

if ($loggedBy === "") {

    createCallResponse(
        false,
        "Unable to identify the logged-in user.",
        [],
        401
    );
}


/*
|--------------------------------------------------------------------------
| VALIDATE ACTIVE LAB & FETCH CONTACT DETAILS FROM DB
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT
        id,
        lab_incharge_contact,
        mobile_number
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

$labContactPerson = trim((string)($lab["lab_incharge_contact"] ?? ""));
$labMobileNumber = trim((string)($lab["mobile_number"] ?? ""));

$contactPerson = $labContactPerson !== "" ? $labContactPerson : "Not Available";
$mobile = $labMobileNumber !== "" ? $labMobileNumber : "Not Available";


/*
|--------------------------------------------------------------------------
| VALIDATE SELECTED INSTRUMENT
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

    $stmt->bind_param(
        "ii",
        $instrumentId,
        $labId
    );

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
| CREATE SERVICE CALL
|--------------------------------------------------------------------------
*/

$status = "Pending";

$loggedDatetime = date("Y-m-d H:i:s");


try {

    /*
    |--------------------------------------------------------------------------
    | START TRANSACTION
    |--------------------------------------------------------------------------
    */

    $conn->begin_transaction();


    /*
    |--------------------------------------------------------------------------
    | TEMPORARY UNIQUE CALL ID
    |--------------------------------------------------------------------------
    |
    | A temporary ID is inserted first because call_id is NOT NULL
    | and UNIQUE in the database.
    |
    | After insertion, the actual AUTO_INCREMENT ID is used
    | as the final Call ID.
    |
    */

    $temporaryCallId = "TEMP-" . bin2hex(random_bytes(16));


    /*
    |--------------------------------------------------------------------------
    | INSERT SERVICE CALL
    |--------------------------------------------------------------------------
    */

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
        $temporaryCallId,
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


    /*
    |--------------------------------------------------------------------------
    | GET GENERATED DATABASE ID
    |--------------------------------------------------------------------------
    */

    $newId = $stmt->insert_id;

    $stmt->close();


    /*
    |--------------------------------------------------------------------------
    | GENERATE CONTINUOUS SERIAL CALL ID
    |--------------------------------------------------------------------------
    |
    | Example:
    | Database ID 1 = Call ID 1
    | Database ID 2 = Call ID 2
    | Database ID 3 = Call ID 3
    |
    */

    $callId = (string)$newId;


    /*
    |--------------------------------------------------------------------------
    | UPDATE FINAL CALL ID
    |--------------------------------------------------------------------------
    */

    $updateStmt = $conn->prepare("
        UPDATE service_calls
        SET call_id = ?
        WHERE id = ?
    ");

    $updateStmt->bind_param(
        "si",
        $callId,
        $newId
    );

    $updateStmt->execute();

    $updateStmt->close();


    /*
    |--------------------------------------------------------------------------
    | COMMIT TRANSACTION
    |--------------------------------------------------------------------------
    */

    $conn->commit();


    /*
    |--------------------------------------------------------------------------
    | SUCCESS RESPONSE
    |--------------------------------------------------------------------------
    */

    createCallResponse(
        true,
        "Service call created successfully.",
        [
            "id" => $newId,
            "call_id" => $callId
        ]
    );


} catch (Throwable $e) {

    /*
    |--------------------------------------------------------------------------
    | ROLLBACK IF ERROR OCCURS
    |--------------------------------------------------------------------------
    */

    $conn->rollback();

    error_log(
        "Create service call error: " . $e->getMessage()
    );

    createCallResponse(
        false,
        "Unable to save the service call. Check the PHP error log.",
        [],
        500
    );

}

?>