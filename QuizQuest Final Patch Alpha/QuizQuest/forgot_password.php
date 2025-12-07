<?php
session_start();

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "quizmaker";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$message = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["reset_request"])) {
    $email = trim($_POST["email"]);

    if (empty($email)) {
        $error = "Please enter your email.";
    } else {
        // Find user by email
        $stmt = $conn->prepare("SELECT id, username FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            $user_id = $user["id"];

            // Create reset token
            $token = bin2hex(random_bytes(32));      // random token
            $expires_at = date("Y-m-d H:i:s", time() + 3600); // valid for 1 hour

            // Optional: delete old tokens for this user
            $conn->prepare("DELETE FROM password_resets WHERE user_id = ?")
                 ->bind_param("i", $user_id)
                 ->execute();

            // Insert new token
            $stmtInsert = $conn->prepare("
                INSERT INTO password_resets (user_id, token, expires_at)
                VALUES (?, ?, ?)
            ");
            $stmtInsert->bind_param("iss", $user_id, $token, $expires_at);
            $stmtInsert->execute();

            // Build reset link
            $resetLink = "http://localhost/QuizQuest/reset_password.php?token=" . urlencode($token);

            // Send email via MailHog (using mail())
            $subject = "QuizQuest Password Reset";
            $body = "Hello,\n\n"
                  . "We received a request to reset your QuizQuest password.\n"
                  . "Click the link below to reset it (valid for 1 hour):\n\n"
                  . $resetLink . "\n\n"
                  . "If you did not request this, you can ignore this email.";

            $headers  = "From: no-reply@quizquest.local\r\n";
            $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

            // This will be intercepted by MailHog in dev environment
            if (mail($email, $subject, $body, $headers)) {
                $message = "If an account with that email exists, a reset link has been sent.";
            } else {
                $error = "Unable to send reset email. Please contact the administrator.";
            }

        } else {
            // To avoid revealing if email exists, show generic msg
            $message = "If an account with that email exists, a reset link has been sent.";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Forgot Password - QuizQuest</title>
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
            <p>Forgot your password?</p>
            <div class="bottom-info">
                <div class="side-line"></div>
                <p>Enter your registered email and we'll send you a reset link.</p>
            </div>
        </div>

        <div class="right-side">
            <div class="title">
                <img src="assets/images/quizquest-title.png">
            </div>

            <?php if (!empty($error)) : ?>
                <div class="error-box mb-2"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if (!empty($message)) : ?>
                <div class="success-box mb-2"><?php echo $message; ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="input-row">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>
                </div>

                <div class="login-buttons">
                    <button type="submit" name="reset_request">Send Reset Link</button>
                    <a href="login.php" class="cancel-btn btn btn-sm">Back to Login</a>
                </div>
            </form>
        </div>

    </div>
</div>

</body>
</html>
<?php $conn->close(); ?>
