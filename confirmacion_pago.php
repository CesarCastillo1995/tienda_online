<?php
// ============================================================
// CONFIRMACIÓN DE PAGO DESDE EL CARRITO
// ============================================================

// Configurar zona horaria de Chile
date_default_timezone_set('America/Santiago');

// Incluir la clase Pedido
require_once 'clases.php';

// Iniciar sesión
session_start();

// ============================================================
// RECUPERAR DATOS DEL ÚLTIMO PEDIDO
// ============================================================
$ultimoPedido = null;
$mensaje = '';

// Verificar si hay un pedido recién creado
if (isset($_SESSION['ultimo_pedido'])) {
    $ultimoPedido = $_SESSION['ultimo_pedido'];
    $mensaje = "✅ ¡Pago procesado exitosamente!";
} else {
    // Si no hay pedido, redirigir al index
    header('Location: index.php');
    exit;
}

// Limpiar el último pedido de la sesión para que no se muestre al recargar
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>✅ Confirmación de Pago</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .container-confirmacion {
            max-width: 700px;
            margin: 0 auto;
            background: white;
            padding: 35px;
            border-radius: 12px;
            box-shadow: 0 0 30px rgba(0,0,0,0.1);
        }
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
        .success-box { background: #e8f8f5; padding: 15px; border-radius: 6px; border-left: 4px solid #1abc9c; margin-top: 15px; }
        .badge-direccion { background: #3498db; color: white; padding: 2px 10px; border-radius: 10px; font-size: 12px; }
        .badge-id { background: #8e44ad; color: white; padding: 2px 10px; border-radius: 10px; font-size: 12px; }
        .id-destacado {
            font-size: 24px;
            font-weight: bold;
            color: #8e44ad;
            background: #f4ecf7;
            padding: 5px 15px;
            border-radius: 8px;
            display: inline-block;
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <div class="container-confirmacion">
        <h1>✅ Confirmación de Pago</h1>
        <p style="text-align: center; color: #7f8c8d; margin-bottom: 20px;">
            <a href="index.php" class="btn-back secondary" style="padding: 6px 15px; font-size: 14px;">← Volver a la tienda</a>
        </p>

        <?php if ($ultimoPedido): ?>
            <div class="result-card">
                <h3 style="color: #27ae60;">✅ ¡Pago procesado exitosamente!</h3>
                <p style="color: #27ae60; margin-bottom: 15px;">
                    Tu pedido ha sido registrado correctamente.
                </p>
                
                <div style="background: white; padding: 15px; border-radius: 6px;">
                    <!-- Datos del pedido -->
                    <div style="background: #eaf2f8; padding: 10px 15px; border-radius: 6px; margin-bottom: 10px;">
                        <strong style="color: #2c3e50;">📦 Datos del pedido</strong>
                    </div>
                    
                    <!-- ID DESTACADO -->
                    <div style="text-align: center; padding: 10px; background: #f4ecf7; border-radius: 8px; margin-bottom: 15px;">
                        <p style="font-size: 14px; color: #7f8c8d; margin-bottom: 5px;">🆔 ID del pedido</p>
                        <span class="id-destacado"><?php echo $ultimoPedido['id_pedido']; ?></span>
                        <br>
                        <span style="font-size: 12px; color: #7f8c8d;">
                            Guarda este ID para buscar tu pedido
                        </span>
                    </div>
                    
                    <div class="result-item">
                        <strong>Número de pedido:</strong>
                        <span class="value"><?php echo $ultimoPedido['numero'] ?? '#' . substr(md5($ultimoPedido['fecha'] . $ultimoPedido['producto']), 0, 8); ?></span>
                    </div>
                    <div class="result-item">
                        <strong>Descripción:</strong>
                        <span class="value"><?php echo $ultimoPedido['descripcion']; ?></span>
                    </div>
                    <div class="result-item">
                        <strong>📍 Dirección de envío:</strong>
                        <span class="value"><span class="badge-direccion">📍</span> <?php echo $ultimoPedido['direccion']; ?></span>
                    </div>

                    <!-- ============================================================ -->
                    <!-- LISTA DE PRODUCTOS COMPRADOS                                -->
                    <!-- ============================================================ -->
                    <div style="background: #eaf2f8; padding: 10px 15px; border-radius: 6px; margin: 10px 0;">
                        <strong style="color: #2c3e50;">🛒 Productos comprados</strong>
                    </div>
                    <?php if (isset($ultimoPedido['productos']) && is_array($ultimoPedido['productos'])): ?>
                        <?php foreach ($ultimoPedido['productos'] as $producto): ?>
                            <div class="result-item">
                                <span class="value">• <?php echo $producto; ?></span>
                            </div>
                        <?php endforeach; ?>
                        <div class="result-item" style="font-weight: bold; color: #27ae60;">
                            <strong>Total:</strong>
                            <span class="value">$<?php echo number_format($ultimoPedido['total'], 2); ?></span>
                        </div>
                    <?php else: ?>
                        <div class="result-item">
                            <strong>Producto:</strong>
                            <span class="value"><?php echo $ultimoPedido['producto']; ?></span>
                        </div>
                        <div class="result-item">
                            <strong>Unidades:</strong>
                            <span class="value"><?php echo $ultimoPedido['unidades']; ?></span>
                        </div>
                    <?php endif; ?>

                    <div class="result-item">
                        <strong>Tipo de pedido:</strong>
                        <span class="value"><?php echo $ultimoPedido['tipo_pedido']; ?></span>
                    </div>
                    <?php if (!empty($ultimoPedido['observaciones'])): ?>
                    <div class="result-item">
                        <strong>Observaciones:</strong>
                        <span class="value"><?php echo $ultimoPedido['observaciones']; ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="result-item">
                        <strong>Fecha del pedido:</strong>
                        <span class="value"><?php echo $ultimoPedido['fecha']; ?></span>
                    </div>
                    <div class="result-item">
                        <strong>Estado actual:</strong>
                        <span class="value" style="background: #f39c12; color: white; padding: 2px 12px; border-radius: 12px; font-size: 13px;">
                            <?php echo $ultimoPedido['estado']; ?>
                        </span>
                    </div>
                </div>

                <div class="success-box">
                    <strong>📌 Resumen:</strong> Pedido <?php echo $ultimoPedido['id_pedido']; ?>
                    <br>
                    <strong>📍 Dirección:</strong> <?php echo $ultimoPedido['direccion']; ?>
                    <br>
                    <strong>🛒 Total productos:</strong> <?php echo isset($ultimoPedido['productos']) ? count($ultimoPedido['productos']) : 1; ?>
                </div>
            </div>

            <!-- Instrucciones de búsqueda -->
            <div style="background: #eaf2f8; padding: 15px; border-radius: 8px; margin-top: 20px; border-left: 4px solid #3498db;">
                <h3 style="font-size: 15px; margin-bottom: 10px;">🔍 ¿Cómo buscar tu pedido?</h3>
                <p style="font-size: 14px; color: #2c3e50;">
                    Puedes buscar tu pedido en la página principal usando el <strong>ID del pedido</strong>:
                </p>
                <ol style="margin-left: 20px; margin-top: 10px; font-size: 14px; color: #34495e;">
                    <li>Ve a la <strong>página principal</strong> (clic en "Volver a la tienda")</li>
                    <li>En el buscador, escribe el ID: <strong><?php echo $ultimoPedido['id_pedido']; ?></strong></li>
                    <li>Haz clic en "Buscar" y verás tu pedido</li>
                </ol>
                <div style="margin-top: 10px; padding: 8px 12px; background: white; border-radius: 4px; border: 1px dashed #3498db;">
                    <span style="font-size: 13px; color: #7f8c8d;">💡 Ejemplo de búsqueda:</span>
                    <br>
                    <span style="font-weight: bold; color: #2c3e50;">"<?php echo $ultimoPedido['id_pedido']; ?>"</span>
                </div>
            </div>
            
            <div style="margin-top: 20px; text-align: center;">
                <a href="index.php" class="btn-back" style="padding: 12px 35px; font-size: 16px;">
                    🏠 Volver a la tienda
                </a>
            </div>
        <?php else: ?>
            <div class="error-box" style="background: #fde8e8; padding: 20px; border-radius: 8px; border-left: 4px solid #e74c3c;">
                <h3 style="color: #e74c3c;">⚠️ No se encontró el pedido</h3>
                <p style="color: #c0392b;">No hay información del pedido. Por favor, realiza una compra desde el carrito.</p>
                <p style="margin-top: 15px;">
                    <a href="index.php" class="btn-back">Volver a la tienda</a>
                </p>
            </div>
        <?php endif; ?>

    </div>
</body>
</html>

<?php
// Limpiar el último pedido de la sesión después de mostrarlo
if (isset($_SESSION['ultimo_pedido'])) {
    unset($_SESSION['ultimo_pedido']);
}
?>