/**
 * Admin Batch Processing JavaScript
 * Toc Toc SEO Images Plugin
 * 
 * Maneja el procesamiento masivo de imágenes via AJAX
 * 
 * @package TocTocSEOImages
 * @version 3.6.0
 */

(function($) {
    'use strict';

    /**
     * Objeto principal para el procesamiento masivo
     */
    const MRIBatchProcessor = {
        
        // Estado del procesamiento
        isProcessing: false,
        shouldStop: false,
        currentOffset: 0,
        totalImages: 0,
        processedCount: 0,
        batchSize: 5, // Tamaño por defecto, se ajusta dinámicamente
        
        // Elementos DOM
        elements: {
            startButton: null,
            stopButton: null,
            spinner: null,
            progressBar: null,
            progressText: null,
            logContainer: null,
            logList: null,
            criteriaCheckbox: null
        },

        /**
         * Inicialización del procesador
         */
        init: function() {
            this.cacheElements();
            this.bindEvents();
            this.setupInitialState();
        },

        /**
         * Cachear elementos DOM
         */
        cacheElements: function() {
            this.elements.startButton = $('#mri-start-processing');
            this.elements.stopButton = $('#mri-stop-processing');
            this.elements.spinner = $('#mri-bulk-spinner');
            this.elements.progressBar = $('#mri-progress-bar');
            this.elements.progressText = $('#mri-progress-text');
            this.elements.logContainer = $('#mri-bulk-log');
            this.elements.logList = $('#mri-log-list');
            this.elements.criteriaCheckbox = $('#mri-criteria');
        },

        /**
         * Vincular eventos
         */
        bindEvents: function() {
            this.elements.startButton.on('click', this.startProcessing.bind(this));
            this.elements.stopButton.on('click', this.stopProcessing.bind(this));
        },

        /**
         * Configurar estado inicial
         */
        setupInitialState: function() {
            this.elements.stopButton.hide();
            this.elements.spinner.removeClass('is-active');
            $('#mri-bulk-progress').hide();
            this.elements.logContainer.hide();
        },

        /**
         * Iniciar procesamiento
         */
        startProcessing: function() {
            if (this.isProcessing) return;

            // Confirmación si hay muchas imágenes
            if (!this.elements.criteriaCheckbox.is(':checked')) {
                if (!confirm('⚠️ Vas a procesar TODAS las imágenes de la biblioteca. Esto puede tardar mucho tiempo. ¿Continuar?')) {
                    return;
                }
            }

            this.isProcessing = true;
            this.shouldStop = false;
            this.currentOffset = 0;
            this.processedCount = 0;

            this.updateUI('processing');
            this.showLog();
            this.addLogMessage('info', '🚀 Iniciando procesamiento masivo...');

            // Obtener total de imágenes primero
            this.getTotalImages();
        },

        /**
         * Detener procesamiento
         */
        stopProcessing: function() {
            if (!this.isProcessing) return;

            if (confirm(mri_bulk_params.text_confirm_stop)) {
                this.shouldStop = true;
                this.elements.stopButton.text(mri_bulk_params.text_stopping).prop('disabled', true);
                this.addLogMessage('notice', '⏹️ Deteniendo procesamiento...');
            }
        },

        /**
         * Obtener total de imágenes a procesar
         */
        getTotalImages: function() {
            const criteria = this.elements.criteriaCheckbox.is(':checked') ? 'missing_alt' : 'all';

            $.ajax({
                url: mri_bulk_params.ajax_url,
                type: 'POST',
                data: {
                    action: mri_bulk_params.action_total,
                    nonce: mri_bulk_params.nonce,
                    criteria: criteria
                },
                success: (response) => {
                    if (response.success) {
                        this.totalImages = response.data.total;
                        
                        if (this.totalImages === 0) {
                            this.addLogMessage('notice', '📋 No se encontraron imágenes para procesar con los criterios seleccionados.');
                            this.completeProcessing();
                            return;
                        }

                        this.addLogMessage('info', `📊 Total de imágenes a procesar: ${this.totalImages}`);
                        this.setupProgressBar();
                        
                        // Ajustar tamaño de lote basado en el total
                        this.batchSize = this.calculateOptimalBatchSize();
                        this.addLogMessage('info', `⚙️ Procesando en lotes de ${this.batchSize} imágenes...`);
                        
                        // Comenzar procesamiento
                        setTimeout(() => this.processBatch(), 500);
                    } else {
                        this.handleError('Error obteniendo total de imágenes: ' + response.data.message);
                    }
                },
                error: (xhr, status, error) => {
                    this.handleError('Error AJAX obteniendo total: ' + error);
                }
            });
        },

        /**
         * Calcular tamaño óptimo de lote
         */
        calculateOptimalBatchSize: function() {
            if (this.totalImages <= 10) return 2;
            if (this.totalImages <= 50) return 3;
            if (this.totalImages <= 200) return 5;
            return 8; // Máximo para evitar timeouts
        },

        /**
         * Configurar barra de progreso
         */
        setupProgressBar: function() {
            this.elements.progressBar.attr('max', this.totalImages).val(0);
            this.updateProgress();
            $('#mri-bulk-progress').show();
        },

        /**
         * Procesar lote de imágenes
         */
        processBatch: function() {
            if (this.shouldStop) {
                this.completeProcessing('stopped');
                return;
            }

            const criteria = this.elements.criteriaCheckbox.is(':checked') ? 'missing_alt' : 'all';

            $.ajax({
                url: mri_bulk_params.ajax_url,
                type: 'POST',
                data: {
                    action: mri_bulk_params.action_batch,
                    nonce: mri_bulk_params.nonce,
                    offset: this.currentOffset,
                    batchSize: this.batchSize,
                    criteria: criteria
                },
                timeout: 120000, // 2 minutos timeout
                success: (response) => {
                    if (response.success) {
                        const data = response.data;
                        this.processedCount += data.processedCount;
                        this.currentOffset += this.batchSize;

                        // Mostrar logs del lote
                        if (data.logMessages && data.logMessages.length > 0) {
                            data.logMessages.forEach(logItem => {
                                if (typeof logItem === 'object' && logItem.message) {
                                    this.addLogMessage(logItem.type || 'info', logItem.message);
                                } else if (typeof logItem === 'string') {
                                    this.addLogMessage('info', logItem);
                                }
                            });
                        }

                        this.updateProgress();

                        // Verificar si hay más imágenes que procesar
                        if (data.processedCount > 0 && this.processedCount < this.totalImages) {
                            // Pausa entre lotes para no saturar el servidor
                            setTimeout(() => this.processBatch(), 1000);
                        } else {
                            this.completeProcessing('completed');
                        }
                    } else {
                        this.handleError('Error procesando lote: ' + response.data.message);
                    }
                },
                error: (xhr, status, error) => {
                    if (status === 'timeout') {
                        this.addLogMessage('error', '⏱️ Timeout procesando lote. Reintentando...');
                        // Reducir tamaño de lote y reintentar
                        this.batchSize = Math.max(1, Math.floor(this.batchSize / 2));
                        setTimeout(() => this.processBatch(), 2000);
                    } else {
                        this.handleError('Error AJAX procesando lote: ' + error);
                    }
                }
            });
        },

        /**
         * Actualizar progreso visual
         */
        updateProgress: function() {
            const progress = Math.min(this.processedCount, this.totalImages);
            const percentage = this.totalImages > 0 ? Math.round((progress / this.totalImages) * 100) : 0;
            
            this.elements.progressBar.val(progress);
            this.elements.progressText.text(`${progress} / ${this.totalImages} (${percentage}%)`);
        },

        /**
         * Completar procesamiento
         */
        completeProcessing: function(status = 'completed') {
            this.isProcessing = false;
            this.shouldStop = false;

            if (status === 'completed') {
                this.addLogMessage('success', `✅ Procesamiento completado. Total procesadas: ${this.processedCount}`);
            } else if (status === 'stopped') {
                this.addLogMessage('notice', `⏹️ Procesamiento detenido por el usuario. Procesadas: ${this.processedCount}`);
            }

            this.updateUI('completed');
        },

        /**
         * Manejar errores
         */
        handleError: function(message) {
            this.addLogMessage('error', '❌ ' + message);
            this.completeProcessing('error');
            console.error('MRI Batch Error:', message);
        },

        /**
         * Actualizar interfaz de usuario
         */
        updateUI: function(state) {
            switch(state) {
                case 'processing':
                    this.elements.startButton.text(mri_bulk_params.text_processing).prop('disabled', true);
                    this.elements.stopButton.show().prop('disabled', false).text(mri_bulk_params.text_stop);
                    this.elements.spinner.addClass('is-active');
                    break;
                    
                case 'completed':
                case 'error':
                    this.elements.startButton.text(mri_bulk_params.text_start).prop('disabled', false);
                    this.elements.stopButton.hide();
                    this.elements.spinner.removeClass('is-active');
                    break;
            }
        },

        /**
         * Mostrar contenedor de logs
         */
        showLog: function() {
            this.elements.logContainer.show();
            this.elements.logList.empty();
        },

        /**
         * Añadir mensaje al log
         */
        addLogMessage: function(type, message) {
            const timestamp = new Date().toLocaleTimeString();
            const $logItem = $('<li></li>')
                .addClass(`mri-log-${type}`)
                .html(`<strong>[${timestamp}]</strong> ${this.escapeHtml(message)}`);
            
            this.elements.logList.append($logItem);
            
            // Auto-scroll al último mensaje
            this.elements.logContainer.scrollTop(this.elements.logContainer[0].scrollHeight);
            
            // Limitar número de mensajes visible (mantener últimos 100)
            const logItems = this.elements.logList.children();
            if (logItems.length > 100) {
                logItems.first().remove();
            }
        },

        /**
         * Escapar HTML para prevenir XSS
         */
        escapeHtml: function(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    };

    /**
     * Inicializar cuando el DOM esté listo
     */
    $(document).ready(function() {
        // Verificar que estamos en la página correcta
        if ($('#mri-bulk-wrap').length) {
            MRIBatchProcessor.init();
            
            // Debug mode
            if (typeof mri_bulk_params !== 'undefined' && mri_bulk_params.debug) {
                window.MRIBatchProcessor = MRIBatchProcessor;
                console.log('MRI Batch Processor initialized in debug mode');
            }
        }
    });

    /**
     * Prevenir navegación accidental durante procesamiento
     */
    $(window).on('beforeunload', function(e) {
        if (MRIBatchProcessor.isProcessing) {
            const message = '¿Estás seguro de que quieres salir? El procesamiento de imágenes está en curso y se perderá el progreso.';
            e.returnValue = message;
            return message;
        }
    });

})(jQuery);
