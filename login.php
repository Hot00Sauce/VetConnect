<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>VetConnect - Login</title>
  <link rel="stylesheet" href="style.css">
  <style>
    body {
      background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      padding: 1rem;
    }
  </style>
</head>
<body>

  <div class="form-container">
    <h2>Login to VetConnect</h2>
    
    <form id="loginForm" action="login_backend.php" method="POST">
      <label for="email">Email:</label>
      <input type="email" id="email" name="email" required>
      
      <label for="password">Password:</label>
      <input type="password" id="password" name="password" required>

      <button type="submit">Login</button>
      
      <div class="form-link">
        <p>Don't have an account? <a href="index.php">Register here</a></p>
      </div>
    </form>
  </div>

</body>
</html>
