/**
 * TinyMCE Plugin for VORTEX Marketplace
 *
 * Adds custom buttons to the WordPress editor for inserting VORTEX shortcodes.
 *
 * @since      1.0.0
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/admin/js
 */

(function() {
    tinymce.create('tinymce.plugins.VortexShortcodes', {
        /**
         * Initialize the plugin
         *
         * @param {tinymce.Editor} ed Editor instance
         * @param {string} url Plugin URL
         */
        init: function(ed, url) {
            // Artwork Shortcode Button
            ed.addButton('vortex_artwork_shortcode', {
                title: 'Insert Artwork',
                icon: 'dashicons-art',
                onclick: function() {
                    ed.windowManager.open({
                        title: 'Insert Artwork Shortcode',
                        body: [
                            {
                                type: 'textbox',
                                name: 'id',
                                label: 'Artwork ID',
                                tooltip: 'Enter the ID of the artwork to display'
                            },
                            {
                                type: 'listbox',
                                name: 'display',
                                label: 'Display Style',
                                values: [
                                    {text: 'Card', value: 'card'},
                                    {text: 'Full', value: 'full'},
                                    {text: 'Image Only', value: 'image'},
                                    {text: 'Gallery', value: 'gallery'}
                                ]
                            },
                            {
                                type: 'checkbox',
                                name: 'show_price',
                                label: 'Show Price',
                                checked: true
                            },
                            {
                                type: 'checkbox',
                                name: 'show_artist',
                                label: 'Show Artist',
                                checked: true
                            }
                        ],
                        onsubmit: function(e) {
                            // Build the shortcode string
                            var shortcode = '[vortex_artwork id="' + e.data.id + '"';
                            
                            if (e.data.display) {
                                shortcode += ' display="' + e.data.display + '"';
                            }
                            
                            shortcode += ' show_price="' + (e.data.show_price ? 'yes' : 'no') + '"';
                            shortcode += ' show_artist="' + (e.data.show_artist ? 'yes' : 'no') + '"';
                            
                            shortcode += ']';
                            
                            // Insert the shortcode
                            ed.insertContent(shortcode);
                        }
                    });
                }
            });

            // Artist Shortcode Button
            ed.addButton('vortex_artist_shortcode', {
                title: 'Insert Artist',
                icon: 'dashicons-businessman',
                onclick: function() {
                    ed.windowManager.open({
                        title: 'Insert Artist Shortcode',
                        body: [
                            {
                                type: 'textbox',
                                name: 'id',
                                label: 'Artist ID',
                                tooltip: 'Enter the ID of the artist to display'
                            },
                            {
                                type: 'listbox',
                                name: 'display',
                                label: 'Display Style',
                                values: [
                                    {text: 'Profile', value: 'profile'},
                                    {text: 'Card', value: 'card'},
                                    {text: 'Gallery', value: 'gallery'}
                                ]
                            },
                            {
                                type: 'textbox',
                                name: 'count',
                                label: 'Artwork Count',
                                tooltip: 'Number of artworks to display in gallery mode',
                                value: '6'
                            }
                        ],
                        onsubmit: function(e) {
                            // Build the shortcode string
                            var shortcode = '[vortex_artist id="' + e.data.id + '"';
                            
                            if (e.data.display) {
                                shortcode += ' display="' + e.data.display + '"';
                            }
                            
                            if (e.data.count && e.data.display === 'gallery') {
                                shortcode += ' count="' + e.data.count + '"';
                            }
                            
                            shortcode += ']';
                            
                            // Insert the shortcode
                            ed.insertContent(shortcode);
                        }
                    });
                }
            });
        },

        /**
         * Information about the plugin
         *
         * @return {Object} Plugin info
         */
        getInfo: function() {
            return {
                longname: 'VORTEX Marketplace Shortcodes',
                author: 'Marianne Nems',
                authorurl: 'https://vortexartec.com',
                infourl: 'https://vortexartec.com/docs/shortcodes',
                version: '1.0.0'
            };
        }
    });

    // Register the plugin
    tinymce.PluginManager.add('vortex_shortcodes', tinymce.plugins.VortexShortcodes);
})(); 