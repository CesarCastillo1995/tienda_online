<?php
// ============================================================
// DETALLE DE PEDIDO - TecnoStore
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

// Obtener ID del pedido desde la URL
$id_compra = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_compra <= 0) {
    header('Location: gestion_pedidos.php');
    exit;
}

// Obtener datos del pedido
$id_cliente = $_SESSION['usuario']['id'];

$sql = "SELECT c.*, p.nombre as producto_nombre, p.precio, p.descripcion as producto_descripcion 
        FROM COMPRA c 
        JOIN PRODUCTO p ON c.id_producto = p.id_producto 
        WHERE c.id_compra = ? AND c.id_cliente = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $id_compra, $id_cliente);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows === 0) {
    header('Location: gestion_pedidos.php');
    exit;
}

$pedido = $resultado->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📄 Detalle del Pedido - TecnoStore</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .container-detalle {
            max-width: 700px;
            margin: 40px auto;
            background: white;
            padding: 35px;
            border-radius: 12px;
            box-shadow: 0 0 30px rgba(0,0,0,0.1);
        }
        .container-detalle h1 {
            text-align: center;
            border-bottom: 3px solid #3498db;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }
        .btn-volver {
            display: inline-block;
            background: #7f8c8d;
            color: white;
            padding: 10px 25px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
        }
        .btn-volver:hover {
            background: #5d6d7e;
        }
        .btn-volver.primary {
            background: #3498db;
        }
        .btn-volver.primary:hover {
            background: #2980b9;
        }
        .card-detalle {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
            border-left: 4px solid #3498db;
        }
        .detalle-item {
            padding: 8px 0;
            border-bottom: 1px solid #ddd;
            display: flex;
            justify-content: space-between;
        }
        .detalle-item:last-child {
            border-bottom: none;
        }
        .detalle-item strong {
            color: #2c3e50;
        }
        .detalle-item .value {
            color: #34495e;
        }
        .badge-estado {
            display: inline-block;
            padding: 3px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-estado.pendiente {
            background: #fef9e7;
            color: #f39c12;
        }
        .badge-estado.pagado {
            background: #d5f5e3;
            color: #27ae60;
        }
        .badge-estado.enviado {
            background: #d6eaf8;
            color: #2980b9;
        }
        .badge-estado.entregado {
            background: #2c3e50;
            color: white;
        }
        .badge-estado.cancelado {
            background: #fde8e8;
            color: #e74c3c;
        }
        .precio-total {
            font-size: 18px;
            font-weight: 700;
            color: #27ae60;
        }
        .acciones {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container-detalle">
        <h1>📄 Detalle del Pedido</h1>
        
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap;">
            <a href="gestion_pedidos.php" class="btn-volver">← Volver a mis pedidos</a>
        </div>

        <div class="card-detalle">
            <div style="background: #eaf2f8; padding: 10px 15px; border-radius: 6px; margin-bottom: 15px;">
                <strong style="color: #2c3e50;">📦 Información del pedido #<?php echo $pedido['id_compra']; ?></strong>
            </div>

            <div class="detalle-item">
                <strong>Producto:</strong>
                <span class="value"><?php echo htmlspecialchars($pedido['producto_nombre']); ?></span>
            </div>
            <div class="detalle-item">
                <strong>Descripción del producto:</strong>
                <span class="value"><?php echo htmlspecialchars($pedido['producto_descripcion']); ?></span>
            </div>
            <div class="detalle-item">
                <strong>Cantidad:</strong>
                <span class="value"><?php echo $pedido['cantidad']; ?></span>
            </div>
            <div class="detalle-item">
                <strong>Precio unitario:</strong>
                <span class="value">$<?php echo number_format($pedido['precio'], 0, ',', '.'); ?></span>
            </div>
            <div class="detalle-item" style="border-bottom: 2px solid #27ae60; padding-bottom: 10px;">
                <strong>Total:</strong>
                <span class="precio-total">$<?php echo number_format($pedido['total'], 0, ',', '.'); ?></span>
            </div>
            <div class="detalle-item">
                <strong>Fecha de compra:</strong>
                <span class="value"><?php echo date('d/m/Y H:i', strtotime($pedido['fecha'])); ?></span>
            </div>
            <div class="detalle-item">
                <strong>Estado actual:</strong>
                <span class="badge-estado <?php echo strtolower($pedido['estado'] ?? 'pendiente'); ?>">
                    <?php echo ucfirst($pedido['estado'] ?? 'Pendiente'); ?>
                </span>
            </div>
        </div>

        <div class="acciones">
            <a href="index.php" class="btn-volver">🏠 Volver a la tienda</a>
        </div>
    </div>
</body>
</html>
