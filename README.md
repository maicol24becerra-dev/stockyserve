# 🍽️ StockYServe — Sistema de Gestión Gastronómica y Hotelera

> **Centro Vacacional y Recreacional El Cielo**  
> Solución integral para la gestión de pedidos, cocina, facturación POS, control de inventarios e informes administrativos.

---

## 📌 Descripción del Proyecto

**StockYServe** es una plataforma web desarrollada para optimizar el ciclo operativo completo de un restaurante y centro recreacional:
- **Meseros:** Toma de pedidos mediante catálogo digital con carrito interactivo, asignación de mesas y control de comandas.
- **Cocina (KDS):** Tablero digital de comandas con semáforo de tiempos de espera para agilizar el despacho de platillos.
- **Facturación POS:** Registro de pagos (Efectivo, Tarjeta, Transferencias, Nequi, Daviplata), cálculo de cambio e impresión de facturas térmicas.
- **Inventario Inteligente:** Descuento automático de materias primas por porciones y recetas técnicas vinculadas a los platos.
- **Administración:** Auditoría integral, métricas clave (KPIs), gráficas interactivas y exportación de reportes a PDF.

---

## 🛠️ Tecnologías Utilizadas

- **Backend:** PHP 8.1 (Arquitectura MVC nativa sin dependencias pesadas) / Node.js & Express
- **Base de Datos:** MySQL 8.0 (Motor InnoDB, Transacciones ACID, Claves Foráneas)
- **Frontend:** HTML5, CSS3 moderno, JavaScript Vanilla / React con Vite
- **Entorno de Desarrollo:** Laragon (Apache + MySQL en puerto 3320)
- **Componentes Gráficos:** Chart.js, SweetAlert2, Lucide Icons, Google Fonts

---

## 📚 Documentación Técnica Detallada

Para consultar la documentación completa por módulo y prepararse para la sustentación del código:

1. [🔐 01. Login y Autenticación](Documentos/modulos/01_LOGIN_Y_AUTENTICACION.md)
2. [👑 02. Panel del Administrador](Documentos/modulos/02_PANEL_ADMINISTRADOR.md)
3. [🍽️ 03. Panel del Mesero](Documentos/modulos/03_PANEL_MESERO.md)
4. [👨‍🍳 04. Panel del Cocinero](Documentos/modulos/04_PANEL_COCINERO.md)
5. [👤 05. Panel del Cliente](Documentos/modulos/05_PANEL_CLIENTE.md)
6. [🧾 06. Pedidos y Facturación POS](Documentos/modulos/06_PEDIDOS_Y_FACTURACION.md)
7. [📦 07. Inventario, Platos y Recetas](Documentos/modulos/07_INVENTARIO_PLATOS_RECETAS.md)
8. [🗄️ 08. Base de Datos y Modelos](Documentos/modulos/08_BASE_DE_DATOS_Y_MODELOS.md)
9. [🏗️ 09. Arquitectura y Conexión](Documentos/modulos/09_ARQUITECTURA_Y_CONEXION.md)
10. [📑 Índice General de Módulos](Documentos/modulos/README.md)

---

## 🚀 Instalación y Puesta en Marcha

1. Clonar el repositorio en la carpeta `www` de Laragon:
   ```bash
   git clone https://github.com/maicol24becerra-dev/stockyserve.git
   ```
2. Importar la base de datos en MySQL:
   - Archivo: `sql/bdstockyserve.sql`
   - Puerto: `3320`
3. Abrir el proyecto en el navegador:
   ```
   http://localhost/stockyserve/public/
   ```

---

## 👥 Roles de Usuario

| Rol | Panel | Responsabilidad |
|---|---|---|
| **Administrador** | `/views/admin/dashboard.php` | Control total, usuarios, inventario y reportes |
| **Mesero** | `/views/mesero/dashboard.php` | Crear pedidos, mesas, cobro y factura POS |
| **Cocinero** | `/views/cocinero/dashboard.php` | Comandas activas, tiempos y avance de platos |
| **Cliente** | `/views/cliente/dashboard.php` | Consulta de carta digital y seguimiento |
