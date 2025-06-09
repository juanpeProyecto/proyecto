# Documentación del Sistema Modular de Cocina

## Diagrama de Flujo del Sistema

```
┌───────────────────────────────────────┐         
│            INTERFAZ COCINA            │         
│  ┌───────────────┐  ┌───────────────┐ │         
│  │ Visualización │  │    Acciones   │ │         
│  │  de Pedidos   │  │   del Usuario │ │         
│  └───────┬───────┘  └───────┬───────┘ │         
└─────────┬│───────────────────│────────┘         
          ││                   │                  
          ││                   ▼                  
          ││        ┌─────────────────────┐       
          ││        │   cocina.js         │       
          ││        │ ┌─────────────────┐ │       
          ││        │ │cargarPedidosCocina│       
          ││        │ └────────┬────────┘ │       
          ││        │          │          │       
          ││        │ ┌────────┴────────┐ │       
          ││        │ │actualizarEstado │ │       
          ││        │ │   Producto      │ │       
          ││        │ └────────┬────────┘ │       
          ││        │          │          │       
          ││        │ ┌────────┴────────┐ │       
          ││        │ │ inicializarBotones│       
          ││        │ └─────────────────┘ │       
          ││        └──────────┬──────────┘       
          ▼▼                   │                  
┌──────────────────┐          ▼                   
│   cocina.php     │  ┌─────────────────┐         
│  ┌────────────┐  │  │ WebSocket       │         
│  │ Router API │  │  │ Notificaciones  │         
│  └──────┬─────┘  │  │ en tiempo real  │         
│         │        │  └─────────────────┘         
│  ┌──────┴─────┐  │                              
│  │ Acciones:  │  │                              
│  │ actualizar │  │                              
│  │ Estado     │  │                              
│  │ Producto   │  │                              
│  │           │  │                              
│  │ cambiarEstado│                              
│  │ Pedido     │  │                              
│  │           │  │                              
│  │ obtenerPedidos│                              
│  │ Pendientes │  │                              
│  └──────┬─────┘  │                              
└─────────┼────────┘                              
          │                                       
          ▼                                       
┌──────────────────┐                              
│  funciones.php   │                              
│ ┌──────────────┐ │                              
│ │obtenerDetalle│ │                              
│ │Pedido        │ │                              
│ └──────┬───────┘ │                              
│        │         │                              
│ ┌──────┴───────┐ │                              
│ │actualizarEstado│                              
│ │ProductoCompleto│                              
│ └──────┬───────┘ │                              
│        │         │                              
│ ┌──────┴───────┐ │                              
│ │cambiarEstado │ │                              
│ │Pedido        │ │                              
│ └──────┬───────┘ │                              
│        │         │                              
│ ┌──────┴───────┐ │                              
│ │obtenerPedidos│ │                              
│ │PendientesArea│ │                              
│ └──────┬───────┘ │                              
└────────┼─────────┘                              
         │                                        
         ▼                                        
┌─────────────────┐                               
│  Base de Datos  │                               
└─────────────────┘                               
```

## Explicación del Flujo

### 1. Flujo de Carga de Página:

1. **Usuario accede a `cocina.php`**
   - PHP genera la estructura HTML básica con las mesas y pedidos pendientes
   - Se cargan los scripts `cocina.js` y otros

2. **Inicialización de JavaScript (`DOMContentLoaded`)**:
   - `cocina.js` llama a `cargarPedidosCocina()` para actualizar contadores
   - `inicializarBotones()` configura los eventos para los botones de actualización de estado
   - `iniciarWebSocket()` establece la conexión para actualización en tiempo real
   - `actualizarContadores()` actualiza los contadores visuales de pedidos

### 2. Flujo de Actualización de Estado de Producto:

1. **Usuario hace clic en un botón de estado (Preparando/Listo)**
   - `actualizarEstadoProducto()` en `cocina.js` se activa
   - Realiza una solicitud AJAX a `cocina.php` con action=actualizarEstadoProducto

2. **`cocina.php` procesa la solicitud**:
   - El router API identifica la acción
   - Llama a `procesarActualizarEstadoProducto()`
   - Esta función llama a `actualizarEstadoProductoCompleto()` en `funciones.php`

3. **`funciones.php` ejecuta la lógica de negocio**:
   - Actualiza el estado del producto en la base de datos
   - Calcula el estado global del pedido basado en productos
   - Actualiza el estado del pedido si es necesario
   - Devuelve respuesta con estado de pedido actualizado

4. **JavaScript actualiza la UI**:
   - Cambia el color y aspecto del elemento de producto
   - Si todos los productos están listos, marca el pedido como completado
   - Si es necesario, elimina el pedido de la interfaz con animación

### 3. Flujo de WebSockets para Actualización en Tiempo Real:

1. **Se produce un cambio en alguna parte del sistema**
   - Por ejemplo, un camarero crea un nuevo pedido
   - El servidor WebSocket envía notificación a todos los clientes

2. **`iniciarWebSocket()` en `cocina.js` recibe la notificación**
   - Procesa el tipo de mensaje
   - Si es un nuevo pedido, puede actualizar contadores o recargar la pantalla
   - Si es actualización de estado, refleja el cambio sin recargar la página

### 4. Flujo para Marcar Pedido como Completado:

1. **Usuario marca un pedido como completado**
   - `inicializarBotonesCompletarPedido()` maneja el evento
   - Envía solicitud a `cocina.php` con action=cambiarEstadoPedido

2. **`cocina.php` procesa la solicitud**:
   - El router API identifica la acción
   - Llama a `procesarCambiarEstadoPedido()`
   - Esta función llama a `cambiarEstadoPedido()` en `funciones.php`

3. **`funciones.php` ejecuta la actualización**:
   - Cambia el estado del pedido en la base de datos
   - Devuelve confirmación

4. **JavaScript actualiza la UI**:
   - Aplica animación de salida al pedido
   - Lo elimina del DOM después de la animación

## Funciones Principales

### PHP (funciones.php):
- **obtenerDetallePedido($codPedido)**:
  - Obtiene todos los datos de un pedido con sus productos
  - Incluye info de mesa, estado, productos y sus estados
  - Devuelve un array estructurado para JSON

- **actualizarEstadoProductoCompleto($codPedido, $codProducto, $estado, $area)**:
  - Actualiza el estado de un producto específico
  - Recalcula el estado global del pedido basado en estados de todos los productos
  - Usa transacciones para garantizar la integridad de la BD
  - Devuelve si el pedido está completamente listo

- **cambiarEstadoPedido($codPedido, $estado)**:
  - Actualiza directamente el estado global de un pedido
  - Simplifica el proceso cuando se quiere forzar un estado

- **obtenerPedidosPendientesArea($area)**:
  - Filtra pedidos por área específica (cocina/bar)
  - Devuelve solo pedidos con estado pendiente o preparando
  - Organiza los resultados por mesa para facilitar visualización

### JavaScript (cocina.js):
- **cargarPedidosCocina()**:
  - Realiza solicitud fetch para obtener datos actualizados
  - Actualiza contadores sin manipular el DOM completo
  - Maneja errores de conexión

- **actualizarEstadoProducto()**:
  - Gestiona UI cuando se cambia estado de un producto
  - Envía datos al servidor mediante fetch API
  - Maneja la respuesta, incluida la animación de eliminación si es necesario

- **inicializarBotones()**:
  - Configura event listeners para todos los botones de acción
  - Asegura que los eventos se asignen correctamente incluso en elementos dinámicos

- **iniciarWebSocket()**:
  - Establece conexión WebSocket para actualizaciones en tiempo real
  - Define handlers para diferentes tipos de mensajes
  - Implementa reconexión automática si se pierde la conexión

## Cambios Realizados Durante la Modularización

### 1. Modularización de Consultas SQL en funciones.php:
- Se crearon funciones dedicadas para cada operación de base de datos
- Se eliminó código repetitivo en múltiples archivos PHP
- Se mejoró el manejo de errores con respuestas JSON consistentes
- Se añadieron transacciones para operaciones complejas

### 2. Consolidación de Endpoints API:
- Se eliminaron archivos PHP individuales (obtenerPedidosPendientes.php, actualizarEstadoProducto.php)
- Se creó un router centralizado en cocina.php que maneja múltiples acciones
- Se implementó detección automática de acciones basada en parámetros

### 3. Mejoras JavaScript:
- Se renombraron funciones para evitar conflictos entre archivos
- Se añadieron verificaciones antes de manipular el DOM
- Se mejoraron animaciones para transiciones suaves
- Se optimizaron las llamadas AJAX

### 4. Corrección de Bugs:
- Se resolvieron errores 404 al actualizar URLs en JavaScript
- Se corrigió el conflicto de funciones con mismo nombre
- Se previno el error "No se encontró el contenedor de pedidos"
- Se aseguró la compatibilidad con la estructura HTML generada por PHP

## Ventajas del Sistema Modularizado

1. **Mayor Mantenibilidad**: Código más organizado y fácil de entender
2. **Reducción de Duplicación**: Funciones centralizadas que se reutilizan
3. **Mejor Manejo de Errores**: Respuestas y errores consistentes
4. **Mayor Escalabilidad**: Fácil adición de nuevas funcionalidades
5. **Rendimiento Mejorado**: Menos consultas a la base de datos
6. **Experiencia de Usuario Fluida**: Animaciones y actualizaciones sin recargar

## Recomendaciones Futuras

1. **Implementar Autenticación Robusta**: Reemplazar el hardcoding de ID de empleados
2. **Ampliar Tests**: Añadir pruebas unitarias para las funciones modulares
3. **Documentar API**: Crear documentación formal de los endpoints disponibles
4. **Optimizar WebSocket**: Implementar reconexión con backoff exponencial
5. **Mejorar Accesibilidad**: Asegurar que la UI cumple estándares WCAG
