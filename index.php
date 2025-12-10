<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>VetConnect - Registration</title>
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
    <h2>Register for VetConnect</h2>
    
    <form id="registrationForm" action="register.php" method="POST">
      <label for="role">Select Role:</label>
      <select id="role" name="role" required>
        <option value="">Choose Role</option>
        <option value="Veterinarian">Veterinarian</option>
        <option value="PetOwner">Pet Owner</option>
      </select>

      <label for="name">Full Name:</label>
      <input type="text" id="name" name="name" required>

      <label for="email">Email:</label>
      <input type="email" id="email" name="email" required>

      <label for="password">Password:</label>
      <input type="password" id="password" name="password" required>

      <button type="submit">Register</button>
      
      <div class="form-link">
        <p>Already have an account? <a href="login.php">Login here</a></p>
      </div>
    </form>
  </div>

  <script src="formValidation.js"></script>

</body>
</html>
