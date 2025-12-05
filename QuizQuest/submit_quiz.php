<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    die("You must be logged in to take a quiz.");
}

$student_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['quiz_id'], $_POST['answers'])) {
    die("Invalid data submitted.");
}

$quiz_id = (int)$_POST['quiz_id'];
$answers = $_POST['answers'];

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "quizmaker";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) die("Database connection failed: " . $conn->connect_error);

// Fetch quiz title
$qInfoStmt = $conn->prepare("SELECT title FROM quizzes WHERE id = ?");
$qInfoStmt->bind_param("i", $quiz_id);
$qInfoStmt->execute();
$qInfoResult = $qInfoStmt->get_result();
$quizInfo = $qInfoResult->fetch_assoc();
$quizTitle = $quizInfo['title'] ?? 'Unknown Quiz';

// Fetch questions
$qstmt = $conn->prepare("SELECT id, question_type, correct_answer, class_code FROM questions WHERE quiz_id = ?");
$qstmt->bind_param("i", $quiz_id);
$qstmt->execute();
$result = $qstmt->get_result();

$score = 0;
$total = $result->num_rows;
$class_code = null;

while ($row = $result->fetch_assoc()) {
    $q_id = $row['id'];
    $correct = $row['correct_answer'];
    $type = $row['question_type'];
    $class_code = $row['class_code'];

    if (!isset($answers[$q_id])) continue;

    $submitted = $answers[$q_id];

    if ($type === 'multiple' && $submitted === $correct) {
        $score++;
    } elseif ($type !== 'multiple' && trim(strtolower($submitted)) === trim(strtolower($correct))) {
        $score++;
    }
}

// Insert into student_quizzes
$insert = $conn->prepare("INSERT INTO student_quizzes (student_id, quiz_id, score, taken_at) VALUES (?, ?, ?, NOW())");
$insert->bind_param("iii", $student_id, $quiz_id, $score);
$successInsert = $insert->execute();

// Remove from active classes
if ($successInsert && $class_code) {
    $del = $conn->prepare("DELETE FROM student_classes WHERE student_id = ? AND class_code = ?");
    $del->bind_param("is", $student_id, $class_code);
    $del->execute();
}

// Pass/fail logic
$passed = ($total > 0 && ($score / $total) >= 0.5) ? 'Passed' : 'Failed';
$taken_at = date('M d, Y H:i');

// -------- LEVELING SYSTEM --------
function getLevelTitle($exp) {
    if ($exp >= 100) return "Legend";
    if ($exp >= 75) return "Hero";
    if ($exp >= 50) return "Master";
    if ($exp >= 35) return "Veteran";
    if ($exp >= 20) return "Squire";
    if ($exp >= 10) return "Recruit";
    return "Newbie";
}

function pointsToNextLevel($exp) {
    if ($exp >= 100) return 0;
    if ($exp >= 75) return 100 - $exp;
    if ($exp >= 50) return 75 - $exp;
    if ($exp >= 35) return 50 - $exp;
    if ($exp >= 20) return 35 - $exp;
    if ($exp >= 10) return 20 - $exp;
    return 10 - $exp;
}

// Convert quiz score to exp points (1:1 here, adjust if needed)
$newPoints = $score;

// Fetch current exp for this classroom
$expStmt = $conn->prepare("SELECT exp_points FROM student_experience WHERE user_id = ? AND classroom_id = ?");
$expStmt->bind_param("ii", $student_id, $class_code);
$expStmt->execute();
$expResult = $expStmt->get_result();
$expRow = $expResult->fetch_assoc();
$currentExp = $expRow['exp_points'] ?? 0;
$updatedExp = $currentExp + $newPoints;

// Update or insert exp
if ($expRow) {
    $updateExp = $conn->prepare("UPDATE student_experience SET exp_points = ?, last_updated = NOW() WHERE user_id = ? AND classroom_id = ?");
    $updateExp->bind_param("iii", $updatedExp, $student_id, $class_code);
    $updateExp->execute();
} else {
    $insertExp = $conn->prepare("INSERT INTO student_experience (user_id, classroom_id, exp_points, last_updated) VALUES (?, ?, ?, NOW())");
    $insertExp->bind_param("iii", $student_id, $class_code, $updatedExp);
    $insertExp->execute();
}

$currentTitle = getLevelTitle($updatedExp);
$nextLevelPoints = pointsToNextLevel($updatedExp);

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Quiz Result & Leveling</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <div class="card text-center">
        <div class="card-header bg-primary text-white">
            Quiz Result
        </div>
        <div class="card-body">
            <h5 class="card-title"><?php echo htmlspecialchars($quizTitle); ?></h5>
            <p class="card-text">Score: <?php echo $score; ?> / <?php echo $total; ?></p>
            <p class="card-text">Taken on: <?php echo $taken_at; ?></p>
            <p class="card-text fw-bold">Status: <?php echo $passed; ?></p>

            <hr>

            <h5>Leveling System</h5>
            <p>Experience: <?php echo $currentExp; ?> → <?php echo $updatedExp; ?></p>
            <p>Current Title: <?php echo $currentTitle; ?></p>
            <p>Points to next level: <?php echo $nextLevelPoints; ?></p>

            <a href="student.php" class="btn btn-success mt-3">Back to Classroom</a>
        </div>
    </div>
</div>
</body>
</html>
