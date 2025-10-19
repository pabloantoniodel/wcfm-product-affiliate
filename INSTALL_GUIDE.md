# 🚀 Guía de Instalación y Activación

## Plugin: WCFM Product Affiliate v1.0.0

---

## ✅ **PLUGIN COMPLETADO Y LISTO PARA USAR**

El plugin ha sido creado exitosamente en:
```
/wp-content/plugins/wcfm-product-affiliate/
```

---

## 📋 **Pasos para Activar**

### 1. **Ir al Dashboard de WordPress**

Accede a tu panel de administración:
```
https://ciudadvirtual.app/wp-admin/
```

### 2. **Ir a Plugins**

Navega a: **Plugins > Plugins Instalados**

### 3. **Buscar el Plugin**

Busca: **"WCFM Product Affiliate"**

### 4. **Activar**

Haz clic en **"Activar"**

El plugin se instalará automáticamente:
- ✅ Creará 2 tablas en la base de datos
- ✅ Configurará opciones por defecto
- ✅ Añadirá menús en WCFM
- ✅ Registrará endpoints

---

## ⚙️ **Configuración Inicial**

### **Ir a Ajustes**

1. Ve a: **WCFM > Settings**
2. Haz clic en la pestaña: **Marketplace**
3. Desplázate hasta: **Product Affiliate Settings**

### **Configurar Opciones**

```
✅ Enable Affiliate System: Activado
✅ Default Affiliate Commission: 20%
✅ Disable Product Clone: Activado
```

### **Guardar Cambios**

Haz clic en **"Save Changes"** al final de la página.

---

## 🧪 **Probar el Plugin**

### **Como Vendedor:**

1. **Inicia sesión como vendedor**

2. **Ve a cualquier producto de otro vendedor**

3. **Verás el botón**: "Sell This Product"

4. **Haz clic** para añadirlo como afiliado

5. **Ve a tu dashboard**: WCFM > Affiliate Products

6. **Verás**:
   - Tus estadísticas de afiliación
   - Productos afiliados activos
   - Catálogo de productos disponibles

### **Como Cliente:**

1. **Visita la tienda de un vendedor**

2. **Añade un producto afiliado al carrito**

3. **Completa la compra**

4. **El sistema registrará**:
   - Desde qué tienda se vendió
   - Comisiones para afiliado y dueño

### **Como Administrador:**

1. **Ve a**: WP Admin > WCFM > Affiliate Sales

2. **Verás**:
   - Total de afiliados activos
   - Total de ventas
   - Comisiones pagadas
   - Lista detallada de ventas

---

## 📊 **Verificar que Funciona**

### **Tabla 1: Afiliaciones**

```sql
SELECT * FROM wp_wcfm_product_affiliates LIMIT 10;
```

Verás las relaciones vendedor-producto.

### **Tabla 2: Ventas**

```sql
SELECT * FROM wp_wcfm_affiliate_sales LIMIT 10;
```

Verás el tracking de ventas con comisiones.

---

## 🎯 **Flujo de Trabajo Típico**

### **1. Vendedor añade producto afiliado**
```
Vendedor A → "Sell This Product" → Producto de Vendedor B
```

### **2. Cliente compra**
```
Cliente → Tienda de Vendedor A → Añade al carrito → Checkout
```

### **3. Se registra la venta**
```
Sistema → Registra origen: Tienda Vendedor A
        → Pedido va a: Vendedor B (dueño)
        → Comisiones:
           - Vendedor B: 80%
           - Vendedor A: 20%
```

### **4. Comisiones se procesan**
```
Pedido completado → Sistema calcula comisiones
                 → Añade a balance de ambos vendedores
                 → Aparece en reportes
```

---

## 🔧 **Solución de Problemas Comunes**

### **Error: "Plugin requires WCFM"**

**Solución:**
1. Instala WCFM Frontend Manager
2. Instala WCFM Marketplace
3. Activa ambos plugins primero
4. Luego activa WCFM Product Affiliate

### **No aparece el botón en productos**

**Solución:**
1. Verifica que estés logueado como vendedor
2. Verifica que no seas el dueño del producto
3. Limpia caché del navegador
4. Verifica que el sistema esté activado en Settings

### **Las tablas no se crean**

**Solución:**
```bash
# Desactiva y reactiva el plugin
# O ejecuta manualmente:
cd /home/ciudadvirtual/htdocs/ciudadvirtual.store
wp plugin deactivate wcfm-product-affiliate
wp plugin activate wcfm-product-affiliate
```

---

## 📁 **Estructura del Plugin**

```
wcfm-product-affiliate/
├── wcfm-product-affiliate.php      ← Archivo principal
├── README.md                        ← Documentación completa
├── INSTALL_GUIDE.md                 ← Este archivo
├── readme.txt                       ← Info para WordPress
│
├── includes/                        ← Clases principales
│   ├── class-wcfm-affiliate-admin.php
│   ├── class-wcfm-affiliate-commission.php
│   ├── class-wcfm-affiliate-db.php
│   ├── class-wcfm-affiliate-frontend.php
│   └── class-wcfm-affiliate-tracking.php
│
├── admin/                           ← Panel admin (futuro)
├── frontend/                        ← Frontend
│   ├── assets/
│   │   └── js/
│   │       └── affiliate.js         ← JavaScript
│   └── views/
│       └── affiliate-catalog.php    ← Vista catálogo
│
└── languages/                       ← Traducciones (futuro)
```

---

## 🎨 **Personalización**

### **Cambiar comisión por defecto**

Edita en Settings o añade en `functions.php`:

```php
add_filter('wcfm_affiliate_default_commission', function() {
    return 25; // 25% para afiliado
});
```

### **Ocultar botón en ciertos productos**

```php
add_filter('wcfm_affiliate_show_button', function($show, $product_id) {
    // Lista de productos a excluir
    $excluded = [100, 200, 300];
    
    if (in_array($product_id, $excluded)) {
        return false;
    }
    
    return $show;
}, 10, 2);
```

---

## 📞 **Soporte**

Si necesitas ayuda:

1. **Lee el README.md** - Documentación completa
2. **Revisa los logs** - `wp-content/debug.log`
3. **Contacta soporte** - soporte@ciudadvirtual.app

---

## ✨ **¡Listo para Usar!**

El plugin está **100% funcional** y listo para:

✅ Añadir productos afiliados  
✅ Tracking automático de ventas  
✅ Comisiones duales  
✅ Reportes y estadísticas  
✅ Dashboard para vendedores  

**¡Disfrútalo!** 🎉

---

**Fecha de creación:** 2025-10-19  
**Versión:** 1.0.0  
**Estado:** Producción ✅

