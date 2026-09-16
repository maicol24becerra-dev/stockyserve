# Módulo 02: Panel del Administrador (StockYServe)

## 1. Propósito del Módulo
Proporcionar al rol de Administrador (`id_rol = 1`) el control total y centralizado del negocio: supervisión de ventas en tiempo real, gestión de empleados y roles, administración del menú y catálogo de platillos, control de insumos y materia prima, auditoría de pedidos globales y generación de reportes analíticos con exportación a PDF.

---

## 2. Archivos Involucrados

### En PHP:
- **Vista Principal:** `views/admin/dashboard.php` (interfaz tipo SPA con pestañas dinámicas por sidebar)
- **Controladores:**
  - `controllers/AdminController.php` (acciones de usuarios, roles y estados)
  - `controllers/PlatoController.php` (crear, editar, eliminar y cambiar disponibilidad de platos)
  - `controllers/InventarioController.php` (materia prima, recetas y entradas de stock)
  - `controllers/ReporteController.php` (generación de informes y exportación a PDF)
  - `controllers/PedidoController.php` (supervisión global de pedidos)
- **Modelos:**
  - `models/Usuario.php`
  - `models/Plato.php`
  - `models/MateriaPrima.php`
  - `models/Receta.php`
  - `models/Pedido.php`

### En React:
- **Vistas/Páginas:**
  - `frontend/src/pages/AdminDashboard.jsx` (Estadísticas y KPIs)
  - `frontend/src/pages/UsuariosAdmin.jsx` (Gestión de usuarios y roles)
  - `frontend/src/pages/MenuAdmin.jsx` (Platillos y precios)
  - `frontend/src/pages/InventarioAdmin.jsx` (Insumos y stock)
  - `frontend/src/pages/PedidosAdmin.jsx` (Auditoría de todos los pedidos)
  - `frontend/src/pages/ReportesAdmin.jsx` (Gráficas y métricas financieras)
  - `frontend/src/pages/QuejasAdmin.jsx` (Atención a quejas de clientes)

---

## 3. Pestañas y Funcionalidades Principales

### 3.1 Estadísticas y KPIs
- **Métricas:** Total de ingresos del mes, cantidad de pedidos procesados, promedio de ticket de venta y clientes atendidos.
- **Top 5 Platos:** Consulta agregada (`COUNT(*)`, `SUM(monto)`) ordenada descendentemente para ver qué platos generan mayor rentabilidad.

### 3.2 Gestión de Usuarios
- **Listar Usuarios:** Muestra nombre, correo, rol actual y estado (Activo/Inactivo).
- **Crear Empleado:** Inserta en la tabla `usuario` asignando rol Mesero (2) o Cocinero (3).
- **Cambiar Rol / Estado:** Modifica `id_rol` o el campo `estado = 'Inactivo'` (soft delete o bloqueo temporal).

### 3.3 Reportes de Ventas
- **Filtros Dinámicos:** Por rango de fechas (hoy, semana, mes, año), método de pago (Efectivo, Tarjeta, Transferencia, Nequi, Daviplata), categoría y platillo.
- **Gráficas (Chart.js):**
  - Evolución de ingresos en línea de tiempo.
  - Platos más vendidos (gráfico de barras).
  - Distribución por método de pago (gráfico de dona/pie).
- **Exportación:** Generación de reporte imprimible / PDF con resumen consolidado.

---

## 4. Flujo de Datos y Consultas Clave

1. **Obtención de Ventas Totales:**
   ```sql
   SELECT SUM(p.monto_pagado) AS total_ingresos, COUNT(DISTINCT pe.id_pedido) AS total_pedidos
   FROM pedido pe
   INNER JOIN pago p ON pe.id_pedido = p.id_pedido
   WHERE pe.estado = 'Pagado' AND MONTH(pe.fecha) = MONTH(CURRENT_DATE());
   ```
2. **Top Platillos Más Vendidos:**
   ```sql
   SELECT pl.nombre, SUM(ip.cantidad) AS total_vendidos, SUM(ip.cantidad * ip.precio_unitario) AS total_dinero
   FROM item_pedido ip
   INNER JOIN plato pl ON ip.id_plato = pl.id_plato
   INNER JOIN pedido pe ON ip.id_pedido = pe.id_pedido
   WHERE pe.estado = 'Pagado'
   GROUP BY pl.id_plato, pl.nombre
   ORDER BY total_vendidos DESC
   LIMIT 5;
   ```

---

## 5. Trampas y Errores Típicos en Evaluaciones

| Si el evaluador cambia... | ¿Qué error produce? | ¿Cómo explicar qué pasó y para qué sirve? |
|---|---|---|
| Cambia `INNER JOIN pago p` por `LEFT JOIN` sin validar estado `Pagado` | Suma pedidos cancelados o pendientes arrojando totales de ventas falsos o valores `NULL`. | *"El `INNER JOIN` con la tabla `pago` y el filtro `estado = 'Pagado'` garantiza que solo se contabilice dinero real efectivamente recaudado en caja."* |
| Cambia el agrupamiento `GROUP BY pl.id_plato` por `GROUP BY pl.categoria` | La tabla del top de platillos no mostrará platos individuales sino totales por categoría. | *"La cláusula `GROUP BY` define el nivel de agregación de la consulta SQL; debe agrupar por el identificador del plato para calcular cantidades por ítem específico."* |
| Elimina la validación de sesión de Admin en el controlador (ej. `$usuario['id_rol'] != 1`) | Cualquier usuario (incluso un cliente o mesero) podría entrar a las URLs de administración tecleando la ruta. | *"El middleware o validación de rol (`id_rol == 1`) protege la ruta en el servidor. Si se quita, se genera una vulnerabilidad grave de Control de Acceso Roto (Broken Access Control)."* |
| Cambia la condición `UPDATE usuario SET estado = ? WHERE id_usuario = ?` quitando el `WHERE` | Desactiva o cambia el estado a **TODOS** los usuarios del sistema a la vez. | *"El `WHERE` en una sentencia `UPDATE` o `DELETE` restringe el impacto al registro específico. Si se omite, la modificación se propaga a toda la tabla."* |
