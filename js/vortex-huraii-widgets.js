jQuery(document).ready(function($) {
    'use strict';

    // Generator Widget
    const generatorWidget = {
        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            $('.vortex-huraii-generator').on('click', '.vortex-huraii-advanced-toggle', this.toggleAdvancedOptions);
            $('.vortex-huraii-generator').on('submit', '.vortex-huraii-generator-form', this.handleSubmit);
        },

        toggleAdvancedOptions: function(e) {
            e.preventDefault();
            const $options = $(this).closest('.vortex-huraii-generator').find('.vortex-huraii-advanced-options');
            $options.slideToggle(200);
            $(this).toggleClass('active');
        },

        handleSubmit: function(e) {
            e.preventDefault();
            const $form = $(this);
            const $widget = $form.closest('.vortex-huraii-generator');
            const $loading = $widget.find('.vortex-huraii-loading');
            const $error = $widget.find('.vortex-huraii-error');
            const $result = $widget.find('.vortex-huraii-result');

            // Reset UI
            $error.removeClass('show').empty();
            $result.removeClass('show').empty();

            // Show loading
            $loading.addClass('show');

            // Get form data
            const formData = new FormData($form[0]);
            formData.append('action', 'vortex_generate_artwork');
            formData.append('nonce', vortexHuraii.nonce);

            // Send request
            $.ajax({
                url: vortexHuraii.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        $result.html(response.data.html).addClass('show');
                    } else {
                        $error.html(response.data.message).addClass('show');
                    }
                },
                error: function() {
                    $error.html(vortexHuraii.i18n.error).addClass('show');
                },
                complete: function() {
                    $loading.removeClass('show');
                }
            });
        }
    };

    // Style Transfer Widget
    const styleTransferWidget = {
        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            $('.vortex-huraii-style-transfer').on('click', '.vortex-huraii-upload-area', this.handleUploadClick);
            $('.vortex-huraii-style-transfer').on('change', '.vortex-huraii-file-input', this.handleFileSelect);
            $('.vortex-huraii-style-transfer').on('submit', '.vortex-huraii-style-transfer-form', this.handleSubmit);
        },

        handleUploadClick: function(e) {
            e.preventDefault();
            $(this).find('.vortex-huraii-file-input').click();
        },

        handleFileSelect: function(e) {
            const file = e.target.files[0];
            if (!file) return;

            const $widget = $(this).closest('.vortex-huraii-style-transfer');
            const $preview = $widget.find('.vortex-huraii-preview');
            const $previewImg = $preview.find('img');

            // Validate file type
            if (!file.type.startsWith('image/')) {
                $widget.find('.vortex-huraii-error')
                    .html(vortexHuraii.i18n.invalidFileType)
                    .addClass('show');
                return;
            }

            // Create preview
            const reader = new FileReader();
            reader.onload = function(e) {
                $previewImg.attr('src', e.target.result);
                $preview.addClass('show');
            };
            reader.readAsDataURL(file);
        },

        handleSubmit: function(e) {
            e.preventDefault();
            const $form = $(this);
            const $widget = $form.closest('.vortex-huraii-style-transfer');
            const $loading = $widget.find('.vortex-huraii-loading');
            const $error = $widget.find('.vortex-huraii-error');
            const $result = $widget.find('.vortex-huraii-result');

            // Reset UI
            $error.removeClass('show').empty();
            $result.removeClass('show').empty();

            // Show loading
            $loading.addClass('show');

            // Get form data
            const formData = new FormData($form[0]);
            formData.append('action', 'vortex_style_transfer');
            formData.append('nonce', vortexHuraii.nonce);

            // Send request
            $.ajax({
                url: vortexHuraii.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        $result.html(response.data.html).addClass('show');
                    } else {
                        $error.html(response.data.message).addClass('show');
                    }
                },
                error: function() {
                    $error.html(vortexHuraii.i18n.error).addClass('show');
                },
                complete: function() {
                    $loading.removeClass('show');
                }
            });
        }
    };

    // Analysis Widget
    const analysisWidget = {
        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            $('.vortex-huraii-analysis').on('click', '.vortex-huraii-upload-area', this.handleUploadClick);
            $('.vortex-huraii-analysis').on('change', '.vortex-huraii-file-input', this.handleFileSelect);
            $('.vortex-huraii-analysis').on('submit', '.vortex-huraii-analysis-form', this.handleSubmit);
        },

        handleUploadClick: function(e) {
            e.preventDefault();
            $(this).find('.vortex-huraii-file-input').click();
        },

        handleFileSelect: function(e) {
            const file = e.target.files[0];
            if (!file) return;

            const $widget = $(this).closest('.vortex-huraii-analysis');
            const $preview = $widget.find('.vortex-huraii-preview');
            const $previewImg = $preview.find('img');

            // Validate file type
            if (!file.type.startsWith('image/')) {
                $widget.find('.vortex-huraii-error')
                    .html(vortexHuraii.i18n.invalidFileType)
                    .addClass('show');
                return;
            }

            // Create preview
            const reader = new FileReader();
            reader.onload = function(e) {
                $previewImg.attr('src', e.target.result);
                $preview.addClass('show');
            };
            reader.readAsDataURL(file);
        },

        handleSubmit: function(e) {
            e.preventDefault();
            const $form = $(this);
            const $widget = $form.closest('.vortex-huraii-analysis');
            const $loading = $widget.find('.vortex-huraii-loading');
            const $error = $widget.find('.vortex-huraii-error');
            const $result = $widget.find('.vortex-huraii-analysis-result');

            // Reset UI
            $error.removeClass('show').empty();
            $result.removeClass('show').empty();

            // Show loading
            $loading.addClass('show');

            // Get form data
            const formData = new FormData($form[0]);
            formData.append('action', 'vortex_analyze_artwork');
            formData.append('nonce', vortexHuraii.nonce);

            // Send request
            $.ajax({
                url: vortexHuraii.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        $result.html(response.data.html).addClass('show');
                    } else {
                        $error.html(response.data.message).addClass('show');
                    }
                },
                error: function() {
                    $error.html(vortexHuraii.i18n.error).addClass('show');
                },
                complete: function() {
                    $loading.removeClass('show');
                }
            });
        }
    };

    // Initialize widgets
    generatorWidget.init();
    styleTransferWidget.init();
    analysisWidget.init();
}); 