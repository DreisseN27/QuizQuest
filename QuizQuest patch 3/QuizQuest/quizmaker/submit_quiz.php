<?php
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    echo json_encode(['success'=>false, 'error'=>'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || empty($data['class_code']) || empty($data['title']) || empty($data['questions'])) {
    echo json_encode(['success'=>false, 'error'=>'Invalid data']);
    exit;
}

require 'db.php';

$class_code = trim($data['class_code']);
$title = trim($data['title']);
$teacher_id = $_SESSION['user_id'];

if ($class_code === '' || $title === '') {
    echo json_encode(['success'=>false, 'error'=>'Class code and title cannot be empty']);
    exit;
}

try {
    $pdo->beginTransaction();


    // Insert quiz
    $stmt = $pdo->prepare("INSERT INTO quizzes (teacher_id, class_code, title) VALUES (?, ?, ?)");
    $stmt->execute([$teacher_id, $class_code, $title]);
    $quiz_id = $pdo->lastInsertId();

    // Insert questions and choices
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
    echo json_encode(['success'=>true, 'quiz_id'=>$quiz_id]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success'=>false, 'error'=>$e->getMessage()]);
}
