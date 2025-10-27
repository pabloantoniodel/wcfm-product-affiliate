# Bloqueador de Productos Afiliados [CLONAR]

## 📋 Descripción

El **Bloqueador de Productos Afiliados** es una funcionalidad opcional que impide el acceso directo a productos afiliados, redirigiendo automáticamente a los visitantes al producto original.

## 🎯 ¿Para qué sirve?

Esta funcionalidad es útil cuando quieres:
- **Evitar duplicados** en los resultados de búsqueda
- **Forzar compras del producto original** en lugar de los afiliados
- **Simplificar la experiencia del cliente** mostrando solo productos únicos
- **Mejorar el SEO** evitando contenido duplicado

## ⚙️ Estado Actual

**🔴 DESACTIVADO** por defecto

## 🚀 Cómo Activar

### Opción 1: Editar el archivo principal

1. Abre el archivo: `/wp-content/plugins/wcfm-product-affiliate/wcfm-product-affiliate.php`

2. Busca esta sección (línea ~156):
```php
// Para activar, descomenta esta línea:
// require_once WCFM_AFFILIATE_PLUGIN_DIR . 'includes/class-wcfm-affiliate-blocker.php';
```

3. Quita el comentario `//` de la línea:
```php
// Para activar, descomenta esta línea:
require_once WCFM_AFFILIATE_PLUGIN_DIR . 'includes/class-wcfm-affiliate-blocker.php';
```

4. Busca la segunda sección (línea ~177):
```php
// if (class_exists('WCFM_Affiliate_Blocker')) {
//     $this->blocker = new WCFM_Affiliate_Blocker();
// }
```

5. Quita los comentarios:
```php
if (class_exists('WCFM_Affiliate_Blocker')) {
    $this->blocker = new WCFM_Affiliate_Blocker();
}
```

6. Guarda el archivo

### Opción 2: Usar Code Snippets (Recomendado)

Si prefieres no editar el archivo directamente, puedes crear un snippet:

```php
// Activar bloqueador de productos afiliados
add_action('plugins_loaded', function() {
    if (class_exists('WCFM_Product_Affiliate')) {
        require_once WP_PLUGIN_DIR . '/wcfm-product-affiliate/includes/class-wcfm-affiliate-blocker.php';
        new WCFM_Affiliate_Blocker();
    }
}, 20);
```

## ❌ Cómo Desactivar

Simplemente vuelve a comentar las líneas que descomentaste (añade `//` al inicio de cada línea).

## 🔧 Qué hace cuando está ACTIVADO

### 1. Redirección Automática (301)
Cuando alguien intenta acceder a un producto afiliado:
```
https://tutienda.com/producto-afiliado/  
    ↓
https://tutienda.com/producto-original/  (Redirección 301)
```

### 2. Oculta de Listados
- Los productos afiliados **NO aparecerán** en:
  - Resultados de búsqueda
  - Listados de categorías
  - Páginas de tienda
  - Widgets de productos

### 3. Solo Muestra Originales
- Solo se mostrarán los productos creados originalmente
- Los afiliados seguirán existiendo en la base de datos pero no serán visibles

## ⚠️ Avisos Importantes

### Aviso en Panel de Administración

Cuando está activo, verás un aviso en la página de plugins:

```
⚠️ WCFM Product Affiliate - Bloqueador Activo:
Los productos afiliados están bloqueados. Los clientes serán redirigidos 
automáticamente a los productos originales.
```

### Logs en Debug

Si tienes `WP_DEBUG` activado, verás logs en `debug.log`:

```
🚫 WCFM Affiliate Blocker: Bloqueando acceso a producto afiliado #123
📍 WCFM Affiliate Blocker: Redirigiendo a producto original #45
```

## 📊 Impacto en el Sistema

### ✅ Ventajas
- Mejor experiencia de usuario (sin duplicados)
- Mejora SEO (evita contenido duplicado)
- Redirecciones 301 (preserva SEO del producto original)
- Comisiones siguen funcionando normalmente

### ⚠️ Consideraciones
- Los vendedores afiliados NO podrán compartir enlaces directos a "sus" productos
- Los clientes SIEMPRE comprarán del vendedor original
- Las comisiones de afiliado se registrarán solo si el cliente llegó mediante un enlace de afiliado válido

## 🛠️ Funciones Auxiliares

El bloqueador incluye métodos estáticos útiles:

### Verificar si un producto es afiliado
```php
if (WCFM_Affiliate_Blocker::is_affiliate_product($product_id)) {
    echo 'Este producto es afiliado';
}
```

### Obtener el ID del producto original
```php
$original_id = WCFM_Affiliate_Blocker::get_original_product_id($product_id);
if ($original_id) {
    echo 'Producto original: #' . $original_id;
}
```

## 📝 Archivos Relacionados

- **Clase principal**: `/includes/class-wcfm-affiliate-blocker.php`
- **Activación**: `/wcfm-product-affiliate.php` (líneas 156 y 177)
- **Documentación**: Este archivo

## 🔍 Casos de Uso

### Caso 1: Marketplace Cerrado
Solo el vendedor original puede vender cada producto. Los afiliados solo reciben comisión si el cliente llega mediante su enlace.

### Caso 2: Evitar Confusión
Los clientes ven un solo producto, no múltiples versiones del mismo producto de diferentes vendedores.

### Caso 3: Control de Calidad
Solo el vendedor original atiende los pedidos, garantizando consistencia en el servicio.

## ⚡ Rendimiento

- **Impacto mínimo**: Solo añade una verificación en páginas de producto
- **Optimizado**: Usa redirecciones 301 (cacheables por navegadores y proxies)
- **Sin consultas extra**: Usa meta queries existentes

## 🆘 Soporte

Si tienes problemas con el bloqueador:

1. Verifica que los productos afiliados tienen el meta `_wcfm_affiliate_original_product_id`
2. Revisa los logs en `debug.log`
3. Comprueba que el producto original existe y está publicado

---

**Versión**: 1.0.0  
**Última actualización**: 2025-10-21  
**Estado**: DESACTIVADO por defecto








