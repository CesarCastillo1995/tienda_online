<?php
// ============================================================
// Formulario de registro de pedido
// ============================================================

// Incluir la clase Pedido (aunque no se usa directamente aquí,
// el formulario está basado en sus propiedades)
require_once 'clases.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Actividad 3 - Registrar Pedido</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background: #f0f2f5; padding: 20px; }
        .container { max-width: 700px; margin: 0 auto; background: white; padding: 35px; border-radius: 12px; box-shadow: 0 0 30px rgba(0,0,0,0.1); }
        h1 { color: #2c3e50; text-align: center; border-bottom: 3px solid #3498db; padding-bottom: 15px; }
        h2 { color: #34495e; margin: 25px 0 20px 0; border-left: 4px solid #3498db; padding-left: 12px; font-size: 18px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; font-weight: 600; color: #2c3e50; margin-bottom: 6px; }
        .required { color: #e74c3c; }
        .help-text { font-size: 12px; color: #7f8c8d; font-weight: normal; }
        input[type="text"], input[type="number"], select, textarea {
            width: 100%; padding: 11px 14px; border: 2px solid #ddd; border-radius: 6px; 
            font-size: 15px; transition: border 0.3s;
        }
        input:focus, select:focus, textarea:focus { border-color: #3498db; outline: none; box-shadow: 0 0 8px rgba(52,152,219,0.2); }
        textarea { resize: vertical; min-height: 80px; }
        .btn-submit {
            background: #3498db; color: white; border: none; padding: 14px 30px; font-size: 16px;
            font-weight: 600; border-radius: 6px; cursor: pointer; width: 100%;
            transition: background 0.3s;
        }
        .btn-submit:hover { background: #2980b9; }
        .info-box { background: #eaf2f8; padding: 15px; border-radius: 6px; margin-bottom: 25px; border-left: 4px solid #3498db; }
        .info-box p { color: #2c3e50; font-size: 14px; }
        hr { margin: 25px 0; border: 1px solid #ecf0f1; }
        .propiedades { 
            background: #f8f9fa; padding: 15px; border-radius: 6px; 
            display: grid; grid-template-columns: 1fr 1fr; gap: 8px; font-size: 14px;
        }
        .propiedades .prop { color: #2c3e50; }
        .propiedades .valor { color: #7f8c8d; font-style: italic; }
        @media (max-width: 600px) { .container { padding: 15px; } .propiedades { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <div class="container">
        <h1>📦 Registrar Pedido</h1>
        
        <!-- ========================================== -->
        <!-- RELACIÓN CON LA CLASE PEDIDO               -->
        <!-- ========================================== -->
        <div class="info-box">
            <h2>🔗 Basado en la clase Pedido</h2>
            <p>Este formulario ha sido diseñado a partir de las propiedades de la clase <strong>Pedido</strong> creada en la actividad anterior:</p>
            <div class="propiedades">
                <span class="prop">descripcion</span>
                <span class="valor">Descripción del pedido</span>
                <span class="prop">tipoPedido</span>
                <span class="valor">Tipo de pedido</span>
                <span class="prop">producto</span>
                <span class="valor">Nombre del producto</span>
                <span class="prop">unidades</span>
                <span class="valor">Cantidad de unidades</span>
                <span class="prop">observaciones</span>
                <span class="valor">Observaciones adicionales</span>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- FORMULARIO DE PEDIDO                       -->
        <!-- ========================================== -->
        <h2>✏️ Datos del pedido</h2>
        <form action="procesar_pedido.php" method="POST">
            
            <!-- Campo 1: descripcion -->
            <div class="form-group">
                <label for="descripcion">
                    Descripción del pedido <span class="required">*</span>
                    <span class="help-text">(Ej: Compra de productos electrónicos)</span>
                </label>
                <input type="text" id="descripcion" name="descripcion" 
                       placeholder="Ingresa una breve descripción del pedido" required>
            </div>
            
            <!-- Campo 2: tipoPedido -->
            <div class="form-group">
                <label for="tipo_pedido">
                    Tipo de pedido <span class="required">*</span>
                    <span class="help-text">(¿Cómo deseas recibir tu pedido?)</span>
                </label>
                <select id="tipo_pedido" name="tipo_pedido" required>
                    <option value="">Selecciona una opción</option>
                    <option value="Entrega a domicilio">🚚 Entrega a domicilio</option>
                    <option value="Recoger en tienda">🏪 Recoger en tienda</option>
                    <option value="Retiro en punto de entrega">📦 Retiro en punto de entrega</option>
                    <option value="Envío internacional">✈️ Envío internacional</option>
                </select>
            </div>
            
            <!-- Campo 3: producto -->
            <div class="form-group">
                <label for="producto">
                    Producto <span class="required">*</span>
                    <span class="help-text">(Selecciona el producto que deseas comprar)</span>
                </label>
                <select id="producto" name="producto" required>
                    <option value="">Selecciona un producto</option>
                    <option value="Smartphone Galaxy S23">📱 Smartphone Galaxy S23</option>
                    <option value="Laptop Dell Inspiron">💻 Laptop Dell Inspiron</option>
                    <option value="Audífonos Sony WH-1000">🎧 Audífonos Sony WH-1000</option>
                    <option value="Smart TV LG 55''">📺 Smart TV LG 55''</option>
                    <option value="Tablet iPad Pro">📱 Tablet iPad Pro</option>
                    <option value="Cámara Canon EOS R">📷 Cámara Canon EOS R</option>
                    <option value="Reloj Smartwatch Apple">⌚ Reloj Smartwatch Apple</option>
                </select>
            </div>
            
            <!-- Campo 4: unidades -->
            <div class="form-group">
                <label for="unidades">
                    Unidades <span class="required">*</span>
                    <span class="help-text">(Cantidad de unidades que deseas comprar)</span>
                </label>
                <input type="number" id="unidades" name="unidades" 
                       placeholder="Ej: 1, 2, 3..." min="1" required>
            </div>
            
            <!-- Campo 5: observaciones -->
            <div class="form-group">
                <label for="observaciones">
                    Observaciones
                    <span class="help-text">(Información adicional sobre tu pedido)</span>
                </label>
                <textarea id="observaciones" name="observaciones" 
                          placeholder="Ej: Enviar a la oficina, horario de entrega, etc." 
                          rows="3"></textarea>
            </div>
            
            <!-- Botón de envío -->
            <button type="submit" class="btn-submit">Enviar Pedido ✅</button>
            
        </form>
        
        <hr>
        
        <!-- ========================================== -->
        <!-- RELACIÓN PROPIEDAD - CAMPO                 -->
        <!-- ========================================== -->
        <div style="background: #f4f6f7; padding: 15px; border-radius: 6px; margin-top: 15px;">
            <h2>🔍 Correspondencia con la clase Pedido</h2>
            <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
                <tr style="background: #3498db; color: white;">
                    <th style="padding: 10px; text-align: left; border-radius: 4px 0 0 0;">Propiedad en la clase</th>
                    <th style="padding: 10px; text-align: left; border-radius: 0 4px 0 0;">Campo en el formulario</th>
                </tr>
                <tr style="background: white; border-bottom: 1px solid #ddd;">
                    <td style="padding: 8px;"><code>$descripcion</code></td>
                    <td style="padding: 8px;">descripcion (input text)</td>
                </tr>
                <tr style="background: #f9f9f9; border-bottom: 1px solid #ddd;">
                    <td style="padding: 8px;"><code>$tipoPedido</code></td>
                    <td style="padding: 8px;">tipo_pedido (select)</td>
                </tr>
                <tr style="background: white; border-bottom: 1px solid #ddd;">
                    <td style="padding: 8px;"><code>$producto</code></td>
                    <td style="padding: 8px;">producto (select)</td>
                </tr>
                <tr style="background: #f9f9f9; border-bottom: 1px solid #ddd;">
                    <td style="padding: 8px;"><code>$unidades</code></td>
                    <td style="padding: 8px;">unidades (input number)</td>
                </tr>
                <tr style="background: white;">
                    <td style="padding: 8px; border-radius: 0 0 0 4px;"><code>$observaciones</code></td>
                    <td style="padding: 8px; border-radius: 0 0 4px 0;">observaciones (textarea)</td>
                </tr>
            </table>
        </div>
        
    </div>
</body>
</html>