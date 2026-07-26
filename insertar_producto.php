<?php
// ============================================================
// INSERTAR PRODUCTOS - SOLO ADMINISTRADORES
// ============================================================

// Incluir conexión a la base de datos
require_once 'config/conexion.php';

// Iniciar sesión para verificar si el usuario está logueado
session_start();

// ============================================================
// 🔒 VERIFICAR QUE EL USUARIO SEA ADMINISTRADOR
// ============================================================
if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}

// Verificar rol de administrador
if (!isset($_SESSION['usuario']['rol']) || $_SESSION['usuario']['rol'] !== 'admin') {
    header('Location: index.php');
    exit;
}

$mensaje = '';
$tipo_mensaje = '';

// Procesar el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'insertar') {
    
    $nombre = $_POST['nombre'] ?? '';
    $descripcion = $_POST['descripcion'] ?? '';
    $precio = $_POST['precio'] ?? '';
    $stock = $_POST['stock'] ?? '';
    
    // Validaciones en PHP
    if (empty($nombre) || strlen($nombre) < 3) {
        $mensaje = 'El nombre del producto debe tener al menos 3 caracteres.';
        $tipo_mensaje = 'error';
    } elseif (empty($precio) || !is_numeric($precio) || $precio <= 0) {
        $mensaje = 'El precio debe ser un número mayor a 0.';
        $tipo_mensaje = 'error';
    } elseif (empty($stock) || !is_numeric($stock) || $stock < 0 || !ctype_digit((string)$stock)) {
        $mensaje = 'El stock debe ser un número entero mayor o igual a 0.';
        $tipo_mensaje = 'error';
    } else {
        // Insertar en la base de datos
        $stmt = $conn->prepare("INSERT INTO PRODUCTO (nombre, descripcion, precio, stock) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssdi", $nombre, $descripcion, $precio, $stock);
        
        if ($stmt->execute()) {
            $mensaje = '✅ ¡Producto agregado exitosamente!';
            $tipo_mensaje = 'exito';
            // Limpiar campos después de insertar
            $nombre = $descripcion = $precio = $stock = '';
        } else {
            $mensaje = '❌ Error al agregar producto: ' . $conn->error;
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
    <title>📦 Agregar Producto - TecnoStore</title>
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
        .form-container textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 15px;
            transition: border 0.3s;
        }
        .form-container input:focus,
        .form-container textarea:focus {
            border-color: #3498db;
            outline: none;
        }
        .form-container textarea {
            resize: vertical;
            min-height: 80px;
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
    </style>
    <script>
        function validarProducto() {
            let nombre = document.getElementById('nombre').value.trim();
            let descripcion = document.getElementById('descripcion').value.trim();
            let precio = document.getElementById('precio').value.trim();
            let stock = document.getElementById('stock').value.trim();

            // Validar nombre
            if (nombre === "" || nombre.length < 3) {
                alert("⚠️ El nombre del producto debe tener al menos 3 caracteres.");
                document.getElementById('nombre').focus();
                return false;
            }

            // Validar precio
            if (precio === "") {
                alert("⚠️ El precio del producto es obligatorio.");
                document.getElementById('precio').focus();
                return false;
            }
            if (isNaN(precio) || parseFloat(precio) <= 0) {
                alert("⚠️ El precio debe ser un número mayor a 0.");
                document.getElementById('precio').focus();
                return false;
            }

            // Validar stock
            if (stock === "") {
                alert("⚠️ El stock del producto es obligatorio.");
                document.getElementById('stock').focus();
                return false;
            }
            if (isNaN(stock) || !Number.isInteger(Number(stock)) || parseInt(stock) < 0) {
                alert("⚠️ El stock debe ser un número entero mayor o igual a 0.");
                document.getElementById('stock').focus();
                return false;
            }

            return true;
        }
    </script>
</head>
<body>
    <div class="form-container">
        <h1>📦 Agregar Nuevo Producto</h1>
        <p style="text-align: center; color: #7f8c8d; margin-bottom: 5px;">
            Completa los datos del producto para agregarlo al catálogo
        </p>
        <p style="text-align: center; margin-bottom: 20px;">
            <span class="badge-admin">🔒 SOLO ADMINISTRADORES</span>
        </p>

        <?php if (!empty($mensaje)): ?>
            <div class="<?php echo $tipo_mensaje === 'exito' ? 'mensaje-exito' : 'mensaje-error'; ?>">
                <?php echo $mensaje; ?>
            </div>
        <?php endif; ?>

        <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" onsubmit="return validarProducto()">
            <input type="hidden" name="accion" value="insertar">

            <div class="form-group">
                <label for="nombre">📦 Nombre del producto <span class="required">*</span></label>
                <input type="text" id="nombre" name="nombre" placeholder="Ej: Smartphone Galaxy S23" value="<?php echo $nombre ?? ''; ?>" required>
            </div>

            <div class="form-group">
                <label for="descripcion">📝 Descripción</label>
                <textarea id="descripcion" name="descripcion" placeholder="Descripción detallada del producto"><?php echo $descripcion ?? ''; ?></textarea>
            </div>

            <div class="form-group">
                <label for="precio">💰 Precio (CLP) <span class="required">*</span></label>
                <input type="number" id="precio" name="precio" step="0.01" placeholder="Ej: 699990" value="<?php echo $precio ?? ''; ?>" required>
            </div>

            <div class="form-group">
                <label for="stock">📦 Stock disponible <span class="required">*</span></label>
                <input type="number" id="stock" name="stock" placeholder="Ej: 15" value="<?php echo $stock ?? ''; ?>" required>
            </div>

            <button type="submit" class="btn-submit">💾 Guardar Producto</button>
        </form>

        <div class="volver-container">
            <a href="mostrar_productos.php" class="btn-volver">📋 Ver productos registrados</a>
            <a href="index.php" class="btn-volver" style="background: #3498db;">🏠 Volver al inicio</a>
        </div>
    </div>
</body>
</html>