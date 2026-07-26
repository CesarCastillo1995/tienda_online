<?php
// ============================================================
// MOSTRAR CLIENTES - CON MYSQL
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
// CONSULTA PARA OBTENER TODOS LOS CLIENTES
// ============================================================
$sql = "SELECT * FROM CLIENTE ORDER BY id_cliente DESC";
$resultado = $conn->query($sql);

// Contar el total de clientes
$total_clientes = $resultado->num_rows;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>👤 Clientes Registrados - TecnoStore</title>
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
        .tabla-clientes {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            font-size: 14px;
        }
        .tabla-clientes th {
            background: #2c3e50;
            color: white;
            padding: 12px 15px;
            text-align: left;
        }
        .tabla-clientes td {
            padding: 10px 15px;
            border-bottom: 1px solid #ecf0f1;
        }
        .tabla-clientes tr:hover {
            background: #f8f9fa;
        }
        .tabla-clientes .sin-datos {
            text-align: center;
            padding: 30px;
            color: #7f8c8d;
        }
        .badge-rol {
            display: inline-block;
            padding: 3px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-rol.admin {
            background: #e67e22;
            color: white;
        }
        .badge-rol.cliente {
            background: #3498db;
            color: white;
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
        <h1>👤 Clientes Registrados</h1>
        
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap;">
            <div>
                <a href="index.php" class="btn-volver">🏠 Volver al inicio</a>
            </div>
        </div>

        <?php if ($total_clientes > 0): ?>
            <table class="tabla-clientes">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>Dirección</th>
                        <th>Rol</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($cliente = $resultado->fetch_assoc()): ?>
                        <tr>
                            <td><strong>#<?php echo $cliente['id_cliente']; ?></strong></td>
                            <td><?php echo htmlspecialchars($cliente['nombre']); ?></td>
                            <td><?php echo htmlspecialchars($cliente['email']); ?></td>
                            <td><?php echo htmlspecialchars($cliente['direccion']); ?></td>
                            <td>
                                <?php if (isset($cliente['rol']) && $cliente['rol'] === 'admin'): ?>
                                    <span class="badge-rol admin">🔑 Admin</span>
                                <?php else: ?>
                                    <span class="badge-rol cliente">👤 Cliente</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <div class="total-registros">
                👤 Total de clientes: <strong><?php echo $total_clientes; ?></strong>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 40px; color: #7f8c8d;">
                <h3>📭 No hay clientes registrados</h3>
                <p>Los clientes se registran desde la página de registro.</p>
            </div>
        <?php endif; ?>
        
        <div class="acciones">
            <a href="mostrar_productos.php" class="btn-volver primary">📦 Ver Productos</a>
        </div>
    </div>
</body>
</html>