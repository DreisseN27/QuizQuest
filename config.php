<?php
$host = "localhost";
$user = "root";  // default XAMPP username
$pass = "";      // default XAMPP password is empty
$db   = "quizquest_login"; // the DB we'll create later

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>