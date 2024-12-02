document.getElementById('registrationForm').addEventListener('submit', function(event) {
  const role = document.getElementById('role').value;
  const name = document.getElementById('name').value;
  const email = document.getElementById('email').value;
  const password = document.getElementById('password').value;

  // Simple validation
  if (role === "") {
    alert("Please select a role.");
    event.preventDefault();
  } else if (name === "" || email === "" || password === "") {
    alert("All fields are required.");
    event.preventDefault();
  } else if (password.length < 6) {
    alert("Password must be at least 6 characters long.");
    event.preventDefault();
  }
});
