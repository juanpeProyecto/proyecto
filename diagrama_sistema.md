# Diagrama del Sistema de Pedidos de Restaurante

## 1. Mapa de Pantallas Principales y Almacenamiento de Información

```
+-------------------+     +-------------------+     +-------------------+
|                   |     |                   |     |                   |
|    mesa.php       |---->|     menu.php      |---->|   carrito.php     |
|                   |     |                   |     |                   |
+-------------------+     +-------------------+     +-------------------+
        |                        |                         |
 Selección de Mesa        Selección Productos       Revisión del Pedido
        |                        |                         |
        v                        v                         v
   [Base de Datos]          [Base de Datos]          [Sesión PHP]
   Tabla: Mesas            Tabla: Productos          (Temporal)
                                                          |
                                                          |
                    +---------------------+               |
                    |                     |<--------------+
                    | confirmarPedido.php |
                    |                     |
                    +---------------------+
                              |
                    Guarda el pedido completo
                              |
                              v
                        [Base de Datos]
                      Tablas: Pedidos y
                       DetallePedidos
                              |
                              | WebSocket: Notificación
                              | "nuevoPedido"
                              v
                    +---------------------+
                    |                     |
                    |     cocina.php      |
                    |                     |
                    +---------------------+
                              |
                     Cambio de estado de
                     pedidos (pendiente,
                    preparando, listo, etc.)
                              |
                              v
                    +---------------------+
                    | actualizar_estado   |
                    |      .php           |
                    +---------------------+
                              |
                              | WebSocket: Notificación
                              | "cambioEstado"
                              v
                        [Base de Datos]
                    Actualiza DetallePedidos
                              |
                              v
                   +----------------------+
                   |   Interfaz Cocina    |
                   | (actualización en    |
                   |    tiempo real)      |
                   +----------------------+
```

## 2. Descripción de cada Pantalla y su Propósito

### `mesa.php`
- **Función**: Página inicial donde el cliente selecciona la mesa.
- **Información guardada**: No guarda información, solo obtiene las mesas disponibles.
- **Flujo de datos**: Lee de la tabla `Mesas` y pasa el número de mesa seleccionado a `menu.php`.

### `menu.php`
- **Función**: Muestra todos los productos disponibles organizados por categorías.
- **Información guardada**: No guarda información, solo muestra productos.
- **Flujo de datos**: Lee de la tabla `Productos` y `Categorias`, permite añadir productos al carrito.

### `carrito.php`
- **Función**: Muestra los productos seleccionados, permite modificar cantidades y añadir observaciones.
- **Información guardada**: Almacena temporalmente el pedido en la sesión del usuario.
- **Flujo de datos**: Lee datos de la sesión PHP, envía los datos del pedido a `confirmarPedido.php`.

### `confirmarPedido.php`
- **Función**: Confirma el pedido y lo registra en la base de datos.
- **Información guardada**: Crea registros en las tablas `Pedidos` y `DetallePedidos`.
- **Flujo de datos**: 
  1. Recibe datos del formulario de `carrito.php`
  2. Guarda el pedido en la base de datos
  3. Envía una notificación WebSocket de "nuevoPedido"

### `cocina.php`
- **Función**: Muestra los pedidos pendientes y en preparación para los cocineros.
- **Información guardada**: No guarda información, solo muestra pedidos.
- **Flujo de datos**: 
  1. Lee pedidos pendientes y en preparación de productos atendidos por cocineros
  2. Permite cambiar el estado de los pedidos
  3. Recibe actualizaciones en tiempo real vía WebSocket

### `actualizar_estado.php` / `actualizar_estado_pedido.php`
- **Función**: Actualiza el estado de un pedido (pendiente → preparando → listo).
- **Información guardada**: Actualiza el campo `Estado` en la tabla `DetallePedidos`.
- **Flujo de datos**: 
  1. Recibe el ID del pedido y el nuevo estado
  2. Actualiza la base de datos
  3. Envía una notificación WebSocket de "cambioEstado"

## 3. Flujo Completo del Sistema WebSocket

```
+------------------------+          +------------------------+          +------------------------+
|                        |          |                        |          |                        |
|  Interfaz de Usuario   |          |    Servidor WebSocket  |          |   Interfaz de Cocina   |
|  (confirmarPedido.php, |          |  (PedidosServer.php)   |          |     (cocina.php)       |
|  actualizar_estado.php)|          |   Puerto: 8080         |          |     (cocina.js)        |
+------------------------+          +------------------------+          +------------------------+
          |                                    |                                   |
          |                                    | 1. Inicia y espera conexiones     |
          |                                    |-------------------------          |
          |                                    |                        |          |
          |                                    |<------------------------          |
          |                                    |                                   |
          | 2. Nuevo pedido                    |                                   | 2a. Carga inicial
          | (confirmarPedido.php)              |                                   |    (obtiene pedidos)
          |                                    |                                   |
          | --WebSocket: nuevoPedido---------->|                                   |
          |                                    |                                   |
          |                                    |--WebSocket: nuevoPedido---------->|
          |                                    |                                   |
          |                                    |                                   | 2b. Actualiza UI
          |                                    |                                   |     añadiendo nuevo
          |                                    |                                   |     pedido
          |                                    |                                   |
          | 3. Cambio de estado               |                                   |
          | (actualizar_estado.php)            |                                   |
          |                                    |                                   |
          | --WebSocket: cambioEstado--------->|                                   |
          |                                    |                                   |
          |                                    |--WebSocket: cambioEstado--------->|
          |                                    |                                   |
          |                                    |                                   | 3b. Actualiza UI
          |                                    |                                   |     moviendo el pedido
          |                                    |                                   |     entre secciones
          |                                    |                                   |
          | 4. Desconexión cliente            |                                   |
          | (Por cierre de navegador)          |                                   |
          |                                    |                                   |
          | --Cierra conexión----------------->|                                   |
          |                                    | 4a. Maneja desconexión            |
          |                                    |------------------------           |
          |                                    |                       |           |
          |                                    |<-----------------------           |
          |                                    |                                   |
          |                                    |                                   | 5. Reconexión automática
          |                                    |                                   |    (si la conexión se pierde)
          |                                    |                                   |
          |                                    |<-----Intenta reconectar-----------| 
          |                                    |                                   |
          |                                    |------Conexión restablecida------->|
          |                                    |                                   |
```

## 4. Detalle del Flujo de WebSocket

### Inicio del Servidor WebSocket
1. El servidor WebSocket (`PedidosServer.php`) se inicia mediante un proceso en segundo plano.
2. Escucha en el puerto 8080 y espera conexiones de clientes.

### Conectar Cliente WebSocket
1. Cuando `cocina.php` se carga, `cocina.js` crea una nueva instancia de `WebSocketClient` (de `websocket.js`).
2. Establece una conexión con el servidor WebSocket en `ws://localhost:8080`.
3. Configura manejadores de eventos para `open`, `message`, `error` y `close`.
4. Si la conexión se pierde, intenta reconectar automáticamente.

### Enviar Notificación de Nuevo Pedido
1. Cuando se confirma un pedido en `confirmarPedido.php`:
   - Se guarda el pedido en la base de datos
   - Se crea un cliente WebSocket y se conecta al servidor
   - Se envía un mensaje JSON con tipo "nuevoPedido" y datos relevantes
   - El cliente WebSocket se cierra después de enviar el mensaje

### Enviar Notificación de Cambio de Estado
1. Cuando se actualiza el estado en `actualizar_estado.php`:
   - Se actualiza el estado del pedido en la base de datos
   - Se crea un cliente WebSocket y se conecta al servidor
   - Se envía un mensaje JSON con tipo "cambioEstado" y datos relevantes
   - El cliente WebSocket se cierra después de enviar el mensaje

### Recibir y Procesar Mensajes
1. En la interfaz de cocina, `cocina.js` está escuchando mensajes WebSocket.
2. Cuando llega un mensaje, verifica su tipo:
   - Si es "nuevoPedido": Actualiza la interfaz añadiendo el nuevo pedido en la sección correspondiente
   - Si es "cambioEstado": Mueve el pedido entre las secciones correspondientes según el nuevo estado

### Manejo de Desconexión
1. Si el cliente pierde conexión, `websocket.js` detecta el cierre y activa la reconexión automática.
2. Intenta reconectar cada pocos segundos hasta que la conexión se restablece.
3. Una vez reconectado, la interfaz puede seguir recibiendo actualizaciones en tiempo real.

## 5. Tablas de la Base de Datos y su Relación

- **Mesas**: Almacena información de las mesas disponibles
- **Categorias**: Categorías de productos (ej: entrantes, bebidas, postres)
- **Productos**: Información de cada producto, incluido quién lo atiende (cocinero, barra, etc.)
- **Pedidos**: Pedidos generales con información de la mesa y fecha
- **DetallePedidos**: Detalles específicos de cada ítem en un pedido y su estado
- **Empleados**: Información de los empleados (cocineros, camareros, etc.)
- **EmpleadoDetallesPedidos**: Registro de qué empleado atendió cada detalle de pedido

La clave del sistema es el flujo de información en tiempo real y la gestión eficiente de los estados de pedidos, todo ello coordinado mediante WebSockets para proporcionar actualizaciones instantáneas.
