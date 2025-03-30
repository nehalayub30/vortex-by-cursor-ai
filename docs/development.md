# Development Guide

## Setting Up Development Environment

1. **Prerequisites**
   - PHP 7.4 or higher
   - WordPress 5.8 or higher
   - Composer
   - Node.js & npm
   - Web3 provider (MetaMask recommended)

2. **Installation**
   ```bash
   # Clone repository
   git clone https://github.com/MarianneNems/VORTEX-AI-AGENTS
   cd VORTEX-AI-AGENTS

   # Install dependencies
   composer install
   npm install
   ```

3. **Running Tests**
   ```bash
   # Run all tests
   composer test

   # Run specific test suite
   composer test -- --testsuite=unit
   composer test -- --testsuite=integration

   # Generate coverage report
   composer test:coverage
   ```

4. **Code Standards**
   ```bash
   # Check coding standards
   composer phpcs

   # Fix coding standards automatically
   composer phpcbf
   ```

## Architecture

### Core Components
1. **AI Engine**
   - Market Analysis
   - Price Prediction
   - Trend Detection

2. **Blockchain Integration**
   - Wallet Connection
   - NFT Minting
   - Transaction Management

3. **Market Interface**
   - Trend Analysis
   - Price Prediction
   - Opportunity Detection

## Contributing

1. Fork the repository
2. Create feature branch (`git checkout -b feature/amazing-feature`)
3. Commit changes (`git commit -m 'Add amazing feature'`)
4. Push to branch (`git push origin feature/amazing-feature`)
5. Open Pull Request 