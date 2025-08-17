<?php
/**
 * AI Processor Class - Google AI (Gemini) Integration
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
 * Clase para procesamiento de IA con Google Gemini
 * 
 * Maneja toda la comunicación con la API de Google AI
 * y el procesamiento de imágenes con inteligencia artificial
 */
class MRI_AI_Processor {

    /**
     * Opciones del plugin
     * 
     * @var array
     */
    private $options;

    /**
     * Endpoint base de la API
     * 
     * @var string
     */
    private $api_endpoint = 'https://generativelanguage.googleapis.com/v1beta/models/';

    /**
     * Tipos MIME compatibles con Gemini Vision
     * 
     * @var array
     */
    private $supported_mime_types = [
        'image/png',
        'image/jpeg', 
        'image/webp',
        'image/heic',
        'image/heif',
        'image/gif',
        'image/avif'
    ];

    /**
     * Idiomas soportados
     * 
     * @var array
     */
    private $supported_languages = [
        'es' => 'Español',
        'en' => 'English',
        'fr' => 'Français',
        'de' => 'Deutsch',
        'it' => 'Italiano',
        'pt' => 'Português'
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
     * Procesar imagen con IA
     * 
     * @param int $attachment_id ID del adjunto
     * @param string $image_path Ruta de la imagen
     * @return array Resultado del procesamiento
     */
    public function process_image($attachment_id, $image_path) {
        $result = [
            'success' => false,
            'data' => [],
            'errors' => []
        ];

        try {
            // Verificar configuración de IA
            if (!$this->is_ai_configured()) {
                throw new Exception(__('IA no configurada correctamente', 'mi-renombrador-imagenes'));
            }

            // Verificar que la imagen es compatible
            $mime_type = get_post_mime_type($attachment_id);
            if (!$this->is_compatible_image($mime_type)) {
                throw new Exception(sprintf(__('Tipo de imagen no compatible con IA: %s', 'mi-renombrador-imagenes'), $mime_type));
            }

            // Cargar imagen en base64
            $image_data = $this->load_image_base64($image_path);
            if (!$image_data) {
                throw new Exception(__('No se pudo cargar la imagen para análisis IA', 'mi-renombrador-imagenes'));
            }

            // Obtener contexto SEO si está habilitado
            $context = $this->get_seo_context($attachment_id);

            // Procesar con IA según las opciones habilitadas
            $ai_data = [];

            if ($this->options['enable_ai_title']) {
                $title_result = $this->generate_title($image_data, $mime_type, $context);
                if ($title_result['success']) {
                    $ai_data['title'] = $title_result['content'];
                } else {
                    $result['errors'][] = 'Title generation: ' . $title_result['error'];
                }
            }

            if ($this->options['enable_ai_alt']) {
                $alt_result = $this->generate_alt_text($image_data, $mime_type, $context);
                if ($alt_result['success']) {
                    $ai_data['alt_text'] = $alt_result['content'];
                } else {
                    $result['errors'][] = 'Alt text generation: ' . $alt_result['error'];
                }
            }

            if ($this->options['enable_ai_caption']) {
                $caption_result = $this->generate_caption($image_data, $mime_type, $context);
                if ($caption_result['success']) {
                    $ai_data['caption'] = $caption_result['content'];
                } else {
                    $result['errors'][] = 'Caption generation: ' . $caption_result['error'];
                }
            }

            if (!empty($ai_data)) {
                $result['success'] = true;
                $result['data'] = $ai_data;
            }

        } catch (Exception $e) {
            $result['errors'][] = $e->getMessage();
            MRI_Logger::error("Error en procesamiento IA para imagen ID {$attachment_id}: " . $e->getMessage());
        }

        return $result;
    }

    /**
     * Generar título con IA
     * 
     * @param string $image_data Datos de imagen en base64
     * @param string $mime_type Tipo MIME de la imagen
     * @param array $context Contexto SEO
     * @return array Resultado de la generación
     */
    public function generate_title($image_data, $mime_type, $context = []) {
        $language_name = $this->get_language_name();
        
        $prompt = sprintf(
            __('Generate the response in %1$s. Analyze this image and generate a concise, descriptive title (5-10 words) suitable for this image as an attachment title on a website.%2$s Be specific and avoid generic phrases. Provide ONLY the final title, without explanations or introductory text.', 'mi-renombrador-imagenes'),
            $language_name,
            $this->format_context($context)
        );

        return $this->call_gemini_api($prompt, $image_data, $mime_type, 50);
    }

    /**
     * Generar texto alternativo con IA
     * 
     * @param string $image_data Datos de imagen en base64
     * @param string $mime_type Tipo MIME de la imagen
     * @param array $context Contexto SEO
     * @return array Resultado de la generación
     */
    public function generate_alt_text($image_data, $mime_type, $context = []) {
        $language_name = $this->get_language_name();
        
        $prompt = sprintf(
            __('Generate the response in %1$s. Analyze this image and generate concise, descriptive alt text (maximum 125 characters) useful for accessibility and SEO.%2$s Do not use phrases like "image of" or "picture of". Provide ONLY the final alt text, without explanations or introductory text.', 'mi-renombrador-imagenes'),
            $language_name,
            $this->format_context($context)
        );

        return $this->call_gemini_api($prompt, $image_data, $mime_type, 60);
    }

    /**
     * Generar leyenda con IA
     * 
     * @param string $image_data Datos de imagen en base64
     * @param string $mime_type Tipo MIME de la imagen
     * @param array $context Contexto SEO
     * @return array Resultado de la generación
     */
    public function generate_caption($image_data, $mime_type, $context = []) {
        $language_name = $this->get_language_name();
        
        $prompt = sprintf(
            __('Generate the response in %1$s. Analyze this image and generate a brief, descriptive caption (1-2 short sentences) that provides interesting context or information about the image to display below it.%2$s Provide ONLY the final caption, without explanations or introductory text.', 'mi-renombrador-imagenes'),
            $language_name,
            $this->format_context($context)
        );

        return $this->call_gemini_api($prompt, $image_data, $mime_type, 100);
    }

    /**
     * Llamar a la API de Gemini
     * 
     * @param string $prompt Prompt para la IA
     * @param string $image_data Datos de imagen en base64
     * @param string $mime_type Tipo MIME de la imagen
     * @param int $max_tokens Máximo de tokens
     * @return array Resultado de la llamada
     */
    private function call_gemini_api($prompt, $image_data, $mime_type, $max_tokens = 150) {
        $result = [
            'success' => false,
            'content' => '',
            'error' => ''
        ];

        try {
            $api_url = $this->api_endpoint . $this->options['gemini_model'] . ':generateContent?key=' . $this->options['gemini_api_key'];

            $parts = [
                ['text' => $prompt],
                [
                    'inline_data' => [
                        'mime_type' => $mime_type,
                        'data' => $image_data
                    ]
                ]
            ];

            $request_body = [
                'contents' => [['parts' => $parts]],
                'generationConfig' => [
                    'temperature' => 0.7,
                    'maxOutputTokens' => absint($max_tokens),
                ],
                'safetySettings' => $this->get_safety_settings()
            ];

            $response = wp_remote_post($api_url, [
                'body' => wp_json_encode($request_body),
                'headers' => ['Content-Type' => 'application/json'],
                'timeout' => 90,
                'sslverify' => true,
            ]);

            if (is_wp_error($response)) {
                throw new Exception('Error de conexión: ' . $response->get_error_message());
            }

            $response_code = wp_remote_retrieve_response_code($response);
            $response_body = wp_remote_retrieve_body($response);

            if ($response_code !== 200) {
                $error_data = json_decode($response_body, true);
                $error_message = isset($error_data['error']['message']) 
                    ? $error_data['error']['message'] 
                    : "HTTP Error {$response_code}";
                throw new Exception($error_message);
            }

            $decoded_body = json_decode($response_body, true);

            if (empty($decoded_body['candidates'])) {
                $block_reason = isset($decoded_body['promptFeedback']['blockReason']) 
                    ? $decoded_body['promptFeedback']['blockReason'] 
                    : 'Unknown reason';
                throw new Exception("No candidates returned. Reason: {$block_reason}");
            }

            if (!isset($decoded_body['candidates'][0]['content']['parts'][0]['text'])) {
                $finish_reason = isset($decoded_body['candidates'][0]['finishReason']) 
                    ? $decoded_body['candidates'][0]['finishReason'] 
                    : 'Unknown';
                throw new Exception("No text found in response. Finish reason: {$finish_reason}");
            }

            $generated_text = trim($decoded_body['candidates'][0]['content']['parts'][0]['text']);
            
            // Limpiar respuesta
            $cleaned_text = $this->clean_ai_response($generated_text);
            
            if (empty($cleaned_text)) {
                throw new Exception('Respuesta vacía después de limpieza');
            }

            $result['success'] = true;
            $result['content'] = $cleaned_text;

        } catch (Exception $e) {
            $result['error'] = $e->getMessage();
            MRI_Logger::error('Error en API Gemini: ' . $e->getMessage());
        }

        return $result;
    }

    /**
     * Limpiar respuesta de IA
     * 
     * @param string $text Texto a limpiar
     * @return string Texto limpio
     */
    private function clean_ai_response($text) {
        if (empty($text)) {
            return '';
        }

        // Eliminar frases introductorias complejas
        $text = preg_replace(
            '/^(?:(?:claro|ok|perfecto|vale|bien|bueno|sure|voici|ecco|aqui\s*está),?\s*)?(?:(?:aquí|here)\s*tienes?|este\s*es|(?:it|this)\s*is|c\'est|è\s*ecco)\b.*?[:;]\s*|(?:(?:[Uu]n|[Aa]n?|[Ee]l|[Ll]a|[Tt]he|[Uu]ne?)\s+(?:texto\s+alternativo|alt\s*text|t[íi]tulo|title|leyenda|caption|descripci[oó]n|description|respuesta|answer)\b).*?[:;]\s*/iu',
            '',
            $text,
            1
        );

        // Eliminar marcadores de Markdown y comillas
        $text = str_replace('**', '', $text);
        $text = trim($text, '"\'');
        $text = trim($text);

        // Eliminar información extra después de la primera oración
        if (preg_match('/(?<!\b(?:Mr|Mrs|Ms|Dr|St|Av|etc))\.\s?/', $text, $matches, PREG_OFFSET_CAPTURE)) {
            $end_pos = $matches[0][1];
            $text_after = trim(substr($text, $end_pos + 1));
            
            // Verificar si contiene metadatos de redes sociales
            $metadata_patterns = [
                '\d+(\.\d+)?K\s+\w+',
                '\d+(\.\d+)?M\s+\w+',
                '\b(Instagram|TikTok|Facebook|YouTube|Twitter|views|followers|likes)\b'
            ];
            
            foreach ($metadata_patterns as $pattern) {
                if (preg_match('/(?:\b|\d)' . $pattern . '(?:\b|\d)/i', $text_after)) {
                    $text = trim(substr($text, 0, $end_pos + 1));
                    break;
                }
            }
        }

        return trim($text);
    }

    /**
     * Obtener contexto SEO
     * 
     * @param int $attachment_id ID del adjunto
     * @return array Contexto SEO
     */
    private function get_seo_context($attachment_id) {
        $context = [];

        if (!$this->options['include_seo_in_ai_prompt']) {
            return $context;
        }

        // Obtener post padre si existe
        $parent_post_id = wp_get_post_parent_id($attachment_id);
        if (!$parent_post_id && isset($_REQUEST['post_id'])) {
            $parent_post_id = absint($_REQUEST['post_id']);
        }

        if ($parent_post_id > 0) {
            $parent_post = get_post($parent_post_id);
            if ($parent_post instanceof WP_Post) {
                $context['post_title'] = get_the_title($parent_post_id);
                $context['post_type'] = $parent_post->post_type;

                // Obtener keyword de SEO plugins
                $context['focus_keyword'] = $this->get_focus_keyword($parent_post_id);

                // Contexto específico para WooCommerce
                if ($parent_post->post_type === 'product' && class_exists('WooCommerce')) {
                    $context['product_name'] = $context['post_title'];
                }
            }
        }

        return $context;
    }

    /**
     * Obtener focus keyword de plugins SEO
     * 
     * @param int $post_id ID del post
     * @return string|null Focus keyword
     */
    private function get_focus_keyword($post_id) {
        // Yoast SEO
        if (function_exists('YoastSEO') || defined('WPSEO_VERSION')) {
            $keyword = get_post_meta($post_id, '_yoast_wpseo_focuskw', true);
            if (!empty($keyword)) {
                return $keyword;
            }
        }

        // Rank Math
        if (defined('RANK_MATH_VERSION')) {
            $keywords = get_post_meta($post_id, 'rank_math_focus_keyword', true);
            if (!empty($keywords)) {
                return explode(',', $keywords)[0];
            }
        }

        // All in One SEO
        if (class_exists('All_in_One_SEO_Pack') || defined('AIOSEO_VERSION')) {
            try {
                $aioseo_data = get_post_meta($post_id, '_aioseo_meta', true);
                if (!empty($aioseo_data['keyphrases']['focus']['keyphrase'])) {
                    return $aioseo_data['keyphrases']['focus']['keyphrase'];
                }
            } catch (Exception $e) {
                // Ignorar errores de AIOSEO
            }
        }

        // SEOPress
        if (defined('SEOPRESS_VERSION')) {
            $keywords = get_post_meta($post_id, '_seopress_analysis_target_kw', true);
            if (!empty($keywords)) {
                return explode(',', $keywords)[0];
            }
        }

        return null;
    }

    /**
     * Formatear contexto para el prompt
     * 
     * @param array $context Contexto SEO
     * @return string Contexto formateado
     */
    private function format_context($context) {
        if (empty($context)) {
            return '';
        }

        $context_parts = [];

        if (!empty($context['post_title'])) {
            if (!empty($context['product_name'])) {
                $context_parts[] = sprintf(__(' Product Name: "%s".', 'mi-renombrador-imagenes'), $context['product_name']);
            } else {
                $context_parts[] = sprintf(__(' Page/Post Title: "%s".', 'mi-renombrador-imagenes'), $context['post_title']);
            }
        }

        if (!empty($context['focus_keyword'])) {
            $context_parts[] = sprintf(__(' Main Keyword: "%s".', 'mi-renombrador-imagenes'), $context['focus_keyword']);
        }

        return empty($context_parts) ? '' : ' Context:' . implode('', $context_parts);
    }

    /**
     * Cargar imagen en base64
     * 
     * @param string $image_path Ruta de la imagen
     * @return string|false Datos en base64 o false en caso de error
     */
    private function load_image_base64($image_path) {
        if (!file_exists($image_path) || !is_readable($image_path)) {
            return false;
        }

        $image_content = @file_get_contents($image_path);
        if ($image_content === false) {
            return false;
        }

        return base64_encode($image_content);
    }

    /**
     * Verificar si la IA está configurada
     * 
     * @return bool
     */
    private function is_ai_configured() {
        return !empty($this->options['gemini_api_key']) && 
               !empty($this->options['gemini_model']);
    }

    /**
     * Verificar si la imagen es compatible con IA
     * 
     * @param string $mime_type Tipo MIME
     * @return bool
     */
    private function is_compatible_image($mime_type) {
        return in_array($mime_type, $this->supported_mime_types);
    }

    /**
     * Obtener nombre del idioma para prompts
     * 
     * @return string
     */
    private function get_language_name() {
        $language_code = $this->options['ai_output_language'] ?? 'es';
        return $this->supported_languages[$language_code] ?? 'Español';
    }

    /**
     * Obtener configuración de seguridad para Gemini
     * 
     * @return array
     */
    private function get_safety_settings() {
        return [
            ['category' => 'HARM_CATEGORY_HARASSMENT', 'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'],
            ['category' => 'HARM_CATEGORY_HATE_SPEECH', 'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'],
            ['category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT', 'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'],
            ['category' => 'HARM_CATEGORY_DANGEROUS_CONTENT', 'threshold' => 'BLOCK_MEDIUM_AND_ABOVE']
        ];
    }

    /**
     * Probar conexión con la API
     * 
     * @return array Resultado de la prueba
     */
    public function test_api_connection() {
        $result = [
            'success' => false,
            'message' => ''
        ];

        try {
            if (!$this->is_ai_configured()) {
                throw new Exception(__('IA no configurada correctamente', 'mi-renombrador-imagenes'));
            }

            // Crear una imagen de prueba simple (1x1 pixel PNG)
            $test_image_data = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';
            
            $test_result = $this->call_gemini_api(
                'Describe this test image briefly in one word.',
                $test_image_data,
                'image/png',
                10
            );

            if ($test_result['success']) {
                $result['success'] = true;
                $result['message'] = __('Conexión exitosa con Google AI', 'mi-renombrador-imagenes');
            } else {
                throw new Exception($test_result['error']);
            }

        } catch (Exception $e) {
            $result['message'] = $e->getMessage();
        }

        return $result;
    }

    /**
     * Obtener estadísticas de uso de IA
     * 
     * @return array Estadísticas
     */
    public function get_usage_stats() {
        return [
            'total_requests' => get_option('mri_ai_total_requests', 0),
            'successful_requests' => get_option('mri_ai_successful_requests', 0),
            'failed_requests' => get_option('mri_ai_failed_requests', 0),
            'last_request' => get_option('mri_ai_last_request', null)
        ];
    }

    /**
     * Actualizar estadísticas de uso
     * 
     * @param bool $success Si la petición fue exitosa
     */
    private function update_usage_stats($success) {
        $total = get_option('mri_ai_total_requests', 0);
        update_option('mri_ai_total_requests', $total + 1);

        if ($success) {
            $successful = get_option('mri_ai_successful_requests', 0);
            update_option('mri_ai_successful_requests', $successful + 1);
        } else {
            $failed = get_option('mri_ai_failed_requests', 0);
            update_option('mri_ai_failed_requests', $failed + 1);
        }

        update_option('mri_ai_last_request', current_time('mysql'));
    }
}
