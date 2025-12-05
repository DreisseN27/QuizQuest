<?php
session_start();

// Only allow logged-in students
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id   = $_SESSION['user_id'];
$username  = $_SESSION['username'] ?? 'Student';
$role      = $_SESSION['role'] ?? 'student';

if ($role !== 'student') {
    die("Access denied.");
}

// Connect to database
$conn = new mysqli("localhost", "root", "", "quizmaker");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$info_error   = "";
$info_success = "";

// Fetch user data
$stmt = $conn->prepare("SELECT id, username, role, full_name, email, created_at FROM users WHERE id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    session_destroy();
    header("Location: login.php");
    exit;
}

$user_data = $result->fetch_assoc();
$current_full_name = $user_data['full_name'] ?? '';
$current_email     = $user_data['email'] ?? '';
$current_role      = $user_data['role'] ?? 'student';
$created_at        = $user_data['created_at'] ?? '';
$current_image     = 'https://i.imgur.com/oQEsWSV.png';
$incomplete_info   = empty($current_full_name) || empty($current_email);

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $full_name = $_POST['full_name'] ?? $current_full_name;
    $email     = $_POST['email'] ?? $current_email;

    $update = $conn->prepare("UPDATE users SET full_name=?, email=? WHERE id=?");
    $update->bind_param("ssi", $full_name, $email, $user_id);

    if ($update->execute()) {
        $info_success = "Profile updated successfully.";
        $current_full_name = $full_name;
        $current_email = $email;
        $incomplete_info = empty($current_full_name) || empty($current_email);
    } else {
        $info_error = "Failed to update profile.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Profile - QuizQuest</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="teacher.css">
<style>
.content{padding:2rem;min-height:100vh;}
.greeting-box{display:flex;align-items:center;gap:2rem;background:rgba(255,255,255,0.05);backdrop-filter:blur(12px);padding:1.5rem 2rem;border-radius:20px;margin-bottom:2rem;box-shadow:0 8px 20px rgba(0,0,0,0.3);transition:all 0.5s ease;}
.greeting-img{width:150px;height:150px;object-fit:cover;border-radius:15px;border:3px solid #fff;flex-shrink:0;}
.greeting-text{flex:1;}
.greeting-text small{color:#ccc;font-size:0.9rem;}
.greeting-text h2{margin:0;font-size:2rem;}
.greeting-box-line{border-top:1px solid rgba(255,255,255,0.3);margin:0.5rem 0;}
#profileEdit{display:none;}
.form-control{background:rgba(255,255,255,0.12);border:1px solid rgba(255,255,255,0.25);color:#fff;border-radius:10px;}
.form-control:focus{background:rgba(255,255,255,0.15);border-color:#fff;box-shadow:none;color:#fff;}
.btn-primary{border-radius:10px;padding:0.6rem 1.8rem;font-weight:600;font-size:1rem;transition:all 0.3s;}
.btn-primary:hover{transform:translateY(-2px);}
.single-profile-card{background:rgba(255,255,255,0.05);backdrop-filter:blur(15px);border-radius:20px;padding:2rem;box-shadow:0 10px 25px rgba(0,0,0,0.25);display:flex;flex-direction:column;gap:1.5rem;transition:all 0.3s ease;}
.single-profile-card:hover{transform:translateY(-3px);}
.profile-row{display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid rgba(255,255,255,0.2);padding:0.75rem 0;}
.profile-row:last-child{border-bottom:none;}
.profile-row dt{font-weight:600;color:#ccc;font-size:1rem;}
.profile-row dd{font-size:1.2rem;margin:0;color:#fff;text-align:right;}
#profileView .btn-primary{align-self:center;}
</style>
</head>
<body>
<canvas id="background-canvas"></canvas>

<div class="sidebar">
    <img src="assets/images/logo.png" class="logo-img" alt="QuizQuest Logo">

    <div class="menu-wrapper">
        <div class="nav">
            <a class="nav-item active" href="profile.php">
                <i data-lucide="user"></i> Profile (<?php echo htmlspecialchars($username); ?>)
            </a>
            <a class="nav-item" href="student.php">
                <i data-lucide="layout"></i> Dashboard
            </a>
            <a class="nav-item" href="leaderboard.php">
                <i data-lucide="award"></i> Leaderboard
            </a>
        </div>
    </div>

    <a class="logout" href="logout.php">
        <i data-lucide="log-out"></i> Logout
    </a>
</div>

<div class="content">
    <div class="greeting-box">
        <img src="<?php echo htmlspecialchars($current_image); ?>" class="greeting-img">
        <div class="greeting-text">
            <small>S.Y. 2025-2026 - 1st Semester</small>
            <div class="greeting-box-line"></div>
            <h2>Hello! <?php echo htmlspecialchars($current_full_name ?: $username); ?></h2>
            <?php if($incomplete_info): ?>
                <small>Please update your profile if any information is still blank.</small>
            <?php endif; ?>
        </div>
    </div>

    <div class="profile-card" id="profileView">
        <?php if($info_error): ?><div class="alert alert-danger py-2"><?php echo $info_error; ?></div><?php endif; ?>
        <?php if($info_success): ?><div class="alert alert-success py-2"><?php echo $info_success; ?></div><?php endif; ?>

        <div class="single-profile-card">
            <div class="profile-row"><dt>Username</dt><dd><?php echo htmlspecialchars($username); ?></dd></div>
            <div class="profile-row"><dt>Full Name</dt><dd><?php echo htmlspecialchars($current_full_name); ?></dd></div>
            <div class="profile-row"><dt>Email</dt><dd><?php echo htmlspecialchars($current_email); ?></dd></div>
            <div class="profile-row"><dt>Role</dt><dd><?php echo ucfirst($current_role); ?></dd></div>
            <div class="profile-row"><dt>Member Since</dt><dd><?php echo date('M d, Y', strtotime($created_at)); ?></dd></div>
            <div class="text-center mt-4">
                <button class="btn btn-primary btn-lg" id="editProfileBtn">Update Profile</button>
            </div>
        </div>
    </div>

    <div class="profile-card" id="profileEdit">
        <h4 class="mb-4 text-center">Edit Profile</h4>
        <form method="POST" enctype="multipart/form-data">
            <div class="text-center mb-4">
                <img src="<?php echo htmlspecialchars($current_image); ?>" id="editPreview" class="greeting-img mb-2">
                <input type="file" name="profile_image" class="form-control form-control-sm mt-2" onchange="previewImage(this)">
            </div>
            <div class="mb-3">
                <label class="form-label">Full Name</label>
                <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($current_full_name); ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($current_email); ?>" required>
            </div>
            <div class="d-flex justify-content-center gap-3 mt-4">
                <button type="submit" name="update_profile" class="btn btn-primary btn-lg">Save Changes</button>
                <button type="button" class="btn btn-outline-light btn-lg" id="cancelEdit">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="teacherscripts.js"></script>
<script>
const viewDiv = document.getElementById('profileView'); 
const editDiv = document.getElementById('profileEdit');
document.getElementById('editProfileBtn').onclick = ()=>{ viewDiv.style.display='none'; editDiv.style.display='grid'; };
document.getElementById('cancelEdit').onclick = ()=>{ editDiv.style.display='none'; viewDiv.style.display='grid'; };
function previewImage(input){ 
    const preview=document.getElementById('editPreview'); 
    const file=input.files[0]; 
    if(file){ 
        const reader=new FileReader(); 
        reader.onload=e=>preview.src=e.target.result; 
        reader.readAsDataURL(file); 
    } 
}
document.querySelectorAll('.sidebar .nav-item').forEach(item=>{
    item.addEventListener('click', e=>{
        const content=document.querySelector('.content'); 
        content.style.opacity=0; 
        setTimeout(()=>{ window.location.href=item.href; },300); 
        e.preventDefault(); 
    });
});
lucide.replace();
</script>
</body>
</html>
<?php $conn->close(); ?>
