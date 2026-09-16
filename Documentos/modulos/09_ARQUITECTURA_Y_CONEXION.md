# Módulo 09: Arquitectura de Software y Conexión (StockYServe)

## 1. Propósito del Módulo
Explicar el diseño arquitectónico de StockYServe, comparando el funcionamiento del patrón **MVC Nativo en PHP** con la arquitectura desacoplada de la migración a **React + API REST**, detallando el ciclo de vida de una petición HTTP, el enrutamiento, la gestión de sesiones/tokens y la integración entre capas.

---

## 2. Arquitectura MVC (Versión PHP)

El sistema original sigue el patrón clásico **Modelo - Vista - Controlador**:

```
                       ┌────────────────────────────────────────┐
                       │          NAVEGADOR (Cliente)           │
                       └──────────────────┬─────────────────────┘
                                          │  Petición HTTP (GET / POST)
                                          ▼
                       ┌────────────────────────────────────────┐
                       │       CONTROLADORES (Controllers)      │
                       │   Reciben datos, validan reglas de     │
                       │       negocio y controlan el flujo     │
                       └───────────┬────────────────┬───────────┘
                                   │                │
            Llama a métodos de BD  │                │  Pasa datos procesados
                                   ▼                ▼
┌────────────────────────────────────────┐    ┌────────────────────────────────────────┐
│             MODELOS (Models)           │    │             VISTAS (Views)             │
│   Consultas SQL vía PDO a MySQL        │    │    Plantillas HTML/PHP con CSS         │
│      Devuelve datos en arrays          │    │      y alertas con SweetAlert2         │
└────────────────────────────────────────┘    └────────────────────────────────────────┘
```

### Principios de Separación de Responsabilidades:
1. **Modelo (`models/`):** Solo se encarga de la lógica de acceso a datos. No contiene HTML ni gestiona sesiones.
2. **Controlador (`controllers/`):** Recibe la solicitud (`$_POST`, `$_GET`), valida permisos, llama al modelo correspondiente y decide qué vista mostrar o a qué URL redirigir.
3. **Vista (`views/`):** Únicamente presenta la información al usuario en pantalla. No ejecuta sentencias `SELECT` ni `INSERT` directamente.

---

## 3. Arquitectura Desacoplada (Versión React + REST API)

En la versión migrada a React, el frontend y el backend operan de forma independiente:

```
[ FRONTEND: React (Vite) ]  ---( JSON / Axios vía HTTP )--->  [ BACKEND: API REST (Node/Express) ]
 Puerto: 5173                                                 Puerto: 5000 o PHP endpoints
 - Componentes UI (Lucide Icons)                              - Rutas y Middlewares (CORS, Auth)
 - Manejo de Estado (useState, useEffect)                     - Controladores y Lógica de Negocio
 - React Router (Navegación sin recargar)                     - Modelos y Acceso a Base de Datos
```

---

## 4. Ciclo Request - Response de una Petición Típica

1. **Usuario hace clic en "Entregar Pedido":**
   - Se envía un `POST` con el `id_pedido` al controlador `PedidoController.php?action=cambiar_estado`.
2. **Validación de Capa (Controlador):**
   - Comprueba que exista sesión activa (`isset($_SESSION['usuario'])`) y que el rol sea Mesero o Cocinero.
3. **Ejecución de Persistencia (Modelo):**
   - Llama a `Pedido::actualizarEstado($id_pedido, 'Entregado')`.
   - El modelo ejecuta el `UPDATE` parametrizado en MySQL.
4. **Respuesta al Usuario:**
   - En PHP: Se asigna `$_SESSION['alert'] = ['tipo' => 'success', 'mensaje' => 'Pedido entregado']` y se ejecuta `header("Location: ../views/mesero/dashboard.php")`.
   - En React: El backend devuelve `{ success: true, estado: 'Entregado' }` con código HTTP `200 OK`, y el frontend actualiza el estado local (`setOrders(...)`) sin recargar la página.

---

## 5. Trampas y Errores Típicos en Evaluaciones

| Si el evaluador cambia... | ¿Qué error produce? | ¿Cómo explicar qué pasó y para qué sirve? |
|---|---|---|
| Coloca sentencias SQL directamente dentro de una Vista (`views/*.php`) | Viola el patrón arquitectónico MVC. | *"El patrón MVC exige separar la presentación de la persistencia. Las vistas no deben acoplarse directamente al motor de base de datos; esto debe delegarse al Modelo."* |
| Hace un `echo` o imprime texto antes de una función `header("Location: ...")` | Error fatal: `Warning: Cannot modify header information - headers already sent`. | *"Las cabeceras HTTP (`header`) deben enviarse antes de cualquier salida de texto o HTML al navegador. Si ya se imprimió algo, el encabezado HTTP ya se cerró y no puede redireccionar."* |
| Quita el middleware de CORS (`app.use(cors())`) en la API REST | Error en la consola del navegador: `Access to XMLHttpRequest blocked by CORS policy`. | *"CORS (Cross-Origin Resource Sharing) es una medida de seguridad de los navegadores. Si el frontend (puerto 5173) y el backend (puerto 5000) están en orígenes distintos, el servidor debe autorizar explícitamente el intercambio con cabeceras CORS."* |
| Quita el encabezado `header('Content-Type: application/json')` en un endpoint que devuelve JSON | El frontend recibe texto plano en lugar de un objeto serializado, fallando en el parsing de la respuesta. | *"El encabezado `Content-Type` le indica al cliente el formato MIME de la respuesta (`application/json`) para que se procese automáticamente como un objeto de datos."* |
| Olvida importar la clase con `require_once` | Error fatal: `Fatal error: Uncaught Error: Class 'Database' not found`. | *"`require_once` incluye e interpreta el archivo de la clase requerida una sola vez, asegurando que sus definiciones estén disponibles en tiempo de ejecución."* |
