/**
 * VORTEX Wallet Connection
 * Handles Solana wallet connections and blockchain interactions
 */

const VortexWallet = (function() {
    // Private variables
    let _isConnected = false;
    let _wallet = null;
    let _publicKey = null;
    let _provider = null;
    let _network = vortexParams.network || 'devnet'; // Default to devnet if not specified
    let _connectionConfig = {
        commitment: 'confirmed'
    };
    
    // Store listeners
    const _eventListeners = {
        'connect': [],
        'disconnect': [],
        'accountChange': [],
        'error': []
    };
    
    /**
     * Initialize wallet functionality
     */
    function init() {
        // Check if Solana is available in window object
        if (!window.solana) {
            console.log('Solana wallet adapter not found. Please install Phantom, Solflare, or another Solana wallet.');
            return;
        }
        
        // Check if auto-connect is enabled
        if (vortexParams.autoConnect) {
            connect();
        }
        
        // Add event listeners for wallet connection/disconnection
        window.addEventListener('load', setupWalletButtons);
    }
    
    /**
     * Setup wallet connection buttons
     */
    function setupWalletButtons() {
        // Connect wallet buttons
        const connectButtons = document.querySelectorAll('.vortex-connect-wallet-btn');
        connectButtons.forEach(button => {
            button.addEventListener('click', async (e) => {
                e.preventDefault();
                
                try {
                    await connect();
                } catch (error) {
                    console.error('Error connecting wallet:', error);
                    _triggerEvent('error', { message: 'Failed to connect wallet', error });
                }
            });
        });
        
        // Disconnect wallet buttons
        const disconnectButtons = document.querySelectorAll('.vortex-disconnect-wallet-btn');
        disconnectButtons.forEach(button => {
            button.addEventListener('click', async (e) => {
                e.preventDefault();
                
                try {
                    await disconnect();
                } catch (error) {
                    console.error('Error disconnecting wallet:', error);
                    _triggerEvent('error', { message: 'Failed to disconnect wallet', error });
                }
            });
        });
    }
    
    /**
     * Connect to Solana wallet
     */
    async function connect() {
        try {
            // Get wallet provider
            _provider = getProvider();
            
            if (!_provider) {
                const errorMsg = 'No wallet provider found';
                _triggerEvent('error', { message: errorMsg });
                throw new Error(errorMsg);
            }
            
            // Request wallet connection
            await _provider.connect();
            _publicKey = _provider.publicKey.toString();
            _isConnected = true;
            _wallet = _provider;
            
            // Trigger connect event
            _triggerEvent('connect', { publicKey: _publicKey });
            
            // Update UI
            updateWalletUI();
            
            // Save wallet to database when connected
            saveWalletAddress(_publicKey);
            
            // Add account change listener
            _provider.on('accountChanged', onAccountChange);
            
            return {
                success: true,
                publicKey: _publicKey
            };
        } catch (error) {
            console.error('Failed to connect wallet:', error);
            _triggerEvent('error', { message: 'Failed to connect wallet', error });
            return {
                success: false,
                error: error.message
            };
        }
    }
    
    /**
     * Disconnect from Solana wallet
     */
    async function disconnect() {
        try {
            if (_provider && _isConnected) {
                await _provider.disconnect();
                _isConnected = false;
                _publicKey = null;
                _wallet = null;
                
                // Trigger disconnect event
                _triggerEvent('disconnect');
                
                // Update UI
                updateWalletUI();
                
                return {
                    success: true
                };
            }
        } catch (error) {
            console.error('Failed to disconnect wallet:', error);
            _triggerEvent('error', { message: 'Failed to disconnect wallet', error });
            return {
                success: false,
                error: error.message
            };
        }
    }
    
    /**
     * Get wallet provider (Phantom, Solflare, etc)
     */
    function getProvider() {
        if (window.solana) {
            return window.solana;
        } else if (window.solflare) {
            return window.solflare;
        }
        
        return null;
    }
    
    /**
     * Account change handler
     */
    function onAccountChange(newPublicKey) {
        if (newPublicKey) {
            _publicKey = newPublicKey.toString();
            _triggerEvent('accountChange', { publicKey: _publicKey });
            
            // Update UI
            updateWalletUI();
            
            // Save new wallet to database
            saveWalletAddress(_publicKey);
        } else {
            // Handle case where user disconnected through wallet
            _isConnected = false;
            _publicKey = null;
            _wallet = null;
            
            _triggerEvent('disconnect');
            updateWalletUI();
        }
    }
    
    /**
     * Update wallet UI elements
     */
    function updateWalletUI() {
        // Update connect/disconnect buttons visibility
        const connectButtons = document.querySelectorAll('.vortex-connect-wallet-btn');
        const disconnectButtons = document.querySelectorAll('.vortex-disconnect-wallet-btn');
        const walletAddressElements = document.querySelectorAll('.vortex-wallet-address');
        const walletsNeededNotice = document.querySelectorAll('.vortex-wallet-needed-notice');
        const walletConnectedElements = document.querySelectorAll('.vortex-wallet-connected');
        
        if (_isConnected) {
            // Hide connect buttons, show disconnect buttons
            connectButtons.forEach(btn => btn.style.display = 'none');
            disconnectButtons.forEach(btn => btn.style.display = 'inline-block');
            
            // Display wallet address
            walletAddressElements.forEach(el => {
                el.textContent = formatWalletAddress(_publicKey);
                el.style.display = 'inline-block';
            });
            
            // Hide wallet needed notices
            walletsNeededNotice.forEach(notice => notice.style.display = 'none');
            
            // Show wallet connected elements
            walletConnectedElements.forEach(el => el.style.display = 'block');
        } else {
            // Show connect buttons, hide disconnect buttons
            connectButtons.forEach(btn => btn.style.display = 'inline-block');
            disconnectButtons.forEach(btn => btn.style.display = 'none');
            
            // Hide wallet address
            walletAddressElements.forEach(el => el.style.display = 'none');
            
            // Show wallet needed notices
            walletsNeededNotice.forEach(notice => notice.style.display = 'block');
            
            // Hide wallet connected elements
            walletConnectedElements.forEach(el => el.style.display = 'none');
        }
    }
    
    /**
     * Format wallet address for display (truncate middle)
     */
    function formatWalletAddress(address) {
        if (!address) return '';
        
        return address.slice(0, 6) + '...' + address.slice(-4);
    }
    
    /**
     * Save wallet address to WordPress database via AJAX
     */
    function saveWalletAddress(walletAddress) {
        if (!walletAddress || !vortexParams.isLoggedIn) return;
        
        // Call AJAX to save wallet address
        jQuery.ajax({
            url: vortexParams.ajaxUrl,
            type: 'POST',
            data: {
                action: 'vortex_add_wallet',
                nonce: vortexParams.nonce,
                address: walletAddress,
                type: 'solana'
            },
            success: function(response) {
                if (response.success) {
                    console.log('Wallet address saved successfully');
                    
                    // Try to verify wallet ownership
                    verifyWalletOwnership(response.data.wallet_id);
                } else {
                    console.error('Failed to save wallet address:', response.data);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', error);
            }
        });
    }
    
    /**
     * Verify wallet ownership with signature
     */
    async function verifyWalletOwnership(walletId) {
        if (!_wallet || !_isConnected) return;
        
        try {
            // Message to sign
            const message = `Verify ownership of wallet for VORTEX DAO (ID: ${walletId})`;
            
            // Convert to Uint8Array
            const messageBytes = new TextEncoder().encode(message);
            
            // Request signature
            const signatureBytes = await _wallet.signMessage(messageBytes, 'utf8');
            
            // Convert signature to base64
            const signature = btoa(String.fromCharCode.apply(null, signatureBytes));
            
            // Send to server
            jQuery.ajax({
                url: vortexParams.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'vortex_verify_wallet',
                    nonce: vortexParams.nonce,
                    wallet_id: walletId,
                    signature: signature
                },
                success: function(response) {
                    if (response.success) {
                        console.log('Wallet verified successfully');
                    } else {
                        console.error('Wallet verification failed:', response.data);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error:', error);
                }
            });
        } catch (error) {
            console.error('Failed to sign message:', error);
        }
    }
    
    /**
     * Sign and send transaction
     */
    async function signAndSendTransaction(transaction) {
        if (!_wallet || !_isConnected) {
            throw new Error('Wallet not connected');
        }
        
        try {
            // Sign transaction
            const signedTransaction = await _wallet.signTransaction(transaction);
            
            // Create connection to Solana network
            const connection = new solanaWeb3.Connection(
                solanaWeb3.clusterApiUrl(_network),
                _connectionConfig
            );
            
            // Send signed transaction
            const signature = await connection.sendRawTransaction(
                signedTransaction.serialize()
            );
            
            // Confirm transaction
            await connection.confirmTransaction(signature, 'confirmed');
            
            return {
                success: true,
                signature: signature
            };
        } catch (error) {
            console.error('Transaction failed:', error);
            _triggerEvent('error', { message: 'Transaction failed', error });
            
            return {
                success: false,
                error: error.message
            };
        }
    }
    
    /**
     * Add event listener
     */
    function addEventListener(eventName, callback) {
        if (_eventListeners[eventName]) {
            _eventListeners[eventName].push(callback);
        }
    }
    
    /**
     * Remove event listener
     */
    function removeEventListener(eventName, callback) {
        if (_eventListeners[eventName]) {
            _eventListeners[eventName] = _eventListeners[eventName].filter(
                listener => listener !== callback
            );
        }
    }
    
    /**
     * Trigger event
     */
    function _triggerEvent(eventName, data = {}) {
        if (_eventListeners[eventName]) {
            _eventListeners[eventName].forEach(callback => callback(data));
        }
    }
    
    // Public API
    return {
        init: init,
        connect: connect,
        disconnect: disconnect,
        isConnected: () => _isConnected,
        getPublicKey: () => _publicKey,
        signAndSendTransaction: signAndSendTransaction,
        formatWalletAddress: formatWalletAddress,
        addEventListener: addEventListener,
        removeEventListener: removeEventListener
    };
})();

// Initialize on document ready
jQuery(document).ready(function() {
    VortexWallet.init();
}); 