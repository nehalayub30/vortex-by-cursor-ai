/**
 * VORTEX AI Marketplace Artist Grid Block
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/blocks
 */

import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { 
    PanelBody, 
    TextControl, 
    RangeControl, 
    SelectControl, 
    ToggleControl 
} from '@wordpress/components';
import { artistIcon } from '../icons';
import ServerSideRender from '@wordpress/server-side-render';

// Block name and unique identifier
export const name = 'vortex-marketplace/artist-grid';

// Block settings
export const settings = {
    title: __('Artist Grid', 'vortex-ai-marketplace'),
    description: __('Display a grid of artists from the marketplace', 'vortex-ai-marketplace'),
    keywords: [
        __('artist', 'vortex-ai-marketplace'),
        __('grid', 'vortex-ai-marketplace'),
        __('creator', 'vortex-ai-marketplace'),
    ],
    icon: artistIcon,
    
    // Define block attributes (mirrors shortcode attributes)
    attributes: {
        title: {
            type: 'string',
            default: '',
        },
        count: {
            type: 'number',
            default: 8,
        },
        columns: {
            type: 'number',
            default: 4,
        },
        category: {
            type: 'string',
            default: '',
        },
        featured: {
            type: 'boolean',
            default: false,
        },
        verified: {
            type: 'string',
            default: '',
        },
        orderby: {
            type: 'string',
            default: 'date',
        },
        order: {
            type: 'string',
            default: 'DESC',
        },
        show_filters: {
            type: 'boolean',
            default: false,
        },
        show_pagination: {
            type: 'boolean',
            default: true,
        },
        show_bio: {
            type: 'boolean',
            default: true,
        },
    },
    
    // Block edit function
    edit: function(props) {
        const { attributes, setAttributes } = props;
        const blockProps = useBlockProps({
            className: 'vortex-artist-grid-block',
        });
        
        // Order options
        const orderByOptions = [
            { label: __('Date', 'vortex-ai-marketplace'), value: 'date' },
            { label: __('Name', 'vortex-ai-marketplace'), value: 'title' },
            { label: __('Popularity', 'vortex-ai-marketplace'), value: 'popularity' },
            { label: __('Sales', 'vortex-ai-marketplace'), value: 'sales' },
            { label: __('Random', 'vortex-ai-marketplace'), value: 'rand' },
        ];
        
        const orderOptions = [
            { label: __('Descending', 'vortex-ai-marketplace'), value: 'DESC' },
            { label: __('Ascending', 'vortex-ai-marketplace'), value: 'ASC' },
        ];
        
        const verifiedOptions = [
            { label: __('All Artists', 'vortex-ai-marketplace'), value: '' },
            { label: __('Verified Only', 'vortex-ai-marketplace'), value: 'yes' },
            { label: __('Unverified Only', 'vortex-ai-marketplace'), value: 'no' },
        ];
        
        // Fetch categories for dropdown
        const artistCategoryOptions = [
            { label: __('All Categories', 'vortex-ai-marketplace'), value: '' },
            { label: __('Digital Artists', 'vortex-ai-marketplace'), value: 'digital' },
            { label: __('Photographers', 'vortex-ai-marketplace'), value: 'photographers' },
            { label: __('Painters', 'vortex-ai-marketplace'), value: 'painters' },
            { label: __('AI Prompt Engineers', 'vortex-ai-marketplace'), value: 'ai-prompt-engineers' },
        ];
        
        return (
            <div { ...blockProps }>
                <InspectorControls>
                    <PanelBody title={__('Grid Settings', 'vortex-ai-marketplace')}>
                        <TextControl
                            label={__('Title', 'vortex-ai-marketplace')}
                            value={attributes.title}
                            onChange={(title) => setAttributes({ title })}
                        />
                        <RangeControl
                            label={__('Number of Artists', 'vortex-ai-marketplace')}
                            value={attributes.count}
                            onChange={(count) => setAttributes({ count })}
                            min={1}
                            max={100}
                        />
                        <RangeControl
                            label={__('Columns', 'vortex-ai-marketplace')}
                            value={attributes.columns}
                            onChange={(columns) => setAttributes({ columns })}
                            min={1}
                            max={6}
                        />
                    </PanelBody>
                    
                    <PanelBody title={__('Filtering', 'vortex-ai-marketplace')} initialOpen={false}>
                        <SelectControl
                            label={__('Category', 'vortex-ai-marketplace')}
                            value={attributes.category}
                            options={artistCategoryOptions}
                            onChange={(category) => setAttributes({ category })}
                        />
                        <ToggleControl
                            label={__('Featured Only', 'vortex-ai-marketplace')}
                            checked={attributes.featured}
                            onChange={(featured) => setAttributes({ featured })}
                        />
                        <SelectControl
                            label={__('Verified Status', 'vortex-ai-marketplace')}
                            value={attributes.verified}
                            options={verifiedOptions}
                            onChange={(verified) => setAttributes({ verified })}
                        />
                    </PanelBody>
                    
                    <PanelBody title={__('Order', 'vortex-ai-marketplace')} initialOpen={false}>
                        <SelectControl
                            label={__('Order By', 'vortex-ai-marketplace')}
                            value={attributes.orderby}
                            options={orderByOptions}
                            onChange={(orderby) => setAttributes({ orderby })}
                        />
                        <SelectControl
                            label={__('Order', 'vortex-ai-marketplace')}
                            value={attributes.order}
                            options={orderOptions}
                            onChange={(order) => setAttributes({ order })}
                        />
                    </PanelBody>
                    
                    <PanelBody title={__('Display', 'vortex-ai-marketplace')} initialOpen={false}>
                        <ToggleControl
                            label={__('Show Filters', 'vortex-ai-marketplace')}
                            checked={attributes.show_filters}
                            onChange={(show_filters) => setAttributes({ show_filters })}
                        />
                        <ToggleControl
                            label={__('Show Pagination', 'vortex-ai-marketplace')}
                            checked={attributes.show_pagination}
                            onChange={(show_pagination) => setAttributes({ show_pagination })}
                        />
                        <ToggleControl
                            label={__('Show Bio', 'vortex-ai-marketplace')}
                            checked={attributes.show_bio}
                            onChange={(show_bio) => setAttributes({ show_bio })}
                        />
                    </PanelBody>
                </InspectorControls>
                
                <div className="vortex-block-preview">
                    {attributes.title && (
                        <div className="vortex-block-title">
                            <h2>{attributes.title}</h2>
                        </div>
                    )}
                    
                    <div className="vortex-artist-grid-preview">
                        <div className="vortex-grid" style={{ display: 'grid', gridTemplateColumns: `repeat(${attributes.columns}, 1fr)`, gap: '20px' }}>
                            {[...Array(Math.min(attributes.count, 8))].map((_, i) => (
                                <div key={i} className="vortex-grid-item vortex-artist-item">
                                    <div className="vortex-artist-avatar"></div>
                                    <div className="vortex-artist-info">
                                        <div className="vortex-artist-name">Artist {i + 1}</div>
                                        {attributes.show_bio && (
                                            <div className="vortex-artist-bio">
                                                {__('Artist bio excerpt...', 'vortex-ai-marketplace')}
                                            </div>
                                        )}
                                    </div>
                                </div>
                            ))}
                        </div>
                        
                        {attributes.show_pagination && (
                            <div className="vortex-pagination-preview">
                                <div className="vortex-load-more">
                                    {__('Load More', 'vortex-ai-marketplace')}
                                </div>
                            </div>
                        )}
                    </div>
                </div>
                
                <div className="vortex-block-notice">
                    {__('VORTEX AI Marketplace - Artist Grid with', 'vortex-ai-marketplace')} {attributes.count} {__('artists in', 'vortex-ai-marketplace')} {attributes.columns} {__('columns', 'vortex-ai-marketplace')}
                </div>
            </div>
        );
    },
    
    // Block save function (empty as we'll render on PHP side)
    save: function() {
        return null;
    },
}; 