<?php
// ============================================================
// LIMPIAR COOKIE DEL CARRITO (SOLUCIONAR ERROR "BAD REQUEST")
// ============================================================

// Eliminar la cookie del carrito
setcookie('carrito_persistente_' . session_id(), '', time() - 42000, '/');

// También intentar eliminar cualquier cookie que empiece con "carrito_persistente"
foreach ($_COOKIE as $nombre => $valor) {
    if (strpos($nombre, 'carrito_persistente_') === 0) {
        setcookie($nombre, '', time() - 42000, '/');
    }
}

echo "✅ Cookies del carrito eliminadas correctamente.";
echo "<br><br>🔗 <a href='http://localhost/phpmyadmin/'>Ir a phpMyAdmin</a>";
?>