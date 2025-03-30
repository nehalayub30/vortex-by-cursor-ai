/**
 * VORTEX AI AGENTS Blockchain Admin Scripts
 *
 * Handles wallet connection, NFT minting, and UI interactions
 */

(function($) {
    'use strict';

    // Store Web3 instance
    let web3Instance = null;

    // Store Solana instance
    let solanaConnection = null;
    let connected = false;
    let publicKey = null;

    // Initialize the blockchain admin interface
    function initBlockchainAdmin() {
        setupTabNavigation();
        setupWalletConnection();
        setupNftMinting();
        setupCollaboratorFields();
        setupRoyaltyCalculation();
    }

    // Set up tab navigation
    function setupTabNavigation() {
        $('.vortex-admin-tabs-nav a').on('click', function(e) {
            e.preventDefault();
            
            // Remove active class from all tabs and contents
            $('.vortex-admin-tabs-nav a').removeClass('active');
            $('.vortex-admin-tab-content').removeClass('active');
            
            // Add active class to clicked tab and corresponding content
            $(this).addClass('active');
            $($(this).attr('href')).addClass('active');
        });
    }

    // Set up wallet connection
    function setupWalletConnection() {
        // Connect wallet button
        $('#vortex-connect-wallet').on('click', async function() {
            try {
                // Check if MetaMask is installed
                if (typeof window.ethereum === 'undefined') {
                    alert('Please install MetaMask to connect your wallet.');
                    return;
                }
                
                // Initialize Web3
                web3Instance = new Web3(window.ethereum);
                
                // Request account access
                const accounts = await window.ethereum.request({ method: 'eth_requestAccounts' });
                const walletAddress = accounts[0];
                
                if (!walletAddress) {
                    alert('No wallet address found. Please unlock MetaMask and try again.');
                    return;
                }
                
                // Save the wallet address via AJAX
                $.ajax({
                    url: vortexBlockchain.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'vortex_connect_wallet',
                        nonce: vortexBlockchain.nonce,
                        wallet_address: walletAddress
                    },
                    success: function(response) {
                        if (response.success) {
                            // Reload the page to show the connected wallet
                            location.reload();
                        } else {
                            alert(response.data.message || 'Failed to connect wallet.');
                        }
                    },
                    error: function() {
                        alert('An error occurred while connecting your wallet.');
                    }
                });
            } catch (error) {
                console.error('Wallet connection error:', error);
                alert('Failed to connect wallet: ' + error.message);
            }
        });
        
        // Disconnect wallet button
        $('#vortex-disconnect-wallet').on('click', function() {
            if (confirm('Are you sure you want to disconnect your wallet?')) {
                // Remove the wallet address from user meta via AJAX
                $.ajax({
                    url: vortexBlockchain.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'vortex_disconnect_wallet',
                        nonce: vortexBlockchain.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            // Reload the page to show the wallet disconnected
                            location.reload();
                        } else {
                            alert(response.data.message || 'Failed to disconnect wallet.');
                        }
                    },
                    error: function() {
                        alert('An error occurred while disconnecting your wallet.');
                    }
                });
            }
        });
    }

    // Set up NFT minting
    function setupNftMinting() {
        // Select artwork button (opens media library)
        $('#vortex-select-artwork').on('click', function(e) {
            e.preventDefault();
            
            const mediaUploader = wp.media({
                title: 'Select Artwork',
                button: {
                    text: 'Use this artwork'
                },
                multiple: false
            });
            
            mediaUploader.on('select', function() {
                const attachment = mediaUploader.state().get('selection').first().toJSON();
                $('#nft-image').val(attachment.url);
            });
            
            mediaUploader.open();
        });
        
        // NFT minting form submission
        $('#vortex-mint-form').on('submit', function(e) {
            e.preventDefault();
            
            const title = $('#nft-title').val();
            const description = $('#nft-description').val();
            const image = $('#nft-image').val();
            const royalty = $('#nft-royalty').val();
            
            if (!title || !image) {
                alert('Please fill in all required fields.');
                return;
            }
            
            // Collect collaborator information
            const collaborators = [];
            $('.collaborator-row').each(function() {
                const walletAddress = $(this).find('.collaborator-wallet').val();
                const percentage = $(this).find('.collaborator-percentage').val();
                
                if (walletAddress && percentage) {
                    collaborators.push({
                        wallet: walletAddress,
                        percentage: parseFloat(percentage)
                    });
                }
            });
            
            // Disable the submit button
            const submitButton = $(this).find('input[type="submit"]');
            submitButton.prop('disabled', true).val('Minting...');
            
            // Mint NFT via AJAX
            $.ajax({
                url: vortexBlockchain.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'vortex_mint_nft',
                    nonce: vortexBlockchain.nonce,
                    title: title,
                    description: description,
                    image: image,
                    royalty: royalty,
                    collaborators: JSON.stringify(collaborators)
                },
                success: function(response) {
                    submitButton.prop('disabled', false).val('Mint NFT');
                    
                    if (response.success) {
                        alert('NFT minted successfully! Token ID: ' + response.data.token_id);
                        // Reload the page to show the new NFT
                        location.reload();
                    } else {
                        alert(response.data.message || 'Failed to mint NFT.');
                    }
                },
                error: function() {
                    submitButton.prop('disabled', false).val('Mint NFT');
                    alert('An error occurred while minting the NFT.');
                }
            });
        });
        
        // View NFT details
        $('.view-nft').on('click', function(e) {
            e.preventDefault();
            
            const nftId = $(this).data('id');
            
            // Get NFT details via AJAX
            $.ajax({
                url: vortexBlockchain.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'vortex_get_nft_details',
                    nonce: vortexBlockchain.nonce,
                    nft_id: nftId
                },
                success: function(response) {
                    if (response.success) {
                        showNftDetailsModal(response.data.nft);
                    } else {
                        alert(response.data.message || 'Failed to get NFT details.');
                    }
                },
                error: function() {
                    alert('An error occurred while getting NFT details.');
                }
            });
        });
    }

    // Set up collaborator fields
    function setupCollaboratorFields() {
        // Add collaborator button
        $('#vortex-add-collaborator').on('click', function() {
            const collaboratorsContainer = $('#vortex-collaborators');
            const collaboratorIndex = $('.collaborator-row').length;
            
            const collaboratorHtml = `
                <div class="collaborator-row">
                    <div class="collaborator-fields">
                        <input type="text" class="regular-text collaborator-wallet" placeholder="Wallet Address (0x...)" />
                        <input type="number" class="small-text collaborator-percentage" min="0.1" max="97" step="0.1" value="5" /> %
                        <button type="button" class="button button-small remove-collaborator">Remove</button>
                    </div>
                </div>
            `;
            
            collaboratorsContainer.append(collaboratorHtml);
            updateRoyaltySummary();
        });
        
        // Remove collaborator button (delegated event for dynamically added elements)
        $(document).on('click', '.remove-collaborator', function() {
            $(this).closest('.collaborator-row').remove();
            updateRoyaltySummary();
        });
        
        // Update royalty when collaborator percentage changes
        $(document).on('change', '.collaborator-percentage', function() {
            updateRoyaltySummary();
        });
    }

    // Set up royalty calculation
    function setupRoyaltyCalculation() {
        // Update royalty summary when creator royalty changes
        $('#nft-royalty').on('change', function() {
            updateRoyaltySummary();
        });
    }

    // Update royalty summary
    function updateRoyaltySummary() {
        const platformRoyalty = 3; // Fixed 3% platform royalty
        let creatorRoyalty = parseFloat($('#nft-royalty').val()) || 0;
        let collaboratorTotal = 0;
        
        // Sum up collaborator percentages
        $('.collaborator-percentage').each(function() {
            collaboratorTotal += parseFloat($(this).val()) || 0;
        });
        
        const totalRoyalty = platformRoyalty + creatorRoyalty + collaboratorTotal;
        
        // Update the summary display
        $('.creator-royalty').text(creatorRoyalty.toFixed(1) + '% Creator (You)');
        
        // Add collaborator text if there are collaborators
        let collaboratorText = '';
        if (collaboratorTotal > 0) {
            collaboratorText = `<span class="collaborator-royalty">${collaboratorTotal.toFixed(1)}% Collaborators</span>`;
            // Insert after creator royalty
            if ($('.collaborator-royalty').length === 0) {
                $('.creator-royalty').after(collaboratorText);
            } else {
                $('.collaborator-royalty').text(collaboratorTotal.toFixed(1) + '% Collaborators');
            }
        } else {
            $('.collaborator-royalty').remove();
        }
        
        $('.total-royalty').text(totalRoyalty.toFixed(1) + '% Total');
        
        // Validate total royalty doesn't exceed 100%
        if (totalRoyalty > 100) {
            $('.royalty-summary').addClass('royalty-error');
            $('.total-royalty').append(' <span class="error-text">(Exceeds maximum 100%)</span>');
        } else {
            $('.royalty-summary').removeClass('royalty-error');
        }
    }

    // Show NFT details modal
    function showNftDetailsModal(nft) {
        // Create modal HTML
        const modalHtml = `
            <div class="vortex-modal-overlay">
                <div class="vortex-modal">
                    <div class="vortex-modal-header">
                        <h2>${nft.title}</h2>
                        <button type="button" class="vortex-modal-close">&times;</button>
                    </div>
                    <div class="vortex-modal-body">
                        <div class="vortex-nft-details">
                            <div class="vortex-nft-image">
                                <img src="${nft.image}" alt="${nft.title}" />
                            </div>
                            <div class="vortex-nft-info">
                                <p><strong>Token ID:</strong> <code>${nft.token_id}</code></p>
                                <p><strong>Description:</strong> ${nft.description}</p>
                                <p><strong>Network:</strong> ${nft.network}</p>
                                <p><strong>Creator:</strong> <code>${nft.creator_wallet}</code></p>
                                
                                <h3>Royalties</h3>
                                <ul class="vortex-royalty-list">
                                    ${nft.royalties.map(royalty => `
                                        <li>
                                            <code>${royalty.wallet}</code>: ${royalty.percentage}%
                                            ${royalty.wallet === vortexBlockchain.platformWallet ? ' (Platform)' : ''}
                                            ${royalty.wallet === nft.creator_wallet ? ' (Creator)' : ''}
                                        </li>
                                    `).join('')}
                                </ul>
                                
                                <div class="vortex-nft-actions">
                                    <a href="#" class="button view-on-blockchain" data-token-id="${nft.token_id}">View on Blockchain</a>
                                    <a href="${nft.image}" class="button" download>Download Artwork</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Append to body and setup event handlers
        $('body').append(modalHtml);
        
        // Close modal when clicking the close button or outside the modal
        $('.vortex-modal-close, .vortex-modal-overlay').on('click', function(e) {
            if (e.target === this) {
                $('.vortex-modal-overlay').remove();
            }
        });
        
        // View on blockchain button
        $('.view-on-blockchain').on('click', function(e) {
            e.preventDefault();
            
            const tokenId = $(this).data('token-id');
            const network = vortexBlockchain.network;
            
            // Determine explorer URL based on network
            let explorerUrl = '';
            switch (network) {
                case 'ethereum':
                    explorerUrl = `https://etherscan.io/token/${vortexBlockchain.contractAddress}?a=${tokenId}`;
                    break;
                case 'polygon':
                    explorerUrl = `https://polygonscan.com/token/${vortexBlockchain.contractAddress}?a=${tokenId}`;
                    break;
                case 'rinkeby':
                    explorerUrl = `https://rinkeby.etherscan.io/token/${vortexBlockchain.contractAddress}?a=${tokenId}`;
                    break;
                default:
                    explorerUrl = '#';
                    break;
            }
            
            if (explorerUrl !== '#') {
                window.open(explorerUrl, '_blank');
            } else {
                alert('Blockchain explorer not available for this network.');
            }
        });
    }

    // Initialize on document ready
    $(document).ready(function() {
        initBlockchainAdmin();
    });

})(jQuery); 