<?php
session_start();
$host = "localhost"; $user = "root"; $pass = ""; $dbname = "quizmaker";
$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$error = ""; $success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["register"])) {
    $username = trim($_POST["username"]);
    $full_name = trim($_POST["full_name"]);
    $password = trim($_POST["password"]);
    $confirm_password = trim($_POST["confirm_password"]);
    $role = $_POST["role"] ?? 'student'; // default student

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
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, full_name, password, role) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $username, $full_name, $hashed_password, $role);
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
    <title>Register - QuizQuest</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="login.css">
</head>
<body>

<header class="header mb-4">
    <div class="logo-container text-center mt-3">
        <img src="assets/images/logo.png" alt="QuizQuest Logo" style="max-width: 200px; height:auto;">
    </div>
</header>

<div class="container mt-3">
    <div class="row justify-content-center">
        <div class="col-lg-8 col-md-10 login-card d-flex p-0">

            <!-- LEFT SIDE -->
            <div class="left-side p-3 flex-fill bg-light">
                <p class="mb-2">Where every quiz is an adventure!</p>
                <div class="bottom-info">
                    <div class="side-line mb-2"></div>
                    <p class="small">Enter QuizQuest, where every quiz brings you closer to mastery. Play, learn, and rise through the ranks.</p>
                </div>
            </div>

            <!-- RIGHT SIDE -->
            <div class="right-side p-3 flex-fill">

                <div class="text-center mb-3">
                    <img src="assets/images/quizquest-title.png" alt="QuizQuest Title" style="max-width: 150px;">
                </div>

                <?php if (!empty($error)) : ?>
                    <div class="alert alert-danger py-1"><?php echo $error; ?></div>
                <?php endif; ?>

                <?php if (!empty($success)) : ?>
                    <div class="alert alert-success py-1"><?php echo $success; ?></div>
                <?php endif; ?>

                <form method="POST">

                    <div class="mb-2">
                        <input type="text" class="form-control form-control-sm" id="username" name="username" placeholder="Username" required>
                    </div>

                    <div class="mb-2">
                        <input type="text" class="form-control form-control-sm" id="full_name" name="full_name" placeholder="Full Name" required>
                    </div>

                    <div class="mb-2">
                        <input type="password" class="form-control form-control-sm" id="password" name="password" placeholder="Password" required>
                    </div>

                    <div class="mb-2">
                        <input type="password" class="form-control form-control-sm" id="confirm_password" name="confirm_password" placeholder="Confirm Password" required>
                    </div>

                    <div class="mb-3">
                        <select class="form-select form-select-sm" name="role" required>
                            <option value="" disabled selected>Select Role</option>
                            <option value="student">Student</option>
                            <option value="teacher">Teacher</option>
                        </select>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-2">
                        <a href="login.php" class="small">Already have an account?</a>
                        <button type="submit" name="register" class="btn btn-primary btn-sm">Register</button>
                    </div>

                    

                </form>
            </div>

        </div>
    </div>
</div>

</body>
</html>

<?php $conn->close(); ?>
