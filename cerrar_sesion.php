<?php
// ============================================================
// CERRAR SESIÓN - CON GUARDADO DE CARRITO Y LÍMITE DE COOKIE
// ============================================================

// Incluir funciones y conexión
require_once 'funciones.php';
require_once 'config/conexion.php';

// Iniciar sesión
session_start();

// ============================================================
// GUARDAR CARRITO EN BASE DE DATOS ANTES DE CERRAR
// ============================================================
if (estaLogueado()) {
    $clave = obtenerClaveCarrito();
    if (isset($_SESSION[$clave]) && !empty($_SESSION[$clave])) {
        $carrito_json = json_encode($_SESSION[$clave]);
        $id_usuario = $_SESSION['usuario']['id'];
        
        // 1. Guardar carrito en la tabla CLIENTE (siempre)
        $stmt = $conn->prepare("UPDATE CLIENTE SET carrito = ? WHERE id_cliente = ?");
        $stmt->bind_param("si", $carrito_json, $id_usuario);
        $stmt->execute();
        $stmt->close();
        
        // 2. Guardar en cookie SOLO si el tamaño es menor a 3KB (para evitar error "Bad Request")
        // El límite de cabeceras de Apache es aproximadamente 8KB, pero usamos 3KB para estar seguros
        if (strlen($carrito_json) < 3000) {
            setcookie('carrito_persistente_' . session_id(), $carrito_json, time() + (86400 * 30), '/');
        } else {
            // Si el carrito es demasiado grande, eliminar la cookie existente
            setcookie('carrito_persistente_' . session_id(), '', time() - 42000, '/');
        }
    } else {
        // Si el carrito está vacío, eliminar la cookie
        setcookie('carrito_persistente_' . session_id(), '', time() - 42000, '/');
    }
}

// ============================================================
// DESTRUIR SESIÓN COMPLETAMENTE
// ============================================================

// 1. Vaciar el array de sesión
$_SESSION = array();

// 2. Destruir la sesión
session_destroy();

// 3. Eliminar la cookie de sesión
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 4. Eliminar la cookie del carrito persistente (por si acaso)
setcookie('carrito_persistente_' . session_id(), '', time() - 42000, '/');

// ============================================================
// REDIRIGIR AL INDEX CON MENSAJE
// ============================================================
header('Location: index.php?mensaje=sesion_cerrada');
exit;
?>