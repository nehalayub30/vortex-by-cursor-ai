/**
 * VORTEX Marketplace Block
 * 
 * Registers a Gutenberg block for the marketplace plugin.
 */

(function(blocks, editor, element, components) {
    var el = element.createElement;
    var SelectControl = components.SelectControl;
    var PanelBody = components.PanelBody;
    var InspectorControls = editor.InspectorControls;
    
    // Register the block
    blocks.registerBlockType('vortex-marketplace/frontend-output', {
        title: 'VORTEX Marketplace',
        icon: 'store',
        category: 'widgets',
        attributes: {
            displayType: {
                type: 'string',
                default: 'default'
            }
        },

        // Block in the editor
        edit: function(props) {
            var displayType = props.attributes.displayType;
            
            // Update display type
            function onChangeDisplayType(newDisplayType) {
                props.setAttributes({ displayType: newDisplayType });
            }
            
            // Block controls
            var controls = el(
                InspectorControls,
                { key: 'controls' },
                el(
                    PanelBody,
                    {
                        title: 'Marketplace Settings',
                        initialOpen: true
                    },
                    el(
                        SelectControl,
                        {
                            label: 'Display Type',
                            value: displayType,
                            options: [
                                { label: 'Default View', value: 'default' },
                                { label: 'Investor Application', value: 'investor_application' },
                                { label: 'Investor Dashboard', value: 'investor_dashboard' }
                            ],
                            onChange: onChangeDisplayType
                        }
                    )
                )
            );
            
            // Generate preview based on selected type
            function getPreviewContent() {
                switch (displayType) {
                    case 'investor_application':
                        return el(
                            'div',
                            { className: 'marketplace-block-preview' },
                            el('h3', {}, 'Investor Application'),
                            el('p', {}, 'This will display the investor application form.')
                        );
                    case 'investor_dashboard':
                        return el(
                            'div',
                            { className: 'marketplace-block-preview' },
                            el('h3', {}, 'Investor Dashboard'),
                            el('p', {}, 'This will display the investor dashboard.')
                        );
                    default:
                        return el(
                            'div',
                            { className: 'marketplace-block-preview' },
                            el('h3', {}, 'VORTEX Marketplace'),
                            el('p', {}, 'This will display the marketplace frontend.'),
                            el('div', { className: 'marketplace-block-preview-sections' },
                                el('div', { className: 'marketplace-block-preview-section' },
                                    el('h4', {}, 'Investor Portal'),
                                    el('p', {}, 'Access investment opportunities in VORTEX AI Marketplace.')
                                )
                            )
                        );
                }
            }
            
            // Render block
            return el(
                'div',
                { className: props.className },
                controls,
                el(
                    'div',
                    { className: 'marketplace-block-container' },
                    getPreviewContent()
                )
            );
        },

        // Block output in the frontend (uses server-side rendering)
        save: function() {
            return null; // Use server-side rendering
        }
    });
}(
    window.wp.blocks,
    window.wp.blockEditor,
    window.wp.element,
    window.wp.components
)); 