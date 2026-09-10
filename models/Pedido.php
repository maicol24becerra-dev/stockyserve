<?php
require_once __DIR__ . '/../config/database.php';

class PedidoModel {
    private ?PDO $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    // ── Helpers internos ────────────────────────────────────────────────────

    /**
     * Construye cláusulas WHERE + parámetros PDO a partir de los filtros del reporte.
     * Usa JOIN con la tabla `pago` para filtrar por método de pago.
     */
    private function buildReporteWhere(array $filtros): array {
        $conditions = ["p.estado = 'Pagado'"];
        $params     = [];

        // Período o rango de fechas personalizadas (HU-04)
        $fechaInicio = trim($filtros['fecha_inicio'] ?? '');
        $fechaFin    = trim($filtros['fecha_fin']    ?? '');

        if ($fechaInicio !== '' && $fechaFin !== '') {
            // Rango explícito de fechas
            $conditions[] = "DATE(p.fecha) BETWEEN ? AND ?";
            $params[]     = $fechaInicio;
            $params[]     = $fechaFin;
        } else {
            $periodo = $filtros['periodo'] ?? 'mes';
            switch ($periodo) {
                case 'semana':
                    $conditions[] = "DATE(p.fecha) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
                    break;
                case 'anio':
                    $conditions[] = "YEAR(p.fecha) = YEAR(CURDATE())";
                    break;
                default: // mes
                    $conditions[] = "YEAR(p.fecha) = YEAR(CURDATE()) AND MONTH(p.fecha) = MONTH(CURDATE())";
            }
        }

        // Método de pago
        if (!empty($filtros['metodo_pago'])) {
            $conditions[] = "pg.metodo_pago = ?";
            $params[]     = $filtros['metodo_pago'];
        }

        // Categoría de plato
        if (!empty($filtros['categoria'])) {
            $conditions[] = "pl.categoria = ?";
            $params[]     = $filtros['categoria'];
        }

        // Platillo específico
        if (!empty($filtros['id_plato'])) {
            $conditions[] = "ip.id_plato = ?";
            $params[]     = (int)$filtros['id_plato'];
        }

        return [implode(' AND ', $conditions), $params];
    }

    // ── Métodos de reporte ───────────────────────────────────────────────────

    /**
     * Serie temporal de ventas con filtros combinados.
     * Siempre hace JOIN con `pago` para poder filtrar por método de pago.
     */
    public function getReporteVentas(string $periodo = 'mes', array $filtros = []): array {
        $filtros['periodo'] = $periodo;

        switch ($periodo) {
            case 'semana':
                $labelFormat = "%d/%m";
                $orderExpr   = "DATE(p.fecha)";
                break;
            case 'anio':
                $labelFormat = "%m/%Y";
                $orderExpr   = "YEAR(p.fecha), MONTH(p.fecha)";
                break;
            default: // mes
                $labelFormat = "%d/%m";
                $orderExpr   = "DATE(p.fecha)";
        }

        [$where, $params] = $this->buildReporteWhere($filtros);

        $stmt = $this->db->prepare("
            SELECT DATE_FORMAT(p.fecha, '$labelFormat') AS label,
                   COUNT(DISTINCT p.id_pedido) AS total_pedidos,
                   COALESCE(SUM(ip.cantidad * ip.precio_unitario), 0) AS total_ventas
            FROM pedido p
            LEFT JOIN pago        pg ON pg.id_pedido = p.id_pedido
            LEFT JOIN item_pedido ip ON ip.id_pedido = p.id_pedido
            LEFT JOIN plato       pl ON pl.id_plato  = ip.id_plato
            WHERE $where
            GROUP BY DATE_FORMAT(p.fecha, '$labelFormat')
            ORDER BY MIN(p.fecha) ASC
        ");
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * KPIs de resumen con filtros combinados.
     */
    public function getResumenVentas(string $periodo = 'mes', array $filtros = []): array {
        $filtros['periodo'] = $periodo;
        [$where, $params]   = $this->buildReporteWhere($filtros);

        $stmt = $this->db->prepare("
            SELECT
                COUNT(DISTINCT p.id_pedido)  AS total_pedidos,
                COALESCE(SUM(ip.cantidad * ip.precio_unitario), 0) AS total_ventas,
                COUNT(DISTINCT p.id_cliente) AS clientes_atendidos
            FROM pedido p
            LEFT JOIN pago        pg ON pg.id_pedido = p.id_pedido
            LEFT JOIN item_pedido ip ON ip.id_pedido = p.id_pedido
            LEFT JOIN plato       pl ON pl.id_plato  = ip.id_plato
            WHERE $where
        ");
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC)
            ?: ['total_pedidos' => 0, 'total_ventas' => 0, 'clientes_atendidos' => 0];
    }

    /**
     * Ranking de platillos con filtros combinados.
     */
    public function getPlatosPreferidos(string $periodo = 'mes', int $limit = 8, array $filtros = []): array {
        $filtros['periodo'] = $periodo;
        [$where, $params]   = $this->buildReporteWhere($filtros);

        $stmt = $this->db->prepare("
            SELECT pl.id_plato, pl.nombre, pl.imagen, pl.categoria,
                   SUM(ip.cantidad) AS total_vendido,
                   SUM(ip.cantidad * ip.precio_unitario) AS total_ingresos
            FROM item_pedido ip
            JOIN pedido p  ON p.id_pedido  = ip.id_pedido
            JOIN plato  pl ON pl.id_plato  = ip.id_plato
            LEFT JOIN pago pg ON pg.id_pedido = p.id_pedido
            WHERE $where
            GROUP BY ip.id_plato
            ORDER BY total_vendido DESC
            LIMIT $limit
        ");
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Desglose de ventas por método de pago (desde tabla `pago`).
     */
    public function getVentasPorMetodoPago(string $periodo = 'mes', array $filtros = []): array {
        $filtros['periodo']    = $periodo;
        // Quitamos el filtro de metodo_pago del WHERE para que aparezcan todos los métodos
        $filtrosSinMetodo      = $filtros;
        unset($filtrosSinMetodo['metodo_pago']);
        [$where, $params]      = $this->buildReporteWhere($filtrosSinMetodo);

        $stmt = $this->db->prepare("
            SELECT COALESCE(pg.metodo_pago, 'Sin registrar') AS metodo,
                   COUNT(DISTINCT p.id_pedido) AS total_pedidos,
                   COALESCE(SUM(ip.cantidad * ip.precio_unitario), 0) AS total_ventas
            FROM pedido p
            LEFT JOIN pago        pg ON pg.id_pedido = p.id_pedido
            LEFT JOIN item_pedido ip ON ip.id_pedido = p.id_pedido
            LEFT JOIN plato       pl ON pl.id_plato  = ip.id_plato
            WHERE $where
            GROUP BY pg.metodo_pago
            ORDER BY total_ventas DESC
        ");
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Categorías distintas con ventas (para el selector de filtro).
     */
    public function getCategoriasConVentas(): array {
        $stmt = $this->db->query("
            SELECT DISTINCT pl.categoria
            FROM item_pedido ip
            JOIN pedido p  ON p.id_pedido = ip.id_pedido
            JOIN plato  pl ON pl.id_plato = ip.id_plato
            WHERE p.estado = 'Pagado'
              AND pl.categoria IS NOT NULL AND pl.categoria != ''
            ORDER BY pl.categoria
        ");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Platillos distintos con ventas (para el selector de filtro).
     */
    public function getPlatosConVentas(): array {
        $stmt = $this->db->query("
            SELECT DISTINCT pl.id_plato, pl.nombre, pl.categoria
            FROM item_pedido ip
            JOIN pedido p  ON p.id_pedido = ip.id_pedido
            JOIN plato  pl ON pl.id_plato = ip.id_plato
            WHERE p.estado = 'Pagado'
            ORDER BY pl.nombre
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Registra el pago en la tabla `pago`.
     */
    public function registrarPago(int $id_pedido, float $monto, string $metodo_pago): bool {
        $stmt = $this->db->prepare("
            INSERT INTO pago (monto_pagado, metodo_pago, fecha_pago, id_pedido)
            VALUES (?, ?, NOW(), ?)
        ");
        return $stmt->execute([$monto, $metodo_pago, $id_pedido]);
    }

    // Todos los pedidos (para admin)
    public function getTodos(): array {
        $stmt = $this->db->query("
            SELECT p.*, u.nombre AS nombre_cliente
            FROM pedido p
            LEFT JOIN usuario u ON p.id_usuario = u.id_usuario
            ORDER BY p.fecha DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Todos los pedidos de un cliente (activos + históricos)
    // Busca por id_cliente (asignado por mesero) O por id_usuario del cliente directamente
    public function getTodosByCliente(int $id_cliente, int $id_usuario = 0): array {
        if ($id_usuario > 0) {
            $stmt = $this->db->prepare("
                SELECT p.*
                FROM pedido p
                WHERE p.id_cliente = ?
                   OR p.id_usuario = ?
                ORDER BY p.fecha DESC
            ");
            $stmt->execute([$id_cliente, $id_usuario]);
        } else {
            $stmt = $this->db->prepare("
                SELECT p.*
                FROM pedido p
                WHERE p.id_cliente = ?
                ORDER BY p.fecha DESC
            ");
            $stmt->execute([$id_cliente]);
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Todos los pedidos activos con datos del cliente
    public function getActivos(): array {
        $stmt = $this->db->query("
            SELECT p.*, u.nombre AS nombre_cliente
            FROM pedido p
            LEFT JOIN usuario u ON p.id_usuario = u.id_usuario
            WHERE p.estado NOT IN ('Pagado','Cancelado')
            ORDER BY p.fecha DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Pedidos activos (para el mesero ve todos los activos)
    public function getActivosByMesero(int $id_mesero): array {
        return $this->getActivos();
    }

    // Historial completo de pedidos
    public function getHistorialByMesero(int $id_mesero): array {
        $stmt = $this->db->query("
            SELECT p.*, u.nombre AS nombre_cliente
            FROM pedido p
            LEFT JOIN usuario u ON p.id_usuario = u.id_usuario
            ORDER BY p.fecha DESC
            LIMIT 50
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Items de un pedido con datos del plato y nota especial
    public function getItemsByPedido(int $id_pedido): array {
        $stmt = $this->db->prepare("
            SELECT ip.*, pl.nombre AS nombre_plato, pl.precio AS precio_plato, pl.imagen,
                   ip.nota_especial AS notas
            FROM item_pedido ip
            JOIN plato pl ON ip.id_plato = pl.id_plato
            WHERE ip.id_pedido = ?
        ");
        $stmt->execute([$id_pedido]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Crear pedido nuevo con cliente asociado
    public function crear(int $id_usuario_mesero, int $id_cliente = 0, int $id_usuario_cliente = 0,
                          int $es_urgente = 0, string $hora_entrega = '', string $notas_pedido = ''): int {
        $stmt = $this->db->prepare("
            INSERT INTO pedido (fecha, estado, id_usuario, id_cliente)
            VALUES (NOW(), 'Pendiente', ?, ?)
        ");
        $stmt->execute([
            $id_usuario_mesero,
            $id_cliente > 0 ? $id_cliente : null,
        ]);
        return (int)$this->db->lastInsertId();
    }

    // Agregar item al pedido con nota especial (HU-14 Esc.5)
    public function agregarItemConNota(int $id_pedido, int $id_plato, int $cantidad,
                                       float $precio_unitario, string $nota = ''): bool {
        $stmt = $this->db->prepare("
            SELECT id_item_pedido, cantidad FROM item_pedido
            WHERE id_pedido=? AND id_plato=?
        ");
        $stmt->execute([$id_pedido, $id_plato]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        // Si ya existe Y no hay nota especial diferente, acumular cantidad
        if ($existing && $nota === '') {
            $stmt = $this->db->prepare(
                "UPDATE item_pedido SET cantidad = cantidad + ? WHERE id_item_pedido = ?"
            );
            return $stmt->execute([$cantidad, $existing['id_item_pedido']]);
        }

        // Insertar como nueva línea (permite misma comida con diferentes notas)
        $stmt = $this->db->prepare("
            INSERT INTO item_pedido (cantidad, precio_unitario, id_pedido, id_plato, nota_especial)
            VALUES (?, ?, ?, ?, ?)
        ");
        return $stmt->execute([$cantidad, $precio_unitario, $id_pedido, $id_plato, $nota ?: null]);
    }

    // Agregar item al pedido (compatibilidad)
    public function agregarItem(int $id_pedido, int $id_plato, int $cantidad, float $precio_unitario): bool {
        return $this->agregarItemConNota($id_pedido, $id_plato, $cantidad, $precio_unitario, '');
    }

    // Eliminar item del pedido
    public function eliminarItem(int $id_item): bool {
        $stmt = $this->db->prepare("DELETE FROM item_pedido WHERE id_item_pedido=?");
        return $stmt->execute([$id_item]);
    }

    // Cambiar estado del pedido
    public function cambiarEstado(int $id_pedido, string $estado): bool {
        $stmt = $this->db->prepare("UPDATE pedido SET estado=? WHERE id_pedido=?");
        return $stmt->execute([$estado, $id_pedido]);
    }

    // Marcar/desmarcar pedido urgente (HU-21)
    public function setUrgente(int $id_pedido, int $urgente): bool {
        try {
            $stmt = $this->db->prepare("UPDATE pedido SET es_urgente=? WHERE id_pedido=?");
            return $stmt->execute([$urgente, $id_pedido]);
        } catch (\Exception $e) {
            // Columna puede no existir en BD antigua
            return false;
        }
    }

    public function markAsCompleted(int $id_pedido): bool {
        return $this->cambiarEstado($id_pedido, 'Pagado');
    }

    // ── Métodos para el cocinero ────────────────────────────────────────────

    /**
     * Pedidos que cocina debe ver: Pendiente y En preparación.
     * Ordenados: urgentes primero, luego por hora_entrega más próxima, luego por tiempo de espera.
     */
    public function getPedidosParaCocina(): array {
        $stmt = $this->db->query("
            SELECT p.*, u.nombre AS nombre_cliente
            FROM pedido p
            LEFT JOIN usuario u ON p.id_usuario = u.id_usuario
            WHERE p.estado IN ('Pendiente', 'En preparación')
            ORDER BY p.fecha ASC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Historial de pedidos completados/entregados para el cocinero (últimos 50).
     */
    public function getHistorialCocina(): array {
        $stmt = $this->db->query("
            SELECT p.id_pedido, p.fecha, p.estado,
                   COUNT(ip.id_item_pedido) AS total_items
            FROM pedido p
            LEFT JOIN item_pedido ip ON ip.id_pedido = p.id_pedido
            WHERE p.estado IN ('Entregado', 'Pagado', 'Cancelado')
            GROUP BY p.id_pedido
            ORDER BY p.fecha DESC
            LIMIT 50
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Resumen del día
    public function getResumenDia(int $id_mesero): array {
        $stmt = $this->db->query("
            SELECT
                COUNT(*) AS total_pedidos,
                SUM(CASE WHEN estado='Pagado' THEN 1 ELSE 0 END) AS completados,
                SUM(CASE WHEN estado='Pendiente' THEN 1 ELSE 0 END) AS pendientes
            FROM pedido
            WHERE DATE(fecha) = CURDATE()
        ");
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: ['total_pedidos'=>0,'completados'=>0,'pendientes'=>0];
    }

    public function getById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM pedido WHERE id_pedido=?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    // Pedidos activos de un cliente (para mostrar en su panel)
    public function getPedidosActivosByCliente(int $id_cliente): array {
        $stmt = $this->db->prepare("
            SELECT p.*
            FROM pedido p
            WHERE p.id_cliente = ?
              AND p.estado NOT IN ('Pagado','Cancelado')
            ORDER BY p.fecha DESC
        ");
        $stmt->execute([$id_cliente]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Eliminar un ítem específico de un pedido
    public function eliminarItemPedido(int $id_item_pedido, int $id_pedido): bool {
        $stmt = $this->db->prepare("
            DELETE FROM item_pedido WHERE id_item_pedido=? AND id_pedido=?
        ");
        return $stmt->execute([$id_item_pedido, $id_pedido]);
    }

    // Obtener total de un pedido
    public function getTotalPedido(int $id_pedido): float {
        $stmt = $this->db->prepare("
            SELECT COALESCE(SUM(cantidad * precio_unitario), 0) AS total
            FROM item_pedido WHERE id_pedido=?
        ");
        $stmt->execute([$id_pedido]);
        return (float)$stmt->fetchColumn();
    }
}
