<?php
session_start(); // Iniciar la sesión

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "secure_login";

// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
  die("Conexión fallida: " . $conn->connect_error);
}

// Función para generar un token 
function generarToken() {
  return bin2hex(random_bytes(32));
}

// Verificar si el usuario está bloqueado por intentos fallidos
if (isset($_SESSION['bloqueo_tiempo']) && time() < $_SESSION['bloqueo_tiempo']) {
  $tiempo_restante = $_SESSION['bloqueo_tiempo'] - time();
  $min_restantes = floor($tiempo_restante / 60);
  $seg_restantes = $tiempo_restante % 60;
  
  die("Intenta nuevamente en $min_restantes minutos y $seg_restantes segundos.");
}

// Si es un método GET, generamos el token y lo almacenamos en la sesión
if ($_SERVER["REQUEST_METHOD"] == "GET") {
  $_SESSION['token'] = generarToken();
}

// Si es un método POST, verificamos el token 
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  // Verificar si el token CSRF enviado es válido
  if (!isset($_POST['token']) || $_POST['token'] !== $_SESSION['token']) {
    die("Error: Token no válido");
  }

  // Sanitizar los datos de entrada
  $email = $conn->real_escape_string($_POST['email']);
  $pass = $conn->real_escape_string($_POST['password']);

  // Verificar si el usuario existe
  $sql = "SELECT * FROM users WHERE email='$email'";
  $result = $conn->query($sql);

  if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    // Verificar la contraseña
    if (password_verify($pass, $row['password'])) {
      echo "Inicio de sesión exitoso";
      // Crear la sesión
      $_SESSION['id'] = $row['id'];

      // Reiniciar el token y los intentos fallidos
      $_SESSION['token'] = generarToken();
      $_SESSION['intentos'] = 0;
    } else {
      echo "Contraseña incorrecta";

      // Registrar intento fallido
      if (!isset($_SESSION['intentos'])) {
        $_SESSION['intentos'] = 0;
      }
      $_SESSION['intentos']++;

      // Si los intentos fallidos alcanzan 3, bloquear al usuario por 5 minutos
      if ($_SESSION['intentos'] >= 3) {
        $_SESSION['bloqueo_tiempo'] = time() + (5 * 60); // Bloquear por 5 minutos
        die("Has alcanzado el límite de intentos, intentalo nuevamente en 5 minutos.");
      }
    }
  } else {
    echo "El usuario no existe";
    // Registrar intento fallido
    if (!isset($_SESSION['intentos'])) {
      $_SESSION['intentos'] = 0;
    }
    $_SESSION['intentos']++;

    // Si los intentos fallidos alcanzan 3, bloquear al usuario por 5 minutos
    if ($_SESSION['intentos'] >= 3) {
      $_SESSION['bloqueo_tiempo'] = time() + (5 * 60);
      die(" Has alcanzado el límite de intentos, intentalo nuevamente en 5 minutos.");
    }
  }
}
?>

<!-- Formulario de inicio de sesión -->
<form method="POST" action="login.php">
  <input type="text" name="email" placeholder="E-Mail" required><br>
  <input type="password" name="password" placeholder="Contraseña" required><br>
  <input type="hidden" name="token" value="<?php echo $_SESSION['token']; ?>"><br>
  <input type="submit" value="Iniciar sesión"><br>
</form>
