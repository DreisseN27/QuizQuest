<?php
$servername = "localhost";   // usually localhost
$username = "root";          // your MySQL username
$password = "";              // your MySQL password (often empty in XAMPP)
$dbname = "login_system";    // the database name

// Create connection
$conn = mysqli_connect($servername, $username, $password, $dbname);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>
