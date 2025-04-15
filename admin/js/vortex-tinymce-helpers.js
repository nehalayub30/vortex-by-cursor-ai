/**
 * TinyMCE Helpers for VORTEX Marketplace
 *
 * Provides additional functionality for the TinyMCE editor when using VORTEX shortcodes.
 *
 * @since      1.0.0
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/admin/js
 */

(function($) {
    'use strict';

    // Only run when TinyMCE is loaded
    if (typeof tinymce === 'undefined') {
        return;
    }

    $(document).ready(function() {
        // Initializes preview functionality for shortcodes
        initShortcodePreviewFunctionality();
        
        // Enhance TinyMCE dialogs with dynamic data
        enhanceTinyMCEDialogs();
    });

    /**
     * Initialize preview functionality for shortcodes
     */
    function initShortcodePreviewFunctionality() {
        // When a new editor is initialized
        $(document).on('tinymce-editor-setup', function(event, editor) {
            // Add a toolbar button for previewing shortcodes
            editor.on('init', function() {
                // Watch for shortcode insertion
                editor.on('change', function() {
                    highlightShortcodes(editor);
                });
                
                // Initial highlight
                highlightShortcodes(editor);
            });
        });
    }

    /**
     * Highlight shortcodes in the editor
     * 
     * @param {tinymce.Editor} editor TinyMCE editor instance
     */
    function highlightShortcodes(editor) {
        // This is a placeholder for actual implementation
        // In a real implementation, this would use the editor's DOM to find and style shortcodes
        // For example: editor.dom.addClass(shortcodeElement, 'vortex-shortcode-highlight');
    }

    /**
     * Enhance TinyMCE dialogs with dynamic data from localized script
     */
    function enhanceTinyMCEDialogs() {
        if (typeof vortex_tinymce_data === 'undefined') {
            return;
        }

        // When a window manager opens
        $(document).on('OpenWindow', function(e, win) {
            // Check if it's one of our dialogs
            if (win && win.title && (
                win.title.indexOf('Insert Artwork Shortcode') !== -1 || 
                win.title.indexOf('Insert Artist Shortcode') !== -1
            )) {
                // Add artwork or artist options if this is our dialog
                setTimeout(function() {
                    enhanceArtworkDialog(win);
                    enhanceArtistDialog(win);
                    
                    // Add preview button
                    addPreviewButton(win);
                }, 100);
            }
        });
    }

    /**
     * Enhance the artwork dialog with dynamic data
     * 
     * @param {Object} win Dialog window object
     */
    function enhanceArtworkDialog(win) {
        if (win.title.indexOf('Insert Artwork Shortcode') === -1 || !vortex_tinymce_data.artwork_options) {
            return;
        }

        // This is a placeholder for actually enhancing the dialog
        // In a real implementation, this would modify the dialog's DOM to add the options
    }

    /**
     * Enhance the artist dialog with dynamic data
     * 
     * @param {Object} win Dialog window object
     */
    function enhanceArtistDialog(win) {
        if (win.title.indexOf('Insert Artist Shortcode') === -1 || !vortex_tinymce_data.artist_options) {
            return;
        }

        // This is a placeholder for actually enhancing the dialog
        // In a real implementation, this would modify the dialog's DOM to add the options
    }

    /**
     * Add preview button to the dialog
     * 
     * @param {Object} win Dialog window object
     */
    function addPreviewButton(win) {
        // This is a placeholder for adding a preview button
        // In a real implementation, this would add a button to preview the shortcode
    }

    /**
     * Load artwork or artist preview
     * 
     * @param {Number} id Entity ID to preview
     * @param {String} type Type of entity (artwork or artist)
     * @param {Object} options Additional options for the preview
     */
    function loadPreview(id, type, options) {
        if (!id) {
            return;
        }

        $.ajax({
            url: vortex_tinymce_data.ajax_url,
            type: 'POST',
            data: {
                action: 'vortex_get_shortcode_preview',
                nonce: vortex_tinymce_data.nonce,
                id: id,
                type: type,
                options: options
            },
            success: function(response) {
                if (response.success && response.data) {
                    // Display preview
                    showPreviewModal(response.data.html);
                }
            }
        });
    }

    /**
     * Show preview in a modal
     * 
     * @param {String} html HTML content to show in the modal
     */
    function showPreviewModal(html) {
        // Create modal if it doesn't exist
        var $modal = $('#vortex-shortcode-preview-modal');
        
        if (!$modal.length) {
            $modal = $('<div id="vortex-shortcode-preview-modal" class="vortex-modal">' +
                '<div class="vortex-modal-content">' +
                '<span class="vortex-modal-close">&times;</span>' +
                '<div class="vortex-modal-body"></div>' +
                '</div></div>');
            
            $('body').append($modal);
            
            // Close modal when clicking X or outside the modal
            $modal.on('click', function(e) {
                if ($(e.target).hasClass('vortex-modal') || $(e.target).hasClass('vortex-modal-close')) {
                    $modal.hide();
                }
            });
        }
        
        // Update content and show modal
        $modal.find('.vortex-modal-body').html(html);
        $modal.show();
    }

})(jQuery); 