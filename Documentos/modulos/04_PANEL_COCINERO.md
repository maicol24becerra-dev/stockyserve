# Módulo 04: Panel del Cocinero (StockYServe)

## 1. Propósito del Módulo
Brindar al equipo de cocina (`id_rol = 3`) una pantalla de comandas digitales (KDS - *Kitchen Display System*) en tiempo real. Permite visualizar los pedidos entrantes realizados por los meseros, ver los platillos solicitados con sus cantidades y especificaciones, actualizar el estado de preparación (`Pendiente` → `En preparación` → `Listo / Entregado`), y controlar los tiempos de espera mediante un semáforo visual de urgencia para evitar demoras en el servicio.

---

## 2. Archivos Involucrados

### En PHP:
- **Vista Principal:** `views/cocinero/dashboard.php` (interfaz de tarjetas tipo comanda con actualización automática)
- **Controlador:** `controllers/PedidoController.php` (métodos: `obtenerParaCocina()`, `cambiarEstadoCocina()`)
- **Modelos:**
  - `models/Pedido.php` (consultas filtradas por estados de cocina y ordenamiento por tiempo)
  - `models/Plato.php` / `models/Receta.php` (consulta de ingredientes y detalles del plato)
- **Script Frontend:** Auto-refresco vía `setInterval()` o polling cada 15-30 segundos para chequear nuevas órdenes sin recargar la página.

### En React:
- **Páginas:**
  - `frontend/src/pages/CocineroDashboard.jsx` (Muro de comandas activas, semáforo de tiempo, botón de cambio de estado)
  - `frontend/src/pages/CocinerHistorial.jsx` (Historial de comandas despachadas y tiempos cumplidos)

---

## 3. Funcionamiento y Semáforo de Urgencia

### 3.1 Estados en Cocina:
1. **`Pendiente`:** El mesero acaba de enviar la comanda. Aparece destacada con botón *"Empezar a Preparar"*.
2. **`En preparación`:** El cocinero tomó el pedido e inició la cocción. Botón cambia a *"Marcar como Listo / Terminado"*.
3. **`Entregado / Listo`:** La comanda sale de la vista activa de cocina y queda disponible para que el mesero la lleve a la mesa.

### 3.2 Semáforo de Tiempos (Cálculo Dinámico):
El sistema calcula la diferencia entre `NOW()` y la hora de creación del pedido (`fecha`):
$$\text{Minutos transcurridos} = \frac{\text{Tiempo actual} - \text{Fecha del pedido}}{60}$$

| Tiempo | Color del Indicador | Significado |
|---|---|---|
| **Menor a 8 minutos** | 🟢 Verde | Tiempo óptimo / recién ingresado |
| **Entre 8 y 14 minutos** | 🟡 Amarillo | Tiempo estándar / requiere atención |
| **15 minutos o más** | 🔴 Rojo parpadeante | **URGENTE** / Prioridad máxima para evitar quejas |

---

## 4. Consulta SQL y Lógica Clave de Cocina

```sql
SELECT 
    pe.id_pedido,
    pe.fecha,
    pe.estado,
    pe.mesa,
    TIMESTAMPDIFF(MINUTE, pe.fecha, NOW()) AS minutos_transcurridos,
    u.nombre AS nombre_mesero,
    GROUP_CONCAT(CONCAT(ip.cantidad, 'x ', pl.nombre) SEPARATOR ' || ') AS platillos
FROM pedido pe
INNER JOIN usuario u ON pe.id_usuario = u.id_usuario
INNER JOIN item_pedido ip ON pe.id_pedido = ip.id_pedido
INNER JOIN plato pl ON ip.id_plato = pl.id_plato
WHERE pe.estado IN ('Pendiente', 'En preparación')
GROUP BY pe.id_pedido
ORDER BY minutos_transcurridos DESC;
```

> **Nota:** El `ORDER BY minutos_transcurridos DESC` asegura que las órdenes con más tiempo de espera aparezcan en la primera posición del tablero del cocinero.

---

## 5. Trampas y Errores Típicos en Evaluaciones

| Si el evaluador cambia... | ¿Qué error produce? | ¿Cómo explicar qué pasó y para qué sirve? |
|---|---|---|
| Cambia `ORDER BY minutos_transcurridos DESC` por `ASC` | Las órdenes más antiguas se van al final de la pantalla y la cocina atiende primero a los clientes que acaban de llegar, provocando retrasos graves. | *"El ordenamiento descendente por tiempo transcurrido garantiza el principio FIFO (First In, First Out), priorizando las mesas que llevan más tiempo esperando."* |
| Quita `GROUP_CONCAT(...)` y `GROUP BY pe.id_pedido` | Se duplican las tarjetas de pedidos: si una mesa pidió 3 platos, la pantalla mostrará 3 tarjetas del mismo pedido en vez de una tarjeta agrupada. | *"`GROUP_CONCAT` y `GROUP BY` permiten consolidar todos los ítems de un mismo pedido en una sola tarjeta de comanda para que el chef vea todo el pedido en conjunto."* |
| Cambia la condición `WHERE pe.estado IN ('Pendiente', 'En preparación')` por `WHERE pe.estado = 'Pagado'` | El tablero de cocina quedará en blanco o mostrará pedidos viejos que ya se cobraron, en lugar de los que faltan por cocinar. | *"La cocina solo debe procesar órdenes activas en preparación. Filtrar por estados pasados rompe el flujo operativo del restaurante."* |
| Desactiva la función de refresco periódico (`setInterval` o `useEffect`) | Si un mesero toma un pedido, el cocinero nunca se enterará a menos que recargue manualmente con F5. | *"El polling o recarga periódica simula comunicación en tiempo real para alertar de inmediato a la cocina cuando ingresa una nueva comanda."* |
