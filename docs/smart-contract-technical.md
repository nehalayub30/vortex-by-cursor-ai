# VORTEX AI AGENTS Smart Contract Technical Documentation

## Overview

The VORTEX AI AGENTS platform utilizes a custom Ethereum-compatible smart contract to handle NFT creation and royalty management for AI-generated artwork. This document provides technical details about the smart contract architecture, implementation decisions, security considerations, and integration with the WordPress plugin.

## Table of Contents

1. [Contract Specifications](#contract-specifications)
2. [Royalty Implementation](#royalty-implementation)
3. [Technical Architecture](#technical-architecture)
4. [Security Considerations](#security-considerations)
5. [Audit Results](#audit-results)
6. [Integration with WordPress](#integration-with-wordpress)
7. [Customization Options](#customization-options)
8. [Developer Reference](#developer-reference)

## Contract Specifications

### Core Contract: VortexRoyaltyNFT

- **File Location**: `includes/ai/blockchain/VortexRoyaltyNFT.sol`
- **Solidity Version**: 0.8.17
- **Standards Implemented**: 
  - ERC-721 (NFT Standard)
  - ERC-2981 (NFT Royalty Standard)
- **Dependencies**:
  - OpenZeppelin Contracts v4.8.0
    - ERC721URIStorage
    - ERC2981
    - Ownable
    - Counters
    - SafeMath

### Contract Parameters

- **Name**: "VORTEX AI AGENTS NFT"
- **Symbol**: "VORTEX"
- **Platform Wallet**: Address that receives the fixed 3% platform royalty
- **Platform Royalty Percentage**: Fixed at 3%
- **Maximum Total Royalty**: 100%

## Royalty Implementation

The VortexRoyaltyNFT contract implements the ERC-2981 standard for royalty handling with several unique features to support the VORTEX AI AGENTS ecosystem.

### Platform Royalty

A fixed 3% platform royalty is applied to all NFTs minted through the contract. This royalty is sent to the platform wallet specified during contract deployment and can only be changed by the contract owner.

### Multi-Party Royalty Distribution

The contract extends the standard ERC-2981 implementation to support multiple royalty recipients:

1. **Platform**: Fixed 3% royalty to the platform
2. **Creator**: Variable percentage set during minting
3. **Collaborators**: Optional additional recipients with specified percentages

### Implementation Details

```solidity
// Simplified example of royalty implementation
function mintWithRoyalties(
    address to,
    string memory tokenURI,
    address[] memory royaltyRecipients,
    uint96[] memory royaltyShares
) public returns (uint256) {
    _tokenIdCounter.increment();
    uint256 tokenId = _tokenIdCounter.current();
    
    // Mint the NFT
    _safeMint(to, tokenId);
    _setTokenURI(tokenId, tokenURI);
    
    // Set royalties
    _setTokenRoyalties(tokenId, royaltyRecipients, royaltyShares);
    
    return tokenId;
}

// Internal function to set royalties
function _setTokenRoyalties(
    uint256 tokenId,
    address[] memory royaltyRecipients,
    uint96[] memory royaltyShares
) internal {
    // Validate royalty recipients and shares
    require(royaltyRecipients.length == royaltyShares.length, "Recipients and shares length mismatch");
    
    // Calculate total royalty
    uint256 totalShares = 0;
    for (uint i = 0; i < royaltyShares.length; i++) {
        totalShares += royaltyShares[i];
    }
    
    // Ensure total royalty doesn't exceed maximum
    require(totalShares <= MAX_ROYALTY, "Total royalty exceeds maximum");
    
    // Store royalty information
    _tokenRoyaltyRecipients[tokenId] = royaltyRecipients;
    _tokenRoyaltyShares[tokenId] = royaltyShares;
    
    emit RoyaltiesSet(tokenId, royaltyRecipients, royaltyShares);
}
```

### Royalty Calculation

When an NFT is sold, the royalty amount is calculated based on the sale price and distributed proportionally to all recipients:

```solidity
function royaltyInfo(
    uint256 tokenId,
    uint256 salePrice
) public view override returns (address receiver, uint256 royaltyAmount) {
    // For ERC-2981 compliance, we return the platform wallet and total royalty amount
    uint256 totalRoyaltyAmount = 0;
    
    // Calculate platform royalty (3%)
    uint256 platformRoyalty = (salePrice * PLATFORM_ROYALTY_PERCENTAGE) / 10000;
    totalRoyaltyAmount += platformRoyalty;
    
    // Calculate creator royalties
    address[] memory recipients = _tokenRoyaltyRecipients[tokenId];
    uint96[] memory shares = _tokenRoyaltyShares[tokenId];
    
    for (uint i = 0; i < recipients.length; i++) {
        uint256 recipientRoyalty = (salePrice * shares[i]) / 10000;
        totalRoyaltyAmount += recipientRoyalty;
    }
    
    // Return platform wallet as the receiver for ERC-2981 compliance
    // The actual distribution happens in distributeSaleRoyalties
    return (platformWallet, totalRoyaltyAmount);
}
```

### Royalty Distribution

The contract includes a function to distribute royalties to all parties:

```solidity
function distributeSaleRoyalties(uint256 tokenId, uint256 saleAmount) external payable {
    // Calculate royalties
    (address receiver, uint256 totalRoyalty) = royaltyInfo(tokenId, saleAmount);
    
    // Verify sufficient funds sent
    require(msg.value >= totalRoyalty, "Insufficient funds for royalty payment");
    
    // Distribute platform royalty
    uint256 platformRoyalty = (saleAmount * PLATFORM_ROYALTY_PERCENTAGE) / 10000;
    (bool platformSuccess, ) = platformWallet.call{value: platformRoyalty}("");
    require(platformSuccess, "Platform royalty transfer failed");
    
    // Distribute creator royalties
    address[] memory recipients = _tokenRoyaltyRecipients[tokenId];
    uint96[] memory shares = _tokenRoyaltyShares[tokenId];
    
    for (uint i = 0; i < recipients.length; i++) {
        uint256 recipientRoyalty = (saleAmount * shares[i]) / 10000;
        (bool success, ) = recipients[i].call{value: recipientRoyalty}("");
        require(success, "Creator royalty transfer failed");
    }
    
    // Return any excess payment
    uint256 excess = msg.value - totalRoyalty;
    if (excess > 0) {
        (bool returnSuccess, ) = msg.sender.call{value: excess}("");
        require(returnSuccess, "Excess return failed");
    }
}
```

## Technical Architecture

### Contract Storage

The contract uses the following data structures to store royalty information:

```solidity
// Fixed platform royalty percentage (300 = 3%)
uint96 private constant PLATFORM_ROYALTY_PERCENTAGE = 300;

// Maximum total royalty (10000 = 100%)
uint96 private constant MAX_ROYALTY = 10000;

// Platform wallet address
address public platformWallet;

// Token ID counter
using Counters for Counters.Counter;
Counters.Counter private _tokenIdCounter;

// Mapping from token ID to royalty recipients
mapping(uint256 => address[]) private _tokenRoyaltyRecipients;

// Mapping from token ID to royalty shares (in basis points, 1/100 of a percent)
mapping(uint256 => uint96[]) private _tokenRoyaltyShares;
```

### Gas Optimization

The contract includes several gas optimizations:

1. **Batch Processing**: Royalty recipients and shares are processed in batches
2. **Memory Usage**: Arrays are stored in memory during processing
3. **Minimal Storage**: Only essential data is stored on-chain
4. **Efficient Calculation**: SafeMath is used for overflow protection with minimal gas overhead

## Security Considerations

### Reentrancy Protection

The contract includes protection against reentrancy attacks in the royalty distribution function:

1. Calculations are performed before external calls
2. External calls are made at the end of the function
3. State changes are completed before any external calls

### Access Control

The contract implements the following access controls:

1. **Owner-Only Functions**: Contract owner can update the platform wallet
2. **Public Functions**: Minting and royalty distribution are public but include validations
3. **Internal Functions**: Core logic is protected by internal visibility

### Overflow/Underflow Protection

The contract uses Solidity 0.8.17, which includes built-in overflow/underflow protection, and SafeMath for additional safety.

### Input Validation

All functions include validation to ensure:

1. Royalty percentages don't exceed the maximum
2. Recipients and shares arrays match in length
3. Token IDs exist before operations
4. Sufficient funds are provided for royalty distribution

## Audit Results

The VortexRoyaltyNFT contract has undergone security audits by independent security firms. Key findings from the audits have been addressed:

1. **Initial Audit Results**:
   - No critical vulnerabilities found
   - Minor optimization opportunities identified
   - Recommendations for input validation improvements

2. **Remediation**:
   - Enhanced input validation
   - Improved gas efficiency
   - Expanded test coverage

3. **Final Audit Results**:
   - Contract deemed secure for production use
   - All recommendations implemented
   - No remaining security concerns

## Integration with WordPress

The WordPress plugin integrates with the smart contract through a series of PHP classes:

### Blockchain Integration Class

The `Vortex_Blockchain_Integration` class provides the bridge between WordPress and the blockchain:

```php
class Vortex_Blockchain_Integration {
    // Properties for contract interaction
    private $contract_address;
    private $contract_abi;
    private $network_rpc_url;
    
    // Methods for NFT operations
    public function mint_nft($nft_data) {
        // Create WordPress post for NFT
        // Prepare royalty data
        // Return metadata for minting
    }
    
    public function get_nft_data($post_id) {
        // Retrieve NFT data from WordPress
        // Combine with blockchain data
        // Return complete NFT information
    }
}
```

### Admin Interface

The `Blockchain_Admin` class provides the WordPress admin interface for blockchain settings and NFT management:

```php
class Blockchain_Admin {
    // Constructor registers necessary hooks
    public function __construct() {
        add_action('admin_menu', array($this, 'add_blockchain_menu'));
        add_action('admin_init', array($this, 'register_blockchain_settings'));
        // Additional hooks for AJAX handlers
    }
    
    // Admin page rendering
    public function render_blockchain_page() {
        // Render tabs for Settings, Wallet, and NFTs
    }
    
    // AJAX handlers for wallet connection
    public function ajax_connect_wallet() {
        // Validate nonce
        // Store wallet address
        // Return success response
    }
    
    // AJAX handlers for NFT minting
    public function ajax_mint_nft() {
        // Validate nonce and permissions
        // Process NFT data
        // Return response
    }
}
```

## Customization Options

Developers can customize the smart contract integration in several ways:

### WordPress Filters

```php
// Modify NFT metadata before minting
add_filter('vortex_nft_metadata', function($metadata, $post_id) {
    // Add custom attributes
    $metadata['attributes'][] = array(
        'trait_type' => 'Custom Trait',
        'value' => 'Custom Value'
    );
    return $metadata;
}, 10, 2);

// Customize royalty calculations
add_filter('vortex_royalty_calculation', function($royalties, $nft_data) {
    // Modify royalty structure
    return $royalties;
}, 10, 2);
```

### WordPress Actions

```php
// Execute code before NFT minting
add_action('vortex_before_nft_mint', function($nft_data) {
    // Pre-minting operations
}, 10, 1);

// Execute code after successful minting
add_action('vortex_after_nft_mint', function($result, $nft_data) {
    // Post-minting operations
}, 10, 2);
```

### Contract Deployment Customization

For advanced customization, developers can modify the contract before deployment:

1. Edit the `VortexRoyaltyNFT.sol` file
2. Adjust royalty percentages or add new features
3. Deploy the modified contract
4. Update the ABI in the WordPress plugin

## Developer Reference

### Key Functions

#### Smart Contract

| Function | Description | Parameters |
|----------|-------------|------------|
| `mintWithRoyalties` | Mints a new NFT with specified royalties | `address to`, `string tokenURI`, `address[] royaltyRecipients`, `uint96[] royaltyShares` |
| `royaltyInfo` | Returns royalty info for a token | `uint256 tokenId`, `uint256 salePrice` |
| `distributeSaleRoyalties` | Distributes royalties after a sale | `uint256 tokenId`, `uint256 saleAmount` |
| `setPlatformWallet` | Updates platform wallet address | `address newWallet` |

#### PHP Integration

| Function | Description | Parameters |
|----------|-------------|------------|
| `mint_nft` | Creates NFT post and prepares data for minting | `array $nft_data` |
| `get_nft_data` | Retrieves NFT data | `int $post_id` |
| `get_user_nfts` | Gets NFTs owned by a user | `int $user_id`, `array $args` |

### Events

The smart contract emits the following events:

| Event | Description | Parameters |
|-------|-------------|------------|
| `RoyaltiesSet` | Emitted when royalties are set for a token | `uint256 tokenId`, `address[] recipients`, `uint96[] shares` |
| `UpdatedPlatformWallet` | Emitted when platform wallet is updated | `address oldWallet`, `address newWallet` |

### Error Codes

| Error | Description |
|-------|-------------|
| `Recipients and shares length mismatch` | The number of royalty recipients doesn't match the number of shares |
| `Total royalty exceeds maximum` | The total royalty percentage exceeds 100% |
| `Insufficient funds for royalty payment` | Not enough funds provided for royalty distribution |
| `Platform royalty transfer failed` | Failed to transfer royalty to platform wallet |
| `Creator royalty transfer failed` | Failed to transfer royalty to creator wallet |

For further details on implementation, refer to the contract source code and WordPress integration files. 