<?php
session_start();

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "quizmaker";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$error = "";
$success = "";
$token  = isset($_GET["token"]) ? $_GET["token"] : "";

if (empty($token)) {
    $error = "Invalid reset token.";
} else {
    // Check token and expiry
    $stmt = $conn->prepare("
        SELECT pr.user_id, pr.expires_at, u.username
        FROM password_resets pr
        JOIN users u ON pr.user_id = u.id
        WHERE pr.token = ?
        LIMIT 1
    ");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows !== 1) {
        $error = "Invalid or expired reset token.";
    } else {
        $row = $result->fetch_assoc();
        $user_id   = $row["user_id"];
        $expiresAt = strtotime($row["expires_at"]);

        if (time() > $expiresAt) {
            $error = "This reset link has expired.";
        }
    }
}

// Handle password change
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["reset_password"])) {
    $token       = $_POST["token"];
    $newPass     = trim($_POST["new_password"]);
    $confirmPass = trim($_POST["confirm_password"]);

    if (empty($newPass) || empty($confirmPass)) {
        $error = "Please fill in both password fields.";
    } elseif ($newPass !== $confirmPass) {
        $error = "Passwords do not match.";
    } else {
        // Re-check token for security
        $stmt = $conn->prepare("
            SELECT user_id, expires_at
            FROM password_resets
            WHERE token = ?
            LIMIT 1
        ");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows !== 1) {
            $error = "Invalid or expired reset token.";
        } else {
            $row      = $res->fetch_assoc();
            $user_id  = $row["user_id"];
            $expiresAt = strtotime($row["expires_at"]);

            if (time() > $expiresAt) {
                $error = "This reset link has expired.";
            } else {
                // Update user password
                $hashed = password_hash($newPass, PASSWORD_DEFAULT);
                $stmtUpd = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmtUpd->bind_param("si", $hashed, $user_id);

                if ($stmtUpd->execute()) {
                    // Remove token so it can't be reused
                    $stmtDel = $conn->prepare("DELETE FROM password_resets WHERE token = ?");
                    $stmtDel->bind_param("s", $token);
                    $stmtDel->execute();

                    $success = "Password updated successfully! <a href='login.php'>Login here</a>.";
                } else {
                    $error = "Error updating password. Please try again.";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Reset Password - QuizQuest</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="login.css">
</head>
<body>

<header class="header">
    <div class="logo-container text-center">
        <img src="assets/images/logo.png" alt="QuizQuest Logo" style="max-width: 200px; height:auto;">
    </div>
</header>

<div class="container mt-5">
    <div class="login-card">

        <div class="left-side">
            <p>Reset your password</p>
            <div class="bottom-info">
                <div class="side-line"></div>
                <p>Choose a new password to regain access to your account.</p>
            </div>
        </div>

        <div class="right-side">
            <div class="title">
                <img src="assets/images/quizquest-title.png">
            </div>

            <?php if (!empty($error)) : ?>
                <div class="error-box mb-2"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if (!empty($success)) : ?>
                <div class="success-box mb-2"><?php echo $success; ?></div>
            <?php endif; ?>

            <?php if (empty($success)) : ?>
                <form method="POST">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                    <div class="input-row">
                        <label for="new_password">New Password:</label>
                        <input type="password" id="new_password" name="new_password" required>
                    </div>

                    <div class="input-row">
                        <label for="confirm_password">Confirm Password:</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>

                    <div class="login-buttons">
                        <button type="submit" name="reset_password">Update Password</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>

    </div>
</div>

</body>
</html>
<?php $conn->close(); ?>
