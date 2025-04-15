/**
 * VORTEX DAO WooCommerce Integration
 */
(function($) {
    'use strict';
    
    const VortexDAOWooCommerce = {
        init: function() {
            // Initialize on document ready
            $(document).ready(this.onDocumentReady.bind(this));
        },
        
        onDocumentReady: function() {
            // Product page enhancements
            this.initProductPage();
            
            // Account page enhancements
            this.initAccountPage();
        },
        
        initProductPage: function() {
            if (!$('.vortex-dao-product-rewards').length) {
                return;
            }
            
            // Animate rewards badge
            $('.dao-reward-badge').addClass('animate__animated animate__fadeIn');
            
            // Quantity change handling
            $('form.cart').on('change', 'input.qty', function() {
                const qty = $(this).val();
                const baseReward = parseFloat($('.dao-reward-text strong').data('base-reward') || 0);
                
                if (baseReward && qty > 0) {
                    const totalReward = (baseReward * qty).toFixed(2);
                    $('.dao-reward-text strong').text(totalReward);
                }
            });
            
            // Store base reward on page load
            const baseRewardText = $('.dao-reward-text strong').text();
            if (baseRewardText) {
                $('.dao-reward-text strong').data('base-reward', parseFloat(baseRewardText));
            }
            
            // Toggle reward details
            $('.dao-reward-info-toggle').on('click', function(e) {
                e.preventDefault();
                const $details = $('.dao-reward-details');
                
                if ($details.is(':visible')) {
                    $details.slideUp(200);
                    $(this).find('.dashicons').removeClass('dashicons-arrow-up').addClass('dashicons-info-outline');
                } else {
                    $details.slideDown(200);
                    $(this).find('.dashicons').removeClass('dashicons-info-outline').addClass('dashicons-arrow-up');
                }
            });
        },
        
        initAccountPage: function() {
            if (!$('.vortex-dao-rewards-container').length) {
                return;
            }
            
            // Initialize achievement cards
            $('.achievement-card').hover(
                function() {
                    $(this).addClass('achievement-hover');
                },
                function() {
                    $(this).removeClass('achievement-hover');
                }
            );
            
            // Claim reward button handling
            $('.claim-reward-btn').on('click', function() {
                const rewardId = $(this).data('reward-id');
                const $button = $(this);
                
                $button.prop('disabled', true).addClass('loading');
                
                $.ajax({
                    url: vortexDAOWooCommerce.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'vortex_claim_dao_product_reward',
                        reward_id: rewardId,
                        nonce: vortexDAOWooCommerce.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            // Replace button with claimed status
                            $button.replaceWith(`
                                <div class="reward-claimed">
                                    <span class="dashicons dashicons-yes-alt"></span>
                                    Claimed
                                </div>
                            `);
                            
                            // Update TOLA balance
                            const newBalance = parseFloat($('.tola-amount').text()) + parseFloat(response.data.tola_amount);
                            $('.tola-amount').text(newBalance.toFixed(2));
                            
                            // Show success notification
                            VortexDAOWooCommerce.showNotification(response.data.message, 'success');
                            
                            // Trigger an event for other components to listen to
                            $(document).trigger('vortex_dao_reward_claimed', [response.data]);
                        } else {
                            $button.prop('disabled', false).removeClass('loading');
                            VortexDAOWooCommerce.showNotification(response.data.message, 'error');
                        }
                    },
                    error: function() {
                        $button.prop('disabled', false).removeClass('loading');
                        VortexDAOWooCommerce.showNotification('Server error while claiming reward', 'error');
                    }
                });
            });
            
            // Initialize blockchain transaction history
            this.initBlockchainHistory();
        },
        
        initBlockchainHistory: function() {
            $('.tola-history-btn').on('click', function(e) {
                e.preventDefault();
                
                // Check if history is already loaded
                if ($('.tola-history-container').length) {
                    $('.tola-history-container').slideToggle(300);
                    return;
                }
                
                // Show loading indicator
                const $historySection = $('<div class="tola-history-container loading"></div>');
                $historySection.html('<div class="vortex-loading"><span class="dashicons dashicons-image-rotate"></span> Loading transaction history...</div>');
                $(this).closest('.tola-actions').after($historySection);
                
                // Ajax request to get transaction history
                $.ajax({
                    url: vortexDAOWooCommerce.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'vortex_get_tola_transactions',
                        nonce: vortexDAOWooCommerce.nonce
                    },
                    success: function(response) {
                        $historySection.removeClass('loading');
                        
                        if (response.success) {
                            VortexDAOWooCommerce.renderTransactionHistory($historySection, response.data);
                        } else {
                            $historySection.html(`<div class="vortex-error">${response.data.message || 'Error loading transaction history'}</div>`);
                        }
                    },
                    error: function() {
                        $historySection.removeClass('loading');
                        $historySection.html('<div class="vortex-error">Server error while loading transaction history</div>');
                    }
                });
            });
        },
        
        renderTransactionHistory: function($container, transactions) {
            if (!transactions || transactions.length === 0) {
                $container.html('<div class="no-transactions-message">No transaction history available yet.</div>');
                return;
            }
            
            let html = '<div class="transaction-history-header">';
            html += '<h4>TOLA Transaction History</h4>';
            html += '<button class="close-history-btn"><span class="dashicons dashicons-no-alt"></span></button>';
            html += '</div>';
            
            html += '<table class="transaction-history-table">';
            html += '<thead><tr>';
            html += '<th>Date</th>';
            html += '<th>Type</th>';
            html += '<th>Amount</th>';
            html += '<th>Source</th>';
            html += '<th>Blockchain</th>';
            html += '</tr></thead>';
            
            html += '<tbody>';
            
            transactions.forEach(tx => {
                const date = new Date(tx.created_at);
                const formattedDate = date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
                
                html += '<tr>';
                html += `<td>${formattedDate}</td>`;
                
                // Transaction type
                let typeClass = tx.type === 'add' ? 'tx-add' : 'tx-subtract';
                let typeIcon = tx.type === 'add' ? 'plus-alt2' : 'minus';
                html += `<td class="${typeClass}"><span class="dashicons dashicons-${typeIcon}"></span> ${tx.type === 'add' ? 'Received' : 'Spent'}</td>`;
                
                // Amount
                html += `<td class="${typeClass}">${tx.type === 'add' ? '+' : '-'}${tx.amount} TOLA</td>`;
                
                // Source
                let source = 'Unknown';
                let metadata = {};
                
                try {
                    if (tx.metadata) {
                        metadata = JSON.parse(tx.metadata);
                    }
                } catch(e) {
                    console.error('Error parsing transaction metadata:', e);
                }
                
                if (metadata.source === 'product_reward') {
                    source = 'Product Purchase';
                } else if (metadata.source === 'achievement') {
                    source = 'Achievement Reward';
                } else if (metadata.source === 'governance') {
                    source = 'Governance Participation';
                } else if (metadata.source === 'staking') {
                    source = 'Staking Reward';
                }
                
                html += `<td>${source}</td>`;
                
                // Blockchain reference
                if (tx.blockchain_ref) {
                    html += `<td><a href="https://tolascan.org/tx/${tx.blockchain_ref}" target="_blank" class="blockchain-link">
                        <span class="dashicons dashicons-shield"></span> View
                    </a></td>`;
                } else {
                    html += '<td>Off-chain</td>';
                }
                
                html += '</tr>';
            });
            
            html += '</tbody></table>';
            
            $container.html(html);
            
            // Add event listener for close button
            $container.find('.close-history-btn').on('click', function() {
                $container.slideUp(300);
            });
        },
        
        showNotification: function(message, type = 'info') {
            // Check if VortexDAO object exists and has showNotification method
            if (typeof VortexDAO !== 'undefined' && typeof VortexDAO.showNotification === 'function') {
                VortexDAO.showNotification(message, type);
                return;
            }
            
            // Fallback notification system
            let $container = $('.vortex-notifications');
            
            if (!$container.length) {
                $container = $('<div class="vortex-notifications"></div>');
                $('body').append($container);
            }
            
            const $notification = $(`
                <div class="vortex-notification ${type}">
                    <div class="notification-content">
                        <span class="notification-icon dashicons dashicons-${this.getNotificationIcon(type)}"></span>
                        <span class="notification-message">${message}</span>
                    </div>
                    <button class="notification-close" aria-label="Close">&times;</button>
                </div>
            `);
            
            $container.append($notification);
            
            // Animate notification
            setTimeout(() => {
                $notification.addClass('show');
            }, 10);
            
            // Auto close after delay
            const timeout = setTimeout(() => {
                this.closeNotification($notification);
            }, 5000);
            
            // Close button handler
            $notification.find('.notification-close').on('click', () => {
                clearTimeout(timeout);
                this.closeNotification($notification);
            });
        },
        
        closeNotification: function($notification) {
            $notification.removeClass('show');
            setTimeout(() => {
                $notification.remove();
            }, 300);
        },
        
        getNotificationIcon: function(type) {
            switch(type) {
                case 'success':
                    return 'yes-alt';
                case 'error':
                    return 'warning';
                case 'warning':
                    return 'flag';
                default:
                    return 'info';
            }
        },
        
        // Add admin settings page functionality
        initAdminSettings: function() {
            if (!$('.vortex-dao-woocommerce-settings').length) {
                return;
            }
            
            // Handle category/tag selection
            $('#vortex_dao_reward_categories, #vortex_dao_reward_tags').on('change', function() {
                const $selectedCount = $(this).closest('.form-field').find('.selected-count');
                const count = $(this).val() ? $(this).val().length : 0;
                $selectedCount.text(`${count} selected`);
            });
            
            // Initialize tooltips
            $('.vortex-tooltip').on('mouseenter', function() {
                const $tooltip = $(this);
                const tooltipText = $tooltip.data('tooltip');
                
                if (!tooltipText) return;
                
                const $tooltipContent = $('<div class="tooltip-content"></div>').text(tooltipText);
                $tooltip.append($tooltipContent);
                
                setTimeout(function() {
                    $tooltipContent.addClass('show');
                }, 10);
            }).on('mouseleave', function() {
                $(this).find('.tooltip-content').removeClass('show');
                setTimeout(() => {
                    $(this).find('.tooltip-content').remove();
                }, 300);
            });
            
            // Test reward calculation
            $('#test_reward_calculation').on('click', function(e) {
                e.preventDefault();
                
                const price = parseFloat($('#test_price').val());
                if (isNaN(price) || price <= 0) {
                    VortexDAOWooCommerce.showNotification('Please enter a valid price', 'error');
                    return;
                }
                
                const rate = parseFloat($('#vortex_dao_tola_reward_rate').val()) || 0.05;
                const minReward = parseFloat($('#vortex_dao_min_tola_reward').val()) || 1;
                
                let calculatedReward = price * rate;
                calculatedReward = Math.max(calculatedReward, minReward);
                calculatedReward = Math.round(calculatedReward * 100) / 100; // Round to 2 decimal places
                
                $('#calculated_reward').text(calculatedReward.toFixed(2) + ' TOLA');
                $('#reward_calculation_result').show();
            });
        }
    };
    
    // Initialize on document ready
    $(document).ready(function() {
        VortexDAOWooCommerce.init();
        
        // Initialize admin settings if on admin page
        if (typeof ajaxurl !== 'undefined') {
            VortexDAOWooCommerce.initAdminSettings();
        }
    });
    
})(jQuery); 