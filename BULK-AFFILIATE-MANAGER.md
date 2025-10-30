# Bulk Affiliate Manager - Gestor Masivo de Afiliación

## Descripción

El Bulk Affiliate Manager es una herramienta administrativa que permite a los administradores de la tienda gestionar de forma masiva la afiliación de productos a vendedores.

## Características Principales

### 1. **Búsqueda de Productos**
- Buscador avanzado de productos originales (no afiliados)
- Resultados con imagen, nombre, vendedor y precio
- Botón "Añadir" para agregar productos al pool

### 2. **Pool de Productos para Afiliar**
- Tabla visual con todos los productos disponibles para afiliar
- Columnas: Imagen, Producto, Vendedor Original, Precio, Stock
- Checkboxes para selección múltiple
- Checkbox "Seleccionar Todos" en el encabezado

### 3. **Acciones Disponibles**
- **Borrar Seleccionados**: Elimina productos del pool
- **Enviar a Afiliado**: Inicia el proceso de afiliación masiva

### 4. **Modal de Selección de Vendedor**
- Búsqueda de vendedores con filtro en tiempo real
- Listado con: Nombre, Email, Cantidad de Productos
- Paginación integrada (10 vendedores por página)
- Botones "Anterior" y "Siguiente"
- Botón "Cancelar" para cerrar sin cambios

### 5. **Modal de Confirmación**
- Muestra el vendedor seleccionado
- Lista de productos a afiliar con checkboxes
- Permite desmarcar productos que no se quieran afiliar
- Botones "Cancelar" y "Aceptar y Afiliar"

### 6. **Proceso de Afiliación**
- Verifica que cada producto exista
- Comprueba que no exista afiliación previa
- Crea relación en tabla `wp_wcfm_affiliate_products`
- Elimina productos afiliados del pool automáticamente
- Muestra resumen de éxitos y errores

## Ubicación en el Dashboard

**Menú**: WooCommerce > Productos Afiliar

## Flujo de Trabajo

1. **Buscar Productos**
   - Usar el buscador para encontrar productos
   - Añadirlos al pool con el botón "Añadir"

2. **Gestionar Pool**
   - Revisar la lista de productos disponibles
   - Eliminar productos no deseados

3. **Seleccionar Productos**
   - Marcar checkboxes de productos a afiliar
   - O usar "Seleccionar Todos"

4. **Elegir Vendedor**
   - Click en "Enviar a Afiliado"
   - Buscar vendedor en el modal
   - Click en "Seleccionar" del vendedor deseado

5. **Confirmar Afiliación**
   - Revisar productos seleccionados
   - Desmarcar si es necesario
   - Click en "Aceptar y Afiliar"

6. **Resultado**
   - Se muestra mensaje con cantidad afiliada
   - Se listan errores si los hay
   - Página se recarga automáticamente

## Características Técnicas

### AJAX Endpoints

- `wcfm_affiliate_search_products`: Búsqueda de productos
- `wcfm_affiliate_add_to_pool`: Añadir producto al pool
- `wcfm_affiliate_remove_from_pool`: Quitar productos del pool
- `wcfm_affiliate_search_vendors`: Búsqueda de vendedores
- `wcfm_affiliate_bulk_affiliate`: Afiliación masiva

### Almacenamiento

- **Pool de Productos**: Opción `wcfm_affiliate_product_pool` (array de IDs)
- **Afiliaciones**: Tabla `wp_wcfm_affiliate_products`

### Permisos

- Requiere capacidad `manage_woocommerce`
- Solo accesible para administradores

### Seguridad

- Nonce verificado en todas las peticiones AJAX
- Sanitización de inputs
- Verificación de permisos
- Escape de outputs

## Estilos Visuales

- Diseño moderno con gradientes morados (#667eea, #764ba2)
- Modales animados con efecto slide-down
- Tablas responsive
- Botones con estados hover
- Spinners de carga

## Responsive

- Totalmente responsive
- Botones apilados en móvil
- Modales adaptados a pantalla pequeña
- Tablas con scroll horizontal si es necesario

## Integración

El Bulk Manager se integra perfectamente con:
- Sistema de afiliación existente
- Base de datos de afiliaciones
- Roles y permisos de WordPress
- WooCommerce
- WCFM Marketplace

## Changelog

### Versión 1.1.0 (2025-10-30)
- ✨ Nueva funcionalidad: Bulk Affiliate Manager
- 📊 Gestión masiva de productos para afiliar
- 🔍 Búsqueda avanzada de productos y vendedores
- ✅ Selección múltiple con checkboxes
- 🎨 Interfaz moderna con modales flotantes
- 📄 Paginación de resultados
- ⚡ 100% AJAX, sin recargas innecesarias
- 🛡️ Seguridad y validaciones completas

## Soporte

Para soporte técnico o preguntas:
- Email: soporte@ciudadvirtual.app
- Sitio: https://ciudadvirtual.app

