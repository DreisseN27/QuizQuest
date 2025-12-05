<?php
session_start();
include "config.php";

$error = "";   // error message text
$shake = false; // used to trigger shake animation

// Only process login if form is submitted via POST and login button was pressed
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["login"])) {
    
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // Query for user
    $sql = "SELECT * FROM users WHERE username = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);

        // Verify password
        if (password_verify($password, $user["password"])) {
            $_SESSION["user"] = $user["username"];
            header("Location: index.php");
            exit;
        } else {
            $error = "Incorrect password.";
            $shake = true;
        }

    } else {
        $error = "User not found.";
        $shake = true;
    }
}

// Optional: handle cancel if needed
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["cancel"])) {
    // nothing as requested
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>QuizQuest Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="assets/css/login.css">
</head>
<body>

<header class="header">
    <div class="logo-container">
        <img src="assets/images/logo.png" alt="QuizQuest Logo">
    </div>
</header>

<div class="container mt-3">
    <div class="login-card <?php if ($shake) echo 'error-shake'; ?>">

        <div class="left-side">

            <div class="patch-notes">

                <h2> Patch Notes </h2>

                <div class="patch-list">

                    <div class="patch-entry">
                        <h3>v1.0.3 – Nov 30, 2025</h3>
                        <p>• Improved login error animation.</p>
                        <p>• Updated spacing and layout adjustments.</p>
                    </div>

                    <div class="patch-entry">
                        <h3>v1.0.2 – Nov 28, 2025</h3>
                        <p>• Added new title image on login page.</p>
                        <p>• Updated UI colors.</p>
                    </div>

                    <div class="patch-entry">
                        <h3>v1.0.1 – Nov 25, 2025</h3>
                        <p>• Initial login screen layout created.</p>
                    </div>

                </div>

            </div>

            <div class="bottom-info">
                <div class="side-line"></div>
                <p>
                    Enter QuizQuest, where every quiz brings you closer to mastery.
                    Play, learn, and rise through the ranks.
                </p>
            </div>
        </div>

        <!-- RIGHT SIDE -->
        <div class="right-side">

            <div class="title">
                <img src="assets/images/quizquest-title.png">
            </div>
            
            <p class="subheading">Where every quiz is an adventure!</p>
            
            <form method="POST">

                <input type="text" name="username" class="form-control form-control-sm mb-2" placeholder="Username" required>

                <input type="password" name="password" class="form-control form-control-sm mb-2" placeholder="Password" required>

                <div class="register-footer">

                    <p class="no-account">
                        <a href="entry_page.php" class="btn-link">Don't have an account?</a>
                    </p>
                    
                </div>

                <div class="error-wrapper">
                    <?php if (!empty($error)) : ?>
                        <div class="error-box"><?php echo $error; ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="login-buttons">
                    <button type="submit" name="login">Login</button>
                    <button type="submit" name="cancel" class="cancel-btn">Cancel</button>
                </div>

            </form>

        </div>

    </div>
</div>
</body>
</html>
