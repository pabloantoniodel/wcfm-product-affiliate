# 🎯 Cómo Funciona el Sistema de Afiliación

## Versión 1.0.0 - Completamente Funcional

---

## 📋 Flujo Completo del Sistema

### **PASO 1: Vendedor Añade Producto Afiliado**

```
VendedorA (afiliado)
  ↓
Dashboard → Productos Afiliados
  ↓
Busca producto de VendedorB
  ↓
Clic en "Añadir"
  ↓
✅ Producto añadido a su catálogo
```

**Base de datos:**
```sql
INSERT INTO wp_wcfm_product_affiliates
(vendor_id=A, product_id=123, product_owner_id=B, commission_rate=1%)
```

---

### **PASO 2: Producto Aparece en Tienda**

```
Cliente visita: ciudadvirtual.app/store/vendedor-a/
                     ↓
Sistema detecta:
- Productos propios de A: [10, 20, 30]
- Productos afiliados de A: [123]
                     ↓
Muestra en tienda: [10, 20, 30, 123]
```

**Características visuales:**
- 🏷️ Badge "Producto Afiliado" en la esquina
- 🔗 URL incluye: `?store_origin=A&ref=vendedor-a`

---

### **PASO 3: Cliente Ve el Producto**

```
Cliente en tienda de VendedorA
  ↓
Ve producto afiliado (ID: 123)
  ↓
Clic en el producto
  ↓
URL: /producto-123/?store_origin=A&ref=vendedor-a
  ↓
Sistema guarda en sesión: store_origin = A
```

**En la página del producto se muestra:**
```
┌────────────────────────────────────────┐
│ 🏪 Visto en la tienda de VendedorA     │
│    Producto de VendedorB               │
└────────────────────────────────────────┘
```

---

### **PASO 4: Cliente Añade al Carrito**

```
Cliente → "Añadir al carrito"
         ↓
Sistema captura:
- store_origin: VendedorA (ID)
- ref: vendedor-a (slug)
         ↓
Guarda en cart_item_data:
{
  "wcfm_affiliate_store": A,
  "wcfm_affiliate_time": 1729123456
}
```

**Tracking guardado en:**
- Sesión de WooCommerce
- Datos del ítem del carrito

---

### **PASO 5: Cliente Completa la Compra**

```
Cliente → Checkout → Pagar
                      ↓
Pedido creado: #456
                      ↓
Sistema procesa:

1. Guarda en pedido:
   _wcfm_affiliate_store_origin = A

2. Guarda en line_item:
   _wcfm_affiliate_store = A
   _affiliate_vendor_id = A
   _affiliate_commission = 0.50€
   _owner_commission = 49.50€

3. Registra en tabla:
   wp_wcfm_affiliate_sales
```

---

### **PASO 6: Sistema Calcula Comisiones**

```
Producto: 50€
Comisión afiliado: 1% = 0.50€
Comisión propietario: 99% = 49.50€

┌──────────────────────────────────────┐
│ Pedido #456                          │
├──────────────────────────────────────┤
│ VendedorB (propietario)              │
│ ✅ Recibe pedido                     │
│ ✅ Gestiona envío                    │
│ ✅ Comisión: 49.50€ (99%)            │
├──────────────────────────────────────┤
│ VendedorA (afiliado)                 │
│ ✅ No gestiona nada                  │
│ ✅ Solo recibe comisión: 0.50€ (1%)  │
└──────────────────────────────────────┘
```

---

## 🔗 URLs de Tracking

### **Ejemplo Real:**

#### **1. En tienda del afiliado:**
```
https://ciudadvirtual.app/store/vendedor-a/
```
Muestra productos propios + afiliados

#### **2. Click en producto afiliado:**
```
https://ciudadvirtual.app/producto/mesa-madera/?store_origin=5&ref=vendedor-a
```

Parámetros:
- `store_origin=5` → ID del vendedor afiliado
- `ref=vendedor-a` → Slug de la tienda

#### **3. En carrito:**
```
Item meta data:
- wcfm_affiliate_store: 5
- wcfm_affiliate_time: 1729123456
```

#### **4. En pedido:**
```
Order meta:
- _wcfm_affiliate_store_origin: 5

Order item meta:
- _wcfm_affiliate_store: 5
- _affiliate_vendor_id: 5
- _affiliate_commission: 0.50
- _owner_commission: 49.50
```

---

## 📊 Visualización para Vendedores

### **Dashboard - Productos Afiliados:**

```
┌─────────────────────────────────────────┐
│ Mis Estadísticas de Afiliación         │
├─────────────────────────────────────────┤
│ 🟣 Productos Activos: 15                │
│ 🔴 Ventas Totales: 42                   │
│ 🔵 Ganancias Totales: 21.00€            │
└─────────────────────────────────────────┘

Mis Productos Afiliados:
┌────────────────────────────────────────┐
│ Mesa de Madera | Propietario: VendedorB│
│ Comisión: 1% | Estado: ✅ Activo       │
└────────────────────────────────────────┘
```

---

## 💰 Ejemplo de Comisiones Reales

### **Venta de 100€:**
```
Producto: 100€
─────────────────
Propietario (99%): 99.00€
  → Recibe pedido
  → Gestiona envío
  → Atiende cliente

Afiliado (1%): 1.00€
  → Solo comisión
  → Sin gestión
  → Ganancia pasiva
```

### **Venta de 25€:**
```
Producto: 25€
─────────────────
Propietario (99%): 24.75€
Afiliado (1%): 0.25€
```

---

## 🎨 Indicadores Visuales

### **En la tienda (lista de productos):**
```
┌─────────────────────────────┐
│ [Imagen]    🏷️ AFILIADO    │
│ Mesa de Madera              │
│ 50€                         │
└─────────────────────────────┘
```

### **En página del producto:**
```
┌────────────────────────────────────────┐
│ 🏪 Visto en la tienda de VendedorA     │
│    Producto de VendedorB               │
└────────────────────────────────────────┘

[Imagen del producto]
Mesa de Madera
50€

[Añadir al carrito]
```

---

## 🔍 Verificación del Sistema

### **Para verificar que funciona:**

1. **Añade un producto afiliado** desde Dashboard
2. **Visita tu tienda pública**
3. **Verifica que aparece** el producto
4. **Haz clic en el producto**
5. **Mira la URL** → debe tener `?store_origin=X&ref=tu-tienda`
6. **Ve el aviso azul** → "Visto en la tienda de..."

### **En logs (si WP_DEBUG activo):**
```
Affiliate: Store vendedor-a showing 10 own + 5 affiliate products
Affiliate Sale: Order #456, Product #123, Owner: 2 (49.50), Affiliate: 5 (0.50)
```

---

## 📈 Reportes

### **Para Vendedor Afiliado:**
```
Dashboard → Productos Afiliados

Estadísticas:
- Productos activos: 15
- Ventas totales: 42
- Ganancias: 21.00€
```

### **Para Administrador:**
```
WP Admin → WCFM → Ventas por Afiliación

Resumen global:
- Total afiliados: 50
- Total ventas: 1,234
- Ganancias afiliados: 1,234€
- Ganancias propietarios: 121,766€
```

---

## ✅ Checklist de Funcionamiento

- ✅ Añadir producto afiliado desde dashboard
- ✅ Producto aparece en escaparate del afiliado
- ✅ Badge "Producto Afiliado" visible
- ✅ URL incluye parámetros de tracking
- ✅ Aviso en página del producto
- ✅ Tracking guarda origen en sesión
- ✅ Al añadir al carrito, guarda referencia
- ✅ Al completar pedido, registra comisiones
- ✅ Pedido va al propietario
- ✅ Comisiones se calculan (1% / 99%)
- ✅ Estadísticas en dashboard
- ✅ Reportes en admin

---

## 🚀 Sistema Completo y Funcional

**Estado:** ✅ OPERATIVO  
**Comisión:** 1% afiliado / 99% propietario  
**Tracking:** ✅ Activo  
**Visualización:** ✅ Integrada  

---

**¡Todo listo para usar!** 🎉



