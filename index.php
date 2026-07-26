<?php
// ============================================================
// PÁGINA PRINCIPAL - TecnoStore
// ============================================================

// Configurar zona horaria de Chile
date_default_timezone_set('America/Santiago');

// Incluir archivos necesarios
require_once 'clases.php';
require_once 'funciones.php';

// ============================================================
// CONEXIÓN A LA BASE DE DATOS
// ============================================================
require_once 'config/conexion.php';

// ============================================================
// CONFIGURACIÓN DE SESIÓN
// ============================================================

// 1. Extender tiempo de vida de la sesión a 2 horas
ini_set('session.gc_maxlifetime', 7200);
ini_set('session.cookie_lifetime', 7200);

// ============================================================
// INICIAR SESIÓN
// ============================================================
session_start();

// ============================================================
// VALIDACIONES DEL CARRITO
// ============================================================

// 2. Validar integridad del carrito
function validarCarrito() {
    $clave = obtenerClaveCarrito();
    if (!isset($_SESSION[$clave]) || !is_array($_SESSION[$clave])) {
        $_SESSION[$clave] = [];
        return;
    }
    
    foreach ($_SESSION[$clave] as $item) {
        if (!isset($item['producto']) || !isset($item['cantidad']) || !isset($item['precio'])) {
            $_SESSION[$clave] = [];
            return;
        }
    }
}
validarCarrito();

// 3. Restaurar carrito desde cookie si no hay sesión
restaurarCarritoDesdeCookie();

// ============================================================
// OBTENER PRODUCTOS DESDE LA BASE DE DATOS
// ============================================================
$productos_disponibles = [];

// Consultar todos los productos de la base de datos
$result = $conn->query("SELECT id_producto, nombre, descripcion, precio, stock FROM PRODUCTO ORDER BY id_producto");

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Asignar categoría según el nombre del producto (para mantener el diseño)
        $categoria = 'Otros';
        $icono = '📦';
        
        // Clasificación por palabras clave en el nombre
        $nombre_lower = strtolower($row['nombre']);
        if (strpos($nombre_lower, 'smartphone') !== false || strpos($nombre_lower, 'galaxy') !== false || strpos($nombre_lower, 'iphone') !== false) {
            $categoria = 'Teléfonos';
            $icono = '📱';
        } elseif (strpos($nombre_lower, 'laptop') !== false || strpos($nombre_lower, 'dell') !== false || strpos($nombre_lower, 'monitor') !== false) {
            $categoria = 'Computadores';
            $icono = '💻';
        } elseif (strpos($nombre_lower, 'tablet') !== false || strpos($nombre_lower, 'ipad') !== false) {
            $categoria = 'Tablets';
            $icono = '📟';
        } elseif (strpos($nombre_lower, 'tv') !== false || strpos($nombre_lower, 'televisor') !== false) {
            $categoria = 'Televisores';
            $icono = '📺';
        } elseif (strpos($nombre_lower, 'reloj') !== false || strpos($nombre_lower, 'watch') !== false) {
            $categoria = 'Relojes';
            $icono = '⌚';
        } elseif (strpos($nombre_lower, 'audifono') !== false || strpos($nombre_lower, 'parlante') !== false || strpos($nombre_lower, 'sony') !== false || strpos($nombre_lower, 'jbl') !== false) {
            $categoria = 'Audio';
            $icono = '🎧';
        } elseif (strpos($nombre_lower, 'camara') !== false || strpos($nombre_lower, 'sony alpha') !== false) {
            $categoria = 'Cámaras';
            $icono = '📷';
        }
        
        $productos_disponibles[] = [
            'id' => $row['id_producto'],
            'nombre' => $row['nombre'],
            'descripcion' => $row['descripcion'],
            'precio' => $row['precio'],
            'stock' => $row['stock'],
            'categoria' => $categoria,
            'icono' => $icono
        ];
    }
} else {
    // Si no hay productos en la base de datos, mostrar un mensaje
    $productos_disponibles = [
        [
            'nombre' => 'No hay productos disponibles',
            'precio' => 0,
            'categoria' => 'Sin categoría',
            'descripcion' => 'Agrega productos desde el panel de administración',
            'stock' => 0,
            'icono' => '📭'
        ]
    ];
}

// ============================================================
// FUNCIONES PARA OBTENER DATOS DE PRODUCTOS
// ============================================================
function obtenerPrecioProducto($nombreProducto) {
    global $conn;
    $stmt = $conn->prepare("SELECT precio FROM PRODUCTO WHERE nombre = ?");
    $stmt->bind_param("s", $nombreProducto);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        return $row['precio'];
    }
    return 0;
}

function obtenerProductoPorNombre($nombreProducto) {
    global $conn;
    $stmt = $conn->prepare("SELECT id_producto, nombre, descripcion, precio, stock FROM PRODUCTO WHERE nombre = ?");
    $stmt->bind_param("s", $nombreProducto);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        return $row;
    }
    return null;
}

// ============================================================
// LIMPIAR RESEÑAS, PEDIDOS Y CARRITO
// ============================================================
if (isset($_GET['limpiar']) && $_GET['limpiar'] === 'reseñas') {
    $_SESSION['reseñas'] = [];
    header('Location: index.php');
    exit;
}
if (isset($_GET['limpiar_pedidos']) && $_GET['limpiar_pedidos'] === 'si') {
    $_SESSION['pedidos'] = [];
    header('Location: index.php');
    exit;
}
if (isset($_GET['limpiar_carrito']) && $_GET['limpiar_carrito'] === 'si') {
    vaciarCarritoUsuario();
    header('Location: index.php?mensaje=carrito_vaciado');
    exit;
}

// ============================================================
// INICIALIZAR ARRAYS DE SESIÓN
// ============================================================
if (!isset($_SESSION['reseñas'])) $_SESSION['reseñas'] = [];
if (!isset($_SESSION['pedidos'])) $_SESSION['pedidos'] = [];

// ============================================================
// PROCESAR BÚSQUEDA CON GET (ID + PRODUCTO)
// ============================================================
$terminoBusqueda = '';
$pedidosFiltrados = [];

if (isset($_GET['buscar']) && !empty($_GET['buscar'])) {
    $terminoBusqueda = limpiarDatos($_GET['buscar']);
    
    $pedidosFiltrados = array_filter($_SESSION['pedidos'], function($pedido) use ($terminoBusqueda) {
        $buscarId = stripos($pedido['id_pedido'], $terminoBusqueda) !== false;
        $buscarProducto = stripos($pedido['producto'], $terminoBusqueda) !== false;
        return $buscarId || $buscarProducto;
    });
} else {
    $pedidosFiltrados = $_SESSION['pedidos'];
}

// ============================================================
// AGREGAR AL CARRITO CON VALIDACIÓN DE STOCK Y LOGIN
// ============================================================
if (isset($_GET['agregar_carrito']) && !empty($_GET['agregar_carrito'])) {
    
    if (!estaLogueado()) {
        header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
    
    $productoNombre = urldecode($_GET['agregar_carrito']);
    
    // Buscar el producto en la base de datos
    $productoInfo = obtenerProductoPorNombre($productoNombre);
    
    if ($productoInfo !== null) {
        $carrito = obtenerCarritoUsuario();
        
        $cantidadEnCarrito = 0;
        foreach ($carrito as $item) {
            if ($item['producto'] === $productoNombre) {
                $cantidadEnCarrito = $item['cantidad'];
                break;
            }
        }
        
        if ($cantidadEnCarrito >= $productoInfo['stock']) {
            header('Location: index.php?mensaje=sin_stock&producto=' . urlencode($productoNombre));
            exit;
        }
        
        $existe = false;
        foreach ($carrito as &$item) {
            if ($item['producto'] === $productoNombre) {
                $item['cantidad']++;
                $existe = true;
                break;
            }
        }
        
        if (!$existe) {
            $carrito[] = [
                'producto' => $productoNombre,
                'cantidad' => 1,
                'precio' => $productoInfo['precio']
            ];
        }
        
        guardarCarritoUsuario($carrito);
        
        header('Location: index.php?mensaje=agregado');
        exit;
    } else {
        header('Location: index.php?mensaje=error_producto');
        exit;
    }
}

// ============================================================
// ELIMINAR DEL CARRITO
// ============================================================
if (isset($_GET['eliminar_carrito']) && is_numeric($_GET['eliminar_carrito'])) {
    $indice = (int)$_GET['eliminar_carrito'];
    $carrito = obtenerCarritoUsuario();
    
    if (isset($carrito[$indice])) {
        unset($carrito[$indice]);
        $carrito = array_values($carrito);
        
        if (empty($carrito)) {
            $carrito = [];
        }
        
        guardarCarritoUsuario($carrito);
    }
    header('Location: index.php');
    exit;
}

// ============================================================
// PROCESAR PAGO
// ============================================================
$errores_pago = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'pagar') {
    
    if (!estaLogueado()) {
        header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
    
    $carrito = obtenerCarritoUsuario();
    
    if (empty($carrito)) {
        $errores_pago[] = "El carrito está vacío. Agrega productos antes de pagar.";
    }
    
// Límite máximo de productos en el carrito (por ej: 20)
if (count($carrito) > 20) {
    $errores_pago[] = "El carrito tiene demasiados productos (máximo 20).";
}

    $direccion = isset($_POST['direccion_pago']) ? limpiarDatos($_POST['direccion_pago']) : '';
    if (empty($direccion) || strlen($direccion) < 5) {
        $errores_pago[] = "La dirección de envío es obligatoria (mínimo 5 caracteres).";
    }
    
    $tarjeta = isset($_POST['tarjeta']) ? preg_replace('/\s/', '', $_POST['tarjeta']) : '';
    if (empty($tarjeta) || !preg_match('/^\d{16}$/', $tarjeta)) {
        $errores_pago[] = "El número de tarjeta debe tener 16 dígitos.";
    }
    
    $titular = isset($_POST['titular']) ? limpiarDatos($_POST['titular']) : '';
    if (empty($titular) || strlen($titular) < 3) {
        $errores_pago[] = "El nombre del titular es obligatorio (mínimo 3 caracteres).";
    }
    
    $fecha = isset($_POST['fecha']) ? limpiarDatos($_POST['fecha']) : '';
    if (empty($fecha) || !preg_match('/^\d{2}\/\d{2}$/', $fecha)) {
        $errores_pago[] = "La fecha de expiración debe tener formato MM/AA.";
    } else {
        $parts = explode('/', $fecha);
        $mes = (int)$parts[0];
        $anio = (int)$parts[1];
        if ($mes < 1 || $mes > 12) {
            $errores_pago[] = "El mes debe estar entre 01 y 12.";
        }
        $anio_actual = (int)date('y');
        $mes_actual = (int)date('m');
        if ($anio < $anio_actual || ($anio == $anio_actual && $mes < $mes_actual)) {
            $errores_pago[] = "La tarjeta está vencida.";
        }
    }
    
    $cvv = isset($_POST['cvv']) ? $_POST['cvv'] : '';
    if (empty($cvv) || !preg_match('/^\d{3}$/', $cvv)) {
        $errores_pago[] = "El CVV debe tener 3 dígitos.";
    }
    
    // ============================================================
    // 🔹 VERIFICAR STOCK DE CADA PRODUCTO EN EL CARRITO
    // ============================================================
    $errores_stock = [];
    $productos_compra = [];
    $total_general = 0;
    
    foreach ($carrito as $item) {
        $productoNombre = $item['producto'];
        $cantidad = $item['cantidad'];
        
        // Buscar el producto en la base de datos
        $stmt = $conn->prepare("SELECT id_producto, nombre, precio, stock FROM PRODUCTO WHERE nombre = ?");
        $stmt->bind_param("s", $productoNombre);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            if ($cantidad > $row['stock']) {
                $errores_stock[] = "No hay suficiente stock de '" . $row['nombre'] . "'. Disponible: " . $row['stock'];
            } else {
                $productos_compra[] = [
                    'id_producto' => $row['id_producto'],
                    'nombre' => $row['nombre'],
                    'precio' => $row['precio'],
                    'cantidad' => $cantidad,
                    'subtotal' => $row['precio'] * $cantidad
                ];
                $total_general += $row['precio'] * $cantidad;
            }
        } else {
            $errores_stock[] = "El producto '" . $productoNombre . "' no existe en la base de datos.";
        }
        $stmt->close();
    }
    
    if (!empty($errores_stock)) {
        $errores_pago = array_merge($errores_pago, $errores_stock);
    }
    
    if (empty($errores_pago)) {
        
        // ============================================================
        // 🔹 GUARDAR COMPRA EN LA BASE DE DATOS
        // ============================================================
        $id_cliente = $_SESSION['usuario']['id'];
        $fecha_compra = date('Y-m-d');
        $compras_exitosas = 0;
        
        foreach ($productos_compra as $producto) {
            // Insertar en COMPRA
            $stmt = $conn->prepare("INSERT INTO COMPRA (cantidad, total, fecha, id_producto, id_cliente) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("idsii", $producto['cantidad'], $producto['subtotal'], $fecha_compra, $producto['id_producto'], $id_cliente);
            
            if ($stmt->execute()) {
                $compras_exitosas++;
                
                // ============================================================
                // 🔹 ACTUALIZAR STOCK (restar la cantidad comprada)
                // ============================================================
                $stmt_stock = $conn->prepare("UPDATE PRODUCTO SET stock = stock - ? WHERE id_producto = ?");
                $stmt_stock->bind_param("ii", $producto['cantidad'], $producto['id_producto']);
                $stmt_stock->execute();
                $stmt_stock->close();
            }
            $stmt->close();
        }
        
        // ============================================================
        // 🔹 GUARDAR PEDIDO EN SESIÓN
        // ============================================================
        session_regenerate_id(true);
        
        $total = $total_general;
        $listaProductos = [];
        $totalUnidades = 0;
        
        foreach ($carrito as $item) {
            $subtotal = $item['precio'] * $item['cantidad'];
            $totalUnidades += $item['cantidad'];
            $listaProductos[] = $item['producto'] . ' (x' . $item['cantidad'] . ') - $' . number_format($subtotal, 2);
        }
        
        $descripcion = "Compra desde carrito: " . implode(', ', $listaProductos);
        $tipoPedido = "Entrega a domicilio";
        $observaciones = "Pago realizado con tarjeta. Dirección: " . $direccion;
        
        $pedido = new Pedido($descripcion, $tipoPedido, 'Múltiples productos', $totalUnidades, $observaciones);
        $datosPedido = $pedido->obtenerDatos();
        $datosPedido['numero'] = '#' . substr(md5($pedido->fechaPedido . $pedido->producto), 0, 8);
        $datosPedido['direccion'] = $direccion;
        $datosPedido['productos'] = $listaProductos;
        $datosPedido['total'] = $total;
        $datosPedido['titular'] = $titular;
        $datosPedido['usuario'] = $_SESSION['usuario']['nombre'];
        $datosPedido['compras_registradas'] = $compras_exitosas;
        
        $_SESSION['pedidos'][] = $datosPedido;
        $_SESSION['ultimo_pedido'] = $datosPedido;
        
        // Guardar pedido en el archivo del usuario
        if (estaLogueado()) {
            agregarPedidoUsuario($_SESSION['usuario']['id'], $datosPedido);
        }
        
        // ============================================================
        // 🔹 VACIAR CARRITO COMPLETAMENTE (SESIÓN + BD + COOKIE)
        // ============================================================
        // 1. Vaciar carrito de la sesión
        vaciarCarritoUsuario();
        
        // 2. Vaciar carrito de la base de datos
        $stmt_vaciar = $conn->prepare("UPDATE CLIENTE SET carrito = NULL WHERE id_cliente = ?");
        $stmt_vaciar->bind_param("i", $id_cliente);
        $stmt_vaciar->execute();
        $stmt_vaciar->close();
        
        // 3. Eliminar cookie del carrito
        setcookie('carrito_persistente_' . session_id(), '', time() - 42000, '/');
        
        header('Location: confirmacion_pago.php');
        exit;
    }
}

// ============================================================
// PROCESAR RESEÑAS
// ============================================================
$reseñaProcesada = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'reseña') {
    $producto = $_POST['producto_reseña'] ?? '';
    $calificacion = $_POST['calificacion'] ?? 0;
    $comentario = $_POST['comentario'] ?? '';
    
    $reseñaProcesada = registrarReseña($producto, $calificacion, $comentario);
    
    if ($reseñaProcesada['calificacion'] > 0) {
        $_SESSION['reseñas'][] = $reseñaProcesada;
    }
}

// Mensaje de sesión cerrada o carrito vaciado
$mensaje_sesion = '';
if (isset($_GET['mensaje']) && $_GET['mensaje'] === 'sesion_cerrada') {
    $mensaje_sesion = 'Has cerrado sesión correctamente.';
}
if (isset($_GET['mensaje']) && $_GET['mensaje'] === 'carrito_vaciado') {
    $mensaje_sesion = '🛒 El carrito ha sido vaciado correctamente.';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>⚡ TecnoStore - Tienda de Electrónica</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <h1>⚡ TecnoStore</h1>
        <p style="text-align: center; color: #7f8c8d; margin-bottom: 20px;">
            La mejor tecnología al mejor precio
        </p>

        <!-- ========================================== -->
        <!-- BARRA DE USUARIO                          -->
        <!-- ========================================== -->
        <div class="user-bar">
            <div class="user-info">
                <?php if (estaLogueado()): ?>
                    <span>👤 Hola, <strong><?php echo $_SESSION['usuario']['nombre']; ?></strong></span>
                    <span style="color: #7f8c8d; margin: 0 10px;">|</span>
                    
                    <!-- ============================================================ -->
                    <!-- 🔹 BOTONES SOLO PARA ADMINISTRADORES                        -->
                    <!-- ============================================================ -->
                    <?php if (isset($_SESSION['usuario']['rol']) && $_SESSION['usuario']['rol'] === 'admin'): ?>
                        <a href="insertar_producto.php" class="btn-admin" style="background: #e67e22; color: white; padding: 6px 16px; border-radius: 6px; text-decoration: none; font-weight: 600; margin-right: 10px;">
                            📦 Agregar Producto
                        </a>
                        <a href="insertar_cliente.php" class="btn-admin" style="background: #27ae60; color: white; padding: 6px 16px; border-radius: 6px; text-decoration: none; font-weight: 600; margin-right: 10px;">
                            👤 Agregar Cliente
                        </a>
                        <a href="insertar_compras.php" class="btn-admin" style="background: #8e44ad; color: white; padding: 6px 16px; border-radius: 6px; text-decoration: none; font-weight: 600; margin-right: 10px;">
                            📊 Generar Compras
                        </a>
                        <a href="consulta_avanzada.php" class="btn-admin" style="background: #2ecc71; color: white; padding: 6px 16px; border-radius: 6px; text-decoration: none; font-weight: 600; margin-right: 10px;">
                            📈 Ver Análisis
                        </a>
                    <?php endif; ?>
                    
                    <a href="cerrar_sesion.php" class="btn-cerrar-sesion">🚪 Cerrar sesión</a>
                <?php else: ?>
                    <a href="login.php" class="btn-login">🔐 Iniciar sesión</a>
                    <span style="color: #7f8c8d; margin: 0 8px;">|</span>
                    <a href="registro.php" class="btn-registro">📝 Registrarse</a>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($mensaje_sesion)): ?>
            <div class="mensaje-exito"><?php echo $mensaje_sesion; ?></div>
        <?php endif; ?>

        <!-- ========================================== -->
        <!-- PROMOCIÓN                                  -->
        <!-- ========================================== -->
        <div class="promo-banner">
            <div>
                <h3>🎉 ¡OFERTA ESPECIAL!</h3>
                <p>10% de descuento en todos los productos. Usa el código <strong>WEB10</strong> al pagar.</p>
            </div>
            <span>⚡ ¡No te lo pierdas!</span>
        </div>

        <!-- ========================================== -->
        <!-- FILTROS Y BÚSQUEDA                        -->
        <!-- ========================================== -->
        <div class="filtros-box">
            <h2>🔍 Buscar producto</h2>
            <div class="filtros-grid">
                <div class="filtro-item">
                    <label for="buscar_producto">Buscar</label>
                    <input type="text" id="buscar_producto" placeholder="Buscar producto..." onkeyup="filtrarProductos()">
                </div>
                <div class="filtro-item">
                    <label for="categoria_filtro">Categoría</label>
                    <select id="categoria_filtro" onchange="filtrarProductos()">
                        <option value="todos">📋 Todas las categorías</option>
                        <option value="Teléfonos">📱 Teléfonos</option>
                        <option value="Computadores">💻 Computadores</option>
                        <option value="Tablets">📟 Tablets</option>
                        <option value="Televisores">📺 Televisores</option>
                        <option value="Relojes">⌚ Relojes</option>
                        <option value="Audio">🎧 Audio</option>
                        <option value="Cámaras">📷 Cámaras</option>
                    </select>
                </div>
                <div class="filtro-item">
                    <label for="precio_maximo">Precio máximo</label>
                    <input type="number" id="precio_maximo" placeholder="Ej: 500000" onkeyup="filtrarProductos()">
                </div>
                <div class="filtro-item" style="display: flex; align-items: flex-end;">
                    <button class="btn-filtro" onclick="filtrarProductos()">Aplicar filtros 🔍</button>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- MENSAJES                                   -->
        <!-- ========================================== -->
        <?php if (isset($_GET['mensaje']) && $_GET['mensaje'] === 'agregado'): ?>
            <div class="mensaje-exito">✅ ¡Producto agregado al carrito correctamente!</div>
        <?php endif; ?>
        
        <?php if (isset($_GET['mensaje']) && $_GET['mensaje'] === 'error_producto'): ?>
            <div class="mensaje-error">❌ Error: Producto no encontrado.</div>
        <?php endif; ?>
        
        <?php if (isset($_GET['mensaje']) && $_GET['mensaje'] === 'sin_stock'): ?>
            <div class="mensaje-error">
                ❌ ¡Sin stock disponible! El producto 
                <strong><?php echo isset($_GET['producto']) ? urldecode($_GET['producto']) : ''; ?></strong> 
                no tiene más unidades disponibles en inventario.
            </div>
        <?php endif; ?>
        
        <?php if (!empty($errores_pago)): ?>
            <div class="mensaje-error">
                <strong>❌ Errores en el pago:</strong>
                <ul>
                    <?php foreach ($errores_pago as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- ========================================== -->
        <!-- CARRITO DE COMPRAS                         -->
        <!-- ========================================== -->
        <div class="carrito-box">
            <div class="carrito-header">
                <h2>🛒 Mi Carrito</h2>
                <?php 
                    $carrito = obtenerCarritoUsuario();
                    if (!empty($carrito)): 
                ?>
                    <a href="?limpiar_carrito=si" class="btn-vaciar" onclick="return confirm('¿Vaciar carrito?')">
                        🗑️ Vaciar carrito
                    </a>
                <?php endif; ?>
            </div>
            
            <?php if (empty($carrito)): ?>
                <p class="carrito-vacio">🛒 El carrito está vacío. Agrega productos desde el catálogo.</p>
            <?php else: ?>
                <?php 
                    $total = 0; 
                    $contador_productos = 0;
                ?>
                <?php foreach ($carrito as $indice => $item): ?>
                    <?php 
                        $precio = (float)$item['precio'];
                        $subtotal = $precio * $item['cantidad'];
                        $total += $subtotal;
                        $contador_productos += $item['cantidad'];
                    ?>
                    <div class="carrito-item">
                        <div class="carrito-item-info">
                            <strong><?php echo $item['producto']; ?></strong>
                            <span class="carrito-item-cantidad">x <?php echo $item['cantidad']; ?></span>
                        </div>
                        <div class="carrito-item-acciones">
                            <span class="carrito-item-subtotal">$<?php echo number_format($subtotal, 2); ?></span>
                            <a href="?eliminar_carrito=<?php echo $indice; ?>" 
                               class="btn-eliminar"
                               onclick="return confirm('¿Eliminar este producto?')">
                                ✕
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <div class="carrito-resumen">
                    <div class="carrito-total">💰 Total: <span>$<?php echo number_format($total, 2); ?></span></div>
                    <span class="carrito-cantidad">📦 <?php echo $contador_productos; ?> producto(s)</span>
                </div>
                
                <div class="form-pago">
                    <h3>💳 Realizar Pago</h3>
                    <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
                        <input type="hidden" name="accion" value="pagar">
                        <div class="grid-pago">
                            <div>
                                <label for="direccion_pago">📍 Dirección de envío <span class="required">*</span></label>
                                <input type="text" id="direccion_pago" name="direccion_pago" 
                                       placeholder="Calle, número, ciudad, región" required>
                            </div>
                            <div>
                                <label for="titular">👤 Nombre del titular <span class="required">*</span></label>
                                <input type="text" id="titular" name="titular" 
                                       placeholder="Nombre en la tarjeta" required>
                            </div>
                            <div>
                                <label for="tarjeta">💳 Número de tarjeta <span class="required">*</span></label>
                                <input type="text" id="tarjeta" name="tarjeta" 
                                       placeholder="1234 5678 9012 3456" maxlength="19"
                                       oninput="this.value = this.value.replace(/[^0-9]/g, '').replace(/(.{4})/g, '$1 ').trim();" required>
                            </div>
                            <div>
                                <label for="fecha">📅 Fecha de expiración <span class="required">*</span></label>
                                <input type="text" id="fecha" name="fecha" 
                                       placeholder="MM/AA" maxlength="5"
                                       oninput="this.value = this.value.replace(/[^0-9]/g, '').replace(/(.{2})/g, '$1/').replace(/\/$/, '').slice(0, 5);" required>
                            </div>
                            <div>
                                <label for="cvv">🔐 CVV <span class="required">*</span></label>
                                <input type="text" id="cvv" name="cvv" 
                                       placeholder="***" maxlength="3"
                                       oninput="this.value = this.value.replace(/[^0-9]/g, '');" required>
                            </div>
                        </div>
                        <button type="submit" class="btn-pagar">
                            💳 Pagar $<?php echo number_format($total, 2); ?>
                        </button>
                    </form>
                    <p class="pago-seguro">🔒 Pago seguro simulado. Al pagar se creará tu pedido.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- ========================================== -->
        <!-- 🔍 BUSCADOR DE PEDIDOS POR ID             -->
        <!-- ========================================== -->
        <div class="search-box">
            <h2>🔍 Buscar tu pedido por ID</h2>
            <p style="color: #7f8c8d; font-size: 14px; margin-bottom: 10px;">
                Ingresa el ID de tu pedido para ver su estado
            </p>
            <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="GET">
                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                    <input type="text" name="buscar" 
                           placeholder="Ej: PED-A3F5" 
                           value="<?php echo $terminoBusqueda; ?>"
                           style="flex: 1; padding: 12px 15px; border: 2px solid #ddd; border-radius: 6px; font-size: 15px; min-width: 200px;">
                    <button type="submit" class="btn-filtro" style="padding: 12px 30px;">
                        🔍 Buscar pedido
                    </button>
                    <?php if (!empty($terminoBusqueda)): ?>
                        <a href="index.php" class="btn-filtro" style="background: #7f8c8d; text-decoration: none; padding: 12px 20px; border-radius: 6px; color: white; display: inline-block;">
                            ✕ Limpiar
                        </a>
                    <?php endif; ?>
                </div>
            </form>
            <p style="font-size: 12px; color: #7f8c8d; margin-top: 8px;">
                💡 Puedes buscar por el <strong>ID del pedido</strong> que recibiste en la confirmación de compra (ej: PED-A3F5)
            </p>
        </div>

        <!-- ========================================== -->
        <!-- CATÁLOGO DE PRODUCTOS                      -->
        <!-- ========================================== -->
        <div class="catalogo-container">
            <h2>📦 Productos Destacados</h2>
            <div class="catalogo-grid" id="catalogoGrid">
                <?php foreach ($productos_disponibles as $producto): ?>
                    <div class="producto-card" 
                         data-nombre="<?php echo strtolower($producto['nombre']); ?>"
                         data-categoria="<?php echo $producto['categoria']; ?>"
                         data-precio="<?php echo $producto['precio']; ?>">
                        <div class="producto-imagen">
                            <span class="producto-icono"><?php echo $producto['icono']; ?></span>
                            <?php if ($producto['stock'] < 10 && $producto['stock'] > 0): ?>
                                <span class="stock-bajo">⚠️ ¡Últimas unidades!</span>
                            <?php elseif ($producto['stock'] == 0): ?>
                                <span class="stock-bajo" style="background: #e74c3c;">❌ Agotado</span>
                            <?php endif; ?>
                        </div>
                        <div class="producto-info">
                            <span class="producto-categoria"><?php echo $producto['icono']; ?> <?php echo $producto['categoria']; ?></span>
                            <h3 class="producto-nombre"><?php echo $producto['icono']; ?> <?php echo $producto['nombre']; ?></h3>
                            <p class="producto-descripcion"><?php echo $producto['descripcion']; ?></p>
                            <div class="producto-precio-stock">
                                <span class="producto-precio">$<?php echo number_format($producto['precio'], 0, ',', '.'); ?></span>
                                <span class="producto-stock">📦 <?php echo $producto['stock']; ?> unidades</span>
                            </div>
                            
                            <?php if ($producto['stock'] > 0): ?>
                                <?php if (estaLogueado()): ?>
                                    <a href="?agregar_carrito=<?php echo urlencode($producto['nombre']); ?>" 
                                       class="btn-agregar-carrito">
                                        ➕ Agregar al carrito
                                    </a>
                                <?php else: ?>
                                    <a href="login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" 
                                       class="btn-agregar-carrito" style="background: #f39c12;">
                                        🔒 Iniciar sesión para comprar
                                    </a>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="btn-agotado">❌ Agotado</span>
                            <?php endif; ?>
                            
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <p class="sin-resultados" id="sinResultados" style="display:none; text-align:center; padding:30px; color:#7f8c8d;">
                No se encontraron productos con los filtros seleccionados.
            </p>
        </div>

        <!-- ========================================== -->
        <!-- HISTORIAL DE PEDIDOS                       -->
        <!-- ========================================== -->
        <?php if (!empty($pedidosFiltrados)): ?>
        <div class="pedidos-container">
            <div class="pedidos-header">
                <h2>📋 Historial de pedidos</h2>
                <a href="?limpiar_pedidos=si" class="btn-limpiar" onclick="return confirm('¿Eliminar historial?')">
                    🗑️ Limpiar historial
                </a>
            </div>
            <?php foreach (array_reverse($pedidosFiltrados) as $pedido): ?>
            <div class="pedido-card">
                <div class="pedido-header">
                    <div>
                        <strong class="pedido-id">🆔 <?php echo isset($pedido['id_pedido']) ? $pedido['id_pedido'] : 'PED-XXXXX'; ?></strong>
                        <span class="pedido-numero"><?php echo $pedido['numero'] ?? ''; ?></span>
                    </div>
                    <?php
                    $estadoClase = 'pendiente';
                    $estadoLower = strtolower($pedido['estado']);
                    if ($estadoLower == 'en proceso') $estadoClase = 'en-proceso';
                    elseif ($estadoLower == 'enviado') $estadoClase = 'enviado';
                    elseif ($estadoLower == 'entregado') $estadoClase = 'entregado';
                    elseif ($estadoLower == 'cancelado') $estadoClase = 'cancelado';
                    ?>
                    <span class="badge-estado <?php echo $estadoClase; ?>">
                        <?php echo $pedido['estado']; ?>
                    </span>
                </div>
                
                <?php if (isset($pedido['productos']) && is_array($pedido['productos'])): ?>
                    <p><strong>🛒 Productos:</strong></p>
                    <ul class="pedido-productos">
                        <?php foreach ($pedido['productos'] as $producto): ?>
                            <li><?php echo $producto; ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <p><strong>💰 Total:</strong> $<?php echo number_format($pedido['total'], 2); ?></p>
                <?php else: ?>
                    <p><strong>📦 Producto:</strong> <?php echo $pedido['producto']; ?> (<?php echo $pedido['unidades']; ?> unidades)</p>
                <?php endif; ?>
                
                <p><strong>📍 Dirección:</strong> <?php echo isset($pedido['direccion']) ? $pedido['direccion'] : 'No especificada'; ?></p>
                <p><strong>🚚 Tipo:</strong> <?php echo $pedido['tipo_pedido']; ?></p>
                <p><strong>👤 Usuario:</strong> <?php echo isset($pedido['usuario']) ? $pedido['usuario'] : 'No especificado'; ?></p>
                <p class="pedido-fecha"><strong>📅 Fecha:</strong> <?php echo $pedido['fecha']; ?></p>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- ========================================== -->
        <!-- RESEÑAS                                   -->
        <!-- ========================================== -->
        <div class="reseñas-container">
            <h2>⭐ Calificar Producto</h2>
            <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
                <input type="hidden" name="accion" value="reseña">
                
                <div class="form-group">
                    <label for="producto_reseña">📦 Producto a calificar <span class="required">*</span></label>
                    <select id="producto_reseña" name="producto_reseña" required>
                        <option value="">Selecciona un producto</option>
                        <?php foreach ($productos_disponibles as $producto): ?>
                            <option value="<?php echo $producto['nombre']; ?>">
                                <?php echo $producto['icono']; ?> <?php echo $producto['nombre']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="calificacion">⭐ Calificación <span class="required">*</span></label>
                    <select id="calificacion" name="calificacion" required>
                        <option value="0">Selecciona una calificación</option>
                        <option value="5">⭐⭐⭐⭐⭐ - Excelente</option>
                        <option value="4">⭐⭐⭐⭐ - Muy bueno</option>
                        <option value="3">⭐⭐⭐ - Bueno</option>
                        <option value="2">⭐⭐ - Regular</option>
                        <option value="1">⭐ - Malo</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="comentario">📝 Comentario <span class="required">*</span></label>
                    <textarea id="comentario" name="comentario" 
                              placeholder="Cuéntanos tu experiencia con este producto..." 
                              rows="3" required></textarea>
                </div>
                
                <button type="submit" class="btn-submit success">📤 Enviar Reseña</button>
            </form>
            
            <?php if ($reseñaProcesada && $reseñaProcesada['calificacion'] > 0): ?>
            <div class="result-card">
                <h3>✅ ¡Reseña registrada!</h3>
                <div class="review-card">
                    <div class="stars"><?php echo $reseñaProcesada['estrellas']; ?></div>
                    <p><strong>📦 Producto:</strong> <?php echo $reseñaProcesada['producto']; ?></p>
                    <p><strong>📝 Comentario:</strong> <?php echo $reseñaProcesada['reseña']; ?></p>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($_SESSION['reseñas'])): ?>
        <hr>
        <div class="reseñas-lista">
            <div class="reseñas-header">
                <h2>📝 Reseñas de clientes</h2>
                <a href="?limpiar=reseñas" class="btn-limpiar" onclick="return confirm('¿Eliminar reseñas?')">
                    🗑️ Limpiar reseñas
                </a>
            </div>
            <?php foreach ($_SESSION['reseñas'] as $reseña): ?>
            <div class="review-card">
                <div class="stars"><?php echo $reseña['estrellas']; ?></div>
                <p><strong>📦 Producto:</strong> <?php echo $reseña['producto']; ?></p>
                <p><strong>📝 Comentario:</strong> <?php echo $reseña['reseña']; ?></p>
                <p class="reseña-fecha">📅 <?php echo $reseña['fecha']; ?></p>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

    </div>

    <script>
        function filtrarProductos() {
            const buscar = document.getElementById('buscar_producto').value.toLowerCase();
            const categoria = document.getElementById('categoria_filtro').value;
            const precioMax = parseInt(document.getElementById('precio_maximo').value) || Infinity;
            
            const productos = document.querySelectorAll('.producto-card');
            let visible = 0;
            
            productos.forEach(producto => {
                const nombre = producto.dataset.nombre;
                const cat = producto.dataset.categoria;
                const precio = parseInt(producto.dataset.precio);
                
                const coincideNombre = nombre.includes(buscar);
                const coincideCategoria = categoria === 'todos' || cat === categoria;
                const coincidePrecio = precio <= precioMax;
                
                if (coincideNombre && coincideCategoria && coincidePrecio) {
                    producto.style.display = 'block';
                    visible++;
                } else {
                    producto.style.display = 'none';
                }
            });
            
            document.getElementById('sinResultados').style.display = visible === 0 ? 'block' : 'none';
        }
    </script>

</body>
</html>