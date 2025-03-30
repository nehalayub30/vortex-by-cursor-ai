/**
 * VORTEX AI Marketplace - Shopping Cart Block
 * 
 * Displays the current shopping cart contents with controls to update quantities
 * and remove items. Includes functionality to show the cart total and checkout button.
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
    ToggleControl,
    SelectControl,
    TextControl,
    Placeholder,
    Button,
    Spinner
} from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';
import { useState, useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

// Register the block
registerBlockType('vortex/shopping-cart', {
    apiVersion: 2,
    title: __('Shopping Cart', 'vortex-ai-marketplace'),
    description: __('Display the current shopping cart with options to update quantities and checkout.', 'vortex-ai-marketplace'),
    category: 'vortex-blocks',
    icon: 'cart',
    supports: {
        html: false,
        align: ['wide', 'full']
    },
    example: {},
    attributes: {
        title: {
            type: 'string',
            default: __('Your Shopping Cart', 'vortex-ai-marketplace')
        },
        emptyCartMessage: {
            type: 'string',
            default: __('Your cart is empty. Browse our artworks to add items to your cart.', 'vortex-ai-marketplace')
        },
        showContinueShopping: {
            type: 'boolean',
            default: true
        },
        continueShoppingUrl: {
            type: 'string',
            default: ''
        },
        continueShoppingText: {
            type: 'string',
            default: __('Continue Shopping', 'vortex-ai-marketplace')
        },
        showCheckoutButton: {
            type: 'boolean',
            default: true
        },
        checkoutButtonText: {
            type: 'string',
            default: __('Proceed to Checkout', 'vortex-ai-marketplace')
        },
        checkoutUrl: {
            type: 'string',
            default: ''
        },
        style: {
            type: 'string',
            default: 'standard'
        },
        showT 