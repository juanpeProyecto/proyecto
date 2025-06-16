<?php

function enviarNotificacionWebSocket($mensaje) {
    // direccion del servidor WebSocket 
    //http no puede usar websockets directamente
    $host = 'localhost';
    $puerto = 8080;
    
    try {
        // Creo un cliente WebSocket usando protocolo TCP/IP
        $context = stream_context_create();
        $socket = stream_socket_client(
            "tcp://$host:$puerto", 
            $errorNo, 
            $errorStr, 
            10,
            STREAM_CLIENT_CONNECT,
            $context
        );
        
        if (!$socket) {
            error_log("Error de conexión WebSocket: $errorNo - $errorStr");
            return false;
        }
        
        // Convierto los  datos a formato JSON para enviarlos
        $jsonMensaje = json_encode($mensaje);
        
        // Escribo en el socket
        fwrite($socket, $jsonMensaje, strlen($jsonMensaje));
        
        // Cierro la conexión
        fclose($socket);
        
        error_log("Notificación WebSocket enviada correctamente: " . $jsonMensaje);
        return true;
        
    } catch (Exception $e) {
        error_log("Error al enviar notificación WebSocket: " . $e->getMessage());
        return false;
    }
}
?>
