<?php
session_start();

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "quizmaker";

$conn = new mysqli("localhost", "root", "", "login_system");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["register"])) {
    $username = trim($_POST["username"]);
    $full_name = trim($_POST["full_name"]);
    $password = trim($_POST["password"]);
    $confirm_password = trim($_POST["confirm_password"]);
    $role = $_POST["role"] ?? 'student';

    if (empty($username) || empty($full_name) || empty($password) || empty($confirm_password)) {
        $error = "All fields are required.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE username=?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $error = "Username already taken.";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, full_name, password, role) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $username, $full_name, $hashed, $role);

            if ($stmt->execute()) {
                $success = "Registration successful! You can now <a href='login.php'>login</a>.";
            } else {
                $error = "Error creating account. Try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>QuizQuest Register</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="assets/css/register.css">
</head>

<body>

<header class="header">
    <div class="logo-container">
        <img src="assets/images/logo.png" alt="QuizQuest Logo">
    </div>
</header>

<div class="container mt-5">
    <div class="register-card">

        <!-- LEFT SIDE -->
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

            <!-- ERRORS -->
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger py-1"><?= $error ?></div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success py-1"><?= $success ?></div>
            <?php endif; ?>

            <form method="POST">

                <input type="text" name="username" class="form-control form-control-sm mb-2" placeholder="Username" required>

                <input type="text" name="full_name" class="form-control form-control-sm mb-2" placeholder="Full Name" required>

                <input type="text" name="email" class="form-control form-control-sm mb-2" placeholder="Email" required>

                <input type="password" name="password" class="form-control form-control-sm mb-2" placeholder="Password" required>

                <input type="password" name="confirm_password" class="form-control form-control-sm mb-2" placeholder="Confirm Password" required>

                <div class="register-footer">
                    <p>
                        <a href="login.php" class="small">Already have an account?</a>
                    </p> 
                    
                    <div class="register-button">
                        <button type="submit" name="register">Register</button>
                    </div>
                </div>

            </form>

        </div>
    </div>
    
</div>

</body>
</html>

<?php $conn->close(); ?>