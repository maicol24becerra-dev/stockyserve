# StockYServe — Documentación Técnica del Proyecto

> Sistema de gestión para el Centro Vacacional y Recreacional **El Cielo**  
> Desarrollado con PHP 8, MySQL 8 y Laragon · Patrón MVC · Sin frameworks externos

---

## Tabla de Contenidos

1. [Descripción General](#1-descripción-general)
2. [Tecnologías Utilizadas](#2-tecnologías-utilizadas)
3. [Estructura del Proyecto](#3-estructura-del-proyecto)
4. [Arquitectura — Patrón MVC](#4-arquitectura--patrón-mvc)
5. [Base de Datos](#5-base-de-datos)
6. [Roles y Permisos](#6-roles-y-permisos)
7. [Módulos del Sistema](#7-módulos-del-sistema)
   - 7.1 [Autenticación](#71-autenticación)
   - 7.2 [Panel Administrador](#72-panel-administrador)
   - 7.3 [Panel Mesero](#73-panel-mesero)
   - 7.4 [Panel Cocinero](#74-panel-cocinero)
   - 7.5 [Panel Cliente](#75-panel-cliente)
8. [Controladores](#8-controladores)
9. [Modelos](#9-modelos)
10. [Vistas](#10-vistas)
11. [Flujo de un Pedido](#11-flujo-de-un-pedido)
12. [Reportes de Ventas](#12-reportes-de-ventas)
13. [Instalación y Configuración](#13-instalación-y-configuración)
14. [Credenciales de Prueba](#14-credenciales-de-prueba)
15. [Historias de Usuario Implementadas](#15-historias-de-usuario-implementadas)

---

## 1. Descripción General

**StockYServe** es un sistema web de gestión integral para restaurantes y centros vacacionales. Permite administrar el ciclo completo de atención al cliente: desde la toma de pedidos por parte del mesero, la preparación en cocina, hasta el cobro y la generación de reportes de ventas para el administrador.

El sistema está orientado al **Centro Vacacional y Recreacional El Cielo** y cubre cuatro roles de usuario con paneles independientes y funcionalidades específicas para cada uno.

---

## 2. Tecnologías Utilizadas

| Componente | Tecnología |
|---|---|
| Lenguaje backend | PHP 8.1 |
| Base de datos | MySQL 8.0 |
| Servidor local | Laragon (Apache + MySQL en puerto 3320) |
| Conexión BD | PDO con manejo de excepciones |
| Frontend | HTML5, CSS3, JavaScript vanilla |
| Iconos | Font Awesome 6 |
| Tipografía | Google Fonts (Nunito, Outfit, Playfair Display) |
| Alertas | SweetAlert2 |
| Gráficas | Chart.js 4.4 |
| Control de versiones | Git |

---

## 3. Estructura del Proyecto

```
StockYServe/
│
├── config/
│   └── database.php              # Clase Database — conexión PDO
│
├── controllers/
│   ├── AdminController.php       # Gestión de usuarios (admin)
│   ├── AuthController.php        # Login, logout, registro, recuperación
│   ├── InventarioController.php  # Materia prima y recetas
│   ├── MeseroController.php      # (Auxiliar mesero)
│   ├── PedidoController.php      # CRUD pedidos, cambio de estado, pagos
│   ├── PlatoController.php       # CRUD platos del menú
│   ├── RegisterController.php    # Registro alternativo
│   ├── ReporteController.php     # Exportación de reportes a PDF
│   └── UsuarioController.php     # Gestión de perfil
│
├── models/
│   ├── MateriaPrima.php          # Modelo inventario físico
│   ├── Pedido.php                # Modelo pedidos + reportes de ventas
│   ├── Plato.php                 # Modelo menú
│   ├── Receta.php                # Modelo recetas (plato ↔ materia prima)
│   └── Usuario.php               # Modelo usuarios
│
├── views/
│   ├── admin/
│   │   └── dashboard.php         # Panel administrador (SPA con tabs)
│   ├── cliente/
│   │   └── dashboard.php         # Panel cliente
│   ├── cocinero/
│   │   └── dashboard.php         # Panel cocina con auto-refresh
│   ├── mesero/
│   │   └── dashboard.php         # Panel mesero con carrito flotante
│   ├── layouts/
│   │   ├── header.php
│   │   ├── sidebar.php
│   │   └── footer.php
│   └── usuarios/
│       ├── registro.php          # Formulario de registro
│       ├── recuperar.php         # Solicitud de código de recuperación
│       ├── verificar_codigo.php  # Verificación del código
│       └── cambiar_password.php  # Nueva contraseña
│
├── public/
│   ├── index.php                 # Página de login (punto de entrada)
│   └── responsive.css            # Estilos responsivos globales
│
├── sql/
│   └── bdstockyserve.sql         # Script de creación de la BD
│
├── uploads/                      # Imágenes de platos subidas
├── img/                          # Recursos gráficos estáticos
├── Scrits/                       # Scripts de mantenimiento/reparación BD
└── Documentos/                   # Documentación del proyecto
```

---

## 4. Arquitectura — Patrón MVC

El proyecto implementa el patrón **Modelo-Vista-Controlador** de forma manual, sin framework:

```
Navegador
    │
    ▼
public/index.php  ←── Punto de entrada (login)
    │
    ▼
controllers/*.php  ←── Reciben POST/GET, validan, llaman al modelo
    │
    ▼
models/*.php       ←── Consultas PDO a MySQL, devuelven arrays
    │
    ▼
views/*.php        ←── HTML + PHP para renderizar la respuesta
```

**Flujo típico:**
1. El usuario envía un formulario a un controlador (ej. `PedidoController.php`)
2. El controlador valida la sesión y los datos
3. Llama al método correspondiente del modelo
4. Guarda un mensaje en `$_SESSION['alert']`
5. Redirige con `header('Location: ...')` a la vista correspondiente
6. La vista lee `$_SESSION['alert']` y lo muestra con SweetAlert2

---

## 5. Base de Datos

**Nombre:** `bdstockyserve`  
**Motor:** InnoDB · Charset: utf8mb4  
**Puerto:** 3320 (Laragon)

### Diagrama de tablas

```
rol ──────────────── usuario ──────────────── cliente
 (id_rol)             (id_usuario)              (id_cliente)
                           │                        │
                           │                        │
                      pedido ◄───────────────────────
                      (id_pedido, id_usuario, id_cliente)
                           │
                      item_pedido ──────── plato
                      (id_item_pedido)      (id_plato)
                           │
                      pago
                      (id_pago, metodo_pago, monto_pagado)

plato ──────── receta ──────── materia_prima
(id_plato)    (id_receta)       (id_materia)
```

### Descripción de tablas

| Tabla | Descripción | Columnas clave |
|---|---|---|
| `rol` | Roles del sistema | `id_rol`, `nombre` |
| `usuario` | Todos los usuarios | `id_usuario`, `nombre`, `correo`, `contrasena`, `id_rol`, `estado`, `reset_code`, `reset_expiry` |
| `cliente` | Perfil de cliente (FK a usuario) | `id_cliente`, `id_usuario` |
| `plato` | Menú del restaurante | `id_plato`, `nombre`, `precio`, `descripcion`, `disponibilidad`, `categoria`, `imagen` |
| `pedido` | Órdenes de servicio | `id_pedido`, `fecha`, `estado`, `id_usuario` (mesero), `id_cliente` |
| `item_pedido` | Líneas de cada pedido | `id_item_pedido`, `cantidad`, `precio_unitario`, `id_pedido`, `id_plato` |
| `pago` | Registro de cobros | `id_pago`, `monto_pagado`, `metodo_pago`, `fecha_pago`, `id_pedido` |
| `materia_prima` | Inventario físico | `id_materia`, `nombre`, `stock_actual`, `stock_minimo`, `unidad_medida` |
| `receta` | Ingredientes por plato | `id_receta`, `id_plato`, `id_materia`, `cantidad_requerida` |

### Estados de un pedido

```
Pendiente → En preparación → Entregado → Pagado
                                       ↘ Cancelado
```

---

## 6. Roles y Permisos

| id_rol | Nombre | Panel | Acceso |
|---|---|---|---|
| 1 | Administrador | `views/admin/dashboard.php` | Gestión total del sistema |
| 2 | Mesero | `views/mesero/dashboard.php` | Pedidos, menú, cobros |
| 3 | Cocinero | `views/cocinero/dashboard.php` | Ver y actualizar estado de pedidos |
| 4 | Cliente | `views/cliente/dashboard.php` | Ver menú, ver sus pedidos |

La redirección por rol se realiza en `AuthController::redirectByRole()` al hacer login.

---

## 7. Módulos del Sistema

### 7.1 Autenticación

**Archivo:** `controllers/AuthController.php`

| Acción | Descripción |
|---|---|
| `login` | Valida credenciales, controla intentos fallidos (máx. 3), bloquea 15 min |
| `register` | Crea usuario con rol Cliente e inserta fila en tabla `cliente` |
| `logout` | Destruye la sesión y redirige al login |
| `send_code` | Genera código de 4 dígitos y lo guarda con expiración de 15 min |
| `verify_code` | Valida el código ingresado contra la BD |
| `update_password` | Actualiza la contraseña con hash bcrypt |

**Seguridad implementada:**
- Contraseñas hasheadas con `password_hash()` / `password_verify()`
- Regeneración de ID de sesión tras login exitoso (`session_regenerate_id`)
- Bloqueo por intentos fallidos (3 intentos → 15 minutos)
- Prevención de retroceso del navegador con `window.history.pushState`

---

### 7.2 Panel Administrador

**Archivo:** `views/admin/dashboard.php`

Panel de una sola página (SPA) con 5 secciones navegables por sidebar:

#### Estadísticas
- KPIs del mes: total ventas, pedidos completados, clientes atendidos
- Top 5 platillos más vendidos del mes
- Enlace directo a reportes completos

#### Reportes de Ventas
- Filtros combinados: **período** (semana/mes/año), **método de pago**, **categoría**, **platillo**
- Chips de filtros activos con botón para limpiar cada uno individualmente
- 4 KPIs: Total ingresos, Pedidos pagados, Clientes atendidos, Ticket promedio
- 3 gráficas (Chart.js): línea de evolución, barras de platillos, dona de métodos de pago
- Tabla de ranking de platillos con medallas (🥇🥈🥉)
- Tabla de desglose por método de pago con barra de progreso
- **Exportar PDF** — abre `ReporteController.php` con los filtros activos

#### Pedidos
- Lista todos los pedidos con filtros por estado
- Muestra ítems, totales y método de pago

#### Usuarios
- Tabla de todos los usuarios con rol, estado y acciones
- Crear nuevo empleado (modal)
- Cambiar rol (modal)
- Activar/desactivar cuenta
- Eliminar usuario

#### Inventario
- Gestión del menú: agregar, editar, cambiar disponibilidad, configurar receta
- Gestión de materia prima: agregar, editar, ingresar stock
- Alertas visuales cuando el stock está por debajo del mínimo

---

### 7.3 Panel Mesero

**Archivo:** `views/mesero/dashboard.php`

#### Pedidos Activos
- Lista todos los pedidos en curso con filtros por estado
- Botones de avance de estado: `En preparación` → `Entregar` → `Registrar Pago`
- Modal de pago con selector de método (Efectivo, Tarjeta, Transferencia, Nequi, Daviplata)
- Botón "Cuenta" — abre modal con desglose completo, permite agregar/quitar ítems

#### Crear Pedido
- Carrito flotante (FAB) con badge de cantidad
- Búsqueda de platos por nombre
- Filtro por categoría
- Asignación de cliente (búsqueda por nombre/correo)
- Confirmación con SweetAlert2 antes de enviar

#### Historial
- Tabla de los últimos 50 pedidos con estado

---

### 7.4 Panel Cocinero

**Archivo:** `views/cocinero/dashboard.php`

- Muestra pedidos en estado `Pendiente` y `En preparación`
- Ordenados por urgencia: primero los que llevan más de 15 minutos
- Indicador de tiempo con colores: verde (<8 min), amarillo (8-14 min), rojo (≥15 min)
- 3 KPIs: Pendientes, En preparación, Urgentes
- Filtros: Todos / Pendientes / En preparación / Urgentes
- Botones: `Iniciar preparación` (Pendiente→En preparación) y `Marcar Listo` (En preparación→Entregado)
- **Auto-refresh cada 15 segundos** para recibir nuevos pedidos en tiempo real
- Historial de pedidos completados

---

### 7.5 Panel Cliente

**Archivo:** `views/cliente/dashboard.php`

#### Inicio
- Banner de bienvenida personalizado
- Últimos 3 pedidos con estado en tiempo real
- Acceso rápido al menú

#### Explorar Menú
- Todos los platos disponibles con imagen, descripción, precio y categoría

#### Mis Pedidos
- Pedidos **en curso** (Pendiente, En preparación, Entregado) con indicador de actualización en tiempo real
- **Historial** de pedidos pagados y cancelados con desglose de ítems y total
- Auto-refresh cada 20 segundos cuando hay pedidos activos

#### Mi Perfil
- Nombre, rol y estado de la cuenta

---

## 8. Controladores

| Archivo | Responsabilidad | Acciones principales |
|---|---|---|
| `AuthController.php` | Sesiones y autenticación | `login`, `register`, `logout`, `send_code`, `verify_code`, `update_password` |
| `AdminController.php` | Gestión de usuarios | `add_user`, `delete_user`, `toggle_status`, `update_role` |
| `PedidoController.php` | Ciclo de vida de pedidos | `crear_pedido`, `agregar_item`, `eliminar_item`, `cambiar_estado`, `completar_pedido`, `cambiar_estado_cocina` |
| `PlatoController.php` | Gestión del menú | `add_plato`, `edit_plato`, `update_disponibilidad` |
| `InventarioController.php` | Materia prima y recetas | `add_materia`, `edit_materia`, `update_stock`, `add_receta` |
| `ReporteController.php` | Exportación de informes | `exportar_pdf` (genera HTML imprimible con todos los filtros) |

---

## 9. Modelos

### `Pedido.php` — el modelo más completo

| Método | Descripción |
|---|---|
| `crear()` | Inserta nuevo pedido con estado `Pendiente` |
| `agregarItem()` | Agrega o incrementa un ítem en el pedido |
| `eliminarItemPedido()` | Elimina un ítem específico |
| `cambiarEstado()` | Actualiza el estado del pedido |
| `registrarPago()` | Inserta en tabla `pago` con monto y método |
| `getTodos()` | Todos los pedidos (admin) |
| `getActivos()` | Pedidos no pagados ni cancelados |
| `getTodosByCliente()` | Pedidos de un cliente por `id_cliente` o `id_usuario` |
| `getItemsByPedido()` | Ítems con nombre del plato e imagen |
| `getPedidosParaCocina()` | Pendientes y en preparación (cocinero) |
| `getHistorialCocina()` | Últimos 50 pedidos completados |
| `getReporteVentas()` | Serie temporal con filtros combinados |
| `getResumenVentas()` | KPIs agregados con filtros |
| `getPlatosPreferidos()` | Ranking de platillos más vendidos |
| `getVentasPorMetodoPago()` | Desglose por método desde tabla `pago` |
| `getCategoriasConVentas()` | Categorías para selector de filtro |
| `getPlatosConVentas()` | Platillos para selector de filtro |

### `Usuario.php`

| Método | Descripción |
|---|---|
| `findByEmail()` | Busca usuario con JOIN a rol |
| `create()` | Crea usuario con contraseña hasheada |
| `getAllUsers()` | Todos los usuarios con nombre de rol |
| `updateStatus()` | Activa/desactiva cuenta |
| `updateRole()` | Cambia el rol |
| `saveResetCode()` | Guarda código con expiración 15 min |
| `verifyResetCode()` | Valida código no expirado |
| `getClientes()` | Clientes activos para el selector del mesero |

---

## 10. Vistas

Todas las vistas son archivos PHP que mezclan lógica de presentación con HTML. Cada una:

- Verifica la sesión y el rol al inicio (`session_start()` + validación)
- Carga los modelos necesarios y prepara los datos
- Renderiza el HTML con PHP embebido
- Muestra alertas de `$_SESSION['alert']` con SweetAlert2
- Previene el retroceso del navegador con `window.history.pushState`

Las vistas de `admin` y `mesero` son **SPA (Single Page Application)** implementadas con JavaScript vanilla que muestra/oculta secciones sin recargar la página.

---

## 11. Flujo de un Pedido

```
1. MESERO crea pedido
   └─ Selecciona platos en el carrito flotante
   └─ Opcionalmente asigna un cliente
   └─ Confirma → PedidoController::crearPedido()
   └─ Estado: Pendiente

2. COCINERO ve el pedido (auto-refresh 15s)
   └─ Presiona "Iniciar preparación"
   └─ PedidoController::cambiarEstado() → Estado: En preparación

3. COCINERO termina
   └─ Presiona "Marcar Listo"
   └─ Estado: Entregado

4. MESERO entrega al cliente
   └─ Presiona "Registrar Pago"
   └─ Selecciona método de pago en modal
   └─ PedidoController::cambiarEstado() → Estado: Pagado
   └─ Se inserta registro en tabla `pago`
   └─ Se descuenta inventario de materia prima (si hay receta configurada)

5. CLIENTE ve su pedido
   └─ En "Mis Pedidos" aparece en historial con estado Pagado
   └─ Auto-refresh cada 20s mientras hay pedidos activos
```

---

## 12. Reportes de Ventas

El módulo de reportes (`views/admin/dashboard.php` sección Reportes) permite filtrar por:

- **Período:** Última semana / Mes actual / Año actual
- **Método de pago:** Efectivo, Tarjeta, Transferencia, Nequi, Daviplata (y cualquier otro registrado en BD)
- **Categoría:** Categorías que tienen ventas registradas
- **Platillo:** Platillos específicos con ventas

Los filtros son combinables y se pueden limpiar individualmente con chips.

### Exportación PDF

`ReporteController.php?action=exportar_pdf&periodo=mes&metodo_pago=...`

Genera una página HTML optimizada para impresión con:
- Encabezado con nombre del restaurante, fecha y filtros activos
- KPIs: Total ventas, Pedidos, Clientes, Ticket promedio
- Tabla de ventas por método de pago con barras de participación
- Ranking de platillos más vendidos
- Detalle de ventas por período

---

## 13. Instalación y Configuración

### Requisitos

- Laragon (o XAMPP/WAMP con PHP 8.1+ y MySQL 8.0+)
- PHP 8.1 o superior
- MySQL 8.0 con `only_full_group_by` activo (compatible)

### Pasos

1. **Clonar o copiar** el proyecto en `C:\laragon\www\StockYServe\`

2. **Crear la base de datos** en phpMyAdmin o MySQL CLI:
   ```sql
   CREATE DATABASE bdstockyserve CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
   ```

3. **Importar el esquema:**
   ```
   Importar: sql/bdstockyserve.sql
   ```

4. **Verificar la configuración** en `config/database.php`:
   ```php
   private $host     = "127.0.0.1;port=3320"; // Puerto de Laragon
   private $db_name  = "bdstockyserve";
   private $username = "root";
   private $password = "";
   ```
   > Ajustar `port` según el puerto MySQL de tu instalación (XAMPP usa 3306).

5. **Crear carpeta de uploads** si no existe:
   ```
   StockYServe/uploads/
   ```
   Asegurarse de que tenga permisos de escritura.

6. **Acceder al sistema:**
   ```
   http://localhost/StockYServe/public/index.php
   ```

---

## 14. Credenciales de Prueba

| Rol | Correo | Contraseña |
|---|---|---|
| Administrador | admin@correo.com | (la configurada en BD) |
| Mesero | mesero@correo.com | (la configurada en BD) |
| Cocinero | cocina@correo.com | (la configurada en BD) |
| Cliente | maicol24becerra@gmail.com | (la configurada en BD) |

> Las contraseñas están hasheadas con bcrypt en la BD. Para resetear una contraseña, usar la función de recuperación o actualizar directamente desde el panel de administrador.

---

## 15. Historias de Usuario Implementadas

| ID | Rol | Funcionalidad | Estado |
|---|---|---|---|
| HU-01 | Todos | Login con redirección por rol, bloqueo por intentos | ✅ Completo |
| HU-02 | Administrador | Gestión de usuarios (crear, editar, desactivar, eliminar) | ✅ Completo |
| HU-03 | Administrador | Gestión del menú (platos, categorías, disponibilidad, imágenes) | ✅ Completo |
| HU-04 | Administrador | Reportes de ventas con filtros y exportación PDF | ✅ Completo |
| HU-05 | Administrador | Inventario de materia prima con alertas de stock mínimo | ✅ Completo |
| HU-07 | Administrador | Asignación y cambio de roles | ✅ Completo |
| HU-08 | Administrador | Supervisión de pedidos activos en tiempo real | ✅ Completo |
| HU-09 | Cliente | Registro con creación automática de perfil cliente | ✅ Completo |
| HU-10 | Cliente | Recuperación de contraseña por código de correo | ✅ Completo |
| HU-11 | Cliente | Ver menú digital con imágenes, precios y categorías | ✅ Completo |
| HU-12 | Cliente | Consultar estado del pedido en tiempo real (polling 20s) | ✅ Completo |
| HU-13 | Cliente | Historial completo de pedidos anteriores | ✅ Completo |
| HU-14 | Mesero | Tomar pedido desde menú digital con carrito | ✅ Completo |
| HU-15 | Mesero | Ver pedidos activos con filtros por estado | ✅ Completo |
| HU-16 | Mesero | Generar cuenta con desglose y registrar pago | ✅ Completo |
| HU-17 | Mesero | Agregar/eliminar ítems en pedidos activos | ✅ Completo |
| HU-18 | Cocinero | Ver pedidos pendientes en tiempo real (auto-refresh 15s) | ✅ Completo |
| HU-19 | Cocinero | Cambiar estado del pedido (Pendiente → En preparación → Listo) | ✅ Completo |
| HU-20 | Cocinero | Ver detalle completo con instrucciones especiales | ✅ Completo |
| HU-21 | Cocinero | Priorización por tiempo de espera con alertas visuales | ✅ Completo |

---

## Notas Técnicas Adicionales

### Compatibilidad con MySQL 8 `only_full_group_by`
Todas las consultas con `GROUP BY` usan la misma expresión en el `SELECT` para cumplir con el modo estricto de MySQL 8. El `LIMIT` en consultas preparadas se interpola directamente como entero (no como parámetro `?`) para evitar el error de tipo de dato.

### Asociación de pedidos a clientes
Al crear un pedido, el mesero puede buscar y asignar un cliente desde el carrito. Si no se asigna, `id_cliente` queda `NULL`. El panel del cliente busca pedidos por `id_cliente` o por `id_usuario` del cliente para cubrir ambos casos.

### Descuento automático de inventario
Al marcar un pedido como `Pagado` mediante `completarPedido()`, el sistema descuenta automáticamente los ingredientes de la materia prima según las recetas configuradas para cada plato.

### Seguridad de sesión
- Todas las vistas verifican `$_SESSION['user_id']` y `$_SESSION['user_role']` al inicio
- El retroceso del navegador cierra la sesión automáticamente
- Las contraseñas se almacenan con `PASSWORD_BCRYPT`
- Los formularios usan `htmlspecialchars()` en todas las salidas

---

*Documentación generada el 13/05/2026 · StockYServe v1.0 · Centro Vacacional El Cielo*
