<?php

if (session_status() === PHP_SESSION_NONE) {
    $isHttps = (
        (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off") ||
        (strtolower((string)($_SERVER["HTTP_X_FORWARDED_PROTO"] ?? "")) === "https") ||
        ((int)($_SERVER["SERVER_PORT"] ?? 0) === 443)
    );

    session_set_cookie_params([
        "lifetime" => 0,
        "path" => "/",
        "domain" => "",
        "secure" => $isHttps,
        "httponly" => true,
        "samesite" => "Lax"
    ]);

    session_start();
}

/*
|--------------------------------------------------------------------------
| Require Login
|--------------------------------------------------------------------------
*/

function requireLogin(): void
{
    if (empty($_SESSION["user_id"])) {
        header("Location: login.php");
        exit;
    }
}

/*
|--------------------------------------------------------------------------
| Require a Specific Role
|--------------------------------------------------------------------------
*/

function requireRole($allowedRoles): void
{
    requireLogin();

    $currentRole = trim((string)($_SESSION["role"] ?? ""));

    if (!in_array($currentRole, $allowedRoles, true)) {
        http_response_code(403);

        exit("Access denied. You do not have permission to access this page.");
    }
}

/*
|--------------------------------------------------------------------------
| API Authentication
|--------------------------------------------------------------------------
| API requests must receive JSON errors, not HTML redirects.
|--------------------------------------------------------------------------
*/

function requireApiLogin(): void
{
    if (empty($_SESSION["user_id"])) {
        http_response_code(401);

        header("Content-Type: application/json; charset=utf-8");

        echo json_encode([
            "success" => false,
            "message" => "Your session has expired. Please log in again."
        ]);

        exit;
    }
}

/*
|--------------------------------------------------------------------------
| API Role Authorization
|--------------------------------------------------------------------------
*/

function requireApiRole($allowedRoles): void
{
    requireApiLogin();

    $currentRole = trim((string)($_SESSION["role"] ?? ""));

    if (!in_array($currentRole, $allowedRoles, true)) {
        http_response_code(403);

        header("Content-Type: application/json; charset=utf-8");

        echo json_encode([
            "success" => false,
            "message" => "You do not have permission to perform this action."
        ]);

        exit;
    }
}