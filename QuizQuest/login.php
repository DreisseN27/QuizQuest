<?php 
session_start();

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "quizmaker";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$error = "";
$shake = false;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["login"])) {
    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);

    $stmt = $conn->prepare("SELECT * FROM users WHERE username=?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user["password"])) {
            // Store session data
            $_SESSION["user_id"]  = $user["id"];
            $_SESSION["username"] = $user["username"];
            $_SESSION["role"]     = $user["role"];   // 'teacher' or 'student'

            // Redirect based on role
            if ($user["role"] === "teacher") {
                header("Location: teacher.php");
            } elseif ($user["role"] === "student") {
                header("Location: student.php");
            } else {
                // fallback if role is something unexpected
                header("Location: index.php");
            }
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
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
        <link rel="stylesheet" href="login.css">
    </head>
<body>

<header class="header">
    <div class="logo-container">
        <img src="assets/images/logo.png" alt="QuizQuest Logo">
    </div>
</header>

<div class="container mt-5">
    <div class="login-card <?php if ($shake) echo 'error-shake'; ?>">

        <!-- LEFT SIDE -->
        <div class="left-side">
 
            <p>Where every quiz is an adventure!</p>

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

            <form method="POST">

                <div class="input-row">
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username" required>
                </div>

                <div class="input-row">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <p class="no-account">
                    Don't have an account? 
                    <a href="register.php">Click here</a>
                </p>

                <p class="no-account">
                    Forgot your password? 
                    <a href="forgot_password.php">Click here</a>
                </p>

                <div class="error-wrapper">
                    <?php if (!empty($error)) : ?>
                        <div class="error-box"><?php echo $error; ?></div>
                    <?php endif; ?>
                </div>

                <div class="login-buttons">
                    <button type="submit" name="login">Login</button>
                </div>

            </form>
        </div>

            </form>

        </div>

    </div>
</div>

</body>
</html>
