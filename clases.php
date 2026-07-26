<?php
// ============================================================
// Clase Pedido
// ============================================================

class Pedido {
    
    // ==========================================
    // PROPIEDADES DE LA CLASE
    // ==========================================
    
    /** @var string Descripción del pedido */
    public $descripcion;
    
    /** @var string Tipo de pedido */
    public $tipoPedido;
    
    /** @var string Nombre del producto */
    public $producto;
    
    /** @var int Cantidad de unidades */
    public $unidades;
    
    /** @var string Observaciones adicionales */
    public $observaciones;
    
    /** @var string Fecha y hora del pedido */
    public $fechaPedido;
    
    /** @var string Estado actual del pedido */
    public $estado;
    
    /** @var string ID único del pedido */
    public $idPedido;
    
    // ==========================================
    // MÉTODO CONSTRUCTOR
    // ==========================================
    
    /**
     * Constructor de la clase Pedido
     * 
     * @param string $descripcion   - Descripción del pedido
     * @param string $tipoPedido    - Tipo de pedido
     * @param string $producto      - Nombre del producto
     * @param int    $unidades      - Cantidad de unidades
     * @param string $observaciones - Observaciones adicionales (opcional)
     */
    public function __construct($descripcion, $tipoPedido, $producto, $unidades, $observaciones = '') {
        $this->descripcion = $descripcion;
        $this->tipoPedido = $tipoPedido;
        $this->producto = $producto;
        $this->unidades = (int)$unidades;
        $this->observaciones = $observaciones;
        $this->fechaPedido = date('d/m/Y H:i:s');
        $this->estado = 'Pendiente';
        // Generar ID único de 8 caracteres
        $this->idPedido = $this->generarId();
    }
    
    // ==========================================
    // MÉTODOS DE LA CLASE
    // ==========================================
    
    /**
     * Genera un ID único para el pedido
     * 
     * @return string - ID de 8 caracteres
     */
    private function generarId() {
        // Generar ID basado en fecha, hora y un número aleatorio
        $base = date('YmdHis') . uniqid();
        return 'PED-' . strtoupper(substr(md5($base), 0, 5));
    }
    
    /**
     * Método para calcular el costo total del pedido
     * 
     * @param float $precioUnitario - Precio por unidad del producto
     * @return float - Costo total
     */
    public function calcularTotal($precioUnitario = 0) {
        return $this->unidades * $precioUnitario;
    }
    
    /**
     * Método para actualizar el estado del pedido
     * 
     * @param string $nuevoEstado - Nuevo estado del pedido
     */
    public function actualizarEstado($nuevoEstado) {
        $estadosValidos = ['Pendiente', 'En proceso', 'Enviado', 'Entregado', 'Cancelado'];
        if (in_array($nuevoEstado, $estadosValidos)) {
            $this->estado = $nuevoEstado;
        }
    }
    
    /**
     * Método para obtener un resumen del pedido
     * 
     * @return string - Resumen del pedido en formato texto
     */
    public function obtenerResumen() {
        return "Pedido {$this->idPedido} | Producto: {$this->producto} | Unidades: {$this->unidades} | Estado: {$this->estado}";
    }
    
    /**
     * Método para verificar si el pedido puede ser procesado
     * 
     * @return bool - True si el pedido es válido
     */
    public function esValido() {
        return !empty($this->producto) && $this->unidades > 0 && !empty($this->tipoPedido);
    }
    
    /**
     * Método para obtener todos los datos del pedido en un array
     * 
     * @return array - Datos del pedido
     */
    public function obtenerDatos() {
        return [
            'id_pedido' => $this->idPedido,
            'descripcion' => $this->descripcion,
            'tipo_pedido' => $this->tipoPedido,
            'producto' => $this->producto,
            'unidades' => $this->unidades,
            'observaciones' => $this->observaciones,
            'fecha' => $this->fechaPedido,
            'estado' => $this->estado
        ];
    }
    
    /**
     * Método para buscar pedidos por tipo de producto
     * 
     * @param string $busqueda - Texto a buscar en el producto
     * @return bool - True si el producto contiene el texto de búsqueda
     */
    public function buscarPorProducto($busqueda) {
        return stripos($this->producto, $busqueda) !== false;
    }
    
    /**
     * Método para buscar pedidos por estado
     * 
     * @param string $estadoBusqueda - Estado a buscar
     * @return bool - True si el estado coincide
     */
    public function buscarPorEstado($estadoBusqueda) {
        return strtolower($this->estado) === strtolower($estadoBusqueda);
    }
}
?>