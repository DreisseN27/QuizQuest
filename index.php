<?php
session_start();

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
