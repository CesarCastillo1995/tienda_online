<?php
// ============================================================
// FUNCIONES PRINCIPALES - TecnoStore
// ============================================================

// Configurar zona horaria
date_default_timezone_set('America/Santiago');

// ============================================================
// RUTA DEL ARCHIVO DE USUARIOS
// ============================================================
define('ARCHIVO_USUARIOS', __DIR__ . '/usuarios.json');

// ============================================================
// FUNCIONES DE LIMPIEZA
// ============================================================

/**
 * Función para sanitizar/limpiar datos de entrada
 */
function limpiarDatos($datos) {
    $datos = trim($datos);
    $datos = stripslashes($datos);
    $datos = htmlspecialchars($datos);
    return $datos;
}

// ============================================================
// FUNCIONES DE RESEÑAS
// ============================================================

/**
 * Permite a los usuarios calificar productos y dejar comentarios
 */
function registrarReseña($producto, $calificacion, $reseña) {
    $producto = limpiarDatos($producto);
    $reseña = limpiarDatos($reseña);
    
    $calificacion = (int)$calificacion;
    if ($calificacion < 1 || $calificacion > 5) {
        $calificacion = 0;
    }
    
    $reseñaProcesada = [
        'producto'      => $producto,
        'calificacion'  => $calificacion,
        'reseña'        => $reseña,
        'fecha'         => date('d/m/Y H:i:s'),
        'estrellas'     => str_repeat('⭐', $calificacion) . str_repeat('☆', 5 - $calificacion)
    ];
    
    return $reseñaProcesada;
}

// ============================================================
// FUNCIONES DE USUARIOS
// ============================================================

/**
 * Obtener todos los usuarios desde el archivo JSON
 */
function obtenerUsuarios() {
    if (!file_exists(ARCHIVO_USUARIOS)) {
        return [];
    }
    $contenido = file_get_contents(ARCHIVO_USUARIOS);
    if (empty($contenido)) {
        return [];
    }
    $usuarios = json_decode($contenido, true);
    return is_array($usuarios) ? $usuarios : [];
}

/**
 * Guardar usuarios en el archivo JSON
 */
function guardarUsuarios($usuarios) {
    $json = json_encode($usuarios, JSON_PRETTY_PRINT);
    file_put_contents(ARCHIVO_USUARIOS, $json);
}

/**
 * Obtener un usuario por su ID
 */
function obtenerUsuarioPorId($id) {
    $usuarios = obtenerUsuarios();
    foreach ($usuarios as $usuario) {
        if ($usuario['id'] === $id) {
            return $usuario;
        }
    }
    return null;
}

/**
 * Obtener un usuario por su email
 */
function obtenerUsuarioPorEmail($email) {
    $usuarios = obtenerUsuarios();
    foreach ($usuarios as $usuario) {
        if ($usuario['email'] === $email) {
            return $usuario;
        }
    }
    return null;
}

/**
 * Actualizar el carrito de un usuario en el archivo
 */
function actualizarCarritoUsuario($usuarioId, $carrito) {
    $usuarios = obtenerUsuarios();
    foreach ($usuarios as &$usuario) {
        if ($usuario['id'] === $usuarioId) {
            $usuario['carrito'] = $carrito;
            break;
        }
    }
    guardarUsuarios($usuarios);
}

/**
 * Obtener el carrito de un usuario desde el archivo
 */
function obtenerCarritoUsuarioPersistente($usuarioId) {
    $usuario = obtenerUsuarioPorId($usuarioId);
    if ($usuario && isset($usuario['carrito'])) {
        return $usuario['carrito'];
    }
    return [];
}

// ============================================================
// FUNCIONES DE PEDIDOS
// ============================================================

/**
 * Agregar un pedido al historial de un usuario
 */
function agregarPedidoUsuario($usuarioId, $pedido) {
    $usuarios = obtenerUsuarios();
    foreach ($usuarios as &$usuario) {
        if ($usuario['id'] === $usuarioId) {
            if (!isset($usuario['pedidos'])) {
                $usuario['pedidos'] = [];
            }
            $usuario['pedidos'][] = $pedido;
            break;
        }
    }
    guardarUsuarios($usuarios);
}

/**
 * Obtener el historial de pedidos de un usuario desde el archivo
 */
function obtenerPedidosUsuario($usuarioId) {
    $usuario = obtenerUsuarioPorId($usuarioId);
    if ($usuario && isset($usuario['pedidos'])) {
        return $usuario['pedidos'];
    }
    return [];
}

/**
 * Registrar un nuevo usuario (con carrito y pedidos vacíos)
 */
function registrarUsuario($nombre, $email, $password) {
    $nombre = limpiarDatos($nombre);
    $email = limpiarDatos($email);
    $password = limpiarDatos($password);
    
    // Validaciones
    if (empty($nombre) || strlen($nombre) < 3) {
        return ['success' => false, 'mensaje' => 'El nombre debe tener al menos 3 caracteres.'];
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'mensaje' => 'El correo electrónico no es válido.'];
    }
    
    if (empty($password) || strlen($password) < 6) {
        return ['success' => false, 'mensaje' => 'La contraseña debe tener al menos 6 caracteres.'];
    }
    
    // Verificar si el usuario ya existe
    $usuarios = obtenerUsuarios();
    foreach ($usuarios as $usuario) {
        if ($usuario['email'] === $email) {
            return ['success' => false, 'mensaje' => 'Este correo ya está registrado.'];
        }
    }
    
    // Crear el usuario con carrito y pedidos vacíos
    $nuevoUsuario = [
        'id' => uniqid('usr_'),
        'nombre' => $nombre,
        'email' => $email,
        'password' => password_hash($password, PASSWORD_DEFAULT),
        'fecha_registro' => date('d/m/Y H:i:s'),
        'carrito' => [],
        'pedidos' => []  // Historial de pedidos vacío
    ];
    
    // Guardar en el archivo
    $usuarios[] = $nuevoUsuario;
    guardarUsuarios($usuarios);
    
    return ['success' => true, 'mensaje' => 'Usuario registrado exitosamente.', 'usuario' => $nuevoUsuario];
}

/**
 * Iniciar sesión - Carga carrito y pedidos del usuario
 */
function iniciarSesion($email, $password) {
    $email = limpiarDatos($email);
    $password = limpiarDatos($password);
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'mensaje' => 'Correo electrónico no válido.'];
    }
    
    if (empty($password)) {
        return ['success' => false, 'mensaje' => 'La contraseña es obligatoria.'];
    }
    
    // Buscar el usuario en el archivo
    $usuarios = obtenerUsuarios();
    foreach ($usuarios as $usuario) {
        if ($usuario['email'] === $email) {
            if (password_verify($password, $usuario['password'])) {
                // Iniciar sesión
                $_SESSION['usuario'] = [
                    'id' => $usuario['id'],
                    'nombre' => $usuario['nombre'],
                    'email' => $usuario['email']
                ];
                
                // Regenerar ID de sesión por seguridad
                session_regenerate_id(true);
                
                // Cargar el carrito del usuario desde el archivo
                $carritoGuardado = obtenerCarritoUsuarioPersistente($usuario['id']);
                $clave = obtenerClaveCarrito();
                $_SESSION[$clave] = $carritoGuardado;
                
                // Cargar el historial de pedidos del usuario desde el archivo
                $pedidosGuardados = obtenerPedidosUsuario($usuario['id']);
                $_SESSION['pedidos'] = $pedidosGuardados;
                
                return ['success' => true, 'mensaje' => 'Inicio de sesión exitoso.'];
            } else {
                return ['success' => false, 'mensaje' => 'Contraseña incorrecta.'];
            }
        }
    }
    
    return ['success' => false, 'mensaje' => 'Usuario no encontrado.'];
}

/**
 * Verificar si el usuario está logueado
 */
function estaLogueado() {
    return isset($_SESSION['usuario']) && !empty($_SESSION['usuario']);
}

/**
 * Obtener el nombre del usuario logueado
 */
function obtenerNombreUsuario() {
    if (estaLogueado()) {
        return $_SESSION['usuario']['nombre'];
    }
    return 'Invitado';
}

// ============================================================
// FUNCIONES PARA CARRITO POR USUARIO
// ============================================================

/**
 * Obtener la clave del carrito para el usuario actual
 */
function obtenerClaveCarrito() {
    if (estaLogueado()) {
        return 'carrito_' . $_SESSION['usuario']['id'];
    }
    return 'carrito_invitado_' . session_id();
}

/**
 * Obtener el carrito del usuario actual
 */
function obtenerCarritoUsuario() {
    $clave = obtenerClaveCarrito();
    
    // Si está logueado, primero intentar cargar desde el archivo
    if (estaLogueado()) {
        $carritoArchivo = obtenerCarritoUsuarioPersistente($_SESSION['usuario']['id']);
        if (!empty($carritoArchivo)) {
            $_SESSION[$clave] = $carritoArchivo;
        }
    }
    
    if (!isset($_SESSION[$clave])) {
        $_SESSION[$clave] = [];
    }
    return $_SESSION[$clave];
}

/**
 * Guardar el carrito del usuario actual
 */
function guardarCarritoUsuario($carrito) {
    $clave = obtenerClaveCarrito();
    $_SESSION[$clave] = $carrito;
    
    if (estaLogueado()) {
        actualizarCarritoUsuario($_SESSION['usuario']['id'], $carrito);
        guardarCarritoEnCookie();
    }
}

/**
 * Vaciar el carrito del usuario actual
 */
function vaciarCarritoUsuario() {
    $clave = obtenerClaveCarrito();
    $_SESSION[$clave] = [];
    
    if (estaLogueado()) {
        actualizarCarritoUsuario($_SESSION['usuario']['id'], []);
        setcookie('carrito_persistente_' . session_id(), '', time() - 42000, '/');
    }
}

/**
 * Guardar carrito en cookie para persistencia
 */
function guardarCarritoEnCookie() {
    if (estaLogueado()) {
        $clave = obtenerClaveCarrito();
        $carrito = isset($_SESSION[$clave]) ? $_SESSION[$clave] : [];
        if (!empty($carrito)) {
            setcookie('carrito_persistente_' . session_id(), json_encode($carrito), time() + (86400 * 30), '/');
        }
    }
}

/**
 * Restaurar carrito desde cookie al iniciar sesión
 */
function restaurarCarritoDesdeCookie() {
    if (estaLogueado()) {
        return;
    }
    
    if (isset($_COOKIE['carrito_persistente_' . session_id()])) {
        $carrito = json_decode($_COOKIE['carrito_persistente_' . session_id()], true);
        if (is_array($carrito) && !empty($carrito)) {
            $clave = obtenerClaveCarrito();
            $_SESSION[$clave] = $carrito;
        }
    }
}

/**
 * Función para agregar un usuario de prueba (demo)
 */
function crearUsuarioDemo() {
    $usuarios = obtenerUsuarios();
    foreach ($usuarios as $usuario) {
        if ($usuario['email'] === 'usuario@demo.com') {
            return;
        }
    }
    
    $usuarioDemo = [
        'id' => uniqid('usr_'),
        'nombre' => 'Usuario Demo',
        'email' => 'usuario@demo.com',
        'password' => password_hash('123456', PASSWORD_DEFAULT),
        'fecha_registro' => date('d/m/Y H:i:s'),
        'carrito' => [],
        'pedidos' => []
    ];
    
    $usuarios[] = $usuarioDemo;
    guardarUsuarios($usuarios);
}

/**
 * Guardar el carrito en la base de datos para un usuario específico
 */
function guardarCarritoEnBD($id_usuario, $carrito) {
    global $conn;
    $carrito_json = json_encode($carrito);
    $stmt = $conn->prepare("UPDATE CLIENTE SET carrito = ? WHERE id_cliente = ?");
    $stmt->bind_param("si", $carrito_json, $id_usuario);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}

/**
 * Obtener el carrito desde la base de datos para un usuario específico
 */
function obtenerCarritoDesdeBD($id_usuario) {
    global $conn;
    $stmt = $conn->prepare("SELECT carrito FROM CLIENTE WHERE id_cliente = ?");
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        if (!empty($row['carrito'])) {
            return json_decode($row['carrito'], true);
        }
    }
    return [];
}

?>