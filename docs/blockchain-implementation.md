# VORTEX AI AGENTS Blockchain Implementation Guide

This guide provides detailed instructions for implementing, configuring, and using the blockchain features of the VORTEX AI AGENTS plugin, particularly focusing on the NFT creation and royalty management system.

## Table of Contents

1. [Overview](#overview)
2. [Prerequisites](#prerequisites)
3. [Installation and Setup](#installation-and-setup)
4. [Smart Contract Deployment](#smart-contract-deployment)
5. [WordPress Plugin Configuration](#wordpress-plugin-configuration)
6. [Creating and Minting NFTs](#creating-and-minting-nfts)
7. [Managing Royalties](#managing-royalties)
8. [Developer Resources](#developer-resources)
9. [Troubleshooting](#troubleshooting)

## Overview

The VORTEX AI AGENTS plugin includes a blockchain component that enables artists to:

- Mint NFTs from AI-generated artwork
- Establish royalty structures with a fixed 3% platform fee and customizable creator royalties
- Support collaborative artwork with split royalties
- Track ownership and sales of digital art on the blockchain
- Receive royalty payments automatically when NFTs are sold

The implementation leverages the ERC-721 standard for NFTs and the ERC-2981 standard for royalty management, providing a seamless integration between WordPress and blockchain networks.

## Prerequisites

Before implementing the blockchain features, ensure you have:

- WordPress 5.8 or higher
- PHP 7.4 or higher
- VORTEX AI AGENTS plugin installed and activated
- Ethereum-compatible wallet (MetaMask recommended)
- Basic understanding of blockchain concepts and Ethereum
- Access to blockchain networks (Ethereum, Polygon, or test networks)
- Small amount of cryptocurrency for gas fees (ETH, MATIC, etc.)

## Installation and Setup

### Step 1: Install Required Dependencies

The plugin requires Web3.js for blockchain interactions. These dependencies are automatically included when the plugin is installed, but you can manually ensure they're available:

```bash
# From your WordPress root directory
cd wp-content/plugins/vortex-ai-agents/
npm install web3 @metamask/detect-provider
```

### Step 2: Configure WordPress Environment

Add the following constants to your `wp-config.php` file to enable blockchain features:

```php
// Enable blockchain features
define('VORTEX_ENABLE_BLOCKCHAIN', true);

// Optional: Set default network (ethereum, polygon, rinkeby)
define('VORTEX_DEFAULT_BLOCKCHAIN_NETWORK', 'polygon');

// Optional: Set Infura API key for Ethereum networks
define('VORTEX_INFURA_API_KEY', 'your_infura_api_key');
```

## Smart Contract Deployment

The plugin includes a ready-to-deploy smart contract (`VortexRoyaltyNFT.sol`) with royalty management capabilities. You'll need to deploy this contract to your chosen blockchain network.

### Option 1: Deploy via Remix IDE (Recommended for beginners)

1. Visit [Remix IDE](https://remix.ethereum.org/)
2. Create a new file named `VortexRoyaltyNFT.sol` and paste the contract code from the plugin's `includes/ai/blockchain/VortexRoyaltyNFT.sol` file
3. Compile the contract using Solidity Compiler 0.8.17 or higher
4. Deploy the contract with the following parameters:
   - `name`: "VORTEX AI AGENTS NFT"
   - `symbol`: "VORTEX"
   - `platformWallet`: Your platform wallet address (to receive the 3% royalty)
5. Save the deployed contract address for later configuration

### Option 2: Deploy via Hardhat (For developers)

If you prefer using Hardhat for deployment:

1. Initialize a new Hardhat project
2. Copy the `VortexRoyaltyNFT.sol` file to your contracts directory
3. Create a deployment script that initializes the contract with your platform wallet address
4. Deploy to your chosen network using Hardhat's deployment commands
5. Save the deployed contract address

## WordPress Plugin Configuration

Once the contract is deployed, configure the plugin to interact with it:

### Step 1: Access Blockchain Settings

1. Log in to your WordPress admin dashboard
2. Navigate to VORTEX AI AGENTS > Blockchain Settings

### Step 2: Configure Network Connection

1. Select your blockchain network (Ethereum, Polygon, or test networks)
2. Enter your Infura API key if using Ethereum or Rinkeby
3. Click "Save Changes"

### Step 3: Connect Smart Contract

1. Enter the deployed contract address
2. The ABI will be automatically detected, but you can verify it matches your deployment
3. Click "Save Changes"

### Step 4: Set Platform Wallet

1. Enter the platform wallet address that will receive the 3% platform royalty
2. This should match the address used during contract deployment
3. Click "Save Changes"

## Creating and Minting NFTs

After configuration, artists can create and mint NFTs from their AI-generated artwork:

### Step 1: Generate Artwork

1. Use the HURAII agent to generate digital artwork
2. Save or download the generated image

### Step 2: Prepare for Minting

1. Navigate to VORTEX AI AGENTS > Create NFT
2. Upload the artwork or select from previously generated images
3. Enter the NFT details:
   - Title
   - Description
   - Creator royalty percentage (on top of the fixed 3% platform royalty)

### Step 3: Set Up Royalty Structure (Optional)

For collaborative artwork:

1. Click "Add Collaborator"
2. Enter collaborator wallet address and royalty percentage
3. Add multiple collaborators as needed
4. The system will display the total royalty breakdown including:
   - 3% Platform royalty (fixed)
   - Creator royalty
   - Collaborator royalties
   - Total royalty percentage (cannot exceed 100%)

### Step 4: Connect Wallet

1. Click "Connect Wallet"
2. Approve the MetaMask connection
3. Ensure you're connected to the correct network (matching your plugin configuration)

### Step 5: Mint NFT

1. Review all details
2. Click "Mint NFT"
3. Approve the transaction in MetaMask
4. Wait for transaction confirmation
5. View your minted NFT in the "My NFTs" section

## Managing Royalties

Royalties are automatically managed by the smart contract:

### For Creators:

1. When your NFT is sold on compatible marketplaces, the royalty is automatically calculated
2. The platform receives its fixed 3% royalty
3. You and any collaborators receive your specified percentages
4. Funds are sent directly to the registered wallet addresses

### For Platform Administrators:

1. Platform royalties are sent to the configured platform wallet
2. No manual distribution is required
3. View royalty statistics in the VORTEX AI AGENTS dashboard

## Developer Resources

For developers looking to extend or customize the blockchain integration:

### Key Files:

- `includes/admin/class-vortex-blockchain-admin.php` - Admin interface
- `includes/blockchain/class-vortex-blockchain-integration.php` - WordPress-blockchain bridge
- `includes/ai/blockchain/VortexRoyaltyNFT.sol` - Smart contract code
- `includes/blockchain/contract-abi.json` - Contract ABI

### Integration Points:

- **Filters**:
  - `vortex_nft_metadata` - Modify NFT metadata before minting
  - `vortex_royalty_calculation` - Customize royalty calculations
  
- **Actions**:
  - `vortex_before_nft_mint` - Execute code before NFT minting
  - `vortex_after_nft_mint` - Execute code after successful minting
  - `vortex_nft_mint_failed` - Handle minting failures

### API Endpoints:

The plugin exposes the following REST API endpoints:

- `POST /wp-json/vortex-ai-agents/v1/mint-nft` - Mint a new NFT
- `GET /wp-json/vortex-ai-agents/v1/user-nfts` - Get NFTs owned by current user
- `GET /wp-json/vortex-ai-agents/v1/nft/{id}` - Get specific NFT details

## Troubleshooting

### Common Issues and Solutions:

#### Transaction Failed

**Problem**: NFT minting transaction fails.

**Solutions**:
- Ensure you have enough funds for gas fees
- Check network connection and try again
- Verify contract address is correct
- Try lowering gas price if network is congested

#### Wallet Connection Issues

**Problem**: Cannot connect MetaMask wallet.

**Solutions**:
- Ensure MetaMask is installed and unlocked
- Check if browser permissions are granted
- Try refreshing the page
- Clear browser cache and try again

#### Royalties Not Received

**Problem**: Royalties not received after NFT sale.

**Solutions**:
- Verify the marketplace supports ERC-2981 royalty standard
- Check transaction on blockchain explorer
- Verify wallet addresses are correct
- Contact marketplace support

#### Network Mismatch

**Problem**: "Network mismatch" error when connecting wallet.

**Solutions**:
- Switch to the network configured in the plugin settings
- Update plugin settings to match your preferred network
- Restart browser and try again

For additional support, please refer to our [GitHub repository](https://github.com/vortex-ai-agents/blockchain) or contact our support team. 