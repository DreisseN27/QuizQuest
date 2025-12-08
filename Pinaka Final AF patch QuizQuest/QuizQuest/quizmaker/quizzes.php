<?php
header('Content-Type: application/json');
require __DIR__ . '/db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    echo json_encode(['success'=>false, 'error'=>'Unauthorized']);
    exit;
}

$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON,true);

$action = $_GET['action'] ?? $_POST['action'] ?? $input['action'] ?? null;
if (!$action) {
    echo json_encode(['success'=>false, 'error'=>'No action specified']);
    exit;
}

// VIEW QUIZZES (teacher) -- optional class_id filter
if ($action === 'view') {
    $class_id = $_GET['class_id'] ?? $input['class_id'] ?? null;
    if ($class_id) {
        $stmt = $pdo->prepare("SELECT id, class_code, title FROM quizzes WHERE teacher_id=? AND class_id=? ORDER BY created_at DESC");
        $stmt->execute([$_SESSION['user_id'], (int)$class_id]);
    } else {
        $stmt = $pdo->prepare("SELECT id, class_code, title FROM quizzes WHERE teacher_id=? ORDER BY created_at DESC");
        $stmt->execute([$_SESSION['user_id']]);
    }
    $quizzes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($quizzes);
    exit;
}

// DELETE QUIZ
if ($action === 'delete') {
    $id = $_POST['id'] ?? $input['id'] ?? null;
    if (!$id) { echo json_encode(['success'=>false,'error'=>'Quiz ID required']); exit; }
    $stmt = $pdo->prepare("DELETE FROM quizzes WHERE id=? AND teacher_id=?");
    echo json_encode(['success'=>$stmt->execute([$id, $_SESSION['user_id']])]);
    exit;
}

// DETAILS
if ($action === 'details') {
    $quiz_id = $_GET['quiz_id'] ?? $input['quiz_id'] ?? null;
    if (!$quiz_id) { echo json_encode([]); exit; }
    $stmt = $pdo->prepare("SELECT * FROM quizzes WHERE id=? AND teacher_id=?");
    $stmt->execute([$quiz_id, $_SESSION['user_id']]);
    $quiz = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$quiz) { echo json_encode([]); exit; }

    $qStmt = $pdo->prepare("SELECT * FROM questions WHERE quiz_id=? ORDER BY id ASC");
    $qStmt->execute([$quiz_id]);
    $questions = $qStmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($questions as &$q) {
        if ($q['question_type'] === 'multiple') {
            $cStmt = $pdo->prepare("SELECT choice_label, choice_text FROM choices WHERE question_id=? ORDER BY choice_label");
            $cStmt->execute([$q['id']]);
            $q['choices'] = $cStmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
    echo json_encode(['quiz'=>$quiz, 'questions'=>$questions]);
    exit;
}

// UPDATE (same as before) - keep unchanged
if ($action === 'update') {
    if (!$input || empty($input['quiz_id']) || empty($input['title']) || !isset($input['questions'])) {
        echo json_encode(['success'=>false,'error'=>'Invalid data']);
        exit;
    }

    $quiz_id = $input['quiz_id'];
    $title = htmlspecialchars(trim($input['title']));
    $questions = $input['questions'];
    $deletedQuestions = $input['deletedQuestions'] ?? [];

    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("UPDATE quizzes SET title=? WHERE id=? AND teacher_id=?");
        $stmt->execute([$title,$quiz_id,$_SESSION['user_id']]);

        if (!empty($deletedQuestions)) {
            $in = str_repeat('?,', count($deletedQuestions)-1) . '?';
            $stmtDelChoices = $pdo->prepare("DELETE FROM choices WHERE question_id IN ($in)");
            $stmtDelChoices->execute($deletedQuestions);
            $stmtDelQuestions = $pdo->prepare("DELETE FROM questions WHERE id IN ($in)");
            $stmtDelQuestions->execute($deletedQuestions);
        }

        foreach ($questions as $q) {
            $text = htmlspecialchars(trim($q['text']));
            $type = htmlspecialchars($q['type']);
            $correct = htmlspecialchars(trim($q['correct']));

            if (!empty($q['id'])) {
                $stmtQ = $pdo->prepare("UPDATE questions SET question_text=?, question_type=?, correct_answer=? WHERE id=?");
                $stmtQ->execute([$text,$type,$correct,$q['id']]);

                if ($type === 'multiple') {
                    $stmtDel = $pdo->prepare("DELETE FROM choices WHERE question_id=?");
                    $stmtDel->execute([$q['id']]);
                    $stmtC = $pdo->prepare("INSERT INTO choices(question_id,choice_label,choice_text) VALUES(?,?,?)");
                    foreach ($q['choices'] as $i=>$c) {
                        $label = chr(65+$i);
                        $stmtC->execute([$q['id'],$label,htmlspecialchars(trim($c))]);
                    }
                }
            } else {
                $stmtIns = $pdo->prepare("INSERT INTO questions (quiz_id,question_text,question_type,correct_answer) VALUES (?,?,?,?)");
                $stmtIns->execute([$quiz_id,$text,$type,$correct]);
                $question_id = $pdo->lastInsertId();
                if ($type === 'multiple') {
                    $stmtC = $pdo->prepare("INSERT INTO choices(question_id,choice_label,choice_text) VALUES(?,?,?)");
                    foreach ($q['choices'] as $i=>$c) {
                        $label = chr(65+$i);
                        $stmtC->execute([$question_id,$label,htmlspecialchars(trim($c))]);
                    }
                }
            }
        }

        $pdo->commit();
        echo json_encode(['success'=>true]);
    } catch(Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
    }
    exit;
}

// NEW: CLASS INFO (secure) - returns class_code and title if
if ($action === 'classinfo') {
    $class_id = $_GET['class_id'] ?? $input['class_id'] ?? null;
    if (!$class_id) { echo json_encode(['success'=>false,'error'=>'No class_id']); exit; }
    $stmt = $pdo->prepare("SELECT id, title, section, class_code FROM classes WHERE id=? AND teacher_id=? LIMIT 1");
    $stmt->execute([$class_id, $_SESSION['user_id']]);
    $c = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$c) { echo json_encode(['success'=>false,'error'=>'Not found or unauthorized']); exit; }
    echo json_encode(['success'=>true, 'class'=>$c]);
    exit;
}

echo json_encode(['success'=>false,'error'=>'Unknown action']);
exit;
