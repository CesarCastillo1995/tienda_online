<?php
require_once 'config/conexion.php';

$emails = ['juan.perez@email.com', 'maria.gonzalez@email.com', 'carlos.rodriguez@email.com'];
$password = '123456';
$hash = password_hash($password, PASSWORD_DEFAULT);

foreach ($emails as $email) {
    $stmt = $conn->prepare("UPDATE CLIENTE SET password = ? WHERE email = ?");
    $stmt->bind_param("ss", $hash, $email);
    
    if ($stmt->execute()) {
        echo "✅ Contraseña actualizada para: $email<br>";
    } else {
        echo "❌ Error para $email: " . $conn->error . "<br>";
    }
    $stmt->close();
}

echo "<br><br>🔑 Contraseña: <strong>123456</strong>";
echo "<br><a href='login.php'>Ir al login</a>";
?>