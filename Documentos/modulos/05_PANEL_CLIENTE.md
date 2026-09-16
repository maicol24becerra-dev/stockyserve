# Módulo 05: Panel del Cliente (StockYServe)

## 1. Propósito del Módulo
Ofrecer a los clientes del Centro Vacacional "El Cielo" (`id_rol = 4`) un portal interactivo y moderno para consultar la carta digital de platillos y bebidas, realizar seguimiento en tiempo real al estado de su orden en mesa, consultar su historial de consumo y radicar quejas, reclamos o sugerencias respecto al servicio.

---

## 2. Archivos Involucrados

### En PHP:
- **Vista Principal:** `views/cliente/dashboard.php`
- **Controladores:**
  - `controllers/PlatoController.php` (listar platos activos y categorías)
  - `controllers/PedidoController.php` (obtener pedidos asociados a `$_SESSION['usuario']['id_cliente']`)
  - `controllers/UsuarioController.php` (perfil y radicación de quejas)
- **Modelos:**
  - `models/Plato.php`
  - `models/Pedido.php`
  - `models/Usuario.php`

### En React:
- **Páginas:**
  - `frontend/src/pages/ClienteDashboard.jsx` (Menú interactivo, estado de pedido activo)
  - `frontend/src/pages/LandingPage.jsx` (Página pública informativa para clientes)

---

## 3. Funcionalidades Principales

### 3.1 Carta / Menú Digital
- Visualización de platos con imagen, precio en pesos colombianos (COP), categoría y descripción de ingredientes.
- Filtro rápido por categorías: Entradas, Carnes, Pescados, Bebidas, Postres.
- Solo se muestran los platos con `disponibilidad = 1`.

### 3.2 Seguimiento de Orden en Mesa
- El cliente puede ver el estado actual de su orden mediante una barra de progreso visual:
  $$\text{Recibido} \longrightarrow \text{En Preparación} \longrightarrow \text{En Camino a tu Mesa} \longrightarrow \text{Finalizado}$$
- Muestra el desglose de productos que pidió y el total estimado a cancelar.

### 3.3 Módulo de Quejas y Sugerencias (PQRS)
- Formulario sencillo con motivo, tipo (Queja, Sugerencia, Felicitación) y comentario detallado.
- Llega directamente a la bandeja del Administrador para auditoría de calidad.

---

## 4. Consultas Clave del Cliente

1. **Obtener Carta de Platos Disponibles:**
   ```sql
   SELECT id_plato, nombre, descripcion, precio, categoria, imagen 
   FROM plato 
   WHERE disponibilidad = 1 
   ORDER BY categoria ASC, nombre ASC;
   ```
2. **Consultar Pedidos Propios del Cliente:**
   ```sql
   SELECT pe.id_pedido, pe.fecha, pe.estado, pe.mesa, SUM(ip.cantidad * ip.precio_unitario) AS total
   FROM pedido pe
   INNER JOIN cliente c ON pe.id_cliente = c.id_cliente
   INNER JOIN item_pedido ip ON pe.id_pedido = ip.id_pedido
   WHERE c.id_usuario = ?
   GROUP BY pe.id_pedido
   ORDER BY pe.fecha DESC;
   ```

---

## 5. Trampas y Errores Típicos en Evaluaciones

| Si el evaluador cambia... | ¿Qué error produce? | ¿Cómo explicar qué pasó y para qué sirve? |
|---|---|---|
| Omite `WHERE c.id_usuario = ?` en la consulta de pedidos del cliente | El cliente verá los pedidos y consumos de **todos los demás clientes** del restaurante (Fuga masiva de privacidad). | *"El filtro `WHERE c.id_usuario = ?` asegura el aislamiento de datos por usuario (tenancy). Sin él, cualquier cliente podría espiar las facturas y mesas de otros comensales."* |
| Quita `WHERE disponibilidad = 1` en el catálogo del menú | Se mostrarán platos que están agotados o fuera de temporada, permitiendo que la gente intente pedirlos. | *"El campo `disponibilidad` actúa como bandera lógica para ocultar platillos sin insumos sin necesidad de borrarlos de la base de datos."* |
| Quita la verificación de sesión en la vista de cliente | Cualquier persona no registrada puede acceder a la URL directamente. | *"Se requiere comprobar que `$_SESSION['usuario']` exista y tenga `id_rol == 4` para restringir el acceso a usuarios autenticados."* |
