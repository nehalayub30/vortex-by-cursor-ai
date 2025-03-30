/**
 * VORTEX AI Marketplace Main Block
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/blocks
 */

import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { 
    PanelBody, 
    TextControl, 
    TextareaControl, 
    ToggleControl 
} from '@wordpress/components';
import { vortexIcon } from '../icons';
import ServerSideRender from '@wordpress/server-side-render';

// Block name and unique identifier
export const name = 'vortex-marketplace/main';

// Block settings
export const settings = {
    title: __('VORTEX Marketplace', 'vortex-ai-marketplace'),
    description: __('Display the main VORTEX AI Marketplace layout', 'vortex-ai-marketplace'),
    keywords: [
        __('marketplace', 'vortex-ai-marketplace'),
        __('vortex', 'vortex-ai-marketplace'),
        __('ai', 'vortex-ai-marketplace'),
        __('art', 'vortex-ai-marketplace'),
    ],
    icon: vortexIcon,
    
    // Define block attributes (mirrors shortcode attributes)
    attributes: {
        title: {
            type: 'string',
            default: __('VORTEX AI Marketplace', 'vortex-ai-marketplace'),
        },
        description: {
            type: 'string',
            default: '',
        },
        featured: {
            type: 'boolean',
            default: true,
        },
        categories: {
            type: 'boolean',
            default: true,
        },
        latest: {
            type: 'boolean',
            default: true,
        },
        artists: {
            type: 'boolean',
            default: true,
        },
        search: {
            type: 'boolean',
            default: true,
        },
    },
    
    // Block edit function
    edit: function(props) {
        const { attributes, setAttributes } = props;
        const blockProps = useBlockProps({
            className: 'vortex-marketplace-block',
        });
        
        return (
            <div { ...blockProps }>
                <InspectorControls>
                    <PanelBody title={__('Marketplace Settings', 'vortex-ai-marketplace')}>
                        <TextControl
                            label={__('Title', 'vortex-ai-marketplace')}
                            value={attributes.title}
                            onChange={(title) => setAttributes({ title })}
                        />
                        <TextareaControl
                            label={__('Description', 'vortex-ai-marketplace')}
                            value={attributes.description}
                            onChange={(description) => setAttributes({ description })}
                        />
                        <ToggleControl
                            label={__('Show Featured Artwork', 'vortex-ai-marketplace')}
                            checked={attributes.featured}
                            onChange={(featured) => setAttributes({ featured })}
                        />
                        <ToggleControl
                            label={__('Show Categories', 'vortex-ai-marketplace')}
                            checked={attributes.categories}
                            onChange={(categories) => setAttributes({ categories })}
                        />
                        <ToggleControl
                            label={__('Show Latest Artwork', 'vortex-ai-marketplace')}
                            checked={attributes.latest}
                            onChange={(latest) => setAttributes({ latest })}
                        />
                        <ToggleControl
                            label={__('Show Featured Artists', 'vortex-ai-marketplace')}
                            checked={attributes.artists}
                            onChange={(artists) => setAttributes({ artists })}
                        />
                        <ToggleControl
                            label={__('Show Search Field', 'vortex-ai-marketplace')}
                            checked={attributes.search}
                            onChange={(search) => setAttributes({ search })}
                        />
                    </PanelBody>
                </InspectorControls>
                
                <div className="vortex-block-preview">
                    <div className="vortex-block-title">
                        <h2>{attributes.title}</h2>
                    </div>
                    {attributes.description && (
                        <div className="vortex-block-description">
                            <p>{attributes.description}</p>
                        </div>
                    )}
                    <div className="vortex-block-sections">
                        {attributes.search && (
                            <div className="vortex-block-section vortex-search-section">
                                <h4>{__('Search', 'vortex-ai-marketplace')}</h4>
                                <div className="vortex-search-preview"></div>
                            </div>
                        )}
                        {attributes.featured && (
                            <div className="vortex-block-section vortex-featured-section">
                                <h4>{__('Featured Artwork', 'vortex-ai-marketplace')}</h4>
                                <div className="vortex-grid-preview"></div>
                            </div>
                        )}
                        {attributes.categories && (
                            <div className="vortex-block-section vortex-categories-section">
                                <h4>{__('Categories', 'vortex-ai-marketplace')}</h4>
                                <div className="vortex-categories-preview"></div>
                            </div>
                        )}
                        {attributes.latest && (
                            <div className="vortex-block-section vortex-latest-section">
                                <h4>{__('Latest Artwork', 'vortex-ai-marketplace')}</h4>
                                <div className="vortex-grid-preview"></div>
                            </div>
                        )}
                        {attributes.artists && (
                            <div className="vortex-block-section vortex-artists-section">
                                <h4>{__('Featured Artists', 'vortex-ai-marketplace')}</h4>
                                <div className="vortex-grid-preview"></div>
                            </div>
                        )}
                    </div>
                </div>
                
                <div className="vortex-block-notice">
                    {__('VORTEX AI Marketplace - This block displays the complete marketplace interface.', 'vortex-ai-marketplace')}
                </div>
            </div>
        );
    },
    
    // Block save function (empty as we'll render on PHP side)
    save: function() {
        return null;
    },
}; 