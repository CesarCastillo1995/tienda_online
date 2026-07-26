<?php
// ============================================================
// CONEXIÓN A BASE DE DATOS MySQL
// ============================================================

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "TIENDA";

// Crear conexión usando MySQLi
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// Configurar caracteres UTF-8 para evitar problemas con tildes
$conn->set_charset("utf8");

// Descomentar para usar PDO en lugar de MySQLi
/*
try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}
*/
?>