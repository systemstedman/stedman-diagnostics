<?php

require_once __DIR__ . "/config/database.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

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
                    password_hash,
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

            $message = "Administrator account created successfully. Delete create-admin.php now.";

        } catch (mysqli_sql_exception $e) {

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
    <title>Create Administrator</title>
</head>

<body>

    <h2>Create Administrator Account</h2>

    <p>
        Use this page only for initial administrator setup.
    </p>

    <?php if ($message !== ""): ?>
        <p>
            <?= htmlspecialchars($message, ENT_QUOTES, "UTF-8") ?>
        </p>
    <?php endif; ?>

    <form method="POST">

        <p>
            <label>Username</label><br>
            <input
                type="text"
                name="username"
                required
            >
        </p>

        <p>
            <label>Full Name</label><br>
            <input
                type="text"
                name="full_name"
                required
            >
        </p>

        <p>
            <label>Password — at least 12 characters</label><br>
            <input
                type="password"
                name="password"
                minlength="12"
                required
            >
        </p>

        <button type="submit">
            Create Admin
        </button>

    </form>

</body>
</html>