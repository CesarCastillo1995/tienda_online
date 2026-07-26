<?php
// ============================================================
// INICIO DE SESIÓN - CON MYSQL Y RESTAURACIÓN DE CARRITO
// ============================================================

// Incluir conexión a la base de datos y funciones
require_once 'config/conexion.php';
require_once 'funciones.php';

// Iniciar sesión
session_start();

// Verificar si ya está logueado
if (isset($_SESSION['usuario'])) {
    header('Location: index.php');
    exit;
}

$mensaje = '';
$tipo_mensaje = '';

// Procesar login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $mensaje = 'Por favor, completa todos los campos.';
        $tipo_mensaje = 'error';
    } else {
        // Buscar el usuario en la base de datos
        $stmt = $conn->prepare("SELECT id_cliente, nombre, email, password, rol FROM CLIENTE WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $usuario = $result->fetch_assoc();
            
            // Verificar la contraseña
            if (password_verify($password, $usuario['password'])) {
                
                // ============================================================
                // INICIAR SESIÓN
                // ============================================================
                $_SESSION['usuario'] = [
                    'id' => $usuario['id_cliente'],
                    'nombre' => $usuario['nombre'],
                    'email' => $usuario['email'],
                    'rol' => $usuario['rol'] ?? 'cliente'
                ];
                
                // ============================================================
                // CARGAR CARRITO DESDE LA BASE DE DATOS
                // ============================================================
                $clave = obtenerClaveCarrito();
                $carrito_guardado = null;
                
                // 1. Intentar cargar desde la base de datos
                $stmt_carrito = $conn->prepare("SELECT carrito FROM CLIENTE WHERE id_cliente = ?");
                $stmt_carrito->bind_param("i", $usuario['id_cliente']);
                $stmt_carrito->execute();
                $result_carrito = $stmt_carrito->get_result();
                
                if ($row_carrito = $result_carrito->fetch_assoc()) {
                    if (!empty($row_carrito['carrito'])) {
                        $carrito_guardado = json_decode($row_carrito['carrito'], true);
                        // Verificar que sea un array válido
                        if (!is_array($carrito_guardado)) {
                            $carrito_guardado = [];
                        }
                    }
                }
                $stmt_carrito->close();
                
                // 2. Si no hay carrito en BD, intentar desde cookie
                if (empty($carrito_guardado) && isset($_COOKIE['carrito_persistente_' . session_id()])) {
                    $carrito_guardado = json_decode($_COOKIE['carrito_persistente_' . session_id()], true);
                    if (!is_array($carrito_guardado)) {
                        $carrito_guardado = [];
                    }
                }
                
                // 3. Si hay carrito, restaurarlo en la sesión
                if (!empty($carrito_guardado) && is_array($carrito_guardado)) {
                    $_SESSION[$clave] = $carrito_guardado;
                } else {
                    $_SESSION[$clave] = [];
                }
                
                // Regenerar ID de sesión por seguridad
                session_regenerate_id(true);
                
                $mensaje = '¡Inicio de sesión exitoso!';
                $tipo_mensaje = 'exito';
                header('refresh:1.5;url=index.php');
            } else {
                $mensaje = 'Contraseña incorrecta.';
                $tipo_mensaje = 'error';
            }
        } else {
            $mensaje = 'Usuario no encontrado.';
            $tipo_mensaje = 'error';
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔐 Login - TecnoStore</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .auth-container {
            max-width: 450px;
            margin: 40px auto;
            background: white;
            padding: 35px;
            border-radius: 12px;
            box-shadow: 0 0 30px rgba(0,0,0,0.1);
        }
        .auth-container h1 {
            text-align: center;
            border-bottom: 3px solid #3498db;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }
        .auth-container .form-group {
            margin-bottom: 18px;
        }
        .auth-container label {
            display: block;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        .auth-container input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 15px;
            transition: border 0.3s;
        }
        .auth-container input:focus {
            border-color: #3498db;
            outline: none;
        }
        .btn-auth {
            background: #27ae60;
            color: white;
            border: none;
            padding: 14px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 6px;
            cursor: pointer;
            width: 100%;
            transition: background 0.3s;
        }
        .btn-auth:hover {
            background: #1e8449;
        }
        .auth-link {
            text-align: center;
            margin-top: 15px;
            color: #7f8c8d;
        }
        .auth-link a {
            color: #3498db;
            text-decoration: none;
            font-weight: 600;
        }
        .auth-link a:hover {
            text-decoration: underline;
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
        .required {
            color: #e74c3c;
        }
        .volver {
            text-align: center;
            margin-top: 15px;
        }
        .volver a {
            color: #7f8c8d;
            text-decoration: none;
        }
        .volver a:hover {
            text-decoration: underline;
        }
        .demo-info {
            background: #f8f9fa;
            padding: 12px 15px;
            border-radius: 6px;
            margin-bottom: 15px;
            font-size: 13px;
            color: #7f8c8d;
            border: 1px dashed #bdc3c7;
        }
        .badge-admin {
            background: #e67e22;
            color: white;
            padding: 2px 10px;
            border-radius: 10px;
            font-size: 11px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <h1>🔐 Iniciar Sesión</h1>
        <p style="text-align: center; color: #7f8c8d; margin-bottom: 20px;">
            Ingresa para comprar en TecnoStore
        </p>

        <?php if (!empty($mensaje)): ?>
            <div class="<?php echo $tipo_mensaje === 'exito' ? 'mensaje-exito' : 'mensaje-error'; ?>">
                <?php echo $mensaje; ?>
                <?php if ($tipo_mensaje === 'exito'): ?>
                    <br><small>Redirigiendo a la tienda...</small>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="demo-info">
            💡 <strong>Usuarios de prueba:</strong><br>
            📧 admin@tienda.com | 🔑 admin123 <span class="badge-admin">ADMIN</span><br>
            📧 juan.perez@email.com | 🔑 123456<br>
            📧 maria.gonzalez@email.com | 🔑 123456
        </div>

        <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
            <div class="form-group">
                <label for="email">📧 Correo electrónico <span class="required">*</span></label>
                <input type="email" id="email" name="email" placeholder="Ej: juan@email.com" required>
            </div>

            <div class="form-group">
                <label for="password">🔐 Contraseña <span class="required">*</span></label>
                <input type="password" id="password" name="password" placeholder="Tu contraseña" required>
            </div>

            <button type="submit" class="btn-auth">🔐 Iniciar Sesión</button>
        </form>

        <div class="auth-link">
            ¿No tienes cuenta? <a href="registro.php">Regístrate aquí</a>
        </div>
        
        <div class="volver">
            <a href="index.php">← Volver a la tienda</a>
        </div>
    </div>
</body>
</html>