<?php
/**
 * Funciones para enviar notificaciones WebSocket en el sistema de pedidos
 * Este archivo centraliza la comunicación con el servidor WebSocket
 */

/**
 * Envía un mensaje al servidor WebSocket
 * 
 * @param array $datos Los datos a enviar
 * @return bool Resultado de la operación
 */
function enviarNotificacionWebSocket($datos) {
    // URL del servidor WebSocket (WebSocket no puede usar HTTP directamente)
    $host = 'localhost';
    $port = 8080;
    
    try {
        // Crear un cliente WebSocket usando protocolo TCP/IP
        // Esto simula lo que haría un navegador al enviar un mensaje WebSocket
        $context = stream_context_create();
        $socket = stream_socket_client(
            "tcp://$host:$port", 
            $errno, 
            $errstr, 
            10,
            STREAM_CLIENT_CONNECT,
            $context
        );
        
        if (!$socket) {
            error_log("Error de conexión WebSocket: $errno - $errstr");
            return false;
        }
        
        // Convertir datos a JSON para enviar
        $mensaje = json_encode($datos);
        
        // Escribir en el socket
        fwrite($socket, $mensaje, strlen($mensaje));
        
        // Cerrar la conexión
        fclose($socket);
        
        // Registrar éxito
        error_log("Notificación WebSocket enviada correctamente: " . $mensaje);
        return true;
        
    } catch (Exception $e) {
        error_log("Error al enviar notificación WebSocket: " . $e->getMessage());
        return false;
    }
}
?>
