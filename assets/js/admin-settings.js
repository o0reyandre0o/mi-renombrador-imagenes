/**
 * Admin Settings JavaScript
 * Toc Toc SEO Images Plugin
 * 
 * Mejora la experiencia de usuario en la página de configuración
 * 
 * @package TocTocSEOImages
 * @version 3.6.0
 */

(function($) {
    'use strict';

    /**
     * Objeto principal para la configuración del plugin
     */
    const MRISettings = {
        
        /**
         * Inicialización
         */
        init: function() {
            this.bindEvents();
            this.setupConditionalFields();
            this.validateApiKey();
            this.setupFormValidation();
        },

        /**
         * Vincular eventos
         */
        bindEvents: function() {
            // Test de API Key
            $(document).on('click', '#mri-test-api', this.testApiKey.bind(this));
            
            // Validación en tiempo real
            $(document).on('input', '#mri_google_ai_options_gemini_api_key', this.validateApiKeyFormat.bind(this));
            $(document).on('change', '#mri_google_ai_options_gemini_model', this.validateModel.bind(this));
            
            // Mostrar/ocultar campos relacionados
            $(document).on('change', 'input[name*="enable_ai"]', this.toggleAIFields.bind(this));
            $(document).on('change', 'input[name*="enable_compression"]', this.toggleCompressionFields.bind(this));
            
            // Previsualización de configuración
            $(document).on('change', 'input, select', this.updatePreview.bind(this));
            
            // Tooltips informativos
            this.setupTooltips();
        },

        /**
         * Configurar campos condicionales
         */
        setupConditionalFields: function() {
            this.toggleAIFields();
            this.toggleCompressionFields();
            this.checkServerCapabilities();
        },

        /**
         * Mostrar/ocultar campos de IA
         */
        toggleAIFields: function() {
            const aiEnabled = $('input[name*="enable_ai"]:checked').length > 0;
            const $aiSection = $('.ai-settings-section');
            
            if (aiEnabled) {
                $aiSection.slideDown();
                this.addRequiredIndicators(['gemini_api_key', 'gemini_model']);
            } else {
                $aiSection.slideUp();
                this.removeRequiredIndicators(['gemini_api_key', 'gemini_model']);
            }
        },

        /**
         * Mostrar/ocultar campos de compresión
         */
        toggleCompressionFields: function() {
            const compressionEnabled = $('input[name*="enable_compression"]:checked').length > 0;
            const $compressionFields = $('.compression-settings');
            
            if (compressionEnabled) {
                $compressionFields.slideDown();
            } else {
                $compressionFields.slideUp();
            }
        },

        /**
         * Verificar capacidades del servidor
         */
        checkServerCapabilities: function() {
            // Verificar si Imagick está disponible
            $.post(ajaxurl, {
                action: 'mri_check_server_capabilities',
                nonce: mri_settings_params?.nonce || ''
            }, (response) => {
                if (response.success) {
                    this.displayServerInfo(response.data);
                }
            });
        },

        /**
         * Mostrar información del servidor
         */
        displayServerInfo: function(data) {
            const $serverInfo = $('#mri-server-info');
            if ($serverInfo.length === 0) return;

            let html = '<div class="mri-server-capabilities">';
            html += '<h4>🔧 Capacidades del Servidor</h4>';
            html += '<ul>';
            
            if (data.imagick) {
                html += '<li class="capability-available">✅ Imagick disponible (recomendado)</li>';
            } else {
                html += '<li class="capability-missing">⚠️ Imagick no disponible - usará GD como fallback</li>';
            }
            
            if (data.gd) {
                html += '<li class="capability-available">✅ GD disponible</li>';
            } else {
                html += '<li class="capability-missing">❌ GD no disponible - compresión limitada</li>';
            }
            
            html += `<li class="capability-info">📋 Memoria PHP: ${data.memory_limit}</li>`;
            html += `<li class="capability-info">⏱️ Tiempo límite: ${data.max_execution_time}s</li>`;
            html += `<li class="capability-info">📁 Tamaño máximo subida: ${data.upload_max_filesize}</li>`;
            
            html += '</ul></div>';
            
            $serverInfo.html(html);
        },

        /**
         * Validar formato de API Key
         */
        validateApiKeyFormat: function(e) {
            const $input = $(e.target);
            const apiKey = $input.val().trim();
            const $feedback = $input.siblings('.api-key-feedback');
            
            // Remover feedback anterior
            $feedback.remove();
            
            if (apiKey.length === 0) return;
            
            // Validación básica del formato
            let isValid = true;
            let message = '';
            
            if (apiKey.length < 20) {
                isValid = false;
                message = '⚠️ La API Key parece muy corta';
            } else if (!apiKey.match(/^[A-Za-z0-9_-]+$/)) {
                isValid = false;
                message = '❌ Formato de API Key inválido';
            } else {
                message = '✅ Formato válido';
            }
            
            const feedbackClass = isValid ? 'valid' : 'invalid';
            $input.after(`<div class="api-key-feedback ${feedbackClass}">${message}</div>`);
        },

        /**
         * Test de API Key
         */
        testApiKey: function(e) {
            e.preventDefault();
            
            const apiKey = $('#mri_google_ai_options_gemini_api_key').val().trim();
            const model = $('#mri_google_ai_options_gemini_model').val().trim();
            
            if (!apiKey) {
                this.showNotice('error', 'Por favor, introduce una API Key antes de probar.');
                return;
            }
            
            const $button = $(e.target);
            const originalText = $button.text();
            
            $button.text('Probando...').prop('disabled', true);
            
            $.post(ajaxurl, {
                action: 'mri_test_api_key',
                nonce: mri_settings_params?.nonce || '',
                api_key: apiKey,
                model: model
            }, (response) => {
                if (response.success) {
                    this.showNotice('success', '✅ API Key válida y funcional');
                } else {
                    this.showNotice('error', '❌ Error: ' + response.data.message);
                }
            }).fail(() => {
                this.showNotice('error', '❌ Error de conexión al probar la API');
            }).always(() => {
                $button.text(originalText).prop('disabled', false);
            });
        },

        /**
         * Validar modelo seleccionado
         */
        validateModel: function(e) {
            const $select = $(e.target);
            const model = $select.val();
            const $feedback = $select.siblings('.model-feedback');
            
            $feedback.remove();
            
            if (!model) return;
            
            // Verificar si es un modelo multimodal
            const isMultimodal = model.includes('1.5') || model.includes('vision');
            
            if (!isMultimodal) {
                $select.after('<div class="model-feedback invalid">⚠️ Este modelo podría no soportar análisis de imágenes</div>');
            } else {
                $select.after('<div class="model-feedback valid">✅ Modelo multimodal compatible</div>');
            }
        },

        /**
         * Configurar validación del formulario
         */
        setupFormValidation: function() {
            $('form').on('submit', (e) => {
                const errors = this.validateForm();
                
                if (errors.length > 0) {
                    e.preventDefault();
                    this.showValidationErrors(errors);
                }
            });
        },

        /**
         * Validar formulario completo
         */
        validateForm: function() {
            const errors = [];
            
            // Validar configuración de IA
            const aiEnabled = $('input[name*="enable_ai"]:checked').length > 0;
            if (aiEnabled) {
                const apiKey = $('#mri_google_ai_options_gemini_api_key').val().trim();
                if (!apiKey) {
                    errors.push('API Key de Google AI es requerida cuando las funciones de IA están activadas');
                }
            }
            
            // Validar calidad JPEG
            const jpegQuality = parseInt($('#mri_google_ai_options_jpeg_quality').val());
            if (jpegQuality < 60 || jpegQuality > 100) {
                errors.push('La calidad JPEG debe estar entre 60 y 100');
            }
            
            return errors;
        },

        /**
         * Mostrar errores de validación
         */
        showValidationErrors: function(errors) {
            let message = '❌ Se encontraron los siguientes errores:\n\n';
            errors.forEach((error, index) => {
                message += `${index + 1}. ${error}\n`;
            });
            
            alert(message);
        },

        /**
         * Actualizar previsualización
         */
        updatePreview: function() {
            const $preview = $('#mri-config-preview');
            if ($preview.length === 0) return;
            
            const config = this.getCurrentConfig();
            let html = '<div class="config-preview">';
            html += '<h4>📋 Configuración Actual</h4>';
            html += '<ul>';
            
            if (config.rename) html += '<li>✅ Renombrado automático</li>';
            if (config.compression) html += `<li>✅ Compresión (calidad: ${config.quality}%)</li>`;
            if (config.ai_title) html += '<li>🤖 Título con IA</li>';
            if (config.ai_alt) html += '<li>🤖 Alt Text con IA</li>';
            if (config.ai_caption) html += '<li>🤖 Leyenda con IA</li>';
            if (config.language) html += `<li>🌐 Idioma: ${config.language}</li>`;
            
            html += '</ul></div>';
            $preview.html(html);
        },

        /**
         * Obtener configuración actual
         */
        getCurrentConfig: function() {
            return {
                rename: $('#mri_google_ai_options_enable_rename').is(':checked'),
                compression: $('#mri_google_ai_options_enable_compression').is(':checked'),
                quality: $('#mri_google_ai_options_jpeg_quality').val(),
                ai_title: $('#mri_google_ai_options_enable_ai_title').is(':checked'),
                ai_alt: $('#mri_google_ai_options_enable_ai_alt').is(':checked'),
                ai_caption: $('#mri_google_ai_options_enable_ai_caption').is(':checked'),
                language: $('#mri_google_ai_options_ai_output_language option:selected').text()
            };
        },

        /**
         * Configurar tooltips
         */
        setupTooltips: function() {
            // Agregar tooltips a campos complejos
            $('[data-tooltip]').each(function() {
                const $this = $(this);
                const tooltip = $this.data('tooltip');
                
                $this.on('mouseenter', function() {
                    $('body').append(`<div class="mri-tooltip">${tooltip}</div>`);
                    const $tooltip = $('.mri-tooltip');
                    const pos = $this.offset();
                    
                    $tooltip.css({
                        top: pos.top - $tooltip.outerHeight() - 5,
                        left: pos.left + ($this.outerWidth() / 2) - ($tooltip.outerWidth() / 2)
                    });
                }).on('mouseleave', function() {
                    $('.mri-tooltip').remove();
                });
            });
        },

        /**
         * Agregar indicadores de campo requerido
         */
        addRequiredIndicators: function(fields) {
            fields.forEach(field => {
                const $field = $(`#mri_google_ai_options_${field}`);
                const $label = $(`label[for="${$field.attr('id')}"]`);
                if ($label.find('.required').length === 0) {
                    $label.append(' <span class="required">*</span>');
                }
            });
        },

        /**
         * Remover indicadores de campo requerido
         */
        removeRequiredIndicators: function(fields) {
            fields.forEach(field => {
                const $field = $(`#mri_google_ai_options_${field}`);
                const $label = $(`label[for="${$field.attr('id')}"]`);
                $label.find('.required').remove();
            });
        },

        /**
         * Mostrar notificación
         */
        showNotice: function(type, message) {
            const $notice = $(`<div class="notice notice-${type} is-dismissible mri-notice"><p>${message}</p></div>`);
            $('.wrap h1').after($notice);
            
            // Auto-remover después de 5 segundos
            setTimeout(() => {
                $notice.fadeOut(() => $notice.remove());
            }, 5000);
        },

        /**
         * Validar API Key en tiempo real
         */
        validateApiKey: function() {
            const $apiKeyField = $('#mri_google_ai_options_gemini_api_key');
            if ($apiKeyField.length === 0) return;
            
            // Agregar botón de test si no existe
            if ($apiKeyField.siblings('#mri-test-api').length === 0) {
                $apiKeyField.after('<button type="button" id="mri-test-api" class="button">Probar API Key</button>');
            }
        }
    };

    /**
     * Inicializar cuando el DOM esté listo
     */
    $(document).ready(function() {
        if ($('.mri-admin-page').length || $('body').hasClass('settings_page_mri_google_ai_settings')) {
            MRISettings.init();
            
            // Debug mode
            if (typeof mri_settings_params !== 'undefined' && mri_settings_params.debug) {
                window.MRISettings = MRISettings;
                console.log('MRI Settings initialized in debug mode');
            }
        }
    });

})(jQuery);
