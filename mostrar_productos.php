<?php
// ============================================================
// MOSTRAR PRODUCTOS - CON MYSQL
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

// ============================================================
// CONSULTA PARA OBTENER TODOS LOS PRODUCTOS
// ============================================================
$sql = "SELECT * FROM PRODUCTO ORDER BY id_producto DESC";
$resultado = $conn->query($sql);

// Contar el total de productos
$total_productos = $resultado->num_rows;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📋 Productos Registrados - TecnoStore</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .container-lista {
            max-width: 1000px;
            margin: 40px auto;
            background: white;
            padding: 35px;
            border-radius: 12px;
            box-shadow: 0 0 30px rgba(0,0,0,0.1);
        }
        .container-lista h1 {
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
        .btn-volver.success {
            background: #27ae60;
        }
        .btn-volver.success:hover {
            background: #1e8449;
        }
        .tabla-productos {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            font-size: 14px;
        }
        .tabla-productos th {
            background: #2c3e50;
            color: white;
            padding: 12px 15px;
            text-align: left;
        }
        .tabla-productos td {
            padding: 10px 15px;
            border-bottom: 1px solid #ecf0f1;
        }
        .tabla-productos tr:hover {
            background: #f8f9fa;
        }
        .tabla-productos .sin-datos {
            text-align: center;
            padding: 30px;
            color: #7f8c8d;
        }
        .badge-stock {
            display: inline-block;
            padding: 3px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-stock.bajo {
            background: #fde8e8;
            color: #c0392b;
        }
        .badge-stock.normal {
            background: #d5f5e3;
            color: #1e8449;
        }
        .badge-stock.agotado {
            background: #e74c3c;
            color: white;
        }
        .precio {
            font-weight: 700;
            color: #27ae60;
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
    </style>
</head>
<body>
    <div class="container-lista">
        <h1>📋 Productos Registrados</h1>
        
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap;">
            <div>
                <a href="index.php" class="btn-volver">🏠 Volver al inicio</a>
                <?php if (isset($_SESSION['usuario']['rol']) && $_SESSION['usuario']['rol'] === 'admin'): ?>
                    <a href="insertar_producto.php" class="btn-volver success">📦 Agregar Producto</a>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($total_productos > 0): ?>
            <table class="tabla-productos">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <th>Precio</th>
                        <th>Stock</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($producto = $resultado->fetch_assoc()): ?>
                        <tr>
                            <td><strong>#<?php echo $producto['id_producto']; ?></strong></td>
                            <td><?php echo htmlspecialchars($producto['nombre']); ?></td>
                            <td><?php echo htmlspecialchars($producto['descripcion']); ?></td>
                            <td class="precio">$<?php echo number_format($producto['precio'], 0, ',', '.'); ?></td>
                            <td><?php echo $producto['stock']; ?></td>
                            <td>
                                <?php if ($producto['stock'] <= 0): ?>
                                    <span class="badge-stock agotado">❌ Agotado</span>
                                <?php elseif ($producto['stock'] < 10): ?>
                                    <span class="badge-stock bajo">⚠️ Bajo stock</span>
                                <?php else: ?>
                                    <span class="badge-stock normal">✅ Disponible</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <div class="total-registros">
                📦 Total de productos: <strong><?php echo $total_productos; ?></strong>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 40px; color: #7f8c8d;">
                <h3>📭 No hay productos registrados</h3>
                <p>Agrega tu primer producto desde el formulario.</p>
                <?php if (isset($_SESSION['usuario']['rol']) && $_SESSION['usuario']['rol'] === 'admin'): ?>
                    <a href="insertar_producto.php" class="btn-volver success" style="margin-top: 15px;">📦 Agregar Producto</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <div class="acciones">
            <a href="mostrar_clientes.php" class="btn-volver primary">👤 Ver Clientes</a>
        </div>
    </div>
</body>
</html>