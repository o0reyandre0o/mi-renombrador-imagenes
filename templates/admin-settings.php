<?php
/**
 * Admin Settings Page Template
 * Toc Toc SEO Images Plugin
 * 
 * @package TocTocSEOImages
 * @version 3.6.0
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Verificar permisos
if (!current_user_can('manage_options')) {
    wp_die(__('No tienes permisos suficientes para acceder a esta página.', 'mi-renombrador-imagenes'));
}

// Obtener opciones actuales
$options = get_option(MRI_SETTINGS_OPTION_NAME, mri_google_ai_get_default_options());
$supported_languages = mri_get_supported_languages();
?>

<div class="wrap mri-admin-page">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <?php settings_errors(); ?>
    
    <!-- Navigation Tabs -->
    <nav class="nav-tab-wrapper mri-nav-tabs">
        <a href="#general" class="nav-tab nav-tab-active" id="general-tab">
            <span class="dashicons dashicons-admin-generic"></span>
            <?php esc_html_e('General', 'mi-renombrador-imagenes'); ?>
        </a>
        <a href="#compression" class="nav-tab" id="compression-tab">
            <span class="dashicons dashicons-performance"></span>
            <?php esc_html_e('Compresión', 'mi-renombrador-imagenes'); ?>
        </a>
        <a href="#ai-settings" class="nav-tab" id="ai-tab">
            <span class="dashicons dashicons-robot"></span>
            <?php esc_html_e('Inteligencia Artificial', 'mi-renombrador-imagenes'); ?>
        </a>
        <a href="#advanced" class="nav-tab" id="advanced-tab">
            <span class="dashicons dashicons-admin-tools"></span>
            <?php esc_html_e('Avanzado', 'mi-renombrador-imagenes'); ?>
        </a>
    </nav>

    <form action="options.php" method="post" id="mri-settings-form">
        <?php
        settings_fields('mri_google_ai_options_group');
        ?>

        <!-- General Settings Tab -->
        <div id="general" class="mri-tab-content mri-tab-active">
            <div class="mri-settings-section">
                <h2 class="section-title">
                    <span class="dashicons dashicons-admin-generic"></span>
                    <?php esc_html_e('Configuración General', 'mi-renombrador-imagenes'); ?>
                </h2>
                <p class="section-description">
                    <?php esc_html_e('Configura las opciones básicas de procesamiento automático para las imágenes subidas.', 'mi-renombrador-imagenes'); ?>
                </p>

                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row">
                            <label for="mri_google_ai_options_enable_rename">
                                <?php esc_html_e('Renombrado Automático', 'mi-renombrador-imagenes'); ?>
                            </label>
                        </th>
                        <td>
                            <fieldset>
                                <label for="mri_google_ai_options_enable_rename">
                                    <input type="checkbox" 
                                           id="mri_google_ai_options_enable_rename" 
                                           name="<?php echo esc_attr(MRI_SETTINGS_OPTION_NAME); ?>[enable_rename]" 
                                           value="1" 
                                           <?php checked(1, $options['enable_rename']); ?> />
                                    <?php esc_html_e('Renombrar archivos usando Título de Página/Producto y Título de Imagen', 'mi-renombrador-imagenes'); ?>
                                </label>
                                <p class="description">
                                    <?php esc_html_e('Los archivos se renombrarán automáticamente con un formato descriptivo basado en el contenido y contexto.', 'mi-renombrador-imagenes'); ?>
                                </p>
                            </fieldset>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="mri_google_ai_options_enable_alt">
                                <?php esc_html_e('Texto Alternativo', 'mi-renombrador-imagenes'); ?>
                            </label>
                        </th>
                        <td>
                            <fieldset>
                                <label for="mri_google_ai_options_enable_alt">
                                    <input type="checkbox" 
                                           id="mri_google_ai_options_enable_alt" 
                                           name="<?php echo esc_attr(MRI_SETTINGS_OPTION_NAME); ?>[enable_alt]" 
                                           value="1" 
                                           <?php checked(1, $options['enable_alt']); ?> />
                                    <?php esc_html_e('Generar automáticamente el texto alternativo', 'mi-renombrador-imagenes'); ?>
                                </label>
                                <br>
                                <label for="mri_google_ai_options_overwrite_alt" style="margin-top: 8px; display: inline-block;">
                                    <input type="checkbox" 
                                           id="mri_google_ai_options_overwrite_alt" 
                                           name="<?php echo esc_attr(MRI_SETTINGS_OPTION_NAME); ?>[overwrite_alt]" 
                                           value="1" 
                                           <?php checked(1, $options['overwrite_alt']); ?> />
                                    <?php esc_html_e('Sobrescribir Alt Text existente', 'mi-renombrador-imagenes'); ?>
                                </label>
                                <p class="description">
                                    <?php esc_html_e('El texto alternativo mejora la accesibilidad y SEO. Si no se sobrescribe, solo se añade cuando está vacío.', 'mi-renombrador-imagenes'); ?>
                                </p>
                            </fieldset>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="mri_google_ai_options_enable_caption">
                                <?php esc_html_e('Leyendas de Imagen', 'mi-renombrador-imagenes'); ?>
                            </label>
                        </th>
                        <td>
                            <fieldset>
                                <label for="mri_google_ai_options_enable_caption">
                                    <input type="checkbox" 
                                           id="mri_google_ai_options_enable_caption" 
                                           name="<?php echo esc_attr(MRI_SETTINGS_OPTION_NAME); ?>[enable_caption]" 
                                           value="1" 
                                           <?php checked(1, $options['enable_caption']); ?> />
                                    <?php esc_html_e('Generar automáticamente leyendas descriptivas', 'mi-renombrador-imagenes'); ?>
                                </label>
                                <br>
                                <label for="mri_google_ai_options_overwrite_caption" style="margin-top: 8px; display: inline-block;">
                                    <input type="checkbox" 
                                           id="mri_google_ai_options_overwrite_caption" 
                                           name="<?php echo esc_attr(MRI_SETTINGS_OPTION_NAME); ?>[overwrite_caption]" 
                                           value="1" 
                                           <?php checked(1, $options['overwrite_caption']); ?> />
                                    <?php esc_html_e('Sobrescribir leyendas existentes', 'mi-renombrador-imagenes'); ?>
                                </label>
                                <p class="description">
                                    <?php esc_html_e('Las leyendas proporcionan contexto adicional y pueden mejorar el engagement del usuario.', 'mi-renombrador-imagenes'); ?>
                                </p>
                            </fieldset>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Compression Settings Tab -->
        <div id="compression" class="mri-tab-content">
            <div class="mri-settings-section">
                <h2 class="section-title">
                    <span class="dashicons dashicons-performance"></span>
                    <?php esc_html_e('Configuración de Compresión', 'mi-renombrador-imagenes'); ?>
                </h2>
                <p class="section-description">
                    <?php esc_html_e('Optimiza automáticamente las imágenes para reducir el tamaño de archivo sin pérdida visible de calidad.', 'mi-renombrador-imagenes'); ?>
                </p>

                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row">
                            <label for="mri_google_ai_options_enable_compression">
                                <?php esc_html_e('Compresión Automática', 'mi-renombrador-imagenes'); ?>
                            </label>
                        </th>
                        <td>
                            <fieldset>
                                <label for="mri_google_ai_options_enable_compression">
                                    <input type="checkbox" 
                                           id="mri_google_ai_options_enable_compression" 
                                           name="<?php echo esc_attr(MRI_SETTINGS_OPTION_NAME); ?>[enable_compression]" 
                                           value="1" 
                                           <?php checked(1, $options['enable_compression']); ?> />
                                    <?php esc_html_e('Comprimir imágenes automáticamente al subirlas', 'mi-renombrador-imagenes'); ?>
                                </label>
                                <p class="description">
                                    <?php esc_html_e('Reduce el tamaño de archivo sin pérdida visible de calidad, mejorando la velocidad de carga del sitio.', 'mi-renombrador-imagenes'); ?>
                                </p>
                            </fieldset>
                        </td>
                    </tr>

                    <tr class="compression-settings">
                        <th scope="row">
                            <label for="mri_google_ai_options_jpeg_quality">
                                <?php esc_html_e('Calidad JPEG/WebP', 'mi-renombrador-imagenes'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="range" 
                                   id="mri_google_ai_options_jpeg_quality" 
                                   name="<?php echo esc_attr(MRI_SETTINGS_OPTION_NAME); ?>[jpeg_quality]" 
                                   min="60" 
                                   max="100" 
                                   value="<?php echo esc_attr($options['jpeg_quality']); ?>" 
                                   step="1" 
                                   oninput="document.getElementById('quality-value').textContent = this.value" />
                            <span id="quality-value" class="quality-display"><?php echo esc_html($options['jpeg_quality']); ?>%</span>
                            <p class="description">
                                <?php esc_html_e('Nivel de calidad para JPEG y WebP (60-100). Recomendado: 82-90 para un buen balance entre calidad y tamaño.', 'mi-renombrador-imagenes'); ?>
                            </p>
                        </td>
                    </tr>

                    <tr class="compression-settings">
                        <th scope="row">
                            <label for="mri_google_ai_options_use_imagick_if_available">
                                <?php esc_html_e('Usar Imagick', 'mi-renombrador-imagenes'); ?>
                            </label>
                        </th>
                        <td>
                            <fieldset>
                                <label for="mri_google_ai_options_use_imagick_if_available">
                                    <input type="checkbox" 
                                           id="mri_google_ai_options_use_imagick_if_available" 
                                           name="<?php echo esc_attr(MRI_SETTINGS_OPTION_NAME); ?>[use_imagick_if_available]" 
                                           value="1" 
                                           <?php checked(1, $options['use_imagick_if_available']); ?> />
                                    <?php esc_html_e('Priorizar Imagick cuando esté disponible', 'mi-renombrador-imagenes'); ?>
                                </label>
                                <p class="description">
                                    <?php 
                                    if (extension_loaded('imagick') && class_exists('Imagick')) {
                                        echo '✅ ' . esc_html__('Imagick está disponible en tu servidor (recomendado).', 'mi-renombrador-imagenes');
                                    } else {
                                        echo '⚠️ ' . esc_html__('Imagick no está disponible. Se usará GD como alternativa.', 'mi-renombrador-imagenes');
                                    }
                                    ?>
                                </p>
                            </fieldset>
                        </td>
                    </tr>
                </table>

                <!-- Server Info -->
                <div id="mri-server-info" class="mri-server-info">
                    <!-- La información del servidor se cargará aquí via JavaScript -->
                </div>
            </div>
        </div>

        <!-- AI Settings Tab -->
        <div id="ai-settings" class="mri-tab-content">
            <div class="mri-settings-section ai-settings-section">
                <h2 class="section-title">
                    <span class="dashicons dashicons-robot"></span>
                    <?php esc_html_e('Integración con Google AI (Gemini)', 'mi-renombrador-imagenes'); ?>
                </h2>
                <p class="section-description">
                    <?php esc_html_e('Configura la integración con Google AI (Gemini Vision) para analizar imágenes y generar metadatos inteligentes.', 'mi-renombrador-imagenes'); ?>
                </p>

                <div class="notice notice-info">
                    <p>
                        <strong><?php esc_html_e('Importante:', 'mi-renombrador-imagenes'); ?></strong>
                        <?php esc_html_e('El uso de Google AI puede tener costos asociados y ralentizará el procesamiento. Revisa los precios y límites en Google AI Studio.', 'mi-renombrador-imagenes'); ?>
                    </p>
                </div>

                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row">
                            <label for="mri_google_ai_options_gemini_api_key">
                                <?php esc_html_e('Clave API Google AI', 'mi-renombrador-imagenes'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="password" 
                                   id="mri_google_ai_options_gemini_api_key" 
                                   name="<?php echo esc_attr(MRI_SETTINGS_OPTION_NAME); ?>[gemini_api_key]" 
                                   value="<?php echo esc_attr($options['gemini_api_key']); ?>" 
                                   class="regular-text" 
                                   autocomplete="off" />
                            <p class="description">
                                <?php printf(
                                    esc_html__('Obtén tu clave API gratuita en %s. Requiere una cuenta Google.', 'mi-renombrador-imagenes'),
                                    '<a href="https://aistudio.google.com/" target="_blank">Google AI Studio</a>'
                                ); ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="mri_google_ai_options_gemini_model">
                                <?php esc_html_e('Modelo Gemini', 'mi-renombrador-imagenes'); ?>
                            </label>
                        </th>
                        <td>
                            <select id="mri_google_ai_options_gemini_model" 
                                    name="<?php echo esc_attr(MRI_SETTINGS_OPTION_NAME); ?>[gemini_model]" 
                                    class="regular-text">
                                <option value="gemini-1.5-flash-latest" <?php selected($options['gemini_model'], 'gemini-1.5-flash-latest'); ?>>
                                    Gemini 1.5 Flash (Recomendado - Rápido y Multimodal)
                                </option>
                                <option value="gemini-1.5-pro-latest" <?php selected($options['gemini_model'], 'gemini-1.5-pro-latest'); ?>>
                                    Gemini 1.5 Pro (Más Potente - Mayor Precisión)
                                </option>
                                <option value="gemini-pro-vision" <?php selected($options['gemini_model'], 'gemini-pro-vision'); ?>>
                                    Gemini Pro Vision (Modelo Anterior)
                                </option>
                            </select>
                            <p class="description">
                                <?php esc_html_e('Selecciona el modelo de Google AI a utilizar. Los modelos 1.5 son más avanzados y recomendados.', 'mi-renombrador-imagenes'); ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="mri_google_ai_options_ai_output_language">
                                <?php esc_html_e('Idioma de Salida', 'mi-renombrador-imagenes'); ?>
                            </label>
                        </th>
                        <td>
                            <select id="mri_google_ai_options_ai_output_language" 
                                    name="<?php echo esc_attr(MRI_SETTINGS_OPTION_NAME); ?>[ai_output_language]">
                                <?php foreach ($supported_languages as $code => $name) : ?>
                                    <option value="<?php echo esc_attr($code); ?>" <?php selected($options['ai_output_language'], $code); ?>>
                                        <?php echo esc_html($name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description">
                                <?php esc_html_e('Idioma en el que la IA generará los títulos, texto alternativo y leyendas.', 'mi-renombrador-imagenes'); ?>
                            </p>
                        </td>
                    </tr>
                </table>

                <h3><?php esc_html_e('Funciones de IA Activadas', 'mi-renombrador-imagenes'); ?></h3>
                
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><?php esc_html_e('Análisis Visual', 'mi-renombrador-imagenes'); ?></th>
                        <td>
                            <fieldset>
                                <label for="mri_google_ai_options_enable_ai_title">
                                    <input type="checkbox" 
                                           id="mri_google_ai_options_enable_ai_title" 
                                           name="<?php echo esc_attr(MRI_SETTINGS_OPTION_NAME); ?>[enable_ai_title]" 
                                           value="1" 
                                           <?php checked(1, $options['enable_ai_title']); ?> />
                                    <?php esc_html_e('Generar títulos con IA analizando la imagen', 'mi-renombrador-imagenes'); ?>
                                </label>
                                <br>
                                <label for="mri_google_ai_options_enable_ai_alt" style="margin-top: 8px; display: inline-block;">
                                    <input type="checkbox" 
                                           id="mri_google_ai_options_enable_ai_alt" 
                                           name="<?php echo esc_attr(MRI_SETTINGS_OPTION_NAME); ?>[enable_ai_alt]" 
                                           value="1" 
                                           <?php checked(1, $options['enable_ai_alt']); ?> />
                                    <?php esc_html_e('Generar Alt Text con IA analizando la imagen', 'mi-renombrador-imagenes'); ?>
                                </label>
                                <br>
                                <label for="mri_google_ai_options_enable_ai_caption" style="margin-top: 8px; display: inline-block;">
                                    <input type="checkbox" 
                                           id="mri_google_ai_options_enable_ai_caption" 
                                           name="<?php echo esc_attr(MRI_SETTINGS_OPTION_NAME); ?>[enable_ai_caption]" 
                                           value="1" 
                                           <?php checked(1, $options['enable_ai_caption']); ?> />
                                    <?php esc_html_e('Generar leyendas con IA analizando la imagen', 'mi-renombrador-imagenes'); ?>
                                </label>
                            </fieldset>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php esc_html_e('Opciones Avanzadas de IA', 'mi-renombrador-imagenes'); ?></th>
                        <td>
                            <fieldset>
                                <label for="mri_google_ai_options_overwrite_title">
                                    <input type="checkbox" 
                                           id="mri_google_ai_options_overwrite_title" 
                                           name="<?php echo esc_attr(MRI_SETTINGS_OPTION_NAME); ?>[overwrite_title]" 
                                           value="1" 
                                           <?php checked(1, $options['overwrite_title']); ?> />
                                    <?php esc_html_e('Sobrescribir títulos existentes con IA', 'mi-renombrador-imagenes'); ?>
                                </label>
                                <br>
                                <label for="mri_google_ai_options_include_seo_in_ai_prompt" style="margin-top: 8px; display: inline-block;">
                                    <input type="checkbox" 
                                           id="mri_google_ai_options_include_seo_in_ai_prompt" 
                                           name="<?php echo esc_attr(MRI_SETTINGS_OPTION_NAME); ?>[include_seo_in_ai_prompt]" 
                                           value="1" 
                                           <?php checked(1, $options['include_seo_in_ai_prompt']); ?> />
                                    <?php esc_html_e('Incluir contexto SEO en prompts de IA', 'mi-renombrador-imagenes'); ?>
                                </label>
                                <p class="description">
                                    <?php esc_html_e('Envía información de la página/producto y keywords a la IA para generar contenido más relevante.', 'mi-renombrador-imagenes'); ?>
                                </p>
                            </fieldset>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Advanced Settings Tab -->
        <div id="advanced" class="mri-tab-content">
            <div class="mri-settings-section">
                <h2 class="section-title">
                    <span class="dashicons dashicons-admin-tools"></span>
                    <?php esc_html_e('Configuración Avanzada', 'mi-renombrador-imagenes'); ?>
                </h2>
                <p class="section-description">
                    <?php esc_html_e('Opciones avanzadas y información del sistema para usuarios experimentados.', 'mi-renombrador-imagenes'); ?>
                </p>

                <!-- Sistema Information -->
                <div class="mri-system-info">
                    <h3><?php esc_html_e('Información del Sistema', 'mi-renombrador-imagenes'); ?></h3>
                    <table class="widefat striped">
                        <tbody>
                            <tr>
                                <td><strong><?php esc_html_e('Versión WordPress:', 'mi-renombrador-imagenes'); ?></strong></td>
                                <td><?php echo esc_html(get_bloginfo('version')); ?></td>
                            </tr>
                            <tr>
                                <td><strong><?php esc_html_e('Versión PHP:', 'mi-renombrador-imagenes'); ?></strong></td>
                                <td><?php echo esc_html(PHP_VERSION); ?></td>
                            </tr>
                            <tr>
                                <td><strong><?php esc_html_e('Extensión GD:', 'mi-renombrador-imagenes'); ?></strong></td>
                                <td><?php echo extension_loaded('gd') ? '✅ ' . esc_html__('Disponible', 'mi-renombrador-imagenes') : '❌ ' . esc_html__('No disponible', 'mi-renombrador-imagenes'); ?></td>
                            </tr>
                            <tr>
                                <td><strong><?php esc_html_e('Extensión Imagick:', 'mi-renombrador-imagenes'); ?></strong></td>
                                <td><?php echo (extension_loaded('imagick') && class_exists('Imagick')) ? '✅ ' . esc_html__('Disponible', 'mi-renombrador-imagenes') : '❌ ' . esc_html__('No disponible', 'mi-renombrador-imagenes'); ?></td>
                            </tr>
                            <tr>
                                <td><strong><?php esc_html_e('Límite de memoria:', 'mi-renombrador-imagenes'); ?></strong></td>
                                <td><?php echo esc_html(ini_get('memory_limit')); ?></td>
                            </tr>
                            <tr>
                                <td><strong><?php esc_html_e('Tamaño máximo de subida:', 'mi-renombrador-imagenes'); ?></strong></td>
                                <td><?php echo esc_html(ini_get('upload_max_filesize')); ?></td>
                            </tr>
                            <tr>
                                <td><strong><?php esc_html_e('Tiempo máximo de ejecución:', 'mi-renombrador-imagenes'); ?></strong></td>
                                <td><?php echo esc_html(ini_get('max_execution_time')); ?> <?php esc_html_e('segundos', 'mi-renombrador-imagenes'); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Current Configuration Preview -->
                <div id="mri-config-preview" class="mri-config-preview">
                    <!-- Se llenará via JavaScript -->
                </div>
            </div>
        </div>

        <?php submit_button(__('Guardar Configuración', 'mi-renombrador-imagenes'), 'primary large', 'submit', true, ['id' => 'mri-save-settings']); ?>
    </form>

    <!-- Help Section -->
    <div class="mri-settings-section">
        <h2 class="section-title">
            <span class="dashicons dashicons-sos"></span>
            <?php esc_html_e('Ayuda y Recursos', 'mi-renombrador-imagenes'); ?>
        </h2>
        
        <div class="mri-help-grid">
            <div class="help-card">
                <h4>📚 <?php esc_html_e('Documentación', 'mi-renombrador-imagenes'); ?></h4>
                <p><?php esc_html_e('Consulta la documentación completa para configuración avanzada y solución de problemas.', 'mi-renombrador-imagenes'); ?></p>
                <a href="https://toctoc.ky/docs/seo-images" target="_blank" class="button"><?php esc_html_e('Ver Documentación', 'mi-renombrador-imagenes'); ?></a>
            </div>
            
            <div class="help-card">
                <h4>🤖 <?php esc_html_e('Google AI Studio', 'mi-renombrador-imagenes'); ?></h4>
                <p><?php esc_html_e('Obtén tu clave API gratuita y gestiona tu cuota de uso en Google AI Studio.', 'mi-renombrador-imagenes'); ?></p>
                <a href="https://aistudio.google.com/" target="_blank" class="button"><?php esc_html_e('Ir a AI Studio', 'mi-renombrador-imagenes'); ?></a>
            </div>
            
            <div class="help-card">
                <h4>🎯 <?php esc_html_e('Procesamiento Masivo', 'mi-renombrador-imagenes'); ?></h4>
                <p><?php esc_html_e('Procesa imágenes existentes en tu biblioteca de medios con las nuevas configuraciones.', 'mi-renombrador-imagenes'); ?></p>
                <a href="<?php echo esc_url(admin_url('upload.php?page=mri_bulk_process_page')); ?>" class="button button-primary"><?php esc_html_e('Procesar Imágenes', 'mi-renombrador-imagenes'); ?></a>
            </div>
        </div>
    </div>
</div>

<style>
/* Tab Navigation */
.mri-nav-tabs {
    margin: 20px 0;
    border-bottom: 1px solid #ccd0d4;
}

.mri-nav-tabs .nav-tab {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    text-decoration: none;
    border: 1px solid transparent;
    border-bottom: none;
    padding: 12px 16px;
    margin-bottom: -1px;
    color: #646970;
    transition: all 0.2s ease;
}

.mri-nav-tabs .nav-tab:hover {
    color: #2271b1;
    background: #f6f7f7;
}

.mri-nav-tabs .nav-tab.nav-tab-active {
    color: #1d2327;
    background: #fff;
    border-color: #ccd0d4 #ccd0d4 #fff;
}

/* Tab Content */
.mri-tab-content {
    display: none;
    padding: 20px 0;
}

.mri-tab-content.mri-tab-active {
    display: block;
}

/* Quality Slider */
.quality-display {
    display: inline-block;
    margin-left: 10px;
    font-weight: 600;
    color: #2271b1;
    min-width: 40px;
}

/* Help Grid */
.mri-help-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.help-card {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.help-card h4 {
    margin: 0 0 10px 0;
    color: #1d2327;
}

.help-card p {
    color: #646970;
    margin-bottom: 15px;
}

/* System Info */
.mri-system-info {
    margin: 20px 0;
}

.mri-system-info .widefat {
    max-width: 600px;
}

/* Configuration Preview */
.mri-config-preview {
    margin-top: 20px;
    padding: 15px;
    background: #f6f7f7;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
}

/* Feedback Messages */
.api-key-feedback,
.model-feedback {
    display: block;
    margin-top: 5px;
    font-size: 12px;
    font-style: italic;
}

.api-key-feedback.valid,
.model-feedback.valid {
    color: #00a32a;
}

.api-key-feedback.invalid,
.model-feedback.invalid {
    color: #d63638;
}

/* Required Fields */
.required {
    color: #d63638;
    font-weight: 600;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Tab navigation
    $('.mri-nav-tabs .nav-tab').on('click', function(e) {
        e.preventDefault();
        
        var target = $(this).attr('href');
        
        // Update tabs
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        
        // Update content
        $('.mri-tab-content').removeClass('mri-tab-active');
        $(target).addClass('mri-tab-active');
    });
    
    // Initialize first tab
    $('.mri-nav-tabs .nav-tab:first').click();
});
</script>
