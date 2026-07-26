<?php
// ============================================================
// REGISTRO DE CLIENTES - CON MYSQL
// ============================================================

// Incluir conexión a la base de datos
require_once 'config/conexion.php';

// Iniciar sesión
session_start();

// Verificar si ya está logueado
if (isset($_SESSION['usuario'])) {
    header('Location: index.php');
    exit;
}

$mensaje = '';
$tipo_mensaje = '';

// Procesar registro
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $direccion = $_POST['direccion'] ?? '';
    
    // Validaciones
    if (empty($nombre) || strlen($nombre) < 3) {
        $mensaje = 'El nombre debe tener al menos 3 caracteres.';
        $tipo_mensaje = 'error';
    } elseif (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensaje = 'El correo electrónico no es válido.';
        $tipo_mensaje = 'error';
    } elseif (empty($password) || strlen($password) < 6) {
        $mensaje = 'La contraseña debe tener al menos 6 caracteres.';
        $tipo_mensaje = 'error';
    } elseif ($password !== $confirm_password) {
        $mensaje = 'Las contraseñas no coinciden.';
        $tipo_mensaje = 'error';
    } elseif (empty($direccion) || strlen($direccion) < 5) {
        $mensaje = 'La dirección debe tener al menos 5 caracteres.';
        $tipo_mensaje = 'error';
    } else {
        // Verificar si el email ya existe en la base de datos
        $stmt = $conn->prepare("SELECT id_cliente FROM CLIENTE WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $mensaje = 'Este correo ya está registrado.';
            $tipo_mensaje = 'error';
        } else {
            // ============================================================
            // 🔑 GENERAR EL HASH DE LA CONTRASEÑA
            // ============================================================
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            // ============================================================
            // 📝 INSERTAR EN LA BASE DE DATOS (INCLUYENDO PASSWORD)
            // ============================================================
            $stmt = $conn->prepare("INSERT INTO CLIENTE (nombre, email, direccion, password) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $nombre, $email, $direccion, $password_hash);
            
            if ($stmt->execute()) {
                $mensaje = '¡Registro exitoso! Redirigiendo al login...';
                $tipo_mensaje = 'exito';
                header('refresh:2;url=login.php');
            } else {
                $mensaje = 'Error al registrar: ' . $conn->error;
                $tipo_mensaje = 'error';
            }
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
    <title>📝 Registro - TecnoStore</title>
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
            background: #3498db;
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
            background: #2980b9;
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
    </style>
    <script>
        function validarRegistro() {
            let nombre = document.getElementById('nombre').value.trim();
            let email = document.getElementById('email').value.trim();
            let password = document.getElementById('password').value;
            let confirm = document.getElementById('confirm_password').value;
            let direccion = document.getElementById('direccion').value.trim();

            if (nombre === "" || nombre.length < 3) {
                alert("⚠️ El nombre debe tener al menos 3 caracteres.");
                document.getElementById('nombre').focus();
                return false;
            }

            if (email === "" || !email.includes('@') || !email.includes('.')) {
                alert("⚠️ Ingresa un correo electrónico válido.");
                document.getElementById('email').focus();
                return false;
            }

            if (password === "" || password.length < 6) {
                alert("⚠️ La contraseña debe tener al menos 6 caracteres.");
                document.getElementById('password').focus();
                return false;
            }

            if (password !== confirm) {
                alert("⚠️ Las contraseñas no coinciden.");
                document.getElementById('confirm_password').focus();
                return false;
            }

            if (direccion === "" || direccion.length < 5) {
                alert("⚠️ La dirección debe tener al menos 5 caracteres.");
                document.getElementById('direccion').focus();
                return false;
            }

            return true;
        }
    </script>
</head>
<body>
    <div class="auth-container">
        <h1>📝 Crear Cuenta</h1>
        <p style="text-align: center; color: #7f8c8d; margin-bottom: 20px;">
            Regístrate para empezar a comprar
        </p>

        <?php if (!empty($mensaje)): ?>
            <div class="<?php echo $tipo_mensaje === 'exito' ? 'mensaje-exito' : 'mensaje-error'; ?>">
                <?php echo $mensaje; ?>
                <?php if ($tipo_mensaje === 'exito'): ?>
                    <br><small>Redirigiendo al inicio de sesión...</small>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" onsubmit="return validarRegistro()">
            <div class="form-group">
                <label for="nombre">👤 Nombre completo <span class="required">*</span></label>
                <input type="text" id="nombre" name="nombre" placeholder="Ej: Juan Pérez" required>
            </div>

            <div class="form-group">
                <label for="email">📧 Correo electrónico <span class="required">*</span></label>
                <input type="email" id="email" name="email" placeholder="Ej: juan@email.com" required>
            </div>

            <div class="form-group">
                <label for="password">🔐 Contraseña <span class="required">*</span></label>
                <input type="password" id="password" name="password" placeholder="Mínimo 6 caracteres" minlength="6" required>
            </div>

            <div class="form-group">
                <label for="confirm_password">🔐 Confirmar contraseña <span class="required">*</span></label>
                <input type="password" id="confirm_password" name="confirm_password" placeholder="Repite la contraseña" minlength="6" required>
            </div>

            <div class="form-group">
                <label for="direccion">📍 Dirección <span class="required">*</span></label>
                <input type="text" id="direccion" name="direccion" placeholder="Ej: Av. Libertador 123, Santiago" required>
            </div>

            <button type="submit" class="btn-auth">📝 Registrarse</button>
        </form>

        <div class="auth-link">
            ¿Ya tienes cuenta? <a href="login.php">Inicia sesión aquí</a>
        </div>
        
        <div class="volver">
            <a href="index.php">← Volver a la tienda</a>
        </div>
    </div>
</body>
</html>