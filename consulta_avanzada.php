<?php
// ============================================================
// CONSULTA AVANZADA: Clientes con más de 2 compras
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
// 📊 CONSULTA AVANZADA CON JOIN, GROUP BY Y HAVING
// ============================================================
$sql = "
    SELECT 
        c.id_cliente,
        c.nombre,
        c.email,
        c.direccion,
        COUNT(co.id_compra) AS total_compras,
        SUM(co.total) AS total_gastado
    FROM CLIENTE c
    INNER JOIN COMPRA co ON c.id_cliente = co.id_cliente
    WHERE c.rol != 'admin'  -- 👈 EXCLUIR AL ADMINISTRADOR
    GROUP BY c.id_cliente, c.nombre, c.email, c.direccion
    HAVING COUNT(co.id_compra) > 2
    ORDER BY total_compras DESC, total_gastado DESC
";

$resultado = $conn->query($sql);
$total_clientes = $resultado->num_rows;

// ============================================================
// 📊 CONSULTA ADICIONAL: Total de compras general
// ============================================================
$sql_total = "SELECT COUNT(*) AS total_compras, SUM(total) AS total_general FROM COMPRA";
$result_total = $conn->query($sql_total);
$totales = $result_total->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📊 Consulta Avanzada - Clientes con +2 Compras</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .container-consulta {
            max-width: 1100px;
            margin: 40px auto;
            background: white;
            padding: 35px;
            border-radius: 12px;
            box-shadow: 0 0 30px rgba(0,0,0,0.1);
        }
        .container-consulta h1 {
            text-align: center;
            border-bottom: 3px solid #8e44ad;
            padding-bottom: 15px;
            margin-bottom: 25px;
            color: #2c3e50;
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
        .btn-volver.purple {
            background: #8e44ad;
        }
        .btn-volver.purple:hover {
            background: #732d91;
        }
        .tabla-consulta {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            font-size: 14px;
        }
        .tabla-consulta th {
            background: #8e44ad;
            color: white;
            padding: 12px 15px;
            text-align: left;
        }
        .tabla-consulta td {
            padding: 10px 15px;
            border-bottom: 1px solid #ecf0f1;
        }
        .tabla-consulta tr:hover {
            background: #f4ecf7;
        }
        .tabla-consulta .sin-datos {
            text-align: center;
            padding: 30px;
            color: #7f8c8d;
        }
        .badge-compras {
            display: inline-block;
            padding: 3px 14px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 700;
        }
        .badge-compras.alto {
            background: #d5f5e3;
            color: #1e8449;
        }
        .badge-compras.medio {
            background: #fef9e7;
            color: #7d6608;
        }
        .badge-compras.bajo {
            background: #fde8e8;
            color: #c0392b;
        }
        .precio-total {
            font-weight: 700;
            color: #27ae60;
        }
        .total-registros {
            text-align: right;
            color: #7f8c8d;
            font-size: 14px;
            margin-top: 10px;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin: 20px 0;
        }
        .stat-card {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            border-left: 4px solid #8e44ad;
        }
        .stat-card .numero {
            font-size: 28px;
            font-weight: 700;
            color: #2c3e50;
        }
        .stat-card .label {
            font-size: 13px;
            color: #7f8c8d;
        }
        .stat-card.purple {
            border-left-color: #8e44ad;
        }
        .stat-card.green {
            border-left-color: #27ae60;
        }
        .stat-card.blue {
            border-left-color: #3498db;
        }
        .stat-card.orange {
            border-left-color: #e67e22;
        }
        .query-box {
            background: #2c3e50;
            color: #ecf0f1;
            padding: 15px 20px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            overflow-x: auto;
            margin: 20px 0;
        }
        .query-box .sql-keyword {
            color: #f39c12;
        }
        .query-box .sql-table {
            color: #3498db;
        }
        .query-box .sql-function {
            color: #2ecc71;
        }
        .acciones {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 20px;
        }
        @media (max-width: 600px) {
            .stats {
                grid-template-columns: 1fr 1fr;
            }
        }
        @media (max-width: 400px) {
            .stats {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container-consulta">
        <h1>📊 Consulta Avanzada</h1>
        <p style="text-align: center; color: #7f8c8d; margin-bottom: 10px;">
            Clientes con más de 2 compras registradas
        </p>
        <p style="text-align: center; font-size: 13px; color: #8e44ad; margin-bottom: 20px;">
            🔗 Relación entre las tablas CLIENTE y COMPRA
        </p>

        <!-- ============================================================ -->
        <!-- 📊 ESTADÍSTICAS                                              -->
        <!-- ============================================================ -->
        <div class="stats">
            <div class="stat-card purple">
                <div class="numero"><?php echo $total_clientes; ?></div>
                <div class="label">👤 Clientes con más de 2 compras</div>
            </div>
            <div class="stat-card green">
                <div class="numero"><?php echo number_format($totales['total_compras'] ?? 0); ?></div>
                <div class="label">🛒 Total de compras</div>
            </div>
            <div class="stat-card blue">
                <div class="numero">$<?php echo number_format($totales['total_general'] ?? 0, 0, ',', '.'); ?></div>
                <div class="label">💰 Total gastado</div>
            </div>
            <div class="stat-card orange">
                <div class="numero">
                    <?php 
                        // Promedio de compras por cliente
                        $stmt = $conn->query("SELECT COUNT(DISTINCT id_cliente) as total_clientes FROM COMPRA");
                        $row = $stmt->fetch_assoc();
                        $total_clientes_bd = $row['total_clientes'] ?? 1;
                        $promedio = round(($totales['total_compras'] ?? 0) / $total_clientes_bd, 1);
                        echo $promedio;
                    ?>
                </div>
                <div class="label">📊 Promedio de compras/cliente</div>
            </div>
        </div>

        <!-- ============================================================ -->
        <!-- 📝 CONSULTA SQL                                              -->
        <!-- ============================================================ -->
        <div style="margin: 20px 0;">
            <h3 style="color: #2c3e50; margin-bottom: 10px;">📝 Consulta SQL utilizada</h3>
            <div class="query-box">
                <span class="sql-keyword">SELECT</span><br>
                &nbsp;&nbsp;&nbsp;c.id_cliente,<br>
                &nbsp;&nbsp;&nbsp;c.nombre,<br>
                &nbsp;&nbsp;&nbsp;c.email,<br>
                &nbsp;&nbsp;&nbsp;c.direccion,<br>
                &nbsp;&nbsp;&nbsp;<span class="sql-function">COUNT</span>(co.id_compra) <span class="sql-keyword">AS</span> total_compras,<br>
                &nbsp;&nbsp;&nbsp;<span class="sql-function">SUM</span>(co.total) <span class="sql-keyword">AS</span> total_gastado<br>
                <span class="sql-keyword">FROM</span> <span class="sql-table">CLIENTE</span> c<br>
                <span class="sql-keyword">INNER JOIN</span> <span class="sql-table">COMPRA</span> co <span class="sql-keyword">ON</span> c.id_cliente = co.id_cliente<br>
                <span class="sql-keyword">GROUP BY</span> c.id_cliente, c.nombre, c.email, c.direccion<br>
                <span class="sql-keyword">HAVING</span> <span class="sql-function">COUNT</span>(co.id_compra) <span class="sql-keyword">></span> 2<br>
                <span class="sql-keyword">ORDER BY</span> total_compras <span class="sql-keyword">DESC</span>, total_gastado <span class="sql-keyword">DESC</span>
            </div>
        </div>

        <!-- ============================================================ -->
        <!-- 📊 TABLA DE RESULTADOS                                       -->
        <!-- ============================================================ -->
        <?php if ($total_clientes > 0): ?>
            <h3 style="color: #2c3e50; margin-bottom: 10px;">👤 Clientes con más de 2 compras</h3>
            <table class="tabla-consulta">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Cliente</th>
                        <th>Email</th>
                        <th>Dirección</th>
                        <th>🛒 Compras</th>
                        <th>💰 Total gastado</th>
                        <th>📊 Nivel</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $contador = 1; ?>
                    <?php while ($cliente = $resultado->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?php echo $contador++; ?></strong></td>
                            <td><strong><?php echo htmlspecialchars($cliente['nombre']); ?></strong></td>
                            <td><?php echo htmlspecialchars($cliente['email']); ?></td>
                            <td><?php echo htmlspecialchars($cliente['direccion']); ?></td>
                            <td>
                                <strong><?php echo $cliente['total_compras']; ?></strong>
                                <?php if ($cliente['total_compras'] >= 5): ?>
                                    <span style="font-size: 12px; color: #27ae60;">⭐</span>
                                <?php endif; ?>
                            </td>
                            <td class="precio-total">$<?php echo number_format($cliente['total_gastado'], 0, ',', '.'); ?></td>
                            <td>
                                <?php if ($cliente['total_compras'] >= 5): ?>
                                    <span class="badge-compras alto">🏆 VIP</span>
                                <?php elseif ($cliente['total_compras'] >= 3): ?>
                                    <span class="badge-compras medio">⭐ Frecuente</span>
                                <?php else: ?>
                                    <span class="badge-compras bajo">📌 Regular</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <div class="total-registros">
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 40px; color: #7f8c8d;">
                <h3>📭 No hay clientes con más de 2 compras</h3>
                <p>Genera más compras desde el script de compras para ver resultados aquí.</p>
                <?php if (isset($_SESSION['usuario']['rol']) && $_SESSION['usuario']['rol'] === 'admin'): ?>
                    <a href="insertar_compras.php" class="btn-volver purple" style="margin-top: 15px;">📊 Generar Compras</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- ============================================================ -->
        <!-- 🔗 ACCIONES                                                 -->
        <!-- ============================================================ -->
        <div class="acciones">
            <a href="index.php" class="btn-volver">🏠 Volver al inicio</a>
            <a href="mostrar_productos.php" class="btn-volver primary">📦 Ver Productos</a>
            <a href="mostrar_clientes.php" class="btn-volver">👤 Ver Clientes</a>
            <?php if (isset($_SESSION['usuario']['rol']) && $_SESSION['usuario']['rol'] === 'admin'): ?>
                <a href="insertar_compras.php" class="btn-volver purple">📊 Generar Compras</a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>