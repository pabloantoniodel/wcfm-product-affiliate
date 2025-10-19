# WCFM Product Affiliate

**Version:** 1.0.0  
**Requires:** WordPress 5.0+, WooCommerce 3.0+, WCFM 6.0+, WCFM Marketplace 3.0+  
**Author:** CiudadVirtual

## 📋 Descripción

Sistema de afiliación de productos para WCFM Marketplace que permite a los vendedores vender productos de otros vendedores **sin clonarlos**, con sistema de tracking de origen y comisiones duales.

### ✨ Características Principales

- ✅ **Sin clonación**: Los productos NO se duplican
- ✅ **Tracking de origen**: Rastrea desde qué tienda se vendió cada producto
- ✅ **Comisiones duales**: Automáticas para dueño del producto y afiliado
- ✅ **Integración total**: Con WCFM Marketplace y WooCommerce
- ✅ **Dashboard completo**: Para vendedores y administradores
- ✅ **Estadísticas**: Reportes de ventas y comisiones
- ✅ **Fácil de usar**: Botones simples para añadir/quitar productos

---

## 🚀 Instalación

### 1. Subir el plugin

Copia la carpeta `wcfm-product-affiliate` a `/wp-content/plugins/`

### 2. Activar el plugin

Ve a **Plugins > Plugins Instalados** y activa **WCFM Product Affiliate**

### 3. Configuración automática

El plugin creará automáticamente:
- Tablas en la base de datos
- Opciones por defecto
- Endpoints en WCFM

---

## ⚙️ Configuración

### Configuración básica

Ve a **WP Admin > WCFM > Settings > Marketplace > Product Affiliate Settings**

#### Opciones disponibles:

1. **Enable Affiliate System** (Activar/Desactivar sistema)
2. **Default Affiliate Commission (%)** (Comisión por defecto para afiliados)
3. **Disable Product Clone** (Desactivar función de clonación original)

### Comisiones recomendadas:

- **Comisión afiliado**: 20-30% del total de venta
- **Comisión dueño**: 70-80% del total de venta

---

## 📖 Cómo Funciona

### Para Vendedores

#### 1. Añadir Productos Afiliados

**Opción A: Desde la página del producto**
1. Visita cualquier producto de otro vendedor
2. Haz clic en el botón **"Sell This Product"**
3. El producto se añadirá a tu catálogo de afiliados

**Opción B: Desde el dashboard**
1. Ve a **WCFM > Affiliate Products**
2. Navega por los productos disponibles
3. Marca los productos que quieras vender
4. Haz clic en **"Add Selected Products"**

#### 2. Gestionar Productos Afiliados

En **WCFM > Affiliate Products** puedes:
- Ver tus productos afiliados activos
- Ver estadísticas de ventas
- Ver tus ganancias por afiliación
- Eliminar productos afiliados

#### 3. Vender Productos

Cuando un cliente:
1. Visita tu tienda
2. Ve el producto afiliado
3. Lo añade al carrito
4. Completa la compra

**Resultado:**
- El pedido va al vendedor original (dueño del producto)
- Se registra que la venta vino de tu tienda
- Recibes tu comisión de afiliación automáticamente

### Para Clientes

Los clientes:
- Ven los productos normalmente
- No notan diferencia
- Compran como siempre
- El pedido lo gestiona el dueño del producto

---

## 💰 Sistema de Comisiones

### Cómo se Calculan

Ejemplo con producto de 100€ y comisión afiliado del 20%:

| Concepto | Monto |
|----------|-------|
| Precio del producto | 100€ |
| Comisión afiliado (20%) | 20€ |
| Comisión dueño (80%) | 80€ |

### Cuándo se Pagan

Las comisiones se procesan cuando:
1. El pedido cambia a estado **"Completado"** o **"Procesando"**
2. Se integra con el sistema de retiros de WCFM
3. Aparece en el balance del vendedor

---

## 📊 Reportes y Estadísticas

### Para Vendedores

En **WCFM > Affiliate Products** verás:
- Total de productos afiliados activos
- Total de ventas realizadas
- Total de ganancias por afiliación

### Para Administradores

En **WP Admin > WCFM > Affiliate Sales** verás:
- Total de afiliados activos
- Total de ventas por afiliación
- Ganancias de afiliados vs dueños
- Lista de ventas recientes con detalles

---

## 🔧 Tablas de Base de Datos

### `wp_wcfm_product_affiliates`

Almacena las relaciones de afiliación:

```sql
id                  - ID único
vendor_id           - ID del vendedor afiliado
product_id          - ID del producto
product_owner_id    - ID del dueño del producto
commission_rate     - Tasa de comisión (%)
status              - Estado (active/inactive)
created_at          - Fecha de creación
```

### `wp_wcfm_affiliate_sales`

Almacena el tracking de ventas:

```sql
id                     - ID único
order_id               - ID del pedido
product_id             - ID del producto
affiliate_vendor_id    - ID del vendedor afiliado
product_owner_id       - ID del dueño
affiliate_commission   - Comisión para afiliado
owner_commission       - Comisión para dueño
commission_rate        - Tasa aplicada
order_status           - Estado del pedido
commission_status      - Estado de comisión
store_origin           - Tienda de origen
created_at             - Fecha de venta
```

---

## 🎨 Personalización

### Hooks y Filtros Disponibles

```php
// Modificar comisión por defecto
add_filter('wcfm_affiliate_default_commission', function($rate) {
    return 25; // 25% para afiliado
});

// Ocultar botón en ciertos productos
add_filter('wcfm_affiliate_show_button', function($show, $product_id) {
    // Tu lógica aquí
    return $show;
}, 10, 2);

// Modificar productos disponibles
add_filter('wcfm_affiliate_available_products', function($args) {
    // Modificar query args
    return $args;
});
```

---

## 🐛 Solución de Problemas

### El botón no aparece en productos

**Solución:**
1. Verifica que eres vendedor (no admin)
2. Verifica que no eres el dueño del producto
3. Verifica que el sistema esté activado en Settings

### Las comisiones no se calculan

**Solución:**
1. Verifica que el tracking esté funcionando (añade producto desde la tienda)
2. Revisa el log de WordPress (`wp-content/debug.log` si WP_DEBUG está activo)
3. Verifica que el pedido esté en estado "completado" o "procesando"

### Los productos no aparecen en el catálogo

**Solución:**
1. Verifica que haya productos de otros vendedores
2. Verifica que no estés ya vendiendo esos productos
3. Limpia la caché si usas plugins de cache

---

## 🔒 Seguridad

El plugin incluye:
- ✅ Nonces en todas las peticiones AJAX
- ✅ Verificación de permisos de usuario
- ✅ Sanitización de datos de entrada
- ✅ Escape de salidas HTML
- ✅ Prepared statements en queries SQL

---

## 📝 Changelog

### Version 1.0.0 (2025-10-19)
- ✨ Lanzamiento inicial
- ✅ Sistema de afiliación sin clonación
- ✅ Tracking de origen de ventas
- ✅ Sistema de comisiones duales
- ✅ Dashboard para vendedores
- ✅ Reportes para administradores
- ✅ Integración completa con WCFM Marketplace

---

## 📧 Soporte

Para soporte técnico o consultas:
- **Email**: soporte@ciudadvirtual.app
- **Documentación**: Ver este README

---

## 📄 Licencia

GPL v2 or later

---

## 🎯 Roadmap Futuro

Ideas para futuras versiones:
- [ ] Comisiones personalizadas por producto
- [ ] Sistema de cupones para afiliados
- [ ] Estadísticas avanzadas con gráficos
- [ ] Export de reportes en CSV/PDF
- [ ] Notificaciones por email de nuevas ventas
- [ ] Widget para mostrar productos afiliados en tienda
- [ ] Integración con programas de lealtad

---

**¡Disfruta del plugin!** 🎉

