<?php
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    echo json_encode(['success'=>false, 'error'=>'Unauthorized']);
    exit;
}

// Read input
$data = json_decode(file_get_contents('php://input'), true);

// Debug: log raw input
file_put_contents(__DIR__.'/quiz_debug.log', "RAW INPUT: ".json_encode($data)."\n", FILE_APPEND);

if (!$data || empty($data['title']) || empty($data['questions'])) {
    echo json_encode([
        'success'=>false,
        'error'=>'Invalid data',
        'debug'=>$data
    ]);
    exit;
}

require __DIR__ . '/db.php';

$class_id = isset($data['class_id']) ? (int)$data['class_id'] : 0;
$class_code = isset($data['class_code']) ? trim($data['class_code']) : null;
$teacher_id = (int)$_SESSION['user_id'];
$title = trim($data['title']);

// Debug: log extracted values
file_put_contents(__DIR__.'/quiz_debug.log', 
    "EXTRACTED VALUES: class_id=$class_id, class_code=$class_code, teacher_id=$teacher_id, title=$title\n", 
    FILE_APPEND
);

// Check class_id early
if ($class_id === 0) {
    echo json_encode([
        'success'=>false, 
        'error'=>'Invalid class_id detected. Ensure class_id is passed from scripts.js',
        'data'=>$data
    ]);
    exit;
}

try {
    // Verify class ownership
    $cstmt = $pdo->prepare("SELECT class_code, teacher_id FROM classes WHERE id=? LIMIT 1");
    $cstmt->execute([$class_id]);
    $c = $cstmt->fetch(PDO::FETCH_ASSOC);

    // Debug: log DB fetch result
    file_put_contents(__DIR__.'/quiz_debug.log', 
        "DB FETCH CLASS: ".json_encode($c)."\n", 
        FILE_APPEND
    );

    if (!$c || $c['teacher_id'] != $teacher_id) {
        echo json_encode(['success'=>false, 'error'=>'Invalid class or unauthorized', 'data'=>$c]);
        exit;
    }

    $class_code = $c['class_code'];

    $pdo->beginTransaction();

    $stmt = $pdo->prepare("INSERT INTO quizzes (teacher_id, class_code, title, class_id) VALUES (?, ?, ?, ?)");
    $stmt->execute([$teacher_id, $class_code, $title, $class_id]);
    $quiz_id = $pdo->lastInsertId();

    $qStmt = $pdo->prepare("INSERT INTO questions (quiz_id, question_text, question_type, correct_answer) VALUES (?, ?, ?, ?)");
    $cStmt = $pdo->prepare("INSERT INTO choices (question_id, choice_label, choice_text) VALUES (?, ?, ?)");

    foreach ($data['questions'] as $q) {
        $text = htmlspecialchars(trim($q['text']));
        $type = htmlspecialchars($q['type']);
        $correct = htmlspecialchars(trim($q['correct']));

        $qStmt->execute([$quiz_id, $text, $type, $correct]);
        $question_id = $pdo->lastInsertId();

        if ($type === 'multiple') {
            foreach ($q['choices'] as $i => $choiceText) {
                $label = chr(65 + $i);
                $cStmt->execute([$question_id, $label, htmlspecialchars(trim($choiceText))]);
            }
        }
    }

    $pdo->commit();

    // Debug: log success
    file_put_contents(__DIR__.'/quiz_debug.log', "QUIZ INSERTED: quiz_id=$quiz_id, class_id=$class_id\n", FILE_APPEND);

    echo json_encode([
        'success'=>true, 
        'quiz_id'=>$quiz_id,
        'class_id'=>$class_id
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    file_put_contents(__DIR__.'/quiz_debug.log', "ERROR: ".$e->getMessage()."\n", FILE_APPEND);
    echo json_encode(['success'=>false, 'error'=>$e->getMessage()]);
}
