/**
 * VORTEX AI Marketplace - Artwork Search Block
 * 
 * Allows users to search for artworks with filters and display results in a grid.
 */

// Import WordPress dependencies
import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import { 
    InspectorControls,
    useBlockProps 
} from '@wordpress/block-editor';
import { 
    PanelBody,
    RangeControl,
    SelectControl,
    ToggleControl,
    TextControl,
    Placeholder,
    Spinner,
    Button,
    RadioControl
} from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { useState, useEffect } from '@wordpress/element';
import ServerSideRender from '@wordpress/server-side-render';
import apiFetch from '@wordpress/api-fetch';

// Register the block
registerBlockType('vortex/artwork-search', {
    apiVersion: 2,
    title: __('Artwork Search', 'vortex-ai-marketplace'),
    description: __('Display an artwork search interface with advanced filters.', 'vortex-ai-marketplace'),
    category: 'vortex-blocks',
    icon: 'search',
    supports: {
        html: false,
        align: ['wide', 'full'],
    },
    example: {},
    attributes: {
        columns: {
            type: 'number',
            default: 3
        },
        perPage: {
            type: 'number',
            default: 12
        },
        showFilters: {
            type: 'boolean',
            default: true
        },
        showSort: {
            type: 'boolean',
            default: true
        },
        defaultSort: {
            type: 'string',
            default: 'newest'
        },
        showSearch: {
            type: 'boolean',
            default: true
        },
        showPriceFilter: {
            type: 'boolean',
            default: true
        },
        showCategoryFilter: {
            type: 'boolean',
            default: true
        },
        showAIModelFilter: {
            type: 'boolean',
            default: true
        },
        showArtistFilter: {
            type: 'boolean',
            default: true
        },
        layout: {
            type: 'string',
            default: 'grid'
        },
        defaultCategory: {
            type: 'string',
            default: ''
        },
        defaultArtist: {
            type: 'string',
            default: ''
        },
        defaultAIModel: {
            type: 'string',
            default: ''
        },
        cardStyle: {
            type: 'string',
            default: 'standard'
        },
        showPagination: {
            type: 'boolean',
            default: true
        },
        useInfiniteScroll: {
            type: 'boolean',
            default: false
        }
    },

    /**
     * Edit function for the block.
     */
    edit: (props) => {
        const { attributes, setAttributes } = props;
        const blockProps = useBlockProps({
            className: `vortex-artwork-search vortex-artwork-search-${attributes.layout}`
        });

        // Get categories from the store
        const categories = useSelect((select) => {
            return select('core').getEntityRecords('taxonomy', 'artwork_category', { per_page: -1 });
        }, []);

        // State for preview data
        const [previewArtworks, setPreviewArtworks] = useState([]);
        const [isLoading, setIsLoading] = useState(false);

        // Load preview data
        useEffect(() => {
            setIsLoading(true);
            apiFetch({ path: '/vortex-ai/v1/artworks?per_page=4' })
                .then((artworks) => {
                    setPreviewArtworks(artworks);
                    setIsLoading(false);
                })
                .catch((error) => {
                    console.error('Error fetching preview artworks:', error);
                    setIsLoading(false);
                });
        }, []);

        return (
            <div {...blockProps}>
                <InspectorControls>
                    <PanelBody title={__('Display Settings', 'vortex-ai-marketplace')}>
                        <SelectControl
                            label={__('Layout', 'vortex-ai-marketplace')}
                            value={attributes.layout}
                            options={[
                                { label: __('Grid', 'vortex-ai-marketplace'), value: 'grid' },
                                { label: __('List', 'vortex-ai-marketplace'), value: 'list' },
                                { label: __('Masonry', 'vortex-ai-marketplace'), value: 'masonry' },
                            ]}
                            onChange={(layout) => setAttributes({ layout })}
                        />

                        <RangeControl
                            label={__('Columns', 'vortex-ai-marketplace')}
                            value={attributes.columns}
                            onChange={(columns) => setAttributes({ columns })}
                            min={1}
                            max={6}
                        />

                        <RangeControl
                            label={__('Artworks Per Page', 'vortex-ai-marketplace')}
                            value={attributes.perPage}
                            onChange={(perPage) => setAttributes({ perPage })}
                            min={4}
                            max={48}
                        />

                        <SelectControl
                            label={__('Card Style', 'vortex-ai-marketplace')}
                            value={attributes.cardStyle}
                            options={[
                                { label: __('Standard', 'vortex-ai-marketplace'), value: 'standard' },
                                { label: __('Minimal', 'vortex-ai-marketplace'), value: 'minimal' },
                                { label: __('Detailed', 'vortex-ai-marketplace'), value: 'detailed' },
                                { label: __('Hover Reveal', 'vortex-ai-marketplace'), value: 'hover-reveal' },
                            ]}
                            onChange={(cardStyle) => setAttributes({ cardStyle })}
                        />
                    </PanelBody>

                    <PanelBody title={__('Filter Settings', 'vortex-ai-marketplace')}>
                        <ToggleControl
                            label={__('Show Filters', 'vortex-ai-marketplace')}
                            checked={attributes.showFilters}
                            onChange={() => setAttributes({ showFilters: !attributes.showFilters })}
                        />

                        <ToggleControl
                            label={__('Show Sort Options', 'vortex-ai-marketplace')}
                            checked={attributes.showSort}
                            onChange={() => setAttributes({ showSort: !attributes.showSort })}
                        />

                        <ToggleControl
                            label={__('Show Search Bar', 'vortex-ai-marketplace')}
                            checked={attributes.showSearch}
                            onChange={() => setAttributes({ showSearch: !attributes.showSearch })}
                        />

                        <ToggleControl
                            label={__('Show Price Filter', 'vortex-ai-marketplace')}
                            checked={attributes.showPriceFilter}
                            onChange={() => setAttributes({ showPriceFilter: !attributes.showPriceFilter })}
                        />

                        <ToggleControl
                            label={__('Show Category Filter', 'vortex-ai-marketplace')}
                            checked={attributes.showCategoryFilter}
                            onChange={() => setAttributes({ showCategoryFilter: !attributes.showCategoryFilter })}
                        />

                        <ToggleControl
                            label={__('Show AI Model Filter', 'vortex-ai-marketplace')}
                            checked={attributes.showAIModelFilter}
                            onChange={() => setAttributes({ showAIModelFilter: !attributes.showAIModelFilter })}
                        />

                        <ToggleControl
                            label={__('Show Artist Filter', 'vortex-ai-marketplace')}
                            checked={attributes.showArtistFilter}
                            onChange={() => setAttributes({ showArtistFilter: !attributes.showArtistFilter })}
                        />
                    </PanelBody>

                    <PanelBody title={__('Default Settings', 'vortex-ai-marketplace')}>
                        <SelectControl
                            label={__('Default Sort', 'vortex-ai-marketplace')}
                            value={attributes.defaultSort}
                            options={[
                                { label: __('Newest', 'vortex-ai-marketplace'), value: 'newest' },
                                { label: __('Price: Low to High', 'vortex-ai-marketplace'), value: 'price-asc' },
                                { label: __('Price: High to Low', 'vortex-ai-marketplace'), value: 'price-desc' },
                                { label: __('Most Popular', 'vortex-ai-marketplace'), value: 'popular' },
                                { label: __('Most Liked', 'vortex-ai-marketplace'), value: 'liked' },
                                { label: __('Trending', 'vortex-ai-marketplace'), value: 'trending' },
                            ]}
                            onChange={(defaultSort) => setAttributes({ defaultSort })}
                        />

                        <SelectControl
                            label={__('Default Category', 'vortex-ai-marketplace')}
                            value={attributes.defaultCategory}
                            options={[
                                { label: __('All Categories', 'vortex-ai-marketplace'), value: '' },
                                ...(categories?.map((category) => ({
                                    label: category.name,
                                    value: category.slug
                                })) || [])
                            ]}
                            onChange={(defaultCategory) => setAttributes({ defaultCategory })}
                        />
                    </PanelBody>

                    <PanelBody title={__('Pagination Settings', 'vortex-ai-marketplace')}>
                        <ToggleControl
                            label={__('Show Pagination', 'vortex-ai-marketplace')}
                            checked={attributes.showPagination}
                            onChange={() => setAttributes({ showPagination: !attributes.showPagination })}
                        />

                        <ToggleControl
                            label={__('Use Infinite Scroll', 'vortex-ai-marketplace')}
                            checked={attributes.useInfiniteScroll}
                            onChange={() => setAttributes({ useInfiniteScroll: !attributes.useInfiniteScroll })}
                            help={__('Load more artworks as user scrolls down.', 'vortex-ai-marketplace')}
                        />
                    </PanelBody>
                </InspectorControls>

                <div className="vortex-block-preview">
                    <Placeholder
                        icon="search"
                        label={__('VORTEX Artwork Search', 'vortex-ai-marketplace')}
                        instructions={__('Displays an artwork search interface with filters and results.', 'vortex-ai-marketplace')}
                        className="vortex-editor-placeholder"
                    >
                        <div className="vortex-block-preview-settings">
                            <div className="vortex-block-preview-setting">
                                <span>{__('Layout:', 'vortex-ai-marketplace')}</span>
                                <strong>{attributes.layout.charAt(0).toUpperCase() + attributes.layout.slice(1)}</strong>
                            </div>
                            <div className="vortex-block-preview-setting">
                                <span>{__('Columns:', 'vortex-ai-marketplace')}</span>
                                <strong>{attributes.columns}</strong>
                            </div>
                            <div className="vortex-block-preview-setting">
                                <span>{__('Per Page:', 'vortex-ai-marketplace')}</span>
                                <strong>{attributes.perPage}</strong>
                            </div>
                            <div className="vortex-block-preview-setting">
                                <span>{__('Filters:', 'vortex-ai-marketplace')}</span>
                                <strong>{attributes.showFilters ? __('Shown', 'vortex-ai-marketplace') : __('Hidden', 'vortex-ai-marketplace')}</strong>
                            </div>
                            <div className="vortex-block-preview-setting">
                                <span>{__('Search:', 'vortex-ai-marketplace')}</span>
                                <strong>{attributes.showSearch ? __('Shown', 'vortex-ai-marketplace') : __('Hidden', 'vortex-ai-marketplace')}</strong>
                            </div>
                        </div>

                        {isLoading ? (
                            <div className="vortex-preview-loading">
                                <Spinner />
                                <p>{__('Loading preview...', 'vortex-ai-marketplace')}</p>
                            </div>
                        ) : (
                            <div className={`vortex-preview-grid vortex-preview-columns-${Math.min(attributes.columns, 4)}`}>
                                {previewArtworks.map((artwork, index) => (
                                    <div className="vortex-preview-artwork" key={index}>
                                        {artwork.thumbnail_url ? (
                                            <img src={artwork.thumbnail_url} alt={artwork.title} />
                                        ) : (
                                            <div className="vortex-preview-placeholder"></div>
                                        )}
                                        <h4>{artwork.title}</h4>
                                        <div className="vortex-preview-meta">
                                            <span className="vortex-preview-price">{artwork.price_formatted}</span>
                                            <span className="vortex-preview-artist">{artwork.artist_name}</span>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}

                        <div className="vortex-artwork-search-edit-preview">
                            <ServerSideRender
                                block="vortex/artwork-search"
                                attributes={attributes}
                                EmptyResponsePlaceholder={() => (
                                    <p>{__('No artworks found. This may be a preview limitation.', 'vortex-ai-marketplace')}</p>
                                )}
                            />
                        </div>
                    </Placeholder>
                </div>
            </div>
        );
    },

    /**
     * Save function for the block.
     * This block is rendered by PHP, so we just need to return the block content.
     */
    save: () => {
        return null;
    },
}); 
 * VORTEX AI Marketplace Artwork Search Block
 *
 * @package    Vortex_AI_Marketplace
 * @sub 