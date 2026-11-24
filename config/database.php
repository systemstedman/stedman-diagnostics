<?php

if (basename($_SERVER["SCRIPT_FILENAME"] ?? "") === basename(__FILE__)) {
    http_response_code(403);
    exit("Forbidden");
}

date_default_timezone_set("Asia/Kolkata");

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

/*
|--------------------------------------------------------------------------
| Bluehost MySQL Database Configuration
|--------------------------------------------------------------------------
| Update the 4 values below using the database & user you created in
| Bluehost cPanel -> MySQL Databases:
|   - $host     : Keep "localhost" on Bluehost shared hosting.
|   - $username : Format is usually "cpaneluser_dbuser"
|   - $password : The strong password set for the MySQL user
|   - $database : Format is usually "cpaneluser_stedman_diagnostics"
|--------------------------------------------------------------------------
*/

$host     = "localhost";
$username = "YOUR_CPANELUSER_DBUSER";
$password = "YOUR_DB_PASSWORD";
$database = "YOUR_CPANELUSER_STEDMAN_DIAGNOSTICS";

try {
    $conn = new mysqli(
        $host,
        $username,
        $password,
        $database
    );

    $conn->set_charset("utf8mb4");

    // Align MySQL CURDATE() / timestamps with Asia/Kolkata (IST)
    // because Bluehost servers default to US Mountain Time.
    $conn->query("SET time_zone = '+05:30'");

} catch (Throwable $e) {
    error_log("Database connection error: " . $e->getMessage());

    http_response_code(500);

    die("Database connection failed. Please check config/database.php and Bluehost MySQL privileges.");
}
