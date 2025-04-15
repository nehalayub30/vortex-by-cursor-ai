// Sample code for Solana wallet integration
class VortexSolanaWallet {
    constructor() {
        this.provider = null;
        this.publicKey = null;
    }
    
    async connect() {
        try {
            // Check for Phantom, Solflare or other Solana wallets
            if (!window.solana) {
                throw new Error('Solana wallet not found! Please install Phantom or Solflare.');
            }
            
            // Connect to wallet
            this.provider = window.solana;
            await this.provider.connect();
            this.publicKey = this.provider.publicKey.toString();
            
            return {
                address: this.publicKey,
                connected: true
            };
        } catch (error) {
            console.error('Error connecting to Solana wallet:', error);
            throw error;
        }
    }
    
    async signMessage(message) {
        if (!this.provider || !this.publicKey) {
            throw new Error('Wallet not connected');
        }
        
        // Convert string to Uint8Array for Solana signing
        const messageBytes = new TextEncoder().encode(message);
        
        // Sign the message with the wallet
        const signedMessage = await this.provider.signMessage(messageBytes, 'utf8');
        
        return {
            signature: Buffer.from(signedMessage.signature).toString('hex'),
            publicKey: this.publicKey
        };
    }
    
    async vote(proposalId, voteChoice) {
        try {
            // Step 1: Prepare the vote
            this.showNotification('info', 'Preparing your vote...');
            
            const prepareResponse = await fetch(vortex_ajax.ajax_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    'action': 'vortex_prepare_vote',
                    'nonce': vortex_ajax.vote_nonce,
                    'proposal_id': proposalId,
                    'vote_choice': voteChoice,
                    'address': this.publicKey
                })
            });
            
            const prepareData = await prepareResponse.json();
            
            if (!prepareData.success) {
                this.showNotification('error', prepareData.data.message);
                return;
            }
            
            // Step 2: Sign the voting message
            this.showNotification('info', 'Please sign the message in your wallet...');
            
            const { signature } = await this.signMessage(prepareData.data.message);
            
            // Step 3: Submit the vote with signature
            this.showNotification('info', 'Submitting your vote...');
            
            const voteResponse = await fetch(vortex_ajax.ajax_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    'action': 'vortex_submit_vote',
                    'nonce': vortex_ajax.vote_nonce,
                    'proposal_id': proposalId,
                    'vote_choice': voteChoice,
                    'address': this.publicKey,
                    'signature': signature
                })
            });
            
            const voteData = await voteResponse.json();
            
            if (voteData.success) {
                this.showNotification('success', 'Vote submitted successfully!');
                
                // Refresh the page after a short delay
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            } else {
                this.showNotification('error', voteData.data.message);
            }
        } catch (error) {
            console.error('Error voting:', error);
            this.showNotification('error', `Error submitting vote: ${error.message}`);
        }
    }
    
    /**
     * Save wallet address on the server
     */
    async saveWalletAddressOnServer() {
        if (!this.connected || !this.publicKey) {
            return;
        }
        
        try {
            const response = await fetch(vortex_ajax.ajax_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    'action': 'vortex_connect_wallet',
                    'nonce': vortex_ajax.wallet_nonce,
                    'address': this.publicKey
                })
            });
            
            const data = await response.json();
            
            if (!data.success && this.debug) {
                console.error('Error saving wallet address:', data.data.message);
            }
        } catch (error) {
            if (this.debug) {
                console.error('Error saving wallet address:', error);
            }
        }
    }
    
    /**
     * Format a number for display
     * 
     * @param {number} number - Number to format
     * @returns {string} - Formatted number
     */
    formatNumber(number) {
        // Handle null or undefined
        if (number === null || number === undefined) {
            return '0';
        }
        
        // Convert to number if it's a string
        const num = typeof number === 'string' ? parseFloat(number) : number;
        
        // Format with commas for thousands and fixed decimal places
        return num.toLocaleString(undefined, {
            minimumFractionDigits: 2,
            maximumFractionDigits: 4
        });
    }
    
    /**
     * Show a notification to the user
     * 
     * @param {string} type - Notification type (success, error, info, warning)
     * @param {string} message - Notification message
     */
    showNotification(type, message) {
        // Check if notification container exists, if not create it
        let container = document.getElementById('vortex-notifications');
        
        if (!container) {
            container = document.createElement('div');
            container.id = 'vortex-notifications';
            container.style.position = 'fixed';
            container.style.top = '20px';
            container.style.right = '20px';
            container.style.zIndex = '9999';
            document.body.appendChild(container);
        }
        
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `vortex-notification ${type}`;
        notification.innerHTML = `
            <div class="notification-icon">${this.getNotificationIcon(type)}</div>
            <div class="notification-content">${message}</div>
            <div class="notification-close">&times;</div>
        `;
        
        // Style the notification
        notification.style.backgroundColor = this.getNotificationColor(type);
        notification.style.color = '#fff';
        notification.style.padding = '12px 16px';
        notification.style.borderRadius = '4px';
        notification.style.marginBottom = '10px';
        notification.style.boxShadow = '0 2px 5px rgba(0,0,0,0.2)';
        notification.style.display = 'flex';
        notification.style.alignItems = 'center';
        notification.style.minWidth = '300px';
        notification.style.maxWidth = '450px';
        notification.style.fontSize = '14px';
        
        // Style the close button
        const closeButton = notification.querySelector('.notification-close');
        closeButton.style.marginLeft = 'auto';
        closeButton.style.cursor = 'pointer';
        closeButton.style.fontWeight = 'bold';
        closeButton.style.fontSize = '18px';
        
        // Add close functionality
        closeButton.addEventListener('click', () => {
            container.removeChild(notification);
        });
        
        // Add notification to container
        container.appendChild(notification);
        
        // Auto-remove after a delay
        setTimeout(() => {
            if (container.contains(notification)) {
                container.removeChild(notification);
            }
        }, 5000);
    }
    
    /**
     * Get color for notification type
     * 
     * @param {string} type - Notification type
     * @returns {string} - Color for notification
     */
    getNotificationColor(type) {
        switch (type) {
            case 'success':
                return '#4CAF50';
            case 'error':
                return '#F44336';
            case 'warning':
                return '#FF9800';
            case 'info':
            default:
                return '#2196F3';
        }
    }
    
    /**
     * Get icon for notification type
     * 
     * @param {string} type - Notification type
     * @returns {string} - Icon HTML
     */
    getNotificationIcon(type) {
        switch (type) {
            case 'success':
                return '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>';
            case 'error':
                return '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>';
            case 'warning':
                return '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>';
            case 'info':
            default:
                return '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>';
        }
    }
}

// Initialize the wallet handler when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.vortexSolanaWallet = new VortexSolanaWallet();
    
    // Add Solana wallet styles to the page
    const style = document.createElement('style');
    style.textContent = `
        .vortex-wallet-address {
            display: inline-flex;
            align-items: center;
            background: #f1f1f1;
            padding: 8px 12px;
            border-radius: 20px;
            font-family: monospace;
            font-size: 14px;
            color: #333;
        }
        
        .vortex-wallet-address.connected {
            background: #E6F7FF;
            color: #0070E0;
        }
        
        .vortex-wallet-address:before {
            content: '';
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background-color: #ccc;
            margin-right: 8px;
        }
        
        .vortex-wallet-address.connected:before {
            background-color: #00C853;
        }
        
        .vortex-connect-wallet, .vortex-disconnect-wallet, .vortex-wallet-action {
            background-color: #3f51b5;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 10px 16px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.2s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        
        .vortex-connect-wallet:hover, .vortex-disconnect-wallet:hover, .vortex-wallet-action:hover {
            background-color: #303f9f;
        }
        
        .vortex-disconnect-wallet {
            background-color: #f44336;
        }
        
        .vortex-disconnect-wallet:hover {
            background-color: #d32f2f;
        }
        
        .vortex-token-balance {
            font-size: 24px;
            font-weight: 700;
            color: #333;
        }
        
        .vortex-token-balance .loading {
            font-size: 16px;
            color: #777;
        }
        
        .vortex-token-balance .error {
            font-size: 16px;
            color: #f44336;
        }
        
        #vortex-notifications {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
        }
        
        .vortex-notification {
            animation: slideIn 0.3s ease-out;
        }
        
        .vortex-notification .notification-icon {
            margin-right: 12px;
        }
        
        .vortex-notification .notification-content {
            flex-grow: 1;
        }
        
        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
    `;
    document.head.appendChild(style);
}); 