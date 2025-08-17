<?php
/**
 * Bulk Processing Page Template
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
$needs_ia_config = $options['enable_ai_title'] || $options['enable_ai_alt'] || $options['enable_ai_caption'];
$can_run_something = $needs_ia_config || $options['enable_rename'] || $options['enable_alt'] || $options['enable_caption'] || $options['enable_compression'];
?>

<div class="wrap mri-admin-page" id="mri-bulk-wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <!-- Introduction Section -->
    <div class="mri-bulk-section mri-intro-section">
        <div class="intro-content">
            <div class="intro-text">
                <h2><?php esc_html_e('¿Qué hace esta herramienta?', 'mi-renombrador-imagenes'); ?></h2>
                <p><?php esc_html_e('Esta herramienta procesa todas las imágenes existentes en tu biblioteca de medios aplicando las configuraciones actuales del plugin:', 'mi-renombrador-imagenes'); ?></p>
                
                <ul class="feature-list">
                    <?php if ($options['enable_rename']) : ?>
                        <li>✅ <?php esc_html_e('Renombrado inteligente de archivos', 'mi-renombrador-imagenes'); ?></li>
                    <?php endif; ?>
                    <?php if ($options['enable_compression']) : ?>
                        <li>🗜️ <?php esc_html_e('Compresión automática sin pérdida visible', 'mi-renombrador-imagenes'); ?></li>
                    <?php endif; ?>
                    <?php if ($options['enable_ai_title']) : ?>
                        <li>🤖 <?php esc_html_e('Generación de títulos con IA', 'mi-renombrador-imagenes'); ?></li>
                    <?php endif; ?>
                    <?php if ($options['enable_ai_alt'] || $options['enable_alt']) : ?>
                        <li>♿ <?php esc_html_e('Generación de texto alternativo', 'mi-renombrador-imagenes'); ?></li>
                    <?php endif; ?>
                    <?php if ($options['enable_ai_caption'] || $options['enable_caption']) : ?>
                        <li>📝 <?php esc_html_e('Generación de leyendas descriptivas', 'mi-renombrador-imagenes'); ?></li>
                    <?php endif; ?>
                </ul>
            </div>
            
            <div class="intro-stats">
                <div class="stat-card">
                    <div class="stat-icon">🖼️</div>
                    <div class="stat-value" id="total-images-count">-</div>
                    <div class="stat-label"><?php esc_html_e('Imágenes en biblioteca', 'mi-renombrador-imagenes'); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">⚙️</div>
                    <div class="stat-value"><?php echo count(array_filter([
                        $options['enable_rename'],
                        $options['enable_compression'], 
                        $options['enable_ai_title'],
                        $options['enable_ai_alt'] || $options['enable_alt'],
                        $options['enable_ai_caption'] || $options['enable_caption']
                    ])); ?></div>
                    <div class="stat-label"><?php esc_html_e('Funciones activas', 'mi-renombrador-imagenes'); ?></div>
                </div>
            </div>
        </div>
    </div>

    <?php
    // Verificar configuración requerida
    if ($needs_ia_config && empty($options['gemini_api_key'])) : ?>
        <div class="notice notice-error">
            <p>
                <strong><?php esc_html_e('Error:', 'mi-renombrador-imagenes'); ?></strong>
                <?php printf(
                    wp_kses_post(__('La clave API de Google AI no está configurada. Ve a la <a href="%s">página de ajustes</a> para añadirla antes de poder procesar imágenes con IA.', 'mi-renombrador-imagenes')),
                    esc_url(admin_url('options-general.php?page=mri_google_ai_settings'))
                ); ?>
            </p>
        </div>
    <?php elseif (!$can_run_something) : ?>
        <div class="notice notice-warning">
            <p>
                <strong><?php esc_html_e('Advertencia:', 'mi-renombrador-imagenes'); ?></strong>
                <?php printf(
                    wp_kses_post(__('Ninguna función de procesamiento está activada en los <a href="%s">ajustes</a>. El procesado masivo no realizará ningún cambio.', 'mi-renombrador-imagenes')),
                    esc_url(admin_url('options-general.php?page=mri_google_ai_settings'))
                ); ?>
            </p>
        </div>
    <?php endif; ?>

    <?php
    // Advertencia sobre Imagick
    if ($options['enable_compression'] && $options['use_imagick_if_available'] && !(extension_loaded('imagick') && class_exists('Imagick'))) : ?>
        <div class="notice notice-info">
            <p>
                <strong><?php esc_html_e('Información:', 'mi-renombrador-imagenes'); ?></strong>
                <?php esc_html_e('La extensión Imagick no está disponible. La compresión usará GD como alternativa.', 'mi-renombrador-imagenes'); ?>
            </p>
        </div>
    <?php endif; ?>

    <!-- Processing Options -->
    <div class="mri-bulk-section">
        <h2><?php esc_html_e('Opciones de Procesamiento', 'mi-renombrador-imagenes'); ?></h2>
        
        <div id="mri-bulk-options">
            <div class="option-group">
                <label for="mri-criteria" class="option-label">
                    <input type="checkbox" id="mri-criteria" name="mri-criteria" value="missing_alt">
                    <span class="checkmark"></span>
                    <span class="label-text">
                        <strong><?php esc_html_e('Procesar solo imágenes sin texto alternativo', 'mi-renombrador-imagenes'); ?></strong>
                        <small><?php esc_html_e('Recomendado para la primera ejecución. Solo procesará imágenes que no tengan Alt Text definido.', 'mi-renombrador-imagenes'); ?></small>
                    </span>
                </label>
            </div>
            
            <div class="info-box">
                <h4>ℹ️ <?php esc_html_e('Información importante:', 'mi-renombrador-imagenes'); ?></h4>
                <ul>
                    <li><?php esc_html_e('Si no marcas la opción anterior, se procesarán TODAS las imágenes de la biblioteca.', 'mi-renombrador-imagenes'); ?></li>
                    <li><?php esc_html_e('Los ajustes "Sobrescribir" en la configuración determinarán si se reemplazan los metadatos existentes.', 'mi-renombrador-imagenes'); ?></li>
                    <li><?php esc_html_e('La compresión se aplicará a todas las imágenes compatibles si está activada.', 'mi-renombrador-imagenes'); ?></li>
                    <li><?php esc_html_e('Se recomienda hacer una copia de seguridad antes del procesamiento masivo.', 'mi-renombrador-imagenes'); ?></li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Processing Controls -->
    <div class="mri-bulk-section">
        <h2><?php esc_html_e('Control de Procesamiento', 'mi-renombrador-imagenes'); ?></h2>
        
        <div id="mri-bulk-controls">
            <button type="button" id="mri-start-processing" class="button button-primary button-hero" <?php echo (!$can_run_something || ($needs_ia_config && empty($options['gemini_api_key']))) ? 'disabled' : ''; ?>>
                <span class="dashicons dashicons-controls-play"></span>
                <?php esc_html_e('Iniciar Procesamiento', 'mi-renombrador-imagenes'); ?>
            </button>
            
            <button type="button" id="mri-stop-processing" class="button button-secondary" style="display: none;">
                <span class="dashicons dashicons-controls-pause"></span>
                <?php esc_html_e('Detener Procesamiento', 'mi-renombrador-imagenes'); ?>
            </button>
            
            <span class="spinner" id="mri-bulk-spinner"></span>
            
            <div class="processing-info">
                <small><?php esc_html_e('El procesamiento se ejecuta en lotes para evitar timeouts del servidor.', 'mi-renombrador-imagenes'); ?></small>
            </div>
        </div>
    </div>

    <!-- Progress Section -->
    <div id="mri-bulk-progress" class="mri-bulk-section" style="display: none;">
        <h3><?php esc_html_e('Progreso del Procesamiento', 'mi-renombrador-imagenes'); ?></h3>
        
        <div class="progress-container">
            <div class="progress-header">
                <label for="mri-progress-bar"><?php esc_html_e('Progreso:', 'mi-renombrador-imagenes'); ?></label>
                <span id="mri-progress-percentage">0%</span>
            </div>
            <progress id="mri-progress-bar" value="0" max="100"></progress>
            <p id="mri-progress-text">0 / 0</p>
            
            <div class="progress-stats">
                <div class="stat-item">
                    <span class="stat-label"><?php esc_html_e('Tiempo transcurrido:', 'mi-renombrador-imagenes'); ?></span>
                    <span id="elapsed-time">00:00</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label"><?php esc_html_e('Tiempo estimado restante:', 'mi-renombrador-imagenes'); ?></span>
                    <span id="estimated-time">--:--</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label"><?php esc_html_e('Velocidad:', 'mi-renombrador-imagenes'); ?></span>
                    <span id="processing-speed">-- img/min</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Log Section -->
    <div id="mri-bulk-log" class="mri-bulk-section" style="display: none;">
        <div class="log-header">
            <h3><?php esc_html_e('Registro de Actividad', 'mi-renombrador-imagenes'); ?></h3>
            <div class="log-controls">
                <button type="button" id="mri-clear-log" class="button button-small">
                    <span class="dashicons dashicons-trash"></span>
                    <?php esc_html_e('Limpiar', 'mi-renombrador-imagenes'); ?>
                </button>
                <button type="button" id="mri-download-log" class="button button-small">
                    <span class="dashicons dashicons-download"></span>
                    <?php esc_html_e('Descargar', 'mi-renombrador-imagenes'); ?>
                </button>
            </div>
        </div>
        
        <div class="log-legend">
            <span class="legend-item success">✅ Éxito</span>
            <span class="legend-item error">❌ Error</span>
            <span class="legend-item notice">⚠️ Advertencia</span>
            <span class="legend-item info">ℹ️ Información</span>
        </div>
        
        <div class="log-container">
            <ul id="mri-log-list"></ul>
        </div>
    </div>

    <!-- Summary Section -->
    <div id="mri-bulk-summary" class="mri-bulk-section" style="display: none;">
        <h3><?php esc_html_e('Resumen del Procesamiento', 'mi-renombrador-imagenes'); ?></h3>
        
        <div class="summary-stats">
            <div class="summary-card success">
                <div class="summary-icon">✅</div>
                <div class="summary-content">
                    <div class="summary-number" id="success-count">0</div>
                    <div class="summary-label"><?php esc_html_e('Procesadas con éxito', 'mi-renombrador-imagenes'); ?></div>
                </div>
            </div>
            
            <div class="summary-card error">
                <div class="summary-icon">❌</div>
                <div class="summary-content">
                    <div class="summary-number" id="error-count">0</div>
                    <div class="summary-label"><?php esc_html_e('Errores encontrados', 'mi-renombrador-imagenes'); ?></div>
                </div>
            </div>
            
            <div class="summary-card time">
                <div class="summary-icon">⏱️</div>
                <div class="summary-content">
                    <div class="summary-number" id="total-time">0:00</div>
                    <div class="summary-label"><?php esc_html_e('Tiempo total', 'mi-renombrador-imagenes'); ?></div>
                </div>
            </div>
        </div>
        
        <div class="summary-actions">
            <button type="button" id="mri-process-more" class="button button-primary">
                <span class="dashicons dashicons-update"></span>
                <?php esc_html_e('Procesar Más Imágenes', 'mi-renombrador-imagenes'); ?>
            </button>
            
            <a href="<?php echo esc_url(admin_url('upload.php')); ?>" class="button">
                <span class="dashicons dashicons-admin-media"></span>
                <?php esc_html_e('Ver Biblioteca de Medios', 'mi-renombrador-imagenes'); ?>
            </a>
            
            <a href="<?php echo esc_url(admin_url('options-general.php?page=mri_google_ai_settings')); ?>" class="button">
                <span class="dashicons dashicons-admin-settings"></span>
                <?php esc_html_e('Ajustar Configuración', 'mi-renombrador-imagenes'); ?>
            </a>
        </div>
    </div>

    <!-- Help Section -->
    <div class="mri-bulk-section mri-help-section">
        <h3><?php esc_html_e('¿Necesitas ayuda?', 'mi-renombrador-imagenes'); ?></h3>
        
        <div class="help-grid">
            <div class="help-item">
                <div class="help-icon">📚</div>
                <h4><?php esc_html_e('Documentación', 'mi-renombrador-imagenes'); ?></h4>
                <p><?php esc_html_e('Consulta la guía completa de procesamiento masivo.', 'mi-renombrador-imagenes'); ?></p>
                <a href="https://toctoc.ky/docs/bulk-processing" target="_blank" class="button button-small"><?php esc_html_e('Ver Guía', 'mi-renombrador-imagenes'); ?></a>
            </div>
            
            <div class="help-item">
                <div class="help-icon">🔧</div>
                <h4><?php esc_html_e('Configuración', 'mi-renombrador-imagenes'); ?></h4>
                <p><?php esc_html_e('Ajusta las opciones de procesamiento antes de comenzar.', 'mi-renombrador-imagenes'); ?></p>
                <a href="<?php echo esc_url(admin_url('options-general.php?page=mri_google_ai_settings')); ?>" class="button button-small"><?php esc_html_e('Ir a Ajustes', 'mi-renombrador-imagenes'); ?></a>
            </div>
            
            <div class="help-item">
                <div class="help-icon">💾</div>
                <h4><?php esc_html_e('Copia de Seguridad', 'mi-renombrador-imagenes'); ?></h4>
                <p><?php esc_html_e('Se recomienda hacer backup antes del procesamiento masivo.', 'mi-renombrador-imagenes'); ?></p>
                <a href="https://toctoc.ky/docs/backup-guide" target="_blank" class="button button-small"><?php esc_html_e('Guía Backup', 'mi-renombrador-imagenes'); ?></a>
            </div>
        </div>
    </div>

    <?php wp_nonce_field('mri_bulk_process_nonce', 'mri_bulk_nonce'); ?>
</div>

<style>
/* Bulk Processing Specific Styles */
.mri-bulk-section {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 6px;
    margin-bottom: 20px;
    padding: 24px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
}

.mri-bulk-section h2 {
    margin-top: 0;
    color: #1d2327;
    font-size: 20px;
    font-weight: 600;
    border-bottom: 2px solid #f0f0f1;
    padding-bottom: 12px;
}

.mri-bulk-section h3 {
    color: #1d2327;
    font-size: 18px;
    font-weight: 600;
    margin-top: 0;
}

/* Introduction Section */
.intro-content {
    display: flex;
    gap: 30px;
    align-items: flex-start;
}

.intro-text {
    flex: 2;
}

.intro-stats {
    flex: 1;
    display: flex;
    gap: 15px;
}

.feature-list {
    list-style: none;
    padding: 0;
    margin: 16px 0;
}

.feature-list li {
    padding: 8px 0;
    border-bottom: 1px solid #f0f0f1;
    font-size: 14px;
}

.feature-list li:last-child {
    border-bottom: none;
}

/* Stat Cards */
.stat-card {
    background: linear-gradient(135deg, #2271b1 0%, #135e96 100%);
    color: white;
    text-align: center;
    padding: 20px 15px;
    border-radius: 8px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    transition: transform 0.2s ease;
}

.stat-card:hover {
    transform: translateY(-2px);
}

.stat-card .stat-icon {
    font-size: 24px;
    margin-bottom: 8px;
}

.stat-card .stat-value {
    font-size: 32px;
    font-weight: 700;
    line-height: 1;
    margin-bottom: 4px;
}

.stat-card .stat-label {
    font-size: 12px;
    opacity: 0.9;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Options Section */
.option-group {
    margin-bottom: 20px;
}

.option-label {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    cursor: pointer;
    padding: 16px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    transition: all 0.2s ease;
}

.option-label:hover {
    border-color: #2271b1;
    background: #f6f7f7;
}

.option-label input[type="checkbox"] {
    margin: 0;
    transform: scale(1.2);
}

.label-text strong {
    display: block;
    color: #1d2327;
    margin-bottom: 4px;
}

.label-text small {
    color: #646970;
    font-style: italic;
}

.info-box {
    background: #e7f3ff;
    border: 1px solid #72aee6;
    border-radius: 6px;
    padding: 16px;
    margin-top: 20px;
}

.info-box h4 {
    margin: 0 0 12px 0;
    color: #0073aa;
}

.info-box ul {
    margin: 0;
    padding-left: 20px;
}

.info-box li {
    margin-bottom: 8px;
    color: #0073aa;
}

/* Controls Section */
#mri-bulk-controls {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 20px;
}

.button-hero {
    padding: 12px 24px !important;
    font-size: 16px !important;
    font-weight: 600 !important;
    height: auto !important;
    display: inline-flex !important;
    align-items: center !important;
    gap: 8px !important;
}

.processing-info {
    margin-left: auto;
}

/* Progress Section */
.progress-container {
    background: #f6f7f7;
    border: 1px solid #c3c4c7;
    border-radius: 6px;
    padding: 20px;
}

.progress-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
}

.progress-header label {
    font-weight: 600;
    color: #1d2327;
}

#mri-progress-percentage {
    font-size: 18px;
    font-weight: 700;
    color: #2271b1;
}

#mri-progress-bar {
    width: 100%;
    height: 28px;
    border-radius: 14px;
    appearance: none;
    margin-bottom: 12px;
}

#mri-progress-bar::-webkit-progress-bar {
    background-color: #e0e0e0;
    border-radius: 14px;
}

#mri-progress-bar::-webkit-progress-value {
    background: linear-gradient(90deg, #2271b1, #00a32a);
    border-radius: 14px;
    transition: width 0.3s ease;
}

#mri-progress-text {
    text-align: center;
    font-weight: 600;
    font-size: 16px;
    color: #1d2327;
    margin: 0 0 16px 0;
}

.progress-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 16px;
    border-top: 1px solid #ddd;
    padding-top: 16px;
}

.stat-item {
    text-align: center;
}

.stat-label {
    display: block;
    font-size: 12px;
    color: #646970;
    margin-bottom: 4px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.stat-item span:last-child {
    font-size: 16px;
    font-weight: 600;
    color: #1d2327;
}

/* Log Section */
.log-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
}

.log-controls {
    display: flex;
    gap: 8px;
}

.log-legend {
    display: flex;
    gap: 16px;
    margin-bottom: 16px;
    padding: 8px 12px;
    background: #f0f0f1;
    border-radius: 4px;
    font-size: 12px;
}

.legend-item {
    font-weight: 600;
}

.legend-item.success { color: #00a32a; }
.legend-item.error { color: #d63638; }
.legend-item.notice { color: #dba617; }
.legend-item.info { color: #2271b1; }

.log-container {
    background: #1e1e1e;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 16px;
    height: 300px;
    overflow-y: auto;
    font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
    font-size: 13px;
    line-height: 1.6;
}

#mri-log-list {
    list-style: none;
    padding: 0;
    margin: 0;
    color: #f0f0f0;
}

#mri-log-list li {
    margin-bottom: 4px;
    padding: 4px 8px;
    border-radius: 3px;
    border-left: 3px solid transparent;
}

#mri-log-list li strong {
    color: #72aee6;
}

#mri-log-list li.mri-log-success {
    background: rgba(0, 163, 42, 0.1);
    border-left-color: #00a32a;
    color: #4ade80;
}

#mri-log-list li.mri-log-error {
    background: rgba(214, 54, 56, 0.1);
    border-left-color: #d63638;
    color: #f87171;
}

#mri-log-list li.mri-log-notice {
    background: rgba(219, 166, 23, 0.1);
    border-left-color: #dba617;
    color: #fbbf24;
}

#mri-log-list li.mri-log-info {
    background: rgba(114, 174, 230, 0.1);
    border-left-color: #72aee6;
    color: #60a5fa;
}

/* Summary Section */
.summary-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 24px;
}

.summary-card {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.summary-card.success {
    background: linear-gradient(135deg, #00a32a, #059669);
    color: white;
}

.summary-card.error {
    background: linear-gradient(135deg, #d63638, #dc2626);
    color: white;
}

.summary-card.time {
    background: linear-gradient(135deg, #2271b1, #1e40af);
    color: white;
}

.summary-icon {
    font-size: 32px;
}

.summary-number {
    font-size: 28px;
    font-weight: 700;
    line-height: 1;
}

.summary-label {
    font-size: 14px;
    opacity: 0.9;
}

.summary-actions {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

/* Help Section */
.help-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.help-item {
    text-align: center;
    padding: 20px;
    border: 1px solid #e0e0e0;
    border-radius: 6px;
    transition: all 0.2s ease;
}

.help-item:hover {
    border-color: #2271b1;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.help-icon {
    font-size: 32px;
    margin-bottom: 12px;
}

.help-item h4 {
    margin: 0 0 8px 0;
    color: #1d2327;
}

.help-item p {
    color: #646970;
    margin-bottom: 16px;
    font-size: 14px;
}

/* Responsive */
@media (max-width: 768px) {
    .intro-content {
        flex-direction: column;
    }
    
    .intro-stats {
        flex-direction: column;
    }
    
    #mri-bulk-controls {
        flex-direction: column;
        align-items: stretch;
    }
    
    .button-hero {
        text-align: center;
    }
    
    .progress-stats {
        grid-template-columns: 1fr;
    }
    
    .summary-stats {
        grid-template-columns: 1fr;
    }
    
    .summary-actions {
        flex-direction: column;
    }
    
    .help-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Cargar estadísticas iniciales
    $.post(ajaxurl, {
        action: 'mri_get_total_images',
        nonce: '<?php echo wp_create_nonce('mri_bulk_process_nonce'); ?>',
        criteria: 'all'
    }, function(response) {
        if (response.success) {
            $('#total-images-count').text(response.data.total);
        }
    });
    
    // Limpiar log
    $('#mri-clear-log').on('click', function() {
        $('#mri-log-list').empty();
    });
    
    // Descargar log
    $('#mri-download-log').on('click', function() {
        const logContent = $('#mri-log-list').text();
        const blob = new Blob([logContent], { type: 'text/plain' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'mri-bulk-processing-log.txt';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
    });
    
    // Procesar más imágenes
    $('#mri-process-more').on('click', function() {
        location.reload();
    });
});
</script>
