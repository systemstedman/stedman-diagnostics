<?php

/*
|--------------------------------------------------------------------------
| Stedman Diagnostics - Complete Service API
|--------------------------------------------------------------------------
*/

header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../config/database.php";


/*
|--------------------------------------------------------------------------
| JSON Response Helper
|--------------------------------------------------------------------------
*/

function failCompletion(string $message, int $code = 400): void
{
    http_response_code($code);

    echo json_encode([
        "success" => false,
        "message" => $message
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/

requireApiLogin();


/*
|--------------------------------------------------------------------------
| Request Method
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Allow: POST");

    failCompletion("POST method required.", 405);
}


/*
|--------------------------------------------------------------------------
| Read Input Fields
|--------------------------------------------------------------------------
*/

$id = filter_var(
    $_POST["service_call_id"] ?? null,
    FILTER_VALIDATE_INT
);

$visitInRaw = trim(
    (string) ($_POST["visit_in_time"] ?? "")
);

$visitOutRaw = trim(
    (string) ($_POST["visit_out_time"] ?? "")
);

$problemReported = trim((string)($_POST["problem_reported"] ?? ""));

$observations = trim(
    (string) ($_POST["observations"] ?? "")
);

$actionsTaken = trim(
    (string) ($_POST["actions_taken"] ?? "")
);

$sparesRequired = trim(
    (string) ($_POST["spares_required"] ?? "No")
);

$sparesToBeReplaced = trim(
    (string) ($_POST["spares_to_be_replaced"] ?? "")
);

$sparesDetails = trim(
    (string) ($_POST["spares_details"] ?? "")
);

if (!in_array($sparesRequired, ["Yes", "No"], true)) {
    failCompletion("Invalid spares/material selection.");
}

if ($sparesRequired === "Yes" && !in_array($sparesToBeReplaced, ["Yes", "No"], true)) {
    failCompletion("Please select whether the spares need to be replaced.");
}

if ($sparesRequired === "Yes" && $sparesDetails === "") {
    failCompletion("Please enter the spares or material details.");
}

$sparesReplaced = $sparesRequired === "Yes" ? $sparesToBeReplaced : null;

$sparesStatus = $sparesRequired === "No"
    ? "Not Required"
    : ($sparesReplaced === "Yes" ? "Required - Replaced" : "Required - To Be Replaced");

$shouldComplete = $sparesRequired === "No" || $sparesReplaced === "Yes";


/*
|--------------------------------------------------------------------------
| Closure User
|--------------------------------------------------------------------------
|
| Use the authenticated session for the closure identity.
| Do not trust a client-supplied user identity.
|
*/

$closedBy = trim((string)($_SESSION["username"] ?? ""));


/*
|--------------------------------------------------------------------------
| Validate Required Fields
|--------------------------------------------------------------------------
*/

if (
    !$id ||
    $visitInRaw === "" ||
    $visitOutRaw === "" ||
    $problemReported === "" ||
    $observations === "" ||
    $actionsTaken === "" ||
    $closedBy === ""
) {
    failCompletion(
        "Please complete all required visit and closure fields."
    );
}



/*
|--------------------------------------------------------------------------
| Validate Visit Times
|--------------------------------------------------------------------------
*/

$visitInTimestamp = strtotime($visitInRaw);
$visitOutTimestamp = strtotime($visitOutRaw);

if (
    $visitInTimestamp === false ||
    $visitOutTimestamp === false
) {
    failCompletion("Please enter valid visit times.");
}

if ($visitOutTimestamp < $visitInTimestamp) {
    failCompletion(
        "Visit out time must be the same as or later than visit in time."
    );
}

$visitIn = date(
    "Y-m-d H:i:s",
    $visitInTimestamp
);

$visitOut = date(
    "Y-m-d H:i:s",
    $visitOutTimestamp
);


/*
|--------------------------------------------------------------------------
| Validate Signed Report
|--------------------------------------------------------------------------
*/

$file = $_FILES["signed_report"] ?? null;
$hasUploadedReport = $file && $file["error"] !== UPLOAD_ERR_NO_FILE;

// A signed report is required only when the call is actually completed.
if ($shouldComplete && !$file) {
    failCompletion("Please upload the signed service report.");
}

if ($shouldComplete && $file && $file["error"] !== UPLOAD_ERR_OK) {
    $uploadErrors = [
        UPLOAD_ERR_INI_SIZE => "The uploaded report exceeds the server upload limit.",
        UPLOAD_ERR_FORM_SIZE => "The uploaded report exceeds the permitted form size.",
        UPLOAD_ERR_PARTIAL => "The report was only partially uploaded.",
        UPLOAD_ERR_NO_FILE => "Please upload the signed service report.",
        UPLOAD_ERR_NO_TMP_DIR => "The server temporary upload directory is missing.",
        UPLOAD_ERR_CANT_WRITE => "The server could not write the uploaded report.",
        UPLOAD_ERR_EXTENSION => "The server blocked the report upload."
    ];
    failCompletion($uploadErrors[$file["error"]] ?? "Report upload failed.");
}

$extension = null;
$destination = null;
$relativePath = null;

if ($shouldComplete) {
    if (!$file || $file["size"] <= 0) {
        failCompletion("The uploaded report is empty.");
    }

    if ($file["size"] > 10 * 1024 * 1024) {
        failCompletion("The report must be 10 MB or smaller.");
    }

    $extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    $allowedMimeTypes = [
        "pdf" => ["application/pdf"],
        "jpg" => ["image/jpeg"],
        "jpeg" => ["image/jpeg"],
        "png" => ["image/png"],
        "doc" => ["application/msword", "application/CDFV2", "application/x-ole-storage"],
        "docx" => ["application/vnd.openxmlformats-officedocument.wordprocessingml.document", "application/zip", "application/x-zip"]
    ];

    if (!array_key_exists($extension, $allowedMimeTypes)) {
        failCompletion("Allowed formats: PDF, JPG, JPEG, PNG, DOC, DOCX.");
    }

    if (!is_uploaded_file($file["tmp_name"]) || !is_readable($file["tmp_name"])) {
        failCompletion("The uploaded report could not be verified.");
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file["tmp_name"]);

    if ($mimeType === false || !in_array($mimeType, $allowedMimeTypes[$extension], true)) {
        failCompletion("The report file content does not match its file type.");
    }
}

/*
|--------------------------------------------------------------------------
| Verify Service Call
|--------------------------------------------------------------------------
*/

try {

    $stmt = $conn->prepare("
        SELECT id, call_id, status, signed_report_url, closed_by,
               spares_status, spares_details, observations, actions_taken
        FROM service_calls
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->bind_param("i", $id);
    $stmt->execute();

    $call = $stmt->get_result()->fetch_assoc();

    $stmt->close();

} catch (Throwable $e) {

    error_log(
        "Service call lookup error: " . $e->getMessage()
    );

    failCompletion(
        "Could not verify the service call.",
        500
    );
}


if (!$call) {
    failCompletion("Service call not found.", 404);
}

if (strcasecmp((string) $call["status"], "Completed") === 0) {
    failCompletion("This service call is already completed.");
}

$relativePath = $call["signed_report_url"] ?? null;


/*
|--------------------------------------------------------------------------
| Save Uploaded File (completed calls only)
|--------------------------------------------------------------------------
*/

if ($shouldComplete) {
    $uploadDirectory = dirname(__DIR__) . "/uploads";

    if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0755, true)) {
        failCompletion("Could not create the uploads directory.", 500);
    }

    if (!is_writable($uploadDirectory)) {
        failCompletion("The uploads directory is not writable.", 500);
    }

    $safeCallId = preg_replace("/[^A-Za-z0-9_-]/", "_", (string) $call["call_id"]);
    $filename = "signed_report_" . $safeCallId . "_" . bin2hex(random_bytes(8)) . "." . $extension;
    $destination = $uploadDirectory . "/" . $filename;
    $relativePath = "uploads/" . $filename;

    if (!move_uploaded_file($file["tmp_name"], $destination)) {
        failCompletion("Could not save the uploaded report.", 500);
    }
}

/*
|--------------------------------------------------------------------------
| Update Service Call
|--------------------------------------------------------------------------
*/

try {

    $conn->begin_transaction();

    /*
     * Recheck status while locking the row to avoid
     * completing the same call twice concurrently.
     */

    $stmt = $conn->prepare("
        SELECT id, call_id, status, closed_by
        FROM service_calls
        WHERE id = ?
        FOR UPDATE
    ");

    $stmt->bind_param("i", $id);
    $stmt->execute();

    $lockedCall = $stmt->get_result()->fetch_assoc();

    $stmt->close();

    if (!$lockedCall) {
        throw new RuntimeException("Service call not found.");
    }

    if (
        strcasecmp(
            (string) $lockedCall["status"],
            "Completed"
        ) === 0
    ) {
        throw new RuntimeException(
            "This service call is already completed."
        );
    }

    $status = $shouldComplete ? "Completed" : "Spares to be Replaced";
    $finalClosedBy = $shouldComplete ? $closedBy : ($lockedCall["closed_by"] ?? null);

    $stmt = $conn->prepare("
        UPDATE service_calls
        SET
            status = ?,
            visit_in_time = ?,
            visit_out_time = ?,
            problem_reported = ?,
            observations = ?,
            actions_taken = ?,
            spares_required = ?,
            spares_replaced = ?,
            spares_status = ?,
            spares_details = ?,
            signed_report_url = ?,
            closed_by = ?
        WHERE id = ?
    ");

    $stmt->bind_param(
        "ssssssssssssi",
        $status,
        $visitIn,
        $visitOut,
        $problemReported,
        $observations,
        $actionsTaken,
        $sparesRequired,
        $sparesReplaced,
        $sparesStatus,
        $sparesDetails,
        $relativePath,
        $finalClosedBy,
        $id
    );

    $stmt->execute();

    $stmt->close();

    $conn->commit();

} catch (Throwable $e) {

    try {
        $conn->rollback();
    } catch (Throwable $rollbackError) {
        error_log(
            "Service completion rollback error: " .
            $rollbackError->getMessage()
        );
    }

    if (is_file($destination)) {
        unlink($destination);
    }

    if (
        $e instanceof RuntimeException &&
        in_array(
            $e->getMessage(),
            [
                "Service call not found.",
                "This service call is already completed."
            ],
            true
        )
    ) {
        failCompletion($e->getMessage(), 409);
    }

    error_log(
        "Service completion database error: " .
        $e->getMessage()
    );

    failCompletion(
        "Could not update the call. Check the database schema and try again.",
        500
    );
}


/*
|--------------------------------------------------------------------------
| Success Response
|--------------------------------------------------------------------------
*/

echo json_encode([
    "success" => true,
    "message" => $shouldComplete
        ? "Service call completed successfully."
        : "Call saved as Spares to be Replaced and remains active.",
    "call_id" => $call["call_id"],
    "status" => $status,
    "report" => $relativePath
]);

exit;
