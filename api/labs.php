<?php

header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../config/database.php";

requireApiLogin();

function labResponse($success, $message, $data = null, $statusCode = 200)
{
    http_response_code($statusCode);

    echo json_encode([
        "success" => $success,
        "message" => $message,
        "data" => $data
    ]);

    exit;
}

$method = $_SERVER["REQUEST_METHOD"];
$cachedLabRequestData = null;

function getLabRequestData(): array
{
    global $cachedLabRequestData;

    if ($cachedLabRequestData !== null) {
        return $cachedLabRequestData;
    }

    $contentType = $_SERVER["CONTENT_TYPE"] ?? "";

    if (stripos($contentType, "application/json") !== false) {
        $data = json_decode(file_get_contents("php://input"), true);
        $cachedLabRequestData = is_array($data) ? $data : [];
    } else {
        $cachedLabRequestData = $_POST;
    }

    return $cachedLabRequestData;
}

if ($method === "POST") {
    $override = strtoupper(trim((string)($_SERVER["HTTP_X_HTTP_METHOD_OVERRIDE"] ?? "")));
    if ($override === "") {
        $bodyData = getLabRequestData();
        $override = strtoupper(trim((string)($bodyData["_method"] ?? "")));
    }
    if ($override === "PUT") {
        $method = "PUT";
    }
}

$isAdmin = (($_SESSION["role"] ?? "") === "Admin");

/*
|--------------------------------------------------------------------------
| GET: Retrieve laboratories
|--------------------------------------------------------------------------
*/

if ($method === "GET") {

    $all = isset($_GET["all"]) && $_GET["all"] === "1";

    if ($all && !$isAdmin) {
        labResponse(false, "Admin access required.", null, 403);
    }

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
    ";

    if (!$all) {
        $sql .= " WHERE status = 'Active'";
    }

    $sql .= " ORDER BY diagnostic_lab_name ASC";

    $result = $conn->query($sql);

    labResponse(
        true,
        "Laboratories retrieved successfully.",
        $result->fetch_all(MYSQLI_ASSOC)
    );
}

/*
|--------------------------------------------------------------------------
| POST: Add laboratory
|--------------------------------------------------------------------------
*/

if ($method === "POST") {

    if (!$isAdmin) {
        labResponse(false, "Admin access required.", null, 403);
    }

    $data = getLabRequestData();

    $name = trim($data["diagnostic_lab_name"] ?? "");
    $contact = trim($data["lab_incharge_contact"] ?? "");
    $mobile = trim($data["mobile_number"] ?? "");
    $email = trim($data["email_address"] ?? "");
    $city = trim($data["city_location"] ?? "");
    $address = trim($data["full_address"] ?? "");
    $status = trim($data["status"] ?? "Active");

    if ($name === "") {
        labResponse(false, "Lab name is required.", null, 400);
    }

    if (!in_array($status, ["Active", "Inactive"], true)) {
        labResponse(false, "Invalid lab status.", null, 400);
    }

    if ($email !== "" && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        labResponse(false, "Enter a valid email address.", null, 400);
    }

    $stmt = $conn->prepare("
        INSERT INTO lab_master (
            diagnostic_lab_name,
            lab_incharge_contact,
            mobile_number,
            email_address,
            city_location,
            full_address,
            status
        )
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "sssssss",
        $name,
        $contact,
        $mobile,
        $email,
        $city,
        $address,
        $status
    );

    try {
        $stmt->execute();

        labResponse(
            true,
            "Laboratory added successfully.",
            ["id" => $stmt->insert_id]
        );

    } catch (mysqli_sql_exception $e) {
        error_log("Lab insert error: " . $e->getMessage());

        labResponse(
            false,
            "Unable to add the laboratory. Check the submitted details.",
            null,
            500
        );
    }
}

/*
|--------------------------------------------------------------------------
| PUT: Update laboratory
|--------------------------------------------------------------------------
*/

if ($method === "PUT") {

    if (!$isAdmin) {
        labResponse(false, "Admin access required.", null, 403);
    }

    $data = getLabRequestData();

    $id = filter_var($data["id"] ?? null, FILTER_VALIDATE_INT);

    $name = trim($data["diagnostic_lab_name"] ?? "");
    $contact = trim($data["lab_incharge_contact"] ?? "");
    $mobile = trim($data["mobile_number"] ?? "");
    $email = trim($data["email_address"] ?? "");
    $city = trim($data["city_location"] ?? "");
    $address = trim($data["full_address"] ?? "");
    $status = trim($data["status"] ?? "Active");

    if (!$id || $name === "") {
        labResponse(false, "Lab ID and lab name are required.", null, 400);
    }

    if (!in_array($status, ["Active", "Inactive"], true)) {
        labResponse(false, "Invalid lab status.", null, 400);
    }

    if ($email !== "" && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        labResponse(false, "Enter a valid email address.", null, 400);
    }

    $stmt = $conn->prepare("
        UPDATE lab_master
        SET
            diagnostic_lab_name = ?,
            lab_incharge_contact = ?,
            mobile_number = ?,
            email_address = ?,
            city_location = ?,
            full_address = ?,
            status = ?
        WHERE id = ?
    ");

    $stmt->bind_param(
        "sssssssi",
        $name,
        $contact,
        $mobile,
        $email,
        $city,
        $address,
        $status,
        $id
    );

    $stmt->execute();

    if ($stmt->affected_rows === 0) {

        $check = $conn->prepare(
            "SELECT id FROM lab_master WHERE id = ?"
        );

        $check->bind_param("i", $id);
        $check->execute();

        if (!$check->get_result()->fetch_assoc()) {
            labResponse(false, "Laboratory not found.", null, 404);
        }
    }

    labResponse(true, "Laboratory updated successfully.");
}

labResponse(false, "Method not allowed.", null, 405);