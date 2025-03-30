# VORTEX AI Marketplace

A comprehensive WordPress plugin for AI-powered art creation, curation, and marketplace with blockchain integration.

## Description

VORTEX AI Marketplace is an all-in-one platform that combines three powerful AI agents (HURAII, CLOE, and BusinessStrategist) with blockchain technology to create a complete ecosystem for digital art creation, distribution, and monetization.

### Key Features

- **HURAII AI Art Generation**: Create 2D, 3D, 4D, and multimedia content with advanced AI
- **CLOE Curation Engine**: Personalized recommendations and trend analysis
- **BusinessStrategist**: Career guidance for artists with 30-day challenges
- **TOLA Blockchain Integration**: Token-based marketplace with smart contracts
- **Creator Royalties**: 5% for HURAII creator + up to 15% for artists
- **Comprehensive Analytics**: Track user behavior and market trends
- **Multi-Format Support**: Generate, manipulate and sell various file formats
- **Deep Learning**: AI agents that continuously learn and improve

## Requirements

- WordPress 5.8+
- PHP 7.4+
- MySQL 5.7+ or MariaDB 10.3+
- Web server with at least 2GB memory limit

## Installation

1. Upload the `vortex-ai-marketplace` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to VORTEX > Settings to configure the plugin
4. Set up your TOLA wallet connection in the Blockchain settings tab

## Usage

### For Artists

1. Register and select "Artist" role
2. Complete the BusinessStrategist's onboarding quiz
3. Commit to uploading at least 2 artworks per week
4. Follow your personalized 30-day business plan
5. Use HURAII to generate various art formats
6. Earn TOLA tokens and build your collector base

### For Collectors

1. Browse AI-curated artwork recommendations
2. Use the swipe interface to refine your preferences
3. Purchase artwork with TOLA tokens
4. Manage your digital art collection
5. Engage with artists and community

### For Administrators

1. Access analytics and reports in the Admin dashboard
2. Monitor platform activity and sales
3. Configure royalty settings and marketplace fees
4. Manage AI agent settings and learning parameters

## AI Agents

### HURAII

HURAII specializes in content generation across multiple formats:

- 2D images (JPG, PNG, WEBP)
- 3D models (OBJ, GLB, GLTF, FBX)
- Video content (MP4, GIF, MOV)
- Audio creation (MP3, WAV)
- Interactive formats (HTML5, SVG)
- 4D time-based content

HURAII also analyzes artwork using the Seed Art technique with seven components:
1. Color harmony
2. Composition
3. Depth and perspective
4. Texture
5. Light and shadow
6. Emotion and narrative
7. Movement and layering

### CLOE

CLOE learns from user behavior to provide:

- Personalized recommendations
- Trend analysis and predictions
- SEO optimization for artwork
- Content curation and discovery
- Demographic insights
- Temporal pattern recognition

CLOE greets users with personalized, motivational messages based on their activity patterns and preferences.

### BusinessStrategist

BusinessStrategist provides career guidance:

- Artist onboarding and business planning
- 30-day challenges with milestone tracking
- Production commitment monitoring (2 artworks/week)
- Market strategy and pricing recommendations
- Career development pathways
- Business opportunity identification

## Blockchain Integration

- **TOLA Token**: Platform utility token for transactions
- **Smart Contracts**: Automatically enforce royalties and agreements
- **NFT Minting**: Create verifiable digital assets
- **Royalty System**: 5% creator royalty + up to 15% artist royalty

## Deep Learning Pipeline

The plugin implements a comprehensive deep learning pipeline:

1. User interaction events trigger learning across all AI agents
2. Cross-agent communication shares insights between HURAII, CLOE, and BusinessStrategist
3. Continuous model training improves recommendations and generation
4. Behavioral pattern recognition enhances user experience
5. External data sources enrich the learning process

## Learning Contexts

The pipeline processes data from various contexts:

1. **User Interaction**: Direct user actions on the platform
2. **Content Analysis**: Analysis of uploaded and generated content
3. **Market Activity**: Sales, pricing, and transaction patterns
4. **Temporal Patterns**: Time-based user behavior
5. **Demographic Insights**: User characteristics and preferences
6. **External Sources**: Art market trends and external data

## Pipeline Components

### 1. Event Tracking

- **User Actions**: Captures views, likes, purchases, creation, and other interactions
- **System Events**: Records system-level events like generation quality, transaction success
- **Temporal Data**: Timestamps all events for temporal pattern analysis
- **Contextual Metadata**: Associates relevant metadata with each event

### 2. Context Extraction

- **Feature Extraction**: Identifies key features from raw event data
- **Context Categorization**: Categorizes events into learning contexts
- **Relevance Scoring**: Scores data relevance for different learning models
- **Privacy Filtering**: Filters sensitive data according to privacy settings

### 3. Agent-Specific Learning

#### HURAII Learning

- **Visual Preference Analysis**: Learns user aesthetic preferences
- **Generation Parameter Optimization**: Refines generation parameters
- **Style Recognition**: Improves style recognition and reproduction
- **Format Handling Optimization**: Enhances capabilities across formats

#### CLOE Learning

- **User Preference Modeling**: Builds detailed user preference profiles
- **Trend Identification**: Identifies emerging trends and patterns
- **Recommendation Refinement**: Improves recommendation accuracy
- **Content Categorization**: Enhances content categorization systems

#### BusinessStrategist Learning

- **Business Plan Optimization**: Refines business plan structures
- **Success Pattern Recognition**: Identifies patterns in successful artists
- **Market Opportunity Detection**: Improves detection of market opportunities
- **Commitment Motivation**: Optimizes motivation strategies

### 4. Cross-Agent Integration

- **Shared Insights**: Distributes relevant insights across agents
- **Complementary Learning**: Integrates complementary knowledge
- **Conflict Resolution**: Resolves conflicting insights between agents
- **Synergy Detection**: Identifies opportunities for agent collaboration

### 5. Model Training & Updates

- **Batch Training**: Regular batch training of agent models
- **Incremental Updates**: Real-time incremental updates for critical learning
- **Model Validation**: Validates model improvements before deployment
- **Version Control**: Manages model versions and rollback capabilities

### 6. Enhanced User Experience

- **Personalization**: Delivers increasingly personalized experiences
- **Adaptive Interfaces**: Adapts interfaces based on user preferences
- **Predictive Features**: Enables predictive features and suggestions
- **Continuous Improvement**: Provides continuously improving AI experiences

## Learning Schedule

The learning pipeline operates on multiple timescales:

- **Real-time Learning**: Immediate updates for critical user interactions
- **Daily Processing**: Daily batch processing for aggregate insights
- **Weekly Analysis**: Weekly deep analysis for pattern recognition
- **Monthly Refinement**: Monthly model refinements and major updates

## Performance Metrics

The pipeline effectiveness is measured through:

- **Recommendation Accuracy**: Percentage of recommendations resulting in engagement
- **Generation Quality**: User satisfaction with generated content
- **Business Plan Success**: Success rate of artist business plans
- **Model Convergence**: Stability and convergence of learning models
- **User Satisfaction**: Overall user satisfaction metrics

## Implementation Details

The learning pipeline is implemented in the `class-vortex-ai-learning.php` file, with agent-specific learning handled in their respective class files. Cross-agent communication is facilitated by the `class-vortex-ai-communications.php` component.

Key technical aspects:

1. **Data Storage**: Learning data is stored in the `vortex_ai_learning_data` table
2. **Model Storage**: Models are stored in the `/models/` directory by agent
3. **Training Process**: Training runs as scheduled WordPress cron jobs
4. **API Integration**: External data is gathered through API integrations

## Extension Points

The learning pipeline provides several extension points:

- **Custom Learning Contexts**: Register custom learning contexts
- **External Data Sources**: Add new external data sources
- **Model Customization**: Customize or replace learning models
- **Monitoring Hooks**: Add custom monitoring and reporting

## Support and Documentation

- For detailed documentation, visit [docs.vortex-marketplace.com](https://docs.vortex-marketplace.com)
- Submit support tickets at [support.vortex-marketplace.com](https://support.vortex-marketplace.com)
- Join our community forum at [community.vortex-marketplace.com](https://community.vortex-marketplace.com)

## License

GPL v2 or later 

## Core Component Interactions

### AI Agent Interaction Flow 

### Data Flow Architecture 

## Database Structure

## Component Responsibilities

### AI Agents

1. **HURAII**
   - Content generation (2D, 3D, 4D, video, audio)
   - Visual analysis and processing
   - Seed Art techniques implementation
   - Format handling and conversion

2. **CLOE**
   - User behavior analysis
   - Content curation and recommendations
   - Trend identification and correlation
   - SEO and marketing optimization

3. **BusinessStrategist**
   - Artist onboarding and business planning
   - Career guidance and milestone tracking
   - 30-day challenges and commitment monitoring
   - Market strategy and pricing recommendations

### Core Systems

1. **Blockchain Integration**
   - TOLA token management
   - Smart contract implementation
   - NFT minting and verification
   - Royalty enforcement (5% creator + up to 15% artist)

2. **Marketplace**
   - Artwork listing and discovery
   - Transaction processing
   - Artist and collector profiles
   - Commission and fee handling

3. **Deep Learning Pipeline**
   - Cross-agent learning integration
   - Model training and optimization
   - User behavior pattern recognition
   - Continuous improvement systems

# VORTEX AI Marketplace - Deep Learning Pipeline

## Overview

The VORTEX AI Marketplace implements a sophisticated deep learning pipeline that enables all AI agents (HURAII, CLOE, and BusinessStrategist) to continuously learn and improve from user interactions, market data, and cross-agent communication.

## Pipeline Architecture

# VORTEX AI Marketplace - API Documentation

## Overview

The VORTEX AI Marketplace provides a comprehensive API for integrating with the platform's AI capabilities, blockchain functionality, and marketplace features.

## Authentication

All API requests require authentication using API keys or OAuth 2.0.

### API Key Authentication

```
GET /wp-json/vortex/v1/endpoint
Authorization: Bearer YOUR_API_KEY
```

### OAuth 2.0 Authentication

1. Register your application to receive client credentials
2. Obtain an access token using the OAuth 2.0 flow
3. Include the access token in the Authorization header

## API Endpoints

### HURAII AI Generation API

#### Generate Artwork

```
POST /wp-json/vortex/v1/huraii/generate
```

Parameters:
- `prompt` (string, required): Text prompt for generation
- `format` (string): Output format (png, jpg, mp4, obj, etc.)
- `width` (integer): Output width
- `height` (integer): Output height
- `seed` (integer): Random seed for reproducibility
- `model` (string): Model to use for generation
- `options` (object): Additional format-specific options

#### Analyze Artwork

```
POST /wp-json/vortex/v1/huraii/analyze
```

Parameters:
- `artwork_id` (integer): ID of artwork to analyze
- `file` (file): Upload file for analysis
- `components` (array): Specific components to analyze

### CLOE API

#### Get Recommendations

```
GET /wp-json/vortex/v1/cloe/recommendations
```

Parameters:
- `user_id` (integer): User to get recommendations for
- `type` (string): Type of recommendations (artwork, artist, style)
- `limit` (integer): Number of recommendations to return

#### Analyze Trends

```
GET /wp-json/vortex/v1/cloe/trends
```

Parameters:
- `category` (string): Category to analyze
- `timeframe` (string): Timeframe for trend analysis
- `limit` (integer): Number of trends to return

### BusinessStrategist API

#### Generate Business Plan

```
POST /wp-json/vortex/v1/business/plan
```

Parameters:
- `user_id` (integer): User to generate plan for
- `plan_type` (string): Type of business plan
- `quiz_answers` (object): Answers from the business quiz
- `goals` (array): Business goals

#### Check Milestone Status

```
GET /wp-json/vortex/v1/business/milestones
```

Parameters:
- `user_id` (integer): User to check milestones for
- `plan_id` (integer): Business plan ID

### Marketplace API

#### List Artwork

```
GET /wp-json/vortex/v1/marketplace/artwork
```

Parameters:
- `page` (integer): Page number
- `per_page` (integer): Items per page
- `category` (string): Filter by category
- `artist_id` (integer): Filter by artist
- `format` (string): Filter by format

#### Create Listing

```
POST /wp-json/vortex/v1/marketplace/listing
```

Parameters:
- `artwork_id` (integer): Artwork to list
- `price` (number): Listing price
- `currency` (string): Currency (default: TOLA)
- `royalty` (number): Artist royalty percentage (max 15%)

### Blockchain API

#### Get Wallet Balance

```
GET /wp-json/vortex/v1/blockchain/balance
```

Parameters:
- `wallet_address` (string): Wallet address to check
- `token` (string): Token type (default: TOLA)

#### Create Transaction

```
POST /wp-json/vortex/v1/blockchain/transaction
```

Parameters:
- `from_wallet` (string): Sender wallet address
- `to_wallet` (string): Recipient wallet address
- `amount` (number): Transaction amount
- `token` (string): Token type (default: TOLA)
- `memo` (string): Transaction memo

#### Mint NFT

```
POST /wp-json/vortex/v1/blockchain/mint
```

Parameters:
- `artwork_id` (integer): Artwork to mint
- `owner_wallet` (string): Wallet to mint to
- `metadata` (object): Additional metadata

## Webhooks

The API provides webhooks for real-time notifications:

### Register Webhook

```
POST /wp-json/vortex/v1/webhooks/register
```

Parameters:
- `event` (string): Event to listen for
- `url` (string): URL to send webhook to
- `secret` (string): Secret for webhook verification

### Available Webhook Events

- `artwork.created`: Triggered when new artwork is created
- `artwork.sold`: Triggered when artwork is sold
- `nft.minted`: Triggered when NFT is minted
- `transaction.completed`: Triggered when transaction completes
- `user.milestone`: Triggered when user reaches milestone

## Rate Limits

- Free tier: 100 requests per hour
- Pro tier: 1,000 requests per hour
- Enterprise tier: Custom limits

## Error Responses

The API uses standard HTTP status codes and returns error details in JSON format:

```json
{
  "code": "error_code",
  "message": "Human-readable error message",
  "data": {
    "additional": "error details"
  }
}
```

## SDK Libraries

Official SDK libraries are available for:
- JavaScript/Node.js
- PHP
- Python
- Ruby

## Examples

### Generate Artwork with HURAII

```javascript
// JavaScript example
const response = await fetch('/wp-json/vortex/v1/huraii/generate', {
  method: 'POST',
  headers: {
    'Authorization': 'Bearer YOUR_API_KEY',
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    prompt: 'A futuristic cityscape with flying cars',
    format: 'png',
    width: 1024,
    height: 1024,
    model: 'sd-v2-1'
  })
});

const result = await response.json();
console.log(result.image_url);
```

### Get Recommendations from CLOE

```php
// PHP example
$response = wp_remote_get(
  '/wp-json/vortex/v1/cloe/recommendations',
  array(
    'headers' => array(
      'Authorization' => 'Bearer ' . YOUR_API_KEY
    ),
    'body' => array(
      'user_id' => get_current_user_id(),
      'type' => 'artwork',
      'limit' => 10
    )
  )
);

$recommendations = json_decode(wp_remote_retrieve_body($response));
```

### `docs/developer-guide.md`

```markdown
# VORTEX AI Marketplace - Developer Guide

## Architecture Overview

VORTEX AI Marketplace follows a modular architecture with clear separation of concerns. The core components are:

1. **AI Agents**: HURAII, CLOE, and BusinessStrategist
2. **Blockchain Integration**: TOLA token and smart contracts
3. **Marketplace**: Artwork management and transactions
4. **Deep Learning Pipeline**: Cross-agent learning system

## Getting Started

### Setting Up Development Environment

1. Clone the repository:
   ```bash
   git clone https://github.com/yourusername/vortex-ai-marketplace.git
   cd vortex-ai-marketplace
   ```

2. Install dependencies:
   ```bash
   composer install
   npm install
   ```

3. Build assets:
   ```bash
   npm run build
   ```

### Environment Configuration

Create a `.env` file in the root directory with the following:

```
VORTEX_DEBUG=true
VORTEX_API_KEY=your_test_api_key
VORTEX_BLOCKCHAIN_TESTNET=true
```

## Core Components

### AI Agent System

AI agents are implemented as singleton classes to ensure single instances throughout the plugin lifecycle.

```php
// Example of extending an AI agent
class My_Custom_Agent extends VORTEX_AI_Agent_Base {
    // Override methods to customize behavior
    public function process_input($input_data) {
        // Custom processing
        return $processed_data;
    }
}

// Register your custom agent
add_filter('vortex_register_ai_agents', function($agents) {
    $agents['my_custom_agent'] = My_Custom_Agent::get_instance();
    return $agents;
});
```

### Hook System

The plugin provides numerous action and filter hooks for extension:

#### AI Agent Hooks

```php
// Before AI processing
add_action('vortex_before_ai_processing', function($agent_name, $input_data) {
    // Do something before processing
}, 10, 2);

// After AI processing
add_action('vortex_after_ai_processing', function($agent_name, $input_data, $output_data) {
    // Do something with the output
}, 10, 3);

// Filter AI output
add_filter('vortex_ai_output', function($output, $agent_name) {
    // Modify AI output
    return $modified_output;
}, 10, 2);
```

#### Marketplace Hooks

```php
// Before creating a new listing
add_action('vortex_before_marketplace_listing_create', function($artwork_id, $price, $currency, $royalty) {
    // Modify listing parameters
}, 10, 4);

// After creating a new listing
add_action('vortex_after_marketplace_listing_created', function($artwork_id, $price, $currency, $royalty) {
    // Handle listing creation
}, 10, 4);
```

#### Blockchain Hooks

```php
// Before creating a new transaction
add_action('vortex_before_blockchain_transaction_create', function($from_wallet, $to_wallet, $amount, $token, $memo) {
    // Modify transaction parameters
}, 10, 5);

// After creating a new transaction
add_action('vortex_after_blockchain_transaction_created', function($from_wallet, $to_wallet, $amount, $token, $memo) {
    // Handle transaction creation
}, 10, 5);
```