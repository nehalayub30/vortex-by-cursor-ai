/**
 * Cart functionality for VORTEX AI Marketplace
 *
 * Handles cart interactions, quantity updates, and checkout preparation
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/public/js
 * @author     Marianne Nems <Marianne@VortexArtec.com>
 */

(function($) {
    'use strict';

    /**
     * VortexCart object for managing shopping cart functionality
     */
    window.VortexCart = {
        /**
         * Initialize cart functionality
         */
        init: function() {
            this.setupCartQuantity();
            this.setupCartRemoval();
            this.setupCartUpdate();
            this.setupCouponForm();
            this.setupCheckoutValidation();
        },

        /**
         * Initialize quantity inputs
         */
        setupCartQuantity: function() {
            // Quantity input handling
            $(document).on('click', '.vortex-quantity-button', function() {
                const $button = $(this);
                const $input = $button.siblings('.vortex-quantity');
                let currentVal = parseInt($input.val()) || 1;
                
                if ($button.hasClass('vortex-quantity-plus')) {
                    $input.val(currentVal + 1).trigger('change');
                } else if ($button.hasClass('vortex-quantity-minus')) {
                    if (currentVal > 1) {
                        $input.val(currentVal - 1).trigger('change');
                    }
                }
            });
            
            // Update quantity via AJAX for cart items
            $('.vortex-cart-quantity .vortex-quantity').on('change', function() {
                const $input = $(this);
                const $cartItem = $input.closest('.vortex-cart-item');
                const quantity = parseInt($input.val()) || 1;
                const cartItemKey = $cartItem.data('cart-item-key');
                
                if (quantity < 1) {
                    $input.val(1);
                    return;
                }
                
                VortexCart.updateCartItemQuantity(cartItemKey, quantity, $cartItem);
            });
        },

        /**
         * Update cart item quantity
         */
        updateCartItemQuantity: function(cartItemKey, quantity, $cartItem) {
            const $quantityWrapper = $cartItem.find('.vortex-cart-quantity');
            const $priceWrapper = $cartItem.find('.vortex-cart-item-price');
            
            $quantityWrapper.addClass('vortex-loading');
            
            $.ajax({
                url: vortexVars.ajaxUrl,
                type: 'post',
                data: {
                    action: 'vortex_update_cart',
                    nonce: vortexVars.nonce,
                    cart_item_key: cartItemKey,
                    quantity: quantity
                },
                success: function(response) {
                    $quantityWrapper.removeClass('vortex-loading');
                    
                    if (response.success) {
                        // Update item price
                        $priceWrapper.html(response.data.cart_item_total);
                        
                        // Update cart totals
                        VortexCart.updateCartTotals(response.data.cart_count, response.data.cart_subtotal);
                        
                        // Trigger cart update event
                        $(document.body).trigger('vortex_cart_updated');
                    } else {
                        VortexPublic.showNotification('error', response.data.message);
                    }
                },
                error: function() {
                    $quantityWrapper.removeClass('vortex-loading');
                    VortexPublic.showNotification('error', vortexVars.i18n.error);
                }
            });
        },

        /**
         * Initialize cart removal functionality
         */
        setupCartRemoval: function() {
            $(document).on('click', '.vortex-cart-remove', function(e) {
                e.preventDefault();
                
                const $button = $(this);
                const cartItemKey = $button.data('cart-item-key');
                
                VortexCart.removeCartItem(cartItemKey);
            });
        },

        /**
         * Remove cart item
         */
        removeCartItem: function(cartItemKey) {
            $.ajax({
                url: vortexVars.ajaxUrl,
                type: 'post',
                data: {
                    action: 'vortex_remove_from_cart',
                    nonce: vortexVars.nonce,
                    cart_item_key: cartItemKey
                },
                success: function(response) {
                    if (response.success) {
                        // Trigger cart update event
                        $(document.body).trigger('vortex_cart_updated');
                    } else {
                        VortexPublic.showNotification('error', response.data.message);
                    }
                },
                error: function() {
                    VortexPublic.showNotification('error', vortexVars.i18n.error);
                }
            });
        },

        /**
         * Initialize cart update functionality
         */
        setupCartUpdate: function() {
            // Update cart totals via AJAX
            $(document.body).on('vortex_cart_updated', function() {
                VortexCart.refreshCartTotals();
            });
        },

        /**
         * Refresh cart totals
         */
        refreshCartTotals: function() {
            $.ajax({
                url: vortexVars.ajaxUrl,
                type: 'post',
                data: {
                    action: 'vortex_get_refreshed_fragments',
                    nonce: vortexVars.nonce
                },
                success: function(response) {
                    if (response && response.fragments) {
                        $.each(response.fragments, function(key, value) {
                            $(key).replaceWith(value);
                        });
                    }
                }
            });
        },

        /**
         * Initialize coupon form functionality
         */
        setupCouponForm: function() {
            // Coupon form submission
            $('.vortex-coupon-form').on('submit', function(e) {
                e.preventDefault();
                
                const $form = $(this);
                const couponCode = $form.find('.vortex-coupon-code').val();
                
                VortexCart.applyCoupon(couponCode);
            });
        },

        /**
         * Apply coupon
         */
        applyCoupon: function(couponCode) {
            $.ajax({
                url: vortexVars.ajaxUrl,
                type: 'post',
                data: {
                    action: 'vortex_apply_coupon',
                    nonce: vortexVars.nonce,
                    coupon_code: couponCode
                },
                success: function(response) {
                    if (response.success) {
                        // Trigger cart update event
                        $(document.body).trigger('vortex_cart_updated');
                        
                        // Show notification
                        VortexPublic.showNotification('success', response.data.message);
                    } else {
                        VortexPublic.showNotification('error', response.data.message);
                    }
                },
                error: function() {
                    VortexPublic.showNotification('error', vortexVars.i18n.error);
                }
            });
        },

        /**
         * Initialize checkout validation
         */
        setupCheckoutValidation: function() {
            // Checkout validation
            $(document).on('click', '.vortex-checkout-button', function(e) {
                e.preventDefault();
                
                const $button = $(this);
                const $form = $button.closest('.vortex-checkout-form');
                
                if (VortexCart.validateCheckout($form)) {
                    VortexCart.processCheckout($form);
                }
            });
        },

        /**
         * Validate checkout form
         */
        validateCheckout: function($form) {
            // Implement form validation logic here
            return true; // Placeholder return, actual implementation needed
        },

        /**
         * Process checkout
         */
        processCheckout: function($form) {
            // Implement checkout processing logic here
            // This is a placeholder and should be replaced with actual implementation
            VortexPublic.showNotification('success', 'Checkout process started');
        }
    };

    // Initialize when DOM is ready
    $(document).ready(function() {
        VortexCart.init();
    });

})(jQuery); 