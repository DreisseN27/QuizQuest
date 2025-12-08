<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    die("You must be logged in to take a quiz.");
}

$student_id = (int)$_SESSION['user_id'];

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
$conn->set_charset("utf8mb4");

// Fetch quiz info including class_code and title
$qInfoStmt = $conn->prepare("SELECT title, class_code FROM quizzes WHERE id = ?");
$qInfoStmt->bind_param("i", $quiz_id);
$qInfoStmt->execute();
$qInfoResult = $qInfoStmt->get_result();
$quizInfo = $qInfoResult->fetch_assoc();
$qInfoStmt->close();

$quizTitle = $quizInfo['title'] ?? 'Unknown Quiz';
$class_code = $quizInfo['class_code'] ?? null;

// Defensive: if quiz has no class_code, still proceed (no class association)
if ($class_code !== null) {
    $class_code = trim($class_code);
}

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

    if ($type === 'multiple') {
        // strict comparison for multiple choice (assuming exact stored option)
        if ((string)$submitted === (string)$correct) {
            $score++;
        }
    } else {
        // Normalize for text-based answers
        if (trim(strtolower((string)$submitted)) === trim(strtolower((string)$correct))) {
            $score++;
        }
    }
}
$qstmt->close();

// Start transaction so we keep DB consistent
$conn->begin_transaction();

$successInsert = false;
try {
    // Insert into student_quizzes
    $insert = $conn->prepare("INSERT INTO student_quizzes (student_id, quiz_id, score, taken_at) VALUES (?, ?, ?, NOW())");
    $insert->bind_param("iii", $student_id, $quiz_id, $score);
    $successInsert = $insert->execute();
    $insert->close();

    // Ensure the student is enrolled in the class in student_classes (so leaderboard and active classes keep showing)
    if ($successInsert && $class_code) {
        // store in a normalized case (uppercase) to avoid mismatch issues
        $cleanCode = strtoupper($class_code);

        // Check if already enrolled
        $chk = $conn->prepare("SELECT id FROM student_classes WHERE student_id = ? AND UPPER(class_code) = UPPER(?)");
        $chk->bind_param("is", $student_id, $cleanCode);
        $chk->execute();
        $chkRes = $chk->get_result();

        if ($chkRes->num_rows === 0) {
            // Insert a student_classes row so class remains visible on leaderboards for this student
            $insSc = $conn->prepare("INSERT INTO student_classes (student_id, class_code) VALUES (?, ?)");
            $insSc->bind_param("is", $student_id, $cleanCode);
            $insSc->execute();
            $insSc->close();
        }
        $chk->close();
    }

    // EXP + LEVEL SYSTEM
    $earned_exp = $score * 10;

    // Fetch current EXP
    $expStmt = $conn->prepare("SELECT exp, title FROM student_exp WHERE student_id = ? AND class_code = ?");
    $expStmt->bind_param("is", $student_id, $class_code);
    $expStmt->execute();
    $expResult = $expStmt->get_result();

    if ($expRow = $expResult->fetch_assoc()) {
        $current_exp = (int)$expRow['exp'];
        $current_title = $expRow['title'];
    } else {
        $current_exp = 0;
        $current_title = 'newbie';
        $insertExp = $conn->prepare("INSERT INTO student_exp (student_id, class_code, exp, title) VALUES (?, ?, 0, 'newbie')");
        $insertExp->bind_param("is", $student_id, $class_code);
        $insertExp->execute();
        $insertExp->close();
    }
    $expStmt->close();

    $new_exp = $current_exp + $earned_exp;

    $levels = [
        ["name" => "newbie",     "min" => 0,   "max" => 49],
        ["name" => "beginner",   "min" => 50,  "max" => 99],
        ["name" => "recruit",    "min" => 100, "max" => 149],
        ["name" => "adventurer", "min" => 150, "max" => 199],
        ["name" => "veteran",    "min" => 200, "max" => 249],
        ["name" => "master",     "min" => 250, "max" => 299],
        ["name" => "hero",       "min" => 300, "max" => 349],
        ["name" => "champion",   "min" => 350, "max" => 399],
        ["name" => "legend",     "min" => 400, "max" => 499],
        ["name" => "ascendant",  "min" => 500, "max" => PHP_INT_MAX]
    ];

    function getLevelData($exp, $levels) {
        foreach ($levels as $i => $lvl) {
            if ($exp >= $lvl["min"] && $exp <= $lvl["max"]) {
                $current = $lvl;
                $next = $levels[$i + 1] ?? null;

                $range = max(1, $current["max"] - $current["min"]);
                $progress = (($exp - $current["min"]) / $range) * 100;
                if ($progress > 100) $progress = 100;

                $exp_to_next = $next ? max(0, $next["min"] - $exp) : 0;

                return [
                    "current" => $current,
                    "next" => $next,
                    "progress" => $progress,
                    "exp_to_next" => $exp_to_next
                ];
            }
        }
        // fallback
        return [
            "current" => $levels[0],
            "next" => $levels[1] ?? null,
            "progress" => 0,
            "exp_to_next" => $levels[1]['min'] ?? 0
        ];
    }

    $levelData = getLevelData($new_exp, $levels);
    $new_title = $levelData["current"]["name"];
    $exp_needed = $levelData["exp_to_next"];
    $progress_pct = $levelData["progress"];

    // Detect level up
    $old_level = $current_title;
    $new_level = $new_title;
    $level_up = ($old_level !== $new_level);

    // Update student_exp
    $updateExp = $conn->prepare("UPDATE student_exp SET exp = ?, title = ? WHERE student_id = ? AND class_code = ?");
    $updateExp->bind_param("isis", $new_exp, $new_title, $student_id, $class_code);
    $updateExp->execute();
    $updateExp->close();

    // commit transaction
    $conn->commit();

} catch (Exception $e) {
    $conn->rollback();
    // for debugging you might want to log $e->getMessage()
    die("An error occurred while saving your results.");
}

$taken_at = date('M d, Y H:i');
$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Quiz Result</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/submit_quiz.css">
</head>

<body>

<?php if (!empty($level_up) && $level_up): ?>
<div id="levelUpPopup" class="popup-overlay">
    <div class="popup-content">
        <h2>🎉 Congratulations! 🎉</h2>
        <p>You have risen the ranks and earned</p>
        <h1 class="new-rank"><?php echo strtoupper(htmlspecialchars($new_title)); ?></h1>
        <p class="tap-msg">Tap anywhere to continue</p>
    </div>
</div>

<script>
document.getElementById("levelUpPopup").addEventListener("click", function() {
    this.style.display = "none";
});
</script>
<?php endif; ?>

<canvas id="background-canvas"></canvas>
<div class="card-result">
    <h3 class="card-title"><?php echo htmlspecialchars($quizTitle); ?></h3>
    <p class="score">Score: <strong><?php echo $score; ?></strong> / <?php echo $total; ?></p>
    <p class="score">Taken on: <strong><?php echo $taken_at; ?></strong></p>

    <hr>

    <p>Starting EXP: <strong><?php echo $current_exp; ?></strong></p>
    <p>New Total EXP: <strong><?php echo $new_exp; ?></strong></p>
    <p class="text-info">+<?php echo $earned_exp; ?> EXP earned</p>

    <hr>

    <?php if (!empty($levelData["next"])): ?>
        <p><?php echo $exp_needed; ?> EXP needed to reach <strong><?php echo ucfirst(htmlspecialchars($levelData["next"]["name"])); ?></strong></p>

        <div class="progress mb-3">
            <div class="progress-bar" style="width: <?php echo $progress_pct; ?>%;"></div>
        </div>
    <?php else: ?>
        <p>You reached the MAX title: <strong>Ascendant</strong></p>
    <?php endif; ?>

    <a href="student.php" class="btn btn-success btn-back mt-3">Continue</a>
</div>
<script src="teacherscripts.js"></script>

</body>
</html>
