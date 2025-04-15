<?php
/**
 * VORTEX DAO Investor Application Form Template
 *
 * @package VORTEX
 */

/**
 * Generate an investor application form with enhanced accessibility.
 *
 * @param array $config DAO configuration.
 * @return string HTML content of the form.
 */
function vortex_generate_investor_application_form($config) {
    // Get the current user data if logged in
    $current_user = wp_get_current_user();
    $first_name = $current_user->first_name;
    $last_name = $current_user->last_name;
    $email = $current_user->user_email;
    
    // Get wallet address if previously saved
    $wallet_address = get_user_meta($current_user->ID, 'vortex_wallet_address', true);
    
    // Generate unique IDs for accessibility
    $form_id = 'vortex-investor-application-form';
    $terms_modal_id = 'terms-modal';
    $response_area_id = 'application-response';
    
    ob_start();
    ?>
    <div class="vortex-investor-application-container marketplace-frontend-wrapper">
        <!-- Skip link for keyboard navigation -->
        <a href="#<?php echo esc_attr($form_id); ?>" class="skip-to-content"><?php _e('Skip to application form', 'vortex'); ?></a>
        
        <h1 class="marketplace-frontend-title" id="investor-application-title"><?php _e('Investor Application', 'vortex'); ?></h1>
        
        <div class="marketplace-frontend-content" aria-labelledby="investor-application-title">
            <?php if (!is_user_logged_in()) : ?>
                <div class="vortex-login-required-message" role="alert">
                    <p><?php _e('You must be logged in to apply as an investor.', 'vortex'); ?></p>
                    <a href="<?php echo wp_login_url(get_permalink()); ?>" class="vortex-btn"><?php _e('Log In', 'vortex'); ?></a>
                </div>
            <?php else : ?>
                <div class="vortex-investor-application-intro">
                    <p><?php _e('Thank you for your interest in investing in VORTEX AI Marketplace. Please complete the form below to submit your application.', 'vortex'); ?></p>
                    
                    <div class="vortex-investment-info" aria-labelledby="investment-details-heading">
                        <h2 id="investment-details-heading" class="sr-only"><?php _e('Investment Details', 'vortex'); ?></h2>
                        <dl class="vortex-info-list">
                            <div class="vortex-info-item">
                                <dt class="info-label"><?php _e('Minimum Investment:', 'vortex'); ?></dt>
                                <dd class="info-value">$<?php echo number_format($config['min_investment'], 2); ?></dd>
                            </div>
                            <div class="vortex-info-item">
                                <dt class="info-label"><?php _e('Token Price:', 'vortex'); ?></dt>
                                <dd class="info-value">$<?php echo number_format($config['token_price'], 2); ?> per TOLA-Equity</dd>
                            </div>
                            <div class="vortex-info-item">
                                <dt class="info-label"><?php _e('Vesting Period:', 'vortex'); ?></dt>
                                <dd class="info-value"><?php echo $config['investor_vesting_months']; ?> months</dd>
                            </div>
                        </dl>
                    </div>
                </div>
                
                <form id="<?php echo esc_attr($form_id); ?>" class="vortex-form marketplace-form" aria-labelledby="investor-application-title" novalidate>
                    <div class="vortex-form-section" role="group" aria-labelledby="personal-info-heading">
                        <h2 id="personal-info-heading"><?php _e('Personal Information', 'vortex'); ?></h2>
                        
                        <div class="vortex-form-row">
                            <div class="vortex-form-group">
                                <label for="first_name" id="first_name_label"><?php _e('First Name', 'vortex'); ?> <span class="required-indicator" aria-hidden="true">*</span></label>
                                <input type="text" id="first_name" name="first_name" value="<?php echo esc_attr($first_name); ?>" 
                                       required aria-required="true" aria-labelledby="first_name_label" 
                                       aria-describedby="first_name_error" autocomplete="given-name">
                                <span id="first_name_error" class="form-error" aria-live="polite"></span>
                            </div>
                            
                            <div class="vortex-form-group">
                                <label for="last_name" id="last_name_label"><?php _e('Last Name', 'vortex'); ?> <span class="required-indicator" aria-hidden="true">*</span></label>
                                <input type="text" id="last_name" name="last_name" value="<?php echo esc_attr($last_name); ?>" 
                                       required aria-required="true" aria-labelledby="last_name_label" 
                                       aria-describedby="last_name_error" autocomplete="family-name">
                                <span id="last_name_error" class="form-error" aria-live="polite"></span>
                            </div>
                        </div>
                        
                        <div class="vortex-form-row">
                            <div class="vortex-form-group">
                                <label for="email" id="email_label"><?php _e('Email Address', 'vortex'); ?> <span class="required-indicator" aria-hidden="true">*</span></label>
                                <input type="email" id="email" name="email" value="<?php echo esc_attr($email); ?>" 
                                       required aria-required="true" aria-labelledby="email_label" 
                                       aria-describedby="email_error" autocomplete="email">
                                <span id="email_error" class="form-error" aria-live="polite"></span>
                            </div>
                            
                            <div class="vortex-form-group">
                                <label for="phone" id="phone_label"><?php _e('Phone Number', 'vortex'); ?> <span class="required-indicator" aria-hidden="true">*</span></label>
                                <input type="tel" id="phone" name="phone" placeholder="e.g. (555) 123-4567" 
                                       required aria-required="true" aria-labelledby="phone_label" 
                                       aria-describedby="phone_error" autocomplete="tel">
                                <span id="phone_error" class="form-error" aria-live="polite"></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="vortex-form-section" role="group" aria-labelledby="investment-details-form-heading">
                        <h2 id="investment-details-form-heading"><?php _e('Investment Details', 'vortex'); ?></h2>
                        
                        <div class="vortex-form-row">
                            <div class="vortex-form-group">
                                <label for="wallet_address" id="wallet_address_label"><?php _e('Wallet Address', 'vortex'); ?> <span class="required-indicator" aria-hidden="true">*</span></label>
                                <div class="wallet-address-container">
                                    <input type="text" id="wallet_address" name="wallet_address" value="<?php echo esc_attr($wallet_address); ?>" 
                                           required aria-required="true" aria-labelledby="wallet_address_label" 
                                           aria-describedby="wallet_address_help wallet_address_error">
                                    <button type="button" id="connect-wallet-btn" class="vortex-btn" aria-controls="wallet_address"><?php _e('Connect Wallet', 'vortex'); ?></button>
                                </div>
                                <p id="wallet_address_help" class="form-help-text"><?php _e('Connect your Solana wallet or enter your wallet address manually.', 'vortex'); ?></p>
                                <span id="wallet_address_error" class="form-error" aria-live="polite"></span>
                            </div>
                        </div>
                        
                        <div class="vortex-form-row">
                            <div class="vortex-form-group">
                                <label for="investment_amount" id="investment_amount_label"><?php _e('Investment Amount (USD)', 'vortex'); ?> <span class="required-indicator" aria-hidden="true">*</span></label>
                                <input type="number" id="investment_amount" name="investment_amount" 
                                       min="<?php echo esc_attr($config['min_investment']); ?>" step="100" 
                                       required aria-required="true" aria-labelledby="investment_amount_label" 
                                       aria-describedby="investment_amount_help investment_amount_error">
                                <p id="investment_amount_help" class="form-help-text"><?php _e('Minimum investment:', 'vortex'); ?> $<?php echo number_format($config['min_investment'], 2); ?></p>
                                <span id="investment_amount_error" class="form-error" aria-live="polite"></span>
                            </div>
                            
                            <div class="vortex-form-group token-calculation">
                                <label id="token_calculation_label"><?php _e('Tokens to Receive:', 'vortex'); ?></label>
                                <div id="token_calculation" class="token-amount" aria-live="polite" aria-atomic="true" aria-labelledby="token_calculation_label">0 TOLA-Equity</div>
                            </div>
                        </div>
                        
                        <div class="vortex-form-row">
                            <div class="vortex-form-group checkbox-group">
                                <div class="checkbox-wrapper">
                                    <input type="checkbox" id="accredited" name="accredited" value="yes" aria-describedby="accredited_label">
                                    <label for="accredited" id="accredited_label" class="checkbox-label"><?php _e('I certify that I am an accredited investor as defined by securities regulations.', 'vortex'); ?></label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="vortex-form-section" role="group" aria-labelledby="terms-conditions-heading">
                        <h2 id="terms-conditions-heading"><?php _e('Terms and Conditions', 'vortex'); ?></h2>
                        
                        <div class="vortex-form-row">
                            <div class="vortex-form-group checkbox-group">
                                <div class="checkbox-wrapper">
                                    <input type="checkbox" id="terms_agreement" name="terms_agreement" value="yes" 
                                           required aria-required="true" aria-describedby="terms_agreement_label terms_agreement_error">
                                    <label for="terms_agreement" id="terms_agreement_label" class="checkbox-label"><?php _e('I have read and agree to the Investment Terms and Conditions.', 'vortex'); ?></label>
                                </div>
                                <span id="terms_agreement_error" class="form-error" aria-live="polite"></span>
                                <button type="button" id="view-terms-btn" class="view-terms-link" aria-haspopup="dialog" aria-controls="<?php echo esc_attr($terms_modal_id); ?>"><?php _e('View Terms', 'vortex'); ?></button>
                            </div>
                        </div>
                        
                        <div class="vortex-form-row">
                            <div class="vortex-form-group checkbox-group">
                                <div class="checkbox-wrapper">
                                    <input type="checkbox" id="risk_acknowledgment" name="risk_acknowledgment" value="yes" 
                                           required aria-required="true" aria-describedby="risk_acknowledgment_label risk_acknowledgment_error">
                                    <label for="risk_acknowledgment" id="risk_acknowledgment_label" class="checkbox-label"><?php _e('I understand the risks involved in cryptocurrency investments and that I could lose my entire investment amount.', 'vortex'); ?></label>
                                </div>
                                <span id="risk_acknowledgment_error" class="form-error" aria-live="polite"></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="vortex-form-actions">
                        <input type="hidden" name="action" value="vortex_investor_application">
                        <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('vortex_investor_application'); ?>">
                        <button type="submit" id="submit-application" class="vortex-btn vortex-btn-primary"><?php _e('Submit Application', 'vortex'); ?></button>
                    </div>
                    
                    <div class="form-status-message" aria-live="assertive" role="status"></div>
                </form>
                
                <div id="<?php echo esc_attr($response_area_id); ?>" class="vortex-application-response" style="display: none;" aria-live="assertive" role="status"></div>
                
                <!-- Terms and Conditions Modal -->
                <div id="<?php echo esc_attr($terms_modal_id); ?>" class="vortex-modal marketplace-modal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="terms-modal-title">
                    <div class="vortex-modal-content">
                        <div class="vortex-modal-header">
                            <h2 id="terms-modal-title"><?php _e('Investment Terms and Conditions', 'vortex'); ?></h2>
                            <button type="button" class="vortex-modal-close" aria-label="<?php _e('Close terms and conditions', 'vortex'); ?>">&times;</button>
                        </div>
                        <div class="vortex-modal-body">
                            <section aria-labelledby="terms-investment-heading">
                                <h3 id="terms-investment-heading">1. Investment Terms</h3>
                                <p>By investing in VORTEX AI Marketplace, you are purchasing TOLA-Equity tokens at a price of $<?php echo number_format($config['token_price'], 2); ?> per token.</p>
                            </section>
                            
                            <section aria-labelledby="terms-vesting-heading">
                                <h3 id="terms-vesting-heading">2. Vesting Schedule</h3>
                                <p>The TOLA-Equity tokens allocated to you will be subject to a vesting period of <?php echo $config['investor_vesting_months']; ?> months, with a cliff period of <?php echo $config['investor_cliff_months']; ?> months.</p>
                                <p>After the cliff period, tokens will vest linearly on a monthly basis until fully vested at the end of the vesting period. Unvested tokens cannot be transferred or sold.</p>
                            </section>
                            
                            <section aria-labelledby="terms-rights-heading">
                                <h3 id="terms-rights-heading">3. Investor Rights</h3>
                                <p>TOLA-Equity tokens provide you with governance rights, revenue sharing, and other benefits as outlined in the full investor agreement.</p>
                            </section>
                            
                            <section aria-labelledby="terms-kyc-heading">
                                <h3 id="terms-kyc-heading">4. KYC/AML Requirements</h3>
                                <p>All investors must complete KYC/AML verification before receiving tokens.</p>
                            </section>
                            
                            <section aria-labelledby="terms-risks-heading">
                                <h3 id="terms-risks-heading">5. Risks</h3>
                                <p>Cryptocurrency investments involve substantial risk. You could lose your entire investment. Only invest what you can afford to lose.</p>
                            </section>
                            
                            <div class="vortex-modal-actions">
                                <button id="accept-terms-btn" class="vortex-btn" aria-controls="terms_agreement"><?php _e('I Accept These Terms', 'vortex'); ?></button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        // Focus management for modal
        let $lastFocusedElement = null;
        
        // Calculate tokens on investment amount change
        $('#investment_amount').on('input', function() {
            const amount = parseFloat($(this).val()) || 0;
            const tokenPrice = <?php echo $config['token_price']; ?>;
            const tokenAmount = Math.floor(amount / tokenPrice);
            $('#token_calculation').text(tokenAmount.toLocaleString() + ' TOLA-Equity');
        });
        
        // Connect wallet button
        $('#connect-wallet-btn').on('click', function(e) {
            e.preventDefault();
            
            // Show loading state
            $(this).addClass('loading').text('<?php _e('Connecting...', 'vortex'); ?>');
            
            if (typeof window.VortexMarketplace !== 'undefined') {
                window.VortexMarketplace.connectWallet()
                    .finally(() => {
                        $(this).removeClass('loading').text('<?php _e('Connect Wallet', 'vortex'); ?>');
                    });
            } else if (typeof window.solana !== 'undefined') {
                // Fallback direct connection
                window.solana.connect()
                    .then(response => {
                        const walletAddress = response.publicKey.toString();
                        $('#wallet_address').val(walletAddress);
                        // Announce to screen readers
                        const announcement = $('<div class="sr-only" role="status" aria-live="polite"><?php _e('Wallet connected successfully', 'vortex'); ?></div>');
                        $('body').append(announcement);
                        setTimeout(() => announcement.remove(), 3000);
                    })
                    .catch(error => {
                        console.error('Wallet connection error:', error);
                        // Show error message in accessible way
                        $('#wallet_address_error').text('<?php _e('Failed to connect wallet. Please try again or enter your address manually.', 'vortex'); ?>');
                        $('#wallet_address').attr('aria-invalid', 'true');
                    })
                    .finally(() => {
                        $(this).removeClass('loading').text('<?php _e('Connect Wallet', 'vortex'); ?>');
                    });
            } else {
                // Show error in accessible way
                $('#wallet_address_error').text('<?php _e('No wallet extension found. Please install a Solana wallet extension like Phantom.', 'vortex'); ?>');
                $('#wallet_address').attr('aria-invalid', 'true').focus();
                $(this).removeClass('loading').text('<?php _e('Connect Wallet', 'vortex'); ?>');
            }
        });
        
        // Handle wallet connection event
        $(document).on('vortex_wallet_connected', function(event, walletAddress) {
            if (walletAddress) {
                $('#wallet_address').val(walletAddress);
                // Clear any previous errors
                $('#wallet_address_error').empty();
                $('#wallet_address').removeAttr('aria-invalid');
            }
        });
        
        // Terms modal accessibility improvements
        function openModal() {
            // Store the currently focused element
            $lastFocusedElement = $(document.activeElement);
            
            // Show the modal
            const $modal = $('#<?php echo esc_attr($terms_modal_id); ?>');
            $modal.attr('aria-hidden', 'false').addClass('active');
            
            // Prevent body scrolling
            $('body').addClass('modal-open');
            
            // Focus the first interactive element inside the modal
            setTimeout(() => {
                // First try to focus the close button
                const $closeButton = $modal.find('.vortex-modal-close');
                if ($closeButton.length) {
                    $closeButton.focus();
                } else {
                    // Fallback to the modal itself
                    $modal.attr('tabindex', '-1').focus();
                }
            }, 50);
            
            // Add ESC key listener
            $(document).on('keydown.modal', function(e) {
                if (e.key === 'Escape') {
                    closeModal();
                }
            });
            
            // Add trap focus
            $modal.on('keydown.trapFocus', trapFocus);
        }
        
        function closeModal() {
            const $modal = $('#<?php echo esc_attr($terms_modal_id); ?>');
            $modal.attr('aria-hidden', 'true').removeClass('active');
            
            // Enable body scrolling
            $('body').removeClass('modal-open');
            
            // Return focus to the last focused element
            if ($lastFocusedElement && $lastFocusedElement.length) {
                $lastFocusedElement.focus();
            }
            
            // Remove event listeners
            $(document).off('keydown.modal');
            $modal.off('keydown.trapFocus');
        }
        
        function trapFocus(e) {
            // If Tab key is pressed
            if (e.key === 'Tab') {
                const $modal = $(e.delegateTarget);
                const $focusableElements = $modal.find('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
                const $firstFocusable = $focusableElements.first();
                const $lastFocusable = $focusableElements.last();
                
                // If shift+tab on first element, wrap to last
                if (e.shiftKey && document.activeElement === $firstFocusable[0]) {
                    e.preventDefault();
                    $lastFocusable.focus();
                } 
                // If tab on last element, wrap to first
                else if (!e.shiftKey && document.activeElement === $lastFocusable[0]) {
                    e.preventDefault();
                    $firstFocusable.focus();
                }
            }
        }
        
        // Terms modal triggers
        $('#view-terms-btn').on('click', function(e) {
            e.preventDefault();
            openModal();
        });
        
        $('.vortex-modal-close').on('click', function() {
            closeModal();
        });
        
        $('#accept-terms-btn').on('click', function() {
            $('#terms_agreement').prop('checked', true);
            // Clear any error message
            $('#terms_agreement_error').empty();
            $('#terms_agreement').removeAttr('aria-invalid');
            closeModal();
        });
        
        // Close modal when clicking outside content
        $(document).on('click', function(e) {
            if ($(e.target).is('.vortex-modal')) {
                closeModal();
            }
        });
        
        // Form validation
        function validateField($field) {
            const $errorElement = $('#' + $field.attr('id') + '_error');
            
            // Skip validation for non-required fields if empty
            if (!$field.prop('required') && !$field.val()) {
                $field.removeAttr('aria-invalid');
                $errorElement.empty();
                return true;
            }
            
            // Basic HTML5 validation
            if (!$field[0].checkValidity()) {
                $field.attr('aria-invalid', 'true');
                $errorElement.text($field[0].validationMessage);
                return false;
            }
            
            // Additional validations if needed
            // For example, minimum investment amount
            if ($field.attr('id') === 'investment_amount') {
                const value = parseFloat($field.val());
                const min = parseFloat($field.attr('min'));
                
                if (value < min) {
                    $field.attr('aria-invalid', 'true');
                    $errorElement.text('<?php _e('Investment amount must be at least $', 'vortex'); ?>' + min);
                    return false;
                }
            }
            
            // Clear error if valid
            $field.removeAttr('aria-invalid');
            $errorElement.empty();
            return true;
        }
        
        // Validate each field on blur
        $('#<?php echo esc_attr($form_id); ?> input, #<?php echo esc_attr($form_id); ?> select, #<?php echo esc_attr($form_id); ?> textarea').on('blur', function() {
            validateField($(this));
        });
        
        // Form submission with improved accessibility and validation
        $('#<?php echo esc_attr($form_id); ?>').on('submit', function(e) {
            e.preventDefault();
            
            const $form = $(this);
            const $submitButton = $('#submit-application');
            const $statusArea = $form.find('.form-status-message');
            const $responseArea = $('#<?php echo esc_attr($response_area_id); ?>');
            let isValid = true;
            
            // Validate all fields before submission
            $form.find('input, select, textarea').each(function() {
                if (!validateField($(this))) {
                    isValid = false;
                }
            });
            
            if (!isValid) {
                // Focus the first invalid field
                $form.find('[aria-invalid="true"]').first().focus();
                
                // Announce validation error to screen readers
                $statusArea.attr('role', 'alert').text('<?php _e('Please correct the errors before submitting the form.', 'vortex'); ?>');
                return;
            }
            
            // Clear status
            $statusArea.empty();
            
            // Show loading state
            $submitButton.prop('disabled', true).attr('aria-busy', 'true').text('<?php _e('Processing...', 'vortex'); ?>');
            
            // Submit form
            $.ajax({
                url: vortex_marketplace_vars.ajax_url,
                type: 'POST',
                data: $form.serialize(),
                dataType: 'json',
                success: function(response) {
                    $submitButton.prop('disabled', false).removeAttr('aria-busy').text('<?php _e('Submit Application', 'vortex'); ?>');
                    
                    if (response.success) {
                        $form.hide();
                        
                        // Accessible success message
                        $responseArea.html('<div class="vortex-message vortex-message-success" role="status">' + response.data.message + '</div>').show();
                        
                        // Focus to the response area for screen readers
                        $responseArea.attr('tabindex', '-1').focus();
                    } else {
                        // Show error in both places for accessibility
                        $responseArea.html('<div class="vortex-message vortex-message-error" role="alert">' + response.data.message + '</div>').show();
                        $statusArea.attr('role', 'alert').text(response.data.message);
                    }
                },
                error: function() {
                    $submitButton.prop('disabled', false).removeAttr('aria-busy').text('<?php _e('Submit Application', 'vortex'); ?>');
                    
                    // Show error message
                    const errorMessage = '<?php _e('An error occurred. Please try again.', 'vortex'); ?>';
                    $responseArea.html('<div class="vortex-message vortex-message-error" role="alert">' + errorMessage + '</div>').show();
                    $statusArea.attr('role', 'alert').text(errorMessage);
                }
            });
        });
    });
    </script>
    <?php
    return ob_get_clean();
} 