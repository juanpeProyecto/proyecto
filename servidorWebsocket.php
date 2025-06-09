<?php
/*
* Servidor de WebSocket para manejar pedidos en tiempo real
* Este servidor recibirá y enviará actualizaciones a los diferentes clientes conectados
*/
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;

require __DIR__ . '/vendor/autoload.php';

// Clase principal para manejar los websockets
class ServidorPedidos implements MessageComponentInterface
{
    protected $clientes;
    protected $clientesTipo = [
        'cocina' => [],
        'barra' => [],
        'camarero' => [],
        'cliente' => []
    ];

    public function __construct()
    {
        // Almacenamiento de conexiones de clientes
        $this->clientes = new \SplObjectStorage();
        echo "Servidor WebSocket iniciado\n";
    }

    // Cuando un cliente se conecta al servidor
    public function onOpen(ConnectionInterface $conn)
    {
        // Almacenamos la nueva conexión
        $this->clientes->attach($conn);
        $conn->tipoCliente = 'desconocido'; // Por defecto, tipo desconocido
        
        // Usando la identificación segura del cliente
        $clienteId = spl_object_hash($conn);
        echo "Nueva conexión: (cliente: {$clienteId})\n";
    }

    // Cuando un cliente envía un mensaje al servidor
    public function onMessage(ConnectionInterface $from, $mensaje)
    {
        $numRecibidos = count($this->clientes) - 1;
        $datos = json_decode($mensaje, true);
        
        // Usando la identificación segura del cliente
        $clienteId = spl_object_hash($from);
        echo "Mensaje recibido de conexión (cliente: {$clienteId}): $mensaje\n";

        // Si el mensaje incluye un tipo de cliente (cocina, barra, camarero, cliente)
        if (isset($datos['tipoCliente']) && in_array($datos['tipoCliente'], array_keys($this->clientesTipo))) {
            $from->tipoCliente = $datos['tipoCliente'];
            // Usando la identificación segura del cliente
            $clienteId = spl_object_hash($from);
            $this->clientesTipo[$datos['tipoCliente']][$clienteId] = $from;
            echo "Cliente {$clienteId} registrado como: {$datos['tipoCliente']}\n";
            return;
        }
        
        // Si es un nuevo pedido, enviamos a cocina, barra y camarero
        if (isset($datos['tipo']) && $datos['tipo'] === 'nuevoPedido') {
            $this->enviarATodos($mensaje, ['cocina', 'barra', 'camarero']);
        }
        
        // Si es una actualización de estado de pedido completo
        if (isset($datos['tipo']) && $datos['tipo'] === 'actualizacionEstado') {
            // Si la actualización es para un pedido específico, notificamos al cliente correspondiente
            if (isset($datos['numMesa'])) {
                $this->enviarACliente($mensaje, $datos['numMesa']);
            }
            
            // También notificamos a todos los trabajadores
            $this->enviarATodos($mensaje, ['cocina', 'barra', 'camarero']);
        }
        
        // Si es una actualización de estado de un producto individual
        if (isset($datos['tipo']) && $datos['tipo'] === 'actualizacionEstadoProducto') {
            // Registramos la acción
            echo "Actualizando estado de producto: {$datos['codProducto']} de pedido {$datos['codPedido']} a {$datos['estado']}\n";
            
            // Si la actualización es para un producto específico, notificamos al cliente correspondiente
            if (isset($datos['numMesa'])) {
                $this->enviarACliente($mensaje, $datos['numMesa']);
            }
            
            // También notificamos a todos los trabajadores según el área que necesita saber
            $areaDestino = ['cocina', 'camarero'];
            $this->enviarATodos($mensaje, $areaDestino);
            
            // Aquí podrías agregar código para actualizar el estado en la base de datos si lo deseas
            // Por ejemplo: actualizarEstadoProducto($datos['codPedido'], $datos['codProducto'], $datos['estado']);
        }
    }

    // Cuando un cliente se desconecta
    public function onClose(ConnectionInterface $conn)
    {
        // Quitamos la conexión del registro
        $this->clientes->detach($conn);
        
        // Lo quitamos también del registro por tipo
        if (isset($conn->tipoCliente) && $conn->tipoCliente !== 'desconocido') {
            $clienteId = spl_object_hash($conn);
            if (isset($this->clientesTipo[$conn->tipoCliente][$clienteId])) {
                unset($this->clientesTipo[$conn->tipoCliente][$clienteId]);
            }
        }
        
        // Usando la identificación segura del cliente
        $clienteId = spl_object_hash($conn);
        echo "Conexión {$clienteId} cerrada\n";
    }

    // Manejo de errores
    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "Error: {$e->getMessage()}\n";
        $conn->close();
    }
    
    // Método para enviar mensajes a ciertos tipos de clientes
    protected function enviarATodos($mensaje, array $tiposDestino)
    {
        foreach ($tiposDestino as $tipo) {
            if (isset($this->clientesTipo[$tipo]) && !empty($this->clientesTipo[$tipo])) {
                foreach ($this->clientesTipo[$tipo] as $cliente) {
                    if ($cliente instanceof ConnectionInterface) {
                        $cliente->send($mensaje);
                        // Usando la identificación segura del cliente
                        $clienteId = spl_object_hash($cliente);
                        echo "Mensaje enviado a {$tipo} (cliente: {$clienteId})\n";
                    }
                }
            }
        }
    }
    
    // Método para enviar mensajes a un cliente específico por número de mesa
    protected function enviarACliente($mensaje, $numMesa)
    {
        if (isset($this->clientesTipo['cliente']) && !empty($this->clientesTipo['cliente'])) {
            foreach ($this->clientesTipo['cliente'] as $cliente) {
                // Verificamos que el cliente sea una instancia válida y tenga registrada su mesa
                if ($cliente instanceof ConnectionInterface && isset($cliente->numMesa) && $cliente->numMesa == $numMesa) {
                    $cliente->send($mensaje);
                    // Usando la identificación segura del cliente
                    $clienteId = spl_object_hash($cliente);
                    echo "Mensaje enviado a cliente de mesa {$numMesa} (cliente: {$clienteId})\n";
                }
            }
        }
    }
}

// Crear e iniciar el servidor
$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new ServidorPedidos()
        )
    ),
    8080
);

// Mensaje indicando que el servidor está activo
echo "Servidor WebSocket escuchando en el puerto 8080...\n";
$server->run();
