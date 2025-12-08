<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: login.php");
    exit;
}

$mysqli = new mysqli("localhost","root","","quizmaker");
if ($mysqli->connect_error) die("Connection failed: ".$mysqli->connect_error);

$teacher_id = (int)$_SESSION['user_id'];
$teacher_name = $_SESSION['username'] ?? 'Teacher';

// Generate random 7-char class code
function generateClassCode($len = 7){
    $chars = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789'; // removed ambiguous chars
    $code = '';
    for($i=0;$i<$len;$i++) $code .= $chars[random_int(0, strlen($chars)-1)];
    return $code;
}

// Handle class creation (modal form)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['title'], $_POST['section'])) {
    $title = trim($_POST['title']);
    $section = trim($_POST['section']);

    if ($title === '' || $section === '') {
        // simple server-side validation: redirect back (modal will not show server errors)
        header("Location: classes.php");
        exit;
    }

    // Ensure unique code
    do {
        $code = generateClassCode();
        $chk = $mysqli->prepare("SELECT id FROM classes WHERE class_code = ?");
        $chk->bind_param("s", $code);
        $chk->execute();
        $chk->store_result();
    } while ($chk->num_rows > 0);
    $chk->close();

    $ins = $mysqli->prepare("INSERT INTO classes (teacher_id, title, section, class_code, created_at) VALUES (?, ?, ?, ?, NOW())");
    $ins->bind_param("isss", $teacher_id, $title, $section, $code);
    $ins->execute();
    $ins->close();

    header("Location: classes.php");
    exit;
}

// Fetch classes for this teacher
$stmt = $mysqli->prepare("SELECT id, title, section, class_code, created_at FROM classes WHERE teacher_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$res = $stmt->get_result();
$classes = $res->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Classes - QuizQuest</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="assets/css/teacher.css">
</head>
<body>
<canvas id="background-canvas"></canvas>

<div class="sidebar">
    <img src="assets/images/logo.png" class="logo-img" alt="QuizQuest">
    <div class="menu-wrapper">
        <div class="nav">
            <a class="nav-item" href="profile.php"><i data-lucide="user"></i> Profile (<?php echo htmlspecialchars($teacher_name); ?>)</a>
            <a class="nav-item active" href="classes.php"><i data-lucide="layout"></i> Classes</a>
            <a class="nav-item" href="leaderboard.php"><i data-lucide="award"></i> Leaderboard</a>
        </div>
    </div>
    <a class="logout" href="logout.php"><i data-lucide="log-out"></i> Logout</a>
</div>

<div class="content">
    <div class="avatar-container">
        <span class="greeting">Hello! <?php echo htmlspecialchars($teacher_name); ?></span>
        <img src="https://i.imgur.com/oQEsWSV.png" alt="avatar" class="freiren-avatar">
    </div>

    <h2 class="quizzes-title mb-4">Your Classes</h2>
    <div class="row g-4">
        <!-- Create class card (not a link; triggers modal) -->
        <div class="col-md-4 col-sm-6">
            <div class="card subject-card h-100 d-flex align-items-center justify-content-center" style="cursor:pointer; background:linear-gradient(135deg,#2563EB,#3B82F6);" data-bs-toggle="modal" data-bs-target="#createClassModal">
                <h3 class="card-title text-center">+ Create Class</h3>
            </div>
        </div>

        <!-- Class cards -->
        <?php if (!empty($classes)): ?>
            <?php foreach($classes as $class): ?>
                <div class="col-md-4 col-sm-6">
                    <div class="card subject-card h-100" style="background: linear-gradient(135deg,#0ea5e9,#0284c7);" onclick="enterClass(<?php echo (int)$class['id']; ?>)">
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h5 class="card-title mb-0"><?php echo htmlspecialchars($class['title']); ?></h5>
                                <span class="badge" style="background:rgba(255,255,255,0.15);color:#fff;">Code: <?php echo htmlspecialchars($class['class_code']); ?></span>
                            </div>
                            <p class="card-text small mb-1">Section: <?php echo htmlspecialchars($class['section']); ?></p>
                            <div class="mt-auto text-end">
                                <small class="text-muted">Created: <?php echo date('M d, Y', strtotime($class['created_at'])); ?></small>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <!-- nothing else -->
        <?php endif; ?>
    </div>
</div>

<!-- Create Class Modal -->
<div class="modal fade" id="createClassModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form method="POST" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Create New Class</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
            <label class="form-label">Class Title</label>
            <input type="text" name="title" class="form-control" required maxlength="255">
        </div>
        <div class="mb-3">
            <label class="form-label">Section</label>
            <input type="text" name="section" class="form-control" required maxlength="255">
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-primary">Create Class</button>
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/lucide@0.259.0/dist/lucide.js"></script>
<script>
// go into class (redirect to teacher.php inside class)
function enterClass(classId){
    window.location.href = `teacher.php?class_id=${classId}`;
}
lucide.replace();
</script>
<script src="teacherscripts.js"></script>
</body>
</html>
<?php $mysqli->close(); ?>
