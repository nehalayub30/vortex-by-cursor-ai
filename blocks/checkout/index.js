/**
 * VORTEX AI Marketplace - Checkout Block
 * 
 * Provides a checkout form for users to complete their purchase,
 * including options for wallet connection, payment processing, and order confirmation.
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
    TextareaControl,
    Placeholder,
    Button,
    Spinner
} from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';
import { useState } from '@wordpress/element';

// Register the block
registerBlockType('vortex/checkout', {
    apiVersion: 2,
    title: __('Checkout', 'vortex-ai-marketplace'),
    description: __('Display a checkout form for users to complete their purchase.', 'vortex-ai-marketplace'),
    category: 'vortex-blocks',
    icon: 'money-alt',
    supports: {
        html: false,
        align: ['wide', 'full']
    },
    example: {},
    attributes: {
        title: {
            type: 'string',
            default: __('Checkout', 'vortex-ai-marketplace')
        },
        emptyCartMessage: {
            type: 'string',
            default: __('Your cart is empty. Add some artworks before proceeding to checkout.', 'vortex-ai-marketplace')
        },
        showOrderSummary: {
            type: 'boolean',
            default: true
        },
        showBillingFields: {
            type: 'boolean',
            default: true
        },
        requiredFields: {
            type: 'string',
            default: 'name,email'
        },
        walletConnectionRequired: {
            type: 'boolean',
            default: true
        },
        supportedWallets: {
            type: 'string',
            default: 'metamask,phantom,walletconnect'
        },
        checkoutLayout: {
            type: 'string',
            default: 'two-column'
        },
        orderConfirmationMessage: {
            type: 'string',
            default: __('Thank you for your purchase! Your order has been processed successfully.', 'vortex-ai-marketplace')
        },
        termsText: {
            type: 'string',
            default: __('I agree to the terms and conditions and privacy policy.', 'vortex-ai-marketplace')
        },
        termsRequired: {
            type: 'boolean',
            default: true
        },
        termsUrl: {
            type: 'string',
            default: ''
        },
        privacyUrl: {
            type: 'string',
            default: ''
        },
        buttonText: {
            type: 'string',
            default: __('Complete Purchase', 'vortex-ai-marketplace')
        },
        enableCoupons: {
            type: 'boolean',
            default: true
        },
        showReturnToCart: {
            type: 'boolean',
            default: true
        },
        returnToCartText: {
            type: 'string',
            default: __('Return to Cart', 'vortex-ai-marketplace')
        }
    },

    /**
     * Edit function for the block.
     */
    edit: (props) => {
        const { attributes, setAttributes } = props;
        const blockProps = useBlockProps({
            className: `vortex-checkout vortex-checkout-${attributes.checkoutLayout}`
        });

        // State for preview
        const [checkoutStep, setCheckoutStep] = useState('cart-review');
        const [isLoading, setIsLoading] = useState(false);

        // Helper to get required fields as array
        const getRequiredFieldsArray = () => {
            return attributes.requiredFields.split(',').map(field => field.trim());
        };

        // Helper to set required fields from array
        const setRequiredFieldsFromArray = (fieldsArray) => {
            setAttributes({ requiredFields: fieldsArray.join(',') });
        };

        // Toggle a field in required fields
        const toggleRequiredField = (field) => {
            const fields = getRequiredFieldsArray();
            if (fields.includes(field)) {
                setRequiredFieldsFromArray(fields.filter(f => f !== field));
            } else {
                setRequiredFieldsFromArray([...fields, field]);
            }
        };

        return (
            <div {...blockProps}>
                <InspectorControls>
                    <PanelBody title={__('Checkout Settings', 'vortex-ai-marketplace')}>
                        <TextControl
                            label={__('Checkout Title', 'vortex-ai-marketplace')}
                            value={attributes.title}
                            onChange={(title) => setAttributes({ title })}
                        />

                        <TextControl
                            label={__('Empty Cart Message', 'vortex-ai-marketplace')}
                            value={attributes.emptyCartMessage}
                            onChange={(emptyCartMessage) => setAttributes({ emptyCartMessage })}
                        />

                        <ToggleControl
                            label={__('Show Order Summary', 'vortex-ai-marketplace')}
                            checked={attributes.showOrderSummary}
                            onChange={() => setAttributes({ showOrderSummary: !attributes.showOrderSummary })}
                        />

                        <ToggleControl
                            label={__('Show Billing Fields', 'vortex-ai-marketplace')}
                            checked={attributes.showBillingFields}
                            onChange={() => setAttributes({ showBillingFields: !attributes.showBillingFields })}
                        />

                        <ToggleControl
                            label={__('Wallet Connection Required', 'vortex-ai-marketplace')}
                            checked={attributes.walletConnectionRequired}
                            onChange={() => setAttributes({ walletConnectionRequired: !attributes.walletConnectionRequired })}
                        />

                        <SelectControl
                            label={__('Supported Wallets', 'vortex-ai-marketplace')}
                            value={attributes.supportedWallets}
                            options={[
                                { label: __('Metamask', 'vortex-ai-marketplace'), value: 'metamask' },
                                { label: __('Phantom', 'vortex-ai-marketplace'), value: 'phantom' },
                                { label: __('WalletConnect', 'vortex-ai-marketplace'), value: 'walletconnect' },
                            ]}
                            onChange={(supportedWallets) => setAttributes({ supportedWallets })}
                        />

                        <SelectControl
                            label={__('Checkout Layout', 'vortex-ai-marketplace')}
                            value={attributes.checkoutLayout}
                            options={[
                                { label: __('Two Column', 'vortex-ai-marketplace'), value: 'two-column' },
                                { label: __('One Column', 'vortex-ai-marketplace'), value: 'one-column' },
                            ]}
                            onChange={(checkoutLayout) => setAttributes({ checkoutLayout })}
                        />

                        <ToggleControl
                            label={__('Enable Coupons', 'vortex-ai-marketplace')}
                            checked={attributes.enableCoupons}
                            onChange={() => setAttributes({ enableCoupons: !attributes.enableCoupons })}
                        />

                        <ToggleControl
                            label={__('Show Return to Cart Button', 'vortex-ai-marketplace')}
                            checked={attributes.showReturnToCart}
                            onChange={() => setAttributes({ showReturnToCart: !attributes.showReturnToCart })}
                        />
                    </PanelBody>

                    <PanelBody title={__('Order Confirmation', 'vortex-ai-marketplace')}>
                        <TextControl
                            label={__('Order Confirmation Message', 'vortex-ai-marketplace')}
                            value={attributes.orderConfirmationMessage}
                            onChange={(orderConfirmationMessage) => setAttributes({ orderConfirmationMessage })}
                        />
                    </PanelBody>

                    <PanelBody title={__('Terms and Conditions', 'vortex-ai-marketplace')}>
                        <TextControl
                            label={__('Terms Text', 'vortex-ai-marketplace')}
                            value={attributes.termsText}
                            onChange={(termsText) => setAttributes({ termsText })}
                        />

                        <ToggleControl
                            label={__('Terms Required', 'vortex-ai-marketplace')}
                            checked={attributes.termsRequired}
                            onChange={() => setAttributes({ termsRequired: !attributes.termsRequired })}
                        />

                        <TextControl
                            label={__('Terms URL', 'vortex-ai-marketplace')}
                            value={attributes.termsUrl}
                            onChange={(termsUrl) => setAttributes({ termsUrl })}
                        />

                        <TextControl
                            label={__('Privacy URL', 'vortex-ai-marketplace')}
                            value={attributes.privacyUrl}
                            onChange={(privacyUrl) => setAttributes({ privacyUrl })}
                        />
                    </PanelBody>

                    <PanelBody title={__('Button Settings', 'vortex-ai-marketplace')}>
                        <TextControl
                            label={__('Button Text', 'vortex-ai-marketplace')}
                            value={attributes.buttonText}
                            onChange={(buttonText) => setAttributes({ buttonText })}
                        />
                    </PanelBody>
                </InspectorControls>

                <div className="vortex-block-preview">
                    <Placeholder
                        icon="money-alt"
                        label={__('VORTEX Checkout', 'vortex-ai-marketplace')}
                        instructions={__('Displays a checkout form for users to complete their purchase.', 'vortex-ai-marketplace')}
                        className="vortex-editor-placeholder"
                    >
                        <div className="vortex-block-preview-settings">
                            <div className="vortex-block-preview-setting">
                                <span>{__('Layout:', 'vortex-ai-marketplace')}</span>
                                <strong>{attributes.checkoutLayout.charAt(0).toUpperCase() + attributes.checkoutLayout.slice(1)}</strong>
                            </div>
                            <div className="vortex-block-preview-setting">
                                <span>{__('Wallet Connection:', 'vortex-ai-marketplace')}</span>
                                <strong>{attributes.walletConnectionRequired ? __('Required', 'vortex-ai-marketplace') : __('Not Required', 'vortex-ai-marketplace')}</strong>
                            </div>
                            <div className="vortex-block-preview-setting">
                                <span>{__('Coupons:', 'vortex-ai-marketplace')}</span>
                                <strong>{attributes.enableCoupons ? __('Enabled', 'vortex-ai-marketplace') : __('Disabled', 'vortex-ai-marketplace')}</strong>
                            </div>
                        </div>

                        {isLoading ? (
                            <div className="vortex-preview-loading">
                                <Spinner />
                                <p>{__('Loading preview...', 'vortex-ai-marketplace')}</p>
                            </div>
                        ) : (
                            <div className="vortex-checkout-preview">
                                <h3>{attributes.title}</h3>
                                
                                {/* Preview content */}
                            </div>
                        )}

                        <div className="vortex-checkout-edit-preview">
                            <ServerSideRender
                                block="vortex/checkout"
                                attributes={attributes}
                                EmptyResponsePlaceholder={() => (
                                    <p>{__('The checkout is currently in preview mode.', 'vortex-ai-marketplace')}</p>
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
     * This block is rendered by PHP, so we just return null.
     */
    save: () => {
        return null;
    },
}); 