<?php
// ============================================================
// GESTIÓN DE PEDIDOS - TecnoStore
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

// Obtener el ID del usuario logueado
$id_cliente = $_SESSION['usuario']['id'];

// ============================================================
// OBTENER TODOS LOS PEDIDOS DEL USUARIO
// ============================================================
$sql = "SELECT * FROM COMPRA WHERE id_cliente = ? ORDER BY fecha DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_cliente);
$stmt->execute();
$resultado = $stmt->get_result();

$total_pedidos = $resultado->num_rows;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📋 Mis Pedidos - TecnoStore</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .container-pedidos {
            max-width: 1000px;
            margin: 40px auto;
            background: white;
            padding: 35px;
            border-radius: 12px;
            box-shadow: 0 0 30px rgba(0,0,0,0.1);
        }
        .container-pedidos h1 {
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
            margin-bottom: 20px;
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
        .tabla-pedidos {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            font-size: 14px;
        }
        .tabla-pedidos th {
            background: #2c3e50;
            color: white;
            padding: 12px 15px;
            text-align: left;
        }
        .tabla-pedidos td {
            padding: 10px 15px;
            border-bottom: 1px solid #ecf0f1;
        }
        .tabla-pedidos tr:hover {
            background: #f8f9fa;
        }
        .tabla-pedidos .sin-datos {
            text-align: center;
            padding: 30px;
            color: #7f8c8d;
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
        .precio {
            font-weight: 700;
            color: #27ae60;
        }
        .btn-ver {
            display: inline-block;
            background: #3498db;
            color: white;
            padding: 5px 15px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 13px;
        }
        .btn-ver:hover {
            background: #2980b9;
        }
        .btn-cancelar {
            display: inline-block;
            background: #e74c3c;
            color: white;
            padding: 5px 15px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 13px;
            border: none;
            cursor: pointer;
        }
        .btn-cancelar:hover {
            background: #c0392b;
        }
        .btn-cancelar:disabled {
            background: #95a5a6;
            cursor: not-allowed;
        }
        .total-registros {
            text-align: right;
            color: #7f8c8d;
            font-size: 14px;
            margin-top: 10px;
        }
        .acciones {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 20px;
        }
        .mensaje-exito {
            background: #d5f5e3;
            padding: 12px 18px;
            border-radius: 6px;
            color: #1e8449;
            border-left: 4px solid #27ae60;
            margin-bottom: 15px;
        }
        .mensaje-error {
            background: #fde8e8;
            padding: 12px 18px;
            border-radius: 6px;
            color: #c0392b;
            border-left: 4px solid #e74c3c;
            margin-bottom: 15px;
        }
        .fecha-pedido {
            font-size: 12px;
            color: #7f8c8d;
        }
    </style>
</head>
<body>
    <div class="container-pedidos">
        <h1>📋 Mis Pedidos</h1>
        
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap;">
            <div>
                <a href="index.php" class="btn-volver">🏠 Volver a la tienda</a>
            </div>
        </div>

        <?php if ($total_pedidos > 0): ?>
            <table class="tabla-pedidos">
                <thead>
                    <tr>
                        <th>ID Compra</th>
                        <th>Producto</th>
                        <th>Cantidad</th>
                        <th>Total</th>
                        <th>Fecha</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($pedido = $resultado->fetch_assoc()): 
                        // Obtener nombre del producto
                        $stmt_prod = $conn->prepare("SELECT nombre FROM PRODUCTO WHERE id_producto = ?");
                        $stmt_prod->bind_param("i", $pedido['id_producto']);
                        $stmt_prod->execute();
                        $result_prod = $stmt_prod->get_result();
                        $producto = $result_prod->fetch_assoc();
                        $nombre_producto = $producto ? $producto['nombre'] : 'Producto desconocido';
                    ?>
                        <tr>
                            <td><strong>#<?php echo $pedido['id_compra']; ?></strong></td>
                            <td><?php echo htmlspecialchars($nombre_producto); ?></td>
                            <td><?php echo $pedido['cantidad']; ?></td>
                            <td class="precio">$<?php echo number_format($pedido['total'], 0, ',', '.'); ?></td>
                            <td class="fecha-pedido"><?php echo date('d/m/Y', strtotime($pedido['fecha'])); ?></td>
                            <td>
                                <span class="badge-estado <?php echo strtolower($pedido['estado'] ?? 'pendiente'); ?>">
                                    <?php echo ucfirst($pedido['estado'] ?? 'Pendiente'); ?>
                                </span>
                            </td>
                            <td>
                                <a href="detalle_pedido.php?id=<?php echo $pedido['id_compra']; ?>" class="btn-ver">🔍 Ver</a>
                                <?php if (($pedido['estado'] ?? 'pendiente') === 'pendiente'): ?>
                                    <form action="cancelar_pedido.php" method="POST" style="display: inline;">
                                        <input type="hidden" name="id_compra" value="<?php echo $pedido['id_compra']; ?>">
                                        <button type="submit" class="btn-cancelar" onclick="return confirm('¿Estás seguro de cancelar este pedido?')">✖ Cancelar</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <div class="total-registros">
                📦 Total de pedidos: <strong><?php echo $total_pedidos; ?></strong>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 40px; color: #7f8c8d;">
                <h3>📭 No tienes pedidos registrados</h3>
                <p>¡Empieza a comprar y tus pedidos aparecerán aquí!</p>
                <a href="index.php" class="btn-volver primary" style="margin-top: 15px;">🛒 Ir a la tienda</a>
            </div>
        <?php endif; ?>
        
        <div class="acciones">
            <a href="mostrar_productos.php" class="btn-volver primary">📦 Ver Productos</a>
        </div>
    </div>
</body>
</html>
