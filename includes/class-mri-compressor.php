<?php
/**
 * Image Compressor Class - Image Optimization
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
 * Clase para compresión de imágenes
 * 
 * Maneja la optimización y compresión de imágenes usando
 * Imagick (preferido) o GD (fallback)
 */
class MRI_Compressor {

    /**
     * Opciones del plugin
     * 
     * @var array
     */
    private $options;

    /**
     * Tipos MIME comprimibles
     * 
     * @var array
     */
    private $compressible_mime_types = [
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/gif',
        'image/avif'
    ];

    /**
     * Estadísticas de compresión
     * 
     * @var array
     */
    private $compression_stats = [
        'original_size' => 0,
        'compressed_size' => 0,
        'reduction_percentage' => 0,
        'method_used' => ''
    ];

    /**
     * Constructor
     * 
     * @param array $options Opciones del plugin
     */
    public function __construct($options) {
        $this->options = $options;
    }

    /**
     * Comprimir imagen
     * 
     * @param string $file_path Ruta del archivo
     * @return array Resultado de la compresión
     */
    public function compress_image($file_path) {
        $result = [
            'success' => false,
            'path' => $file_path,
            'message' => '',
            'stats' => []
        ];

        try {
            // Verificar que la compresión esté habilitada
            if (!$this->options['enable_compression']) {
                return $result;
            }

            // Verificar que el archivo existe y es legible
            if (!file_exists($file_path) || !is_readable($file_path)) {
                throw new Exception(__('Archivo no encontrado o no legible', 'mi-renombrador-imagenes'));
            }

            // Verificar permisos de escritura
            if (!is_writable($file_path)) {
                throw new Exception(__('Archivo no escribible', 'mi-renombrador-imagenes'));
            }

            // Obtener información del archivo
            $mime_type = $this->get_mime_type($file_path);
            if (!$this->is_compressible($mime_type)) {
                throw new Exception(sprintf(__('Tipo MIME no comprimible: %s', 'mi-renombrador-imagenes'), $mime_type));
            }

            // Guardar tamaño original
            $this->compression_stats['original_size'] = filesize($file_path);

            // Intentar compresión con Imagick primero, luego GD
            $compression_result = $this->attempt_compression($file_path, $mime_type);

            if ($compression_result['success']) {
                // Verificar mejora en tamaño
                clearstatcache();
                $this->compression_stats['compressed_size'] = filesize($file_path);
                $this->compression_stats['reduction_percentage'] = $this->calculate_reduction_percentage();

                $result['success'] = true;
                $result['stats'] = $this->compression_stats;
                $result['message'] = sprintf(
                    __('Imagen comprimida con %s. Reducción: %d%%', 'mi-renombrador-imagenes'),
                    $this->compression_stats['method_used'],
                    $this->compression_stats['reduction_percentage']
                );

                // Actualizar metadatos de WordPress si es necesario
                $this->update_wordpress_metadata($file_path);

            } else {
                throw new Exception($compression_result['error']);
            }

        } catch (Exception $e) {
            $result['message'] = $e->getMessage();
            MRI_Logger::error("Error comprimiendo imagen: " . $e->getMessage());
        }

        return $result;
    }

    /**
     * Intentar compresión con diferentes métodos
     * 
     * @param string $file_path Ruta del archivo
     * @param string $mime_type Tipo MIME
     * @return array Resultado del intento
     */
    private function attempt_compression($file_path, $mime_type) {
        $result = [
            'success' => false,
            'error' => ''
        ];

        // Intentar con Imagick si está disponible y configurado
        if ($this->options['use_imagick_if_available'] && $this->is_imagick_available()) {
            $imagick_result = $this->compress_with_imagick($file_path, $mime_type);
            if ($imagick_result['success']) {
                $this->compression_stats['method_used'] = 'Imagick';
                return $imagick_result;
            } else {
                MRI_Logger::warning("Fallo compresión Imagick: " . $imagick_result['error']);
            }
        }

        // Fallback a GD
        if ($this->is_gd_available()) {
            $gd_result = $this->compress_with_gd($file_path, $mime_type);
            if ($gd_result['success']) {
                $this->compression_stats['method_used'] = 'GD';
                return $gd_result;
            } else {
                $result['error'] = "Fallo con GD: " . $gd_result['error'];
            }
        } else {
            $result['error'] = __('Ni Imagick ni GD están disponibles', 'mi-renombrador-imagenes');
        }

        return $result;
    }

    /**
     * Comprimir con Imagick
     * 
     * @param string $file_path Ruta del archivo
     * @param string $mime_type Tipo MIME
     * @return array Resultado de la compresión
     */
    private function compress_with_imagick($file_path, $mime_type) {
        $result = [
            'success' => false,
            'error' => ''
        ];

        try {
            // Aumentar memoria temporalmente
            ini_set('memory_limit', '256M');

            $imagick = new \Imagick($file_path);
            $format = $imagick->getImageFormat();

            // Configurar compresión según el formato
            switch (strtoupper($format)) {
                case 'JPEG':
                    $imagick->setImageCompression(\Imagick::COMPRESSION_JPEG);
                    $imagick->setImageCompressionQuality($this->options['jpeg_quality']);
                    break;

                case 'PNG':
                    $imagick->setImageCompression(\Imagick::COMPRESSION_ZIP);
                    $imagick->setImageCompressionQuality(9);
                    // Preservar transparencia
                    $imagick->setImageAlphaChannel(\Imagick::ALPHACHANNEL_ACTIVATE);
                    break;

                case 'WEBP':
                    $imagick->setImageFormat('WEBP');
                    $imagick->setImageCompressionQuality($this->options['jpeg_quality']);
                    // Configurar para lossless si la calidad es muy alta
                    if ($this->options['jpeg_quality'] >= 95) {
                        $imagick->setOption('webp:lossless', 'true');
                    }
                    break;

                case 'GIF':
                    // Optimizar layers para GIFs animados
                    $imagick = $imagick->optimizeImageLayers();
                    break;

                case 'AVIF':
                    if ($imagick->queryFormats('AVIF')) {
                        $imagick->setImageFormat('AVIF');
                        $imagick->setImageCompressionQuality($this->options['jpeg_quality']);
                    } else {
                        throw new Exception(__('AVIF no soportado por esta versión de Imagick', 'mi-renombrador-imagenes'));
                    }
                    break;

                default:
                    throw new Exception(sprintf(__('Formato no soportado: %s', 'mi-renombrador-imagenes'), $format));
            }

            // Eliminar metadatos EXIF para reducir tamaño
            $imagick->stripImage();

            // Aplicar perfilado de color básico si es necesario
            if ($format === 'JPEG' || $format === 'WEBP') {
                try {
                    $imagick->transformImageColorspace(\Imagick::COLORSPACE_SRGB);
                } catch (Exception $e) {
                    // Ignorar errores de colorspace
                }
            }

            // Guardar imagen comprimida
            if (!$imagick->writeImage($file_path)) {
                throw new Exception(__('No se pudo escribir la imagen comprimida', 'mi-renombrador-imagenes'));
            }

            $imagick->clear();
            $imagick->destroy();

            $result['success'] = true;

        } catch (\ImagickException $e) {
            $result['error'] = sprintf(__('Error Imagick: %s', 'mi-renombrador-imagenes'), $e->getMessage());
        } catch (Exception $e) {
            $result['error'] = $e->getMessage();
        }

        return $result;
    }

    /**
     * Comprimir con GD
     * 
     * @param string $file_path Ruta del archivo
     * @param string $mime_type Tipo MIME
     * @return array Resultado de la compresión
     */
    private function compress_with_gd($file_path, $mime_type) {
        $result = [
            'success' => false,
            'error' => ''
        ];

        try {
            // Aumentar memoria temporalmente
            ini_set('memory_limit', '256M');

            $image_resource = null;

            // Crear recurso de imagen según el tipo
            switch ($mime_type) {
                case 'image/jpeg':
                    if (!function_exists('imagecreatefromjpeg')) {
                        throw new Exception(__('GD no soporta JPEG', 'mi-renombrador-imagenes'));
                    }
                    $image_resource = @imagecreatefromjpeg($file_path);
                    break;

                case 'image/png':
                    if (!function_exists('imagecreatefrompng')) {
                        throw new Exception(__('GD no soporta PNG', 'mi-renombrador-imagenes'));
                    }
                    $image_resource = @imagecreatefrompng($file_path);
                    break;

                case 'image/gif':
                    if (!function_exists('imagecreatefromgif')) {
                        throw new Exception(__('GD no soporta GIF', 'mi-renombrador-imagenes'));
                    }
                    $image_resource = @imagecreatefromgif($file_path);
                    break;

                case 'image/webp':
                    if (!function_exists('imagecreatefromwebp')) {
                        throw new Exception(__('GD no soporta WebP', 'mi-renombrador-imagenes'));
                    }
                    $image_resource = @imagecreatefromwebp($file_path);
                    break;

                default:
                    throw new Exception(sprintf(__('Tipo MIME no soportado por GD: %s', 'mi-renombrador-imagenes'), $mime_type));
            }

            if (!$image_resource) {
                throw new Exception(__('No se pudo crear recurso de imagen con GD', 'mi-renombrador-imagenes'));
            }

            // Guardar imagen comprimida según el tipo
            $save_success = false;
            switch ($mime_type) {
                case 'image/jpeg':
                    $save_success = imagejpeg($image_resource, $file_path, $this->options['jpeg_quality']);
                    break;

                case 'image/png':
                    // Preservar transparencia para PNG
                    imagealphablending($image_resource, false);
                    imagesavealpha($image_resource, true);
                    $save_success = imagepng($image_resource, $file_path, 9); // Nivel máximo de compresión
                    break;

                case 'image/gif':
                    $save_success = imagegif($image_resource, $file_path);
                    break;

                case 'image/webp':
                    $save_success = imagewebp($image_resource, $file_path, $this->options['jpeg_quality']);
                    break;
            }

            // Liberar memoria
            imagedestroy($image_resource);

            if (!$save_success) {
                throw new Exception(__('No se pudo guardar la imagen comprimida con GD', 'mi-renombrador-imagenes'));
            }

            $result['success'] = true;

        } catch (Exception $e) {
            if (isset($image_resource) && is_resource($image_resource)) {
                imagedestroy($image_resource);
            }
            $result['error'] = $e->getMessage();
        }

        return $result;
    }

    /**
     * Verificar si un tipo MIME es comprimible
     * 
     * @param string $mime_type Tipo MIME
     * @return bool
     */
    private function is_compressible($mime_type) {
        return in_array($mime_type, $this->compressible_mime_types);
    }

    /**
     * Verificar si Imagick está disponible
     * 
     * @return bool
     */
    private function is_imagick_available() {
        return extension_loaded('imagick') && class_exists('Imagick');
    }

    /**
     * Verificar si GD está disponible
     * 
     * @return bool
     */
    private function is_gd_available() {
        return extension_loaded('gd') && function_exists('gd_info');
    }

    /**
     * Obtener tipo MIME de un archivo
     * 
     * @param string $file_path Ruta del archivo
     * @return string|false Tipo MIME o false
     */
    private function get_mime_type($file_path) {
        // Usar wp_check_filetype como primera opción
        $file_info = wp_check_filetype($file_path);
        if (!empty($file_info['type'])) {
            return $file_info['type'];
        }

        // Fallback a finfo si está disponible
        if (function_exists('finfo_file')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $file_path);
            finfo_close($finfo);
            return $mime_type;
        }

        // Último recurso: getimagesize
        $image_info = @getimagesize($file_path);
        return $image_info ? $image_info['mime'] : false;
    }

    /**
     * Calcular porcentaje de reducción
     * 
     * @return int Porcentaje de reducción
     */
    private function calculate_reduction_percentage() {
        if ($this->compression_stats['original_size'] <= 0) {
            return 0;
        }

        $reduction = $this->compression_stats['original_size'] - $this->compression_stats['compressed_size'];
        $percentage = ($reduction / $this->compression_stats['original_size']) * 100;
        
        return max(0, round($percentage));
    }

    /**
     * Actualizar metadatos de WordPress después de compresión
     * 
     * @param string $file_path Ruta del archivo
     */
    private function update_wordpress_metadata($file_path) {
        // Obtener attachment ID a partir del path
        $attachment_id = $this->get_attachment_id_from_path($file_path);
        
        if ($attachment_id) {
            // Regenerar metadatos de imagen (tamaños, etc.)
            require_once ABSPATH . 'wp-admin/includes/image.php';
            $metadata = wp_generate_attachment_metadata($attachment_id, $file_path);
            wp_update_attachment_metadata($attachment_id, $metadata);
        }
    }

    /**
     * Obtener ID de attachment a partir de la ruta del archivo
     * 
     * @param string $file_path Ruta del archivo
     * @return int|false ID del attachment o false
     */
    private function get_attachment_id_from_path($file_path) {
        global $wpdb;

        $upload_dir = wp_upload_dir();
        $file_url = str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $file_path);
        
        $attachment_id = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM $wpdb->postmeta 
             WHERE meta_key = '_wp_attached_file' 
             AND meta_value = %s",
            str_replace($upload_dir['baseurl'] . '/', '', $file_url)
        ));

        return $attachment_id ? intval($attachment_id) : false;
    }

    /**
     * Obtener información sobre capacidades de compresión
     * 
     * @return array Información de capacidades
     */
    public function get_compression_capabilities() {
        $capabilities = [
            'imagick' => [
                'available' => $this->is_imagick_available(),
                'version' => '',
                'supported_formats' => []
            ],
            'gd' => [
                'available' => $this->is_gd_available(),
                'version' => '',
                'supported_formats' => []
            ]
        ];

        // Información de Imagick
        if ($capabilities['imagick']['available']) {
            try {
                $imagick = new \Imagick();
                $capabilities['imagick']['version'] = $imagick->getVersion()['versionString'];
                $capabilities['imagick']['supported_formats'] = $imagick->queryFormats();
                $imagick->destroy();
            } catch (Exception $e) {
                $capabilities['imagick']['available'] = false;
            }
        }

        // Información de GD
        if ($capabilities['gd']['available']) {
            $gd_info = gd_info();
            $capabilities['gd']['version'] = $gd_info['GD Version'];
            $capabilities['gd']['supported_formats'] = [];
            
            if ($gd_info['JPEG Support']) $capabilities['gd']['supported_formats'][] = 'JPEG';
            if ($gd_info['PNG Support']) $capabilities['gd']['supported_formats'][] = 'PNG';
            if ($gd_info['GIF Read Support'] && $gd_info['GIF Create Support']) $capabilities['gd']['supported_formats'][] = 'GIF';
            if (isset($gd_info['WebP Support']) && $gd_info['WebP Support']) $capabilities['gd']['supported_formats'][] = 'WEBP';
        }

        return $capabilities;
    }

    /**
     * Obtener estadísticas globales de compresión
     * 
     * @return array Estadísticas
     */
    public function get_compression_stats() {
        return [
            'total_compressed' => get_option('mri_total_compressed', 0),
            'total_bytes_saved' => get_option('mri_total_bytes_saved', 0),
            'average_reduction' => get_option('mri_average_reduction', 0),
            'last_compression' => get_option('mri_last_compression', null)
        ];
    }

    /**
     * Actualizar estadísticas globales
     * 
     * @param array $stats Estadísticas de la compresión actual
     */
    public function update_global_stats($stats) {
        if (!isset($stats['reduction_percentage']) || $stats['reduction_percentage'] <= 0) {
            return;
        }

        $total_compressed = get_option('mri_total_compressed', 0);
        $total_bytes_saved = get_option('mri_total_bytes_saved', 0);
        $average_reduction = get_option('mri_average_reduction', 0);

        $bytes_saved = $stats['original_size'] - $stats['compressed_size'];
        
        // Actualizar contadores
        update_option('mri_total_compressed', $total_compressed + 1);
        update_option('mri_total_bytes_saved', $total_bytes_saved + $bytes_saved);
        
        // Calcular nueva media de reducción
        $new_average = (($average_reduction * $total_compressed) + $stats['reduction_percentage']) / ($total_compressed + 1);
        update_option('mri_average_reduction', round($new_average, 2));
        
        update_option('mri_last_compression', current_time('mysql'));
    }

    /**
     * Comprimir múltiples imágenes
     * 
     * @param array $file_paths Array de rutas de archivos
     * @return array Resultados de compresión
     */
    public function compress_multiple_images($file_paths) {
        $results = [];
        
        foreach ($file_paths as $file_path) {
            $results[] = $this->compress_image($file_path);
            
            // Pausa pequeña para no saturar el servidor
            usleep(500000); // 0.5 segundos
        }
        
        return $results;
    }

    /**
     * Validar configuración de compresión
     * 
     * @return array Resultado de validación
     */
    public function validate_compression_config() {
        $validation = [
            'valid' => true,
            'warnings' => [],
            'errors' => []
        ];

        // Verificar que al menos una librería esté disponible
        if (!$this->is_imagick_available() && !$this->is_gd_available()) {
            $validation['valid'] = false;
            $validation['errors'][] = __('Ni Imagick ni GD están disponibles para compresión', 'mi-renombrador-imagenes');
        }

        // Verificar configuración de calidad
        $quality = $this->options['jpeg_quality'];
        if ($quality < 60 || $quality > 100) {
            $validation['warnings'][] = __('La calidad JPEG debería estar entre 60 y 100 para mejores resultados', 'mi-renombrador-imagenes');
        }

        // Verificar preferencia de Imagick
        if ($this->options['use_imagick_if_available'] && !$this->is_imagick_available()) {
            $validation['warnings'][] = __('Imagick está configurado como preferido pero no está disponible', 'mi-renombrador-imagenes');
        }

        return $validation;
    }
}
