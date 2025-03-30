/**
 * TOLA Token Integration for Vortex AI Marketplace
 */
(function($) {
    'use strict';

    // Initialize when document is ready
    $(document).ready(function() {
        // Initialize wallet connection
        initWalletConnection();
        
        // Initialize TOLA balance display
        updateTolaBalance();
        
        // Initialize token transaction handlers
        initTransactionHandlers();
        
        // Initialize product purchase handlers
        initProductPurchaseHandlers();
    });

    /**
     * Initialize Solana wallet connection
     */
    function initWalletConnection() {
        // Connect wallet button click handler
        $('.vortex-connect-wallet-button').on('click', function(e) {
            e.preventDefault();
            connectWallet();
        });
        
        // Disconnect wallet button click handler
        $('.vortex-disconnect-wallet-button').on('click', function(e) {
            e.preventDefault();
            disconnectWallet();
        });
        
        // Copy address button click handler
        $('.vortex-copy-address-button').on('click', function(e) {
            e.preventDefault();
            const address = $(this).data('address');
            copyToClipboard(address);
            alert('Address copied to clipboard!');
        });
    }
    
    /**
     * Connect to Solana wallet
     */
    async function connectWallet() {
        try {
            // Check if Phantom wallet is installed
            const isPhantomInstalled = window.solana && window.solana.isPhantom;
            
            if (!isPhantomInstalled) {
                alert('Phantom wallet is not installed. Please install it to continue.');
                window.open('https://phantom.app/', '_blank');
                return;
            }
            
            // Connect to wallet
            const response = await window.solana.connect();
            const walletAddress = response.publicKey.toString();
            
            // Save the wallet address via AJAX
            saveWalletAddress(walletAddress);
            
        } catch (error) {
            console.error('Error connecting to wallet:', error);
            alert('Failed to connect to wallet: ' + error.message);
        }
    }
    
    /**
     * Disconnect from Solana wallet
     */
    async function disconnectWallet() {
        try {
            if (window.solana && window.solana.isConnected) {
                await window.solana.disconnect();
            }
            
            // Remove the wallet address via AJAX
            $.ajax({
                url: vortexTola.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'vortex_disconnect_wallet',
                    nonce: vortexTola.nonce
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data.message);
                    }
                },
                error: function() {
                    alert('An error occurred while disconnecting your wallet.');
                }
            });
            
        } catch (error) {
            console.error('Error disconnecting wallet:', error);
            alert('Failed to disconnect wallet: ' + error.message);
        }
    }
    
    /**
     * Save wallet address to user profile
     */
    function saveWalletAddress(address) {
        $.ajax({
            url: vortexTola.ajaxUrl,
            type: 'POST',
            data: {
                action: 'vortex_save_wallet_address',
                wallet_address: address,
                nonce: vortexTola.nonce
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data.message);
                }
            },
            error: function() {
                alert('An error occurred while saving your wallet address.');
            }
        });
    }
    
    /**
     * Update TOLA balance display
     */
    function updateTolaBalance() {
        // Get wallet address from data attribute
        const walletAddressEl = $('.vortex-tola-wallet-address .value');
        
        if (walletAddressEl.length === 0) {
            return;
        }
        
        const walletAddress = $('.vortex-copy-address-button').data('address');
        
        if (!walletAddress) {
            return;
        }
        
        // Get TOLA balance via AJAX
        $.ajax({
            url: vortexTola.ajaxUrl,
            type: 'POST',
            data: {
                action: 'vortex_get_tola_balance',
                wallet_address: walletAddress,
                nonce: vortexTola.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('.vortex-tola-wallet-balance .value').text(response.data.formatted_balance);
                    $('.vortex-tola-balance-amount').text(response.data.formatted_balance);
                }
            },
            error: function() {
                console.error('Failed to update TOLA balance.');
            }
        });
    }
    
    /**
     * Initialize TOLA transaction handlers
     */
    function initTransactionHandlers() {
        // Send TOLA form submission
        $('.vortex-tola-send-form').on('submit', function(e) {
            e.preventDefault();
            
            const recipientAddress = $('#recipient_address').val();
            const amount = parseFloat($('#amount').val());
            
            if (!recipientAddress || isNaN(amount) || amount <= 0) {
                $('.vortex-tola-send-result').html('<div class="error">Please enter a valid recipient address and amount.</div>');
                return;
            }
            
            sendTolaTokens(recipientAddress, amount);
        });
    }
    
    /**
     * Send TOLA tokens to recipient
     */
    async function sendTolaTokens(recipient, amount) {
        try {
            // Check if Phantom wallet is installed and connected
            if (!window.solana || !window.solana.isPhantom || !window.solana.isConnected) {
                alert('Please connect your Phantom wallet first.');
                return;
            }
            
            // Get the token program
            const tokenProgramId = new solanaWeb3.PublicKey('TokenkegQfeZyiNwAJbNbGKPFXCWuBvf9Ss623VQ5DA');
            const tokenMint = new solanaWeb3.PublicKey(vortexTola.tokenAddress);
            
            // Get the current wallet
            const fromAddress = window.solana.publicKey.toString();
            
            // Create a new connection to the Solana network
            const connection = new solanaWeb3.Connection(vortexTola.rpcUrl);
            
            // Get token accounts
            const fromTokenAccounts = await connection.getParsedTokenAccountsByOwner(
                window.solana.publicKey,
                { mint: tokenMint }
            );
            
            if (fromTokenAccounts.value.length === 0) {
                alert('You do not have a TOLA token account.');
                return;
            }
            
            const fromTokenAccount = fromTokenAccounts.value[0].pubkey;
            
            // Get or create recipient token account
            let toTokenAccount;
            const recipientPubkey = new solanaWeb3.PublicKey(recipient);
            
            try {
                const toTokenAccounts = await connection.getParsedTokenAccountsByOwner(
                    recipientPubkey,
                    { mint: tokenMint }
                );
                
                if (toTokenAccounts.value.length > 0) {
                    toTokenAccount = toTokenAccounts.value[0].pubkey;
                } else {
                    // Would need to create an account, which requires more complex transaction
                    alert('Recipient does not have a TOLA token account yet.');
                    return;
                }
            } catch (error) {
                console.error('Error getting recipient token account:', error);
                alert('Failed to get recipient token account: ' + error.message);
                return;
            }
            
            // Convert amount to lamports (accounting for decimals)
            const amountLamports = Math.floor(amount * Math.pow(10, vortexTola.tokenDecimals));
            
            // Create a new transaction
            const transaction = new solanaWeb3.Transaction().add(
                splToken.Token.createTransferInstruction(
                    tokenProgramId,
                    fromTokenAccount,
                    toTokenAccount,
                    window.solana.publicKey,
                    [],
                    amountLamports
                )
            );
            
            // Set recent blockhash and fee payer
            transaction.feePayer = window.solana.publicKey;
            transaction.recentBlockhash = (await connection.getRecentBlockhash()).blockhash;
            
            // Sign and send transaction
            const signed = await window.solana.signTransaction(transaction);
            const signature = await connection.sendRawTransaction(signed.serialize());
            
            // Wait for confirmation
            await connection.confirmTransaction(signature);
            
            // Record transaction on server
            recordTransaction(fromAddress, recipient, amount, { signature: signature });
            
            $('.vortex-tola-send-result').html('<div class="success">Transaction sent successfully!</div>');
            $('#recipient_address').val('');
            $('#amount').val('');
            
            // Update balance
            setTimeout(updateTolaBalance, 2000);
            
        } catch (error) {
            console.error('Error sending TOLA tokens:', error);
            $('.vortex-tola-send-result').html('<div class="error">Failed to send tokens: ' + error.message + '</div>');
        }
    }
    
    /**
     * Record transaction on server
     */
    function recordTransaction(from, to, amount, transactionData) {
        $.ajax({
            url: vortexTola.ajaxUrl,
            type: 'POST',
            data: {
                action: 'vortex_process_tola_transaction',
                from_address: from,
                to_address: to,
                amount: amount,
                transaction_data: transactionData,
                nonce: vortexTola.nonce
            },
            success: function(response) {
                if (!response.success) {
                    console.error('Failed to record transaction:', response.data.message);
                }
            },
            error: function() {
                console.error('AJAX error recording transaction.');
            }
        });
    }
    
    /**
     * Initialize product purchase handlers
     */
    function initProductPurchaseHandlers() {
        // Product purchase form submission
        $('.vortex-product-purchase-form').on('submit', function(e) {
            e.preventDefault();
            
            const productId = $(this).find('input[name="product_id"]').val();
            const tolaPrice = $(this).find('input[name="tola_price"]').val();
            
            // Confirm purchase
            if (confirm('Are you sure you want to purchase this product for ' + tolaPrice + ' TOLA?')) {
                purchaseProduct($(this), productId, tolaPrice);
            }
        });
    }
    
    /**
     * Purchase a product with TOLA
     */
    function purchaseProduct(form, productId, tolaPrice) {
        // Show loading state
        const resultDiv = form.find('.vortex-purchase-result');
        resultDiv.html('<div class="loading">Processing your purchase...</div>');
        
        // Send purchase request
        $.ajax({
            url: vortexTola.ajaxUrl,
            type: 'POST',
            data: {
                action: 'vortex_purchase_product',
                product_id: productId,
                tola_price: tolaPrice,
                nonce: form.find('input[name="nonce"]').val()
            },
            success: function(response) {
                if (response.success) {
                    resultDiv.html('<div class="success">' + response.data.message + '</div>');
                    
                    // Redirect after successful purchase
                    if (response.data.redirect) {
                        setTimeout(function() {
                            window.location.href = response.data.redirect;
                        }, 2000);
                    }
                } else {
                    resultDiv.html('<div class="error">' + response.data.message + '</div>');
                }
            },
            error: function() {
                resultDiv.html('<div class="error">An error occurred while processing your purchase.</div>');
            }
        });
    }
    
    /**
     * Helper function to copy text to clipboard
     */
    function copyToClipboard(text) {
        const el = document.createElement('textarea');
        el.value = text;
        document.body.appendChild(el);
        el.select();
        document.execCommand('copy');
        document.body.removeChild(el);
    }

})(jQuery); 