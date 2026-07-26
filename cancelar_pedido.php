<?php
// ============================================================
// CANCELAR PEDIDO - TecnoStore
// ============================================================

// Incluir conexión a la base de datos
require_once 'config/conexion.php';

// Iniciar sesión
session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}

// Obtener ID del pedido desde el formulario
$id_compra = isset($_POST['id_compra']) ? (int)$_POST['id_compra'] : 0;

if ($id_compra <= 0) {
    header('Location: gestion_pedidos.php');
    exit;
}

$id_cliente = $_SESSION['usuario']['id'];

// Verificar que el pedido pertenezca al usuario y esté pendiente
$sql = "SELECT id_compra, estado, id_producto, cantidad FROM COMPRA WHERE id_compra = ? AND id_cliente = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $id_compra, $id_cliente);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows === 0) {
    header('Location: gestion_pedidos.php');
    exit;
}

$pedido = $resultado->fetch_assoc();

// Solo se puede cancelar si está pendiente
if (($pedido['estado'] ?? 'pendiente') !== 'pendiente') {
    header('Location: gestion_pedidos.php');
    exit;
}

// ============================================================
// ACTUALIZAR ESTADO A CANCELADO
// ============================================================
$sql_update = "UPDATE COMPRA SET estado = 'cancelado' WHERE id_compra = ?";
$stmt_update = $conn->prepare($sql_update);
$stmt_update->bind_param("i", $id_compra);

if ($stmt_update->execute()) {
    // ============================================================
    // DEVOLVER STOCK (sumar la cantidad cancelada)
    // ============================================================
    $stmt_stock = $conn->prepare("UPDATE PRODUCTO SET stock = stock + ? WHERE id_producto = ?");
    $stmt_stock->bind_param("ii", $pedido['cantidad'], $pedido['id_producto']);
    $stmt_stock->execute();
    $stmt_stock->close();
    
    $_SESSION['mensaje_exito'] = "✅ El pedido #$id_compra ha sido cancelado correctamente. El stock ha sido devuelto.";
} else {
    $_SESSION['mensaje_error'] = "❌ Error al cancelar el pedido.";
}

$stmt_update->close();

header('Location: gestion_pedidos.php');
exit;
?>
