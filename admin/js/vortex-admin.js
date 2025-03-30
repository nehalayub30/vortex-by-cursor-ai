/**
 * VORTEX AI Marketplace Admin JavaScript
 *
 * Main admin functionality for settings pages, tabs, and general admin features.
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/admin/js
 * @author     Marianne Nems <Marianne@VortexArtec.com>
 */

(function($) {
    'use strict';

    /**
     * VORTEX Admin functionality
     */
    const VortexAdmin = {
        /**
         * Initialize admin features
         */
        init: function() {
            this.initTabs();
            this.initColorPickers();
            this.initSortables();
            this.initDialogs();
            this.initTooltips();
            this.initSettingsForm();
            this.initDismissibleNotices();
            this.initMediaUploads();
            this.initDatepickers();
            this.initToggleControls();
            this.initDependentFields();
        },

        /**
         * Initialize tabbed interfaces
         */
        initTabs: function() {
            $('.vortex-tabs').tabs({
                activate: function(event, ui) {
                    // Update URL with active tab
                    const urlParams = new URLSearchParams(window.location.search);
                    urlParams.set('tab', ui.newTab.attr('data-tab'));
                    const newUrl = window.location.pathname + '?' + urlParams.toString();
                    window.history.replaceState({}, '', newUrl);
                    
                    // Store active tab in localstorage for persistence
                    const tabContainer = $(this).attr('id');
                    if (tabContainer) {
                        localStorage.setItem('vortex_active_tab_' + tabContainer, ui.newTab.attr('data-tab'));
                    }
                },
                create: function(event, ui) {
                    // Try to activate tab from URL or localStorage
                    const urlParams = new URLSearchParams(window.location.search);
                    const urlTab = urlParams.get('tab');
                    const tabContainer = $(this).attr('id');
                    let savedTab = null;
                    
                    if (tabContainer) {
                        savedTab = localStorage.getItem('vortex_active_tab_' + tabContainer);
                    }
                    
                    if (urlTab) {
                        const tabIndex = $(this).find('li[data-tab="' + urlTab + '"]').index();
                        if (tabIndex !== -1) {
                            $(this).tabs('option', 'active', tabIndex);
                        }
                    } else if (savedTab) {
                        const tabIndex = $(this).find('li[data-tab="' + savedTab + '"]').index();
                        if (tabIndex !== -1) {
                            $(this).tabs('option', 'active', tabIndex);
                        }
                    }
                }
            });
        },

        /**
         * Initialize color picker fields
         */
        initColorPickers: function() {
            $('.vortex-color-picker').wpColorPicker({
                change: function(event, ui) {
                    // Trigger change event for dependent fields
                    $(this).trigger('change');
                }
            });
        },

        /**
         * Initialize sortable elements
         */
        initSortables: function() {
            $('.vortex-sortable').sortable({
                handle: '.vortex-sort-handle',
                update: function(event, ui) {
                    // Update hidden field with serialized order
                    const container = $(this).closest('.vortex-sortable-container');
                    const orderField = container.find('.vortex-sort-order');
                    if (orderField.length) {
                        const items = [];
                        $(this).find('.vortex-sortable-item').each(function() {
                            items.push($(this).data('id'));
                        });
                        orderField.val(JSON.stringify(items));
                    }
                    
                    // Trigger change event
                    container.trigger('sortupdate');
                }
            });
        },

        /**
         * Initialize jQuery UI dialogs
         */
        initDialogs: function() {
            // Modal confirmation dialog
            $('body').append('<div id="vortex-confirm-dialog" title="Confirmation" style="display:none;"><p id="vortex-confirm-message"></p></div>');
            
            $('#vortex-confirm-dialog').dialog({
                autoOpen: false,
                resizable: false,
                height: "auto",
                width: 400,
                modal: true,
                buttons: {
                    Cancel: function() {
                        $(this).dialog('close');
                    },
                    Confirm: function() {
                        if (typeof $(this).data('confirm-callback') === 'function') {
                            $(this).data('confirm-callback')();
                        }
                        $(this).dialog('close');
                    }
                }
            });
            
            // Attach click handlers to confirmation buttons
            $('.vortex-confirm-action').on('click', function(e) {
                e.preventDefault();
                
                const message = $(this).data('confirm-message') || vortexAdmin.strings.confirmDelete;
                const callback = () => {
                    if ($(this).is('a')) {
                        window.location.href = $(this).attr('href');
                    } else if ($(this).closest('form').length) {
                        $(this).closest('form').submit();
                    } else {
                        // Execute data-action if it exists
                        const action = $(this).data('action');
                        if (action && typeof VortexAdmin[action] === 'function') {
                            VortexAdmin[action]($(this));
                        }
                    }
                };
                
                VortexAdmin.showConfirmDialog(message, callback);
            });
        },

        /**
         * Show confirmation dialog
         */
        showConfirmDialog: function(message, callback) {
            $('#vortex-confirm-message').text(message);
            $('#vortex-confirm-dialog').data('confirm-callback', callback).dialog('open');
        },

        /**
         * Initialize tooltips
         */
        initTooltips: function() {
            $('.vortex-tooltip').each(function() {
                const tooltip = $(this).data('tooltip');
                if (tooltip) {
                    $(this).append('<span class="vortex-tooltip-icon dashicons dashicons-editor-help"></span>');
                    $(this).find('.vortex-tooltip-icon').attr('title', tooltip);
                }
            });
            
            $('.vortex-tooltip-icon').tooltip({
                position: { my: "left+15 center", at: "right center" }
            });
        },

        /**
         * Initialize settings form handling
         */
        initSettingsForm: function() {
            // Form submit handling
            $('.vortex-settings-form').on('submit', function(e) {
                const form = $(this);
                
                // Only handle AJAX submission if form has data-ajax="true"
                if (form.data('ajax') !== true) {
                    return true;
                }
                
                e.preventDefault();
                
                // Add loading state
                form.addClass('vortex-loading');
                form.find('button[type="submit"]').prop('disabled', true);
                form.find('.vortex-submit-spinner').show();
                
                // Clear previous errors
                form.find('.vortex-field-error').remove();
                
                // Collect form data
                const formData = new FormData(form[0]);
                formData.append('action', 'vortex_save_settings');
                formData.append('nonce', vortexAdmin.nonce);
                formData.append('settings_group', form.data('settings-group'));
                
                // Submit via AJAX
                $.ajax({
                    url: vortexAdmin.ajaxUrl,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            VortexAdmin.showNotice(response.data.message || vortexAdmin.strings.success, 'success');
                            
                            // If there's a redirect URL, redirect after a delay
                            if (response.data.redirect) {
                                setTimeout(function() {
                                    window.location.href = response.data.redirect;
                                }, 1000);
                            }
                            
                            // Trigger success event for other scripts
                            form.trigger('vortex:settings-saved', [response.data]);
                        } else {
                            // Show errors
                            if (response.data.errors) {
                                $.each(response.data.errors, function(field, error) {
                                    const fieldElement = form.find('[name="' + field + '"]');
                                    if (fieldElement.length) {
                                        fieldElement.after('<span class="vortex-field-error">' + error + '</span>');
                                    } else {
                                        VortexAdmin.showNotice(field + ': ' + error, 'error');
                                    }
                                });
                            } else {
                                VortexAdmin.showNotice(response.data.message || vortexAdmin.strings.error, 'error');
                            }
                        }
                    },
                    error: function() {
                        VortexAdmin.showNotice(vortexAdmin.strings.error, 'error');
                    },
                    complete: function() {
                        // Remove loading state
                        form.removeClass('vortex-loading');
                        form.find('button[type="submit"]').prop('disabled', false);
                        form.find('.vortex-submit-spinner').hide();
                    }
                });
            });
            
            // Reset settings button
            $('.vortex-reset-settings').on('click', function(e) {
                e.preventDefault();
                
                const settingsGroup = $(this).data('settings-group');
                const message = vortexAdmin.strings.confirmReset;
                
                VortexAdmin.showConfirmDialog(message, function() {
                    VortexAdmin.resetSettings(settingsGroup);
                });
            });
        },

        /**
         * Reset settings to defaults
         */
        resetSettings: function(settingsGroup) {
            $.ajax({
                url: vortexAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'vortex_reset_settings',
                    nonce: vortexAdmin.nonce,
                    settings_group: settingsGroup
                },
                success: function(response) {
                    if (response.success) {
                        VortexAdmin.showNotice(response.data.message, 'success');
                        // Reload the page to show default values
                        window.location.reload();
                    } else {
                        VortexAdmin.showNotice(response.data.message || vortexAdmin.strings.error, 'error');
                    }
                },
                error: function() {
                    VortexAdmin.showNotice(vortexAdmin.strings.error, 'error');
                }
            });
        },

        /**
         * Initialize dismissible admin notices
         */
        initDismissibleNotices: function() {
            // Handle notice dismissal
            $(document).on('click', '.vortex-admin-notice .notice-dismiss', function() {
                const notice = $(this).closest('.vortex-admin-notice');
                const noticeType = notice.data('notice');
                
                if (noticeType) {
                    $.ajax({
                        url: vortexAdmin.ajaxUrl,
                        type: 'POST',
                        data: {
                            action: 'vortex_dismiss_notice',
                            nonce: vortexAdmin.nonce,
                            notice: noticeType
                        }
                    });
                }
            });
        },

        /**
         * Show admin notice
         */
        showNotice: function(message, type = 'info') {
            // Remove any existing notices
            $('.vortex-admin-js-notice').remove();
            
            // Create notice
            const notice = $('<div class="notice is-dismissible vortex-admin-js-notice notice-' + type + '"><p>' + message + '</p></div>');
            
            // Add dismiss button
            const dismissButton = $('<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>');
            notice.append(dismissButton);
            
            // Add to top of page
            $('#wpbody-content').prepend(notice);
            
            // Handle dismiss button
            dismissButton.on('click', function() {
                notice.fadeTo(100, 0, function() {
                    notice.slideUp(100, function() {
                        notice.remove();
                    });
                });
            });
            
            // Auto-dismiss after 5 seconds for success messages
            if (type === 'success') {
                setTimeout(function() {
                    notice.fadeTo(100, 0, function() {
                        notice.slideUp(100, function() {
                            notice.remove();
                        });
                    });
                }, 5000);
            }
        },

        /**
         * Initialize media upload fields
         */
        initMediaUploads: function() {
            $('.vortex-media-upload').each(function() {
                const uploadButton = $(this);
                const clearButton = uploadButton.siblings('.vortex-media-clear');
                const previewContainer = uploadButton.siblings('.vortex-media-preview');
                const inputField = uploadButton.siblings('input[type="hidden"]');
                const mediaTitle = uploadButton.data('media-title') || 'Select Media';
                const mediaButton = uploadButton.data('media-button') || 'Use this media';
                const mediaType = uploadButton.data('media-type') || 'image';
                
                let mediaUploader = null;
                
                // Setup media uploader
                uploadButton.on('click', function(e) {
                    e.preventDefault();
                    
                    // If the uploader object has already been created, reopen the dialog
                    if (mediaUploader) {
                        mediaUploader.open();
                        return;
                    }
                    
                    // Create the media uploader
                    mediaUploader = wp.media({
                        title: mediaTitle,
                        button: {
                            text: mediaButton
                        },
                        multiple: false,
                        library: {
                            type: mediaType
                        }
                    });
                    
                    // When media is selected, update the field value and preview
                    mediaUploader.on('select', function() {
                        const attachment = mediaUploader.state().get('selection').first().toJSON();
                        
                        // Update the hidden input
                        inputField.val(attachment.id);
                        
                        // Update the preview
                        if (mediaType === 'image') {
                            // Use thumbnail if available
                            const imageUrl = attachment.sizes && attachment.sizes.thumbnail ? 
                                             attachment.sizes.thumbnail.url : attachment.url;
                            
                            previewContainer.html('<img src="' + imageUrl + '" alt="Preview">');
                        } else {
                            previewContainer.html('<span class="vortex-media-filename">' + attachment.filename + '</span>');
                        }
                        
                        // Show the clear button
                        clearButton.show();
                        
                        // Trigger change for dependent fields
                        inputField.trigger('change');
                    });
                    
                    // Open the uploader dialog
                    mediaUploader.open();
                });
                
                // Clear the field
                clearButton.on('click', function(e) {
                    e.preventDefault();
                    
                    // Clear the hidden input
                    inputField.val('');
                    
                    // Clear the preview
                    previewContainer.empty();
                    
                    // Hide the clear button
                    clearButton.hide();
                    
                    // Trigger change for dependent fields
                    inputField.trigger('change');
                });
            });
        },

        /**
         * Initialize datepicker fields
         */
        initDatepickers: function() {
            $('.vortex-datepicker').each(function() {
                const dateFormat = $(this).data('date-format') || 'yy-mm-dd';
                
                $(this).datepicker({
                    dateFormat: dateFormat,
                    changeMonth: true,
                    changeYear: true,
                    yearRange: 'c-10:c+10'
                });
            });
        },

        /**
         * Initialize toggle fields (checkboxes that show/hide related fields)
         */
        initToggleControls: function() {
            // Handle toggles on initial load
            $('.vortex-toggle-control').each(function() {
                VortexAdmin.handleToggleControl($(this));
            });
            
            // Handle toggle changes
            $(document).on('change', '.vortex-toggle-control', function() {
                VortexAdmin.handleToggleControl($(this));
            });
        },

        /**
         * Handle toggle control state
         */
        handleToggleControl: function(control) {
            const target = control.data('toggle-target');
            const isChecked = control.is(':checked');
            
            if (target) {
                const targetElements = $(target);
                
                if (isChecked) {
                    targetElements.removeClass('vortex-hidden');
                } else {
                    targetElements.addClass('vortex-hidden');
                }
            }
        },

        /**
         * Initialize dependent fields
         */
        initDependentFields: function() {
            // Handle dependencies on initial load
            $('.vortex-dependent-field').each(function() {
                VortexAdmin.handleDependentField($(this));
            });
            
            // Handle dependency changes
            $(document).on('change', '.vortex-dependency-source', function() {
                $('.vortex-dependent-field').each(function() {
                    if ($(this).data('depends-on') === $(this).attr('id')) {
                        VortexAdmin.handleDependentField($(this));
                    }
                });
            });
        },

        /**
         * Handle dependent field visibility
         */
        handleDependentField: function(field) {
            const dependsOn = field.data('depends-on');
            const dependsValue = field.data('depends-value');
            const dependsCompare = field.data('depends-compare') || 'equal';
            
            if (dependsOn) {
                const sourceField = $('#' + dependsOn);
                let sourceValue = sourceField.val();
                
                // Handle checkboxes
                if (sourceField.is(':checkbox')) {
                    sourceValue = sourceField.is(':checked') ? '1' : '0';
                }
                
                // Handle radio buttons
                if (sourceField.is(':radio')) {
                    sourceValue = $('input[name="' + sourceField.attr('name') + '"]:checked').val();
                }
                
                let shouldShow = false;
                
                switch (dependsCompare) {
                    case 'equal':
                        shouldShow = sourceValue === dependsValue;
                        break;
                    case 'not_equal':
                        shouldShow = sourceValue !== dependsValue;
                        break;
                    case 'contains':
                        shouldShow = sourceValue && sourceValue.indexOf(dependsValue) !== -1;
                        break;
                    case 'in':
                        const values = dependsValue.split(',');
                        shouldShow = values.includes(sourceValue);
                        break;
                    case 'greater_than':
                        shouldShow = parseFloat(sourceValue) > parseFloat(dependsValue);
                        break;
                    case 'less_than':
                        shouldShow = parseFloat(sourceValue) < parseFloat(dependsValue);
                        break;
                }
                
                if (shouldShow) {
                    field.closest('.vortex-field-row').removeClass('vortex-hidden');
                } else {
                    field.closest('.vortex-field-row').addClass('vortex-hidden');
                }
            }
        },

        /**
         * Perform generic AJAX action
         */
        performAjaxAction: function(actionName, actionData, successCallback, errorCallback) {
            const data = {
                action: 'vortex_admin_action',
                nonce: vortexAdmin.nonce,
                vortex_action: actionName,
                ...actionData
            };
            
            $.ajax({
                url: vortexAdmin.ajaxUrl,
                type: 'POST',
                data: data,
                success: function(response) {
                    if (response.success) {
                        if (typeof successCallback === 'function') {
                            successCallback(response.data);
                        } else {
                            VortexAdmin.showNotice(response.data.message || vortexAdmin.strings.success, 'success');
                        }
                    } else {
                        if (typeof errorCallback === 'function') {
                            errorCallback(response.data);
                        } else {
                            VortexAdmin.showNotice(response.data.message || vortexAdmin.strings.error, 'error');
                        }
                    }
                },
                error: function() {
                    if (typeof errorCallback === 'function') {
                        errorCallback({message: vortexAdmin.strings.error});
                    } else {
                        VortexAdmin.showNotice(vortexAdmin.strings.error, 'error');
                    }
                }
            });
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        VortexAdmin.init();
    });

    // Make VortexAdmin accessible globally
    window.VortexAdmin = VortexAdmin;

})(jQuery); 