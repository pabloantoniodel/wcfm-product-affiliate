# Sistema de Tracking de Enlaces para Productos Afiliados

## 📋 Descripción

Sistema completo de seguimiento de enlaces compartidos para productos afiliados. Permite a los vendedores compartir productos afiliados con una referencia única y ver estadísticas detalladas de:

- **Cuántas personas** hacen clic en sus enlaces
- **Qué productos** generan más interés  
- **Cuándo** ocurren los clics
- **Tasa de conversión** a ventas

## ✨ Características Implementadas

### 1. **Modificación Automática de Enlaces**

Cuando un vendedor está logueado y tiene productos afiliados:
- ✅ **Todos los enlaces** de productos afiliados incluyen automáticamente su referencia
- ✅ El parámetro `ref_vendor` se añade a la URL
- ✅ Funciona en:
  - Páginas de productos individuales
  - Listados de productos (loops)
  - Tienda del vendedor
  - Catálogo de afiliados

**Ejemplo de URL generada:**
```
https://ciudadvirtual.app/producto/ejemplo/?ref_vendor=123
```

### 2. **Captura de Clics**

Cuando alguien visita un producto usando un enlace con referencia:
- ✅ Se registra el clic en la base de datos
- ✅ Se guarda información del visitante (IP anónima, navegador, origen)
- ✅ Se establece el vendedor como origen para futuras compras
- ✅ Se evitan duplicados (mismo IP + producto en 30 minutos)

### 3. **Tabla de Base de Datos**

Tabla: `wp_wcfm_affiliate_link_clicks`

**Campos:**
- `id` - ID único del clic
- `product_id` - Producto visitado
- `product_owner_id` - Dueño original del producto
- `affiliate_vendor_id` - Vendedor que compartió el enlace
- `visitor_ip` - IP anónima del visitante (GDPR compliant)
- `visitor_user_agent` - Navegador del visitante
- `referrer_url` - De dónde viene el visitante
- `visitor_user_id` - ID de usuario si está logueado (0 si no)
- `converted_to_sale` - Si el clic resultó en venta (0/1)
- `created_at` - Fecha y hora del clic

### 4. **Dashboard de Estadísticas**

Ubicación: **Panel WCFM > Estadísticas de Enlaces**

**Métricas mostradas:**
- 📊 **Clics Totales** - Cantidad total de clics en tus enlaces
- 🎯 **Productos Compartidos** - Cantidad de productos diferentes que compartiste
- 👥 **Visitantes Únicos** - Cantidad de personas diferentes que hicieron clic
- 🛒 **Conversiones a Venta** - Cuántos clics resultaron en ventas
- 📈 **Tasa de Conversión** - Porcentaje de éxito

**Tabla de clics recientes:**
- Fecha y hora del clic
- Producto visitado
- Dueño original del producto
- Origen del visitante
- Estado (Pendiente o Convertido)

**Filtros disponibles:**
- Rango de fechas personalizado
- Filtro por producto específico
- Período predeterminado: últimos 30 días

### 5. **Privacidad (GDPR Compliant)**

- ✅ Las IPs se anonimizan automáticamente
  - IPv4: `192.168.1.123` → `192.168.1.0`
  - IPv6: Últimos 80 bits enmascarados
- ✅ No se almacenan datos personales sin consentimiento
- ✅ Solo estadísticas agregadas

## 🚀 Cómo Usar el Sistema

### Para Vendedores (Afiliados)

#### 1. **Compartir Enlaces**

**Opción A: Automático (Recomendado)**
1. Inicia sesión en tu cuenta
2. Ve a tu catálogo de productos afiliados
3. Copia cualquier enlace de producto (ya incluye tu referencia automáticamente)
4. Comparte en redes sociales, WhatsApp, email, etc.

**Opción B: Manual**
1. Ve al producto que quieres compartir
2. Añade `?ref_vendor=TU_ID` al final de la URL
3. Comparte el enlace

**Ejemplo:**
```
Original: https://ciudadvirtual.app/producto/ejemplo/
Con referencia: https://ciudadvirtual.app/producto/ejemplo/?ref_vendor=123
```

#### 2. **Ver Estadísticas**

1. Ve a **Panel WCFM**
2. Click en **Estadísticas de Enlaces** en el menú
3. Selecciona el rango de fechas
4. Revisa tus métricas y clics recientes

#### 3. **Optimizar tu Estrategia**

- Identifica qué productos generan más clics
- Ve qué canales (redes sociales, email, etc.) funcionan mejor
- Analiza la tasa de conversión
- Enfócate en los productos más exitosos

### Para Dueños de Productos

Los dueños de productos pueden ver:
- **Quién está compartiendo** sus productos
- **Cuántos afiliados** están promoviendo cada producto
- **Rendimiento** de cada afiliado

## 🔧 Aspectos Técnicos

### Archivos Creados/Modificados

**Nuevos archivos:**
1. `/includes/class-wcfm-affiliate-link-tracking.php` - Clase principal de tracking
2. `/frontend/views/link-statistics.php` - Vista de estadísticas
3. `TRACKING-ENLACES-AFILIADOS.md` - Esta documentación

**Archivos modificados:**
1. `wcfm-product-affiliate.php` - Cargar nueva clase y crear tabla
2. `/includes/class-wcfm-affiliate-frontend.php` - Añadir endpoint y menú

### Hooks y Filtros Utilizados

**Para modificar enlaces:**
- `post_link` - Enlaces de posts/productos
- `post_type_link` - Enlaces de tipos de post
- `woocommerce_loop_product_link` - Enlaces en loops de WooCommerce

**Para capturar visitas:**
- `template_redirect` (prioridad 5) - Detectar visitas con ref_vendor

**AJAX:**
- `wp_ajax_get_affiliate_share_link` - Obtener enlace para compartir

### Métodos Principales

**Clase `WCFM_Affiliate_Link_Tracking`:**

```php
// Tracking
track_link_visit()              // Captura clics en enlaces
record_link_click($data)        // Guarda clic en DB

// Modificación de enlaces
add_vendor_ref_to_link($permalink, $post)
add_vendor_ref_to_product_link($link, $product)

// Estadísticas
get_vendor_link_clicks($vendor_id, $args)
get_vendor_link_stats($vendor_id, $date_from, $date_to)
get_product_link_clicks($product_id, $args)

// Conversiones
mark_click_converted($product_id, $affiliate_vendor_id)
```

## 📊 Integración con Sistema de Comisiones

El sistema de tracking se integra automáticamente con el sistema de comisiones:

1. **Clic en enlace** → Se guarda referencia del vendedor
2. **Visitante añade al carrito** → Se asocia vendedor al producto
3. **Compra realizada** → Se marca el clic como convertido
4. **Comisión generada** → Se calcula automáticamente para ambos vendedores

## 🔒 Seguridad

- ✅ Validación de IDs de vendedor
- ✅ Verificación de relación de afiliado antes de guardar
- ✅ Prevención de duplicados
- ✅ IPs anonimizadas
- ✅ Sanitización de datos de entrada
- ✅ Prepared statements en queries SQL

## 📈 Casos de Uso

### Caso 1: Vendedor Promociona en Redes Sociales
```
1. Vendedor comparte en Instagram: ciudadvirtual.app/producto/123?ref_vendor=5
2. 50 personas hacen clic
3. 5 personas compran
4. Dashboard muestra: 50 clics, 5 conversiones, 10% tasa conversión
```

### Caso 2: Email Marketing
```
1. Vendedor envía newsletter con enlaces de productos
2. Tracking registra origen: email
3. Ve qué productos generan más interés
4. Optimiza próximas campañas
```

### Caso 3: Colaboración entre Vendedores
```
1. Vendedor A comparte productos de Vendedor B
2. Vendedor B ve en su dashboard que A está promocionando
3. Pueden ver el rendimiento de la colaboración
4. Ambos reciben comisiones automáticamente
```

## 🐛 Resolución de Problemas

### Los enlaces no incluyen la referencia

**Solución:**
1. Asegúrate de estar logueado
2. Verifica que el producto sea un afiliado tuyo
3. Comprueba que tienes permisos de vendedor

### Las estadísticas no aparecen

**Solución:**
1. Ve a Dashboard > Estadísticas de Enlaces
2. Ajusta el rango de fechas
3. Verifica que has compartido enlaces con tu referencia
4. Comprueba que alguien ha hecho clic en ellos

### Los clics no se registran

**Posibles causas:**
1. El producto no es un afiliado válido
2. Es un clic duplicado (mismo IP en 30 min)
3. El vendedor es el dueño del producto

## 🎯 Próximas Mejoras Posibles

- [ ] Gráficos de evolución temporal
- [ ] Exportar estadísticas a CSV
- [ ] Comparación entre productos
- [ ] Alertas de nuevos clics por email
- [ ] Integración con Google Analytics
- [ ] Códigos QR con referencia
- [ ] Enlaces cortos personalizados
- [ ] Tracking de conversiones por canal

## 💡 Tips para Maximizar Resultados

1. **Comparte regularmente** - Mantén una presencia constante
2. **Usa todos los canales** - Redes sociales, email, WhatsApp, etc.
3. **Analiza y optimiza** - Revisa las estadísticas semanalmente
4. **Enfócate en lo que funciona** - Promociona más los productos exitosos
5. **Prueba diferentes estrategias** - Experimenta con mensajes y formatos

## 📞 Soporte

Para soporte técnico o preguntas:
- Email: francisco@ciudadvirtual.app
- Dashboard: Panel de administración WCFM

---

**Versión del Sistema:** 1.0.0  
**Fecha de Implementación:** Octubre 2025  
**Última Actualización:** Octubre 2025

