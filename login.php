<?php

/*
|--------------------------------------------------------------------------
| Session Configuration
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {

    $isHttps = (
        !empty($_SERVER["HTTPS"]) &&
        $_SERVER["HTTPS"] !== "off"
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

require_once __DIR__ . "/config/database.php";

$error = "";

/*
|--------------------------------------------------------------------------
| Redirect Already Logged-in Users
|--------------------------------------------------------------------------
*/

if (!empty($_SESSION["user_id"])) {
    header("Location: index.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| Login Processing
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($username === "" || $password === "") {

        $error = "Please enter your username and password.";

    } else {

        try {

            $stmt = $conn->prepare("
                SELECT
                    id,
                    username,
                    password,
                    full_name,
                    role,
                    status
                FROM users
                WHERE username = ?
                LIMIT 1
            ");

            $stmt->bind_param("s", $username);
            $stmt->execute();

            $result = $stmt->get_result();
            $user = $result->fetch_assoc();

            $stmt->close();

            /*
            |--------------------------------------------------------------------------
            | Verify Credentials
            |--------------------------------------------------------------------------
            */

            $passwordValid = false;

            if ($user && $user["status"] === "Active") {

                /*
                 * Support existing plaintext passwords and
                 * passwords stored using PHP password_hash().
                 *
                 * Plaintext passwords should eventually be migrated
                 * to password_hash() for better security.
                 */

                if (password_verify($password, $user["password"])) {

                    $passwordValid = true;

                } elseif (
                    hash_equals(
                        (string) $user["password"],
                        (string) $password
                    )
                ) {

                    $passwordValid = true;
                }
            }

            if ($user && $passwordValid) {

                /*
                |--------------------------------------------------------------------------
                | Regenerate Session ID
                |--------------------------------------------------------------------------
                */

                session_regenerate_id(true);

                /*
                |--------------------------------------------------------------------------
                | Store Authenticated User Information
                |--------------------------------------------------------------------------
                */

                $_SESSION["user_id"] = (int) $user["id"];
                $_SESSION["username"] = $user["username"];
                $_SESSION["full_name"] = $user["full_name"];
                $_SESSION["role"] = $user["role"];

                /*
                |--------------------------------------------------------------------------
                | Redirect to Dashboard
                |--------------------------------------------------------------------------
                */

                header("Location: index.php");
                exit;

            } else {

                $error = "Invalid username or password, or account inactive.";
            }

        } catch (Throwable $e) {

            error_log("Login error: " . $e->getMessage());

            $error = "Unable to process login. Please try again.";
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Login | Stedman Pharmaceuticals Pvt Ltd (Diagnostics Divison)</title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f1f5f9;
            font-family: Arial, sans-serif;
        }

        .login-card {
            width: 100%;
            max-width: 420px;
            padding: 35px;
            background: #ffffff;
            border-radius: 14px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
        }

        .logo {
            text-align: center;
            color: #12345a;
            font-size: 25px;
            font-weight: bold;
        }

        .subtitle {
            text-align: center;
            color: #64748b;
            margin: 10px 0 25px;
        }

        label {
            display: block;
            margin: 15px 0 7px;
            font-weight: bold;
            color: #334155;
        }

        input {
            width: 100%;
            padding: 12px;
            border: 1px solid #cbd5e1;
            border-radius: 7px;
            font-size: 15px;
        }

        button {
            width: 100%;
            margin-top: 22px;
            padding: 13px;
            border: none;
            border-radius: 7px;
            background: #12345a;
            color: #ffffff;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
        }

        button:hover {
            background: #1d4f80;
        }

        .error {
            padding: 11px;
            background: #fee2e2;
            color: #991b1b;
            border-radius: 6px;
            font-size: 14px;
        }

        .footer {
            margin-top: 22px;
            text-align: center;
            color: #94a3b8;
            font-size: 12px;
        }

    </style>

</head>

<body>

    <div class="login-card">

        <div class="logo">
           <h3> Stedman Pharmaceuticals Pvt Ltd </h3> <h4> (Diagnostics Divison) </h4>
        </div>

        <p class="subtitle">
            Service Call Management System
        </p>

        <?php if ($error !== ""): ?>

            <div class="error">
                <?= htmlspecialchars(
                    $error,
                    ENT_QUOTES,
                    "UTF-8"
                ) ?>
            </div>

        <?php endif; ?>

        <form method="POST" action="login.php">

            <label for="username">
                Username
            </label>

            <input
                type="text"
                id="username"
                name="username"
                autocomplete="username"
                required
                autofocus
            >

            <label for="password">
                Password
            </label>

            <input
                type="password"
                id="password"
                name="password"
                autocomplete="current-password"
                required
            >

            <button type="submit">
                Login
            </button>

        </form>

        <div class="footer">
            Stedman Pharmaceuticals Pvt Ltd (Diagnostics Divison)
        </div>

    </div>

</body>

</html>
