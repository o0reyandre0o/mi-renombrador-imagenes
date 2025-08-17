<?php
/**
 * Core Class - Main Plugin Controller
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
 * Clase principal del plugin
 * 
 * Controla la inicialización y coordinación de todos los componentes del plugin
 */
class MRI_Core {

    /**
     * Instancia única del plugin (Singleton)
     * 
     * @var MRI_Core|null
     */
    private static $instance = null;

    /**
     * Procesador de IA
     * 
     * @var MRI_AI_Processor
     */
    private $ai_processor;

    /**
     * Compresor de imágenes
     * 
     * @var MRI_Compressor
     */
    private $compressor;

    /**
     * Administrador del panel
     * 
     * @var MRI_Admin
     */
    private $admin;

    /**
     * Manejador AJAX
     * 
     * @var MRI_Ajax
     */
    private $ajax;

    /**
     * Opciones del plugin
     * 
     * @var array
     */
    private $options;

    /**
     * Versión del plugin
     * 
     * @var string
     */
    private $version = '3.6.0';

    /**
     * Constructor privado (Singleton)
     */
    private function __construct() {
        $this->load_dependencies();
        $this->init_options();
        $this->init_components();
        $this->init_hooks();
    }

    /**
     * Obtener instancia única del plugin (Singleton)
     * 
     * @return MRI_Core
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Inicializar el plugin
     */
    public static function init() {
        return self::get_instance();
    }

    /**
     * Cargar dependencias requeridas
     */
    private function load_dependencies() {
        $includes_path = plugin_dir_path(__FILE__);

        // Cargar clases principales
        require_once $includes_path . 'class-mri-ai-processor.php';
        require_once $includes_path . 'class-mri-compressor.php';
        require_once $includes_path . 'class-mri-admin.php';
        require_once $includes_path . 'class-mri-ajax.php';
        require_once $includes_path . 'class-mri-image-processor.php';

        // Cargar utilidades
        require_once $includes_path . 'class-mri-utils.php';
        require_once $includes_path . 'class-mri-logger.php';
    }

    /**
     * Inicializar opciones del plugin
     */
    private function init_options() {
        $this->options = get_option(MRI_SETTINGS_OPTION_NAME, $this->get_default_options());
    }

    /**
     * Inicializar componentes del plugin
     */
    private function init_components() {
        $this->ai_processor = new MRI_AI_Processor($this->options);
        $this->compressor = new MRI_Compressor($this->options);
        $this->admin = new MRI_Admin($this->options);
        $this->ajax = new MRI_Ajax($this->options);
    }

    /**
     * Inicializar hooks de WordPress
     */
    private function init_hooks() {
        // Hooks de activación/desactivación
        register_activation_hook(MRI_PLUGIN_FILE, [$this, 'activate']);
        register_deactivation_hook(MRI_PLUGIN_FILE, [$this, 'deactivate']);

        // Hooks de WordPress
        add_action('plugins_loaded', [$this, 'load_textdomain']);
        add_action('init', [$this, 'init_plugin']);
        add_action('admin_init', [$this, 'admin_init']);

        // Hook principal para procesamiento de imágenes
        add_action('add_attachment', [$this, 'process_new_attachment'], 20, 1);

        // Hooks de actualización
        add_action('upgrader_process_complete', [$this, 'upgrade_completed'], 10, 2);

        // Filtros personalizados
        add_filter('mri_process_image', [$this, 'process_image_filter'], 10, 2);
    }

    /**
     * Activación del plugin
     */
    public function activate() {
        // Crear opciones por defecto
        if (!get_option(MRI_SETTINGS_OPTION_NAME)) {
            add_option(MRI_SETTINGS_OPTION_NAME, $this->get_default_options());
        }

        // Crear tablas personalizadas si es necesario
        $this->create_tables();

        // Limpiar cache
        $this->clear_cache();

        // Programar tareas cron si es necesario
        $this->schedule_events();

        // Log de activación
        MRI_Logger::info('Plugin activado correctamente');
    }

    /**
     * Desactivación del plugin
     */
    public function deactivate() {
        // Limpiar eventos programados
        $this->unschedule_events();

        // Limpiar cache
        $this->clear_cache();

        // Log de desactivación
        MRI_Logger::info('Plugin desactivado');
    }

    /**
     * Cargar textdomain para traducciones
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'mi-renombrador-imagenes',
            false,
            dirname(plugin_basename(MRI_PLUGIN_FILE)) . '/languages/'
        );
    }

    /**
     * Inicialización del plugin
     */
    public function init_plugin() {
        // Verificar versión de WordPress
        if (!$this->check_wordpress_version()) {
            return;
        }

        // Verificar requisitos PHP
        if (!$this->check_php_requirements()) {
            return;
        }

        // Inicializar componentes que requieren WordPress completamente cargado
        do_action('mri_plugin_loaded');
    }

    /**
     * Inicialización del admin
     */
    public function admin_init() {
        // Verificar si necesita actualización
        $this->maybe_upgrade();

        // Registrar configuraciones
        $this->admin->register_settings();
    }

    /**
     * Procesar nueva imagen subida
     * 
     * @param int $attachment_id ID del adjunto
     */
    public function process_new_attachment($attachment_id) {
        // Verificar que no sea una revisión
        if (wp_is_post_revision($attachment_id)) {
            return;
        }

        // Verificar que no esté siendo procesado por bulk
        if (get_post_meta($attachment_id, '_mri_processing_bulk', true)) {
            return;
        }

        // Evitar procesamiento doble
        if (get_post_meta($attachment_id, '_mri_processing_upload', true)) {
            return;
        }

        // Marcar como en procesamiento
        update_post_meta($attachment_id, '_mri_processing_upload', true);

        try {
            // Procesar imagen
            $result = $this->process_image($attachment_id, false);
            
            // Log del resultado
            if ($result['success']) {
                MRI_Logger::info("Imagen procesada correctamente: ID {$attachment_id}");
            } else {
                MRI_Logger::warning("Error procesando imagen: ID {$attachment_id} - {$result['message']}");
            }

        } catch (Exception $e) {
            MRI_Logger::error("Excepción procesando imagen ID {$attachment_id}: " . $e->getMessage());
        } finally {
            // Eliminar marca de procesamiento
            delete_post_meta($attachment_id, '_mri_processing_upload');
        }
    }

    /**
     * Procesar imagen (método principal)
     * 
     * @param int $attachment_id ID del adjunto
     * @param bool $is_bulk_process Si es procesamiento masivo
     * @return array Resultado del procesamiento
     */
    public function process_image($attachment_id, $is_bulk_process = false) {
        $result = [
            'success' => false,
            'message' => '',
            'actions' => []
        ];

        try {
            // Verificar que es una imagen válida
            if (!$this->is_valid_image($attachment_id)) {
                throw new Exception(__('Archivo no es una imagen válida', 'mi-renombrador-imagenes'));
            }

            // Crear procesador de imagen
            $image_processor = new MRI_Image_Processor($attachment_id, $this->options, $is_bulk_process);

            // Procesar con IA si está habilitado
            if ($this->should_use_ai()) {
                $ai_result = $this->ai_processor->process_image($attachment_id, $image_processor->get_image_path());
                if ($ai_result['success']) {
                    $image_processor->set_ai_metadata($ai_result['data']);
                    $result['actions'][] = 'AI processing';
                }
            }

            // Comprimir imagen si está habilitado
            if ($this->options['enable_compression']) {
                $compression_result = $this->compressor->compress_image($image_processor->get_image_path());
                if ($compression_result['success']) {
                    $image_processor->set_compressed_path($compression_result['path']);
                    $result['actions'][] = 'Compression';
                }
            }

            // Procesar metadatos
            $metadata_result = $image_processor->process_metadata();
            if ($metadata_result['success']) {
                $result['actions'] = array_merge($result['actions'], $metadata_result['actions']);
            }

            // Renombrar archivo si está habilitado
            if ($this->options['enable_rename']) {
                $rename_result = $image_processor->rename_file();
                if ($rename_result['success']) {
                    $result['actions'][] = 'Rename';
                }
            }

            $result['success'] = true;
            $result['message'] = $is_bulk_process 
                ? sprintf(__('ID %d: %s', 'mi-renombrador-imagenes'), $attachment_id, implode(', ', $result['actions']))
                : __('Imagen procesada correctamente', 'mi-renombrador-imagenes');

        } catch (Exception $e) {
            $result['message'] = $e->getMessage();
            MRI_Logger::error("Error procesando imagen ID {$attachment_id}: " . $e->getMessage());
        }

        // Aplicar filtro para permitir modificaciones
        return apply_filters('mri_process_image_result', $result, $attachment_id, $is_bulk_process);
    }

    /**
     * Filtro personalizado para procesamiento de imagen
     * 
     * @param int $attachment_id ID del adjunto
     * @param bool $is_bulk_process Si es procesamiento masivo
     * @return int ID del adjunto (permite modificación)
     */
    public function process_image_filter($attachment_id, $is_bulk_process) {
        return apply_filters('mri_before_process_image', $attachment_id, $is_bulk_process);
    }

    /**
     * Verificar si es una imagen válida
     * 
     * @param int $attachment_id ID del adjunto
     * @return bool
     */
    private function is_valid_image($attachment_id) {
        $mime_type = get_post_mime_type($attachment_id);
        $allowed_types = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/avif', 'image/svg+xml'];
        
        return in_array($mime_type, $allowed_types);
    }

    /**
     * Verificar si se debe usar IA
     * 
     * @return bool
     */
    private function should_use_ai() {
        return ($this->options['enable_ai_title'] || 
                $this->options['enable_ai_alt'] || 
                $this->options['enable_ai_caption']) &&
               !empty($this->options['gemini_api_key']);
    }

    /**
     * Verificar versión de WordPress
     * 
     * @return bool
     */
    private function check_wordpress_version() {
        global $wp_version;
        $required_version = '5.0';
        
        if (version_compare($wp_version, $required_version, '<')) {
            add_action('admin_notices', function() use ($required_version) {
                echo '<div class="notice notice-error"><p>';
                printf(
                    esc_html__('Toc Toc SEO Images requiere WordPress %s o superior. Versión actual: %s', 'mi-renombrador-imagenes'),
                    $required_version,
                    $GLOBALS['wp_version']
                );
                echo '</p></div>';
            });
            return false;
        }
        
        return true;
    }

    /**
     * Verificar requisitos PHP
     * 
     * @return bool
     */
    private function check_php_requirements() {
        $required_version = '7.4';
        
        if (version_compare(PHP_VERSION, $required_version, '<')) {
            add_action('admin_notices', function() use ($required_version) {
                echo '<div class="notice notice-error"><p>';
                printf(
                    esc_html__('Toc Toc SEO Images requiere PHP %s o superior. Versión actual: %s', 'mi-renombrador-imagenes'),
                    $required_version,
                    PHP_VERSION
                );
                echo '</p></div>';
            });
            return false;
        }
        
        return true;
    }

    /**
     * Verificar si necesita actualización
     */
    private function maybe_upgrade() {
        $installed_version = get_option('mri_plugin_version', '0.0.0');
        
        if (version_compare($installed_version, $this->version, '<')) {
            $this->upgrade($installed_version);
            update_option('mri_plugin_version', $this->version);
        }
    }

    /**
     * Ejecutar actualización
     * 
     * @param string $from_version Versión anterior
     */
    private function upgrade($from_version) {
        MRI_Logger::info("Actualizando plugin desde versión {$from_version} a {$this->version}");

        // Ejecutar migraciones según la versión
        if (version_compare($from_version, '3.0.0', '<')) {
            $this->migrate_to_3_0_0();
        }

        if (version_compare($from_version, '3.5.0', '<')) {
            $this->migrate_to_3_5_0();
        }

        // Limpiar cache después de actualización
        $this->clear_cache();

        MRI_Logger::info("Actualización completada a versión {$this->version}");
    }

    /**
     * Migración a versión 3.0.0
     */
    private function migrate_to_3_0_0() {
        // Migrar opciones antiguas si existen
        $old_options = get_option('mri_old_options', []);
        if (!empty($old_options)) {
            $new_options = $this->get_default_options();
            // Mapear opciones antiguas a nuevas
            update_option(MRI_SETTINGS_OPTION_NAME, array_merge($new_options, $old_options));
            delete_option('mri_old_options');
        }
    }

    /**
     * Migración a versión 3.5.0
     */
    private function migrate_to_3_5_0() {
        // Añadir nuevas opciones de compresión si no existen
        $options = get_option(MRI_SETTINGS_OPTION_NAME, []);
        $defaults = $this->get_default_options();
        
        $updated = false;
        foreach (['enable_compression', 'jpeg_quality', 'use_imagick_if_available'] as $key) {
            if (!isset($options[$key])) {
                $options[$key] = $defaults[$key];
                $updated = true;
            }
        }
        
        if ($updated) {
            update_option(MRI_SETTINGS_OPTION_NAME, $options);
        }
    }

    /**
     * Crear tablas personalizadas
     */
    private function create_tables() {
        global $wpdb;

        // Tabla de estadísticas (opcional para futuras mejoras)
        $table_name = $wpdb->prefix . 'mri_statistics';
        
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            attachment_id bigint(20) NOT NULL,
            action_type varchar(50) NOT NULL,
            action_data text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY attachment_id (attachment_id),
            KEY action_type (action_type),
            KEY created_at (created_at)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    /**
     * Programar eventos cron
     */
    private function schedule_events() {
        // Programar limpieza diaria
        if (!wp_next_scheduled('mri_daily_cleanup')) {
            wp_schedule_event(time(), 'daily', 'mri_daily_cleanup');
        }

        // Programar estadísticas semanales
        if (!wp_next_scheduled('mri_weekly_stats')) {
            wp_schedule_event(time(), 'weekly', 'mri_weekly_stats');
        }
    }

    /**
     * Desprogramar eventos cron
     */
    private function unschedule_events() {
        wp_clear_scheduled_hook('mri_daily_cleanup');
        wp_clear_scheduled_hook('mri_weekly_stats');
    }

    /**
     * Limpiar cache
     */
    private function clear_cache() {
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }

        // Limpiar transients específicos del plugin
        delete_transient('mri_server_capabilities');
        delete_transient('mri_api_status');
    }

    /**
     * Obtener opciones por defecto
     * 
     * @return array
     */
    private function get_default_options() {
        return [
            'enable_rename'            => 1,
            'enable_compression'       => 1,
            'jpeg_quality'             => 85,
            'use_imagick_if_available' => 1,
            'enable_alt'               => 1,
            'overwrite_alt'            => 1,
            'enable_caption'           => 1,
            'overwrite_caption'        => 0,
            'gemini_api_key'           => '',
            'gemini_model'             => 'gemini-1.5-flash-latest',
            'ai_output_language'       => 'es',
            'enable_ai_title'          => 0,
            'overwrite_title'          => 0,
            'enable_ai_alt'            => 0,
            'enable_ai_caption'        => 0,
            'include_seo_in_ai_prompt' => 1,
        ];
    }

    /**
     * Obtener opciones actuales
     * 
     * @return array
     */
    public function get_options() {
        return $this->options;
    }

    /**
     * Actualizar opciones
     * 
     * @param array $new_options Nuevas opciones
     */
    public function update_options($new_options) {
        $this->options = array_merge($this->options, $new_options);
        update_option(MRI_SETTINGS_OPTION_NAME, $this->options);
    }

    /**
     * Obtener versión del plugin
     * 
     * @return string
     */
    public function get_version() {
        return $this->version;
    }

    /**
     * Obtener componente específico
     * 
     * @param string $component Nombre del componente
     * @return object|null
     */
    public function get_component($component) {
        switch ($component) {
            case 'ai_processor':
                return $this->ai_processor;
            case 'compressor':
                return $this->compressor;
            case 'admin':
                return $this->admin;
            case 'ajax':
                return $this->ajax;
            default:
                return null;
        }
    }

    /**
     * Callback para cuando se completa una actualización
     * 
     * @param WP_Upgrader $upgrader Objeto upgrader
     * @param array $hook_extra Información extra
     */
    public function upgrade_completed($upgrader, $hook_extra) {
        if (isset($hook_extra['plugin']) && $hook_extra['plugin'] === plugin_basename(MRI_PLUGIN_FILE)) {
            // Limpiar cache después de actualización
            $this->clear_cache();
            
            // Verificar si necesita migración
            $this->maybe_upgrade();
            
            MRI_Logger::info('Plugin actualizado automáticamente');
        }
    }

    /**
     * Destructor
     */
    public function __destruct() {
        // Limpieza final si es necesario
    }
}
