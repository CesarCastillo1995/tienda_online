<?php
// ============================================================
// INSERTAR 10 COMPRAS DE PRUEBA (CON ACTUALIZACIÓN DE STOCK)
// ============================================================

// Incluir conexión a la base de datos
require_once 'config/conexion.php';

// Iniciar sesión
session_start();

// Verificar que el usuario sea administrador
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'admin') {
    header('Location: index.php');
    exit;
}

$mensaje = '';
$tipo_mensaje = '';
$compras_insertadas = 0;
$errores = [];

// ============================================================
// OBTENER LISTA DE PRODUCTOS Y CLIENTES
// ============================================================
$productos = [];
$clientes = [];

$result = $conn->query("SELECT id_producto, nombre, precio, stock FROM PRODUCTO");
while ($row = $result->fetch_assoc()) {
    $productos[] = $row;
}

$result = $conn->query("SELECT id_cliente, nombre FROM CLIENTE");
while ($row = $result->fetch_assoc()) {
    $clientes[] = $row;
}

// ============================================================
// PROCESAR INSERCIÓN DE COMPRAS
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'insertar_compras') {
    
    if (empty($productos)) {
        $mensaje = '❌ No hay productos registrados. Primero agrega productos.';
        $tipo_mensaje = 'error';
    } elseif (empty($clientes)) {
        $mensaje = '❌ No hay clientes registrados. Primero registra clientes.';
        $tipo_mensaje = 'error';
    } else {
        $compras_insertadas = 0;
        $errores = [];
        
        for ($i = 1; $i <= 10; $i++) {
            $producto = $productos[array_rand($productos)];
            $cliente = $clientes[array_rand($clientes)];
            $cantidad = rand(1, 3);
            $total = $producto['precio'] * $cantidad;
            $fecha = date('Y-m-d', strtotime('-' . rand(0, 30) . ' days'));
            
            // ============================================================
            // 🔹 VERIFICAR STOCK Y ACTUALIZAR
            // ============================================================
            if ($cantidad > $producto['stock']) {
                $errores[] = "Stock insuficiente para " . $producto['nombre'] . " (stock: " . $producto['stock'] . ", solicitado: " . $cantidad . ")";
            } else {
                // Insertar compra
                $stmt = $conn->prepare("INSERT INTO COMPRA (cantidad, total, fecha, id_producto, id_cliente) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("idsii", $cantidad, $total, $fecha, $producto['id_producto'], $cliente['id_cliente']);
                
                if ($stmt->execute()) {
                    $compras_insertadas++;
                    
                    // ============================================================
                    // 🔹 ACTUALIZAR STOCK (restar la cantidad comprada)
                    // ============================================================
                    $stmt_stock = $conn->prepare("UPDATE PRODUCTO SET stock = stock - ? WHERE id_producto = ?");
                    $stmt_stock->bind_param("ii", $cantidad, $producto['id_producto']);
                    $stmt_stock->execute();
                    $stmt_stock->close();
                } else {
                    $errores[] = "Error en compra $i: " . $conn->error;
                }
                $stmt->close();
            }
        }
        
        if ($compras_insertadas > 0) {
            $mensaje = "✅ ¡$compras_insertadas compras insertadas correctamente!";
            if (!empty($errores)) {
                $mensaje .= " ⚠️ Algunos productos no tenían stock suficiente.";
            }
            $tipo_mensaje = 'exito';
        } else {
            $mensaje = '❌ No se pudo insertar ninguna compra. Verifica el stock de los productos.';
            $tipo_mensaje = 'error';
        }
    }
}

// Contar compras existentes
$total_compras = 0;
$result = $conn->query("SELECT COUNT(*) as total FROM COMPRA");
if ($row = $result->fetch_assoc()) {
    $total_compras = $row['total'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📊 Insertar Compras - TecnoStore</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .container-compras {
            max-width: 800px;
            margin: 40px auto;
            background: white;
            padding: 35px;
            border-radius: 12px;
            box-shadow: 0 0 30px rgba(0,0,0,0.1);
        }
        .container-compras h1 {
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
            margin-top: 15px;
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
        .btn-submit {
            background: #27ae60;
            color: white;
            border: none;
            padding: 14px 40px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 6px;
            cursor: pointer;
            transition: background 0.3s;
        }
        .btn-submit:hover {
            background: #1e8449;
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
        .info-box {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #3498db;
        }
        .info-box h3 {
            margin-bottom: 10px;
            color: #2c3e50;
        }
        .info-box ul {
            margin-left: 20px;
            color: #34495e;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin: 20px 0;
        }
        .stat-card {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
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
        .acciones {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 20px;
        }
        @media (max-width: 500px) {
            .stats {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container-compras">
        <h1>📊 Insertar Compras de Prueba</h1>
        <p style="text-align: center; color: #7f8c8d; margin-bottom: 20px;">
            Genera 10 compras automáticamente para probar la consulta avanzada
        </p>

        <?php if (!empty($mensaje)): ?>
            <div class="<?php echo $tipo_mensaje === 'exito' ? 'mensaje-exito' : 'mensaje-error'; ?>">
                <?php echo $mensaje; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($errores)): ?>
            <div class="mensaje-error">
                <strong>⚠️ Errores de stock:</strong>
                <ul>
                    <?php foreach ($errores as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Estadísticas -->
        <div class="stats">
            <div class="stat-card">
                <div class="numero"><?php echo count($productos); ?></div>
                <div class="label">📦 Productos disponibles</div>
            </div>
            <div class="stat-card">
                <div class="numero"><?php echo count($clientes); ?></div>
                <div class="label">👤 Clientes registrados</div>
            </div>
            <div class="stat-card">
                <div class="numero"><?php echo $total_compras; ?></div>
                <div class="label">🛒 Compras registradas</div>
            </div>
        </div>

        <!-- Información -->
        <div class="info-box">
            <h3>ℹ️ ¿Qué va a hacer este script?</h3>
            <ul>
                <li>Seleccionará <strong>productos</strong> y <strong>clientes</strong> aleatorios</li>
                <li>Generará <strong>10 compras</strong> con cantidades entre 1 y 3 unidades</li>
                <li>✅ <strong>Actualizará el stock</strong> restando la cantidad comprada</li>
                <li>Las fechas serán aleatorias en los últimos 30 días</li>
            </ul>
        </div>

        <!-- Botón para insertar -->
        <?php if (count($productos) > 0 && count($clientes) > 0): ?>
            <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
                <input type="hidden" name="accion" value="insertar_compras">
                <button type="submit" class="btn-submit">🚀 Generar 10 Compras</button>
            </form>
        <?php else: ?>
            <div class="mensaje-error">
                <strong>❌ Faltan datos:</strong>
                <?php if (count($productos) == 0): ?>
                    <br>• No hay <strong>productos</strong> registrados. Agrega productos primero.
                <?php endif; ?>
                <?php if (count($clientes) == 0): ?>
                    <br>• No hay <strong>clientes</strong> registrados. Registra clientes primero.
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Acciones -->
        <div class="acciones">
            <a href="mostrar_productos.php" class="btn-volver primary">📋 Ver Productos</a>
            <a href="mostrar_clientes.php" class="btn-volver">👤 Ver Clientes</a>
            <a href="consulta_avanzada.php" class="btn-volver" style="background: #8e44ad;">📊 Consulta Avanzada</a>
            <a href="index.php" class="btn-volver">🏠 Volver al inicio</a>
        </div>

        <?php if ($total_compras > 0): ?>
            <div style="margin-top: 20px; padding: 15px; background: #eaf2f8; border-radius: 8px;">
                <p style="margin: 0; color: #2c3e50;">
                    ✅ Ya hay <strong><?php echo $total_compras; ?></strong> compras registradas en la base de datos.
                    Puedes ejecutar el script nuevamente para agregar más compras.
                </p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>