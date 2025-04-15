/**
 * Thorius AI Admin JavaScript
 * 
 * Handles all JavaScript functionality for the Thorius AI admin interface,
 * including meta boxes, analysis tools, and content generation.
 */
(function($) {
    'use strict';

    // Initialize when document is ready
    $(document).ready(function() {
        initMetaBoxes();
    });

    /**
     * Initialize all meta box functionality
     */
    function initMetaBoxes() {
        // Analysis meta box
        if ($('.thorius-analyze-button').length) {
            initAnalysisMetaBox();
        }

        // Suggestions meta box
        if ($('.thorius-generate-button').length) {
            initSuggestionsMetaBox();
        }

        // SEO meta box
        if ($('.thorius-analyze-seo-button').length) {
            initSeoMetaBox();
        }

        // Product meta box
        if ($('.thorius-generate-product-button').length) {
            initProductMetaBox();
        }

        // Image meta box
        if ($('.thorius-generate-image-button').length) {
            initImageMetaBox();
        }
    }

    /**
     * Initialize AI Analysis meta box
     */
    function initAnalysisMetaBox() {
        $('.thorius-analyze-button').on('click', function() {
            var $button = $(this);
            var $spinner = $button.siblings('.thorius-spinner');
            var postId = $button.data('post-id');
            var nonce = $button.data('nonce');

            // Show spinner, disable button
            $spinner.addClass('is-active');
            $button.prop('disabled', true);

            // Ajax request to analyze content
            $.ajax({
                url: thorius_admin_params.ajax_url,
                type: 'POST',
                data: {
                    action: 'thorius_analyze_content',
                    post_id: postId,
                    nonce: nonce
                },
                success: function(response) {
                    // Hide spinner, enable button
                    $spinner.removeClass('is-active');
                    $button.prop('disabled', false);

                    if (response.success) {
                        // Reload meta box content
                        location.reload();
                    } else {
                        // Show error message
                        alert(response.data.message || thorius_admin_params.i18n.error);
                    }
                },
                error: function() {
                    // Hide spinner, enable button
                    $spinner.removeClass('is-active');
                    $button.prop('disabled', false);

                    // Show error message
                    alert(thorius_admin_params.i18n.error);
                }
            });
        });
    }

    /**
     * Initialize AI Suggestions meta box
     */
    function initSuggestionsMetaBox() {
        $('.thorius-generate-button').on('click', function() {
            var $button = $(this);
            var $spinner = $button.siblings('.thorius-spinner');
            var $results = $('#thorius-suggestion-results');
            var postId = $button.data('post-id');
            var nonce = $button.data('nonce');
            var suggestionType = $('#thorius-suggestion-type').val();

            // Show spinner, disable button
            $spinner.addClass('is-active');
            $button.prop('disabled', true);

            // Clear previous results
            $results.empty();
            $results.append('<p class="thorius-generating">' + thorius_admin_params.i18n.loading + '</p>');

            // Ajax request to generate suggestion
            $.ajax({
                url: thorius_admin_params.ajax_url,
                type: 'POST',
                data: {
                    action: 'thorius_generate_suggestion',
                    post_id: postId,
                    suggestion_type: suggestionType,
                    nonce: nonce
                },
                success: function(response) {
                    // Hide spinner, enable button
                    $spinner.removeClass('is-active');
                    $button.prop('disabled', false);

                    // Clear loading message
                    $results.empty();

                    if (response.success) {
                        // Display generated content
                        $results.append('<div class="thorius-suggestion-result">' +
                            '<p class="thorius-suggestion-content">' + response.data.content + '</p>' +
                            '<div class="thorius-suggestion-actions">' +
                            '<button type="button" class="button thorius-use-suggestion" data-content="' + 
                            encodeURIComponent(response.data.content) + '">' +
                            '<span class="dashicons dashicons-yes"></span> ' + 
                            'Use This</button>' +
                            '</div>' +
                            '</div>');

                        // Initialize use suggestion button
                        initUseSuggestionButton();
                    } else {
                        // Show error message
                        $results.append('<p class="thorius-error">' + 
                        (response.data.message || thorius_admin_params.i18n.error) + '</p>');
                    }
                },
                error: function() {
                    // Hide spinner, enable button
                    $spinner.removeClass('is-active');
                    $button.prop('disabled', false);

                    // Clear loading message
                    $results.empty();

                    // Show error message
                    $results.append('<p class="thorius-error">' + thorius_admin_params.i18n.error + '</p>');
                }
            });
        });
    }

    /**
     * Initialize use suggestion button
     */
    function initUseSuggestionButton() {
        $('.thorius-use-suggestion').on('click', function() {
            var content = decodeURIComponent($(this).data('content'));
            var suggestionType = $('#thorius-suggestion-type').val();

            // Handle different types of suggestions
            switch (suggestionType) {
                case 'headline':
                    // Update post title
                    $('#title').val(content);
                    break;
                case 'meta_description':
                    // Update Yoast SEO meta description if available
                    if ($('#yoast_wpseo_metadesc').length) {
                        $('#yoast_wpseo_metadesc').val(content);
                    }
                    break;
                case 'intro':
                case 'conclusion':
                    // Add to editor content (support for classic and block editor)
                    if (typeof wp !== 'undefined' && wp.data && wp.data.select('core/editor')) {
                        // Block editor
                        var currentContent = wp.data.select('core/editor').getEditedPostContent();
                        var newContent = suggestionType === 'intro' ? 
                            content + '\n\n' + currentContent : 
                            currentContent + '\n\n' + content;
                        wp.data.dispatch('core/editor').editPost({ content: newContent });
                    } else if (typeof tinyMCE !== 'undefined' && tinyMCE.get('content')) {
                        // Classic editor - TinyMCE
                        var editor = tinyMCE.get('content');
                        var currentContent = editor.getContent();
                        var newContent = suggestionType === 'intro' ? 
                            '<p>' + content + '</p>' + currentContent : 
                            currentContent + '<p>' + content + '</p>';
                        editor.setContent(newContent);
                    } else if ($('#content').length) {
                        // Classic editor - Textarea
                        var $textarea = $('#content');
                        var currentContent = $textarea.val();
                        var newContent = suggestionType === 'intro' ? 
                            content + '\n\n' + currentContent : 
                            currentContent + '\n\n' + content;
                        $textarea.val(newContent);
                    }
                    break;
                case 'social_media':
                    // Copy to clipboard
                    copyToClipboard(content);
                    alert('Social media post copied to clipboard!');
                    break;
            }
        });
    }

    /**
     * Initialize SEO meta box
     */
    function initSeoMetaBox() {
        $('.thorius-analyze-seo-button').on('click', function() {
            var $button = $(this);
            var $spinner = $button.siblings('.thorius-spinner');
            var postId = $button.data('post-id');
            var nonce = $button.data('nonce');

            // Show spinner, disable button
            $spinner.addClass('is-active');
            $button.prop('disabled', true);

            // Ajax request to analyze SEO
            $.ajax({
                url: thorius_admin_params.ajax_url,
                type: 'POST',
                data: {
                    action: 'thorius_analyze_seo',
                    post_id: postId,
                    nonce: nonce
                },
                success: function(response) {
                    // Hide spinner, enable button
                    $spinner.removeClass('is-active');
                    $button.prop('disabled', false);

                    if (response.success) {
                        // Reload meta box content
                        location.reload();
                    } else {
                        // Show error message
                        alert(response.data.message || thorius_admin_params.i18n.error);
                    }
                },
                error: function() {
                    // Hide spinner, enable button
                    $spinner.removeClass('is-active');
                    $button.prop('disabled', false);

                    // Show error message
                    alert(thorius_admin_params.i18n.error);
                }
            });
        });
    }

    /**
     * Initialize Product meta box
     */
    function initProductMetaBox() {
        $('.thorius-generate-product-button').on('click', function() {
            var $button = $(this);
            var $spinner = $button.siblings('.thorius-spinner');
            var $results = $('#thorius-product-suggestion-results');
            var postId = $button.data('post-id');
            var nonce = $button.data('nonce');
            var suggestionType = $('#thorius-product-suggestion-type').val();

            // Show spinner, disable button
            $spinner.addClass('is-active');
            $button.prop('disabled', true);

            // Clear previous results
            $results.empty();
            $results.append('<p class="thorius-generating">' + thorius_admin_params.i18n.loading + '</p>');

            // Ajax request to generate product suggestion
            $.ajax({
                url: thorius_admin_params.ajax_url,
                type: 'POST',
                data: {
                    action: 'thorius_generate_product_suggestion',
                    post_id: postId,
                    suggestion_type: suggestionType,
                    nonce: nonce
                },
                success: function(response) {
                    // Hide spinner, enable button
                    $spinner.removeClass('is-active');
                    $button.prop('disabled', false);

                    // Clear loading message
                    $results.empty();

                    if (response.success) {
                        // Display generated content
                        $results.append('<div class="thorius-suggestion-result">' +
                            '<p class="thorius-suggestion-content">' + response.data.content + '</p>' +
                            '<div class="thorius-suggestion-actions">' +
                            '<button type="button" class="button thorius-use-product-suggestion" data-content="' + 
                            encodeURIComponent(response.data.content) + '" data-type="' + suggestionType + '">' +
                            '<span class="dashicons dashicons-yes"></span> ' + 
                            'Use This</button>' +
                            '</div>' +
                            '</div>');

                        // Initialize use product suggestion button
                        initUseProductSuggestionButton();
                    } else {
                        // Show error message
                        $results.append('<p class="thorius-error">' + 
                        (response.data.message || thorius_admin_params.i18n.error) + '</p>');
                    }
                },
                error: function() {
                    // Hide spinner, enable button
                    $spinner.removeClass('is-active');
                    $button.prop('disabled', false);

                    // Clear loading message
                    $results.empty();

                    // Show error message
                    $results.append('<p class="thorius-error">' + thorius_admin_params.i18n.error + '</p>');
                }
            });
        });
    }

    /**
     * Initialize use product suggestion button
     */
    function initUseProductSuggestionButton() {
        $('.thorius-use-product-suggestion').on('click', function() {
            var content = decodeURIComponent($(this).data('content'));
            var suggestionType = $(this).data('type');

            // Handle different types of product suggestions
            switch (suggestionType) {
                case 'title':
                    $('#title').val(content);
                    break;
                case 'short_description':
                    if (typeof tinyMCE !== 'undefined' && tinyMCE.get('excerpt')) {
                        // TinyMCE
                        tinyMCE.get('excerpt').setContent(content);
                    } else {
                        // Textarea
                        $('#excerpt').val(content);
                    }
                    break;
                case 'full_description':
                    if (typeof tinyMCE !== 'undefined' && tinyMCE.get('content')) {
                        // TinyMCE
                        tinyMCE.get('content').setContent(content);
                    } else {
                        // Textarea
                        $('#content').val(content);
                    }
                    break;
                case 'features':
                    // Add to product short description or main content
                    var features = '<h3>Key Features</h3><ul>';
                    var lines = content.split("\n");
                    
                    for (var i = 0; i < lines.length; i++) {
                        var line = lines[i].trim();
                        if (line) {
                            features += '<li>' + line + '</li>';
                        }
                    }
                    
                    features += '</ul>';
                    
                    if (typeof tinyMCE !== 'undefined' && tinyMCE.get('content')) {
                        // TinyMCE
                        var editor = tinyMCE.get('content');
                        var currentContent = editor.getContent();
                        editor.setContent(currentContent + features);
                    } else {
                        // Textarea
                        var $textarea = $('#content');
                        var currentContent = $textarea.val();
                        $textarea.val(currentContent + features);
                    }
                    break;
                case 'meta_description':
                    // Update Yoast SEO meta description if available
                    if ($('#yoast_wpseo_metadesc').length) {
                        $('#yoast_wpseo_metadesc').val(content);
                    } else if ($('#_yoast_wpseo_metadesc').length) {
                        $('#_yoast_wpseo_metadesc').val(content);
                    }
                    break;
            }
        });
    }

    /**
     * Initialize Image meta box
     */
    function initImageMetaBox() {
        // Toggle advanced options
        $('.thorius-toggle-advanced').on('click', function(e) {
            e.preventDefault();
            var $link = $(this);
            var $advanced = $('.thorius-image-advanced');
            
            $advanced.slideToggle();
            $link.toggleClass('open');
        });

        // Generate image button
        $('.thorius-generate-image-button').on('click', function() {
            var $button = $(this);
            var $spinner = $button.siblings('.thorius-spinner');
            var $results = $('#thorius-image-results');
            var postId = $button.data('post-id');
            var nonce = $button.data('nonce');
            var prompt = $('#thorius-image-prompt-input').val();
            var style = $('#thorius-image-style').val();
            var size = $('#thorius-image-size').val();

            // Validate prompt
            if (!prompt) {
                alert('Please enter an image prompt');
                return;
            }

            // Show spinner, disable button
            $spinner.addClass('is-active');
            $button.prop('disabled', true);

            // Clear previous results and show loading
            $results.empty();
            $results.append('<div class="thorius-generating">' + thorius_admin_params.i18n.loading + '</div>');

            // Ajax request to generate image
            $.ajax({
                url: thorius_admin_params.ajax_url,
                type: 'POST',
                data: {
                    action: 'thorius_generate_image',
                    post_id: postId,
                    prompt: prompt,
                    style: style,
                    size: size,
                    nonce: nonce
                },
                success: function(response) {
                    // Hide spinner, enable button
                    $spinner.removeClass('is-active');
                    $button.prop('disabled', false);

                    // Clear loading message
                    $results.empty();

                    if (response.success) {
                        // Display generated images
                        $.each(response.data.images, function(index, image) {
                            $results.append('<div class="thorius-image-result">' +
                                '<img src="' + image.url + '" alt="Generated image">' +
                                '<div class="thorius-image-actions">' +
                                '<button type="button" class="thorius-use-image" data-id="' + image.id + '" title="Use as featured image">' +
                                '<span class="dashicons dashicons-format-image"></span>' +
                                '</button>' +
                                '<button type="button" class="thorius-insert-image" data-url="' + image.url + '" title="Insert into content">' +
                                '<span class="dashicons dashicons-admin-media"></span>' +
                                '</button>' +
                                '</div>' +
                                '</div>');
                        });

                        // Initialize image action buttons
                        initImageActionButtons();
                    } else {
                        // Show error message
                        $results.append('<p class="thorius-error">' + 
                        (response.data.message || thorius_admin_params.i18n.error) + '</p>');
                    }
                },
                error: function() {
                    // Hide spinner, enable button
                    $spinner.removeClass('is-active');
                    $button.prop('disabled', false);

                    // Clear loading message
                    $results.empty();

                    // Show error message
                    $results.append('<p class="thorius-error">' + thorius_admin_params.i18n.error + '</p>');
                }
            });
        });
    }

    /**
     * Initialize image action buttons
     */
    function initImageActionButtons() {
        // Use as featured image
        $('.thorius-use-image').on('click', function() {
            var imageId = $(this).data('id');
            var postId = $('.thorius-generate-image-button').data('post-id');
            var nonce = $('.thorius-generate-image-button').data('nonce');

            $.ajax({
                url: thorius_admin_params.ajax_url,
                type: 'POST',
                data: {
                    action: 'thorius_set_featured_image',
                    post_id: postId,
                    image_id: imageId,
                    nonce: nonce
                },
                success: function(response) {
                    if (response.success) {
                        alert('Featured image updated successfully!');
                    } else {
                        alert(response.data.message || thorius_admin_params.i18n.error);
                    }
                },
                error: function() {
                    alert(thorius_admin_params.i18n.error);
                }
            });
        });

        // Insert into content
        $('.thorius-insert-image').on('click', function() {
            var imageUrl = $(this).data('url');
            var imageHtml = '<img src="' + imageUrl + '" alt="AI generated image" />';

            // Insert into editor (support for classic and block editor)
            if (typeof wp !== 'undefined' && wp.data && wp.data.select('core/editor')) {
                // Block editor
                wp.data.dispatch('core/editor').insertBlocks(
                    wp.blocks.createBlock('core/image', {
                        url: imageUrl,
                        alt: 'AI generated image'
                    })
                );
            } else if (typeof tinyMCE !== 'undefined' && tinyMCE.get('content')) {
                // Classic editor - TinyMCE
                tinyMCE.get('content').execCommand('mceInsertContent', false, imageHtml);
            } else if ($('#content').length) {
                // Classic editor - Textarea
                var $textarea = $('#content');
                var currentContent = $textarea.val();
                $textarea.val(currentContent + '\n\n' + imageHtml + '\n\n');
            }

            alert('Image inserted into content!');
        });
    }

    /**
     * Helper function to copy text to clipboard
     */
    function copyToClipboard(text) {
        var $temp = $('<textarea>');
        $('body').append($temp);
        $temp.val(text).select();
        document.execCommand('copy');
        $temp.remove();
    }

})(jQuery); 