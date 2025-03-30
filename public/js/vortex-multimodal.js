/**
 * Multimodal processing functionality
 */
(function($) {
    'use strict';
    
    // Initialize when document is ready
    $(document).ready(function() {
        // Handle file selection
        $('.vortex-file-input').on('change', function() {
            var file = this.files[0];
            if (!file) return;
            
            var container = $(this).closest('.vortex-multimodal-uploader');
            var preview = container.find('.vortex-upload-preview');
            var reader = new FileReader();
            
            // Create preview based on file type
            reader.onload = function(e) {
                var fileType = file.type;
                var html = '';
                
                if (fileType.indexOf('image/') === 0) {
                    html = '<div class="vortex-file-preview">' +
                          '<img src="' + e.target.result + '" alt="Preview" />' +
                          '<span class="vortex-file-name">' + file.name + '</span>' +
                          '</div>';
                } else {
                    var icon = fileType.indexOf('pdf') > -1 ? 'dashicons-pdf' : 
                              (fileType.indexOf('csv') > -1 ? 'dashicons-media-spreadsheet' : 'dashicons-media-document');
                    
                    html = '<div class="vortex-file-preview">' +
                          '<span class="dashicons ' + icon + '"></span>' +
                          '<span class="vortex-file-name">' + file.name + '</span>' +
                          '</div>';
                }
                
                preview.html(html).show();
            };
            
            reader.readAsDataURL(file);
        });
        
        // Handle file upload
        $('.vortex-upload-button').on('click', function() {
            var container = $(this).closest('.vortex-multimodal-uploader');
            var fileInput = container.find('.vortex-file-input');
            var message = container.find('.vortex-upload-message');
            var targetAgent = container.data('target');
            
            if (fileInput[0].files.length === 0) {
                message.html('<div class="vortex-notice vortex-notice-error">Please select a file first.</div>').show();
                return;
            }
            
            var file = fileInput[0].files[0];
            var maxSize = 10 * 1024 * 1024; // 10MB
            
            if (file.size > maxSize) {
                message.html('<div class="vortex-notice vortex-notice-error">File is too large. Maximum size is 10MB.</div>').show();
                return;
            }
            
            // Create form data
            var formData = new FormData();
            formData.append('action', 'vortex_upload_multimodal');
            formData.append('multimodal_file', file);
            formData.append('target_agent', targetAgent);
            formData.append('nonce', container.find('[name="multimodal_nonce"]').val());
            
            // Show loading indicator
            message.html('<div class="vortex-loading">Uploading file...</div>').show();
            
            // Send AJAX request
            $.ajax({
                url: vortex_ajax.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        message.html('<div class="vortex-notice vortex-notice-success">File uploaded successfully!</div>');
                        
                        // Trigger event for parent components to handle
                        container.trigger('vortex_multimodal_uploaded', [response.data]);
                        
                        // Store reference to the uploaded file for the AI agents
                        if (targetAgent) {
                            var targetForm = $('.vortex-' + targetAgent.toLowerCase() + '-form');
                            if (targetForm.length) {
                                targetForm.append('<input type="hidden" name="multimodal_data" value="' + encodeURIComponent(JSON.stringify(response.data)) + '" />');
                                targetForm.find('.vortex-multimodal-status').html('<div class="vortex-multimodal-badge">' + 
                                                                                 '<span class="dashicons dashicons-paperclip"></span>' + 
                                                                                 'Using uploaded ' + response.data.file_type + '</div>');
                            }
                        }
                    } else {
                        message.html('<div class="vortex-notice vortex-notice-error">' + response.data.message + '</div>');
                    }
                },
                error: function() {
                    message.html('<div class="vortex-notice vortex-notice-error">Error uploading file. Please try again.</div>');
                }
            });
        });
        
        // Drag and drop functionality
        var fileArea = $('.vortex-file-upload-area');
        
        fileArea.on('dragover dragenter', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).addClass('vortex-dragover');
        });
        
        fileArea.on('dragleave dragend drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).removeClass('vortex-dragover');
        });
        
        fileArea.on('drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var files = e.originalEvent.dataTransfer.files;
            if (files.length) {
                $(this).find('.vortex-file-input')[0].files = files;
                $(this).find('.vortex-file-input').trigger('change');
            }
        });
    });
    
})(jQuery); 