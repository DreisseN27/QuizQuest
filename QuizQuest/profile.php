<?php
session_start();

// Only allow logged-in users
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id   = $_SESSION['user_id'];
$username  = $_SESSION['username'];
$role      = $_SESSION['role']; // 'student' or 'teacher'

// DB connection
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "quizmaker";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$info_error   = "";
$info_success = "";

// --- Fetch current user info ---
$user_sql = "SELECT id, username, role, full_name, email, created_at FROM users WHERE id = ?";
$stmt = $conn->prepare($user_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();

if ($user_result->num_rows !== 1) {
    // something wrong, force logout
    session_destroy();
    header("Location: login.php");
    exit;
}

$user_data = $user_result->fetch_assoc();
$current_full_name = $user_data['full_name'];
$current_email     = $user_data['email'];
$current_role      = $user_data['role'];
$created_at        = $user_data['created_at'];

// ---------- STUDENT SECTION HANDLING ----------
$current_section_id   = null;
$current_section_name = null;

// If this is a student, load their current section and possible sections
$sections = [];

if ($current_role === 'student') {
    // Load all sections for dropdown
    $sec_sql = "SELECT id, section_name, grade_level FROM sections ORDER BY grade_level, section_name";
    $sec_res = $conn->query($sec_sql);
    if ($sec_res && $sec_res->num_rows > 0) {
        while ($row = $sec_res->fetch_assoc()) {
            $sections[] = $row;
        }
    }

    // Check if student already has a section
    $ss_sql = "
        SELECT s.id, s.section_name, s.grade_level
        FROM student_sections ss
        JOIN sections s ON ss.section_id = s.id
        WHERE ss.student_id = ?
        ORDER BY ss.created_at DESC
        LIMIT 1
    ";
    $stmt = $conn->prepare($ss_sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $ss_res = $stmt->get_result();

    if ($ss_res && $ss_res->num_rows === 1) {
        $row                  = $ss_res->fetch_assoc();
        $current_section_id   = $row['id'];
        $current_section_name = $row['section_name'] . " (Grade " . $row['grade_level'] . ")";
    }
}

// Handle student section update
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update_section']) && $current_role === 'student') {
    $new_section_id = (int) $_POST['section_id'];

    // Basic validation: must be a valid section
    $check_sql = "SELECT id FROM sections WHERE id = ?";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("i", $new_section_id);
    $stmt->execute();
    $check_res = $stmt->get_result();

    if ($check_res && $check_res->num_rows === 1) {
        // Insert or update record in student_sections
        // simplest: add new record (history)
        $ins_sql = "INSERT INTO student_sections (student_id, section_id) VALUES (?, ?)";
        $stmt    = $conn->prepare($ins_sql);
        $stmt->bind_param("ii", $user_id, $new_section_id);

        if ($stmt->execute()) {
            $info_success = "Section updated successfully.";
            // refresh current section display
            $current_section_id = $new_section_id;

            // get section name
            foreach ($sections as $sec) {
                if ($sec['id'] == $new_section_id) {
                    $current_section_name = $sec['section_name'] . " (Grade " . $sec['grade_level'] . ")";
                    break;
                }
            }
        } else {
            $info_error = "Failed to update section. Please try again.";
        }
    } else {
        $info_error = "Invalid section selected.";
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Profile - QuizQuest</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="teacher.css"><!-- reuse navbar + background -->
</head>
<body>

<nav class="navbar navbar-expand-lg fixed-top my-navbar">
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav"
            aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="mainNav">
      <ul class="nav nav-pills nav-pills-small w-100 align-items-center">
        <div class="d-flex justify-content-center flex-grow-1 gap-2">
          <li class="nav-item">
            <a class="nav-link" href="profile.php">Profile</a>
          </li>
          <?php if ($current_role === 'teacher'): ?>
              <li class="nav-item">
                <a class="nav-link" href="teacher.php">Teacher Dashboard</a>
              </li>
          <?php else: ?>
              <li class="nav-item">
                <a class="nav-link" href="student.php">Student Dashboard</a>
              </li>
          <?php endif; ?>
        </div>

        <li class="nav-item ms-auto me-3">
          <a class="nav-link" href="logout.php">Logout (<?php echo htmlspecialchars($username); ?>)</a>
        </li>
      </ul>
    </div>
</nav>

<header class="header">
    <div class="logo-container text-center">
        <img src="assets/images/logo.png" alt="QuizQuest Logo">
    </div>
</header>

<main class="container py-3 mt-2">
    <div class="row justify-content-center">
        <div class="col-md-8">

            <div class="card shadow-sm">
                <div class="card-body">
                    <h4 class="mb-3">My Profile</h4>

                    <?php if (!empty($info_error)): ?>
                        <div class="alert alert-danger py-2"><?php echo $info_error; ?></div>
                    <?php endif; ?>

                    <?php if (!empty($info_success)): ?>
                        <div class="alert alert-success py-2"><?php echo $info_success; ?></div>
                    <?php endif; ?>

                    <dl class="row mb-0">
                        <dt class="col-sm-3">Username</dt>
                        <dd class="col-sm-9"><?php echo htmlspecialchars($user_data['username']); ?></dd>

                        <dt class="col-sm-3">Full Name</dt>
                        <dd class="col-sm-9"><?php echo htmlspecialchars($current_full_name); ?></dd>

                        <dt class="col-sm-3">Role</dt>
                        <dd class="col-sm-9 text-capitalize"><?php echo htmlspecialchars($current_role); ?></dd>

                        <dt class="col-sm-3">Email</dt>
                        <dd class="col-sm-9"><?php echo htmlspecialchars($current_email); ?></dd>

                        <dt class="col-sm-3">Member Since</dt>
                        <dd class="col-sm-9"><?php echo date('M d, Y', strtotime($created_at)); ?></dd>
                    </dl>
                </div>
            </div>

            <?php if ($current_role === 'student'): ?>
                <!-- Student Section Card -->
                <div class="card mt-3 shadow-sm">
                    <div class="card-body">
                        <h5 class="mb-3">My Section</h5>

                        <p class="mb-2">
                            <strong>Current Section:</strong>
                            <?php
                                if ($current_section_name) {
                                    echo htmlspecialchars($current_section_name);
                                } else {
                                    echo '<span class="text-muted">Not set yet.</span>';
                                }
                            ?>
                        </p>

                        <form method="POST" class="row g-2 align-items-center">
                            <div class="col-sm-8">
                                <label for="section_id" class="form-label mb-1">Change Section:</label>
                                <select name="section_id" id="section_id" class="form-select form-select-sm">
                                    <option value="">-- Select Section --</option>
                                    <?php foreach ($sections as $sec): ?>
                                        <option value="<?php echo $sec['id']; ?>"
                                            <?php if ($sec['id'] == $current_section_id) echo 'selected'; ?>>
                                            <?php echo htmlspecialchars($sec['section_name']) . ' (Grade ' . htmlspecialchars($sec['grade_level']) . ')'; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-sm-4 mt-2">
                                <button type="submit" name="update_section" class="btn btn-primary btn-sm w-100">
                                    Save Section
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($current_role === 'teacher'): ?>
                <!-- Placeholder for future teacher section management -->
                <div class="card mt-3 shadow-sm">
                    <div class="card-body">
                        <h5 class="mb-2">Teacher Tools</h5>
                        <p class="small text-muted mb-0">
                            In the future, you can manage your sections and students here.
                        </p>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php $conn->close(); ?>
