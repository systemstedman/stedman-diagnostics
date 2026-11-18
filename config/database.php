<?php

date_default_timezone_set("Asia/Kolkata");

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$host = "localhost";
$username = "root";
$password = "";
$database = "stedman_diagnostics";

try {
    $conn = new mysqli(
        $host,
        $username,
        $password,
        $database
    );

    $conn->set_charset("utf8mb4");

} catch (Throwable $e) {
    error_log("Database connection error: " . $e->getMessage());

    http_response_code(500);

    die("Database connection failed. Check MySQL and database configuration.");
}