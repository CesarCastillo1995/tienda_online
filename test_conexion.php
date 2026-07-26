<?php
// Incluir el archivo de conexión
include 'config/conexion.php';

// Verificar si la conexión está activa
if ($conn) {
    echo "✅ Conexión exitosa a la base de datos TIENDA";
    echo "<br><br>🖥️ Servidor: " . $conn->server_info;
    echo "<br>📊 Base de datos: " . $dbname;
} else {
    echo "❌ Error de conexión";
}
?>