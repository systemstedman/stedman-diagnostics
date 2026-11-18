<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function requireLogin(): void
{
    if (empty($_SESSION["user_id"])) {
        header("Location: login.php");
        exit;
    }
}

function requireRole($allowedRoles): void
{
    requireLogin();

    $currentRole = trim((string)($_SESSION["role"] ?? ""));

    if (!in_array($currentRole, $allowedRoles, true)) {
        http_response_code(403);

        exit(
            "Access denied. You do not have permission to access this page."
        );
    }
}

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