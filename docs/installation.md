# Guía de Instalación y Configuración - Toc Toc SEO Images

## 🚀 Instalación para Desarrollo

### 1. **Clonar/Descargar el Proyecto**
```bash
# Si tienes repositorio Git
git clone [url-del-repositorio] mi-renombrador-imagenes
cd mi-renombrador-imagenes

# O simplemente copia la carpeta al directorio de plugins de WordPress
# wp-content/plugins/mi-renombrador-imagenes/
```

### 2. **Instalar Dependencias de Desarrollo**
```bash
# Dependencias PHP (Composer)
composer install --dev

# Dependencias JavaScript (si tienes package.json)
npm install
```

### 3. **Configurar Herramientas de Desarrollo**
```bash
# Configurar PHPCS para WordPress
./vendor/bin/phpcs --config-set installed_paths vendor/wp-coding-standards/wpcs

# Verificar configuración
./vendor/bin/phpcs -i
```

## ⚙️ Configuración del Plugin

### 1. **Activar en WordPress**
1. Acceder al panel de administración de WordPress
2. Ir a `Plugins > Plugins Instalados`
3. Activar "Toc Toc SEO Images"

### 2. **Configuración Inicial Básica**
```
Ajustes > Renombrador Imágenes IA

Configuración General:
✅ Activar Renombrado
✅ Activar Compresión
⚙️ Calidad JPEG/WebP: 85
✅ Usar Imagick si está disponible
✅ Activar Generación Alt
✅ Sobrescribir Alt
✅ Activar Generación Leyenda
```

### 3. **Configuración Avanzada con IA**
```
Integración con Google AI (Gemini):
🔑 Clave API: [tu-clave-de-google-ai]
🤖 Modelo: gemini-1.5-flash-latest
🌐 Idioma: Español (o el deseado)
✅ Usar IA para Título
✅ Usar IA para Alt Text  
✅ Usar IA para Leyenda
✅ Incluir Contexto SEO
```

## 🔑 Obtener Clave API de Google AI

### 1. **Google AI Studio (Gratuito)**
1. Visitar: https://aistudio.google.com/
2. Iniciar sesión con cuenta Google
3. Hacer clic en "Get API Key"
4. Crear nuevo proyecto o usar existente
5. Copiar la clave API generada

### 2. **Google Cloud Console (Avanzado)**
1. Ir a: https://console.cloud.google.com/
2. Crear nuevo proyecto o seleccionar existente
3. Habilitar "Generative Language API"
4. Ir a "Credenciales" > "Crear credenciales" > "Clave API"
5. Configurar restricciones (recomendado)

### 3. **Configurar Restricciones de API (Recomendado)**
```
Restricciones de aplicación:
- Referentes HTTP: tu-dominio.com/*

Restricciones de API:
- Generative Language API

Límites de cuota:
- Configurar límites diarios apropiados
```

## 🧪 Verificación de Configuración

### 1. **Verificar Dependencias del Servidor**
```bash
# PHP Extensions requeridas
php -m | grep -E "(gd|imagick|curl|json)"

# Debe mostrar:
# gd
# imagick (opcional pero recomendado)
# curl
# json
```

### 2. **Test de Funcionalidad WordPress**
```php
// Añadir a functions.php temporalmente para debug
add_action('init', function() {
    if (current_user_can('administrator')) {
        echo '<pre>';
        echo "WordPress Version: " . get_bloginfo('version') . "\n";
        echo "PHP Version: " . PHP_VERSION . "\n";
        echo "GD Extension: " . (extension_loaded('gd') ? 'Sí' : 'No') . "\n";
        echo "Imagick Extension: " . (extension_loaded('imagick') ? 'Sí' : 'No') . "\n";
        echo "Upload Max Size: " . ini_get('upload_max_filesize') . "\n";
        echo "Memory Limit: " . ini_get('memory_limit') . "\n";
        echo '</pre>';
    }
});
```

### 3. **Test de API de Google AI**
1. Subir una imagen de prueba
2. Verificar en `Medios > Biblioteca` que se han generado:
   - Título descriptivo
   - Alt text optimizado
   - Leyenda contextual
3. Comprobar logs de error en caso de fallos

## 🔧 Comandos de Desarrollo

### **Linting y Calidad de Código**
```bash
# Verificar código PHP con WordPress Standards
composer lint

# Corregir automáticamente errores menores
composer lint:fix

# Verificar compatibilidad PHP
./vendor/bin/phpcs --standard=PHPCompatibilityWP mi-renombrador-imagenes.php
```

### **Testing**
```bash
# Ejecutar tests unitarios
composer test

# Tests con cobertura
composer test:coverage

# Verificar todo (lint + test)
composer check
```

### **Build para Producción**
```bash
# Si tienes webpack configurado
npm run build

# Crear ZIP para distribución
zip -r mi-renombrador-imagenes.zip . -x "node_modules/*" "vendor/*" "tests/*" ".git/*"
```

## 🐛 Solución de Problemas Comunes

### **Error: API Key no válida**
```
1. Verificar que la clave está correctamente copiada
2. Comprobar que la API está habilitada en Google Cloud
3. Verificar restricciones de dominio
4. Comprobar límites de cuota
```

### **Error: Imagick no disponible**
```bash
# Ubuntu/Debian
sudo apt-get install php-imagick

# CentOS/RHEL
sudo yum install php-pecl-imagick

# Verificar instalación
php -m | grep imagick
```

### **Timeouts en procesamiento masivo**
```php
// Aumentar límites en wp-config.php
ini_set('max_execution_time', 300);
ini_set('memory_limit', '256M');

// O configurar en .htaccess
php_value max_execution_time 300
php_value memory_limit 256M
```

### **Permisos de archivos**
```bash
# Configurar permisos correctos
chmod 755 wp-content/plugins/mi-renombrador-imagenes/
chmod 644 wp-content/plugins/mi-renombrador-imagenes/*.php
chmod 755 wp-content/uploads/
```

## 📊 Monitoreo y Logs

### **Habilitar Debug de WordPress**
```php
// wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

### **Logs del Plugin**
```bash
# Ver logs en tiempo real
tail -f wp-content/debug.log | grep "MRI Plugin"

# Logs específicos del plugin
grep "MRI Plugin" wp-content/debug.log
```

### **Métricas de Performance**
```php
// Tiempo de procesamiento
add_action('mri_image_processed', function($attachment_id, $log) {
    error_log("MRI Performance: Image $attachment_id processed in " . timer_stop() . "s");
});
```

## 🔄 Actualizaciones del Plugin

### **Preparar Nueva Versión**
1. Actualizar número de versión en archivo principal
2. Actualizar CHANGELOG.md
3. Ejecutar tests completos
4. Verificar compatibilidad con última versión WordPress
5. Crear tag de versión

### **Despliegue**
```bash
# Crear paquete limpio
composer install --no-dev --optimize-autoloader
npm run build
zip -r mi-renombrador-imagenes-v3.6.0.zip . -x "*.git*" "node_modules/*" "vendor/*" "tests/*"
```

## 📚 Recursos Adicionales

### **Documentación Relacionada**
- [WordPress Plugin API](https://developer.wordpress.org/plugins/)
- [Google AI Documentation](https://ai.google.dev/docs)
- [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/)
- [Imagick PHP Documentation](https://www.php.net/manual/en/book.imagick.php)

### **Herramientas Útiles**
- [WordPress Plugin Boilerplate](https://wppb.me/)
- [Query Monitor](https://wordpress.org/plugins/query-monitor/) - Debug WordPress
- [Debug Bar](https://wordpress.org/plugins/debug-bar/) - Información de debug
- [P3 Plugin Profiler](https://wordpress.org/plugins/p3-profiler/) - Performance

### **Extensiones VSCode Recomendadas**
```json
{
    "recommendations": [
        "phpcs.php-codesniffer",
        "bmewburn.vscode-intelephense-client",
        "bradlc.vscode-tailwindcss",
        "ms-vscode.vscode-json"
    ]
}
```

---

*Guía de instalación actualizada para la versión 3.6.0*  
*Última revisión: [Fecha actual]*
