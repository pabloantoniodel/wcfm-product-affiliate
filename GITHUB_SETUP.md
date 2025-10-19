# 📦 Subir Plugin a GitHub

## Repositorio Git Creado ✅

El repositorio local está listo en:
```
/home/ciudadvirtual/htdocs/ciudadvirtual.store/wp-content/plugins/wcfm-product-affiliate/
```

**Commit inicial:** ✅ Completado  
**Archivos:** 14 archivos (3,988 líneas)

---

## 🚀 Pasos para Subir a GitHub

### **Opción 1: Crear Repositorio desde GitHub Web**

#### **1. Crear repositorio en GitHub:**

1. Ve a: https://github.com/new
2. Inicia sesión con: **pablaontoniodel@gmail.com**
3. Nombre del repositorio: `wcfm-product-affiliate`
4. Descripción: `Sistema de afiliación de productos para WCFM Marketplace sin clonación`
5. **Visibilidad**: 
   - ✅ **Private** (recomendado) - Solo tú lo ves
   - ⬜ Public - Cualquiera lo ve
6. ❌ **NO** marques "Initialize with README" (ya tenemos uno)
7. Haz clic en **"Create repository"**

#### **2. Subir el código:**

GitHub te mostrará instrucciones. Ejecuta en el servidor:

```bash
cd /home/ciudadvirtual/htdocs/ciudadvirtual.store/wp-content/plugins/wcfm-product-affiliate

# Añadir el remote
git remote add origin https://github.com/pablaontoniodel/wcfm-product-affiliate.git

# Renombrar rama a main (opcional)
git branch -M main

# Subir código
git push -u origin main
```

**Nota:** Te pedirá usuario y contraseña/token de GitHub.

---

### **Opción 2: Usar Token de GitHub**

Si GitHub pide autenticación:

#### **1. Crear Personal Access Token:**

1. Ve a: https://github.com/settings/tokens
2. Clic en **"Generate new token (classic)"**
3. Nombre: `wcfm-affiliate-upload`
4. Scopes: Marca **✅ repo** (todos los permisos de repo)
5. Clic en **"Generate token"**
6. **Copia el token** (solo se muestra una vez)

#### **2. Usar el token para subir:**

```bash
cd /home/ciudadvirtual/htdocs/ciudadvirtual.store/wp-content/plugins/wcfm-product-affiliate

# Usuario: tu username de GitHub
# Contraseña: el token que copiaste

git push -u origin main
```

---

### **Opción 3: Script Automático**

He creado un script para facilitar el proceso:

```bash
cd /home/ciudadvirtual/htdocs/ciudadvirtual.store/wp-content/plugins/wcfm-product-affiliate
./github-push.sh
```

---

## 📋 Comandos Útiles

### **Ver estado del repositorio:**
```bash
cd /home/ciudadvirtual/htdocs/ciudadvirtual.store/wp-content/plugins/wcfm-product-affiliate
git status
```

### **Ver historial:**
```bash
git log --oneline
```

### **Ver archivos en el commit:**
```bash
git ls-files
```

### **Hacer cambios futuros:**
```bash
# Después de editar archivos
git add .
git commit -m "Descripción de los cambios"
git push
```

---

## 📊 Contenido del Repositorio

```
wcfm-product-affiliate/
├── .git/                    ← Repositorio Git
├── .gitignore              ← Archivos a ignorar
├── wcfm-product-affiliate.php
├── README.md
├── INSTALL_GUIDE.md
├── FUNCIONAMIENTO.md
├── GITHUB_SETUP.md         ← Este archivo
├── readme.txt
├── includes/ (5 archivos)
├── frontend/ (2 archivos)
├── admin/
├── templates/
└── languages/
```

**Total:** 14 archivos, 3,988 líneas de código

---

## ✅ Estado Actual

- ✅ Git inicializado
- ✅ Commit inicial creado
- ✅ Archivos listos para subir
- ⏳ Pendiente: Crear repositorio en GitHub
- ⏳ Pendiente: Configurar remote
- ⏳ Pendiente: Push inicial

---

## 🔐 Configuración Recomendada

### **Configurar nombre y email de Git:**

```bash
cd /home/ciudadvirtual/htdocs/ciudadvirtual.store/wp-content/plugins/wcfm-product-affiliate

git config user.name "Pablo Antonio"
git config user.email "pablaontoniodel@gmail.com"

# O global para todo el servidor
git config --global user.name "Pablo Antonio"
git config --global user.email "pablaontoniodel@gmail.com"
```

### **Actualizar el commit con la info correcta:**
```bash
git commit --amend --reset-author --no-edit
```

---

## 📝 Descripción del Repositorio (para GitHub)

**Nombre:** `wcfm-product-affiliate`

**Descripción corta:**
```
Sistema de afiliación para WCFM Marketplace - Vende productos sin clonarlos
```

**Descripción completa:**
```
Plugin de WordPress que permite a los vendedores de WCFM Marketplace 
vender productos de otros vendedores sin clonarlos. Incluye:

- Sistema de tracking de origen de ventas
- Comisiones duales automáticas
- Dashboard completo para vendedores
- Reportes para administradores
- Integración total con WooCommerce y WCFM
- Interfaz en español
```

**Topics (etiquetas):**
```
wordpress, woocommerce, wcfm, marketplace, affiliate, commission, 
multivendor, ecommerce, plugin
```

---

## 🎯 Próximos Pasos

1. **Crear repositorio** en GitHub (https://github.com/new)
2. **Copiar URL** del repositorio
3. **Ejecutar comandos** de arriba para vincular y subir

¿Quieres que te ayude con algún paso específico?

