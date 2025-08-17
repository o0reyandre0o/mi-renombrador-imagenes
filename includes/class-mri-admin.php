<?php
/**
 * Admin Class - WordPress Administration Interface
 * Toc Toc SEO Images Plugin
 * 
 * @package TocTocSEOImages
 * @version 3.6.0
 */

namespace TocTocMarketing\SEOImages;

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para manejo de la interfaz de administración
 * 
 * Gestiona páginas de admin, configuraciones, scripts y estilos
 */
class MRI_Admin {

    /**
     * Opciones del plugin
     * 
     * @var array
     */
    private $options;

    /**
     * Slug de la página de configuración
     * 
     * @var string
     */
    private $settings_page_slug = 'mri_google_ai_settings';

    /**
     * Slug de la página de procesamiento masivo
     * 
     * @var string
     */
    private $bulk_page_slug = 'mri_bulk_process_page';

    /**
     * Constructor
     * 
     * @param array $options Opciones del plugin
     */
    public function __construct($options) {
        $this->options = $options;
        $this->init_hooks();
    }

    /**
     * Inicializar hooks
     */
    private function init_hooks() {
        add_action('admin_menu', [$this, 'add_admin_pages']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        add_action('admin_notices', [$this, 'show_admin_notices']);
        add_action('admin_init', [$this, 'maybe_show_welcome_notice']);
        
        // Agregar enlaces en la página de plugins
        add_filter('plugin_action_links_' . plugin_basename(MRI_PLUGIN_FILE), [$this, 'add_plugin_action_links']);
        add_filter('plugin_row_meta', [$this, 'add_plugin_row_meta'], 10, 2);
    }

    /**
     * Añadir páginas de administración
     */
    public function add_admin_pages() {
        // Página principal de configuración
        add_options_page(
            __('Toc Toc SEO Images - Configuración', 'mi-renombrador-imagenes'),
            __('SEO Images IA', 'mi-renombrador-imagenes'),
            'manage_options',
            $this->settings_page_slug,
            [$this, 'render_settings_page']
        );

        // Página de procesamiento masivo
        add_media_page(
            __('Procesar Imágenes Antiguas con IA', 'mi-renombrador-imagenes'),
            __('Procesar con IA', 'mi-renombrador-imagenes'),
            'manage_options',
            $this->bulk_page_slug,
            [$this, 'render_bulk_process_page']
        );

        // Página de estadísticas (submenu oculto)
        add_submenu_page(
            null, // Parent slug null = página oculta
            __('Estadísticas SEO Images', 'mi-renombrador-imagenes'),
            __('Estadísticas', 'mi-renombrador-imagenes'),
            'manage_options',
            'mri_stats_page',
            [$this, 'render_stats_page']
        );
    }

    /**
     * Registrar configuraciones
     */
    public function register_settings() {
        register_setting(
            'mri_google_ai_options_group',
            MRI_SETTINGS_OPTION_NAME,
            [$this, 'sanitize_options']
        );

        // Sección General
        add_settings_section(
            'mri_general_section',
            __('Configuración General', 'mi-renombrador-imagenes'),
            [$this, 'general_section_callback'],
            $this->settings_page_slug
        );

        // Sección de Compresión
        add_settings_section(
            'mri_compression_section',
            __('Compresión de Imágenes', 'mi-renombrador-imagenes'),
            [$this, 'compression_section_callback'],
            $this->settings_page_slug
        );

        // Sección de IA
        add_settings_section(
            'mri_ai_section',
            __('Inteligencia Artificial (Google AI)', 'mi-renombrador-imagenes'),
            [$this, 'ai_section_callback'],
            $this->settings_page_slug
        );

        $this->register_general_fields();
        $this->register_compression_fields();
        $this->register_ai_fields();
    }

    /**
     * Registrar campos de configuración general
     */
    private function register_general_fields() {
        $fields = [
            'enable_rename' => [
                'title' => __('Renombrado Automático', 'mi-renombrador-imagenes'),
                'type' => 'checkbox',
                'description' => __('Renombrar archivos automáticamente usando título de página/producto e imagen.', 'mi-renombrador-imagenes')
            ],
            'enable_alt' => [
                'title' => __('Texto Alternativo', 'mi-renombrador-imagenes'),
                'type' => 'checkbox',
                'description' => __('Generar automáticamente texto alternativo para accesibilidad y SEO.', 'mi-renombrador-imagenes')
            ],
            'overwrite_alt' => [
                'title' => __('Sobrescribir Alt Existente', 'mi-renombrador-imagenes'),
                'type' => 'checkbox',
                'description' => __('Reemplazar texto alternativo existente. Si no, solo se añade cuando está vacío.', 'mi-renombrador-imagenes')
            ],
            'enable_caption' => [
                'title' => __('Leyendas de Imagen', 'mi-renombrador-imagenes'),
                'type' => 'checkbox',
                'description' => __('Generar automáticamente leyendas descriptivas para las imágenes.', 'mi-renombrador-imagenes')
            ],
            'overwrite_caption' => [
                'title' => __('Sobrescribir Leyenda Existente', 'mi-renombrador-imagenes'),
                'type' => 'checkbox',
                'description' => __('Reemplazar leyendas existentes. Si no, solo se añade cuando está vacía.', 'mi-renombrador-imagenes')
            ]
        ];

        foreach ($fields as $field_id => $field_config) {
            add_settings_field(
                $field_id,
                $field_config['title'],
                [$this, 'render_field'],
                $this->settings_page_slug,
                'mri_general_section',
                array_merge($field_config, ['id' => $field_id])
            );
        }
    }

    /**
     * Registrar campos de compresión
     */
    private function register_compression_fields() {
        $fields = [
            'enable_compression' => [
                'title' => __('Compresión Automática', 'mi-renombrador-imagenes'),
                'type' => 'checkbox',
                'description' => __('Comprimir imágenes automáticamente para reducir tamaño sin pérdida visible.', 'mi-renombrador-imagenes')
            ],
            'jpeg_quality' => [
                'title' => __('Calidad JPEG/WebP', 'mi-renombrador-imagenes'),
                'type' => 'number',
                'min' => 60,
                'max' => 100,
                'step' => 1,
                'description' => __('Nivel de calidad para JPEG y WebP (60-100). Recomendado: 82-90.', 'mi-renombrador-imagenes')
            ],
            'use_imagick_if_available' => [
                'title' => __('Usar Imagick', 'mi-renombrador-imagenes'),
                'type' => 'checkbox',
                'description' => __('Priorizar Imagick cuando esté disponible (mejores resultados que GD).', 'mi-renombrador-imagenes')
            ]
        ];

        foreach ($fields as $field_id => $field_config) {
            add_settings_field(
                $field_id,
                $field_config['title'],
                [$this, 'render_field'],
                $this->settings_page_slug,
                'mri_compression_section',
                array_merge($field_config, ['id' => $field_id])
            );
        }
    }

    /**
     * Registrar campos de IA
     */
    private function register_ai_fields() {
        $fields = [
            'gemini_api_key' => [
                'title' => __('Clave API Google AI', 'mi-renombrador-imagenes'),
                'type' => 'password',
                'description' => sprintf(
                    __('Obtén tu clave API gratuita en %s', 'mi-renombrador-imagenes'),
                    '<a href="https://aistudio.google.com/" target="_blank">Google AI Studio</a>'
                )
            ],
            'gemini_model' => [
                'title' => __('Modelo Gemini', 'mi-renombrador-imagenes'),
                'type' => 'select',
                'options' => [
                    'gemini-1.5-flash-latest' => 'Gemini 1.5 Flash (Recomendado)',
                    'gemini-1.5-pro-latest' => 'Gemini 1.5 Pro (Más Potente)',
                    'gemini-pro-vision' => 'Gemini Pro Vision (Anterior)'
                ],
                'description' => __('Modelo de Google AI a utilizar. Los modelos 1.5 son más avanzados.', 'mi-renombrador-imagenes')
            ],
            'ai_output_language' => [
                'title' => __('Idioma de Salida IA', 'mi-renombrador-imagenes'),
                'type' => 'select',
                'options' => $this->get_supported_languages(),
                'description' => __('Idioma en el que la IA generará títulos, alt text y leyendas.', 'mi-renombrador-imagenes')
            ],
            'enable_ai_title' => [
                'title' => __('Título con IA', 'mi-renombrador-imagenes'),
                'type' => 'checkbox',
                'description' => __('Generar títulos analizando la imagen con Google AI Vision.', 'mi-renombrador-imagenes')
            ],
            'overwrite_title' => [
                'title' => __('Sobrescribir Título', 'mi-renombrador-imagenes'),
                'type' => 'checkbox',
                'description' => __('Reemplazar títulos existentes con los generados por IA.', 'mi-renombrador-imagenes')
            ],
            'enable_ai_alt' => [
                'title' => __('Alt Text con IA', 'mi-renombrador-imagenes'),
                'type' => 'checkbox',
                'description' => __('Generar texto alternativo analizando la imagen con Google AI.', 'mi-renombrador-imagenes')
            ],
            'enable_ai_caption' => [
                'title' => __('Leyenda con IA', 'mi-renombrador-imagenes'),
                'type' => 'checkbox',
                'description' => __('Generar leyendas analizando la imagen con Google AI.', 'mi-renombrador-imagenes')
            ],
            'include_seo_in_ai_prompt' => [
                'title' => __('Contexto SEO en IA', 'mi-renombrador-imagenes'),
                'type' => 'checkbox',
                'description' => __('Incluir título de página/producto y keywords en los prompts de IA.', 'mi-renombrador-imagenes')
            ]
        ];

        foreach ($fields as $field_id => $field_config) {
            add_settings_field(
                $field_id,
                $field_config['title'],
                [$this, 'render_field'],
                $this->settings_page_slug,
                'mri_ai_section',
                array_merge($field_config, ['id' => $field_id])
            );
        }
    }

    /**
     * Renderizar campo de configuración
     * 
     * @param array $args Argumentos del campo
     */
    public function render_field($args) {
        $field_id = $args['id'];
        $field_type = $args['type'];
        $field_name = MRI_SETTINGS_OPTION_NAME . '[' . $field_id . ']';
        $field_value = isset($this->options[$field_id]) ? $this->options[$field_id] : '';
        $description = isset($args['description']) ? $args['description'] : '';

        switch ($field_type) {
            case 'checkbox':
                printf(
                    '<label for="%1$s"><input type="checkbox" id="%1$s" name="%2$s" value="1" %3$s /> %4$s</label>',
                    esc_attr($field_id),
                    esc_attr($field_name),
                    checked(1, $field_value, false),
                    esc_html($description)
                );
                break;

            case 'password':
                printf(
                    '<input type="password" id="%1$s" name="%2$s" value="%3$s" class="regular-text" autocomplete="off" />',
                    esc_attr($field_id),
                    esc_attr($field_name),
                    esc_attr($field_value)
                );
                if ($description) {
                    echo '<p class="description">' . wp_kses_post($description) . '</p>';
                }
                break;

            case 'number':
                printf(
                    '<input type="number" id="%1$s" name="%2$s" value="%3$s" class="small-text" min="%4$s" max="%5$s" step="%6$s" />',
                    esc_attr($field_id),
                    esc_attr($field_name),
                    esc_attr($field_value),
                    esc_attr($args['min'] ?? 0),
                    esc_attr($args['max'] ?? 100),
                    esc_attr($args['step'] ?? 1)
                );
                if ($description) {
                    echo '<p class="description">' . wp_kses_post($description) . '</p>';
                }
                break;

            case 'select':
                printf('<select id="%1$s" name="%2$s">', esc_attr($field_id), esc_attr($field_name));
                foreach ($args['options'] as $option_value => $option_label) {
                    printf(
                        '<option value="%1$s" %2$s>%3$s</option>',
                        esc_attr($option_value),
                        selected($field_value, $option_value, false),
                        esc_html($option_label)
                    );
                }
                echo '</select>';
                if ($description) {
                    echo '<p class="description">' . wp_kses_post($description) . '</p>';
                }
                break;

            default:
                printf(
                    '<input type="text" id="%1$s" name="%2$s" value="%3$s" class="regular-text" />',
                    esc_attr($field_id),
                    esc_attr($field_name),
                    esc_attr($field_value)
                );
                if ($description) {
                    echo '<p class="description">' . wp_kses_post($description) . '</p>';
                }
                break;
        }
    }

    /**
     * Sanitizar opciones
     * 
     * @param array $input Datos de entrada
     * @return array Datos sanitizados
     */
    public function sanitize_options($input) {
        $sanitized = [];
        $defaults = MRI_Core::get_instance()->get_options();

        foreach ($defaults as $key => $default_value) {
            if ($key === 'gemini_api_key' || $key === 'gemini_model') {
                $sanitized[$key] = isset($input[$key]) ? sanitize_text_field(trim($input[$key])) : '';
            } elseif ($key === 'ai_output_language') {
                $submitted_lang = isset($input[$key]) ? sanitize_key($input[$key]) : $defaults['ai_output_language'];
                $supported_languages = $this->get_supported_languages();
                $sanitized[$key] = array_key_exists($submitted_lang, $supported_languages) ? $submitted_lang : $defaults['ai_output_language'];
            } elseif ($key === 'jpeg_quality') {
                $quality = isset($input[$key]) ? absint($input[$key]) : $defaults['jpeg_quality'];
                $sanitized[$key] = max(60, min(100, $quality));
            } else {
                // Checkboxes y otros campos
                $sanitized[$key] = isset($input[$key]) && $input[$key] == 1 ? 1 : 0;
            }
        }

        // Validaciones y advertencias
        $this->validate_and_warn($sanitized);

        return $sanitized;
    }

    /**
     * Validar configuración y mostrar advertencias
     * 
     * @param array $options Opciones sanitizadas
     */
    private function validate_and_warn($options) {
        // Verificar configuración de IA
        $needs_ai = $options['enable_ai_title'] || $options['enable_ai_alt'] || $options['enable_ai_caption'];
        
        if ($needs_ai && empty($options['gemini_api_key'])) {
            add_settings_error(
                MRI_SETTINGS_OPTION_NAME,
                'missing_api_key',
                __('Se ha activado una función de IA pero no se ha introducido la Clave API de Google AI.', 'mi-renombrador-imagenes'),
                'warning'
            );
        }

        if ($needs_ai && empty($options['gemini_model'])) {
            add_settings_error(
                MRI_SETTINGS_OPTION_NAME,
                'missing_model',
                __('Se ha activado una función de IA pero no se ha especificado un Modelo Gemini.', 'mi-renombrador-imagenes'),
                'warning'
            );
        }

        // Verificar compatibilidad del modelo
        if ($needs_ai && !empty($options['gemini_model'])) {
            $model_lower = strtolower($options['gemini_model']);
            if (strpos($model_lower, 'gemini-1.5') === false && strpos($model_lower, 'vision') === false) {
                add_settings_error(
                    MRI_SETTINGS_OPTION_NAME,
                    'non_multimodal_model',
                    sprintf(
                        __('El modelo seleccionado (%s) podría no ser multimodal. Se recomienda usar gemini-1.5-flash-latest.', 'mi-renombrador-imagenes'),
                        esc_html($options['gemini_model'])
                    ),
                    'warning'
                );
            }
        }

        // Verificar Imagick
        if ($options['enable_compression'] && $options['use_imagick_if_available'] && 
            !(extension_loaded('imagick') && class_exists('Imagick'))) {
            add_settings_error(
                MRI_SETTINGS_OPTION_NAME,
                'imagick_not_found',
                __('Se ha seleccionado usar Imagick pero no está disponible. Se usará GD como alternativa.', 'mi-renombrador-imagenes'),
                'info'
            );
        }
    }

    /**
     * Enqueue scripts y estilos de administración
     * 
     * @param string $hook_suffix Sufijo de la página actual
     */
    public function enqueue_admin_scripts($hook_suffix) {
        // Scripts para página de configuración
        if ('settings_page_' . $this->settings_page_slug === $hook_suffix) {
            wp_enqueue_script(
                'mri-admin-settings',
                plugin_dir_url(MRI_PLUGIN_FILE) . 'assets/js/admin-settings.js',
                ['jquery'],
                MRI_Core::get_instance()->get_version(),
                true
            );

            wp_localize_script('mri-admin-settings', 'mri_settings_params', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('mri_settings_nonce'),
                'debug' => defined('WP_DEBUG') && WP_DEBUG
            ]);
        }

        // Scripts para página de procesamiento masivo
        if ('media_page_' . $this->bulk_page_slug === $hook_suffix) {
            wp_enqueue_script(
                'mri-admin-batch',
                plugin_dir_url(MRI_PLUGIN_FILE) . 'assets/js/admin-batch.js',
                ['jquery'],
                MRI_Core::get_instance()->get_version(),
                true
            );

            wp_localize_script('mri-admin-batch', 'mri_bulk_params', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('mri_bulk_process_nonce'),
                'text_start' => __('Iniciar Procesamiento', 'mi-renombrador-imagenes'),
                'text_stop' => __('Detener Procesamiento', 'mi-renombrador-imagenes'),
                'text_stopping' => __('Deteniendo...', 'mi-renombrador-imagenes'),
                'text_processing' => __('Procesando...', 'mi-renombrador-imagenes'),
                'text_complete' => __('Procesamiento completado.', 'mi-renombrador-imagenes'),
                'text_error' => __('Ocurrió un error.', 'mi-renombrador-imagenes'),
                'text_confirm_stop' => __('¿Estás seguro de que quieres detener el procesamiento?', 'mi-renombrador-imagenes'),
                'action_total' => 'mri_get_total_images',
                'action_batch' => 'mri_process_batch'
            ]);
        }

        // Estilos para todas las páginas del plugin
        if (strpos($hook_suffix, 'mri_') !== false || 
            strpos($hook_suffix, $this->settings_page_slug) !== false ||
            strpos($hook_suffix, $this->bulk_page_slug) !== false) {
            
            wp_enqueue_style(
                'mri-admin-styles',
                plugin_dir_url(MRI_PLUGIN_FILE) . 'assets/css/admin-styles.css',
                [],
                MRI_Core::get_instance()->get_version()
            );
        }
    }

    /**
     * Renderizar página de configuración
     */
    public function render_settings_page() {
        include_once plugin_dir_path(MRI_PLUGIN_FILE) . 'templates/admin-settings.php';
    }

    /**
     * Renderizar página de procesamiento masivo
     */
    public function render_bulk_process_page() {
        include_once plugin_dir_path(MRI_PLUGIN_FILE) . 'templates/admin-bulk-process.php';
    }

    /**
     * Renderizar página de estadísticas
     */
    public function render_stats_page() {
        // Obtener estadísticas
        $ai_stats = MRI_Core::get_instance()->get_component('ai_processor')->get_usage_stats();
        $compression_stats = MRI_Core::get_instance()->get_component('compressor')->get_compression_stats();
        
        ?>
        <div class="wrap mri-admin-page">
            <h1><?php esc_html_e('Estadísticas SEO Images', 'mi-renombrador-imagenes'); ?></h1>
            
            <div class="mri-stats-container">
                <div class="stat-card">
                    <div class="stat-icon">🤖</div>
                    <div class="stat-value"><?php echo number_format($ai_stats['total_requests']); ?></div>
                    <div class="stat-label"><?php esc_html_e('Peticiones IA Total', 'mi-renombrador-imagenes'); ?></div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">✅</div>
                    <div class="stat-value"><?php echo number_format($ai_stats['successful_requests']); ?></div>
                    <div class="stat-label"><?php esc_html_e('IA Exitosas', 'mi-renombrador-imagenes'); ?></div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">🗜️</div>
                    <div class="stat-value"><?php echo number_format($compression_stats['total_compressed']); ?></div>
                    <div class="stat-label"><?php esc_html_e('Imágenes Comprimidas', 'mi-renombrador-imagenes'); ?></div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">💾</div>
                    <div class="stat-value"><?php echo size_format($compression_stats['total_bytes_saved']); ?></div>
                    <div class="stat-label"><?php esc_html_e('Espacio Ahorrado', 'mi-renombrador-imagenes'); ?></div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Callbacks para secciones
     */
    public function general_section_callback() {
        echo '<p>' . esc_html__('Configura las opciones básicas de procesamiento automático.', 'mi-renombrador-imagenes') . '</p>';
    }

    public function compression_section_callback() {
        echo '<p>' . esc_html__('Optimiza automáticamente las imágenes para reducir su tamaño.', 'mi-renombrador-imagenes') . '</p>';
    }

    public function ai_section_callback() {
        echo '<p>' . esc_html__('Configura la integración con Google AI para análisis inteligente de imágenes.', 'mi-renombrador-imagenes') . '</p>';
    }

    /**
     * Mostrar notificaciones de administración
     */
    public function show_admin_notices() {
        // Verificar si el plugin necesita configuración inicial
        if ($this->needs_initial_setup()) {
            $this->show_setup_notice();
        }

        // Verificar problemas de configuración
        if ($this->has_configuration_issues()) {
            $this->show_configuration_issues();
        }
    }

    /**
     * Verificar si necesita configuración inicial
     * 
     * @return bool
     */
    private function needs_initial_setup() {
        $setup_completed = get_option('mri_initial_setup_completed', false);
        return !$setup_completed;
    }

    /**
     * Mostrar notificación de configuración inicial
     */
    private function show_setup_notice() {
        $settings_url = admin_url('options-general.php?page=' . $this->settings_page_slug);
        ?>
        <div class="notice notice-info is-dismissible">
            <p>
                <strong><?php esc_html_e('¡Bienvenido a Toc Toc SEO Images!', 'mi-renombrador-imagenes'); ?></strong>
                <?php printf(
                    wp_kses_post(__('Para comenzar a optimizar tus imágenes, <a href="%s">configura el plugin</a>.', 'mi-renombrador-imagenes')),
                    esc_url($settings_url)
                ); ?>
            </p>
        </div>
        <?php
    }

    /**
     * Verificar problemas de configuración
     * 
     * @return bool
     */
    private function has_configuration_issues() {
        // Verificar si IA está habilitada pero mal configurada
        $needs_ai = $this->options['enable_ai_title'] || $this->options['enable_ai_alt'] || $this->options['enable_ai_caption'];
        return $needs_ai && empty($this->options['gemini_api_key']);
    }

    /**
     * Mostrar problemas de configuración
     */
    private function show_configuration_issues() {
        $settings_url = admin_url('options-general.php?page=' . $this->settings_page_slug);
        ?>
        <div class="notice notice-warning">
            <p>
                <strong><?php esc_html_e('Toc Toc SEO Images:', 'mi-renombrador-imagenes'); ?></strong>
                <?php printf(
                    wp_kses_post(__('Las funciones de IA están habilitadas pero falta la API Key. <a href="%s">Configúrala aquí</a>.', 'mi-renombrador-imagenes')),
                    esc_url($settings_url)
                ); ?>
            </p>
        </div>
        <?php
    }

    /**
     * Mostrar notificación de bienvenida para nuevos usuarios
     */
    public function maybe_show_welcome_notice() {
        $welcome_shown = get_option('mri_welcome_notice_shown', false);
        
        if (!$welcome_shown && current_user_can('manage_options')) {
            add_action('admin_notices', [$this, 'show_welcome_notice']);
            update_option('mri_welcome_notice_shown', true);
        }
    }

    /**
     * Mostrar notificación de bienvenida
     */
    public function show_welcome_notice() {
        ?>
        <div class="notice notice-success is-dismissible">
            <h3><?php esc_html_e('🎉 ¡Toc Toc SEO Images Activado!', 'mi-renombrador-imagenes'); ?></h3>
            <p><?php esc_html_e('Tu plugin de optimización de imágenes con IA está listo. Aquí tienes algunos enlaces útiles:', 'mi-renombrador-imagenes'); ?></p>
            <p>
                <a href="<?php echo esc_url(admin_url('options-general.php?page=' . $this->settings_page_slug)); ?>" class="button button-primary">
                    <?php esc_html_e('Configurar Plugin', 'mi-renombrador-imagenes'); ?>
                </a>
                <a href="<?php echo esc_url(admin_url('upload.php?page=' . $this->bulk_page_slug)); ?>" class="button">
                    <?php esc_html_e('Procesar Imágenes Existentes', 'mi-renombrador-imagenes'); ?>
                </a>
                <a href="https://toctoc.ky/docs/seo-images" target="_blank" class="button">
                    <?php esc_html_e('Ver Documentación', 'mi-renombrador-imagenes'); ?>
                </a>
            </p>
        </div>
        <?php
    }

    /**
     * Añadir enlaces de acción en la página de plugins
     * 
     * @param array $links Enlaces existentes
     * @return array Enlaces modificados
     */
    public function add_plugin_action_links($links) {
        $settings_link = sprintf(
            '<a href="%s">%s</a>',
            admin_url('options-general.php?page=' . $this->settings_page_slug),
            __('Configuración', 'mi-renombrador-imagenes')
        );

        $bulk_link = sprintf(
            '<a href="%s">%s</a>',
            admin_url('upload.php?page=' . $this->bulk_page_slug),
            __('Procesar Masivo', 'mi-renombrador-imagenes')
        );

        array_unshift($links, $settings_link, $bulk_link);
        return $links;
    }

    /**
     * Añadir metadatos en la fila del plugin
     * 
     * @param array $links Enlaces existentes
     * @param string $file Archivo del plugin
     * @return array Enlaces modificados
     */
    public function add_plugin_row_meta($links, $file) {
        if (plugin_basename(MRI_PLUGIN_FILE) === $file) {
            $new_links = [
                '<a href="https://toctoc.ky/docs/seo-images" target="_blank">' . __('Documentación', 'mi-renombrador-imagenes') . '</a>',
                '<a href="https://toctoc.ky/support" target="_blank">' . __('Soporte', 'mi-renombrador-imagenes') . '</a>',
                '<a href="' . admin_url('upload.php?page=mri_stats_page') . '">' . __('Estadísticas', 'mi-renombrador-imagenes') . '</a>'
            ];
            $links = array_merge($links, $new_links);
        }
        return $links;
    }

    /**
     * Obtener idiomas soportados
     * 
     * @return array
     */
    private function get_supported_languages() {
        return [
            'es' => __('Español', 'mi-renombrador-imagenes'),
            'en' => __('Inglés', 'mi-renombrador-imagenes'),
            'fr' => __('Francés', 'mi-renombrador-imagenes'),
            'de' => __('Alemán', 'mi-renombrador-imagenes'),
            'it' => __('Italiano', 'mi-renombrador-imagenes'),
            'pt' => __('Portugués', 'mi-renombrador-imagenes'),
        ];
    }
}
