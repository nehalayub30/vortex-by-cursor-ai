# VORTEX Blockchain Integration

This document details the blockchain integration components for the VORTEX AI Marketplace WordPress plugin, enabling NFT creation and royalty management for AI-generated artwork.

## 🚀 Features

- **NFT Creation**: Mint NFTs from AI-generated artwork directly within WordPress
- **Royalty Management**: Implement the ERC-2981 royalty standard with a fixed 3% platform fee plus customizable creator royalties
- **Collaborator Support**: Allow multiple collaborators with customizable royalty percentages
- **Multi-Network Support**: Compatible with Ethereum, Polygon, and test networks
- **WordPress Integration**: Seamless integration with the WordPress admin interface
- **User-Friendly UI**: Simple interface for wallet connection and NFT management

## 📋 Requirements

- WordPress 5.8+
- PHP 7.4+
- Web3-compatible browser (Chrome, Firefox with MetaMask)
- Ethereum-compatible wallet (MetaMask recommended)
- Small amount of cryptocurrency for gas fees (ETH, MATIC, etc.)

## 📦 Installation

### Option 1: Plugin Installation

1. Download the VORTEX plugin ZIP from the releases
2. Upload and activate the plugin in your WordPress admin
3. Navigate to VORTEX > Blockchain Settings to configure

### Option 2: Manual Installation

1. Clone this repository
2. Copy the files to your WordPress plugin directory
3. Activate the plugin in your WordPress admin
4. Configure blockchain settings

## ⚙️ Configuration

1. Deploy the smart contract to your preferred network (see docs/blockchain-implementation.md)
2. In WordPress, navigate to VORTEX > Blockchain Settings
3. Enter your contract address and platform wallet
4. Save changes

## 🔧 Development

### Project Structure

```
includes/
  ├── admin/
  │     ├── class-vortex-blockchain-admin.php  # Admin interface
  │     ├── css/blockchain-admin.css           # Admin styles
  │     └── js/blockchain-admin.js             # Admin scripts
  ├── blockchain/
  │     ├── class-vortex-blockchain-integration.php  # WordPress integration
  │     └── contract-abi.json                        # Contract ABI
  └── ai/
        └── blockchain/
              └── VortexRoyaltyNFT.sol               # Smart contract
docs/
  ├── blockchain-implementation.md    # Implementation guide
  ├── smart-contract.md               # User documentation
  └── smart-contract-technical.md     # Technical documentation
```

### Smart Contract Development

To modify the smart contract:

1. Edit `includes/ai/blockchain/VortexRoyaltyNFT.sol`
2. Compile using Solidity 0.8.17+
3. Deploy to your preferred network
4. Update the ABI in `includes/blockchain/contract-abi.json`

### WordPress Integration Development

To customize the WordPress integration:

1. Modify the PHP classes in `includes/admin` and `includes/blockchain`
2. Use WordPress filters and actions (see docs/smart-contract-technical.md)
3. Customize the admin interface in the CSS and JS files

## 📝 Documentation

- [Implementation Guide](docs/blockchain-implementation.md) - Step-by-step setup instructions
- [Smart Contract Documentation](docs/smart-contract.md) - User-friendly explanation
- [Technical Documentation](docs/smart-contract-technical.md) - Technical details and architecture

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📄 License

This project is licensed under the GPL v2 or later - see the LICENSE file for details.

## 🔗 Links

- [VORTEX Plugin](https://github.com/MarianneNems/VORTEX)
- [Documentation](https://vortexartec.com/docs)
- [Support](https://vortexartec.com/support)

## 🙏 Acknowledgments

- OpenZeppelin for secure contract implementations
- Web3.js for blockchain interaction
- MetaMask for wallet integration 