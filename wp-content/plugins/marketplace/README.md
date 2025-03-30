# Vortex AI Marketplace Plugin

## Description

The Vortex AI Marketplace is a comprehensive WordPress plugin that creates an AI-powered digital marketplace for artists. It features deep learning capabilities, blockchain integration with TOLA tokens, gamification elements, and a sophisticated AI agent system to provide intelligent insights and assistance.

## Key Features

### AI Agents

The plugin includes four primary AI agents:

1. **CLOE (Contextual Learning and Optimization Engine)** - Analyzes user behavior and market trends
2. **HURAII (Human Understanding, Response, and Artistic Intelligence Interface)** - Handles user interactions and creative guidance
3. **Business Strategist** - Provides business intelligence and strategic recommendations
4. **Thorius** - Manages blockchain and smart contract operations

### Deep Learning System

- Continuous learning for all AI agents
- Adaptable learning rates
- Context window management
- Cross-learning between agents

### Blockchain Integration

- TOLA token integration
- Artwork tokenization
- Smart contracts
- Real-time blockchain metrics

### Gamification System

- Points and levels
- Achievements and badges
- Leaderboards
- User actions tracking

## Installation

1. Upload the `marketplace` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to "Vortex Marketplace" in the admin menu to configure settings

## Shortcodes

- `[vortex_artwork_gallery]` - Displays a gallery of artwork
- `[vortex_artist_profile]` - Shows an artist's profile
- `[vortex_marketplace_stats]` - Displays marketplace statistics
- `[vortex_blockchain_stats]` - Shows blockchain and TOLA token metrics
- `[vortex_gamification_leaderboard]` - Displays the gamification leaderboard
- `[vortex_user_dashboard]` - Shows user-specific dashboard
- `[vortex_ai_insights]` - Displays AI-generated insights

## Configuration

### Deep Learning Settings

The deep learning settings can be configured under "Vortex Marketplace > Deep Learning" in the WordPress admin:

- Enable/disable deep learning
- Adjust learning rates
- Configure context windows
- Manage cross-learning between agents

### Blockchain Settings

Configure blockchain settings under "Vortex Marketplace > Blockchain":

- Connect to TOLA blockchain
- Configure smart contract templates
- Set token distribution rules
- Manage blockchain metrics refresh rate

### Gamification Settings

Gamification can be configured under "Vortex Marketplace > Gamification":

- Define point values for actions
- Create badge requirements
- Configure level progression
- Set up achievement notifications

## Developer Documentation

### Action Hooks

- `vortex_artwork_upload` - Fired when artwork is uploaded
- `vortex_artwork_sold` - Fired when artwork is sold
- `vortex_blockchain_transaction` - Fired when a blockchain transaction occurs
- `vortex_user_level_up` - Fired when a user levels up

### Filter Hooks

- `vortex_ai_response` - Filter AI agent responses
- `vortex_points_awarded` - Filter points awarded for actions
- `vortex_token_value` - Filter TOLA token values

### REST API Endpoints

- `vortex/v1/blockchain/metrics` - Get blockchain metrics
- `vortex/v1/blockchain/stats/{days}` - Get blockchain stats for specified days
- `vortex/v1/marketplace/insights` - Get marketplace insights
- `vortex/v1/artworks` - Get artwork data

## Credits

Developed by the Vortex AI team. Special thanks to all open-source contributors and the WordPress community.

## License

This plugin is licensed under the GPL v2 or later.

## Changelog

### 1.0.0
- Initial release
- AI agent system implementation
- Blockchain integration
- Gamification system
- Deep learning capabilities

### 1.1.0
- Added real-time blockchain metrics
- Enhanced HURAII image generation capabilities
- Improved cross-learning between AI agents
- Added user dashboard shortcode 