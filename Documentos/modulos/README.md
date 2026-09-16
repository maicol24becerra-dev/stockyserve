# Índice de Módulos Técnicos — StockYServe

Esta carpeta contiene la documentación técnica **modular** de StockYServe, separada en archivos `.md` individuales por cada funcionalidad y módulo del sistema. Cada documento está diseñado con su arquitectura, flujos, archivos participantes y la **sección de preguntas / trampas típicas de evaluación** para sustentar el código.

---

## 📁 Lista de Documentos Modulares

1. 🔐 **[01_LOGIN_Y_AUTENTICACION.md](file:///c:/laragon/www/stockyserve/Documentos/modulos/01_LOGIN_Y_AUTENTICACION.md)**
   - Login, registro de usuarios, logout y recuperación de contraseñas.
   - Hashing con BCRYPT (`password_hash`, `password_verify`), protección de sesiones (`session_regenerate_id`).
   - Redirección por roles (`id_rol`: 1 Admin, 2 Mesero, 3 Cocinero, 4 Cliente).
   - Errores típicos en evaluaciones y cómo defenderlos.

2. 👑 **[02_PANEL_ADMINISTRADOR.md](file:///c:/laragon/www/stockyserve/Documentos/modulos/02_PANEL_ADMINISTRADOR.md)**
   - Dashboard central, métricas y KPIs del restaurante.
   - Gestión de empleados y usuarios (activar, desactivar, cambiar rol).
   - Reportes de ventas con filtros avanzados (período, método de pago, categoría).
   - Gráficas con Chart.js y exportación a PDF.
   - Errores típicos en evaluaciones y cómo defenderlos.

3. 🍽️ **[03_PANEL_MESERO.md](file:///c:/laragon/www/stockyserve/Documentos/modulos/03_PANEL_MESERO.md)**
   - Toma de pedidos con carrito interactivo.
   - Asignación de mesa y cliente comensal.
   - Monitoreo en tiempo real de Pedidos Activos.
   - Módulo de cobro, desglose de cuenta, métodos de pago y Factura POS.
   - Errores típicos en evaluaciones y cómo defenderlos.

4. 👨‍🍳 **[04_PANEL_COCINERO.md](file:///c:/laragon/www/stockyserve/Documentos/modulos/04_PANEL_COCINERO.md)**
   - Pantalla de comandas de cocina (KDS).
   - Transiciones de preparación (`Pendiente` $\to$ `En preparación` $\to$ `Listo / Entregado`).
   - Semáforo visual de urgencia (Verde < 8 min, Amarillo 8-14 min, Rojo $\ge$ 15 min).
   - Algoritmo de ordenamiento de pedidos por tiempo de espera (FIFO).
   - Errores típicos en evaluaciones y cómo defenderlos.

5. 👤 **[05_PANEL_CLIENTE.md](file:///c:/laragon/www/stockyserve/Documentos/modulos/05_PANEL_CLIENTE.md)**
   - Consulta de la carta digital de platos y bebidas con disponibilidad en tiempo real.
   - Barra de seguimiento del pedido en curso para el comensal.
   - Módulo de radicación de quejas y sugerencias (PQRS).
   - Errores típicos en evaluaciones y cómo defenderlos.

6. 🧾 **[06_PEDIDOS_Y_FACTURACION.md](file:///c:/laragon/www/stockyserve/Documentos/modulos/06_PEDIDOS_Y_FACTURACION.md)**
   - Ciclo de vida y máquina de estados del pedido.
   - Tablas relacionales: `pedido`, `item_pedido`, `pago`.
   - Cálculo de subtotales, totales y cambio.
   - Inmutabilidad de precios históricos y formato de Factura POS.
   - Errores típicos en evaluaciones y cómo defenderlos.

7. 📦 **[07_INVENTARIO_PLATOS_RECETAS.md](file:///c:/laragon/www/stockyserve/Documentos/modulos/07_INVENTARIO_PLATOS_RECETAS.md)**
   - Materias primas e insumos físicos en bodega (`materia_prima`).
   - Platos de la carta (`plato`) y tabla puente de composición (`receta`).
   - Algoritmo de descuento automático de insumos al preparar pedidos.
   - Alertas visuales de stock mínimo de seguridad.
   - Errores típicos en evaluaciones y cómo defenderlos.

8. 🗄️ **[08_BASE_DE_DATOS_Y_MODELOS.md](file:///c:/laragon/www/stockyserve/Documentos/modulos/08_BASE_DE_DATOS_Y_MODELOS.md)**
   - Diccionario de datos de las 9 tablas de `bdstockyserve`.
   - Parámetros de conexión PDO en MySQL (puerto 3320, Laragon).
   - Integridad referencial, claves primarias y foráneas (`FOREIGN KEY`).
   - Transacciones ACID (`beginTransaction`, `commit`, `rollBack`).
   - Errores típicos en evaluaciones y cómo defenderlos.

9. 🏗️ **[09_ARQUITECTURA_Y_CONEXION.md](file:///c:/laragon/www/stockyserve/Documentos/modulos/09_ARQUITECTURA_Y_CONEXION.md)**
   - Patrón de diseño Modelo-Vista-Controlador (MVC).
   - Arquitectura desacoplada React + API REST.
   - Ciclo de vida de una petición HTTP (Request/Response).
   - Seguridad: Prepared Statements contra SQL Injection, CORS, cabeceras HTTP.
   - Errores típicos en evaluaciones y cómo defenderlos.
