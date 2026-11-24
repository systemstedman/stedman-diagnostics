<?php

require_once __DIR__ . "/config/database.php";

$message = "";
$adminAlreadyExists = false;

try {
    $check = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role = 'Admin'");
    $row = $check ? $check->fetch_assoc() : null;
    $adminAlreadyExists = ((int)($row["total"] ?? 0)) > 0;
} catch (Throwable $e) {
    error_log("Create admin check error: " . $e->getMessage());
    $message = "Could not verify existing administrator accounts. Make sure database/schema.sql has been imported.";
}

if ($adminAlreadyExists) {
    http_response_code(403);
    $message = "An Administrator account already exists. For security, this setup page is locked. Please delete create-admin.php from your Bluehost server.";
} elseif ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST["username"] ?? "");
    $fullName = trim($_POST["full_name"] ?? "");
    $password = $_POST["password"] ?? "";

    if (
        $username === "" ||
        $fullName === "" ||
        strlen($password) < 12
    ) {

        $message = "Enter all fields. Password must be at least 12 characters.";

    } else {

        $passwordHash = password_hash(
            $password,
            PASSWORD_DEFAULT
        );

        try {

            $stmt = $conn->prepare("
                INSERT INTO users (
                    username,
                    password,
                    full_name,
                    role,
                    status
                )
                VALUES (?, ?, ?, 'Admin', 'Active')
            ");

            $stmt->bind_param(
                "sss",
                $username,
                $passwordHash,
                $fullName
            );

            $stmt->execute();

            $adminAlreadyExists = true;
            $message = "Administrator account created successfully. Delete create-admin.php from Bluehost now and log in via login.php.";

        } catch (mysqli_sql_exception $e) {

            error_log("Create admin insert error: " . $e->getMessage());
            $message = "Could not create account. The username may already exist.";

        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Administrator | Stedman Diagnostics</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f1f5f9; padding: 40px 20px; color: #1e293b; }
        .card { max-width: 440px; margin: 0 auto; background: #fff; padding: 28px; border-radius: 12px; box-shadow: 0 6px 24px rgba(0,0,0,0.08); }
        label { display: block; font-weight: bold; margin: 14px 0 6px; }
        input { width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box; }
        button { width: 100%; margin-top: 20px; padding: 12px; background: #12345a; color: #fff; border: 0; border-radius: 6px; font-weight: bold; cursor: pointer; }
        .msg { padding: 12px; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 6px; margin-bottom: 16px; }
    </style>
</head>

<body>
    <div class="card">
        <h2>Create Administrator Account</h2>
        <p>Use this page only for initial administrator setup on a fresh database, then delete <code>create-admin.php</code>.</p>

        <?php if ($message !== ""): ?>
            <div class="msg">
                <?= htmlspecialchars($message, ENT_QUOTES, "UTF-8") ?>
            </div>
        <?php endif; ?>

        <?php if (!$adminAlreadyExists): ?>
            <form method="POST">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required>

                <label for="full_name">Full Name</label>
                <input type="text" id="full_name" name="full_name" required>

                <label for="password">Password (at least 12 characters)</label>
                <input type="password" id="password" name="password" minlength="12" required>

                <button type="submit">Create Admin</button>
            </form>
        <?php else: ?>
            <p><a href="login.php">Go to Login Page</a></p>
        <?php endif; ?>
    </div>
</body>
</html>
