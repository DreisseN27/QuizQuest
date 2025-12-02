<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Bootstrap 5 CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">

  <!-- Custom CSS -->
  <link rel="stylesheet" href="./teacher.css">
  <link rel="stylesheet" href="style.css">

  <title>File Upload and Download</title>
</head>

<body>
  <!-- Full-width Navbar -->
  <nav class="navbar navbar-expand-lg fixed-top my-navbar">
    <div class="container">
      <!-- Brand -->
      <a class="navbar-brand" href="#">
  		<img src="images/logo1.png" alt="Logo" style="height:32px;">
		</a>

      <!-- Toggler for small screens -->
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav"
              aria-controls="mainNav" saria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>

      <!-- Nav links -->
      <div class="collapse navbar-collapse" id="mainNav">
      <ul class="nav nav-pills nav-fill nav-pills-small">
        <li class="nav-item">
          <a class="nav-link active" aria-current="page" href="#">Profile</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="#">Make a Quiz</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="#">Leaderboard</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="upload.php">Upload/View Notes</a>
        </li>
      </ul>
    </div>
  </nav>
	
  <!-- Main content -->
  <div class="container mt-5 pt-4">
    <h2>Upload a File</h2>
    <form action="#" method="POST" enctype="multipart/form-data">
      <div class="mb-3">
        <label for="file" class="form-label">Select file</label>
        <input type="file" class="form-control" name="file" id="file" required>
      </div>
      <button type="submit" class="btn btn-primary">Upload file</button>
    </form>
  </div>

  <!-- Bootstrap 5 JS bundle -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
