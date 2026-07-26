<?php
// ============================================================
// RECUPERACIÓN DE DATOS Y PASO DE VARIABLES
// ============================================================

// Configurar zona horaria de Chile
date_default_timezone_set('America/Santiago');

// Incluir la clase Pedido y la conexión a la base de datos
require_once 'clases.php';
require_once 'config/conexion.php';

// Iniciar sesión para guardar el historial de pedidos
session_start();

// Crear array para almacenar pedidos en sesión (si no existe)
if (!isset($_SESSION['pedidos'])) {
    $_SESSION['pedidos'] = [];
}

// ============================================================
// DECLARAR VARIABLE AL INICIO
// ============================================================
$pedidoCreado = null;
$errores = [];
$direccion = '';
$titular = '';
$tarjeta = '';
$fecha = '';
$cvv = '';

// ============================================================
// FUNCIONES AUXILIARES
// ============================================================

/**
 * Obtener el ID de un producto por su nombre
 */
function obtenerIdProductoPorNombre($nombre) {
    global $conn;
    $stmt = $conn->prepare("SELECT id_producto, precio, stock FROM PRODUCTO WHERE nombre = ?");
    $stmt->bind_param("s", $nombre);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        return $row;
    }
    return null;
}

/**
 * Obtener el ID del cliente logueado
 */
function obtenerIdClienteLogueado() {
    if (isset($_SESSION['usuario']['id'])) {
        return $_SESSION['usuario']['id'];
    }
    return null;
}

// ============================================================
// 1. RECUPERACIÓN DE DATOS CON $_POST
// ============================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Recuperar datos del formulario
    $descripcion = isset($_POST['descripcion']) ? $_POST['descripcion'] : '';
    $direccion = isset($_POST['direccion']) ? $_POST['direccion'] : '';
    $tipoPedido = isset($_POST['tipo_pedido']) ? $_POST['tipo_pedido'] : '';
    $producto = isset($_POST['producto']) ? $_POST['producto'] : '';
    $unidades = isset($_POST['unidades']) ? $_POST['unidades'] : 0;
    $observaciones = isset($_POST['observaciones']) ? $_POST['observaciones'] : '';
    
    // Recuperar datos de pago
    $titular = isset($_POST['titular_pedido']) ? $_POST['titular_pedido'] : '';
    $tarjeta = isset($_POST['tarjeta_pedido']) ? preg_replace('/\s/', '', $_POST['tarjeta_pedido']) : '';
    $fecha = isset($_POST['fecha_pedido']) ? $_POST['fecha_pedido'] : '';
    $cvv = isset($_POST['cvv_pedido']) ? $_POST['cvv_pedido'] : '';
    
    // ============================================================
    // 2. VALIDACIÓN DE DATOS
    // ============================================================
    
    /**
     * Función para sanitizar/limpiar datos de entrada
     */
    function limpiarDatos($dato) {
        $dato = trim($dato);
        $dato = stripslashes($dato);
        $dato = htmlspecialchars($dato);
        return $dato;
    }
    
    // ============================================================
    // VALIDACIÓN DEL PEDIDO
    // ============================================================
    if (empty($descripcion)) {
        $errores['descripcion'] = "El campo 'Descripción del pedido' es obligatorio.";
    }
    if (empty($direccion)) {
        $errores['direccion'] = "El campo 'Dirección de envío' es obligatorio.";
    } elseif (strlen($direccion) < 5) {
        $errores['direccion'] = "La dirección debe tener al menos 5 caracteres.";
    }
    if (empty($tipoPedido)) {
        $errores['tipo_pedido'] = "El campo 'Tipo de pedido' es obligatorio.";
    }
    if (empty($producto)) {
        $errores['producto'] = "El campo 'Producto' es obligatorio.";
    }
    if (!is_numeric($unidades) || $unidades <= 0) {
        $errores['unidades'] = "El campo 'Unidades' debe ser un número positivo.";
    }
    
    // ============================================================
    // VALIDACIÓN DEL PAGO
    // ============================================================
    if (empty($titular) || strlen($titular) < 3) {
        $errores['titular'] = "El nombre del titular es obligatorio (mínimo 3 caracteres).";
    }
    if (empty($tarjeta) || !preg_match('/^\d{16}$/', $tarjeta)) {
        $errores['tarjeta'] = "El número de tarjeta debe tener 16 dígitos.";
    }
    if (empty($fecha) || !preg_match('/^\d{2}\/\d{2}$/', $fecha)) {
        $errores['fecha'] = "La fecha de expiración debe tener formato MM/AA.";
    } else {
        $parts = explode('/', $fecha);
        $mes = (int)$parts[0];
        $anio = (int)$parts[1];
        if ($mes < 1 || $mes > 12) {
            $errores['fecha'] = "El mes debe estar entre 01 y 12.";
        }
        $anio_actual = (int)date('y');
        $mes_actual = (int)date('m');
        if ($anio < $anio_actual || ($anio == $anio_actual && $mes < $mes_actual)) {
            $errores['fecha'] = "La tarjeta está vencida.";
        }
    }
    if (empty($cvv) || !preg_match('/^\d{3}$/', $cvv)) {
        $errores['cvv'] = "El CVV debe tener 3 dígitos.";
    }
    
    $descripcion = limpiarDatos($descripcion);
    $direccion = limpiarDatos($direccion);
    $tipoPedido = limpiarDatos($tipoPedido);
    $producto = limpiarDatos($producto);
    $observaciones = limpiarDatos($observaciones);
    $unidades = (int)$unidades;
    $titular = limpiarDatos($titular);
    
    // ============================================================
    // 3. VERIFICAR STOCK DEL PRODUCTO
    // ============================================================
    $productoInfo = obtenerIdProductoPorNombre($producto);
    
    if ($productoInfo === null) {
        $errores['producto'] = "El producto seleccionado no existe en la base de datos.";
    } elseif ($unidades > $productoInfo['stock']) {
        $errores['stock'] = "No hay suficiente stock. Disponible: " . $productoInfo['stock'] . " unidades.";
    }
    
    // ============================================================
    // 4. CREAR OBJETO PEDIDO Y GUARDAR EN SESIÓN Y BD
    // ============================================================
    
    if (empty($errores)) {
        
        // ============================================================
        // MEDIDA DE SEGURIDAD: REGENERAR ID DE SESIÓN ANTES DEL PAGO
        // ============================================================
        session_regenerate_id(true);
        
        // Crear el objeto Pedido (genera ID automáticamente)
        $pedidoCreado = new Pedido($descripcion, $tipoPedido, $producto, $unidades, $observaciones);
        
        // Obtener datos del pedido (incluye id_pedido)
        $datosPedido = $pedidoCreado->obtenerDatos();
        
        // Agregar datos adicionales
        $datosPedido['numero'] = '#' . substr(md5($pedidoCreado->fechaPedido . $pedidoCreado->producto), 0, 8);
        $datosPedido['direccion'] = $direccion;
        $datosPedido['titular'] = $titular;
        
        // ============================================================
        // 5. GUARDAR EN LA BASE DE DATOS (TABLA COMPRA)
        // ============================================================
        if (isset($_SESSION['usuario'])) {
            $id_cliente = $_SESSION['usuario']['id'];
            $id_producto = $productoInfo['id_producto'];
            $precio = $productoInfo['precio'];
            $total = $precio * $unidades;
            $fecha_compra = date('Y-m-d');
            
            // ============================================================
            // INSERTAR EN LA TABLA COMPRA
            // ============================================================
            $stmt = $conn->prepare("INSERT INTO COMPRA (cantidad, total, fecha, id_producto, id_cliente) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("idsii", $unidades, $total, $fecha_compra, $id_producto, $id_cliente);
            
            if ($stmt->execute()) {
                // ============================================================
                // ACTUALIZAR STOCK (restar la cantidad comprada)
                // ============================================================
                $stmt_stock = $conn->prepare("UPDATE PRODUCTO SET stock = stock - ? WHERE id_producto = ?");
                $stmt_stock->bind_param("ii", $unidades, $id_producto);
                $stmt_stock->execute();
                $stmt_stock->close();
                
                // Agregar datos de la BD al array del pedido
                $datosPedido['id_compra'] = $conn->insert_id;
                $datosPedido['total_bd'] = $total;
            } else {
                $errores['bd'] = "Error al guardar la compra: " . $conn->error;
            }
            $stmt->close();
        } else {
            $errores['sesion'] = "Debes iniciar sesión para realizar una compra.";
        }
        
        // ============================================================
        // 6. GUARDAR PEDIDO EN SESIÓN
        // ============================================================
        if (empty($errores)) {
            $_SESSION['pedidos'][] = $datosPedido;
            $_SESSION['ultimo_pedido'] = $datosPedido;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Actividad 4 - Confirmación de Pedido</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .container { max-width: 700px; margin: 0 auto; background: white; padding: 35px; border-radius: 12px; box-shadow: 0 0 30px rgba(0,0,0,0.1); }
        h1 { color: #2c3e50; text-align: center; border-bottom: 3px solid #3498db; padding-bottom: 15px; }
        .btn-back { display: inline-block; background: #3498db; color: white; padding: 10px 25px; text-decoration: none; border-radius: 6px; font-weight: 600; }
        .btn-back:hover { background: #2980b9; }
        .btn-back.secondary { background: #7f8c8d; }
        .btn-back.secondary:hover { background: #5d6d7e; }
        .result-card { background: #ecf0f1; padding: 20px; border-radius: 8px; margin-top: 20px; border-left: 4px solid #27ae60; }
        .result-item { padding: 8px 0; border-bottom: 1px solid #ddd; display: flex; justify-content: space-between; }
        .result-item:last-child { border-bottom: none; }
        .result-item strong { color: #2c3e50; }
        .result-item .value { color: #34495e; }
        .error-box { background: #fde8e8; padding: 20px; border-radius: 8px; border-left: 4px solid #e74c3c; }
        .error-box h3 { color: #e74c3c; margin-bottom: 10px; }
        .error-box ul { color: #c0392b; margin-left: 20px; }
        .success-box { background: #e8f8f5; padding: 15px; border-radius: 6px; border-left: 4px solid #1abc9c; margin-top: 15px; }
        hr { margin: 25px 0; border: 1px solid #ecf0f1; }
        .badge-direccion { background: #3498db; color: white; padding: 2px 10px; border-radius: 10px; font-size: 12px; }
        .badge-pago { background: #27ae60; color: white; padding: 2px 10px; border-radius: 10px; font-size: 12px; }
        .badge-id { background: #8e44ad; color: white; padding: 2px 10px; border-radius: 10px; font-size: 12px; }
        .badge-stock { background: #e67e22; color: white; padding: 2px 10px; border-radius: 10px; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>📋 Confirmación de Pedido</h1>
        <p style="text-align: center; color: #7f8c8d; margin-bottom: 20px;">
            <a href="index.php" class="btn-back secondary" style="padding: 6px 15px; font-size: 14px;">← Volver a la tienda</a>
        </p>

        <?php if ($_SERVER['REQUEST_METHOD'] !== 'POST'): ?>
            <div class="error-box">
                <h3>⚠️ Acceso incorrecto</h3>
                <p>No se ha enviado ningún pedido. Por favor, completa el formulario desde la página principal.</p>
                <p style="margin-top: 15px;">
                    <a href="index.php" class="btn-back">Ir al formulario</a>
                </p>
            </div>

        <?php elseif (!empty($errores)): ?>
            <div class="error-box">
                <h3>❌ Errores en el formulario</h3>
                <p>Por favor, corrige los siguientes errores:</p>
                <ul>
                    <?php foreach ($errores as $error): ?>
                        <li>• <?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
                <p style="margin-top: 15px;">
                    <a href="index.php" class="btn-back">Volver al formulario</a>
                </p>
            </div>

        <?php elseif ($pedidoCreado && empty($errores)): ?>
            <div class="result-card">
                <h3>✅ ¡Pedido registrado y pago procesado con éxito!</h3>
                <p style="color: #27ae60; margin-bottom: 15px;">
                    Tu pedido ha sido procesado correctamente y el stock ha sido actualizado.
                </p>
                
                <div style="background: white; padding: 15px; border-radius: 6px;">
                    <div style="background: #eaf2f8; padding: 10px 15px; border-radius: 6px; margin-bottom: 10px;">
                        <strong style="color: #2c3e50;">📦 Datos del pedido</strong>
                    </div>
                    <div class="result-item">
                        <strong>ID del pedido:</strong>
                        <span class="value"><span class="badge-id">🆔</span> <?php echo $pedidoCreado->idPedido; ?></span>
                    </div>
                    <div class="result-item">
                        <strong>Número de pedido:</strong>
                        <span class="value"><?php echo '#' . substr(md5($pedidoCreado->fechaPedido . $pedidoCreado->producto), 0, 8); ?></span>
                    </div>
                    <div class="result-item">
                        <strong>Descripción:</strong>
                        <span class="value"><?php echo $pedidoCreado->descripcion; ?></span>
                    </div>
                    <div class="result-item">
                        <strong>📍 Dirección de envío:</strong>
                        <span class="value"><span class="badge-direccion">📍</span> <?php echo $direccion; ?></span>
                    </div>
                    <div class="result-item">
                        <strong>Tipo de pedido:</strong>
                        <span class="value"><?php echo $pedidoCreado->tipoPedido; ?></span>
                    </div>
                    <div class="result-item">
                        <strong>Producto:</strong>
                        <span class="value"><?php echo $pedidoCreado->producto; ?></span>
                    </div>
                    <div class="result-item">
                        <strong>Unidades:</strong>
                        <span class="value"><?php echo $pedidoCreado->unidades; ?></span>
                    </div>
                    <?php if (!empty($pedidoCreado->observaciones)): ?>
                    <div class="result-item">
                        <strong>Observaciones:</strong>
                        <span class="value"><?php echo $pedidoCreado->observaciones; ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <div style="background: #fef9e7; padding: 10px 15px; border-radius: 6px; margin: 10px 0;">
                        <strong style="color: #2c3e50;">💳 Datos de pago</strong>
                    </div>
                    <div class="result-item">
                        <strong>Titular:</strong>
                        <span class="value"><?php echo $titular; ?></span>
                    </div>
                    <div class="result-item">
                        <strong>Tarjeta:</strong>
                        <span class="value">•••• •••• •••• <?php echo substr($tarjeta, -4); ?></span>
                    </div>
                    <div class="result-item">
                        <strong>Fecha expiración:</strong>
                        <span class="value"><?php echo $fecha; ?></span>
                    </div>
                    
                    <div class="result-item">
                        <strong>Estado actual:</strong>
                        <span class="value" style="background: #f39c12; color: white; padding: 2px 12px; border-radius: 12px; font-size: 13px;">
                            <?php echo $pedidoCreado->estado; ?>
                        </span>
                    </div>
                </div>

                <div class="success-box">
                    <strong>📌 Resumen:</strong> <?php echo $pedidoCreado->obtenerResumen(); ?>
                    <br>
                    <strong>📍 Dirección:</strong> <?php echo $direccion; ?>
                    <br>
                    <strong>💳 Pago:</strong> Aprobado con tarjeta terminada en <?php echo substr($tarjeta, -4); ?>
                    <br>
                    <strong>📦 Stock actualizado:</strong> ✅ El stock del producto ha sido reducido correctamente.
                </div>

                <?php if ($pedidoCreado->esValido()): ?>
                <div style="margin-top: 10px; padding: 10px; background: #d5f5e3; border-radius: 6px; color: #1e8449;">
                    ✅ Pedido válido y pago procesado
                </div>
                <?php endif; ?>
            </div>

        <?php endif; ?>
        
    </div>
</body>
</html>