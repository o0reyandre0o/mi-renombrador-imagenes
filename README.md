# Toc Toc SEO Images

**Versión:** 3.6.0  
**Autor:** Toc Toc Marketing  
**Licencia:** GPL v2 or later  

## 🚀 Descripción

Plugin avanzado de WordPress que optimiza automáticamente las imágenes mediante renombrado inteligente, compresión sin pérdida visible y generación de metadatos SEO utilizando Google AI (Gemini Vision). Ideal para mejorar el SEO de imágenes y la velocidad de carga del sitio web.

## ✨ Características Principales

### 🤖 Inteligencia Artificial
- **Análisis visual con Google AI (Gemini Vision)**
- Generación automática de títulos descriptivos
- Creación de texto alternativo optimizado para SEO
- Generación de leyendas contextuales
- **Soporte multiidioma** (Español, Inglés, Francés, Alemán, Italiano, Portugués)

### 📁 Gestión de Archivos
- **Renombrado inteligente** basado en título de página/producto
- **Compresión automática** sin pérdida visible de calidad
- Soporte para múltiples formatos: JPG, PNG, WebP, GIF, AVIF, SVG
- Procesamiento masivo de imágenes existentes

### 🔧 Optimización Técnica
- **Compresión avanzada** con Imagick o GD como fallback
- Control de calidad JPEG/WebP personalizable (60-100%)
- Eliminación automática de metadatos EXIF
- Regeneración automática de metadatos de WordPress

### 🎯 Integración SEO
- Compatible con **Yoast SEO**, **Rank Math**, **AIOSEO**, **SEOPress**
- Incorporación automática de focus keywords
- Optimización de alt text para accesibilidad
- Contexto de producto/página en la generación IA

## 📋 Requisitos

### Mínimos
- WordPress 5.0+
- PHP 7.4+
- Extensión GD habilitada

### Recomendados
- WordPress 6.0+
- PHP 8.0+
- **Extensión Imagick** habilitada (mejor compresión)
- **Clave API de Google AI** (para funciones IA)
- Memoria PHP: 256MB+ (recomendado para procesamiento masivo)

## 🛠️ Instalación

1. **Subir el plugin:**
   ```
   /wp-content/plugins/mi-renombrador-imagenes/
   ```

2. **Activar el plugin** desde el panel de administración de WordPress

3. **Configurar las opciones:**
   - Ir a `Ajustes > Renombrador Imágenes IA`
   - Configurar la clave API de Google AI (opcional)
   - Ajustar preferencias de compresión y renombrado

## ⚙️ Configuración Básica

### 1. Configuración General
- ✅ **Activar Renombrado**: Renombra archivos automáticamente
- ✅ **Activar Compresión**: Reduce el tamaño sin pérdida visible
- ⚙️ **Calidad JPEG/WebP**: 82-90 recomendado
- ✅ **Usar Imagick**: Mejor calidad de compresión

### 2. Configuración de Google AI
```
API Key: tu-clave-de-google-ai-studio
Modelo: gemini-1.5-flash-latest (recomendado)
Idioma: Español (o el de tu preferencia)
```

### 3. Metadatos Automáticos
- **Título IA**: Genera títulos descriptivos
- **Alt Text IA**: Optimizado para SEO y accesibilidad
- **Leyenda IA**: Descripciones contextuales

## 🚀 Uso

### Subida Automática
- Las imágenes se procesan automáticamente al subirlas
- El renombrado y compresión se aplican inmediatamente
- Los metadatos se generan según la configuración

### Procesamiento Masivo
1. Ir a `Medios > Procesar Imágenes Antiguas IA`
2. Seleccionar criterios (todas las imágenes o solo sin alt text)
3. Hacer clic en "Iniciar Procesamiento"
4. El proceso se ejecuta en lotes para evitar timeouts

### Resultados Esperados
- **Archivos renombrados**: `titulo-pagina-titulo-imagen.jpg`
- **Reducción de tamaño**: 10-40% típicamente
- **Metadatos completos**: Título, Alt Text, Leyenda optimizados

## 📊 Monitoreo y Logs

### Registro de Actividad
- **Logs en tiempo real** durante procesamiento masivo
- **Códigos de color** para diferentes tipos de eventos:
  - 🟢 Verde: Operaciones exitosas
  - 🟡 Amarillo: Advertencias
  - 🔴 Rojo: Errores
  - ℹ️ Azul: Información general

### Seguimiento de Progreso
- Barra de progreso visual
- Contador de imágenes procesadas
- Estimación de tiempo restante

## 🔍 Solución de Problemas

### Problemas Comunes

**1. Error de API de Google AI**
```
Verificar:
- Clave API válida
- Modelo multimodal seleccionado
- Créditos disponibles en Google AI Studio
```

**2. Fallo en Compresión**
```
Verificar:
- Imagick instalado (preferido) o GD disponible
- Permisos de escritura en directorio uploads
- Memoria PHP suficiente
```

**3. Timeouts en Procesamiento Masivo**
```
Soluciones:
- Reducir tamaño de lote (automático para tareas intensivas)
- Aumentar tiempo límite PHP
- Procesar por etapas
```

## 🔗 Enlaces Útiles

- [Google AI Studio](https://aistudio.google.com/) - Obtener API Key
- [Documentación de Gemini](https://ai.google.dev/docs)
- [Documentación Técnica](docs/documentation.md)

## 🤝 Soporte

Para soporte técnico y consultas:
- **Sitio web:** https://toctoc.ky/
- **Documentación completa:** Ver `docs/documentation.md`

## 📄 Licencia

Este plugin está licenciado bajo GPL v2 or later.
