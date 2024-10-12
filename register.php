<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "secure_login";

// Crear conexion
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexion
if ($conn->connect_error) {
  // Evitar en producción
  die("Conexion fallida " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $email = $conn->real_escape_string($_POST['email']);
  $pass = $conn->real_escape_string($_POST['password']);

  $hashed_password = password_hash($pass, PASSWORD_BCRYPT);

  // Insertar usuario en la base de datos
  $sql = "INSERT INTO users (email, password) VALUES('$email', '$hashed_password')";

  if ($conn->query($sql) === TRUE) {
    echo "Registro exitoso <a href='login.php'>Ir al Login</a>";
  } else {
    // Evitar en producción
    echo "Error: " . $sql . "<br>" . $conn->error;
  }
}
?>

<form method="POST" action="register.php">
  <input type="text" name="email" placeholder="E-Mail" required><br>
  <input type="password" name="password" placeholder="Contraseña" required><br>
  <input type="submit" value="Registrarse"><br>
</form>