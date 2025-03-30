/**
 * VORTEX AI Marketplace Artwork Grid Block
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
import { artworkIcon } from '../icons';
import ServerSideRender from '@wordpress/server-side-render';

// Fetch categories for dropdown
const fetchCategories = async () => {
    // This would normally fetch from the REST API, but for simplicity we'll use a placeholder
    return [
        { label: __('All Categories', 'vortex-ai-marketplace'), value: '' },
        { label: __('Digital Art', 'vortex-ai-marketplace'), value: 'digital-art' },
        { label: __('Photography', 'vortex-ai-marketplace'), value: 'photography' },
        { label: __('Painting', 'vortex-ai-marketplace'), value: 'painting' },
    ];
};

// Block name and unique identifier
export const name = 'vortex-marketplace/artwork-grid';

// Block settings
export const settings = {
    title: __('Artwork Grid', 'vortex-ai-marketplace'),
    description: __('Display a grid of artworks from the marketplace', 'vortex-ai-marketplace'),
    keywords: [
        __('artwork', 'vortex-ai-marketplace'),
        __('grid', 'vortex-ai-marketplace'),
        __('gallery', 'vortex-ai-marketplace'),
    ],
    icon: artworkIcon,
    
    // Define block attributes (mirrors shortcode attributes)
    attributes: {
        title: {
            type: 'string',
            default: '',
        },
        count: {
            type: 'number',
            default: 12,
        },
        columns: {
            type: 'number',
            default: 3,
        },
        category: {
            type: 'string',
            default: '',
        },
        tag: {
            type: 'string',
            default: '',
        },
        artist: {
            type: 'string',
            default: '',
        },
        featured: {
            type: 'boolean',
            default: false,
        },
        ai_generated: {
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
        show_price: {
            type: 'boolean',
            default: true,
        },
        show_artist: {
            type: 'boolean',
            default: true,
        },
    },
    
    // Block edit function
    edit: function(props) {
        const { attributes, setAttributes } = props;
        const blockProps = useBlockProps({
            className: 'vortex-artwork-grid-block',
        });
        
        // Order options
        const orderByOptions = [
            { label: __('Date', 'vortex-ai-marketplace'), value: 'date' },
            { label: __('Price', 'vortex-ai-marketplace'), value: 'price' },
            { label: __('Title', 'vortex-ai-marketplace'), value: 'title' },
            { label: __('Popularity', 'vortex-ai-marketplace'), value: 'popularity' },
            { label: __('Rating', 'vortex-ai-marketplace'), value: 'rating' },
            { label: __('Random', 'vortex-ai-marketplace'), value: 'rand' },
        ];
        
        const orderOptions = [
            { label: __('Descending', 'vortex-ai-marketplace'), value: 'DESC' },
            { label: __('Ascending', 'vortex-ai-marketplace'), value: 'ASC' },
        ];
        
        const aiGeneratedOptions = [
            { label: __('All Artworks', 'vortex-ai-marketplace'), value: '' },
            { label: __('AI Generated Only', 'vortex-ai-marketplace'), value: 'yes' },
            { label: __('Non-AI Generated Only', 'vortex-ai-marketplace'), value: 'no' },
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
                            label={__('Number of Artworks', 'vortex-ai-marketplace')}
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
                            options={fetchCategories()}
                            onChange={(category) => setAttributes({ category })}
                        />
                        <TextControl
                            label={__('Tag', 'vortex-ai-marketplace')}
                            value={attributes.tag}
                            onChange={(tag) => setAttributes({ tag })}
                        />
                        <TextControl
                            label={__('Artist', 'vortex-ai-marketplace')}
                            value={attributes.artist}
                            onChange={(artist) => setAttributes({ artist })}
                        />
                        <ToggleControl
                            label={__('Featured Only', 'vortex-ai-marketplace')}
                            checked={attributes.featured}
                            onChange={(featured) => setAttributes({ featured })}
                        />
                        <SelectControl
                            label={__('AI Generated', 'vortex-ai-marketplace')}
                            value={attributes.ai_generated}
                            options={aiGeneratedOptions}
                            onChange={(ai_generated) => setAttributes({ ai_generated })}
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
                            label={__('Show Price', 'vortex-ai-marketplace')}
                            checked={attributes.show_price}
                            onChange={(show_price) => setAttributes({ show_price })}
                        />
                        <ToggleControl
                            label={__('Show Artist', 'vortex-ai-marketplace')}
                            checked={attributes.show_artist}
                            onChange={(show_artist) => setAttributes({ show_artist })}
                        />
                    </PanelBody>
                </InspectorControls>
                
                <div className="vortex-block-preview">
                    {attributes.title && (
                        <div className="vortex-block-title">
                            <h2>{attributes.title}</h2>
                        </div>
                    )}
                    
                    <div className="vortex-artwork-grid-preview">
                        <div className="vortex-grid" style={{ display: 'grid', gridTemplateColumns: `repeat(${attributes.columns}, 1fr)`, gap: '20px' }}>
                            {[...Array(Math.min(attributes.count, 9))].map((_, i) => (
                                <div key={i} className="vortex-grid-item vortex-artwork-item">
                                    <div className="vortex-artwork-image"></div>
                                    <div className="vortex-artwork-info">
                                        <div className="vortex-artwork-title">Artwork {i + 1}</div>
                                        {attributes.show_artist && (
                                            <div className="vortex-artwork-artist">Artist Name</div>
                                        )}
                                        {attributes.show_price && (
                                            <div className="vortex-artwork-price">$99.99</div>
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
                    {__('VORTEX AI Marketplace - Artwork Grid with', 'vortex-ai-marketplace')} {attributes.count} {__('items in', 'vortex-ai-marketplace')} {attributes.columns} {__('columns', 'vortex-ai-marketplace')}
                </div>
            </div>
        );
    },
    
    // Block save function (empty as we'll render on PHP side)
    save: function() {
        return null;
    },
}; 