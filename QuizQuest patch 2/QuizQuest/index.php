<?php
session_start();
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "quizmaker";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$student_id = $_SESSION['user_id'] ?? 0;

// if user is not logged in, block access
if (!isset($_SESSION["user"])) {
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Welcome</title>
</head>
<body>





<h1>Welcome, <?php echo $_SESSION["user"]; ?>!</h1>
<p>You have successfully logged in.</p>

<a href="logout.php">Logout</a>

</body>
</html>
