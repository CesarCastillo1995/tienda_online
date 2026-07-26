<?php
// ============================================================
// INSERTAR CLIENTES - SOLO ADMINISTRADORES
// ============================================================

// Incluir conexión a la base de datos
require_once 'config/conexion.php';

// Iniciar sesión
session_start();

// ============================================================
// 🔒 VERIFICAR QUE EL USUARIO SEA ADMINISTRADOR
// ============================================================
if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}

if (!isset($_SESSION['usuario']['rol']) || $_SESSION['usuario']['rol'] !== 'admin') {
    header('Location: index.php');
    exit;
}

$mensaje = '';
$tipo_mensaje = '';

// ============================================================
// PROCESAR EL FORMULARIO
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'insertar_cliente') {
    
    $nombre = $_POST['nombre'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $direccion = $_POST['direccion'] ?? '';
    $rol = $_POST['rol'] ?? 'cliente';
    
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
        // Verificar si el email ya existe
        $stmt = $conn->prepare("SELECT id_cliente FROM CLIENTE WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $mensaje = 'Este correo ya está registrado.';
            $tipo_mensaje = 'error';
        } else {
            // Generar hash de la contraseña
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            // Insertar cliente
            $stmt = $conn->prepare("INSERT INTO CLIENTE (nombre, email, direccion, password, rol) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $nombre, $email, $direccion, $password_hash, $rol);
            
            if ($stmt->execute()) {
                $mensaje = '✅ ¡Cliente agregado exitosamente!';
                $tipo_mensaje = 'exito';
                // Limpiar campos
                $nombre = $email = $password = $confirm_password = $direccion = '';
                $rol = 'cliente';
            } else {
                $mensaje = '❌ Error al agregar cliente: ' . $conn->error;
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
    <title>👤 Agregar Cliente - TecnoStore</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .form-container {
            max-width: 600px;
            margin: 40px auto;
            background: white;
            padding: 35px;
            border-radius: 12px;
            box-shadow: 0 0 30px rgba(0,0,0,0.1);
        }
        .form-container h1 {
            text-align: center;
            border-bottom: 3px solid #3498db;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }
        .form-container .form-group {
            margin-bottom: 18px;
        }
        .form-container label {
            display: block;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        .form-container input, 
        .form-container select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 15px;
            transition: border 0.3s;
        }
        .form-container input:focus,
        .form-container select:focus {
            border-color: #3498db;
            outline: none;
        }
        .btn-submit {
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
        .btn-submit:hover {
            background: #1e8449;
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
        .help-text {
            font-size: 12px;
            color: #7f8c8d;
            font-weight: normal;
        }
        .volver-container {
            text-align: center;
            margin-top: 20px;
        }
        .badge-admin {
            background: #e67e22;
            color: white;
            padding: 2px 12px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: bold;
            display: inline-block;
            margin-bottom: 15px;
        }
        .rol-select {
            display: flex;
            gap: 20px;
            align-items: center;
            margin-top: 5px;
        }
        .rol-select label {
            font-weight: normal;
            margin-bottom: 0;
            display: flex;
            align-items: center;
            gap: 5px;
            cursor: pointer;
        }
        .rol-select input[type="radio"] {
            width: auto;
            padding: 0;
        }
    </style>
    <script>
        function validarCliente() {
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
    <div class="form-container">
        <h1>👤 Agregar Nuevo Cliente</h1>
        <p style="text-align: center; color: #7f8c8d; margin-bottom: 5px;">
            Registra un nuevo cliente en la base de datos
        </p>
        <p style="text-align: center; margin-bottom: 20px;">
            <span class="badge-admin">🔒 SOLO ADMINISTRADORES</span>
        </p>

        <?php if (!empty($mensaje)): ?>
            <div class="<?php echo $tipo_mensaje === 'exito' ? 'mensaje-exito' : 'mensaje-error'; ?>">
                <?php echo $mensaje; ?>
            </div>
        <?php endif; ?>

        <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" onsubmit="return validarCliente()">
            <input type="hidden" name="accion" value="insertar_cliente">

            <div class="form-group">
                <label for="nombre">👤 Nombre completo <span class="required">*</span></label>
                <input type="text" id="nombre" name="nombre" placeholder="Ej: Juan Pérez" value="<?php echo $nombre ?? ''; ?>" required>
            </div>

            <div class="form-group">
                <label for="email">📧 Correo electrónico <span class="required">*</span></label>
                <input type="email" id="email" name="email" placeholder="Ej: juan@email.com" value="<?php echo $email ?? ''; ?>" required>
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
                <input type="text" id="direccion" name="direccion" placeholder="Ej: Av. Libertador 123, Santiago" value="<?php echo $direccion ?? ''; ?>" required>
            </div>

            <div class="form-group">
                <label>👑 Rol del usuario</label>
                <div class="rol-select">
                    <label>
                        <input type="radio" name="rol" value="cliente" <?php echo (!isset($_POST['rol']) || $_POST['rol'] === 'cliente') ? 'checked' : ''; ?>>
                        👤 Cliente
                    </label>
                    <label>
                        <input type="radio" name="rol" value="admin" <?php echo (isset($_POST['rol']) && $_POST['rol'] === 'admin') ? 'checked' : ''; ?>>
                        🔑 Administrador
                    </label>
                </div>
            </div>

            <button type="submit" class="btn-submit">💾 Guardar Cliente</button>
        </form>

        <div class="volver-container">
            <a href="mostrar_clientes.php" class="btn-volver primary">📋 Ver clientes registrados</a>
            <a href="index.php" class="btn-volver">🏠 Volver al inicio</a>
        </div>
    </div>
</body>
</html>