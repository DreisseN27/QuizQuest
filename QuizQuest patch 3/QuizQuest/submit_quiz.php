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

// Fetch quiz info including class_code
$qInfoStmt = $conn->prepare("SELECT title, class_code FROM quizzes WHERE id = ?");
$qInfoStmt->bind_param("i", $quiz_id);
$qInfoStmt->execute();
$qInfoResult = $qInfoStmt->get_result();
$quizInfo = $qInfoResult->fetch_assoc();
$quizTitle = $quizInfo['title'] ?? 'Unknown Quiz';
$class_code = $quizInfo['class_code'] ?? null;

// Fetch questions and correct answers
$qstmt = $conn->prepare("SELECT id, question_type, correct_answer FROM questions WHERE quiz_id = ?");
$qstmt->bind_param("i", $quiz_id);
$qstmt->execute();
$result = $qstmt->get_result();

$score = 0;
$total = $result->num_rows;

// Score calculation
while ($row = $result->fetch_assoc()) {
    $q_id = $row['id'];
    $correct = $row['correct_answer'];
    $type = $row['question_type'];

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

// Remove class from active classes if submission successful
if ($successInsert && $class_code) {
    $del = $conn->prepare("DELETE FROM student_classes WHERE student_id = ? AND class_code = ?");
    $del->bind_param("is", $student_id, $class_code);
    $del->execute();
}

/* ==========================================================
   EXP + LEVEL SYSTEM
   ========================================================== */

// 1 point = 10 EXP
$earned_exp = $score * 10;

// Fetch current EXP
$expStmt = $conn->prepare("SELECT exp, title FROM student_exp WHERE student_id = ? AND class_code = ?");
$expStmt->bind_param("is", $student_id, $class_code);
$expStmt->execute();
$expResult = $expStmt->get_result();

if ($expRow = $expResult->fetch_assoc()) {
    $current_exp = $expRow['exp'];
    $current_title = $expRow['title'];
} else {
    // Create blank record
    $current_exp = 0;
    $current_title = 'newbie';
    $insertExp = $conn->prepare("INSERT INTO student_exp (student_id, class_code, exp, title) VALUES (?, ?, 0, 'newbie')");
    $insertExp->bind_param("is", $student_id, $class_code);
    $insertExp->execute();
}

$new_exp = $current_exp + $earned_exp;

// Level titles
function getTitleFromExp($exp) {
    if ($exp >= 500) return "ascendant";
    if ($exp >= 400) return "legend";
    if ($exp >= 350) return "champion";
    if ($exp >= 300) return "hero";
    if ($exp >= 250) return "master";
    if ($exp >= 200) return "veteran";
    if ($exp >= 150) return "adventurer";
    if ($exp >= 100) return "recruit";
    if ($exp >= 50)  return "beginner";
    return "newbie";
}

$new_title = getTitleFromExp($new_exp);

// Save updated EXP + Title
$updateExp = $conn->prepare("UPDATE student_exp SET exp = ?, title = ? WHERE student_id = ? AND class_code = ?");
$updateExp->bind_param("isis", $new_exp, $new_title, $student_id, $class_code);
$updateExp->execute();

// Next level requirements
$levels = [
    "newbie" => 50,
    "beginner" => 100,
    "recruit" => 150,
    "adventurer" => 200,
    "veteran" => 250,
    "master" => 300,
    "hero" => 350,
    "champion" => 400,
    "legend" => 500,
    "ascendant" => null
];

$next_threshold = $levels[$new_title];
$exp_needed = $next_threshold ? ($next_threshold - $new_exp) : 0;

$progress_pct = $next_threshold
    ? min(100, ($new_exp / $next_threshold) * 100)
    : 100;

// Pass/fail logic
$passed = ($total > 0 && ($score / $total) >= 0.5) ? 'Passed' : 'Failed';
$taken_at = date('M d, Y H:i');

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Quiz Result</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body {
    background: #0d1117;
    color: #fff;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
}
.card-result {
    background: rgba(255,255,255,0.05);
    backdrop-filter: blur(15px);
    border-radius: 20px;
    padding: 2rem;
    max-width: 420px;
    width: 100%;
    text-align: center;
}
.progress {
    background: rgba(255,255,255,0.1);
    border-radius: 10px;
}
.progress-bar {
    background-color: #4ade80;
}
.text-info { color: #60a5fa; }
</style>
</head>
<body>

<div class="card-result">
    <h3 class="card-title"><?php echo htmlspecialchars($quizTitle); ?></h3>
    <p class="score">Score: <strong><?php echo $score; ?></strong> / <?php echo $total; ?></p>
    <p class="score">Taken on: <strong><?php echo $taken_at; ?></strong></p>

    <hr>

    <p>Starting EXP: <strong><?php echo $current_exp; ?></strong></p>
    <p>New Total EXP: <strong><?php echo $new_exp; ?></strong></p>
    <p class="text-info">+<?php echo $earned_exp; ?> EXP earned</p>

    <hr>

    <?php if ($next_threshold): ?>
        <p><?php echo $exp_needed; ?> EXP needed to reach <strong><?php echo ucfirst($new_title); ?></strong></p>

        <div class="progress mb-3">
            <div class="progress-bar" role="progressbar"
                style="width: <?php echo $progress_pct; ?>%;">
                <?php echo round($progress_pct); ?>%
            </div>
        </div>
    <?php else: ?>
        <p>You reached the MAX title: <strong>Ascendant</strong></p>
    <?php endif; ?>

    <a href="student.php" class="btn btn-success btn-back mt-3">Continue</a>
</div>

</body>
</html>
