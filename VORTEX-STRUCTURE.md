# VORTEX AI Marketplace - Complete Structure

This document outlines the comprehensive directory structure and architecture for the VORTEX AI Marketplace plugin, a blockchain-powered art marketplace with integrated AI art generation tools and TOLA token functionality.

## Directory Structure

The color-coding indicates different functional areas of the plugin:

- 🔵 **Blue**: Core Plugin Files & General Infrastructure
- 🟢 **Green**: Core Functionality & Internationalization
- 🟡 **Yellow**: Marketplace, Artists & Artwork Features
- 🔴 **Red**: Admin Interface Components
- 🟠 **Orange**: HURAII AI & Image Processing Systems
- 🟣 **Purple**: Blockchain & TOLA Token Integration
- 🟤 **Brown**: Analytics, Metrics & Rankings
- 🟨 **Light Yellow**: Career, Project & Collaboration Features

```
vortex-ai-marketplace/
│
├── 🔵 vortex-ai-marketplace.php              # Main plugin file with WordPress header
├── 🔵 uninstall.php                          # Cleanup on uninstallation
├── 🔵 readme.txt                             # Plugin documentation for WordPress.org
│
├── 🟢 includes/                              # Core functionality
│   ├── 🟢 class-vortex-ai-marketplace.php    # Main plugin class
│   ├── 🟢 class-vortex-activator.php         # Activation hooks, DB setup
│   ├── 🟢 class-vortex-deactivator.php       # Deactivation cleanup
│   ├── 🟢 class-vortex-loader.php            # Hook loader utility
│   ├── 🟢 class-vortex-i18n.php              # Internationalization handler
│   │
│   ├── 🟨 class-vortex-career-project-collaboration.php  # Career path & collaboration features
│   │
│   ├── 🟣 blockchain/                         # Blockchain functionality
│   │   ├── 🟣 class-vortex-blockchain-integration.php  # Main blockchain integration
│   │   ├── 🟣 class-vortex-tola.php           # TOLA token integration
│   │   ├── 🟣 contract-abi.json               # Contract ABI
│   │   └── 🟣 class-vortex-wallet-connect.php # Wallet connection handler
│   │
│   ├── 🟠 class-vortex-huraii.php            # HURAII AI integration
│   ├── 🟡 class-vortex-marketplace.php       # Marketplace functionality
│   ├── 🟡 class-vortex-artists.php           # Artist management
│   ├── 🟤 class-vortex-metrics.php           # Metrics collection and processing
│   ├── 🟤 class-vortex-rankings.php          # Ranking algorithms and display
│   ├── 🟤 class-vortex-analytics.php         # Analytics processing
│   ├── 🟠 class-vortex-image-processor.php   # Image processing functionality
│   ├── 🟠 class-vortex-cloe.php              # CLOE AI integration
│   ├── 🟠 class-vortex-business-strategist.php # Business Strategist AI integration
│   │
│   ├── 🟡 post-types/                        # Custom post types
│   │   ├── 🟡 class-vortex-artwork.php       # Artwork post type
│   │   ├── 🟡 class-vortex-artist.php        # Artist post type
│   │   ├── 🟠 class-vortex-huraii-template.php # HURAII template post type
│   │   ├── 🟨 class-vortex-project.php       # Project post type
│   │   └── 🟨 class-vortex-collaboration.php # Collaboration post type
│   │
│   ├── 🟡 taxonomies/                        # Custom taxonomies
│   │   ├── 🟡 class-vortex-artwork-category.php
│   │   ├── 🟡 class-vortex-artwork-tag.php
│   │   └── 🟨 class-vortex-project-category.php # Project categories
│   │
│   ├── 🔵 widgets/                           # WordPress widgets
│   │   ├── 🟡 class-vortex-featured-artwork-widget.php
│   │   ├── 🟡 class-vortex-artist-widget.php
│   │   ├── 🟤 class-vortex-metrics-widget.php      # Metrics display widget
│   │   ├── 🟤 class-vortex-top-artists-widget.php  # Top artists widget
│   │   ├── 🟣 class-vortex-tola-balance-widget.php # TOLA balance widget
│   │   ├── 🟢 class-vortex-language-widget.php     # Language switcher widget
│   │   ├── 🟠 class-vortex-huraii-widgets.php      # HURAII widgets
│   │   ├── 🟠 class-vortex-cloe-widgets.php        # CLOE widgets
│   │   └── 🟠 class-vortex-business-strategist-widgets.php # Business Strategist widgets
│   │
│   └── 🟠 ai-models/                         # AI model configuration
│       ├── 🟠 class-vortex-text2img.php      # Text-to-image model integration
│       ├── 🟠 class-vortex-img2img.php       # Image-to-image model integration
│       └── 🟠 class-vortex-model-loader.php  # AI model loader
│
├── 🔴 admin/                                 # Admin interface
│   ├── 🔴 class-vortex-admin.php             # Admin main class
│   │
│   ├── 🔴 js/
│   │   ├── 🔴 vortex-admin.js                # Main admin JS
│   │   ├── 🟣 blockchain-admin.js            # Blockchain settings & admin functionality
│   │   ├── 🟤 vortex-metrics-dashboard.js    # Admin metrics dashboard JS
│   │   └── 🟢 vortex-language-admin.js       # Language management JS
│   │
│   ├── 🔴 css/
│   │   ├── 🔴 vortex-admin.css               # Main admin CSS
│   │   ├── 🟣 blockchain-admin.css           # Blockchain admin interface styling
│   │   ├── 🟤 vortex-metrics-dashboard.css   # Admin metrics dashboard CSS
│   │   └── 🟢 vortex-language-admin.css      # Language management CSS
│   │
│   ├── 🔴 partials/                          # Admin page templates
│   │   ├── 🔴 dashboard.php                  # Main dashboard
│   │   ├── 🟡 marketplace-settings.php       # Marketplace configuration
│   │   ├── 🟣 blockchain-settings.php        # Blockchain integration settings
│   │   ├── 🟣 tola-settings.php              # TOLA token settings
│   │   ├── 🟠 huraii-settings.php            # HURAII generation settings
│   │   ├── 🟠 image-to-image-settings.php    # Image-to-image settings
│   │   ├── 🔴 appearance-settings.php        # UI/UX customization
│   │   ├── 🟤 metrics-dashboard.php          # Metrics dashboard view
│   │   ├── 🟤 rankings-settings.php          # Rankings configuration panel
│   │   ├── 🟢 language-settings.php          # Language configuration
│   │   └── 🟨 career-project-settings.php    # Career and project settings
│   │
│   └── 🔴 images/                            # Admin interface images
│
├── 🔵 public/                                # Public-facing functionality
│   ├── 🔵 class-vortex-public.php            # Public main class
│   │
│   ├── 🔵 js/
│   │   ├── 🟡 vortex-marketplace.js          # Marketplace interactions
│   │   ├── 🟣 vortex-blockchain.js           # Blockchain integration
│   │   ├── 🟣 vortex-tola.js                 # TOLA token functionality
│   │   ├── 🟠 vortex-huraii.js               # HURAII integration
│   │   ├── 🟠 vortex-img2img.js              # Image-to-image functions
│   │   ├── 🔵 vortex-public.js               # General public scripts
│   │   ├── 🟤 vortex-metrics-display.js      # Front-end metrics display
│   │   ├── 🟤 vortex-live-rankings.js        # Live rankings update script
│   │   ├── 🟢 vortex-language-switcher.js    # Front-end language switcher
│   │   ├── 🟠 vortex-ajax.js                 # AJAX handler utilities
│   │   ├── 🟡 vortex-swipe.js                # Swipe functionality for collector workplace
│   │   └── 🟨 vortex-career-project.js       # Career path and project functionality
│   │
│   ├── 🔵 css/
│   │   ├── 🟡 vortex-marketplace.css         # Marketplace styling
│   │   ├── 🟣 vortex-blockchain.css          # Blockchain UI styling
│   │   ├── 🟠 vortex-huraii.css              # HURAII UI styling
│   │   ├── 🟠 vortex-img2img.css             # Image-to-image styling
│   │   ├── 🔵 vortex-public.css              # General public styling
│   │   ├── 🟤 vortex-metrics.css             # Metrics styling
│   │   ├── 🟤 vortex-rankings.css            # Rankings styling
│   │   ├── 🟢 vortex-rtl.css                 # RTL language support
│   │   └── 🟨 vortex-career-project.css      # Career and project styling
│   │
│   ├── 🔵 partials/                          # Template parts
│   │   ├── 🟡 artwork-grid.php               # Artwork gallery grid
│   │   ├── 🟡 artwork-single.php             # Single artwork view
│   │   ├── 🟡 artist-grid.php                # Artist gallery grid
│   │   ├── 🟡 artist-single.php              # Single artist profile
│   │   ├── 🟣 tola-wallet.php                # TOLA wallet interface
│   │   ├── 🟣 tola-transaction.php           # TOLA transaction interface
│   │   ├── 🟠 huraii-interface.php           # HURAII main interface
│   │   ├── 🟠 img2img-interface.php          # Image-to-image interface
│   │   ├── 🟡 cart-checkout.php              # Purchase workflow
│   │   ├── 🟤 metrics-dashboard.php          # Public metrics dashboard
│   │   ├── 🟤 top-artists.php                # Top artists display
│   │   ├── 🟤 trending-artworks.php          # Trending artworks display
│   │   ├── 🟤 sales-leaderboard.php          # Sales leaderboard
│   │   ├── 🟨 vortex-career-path.php         # Career path interface
│   │   ├── 🟨 vortex-project-proposals.php   # Project proposals interface
│   │   ├── 🟨 vortex-collaboration-hub.php   # Collaboration hub interface
│   │   ├── 🟨 vortex-collaboration-form.php  # Collaboration creation form
│   │   └── 🟨 vortex-modal.php               # Modal component for forms
│   │
│   └── 🔵 images/                            # Public interface images
│
├── 🟡 blocks/                                # Gutenberg blocks
│   ├── 🟡 marketplace/                       # Marketplace block
│   ├── 🟡 artist-showcase/                   # Artist showcase block
│   ├── 🟡 artwork-grid/                      # Artwork grid block
│   ├── 🟡 featured-artwork/                  # Featured artwork block
│   ├── 🟣 tola-wallet/                       # TOLA wallet block
│   ├── 🟣 tola-balance/                      # TOLA balance display block
│   ├── 🟠 huraii-creator/                    # HURAII creator block
│   ├── 🟠 img2img-creator/                   # Image-to-image block
│   ├── 🟤 metrics-display/                   # Metrics display block
│   ├── 🟤 artist-ranking/                    # Artist ranking block
│   ├── 🟤 artwork-trending/                  # Trending artworks block
│   ├── 🟢 language-switcher/                 # Language switcher block
│   ├── 🟨 career-path/                       # Career path block
│   ├── 🟨 project-proposals/                 # Project proposals block
│   └── 🟨 collaboration-hub/                 # Collaboration hub block
│
├── 🟤 templates/                             # Theme template overrides
│   ├── 🟡 single-vortex-artwork.php          # Single artwork template
│   ├── 🟡 single-vortex-artist.php           # Single artist template
│   ├── 🟡 archive-vortex-artwork.php         # Artwork archive template
│   ├── 🟡 archive-vortex-artist.php          # Artist archive template
│   ├── 🟡 taxonomy-vortex-artwork-category.php # Category archive
│   ├── 🟤 metrics-page-template.php          # Dedicated metrics page
│   ├── 🟨 single-vortex-project.php          # Single project template
│   ├── 🟨 archive-vortex-project.php         # Project archive template
│   ├── 🟨 single-vortex-collaboration.php    # Single collaboration template
│   └── 🟨 archive-vortex-collaboration.php   # Collaboration archive template
│
├── 🔵 api/                                   # API endpoints
│   ├── 🔵 class-vortex-api.php               # API setup
│   ├── 🟠 class-vortex-huraii-api.php        # HURAII API integration
│   ├── 🟣 class-vortex-blockchain-api.php    # Blockchain API integration
│   ├── 🟣 class-vortex-tola-api.php          # TOLA token API endpoints
│   ├── 🟤 class-vortex-metrics-api.php       # Metrics API endpoints
│   ├── 🟤 class-vortex-rankings-api.php      # Rankings API endpoints
│   ├── 🟢 class-vortex-translation-api.php   # Translation API endpoints
│   └── 🟨 class-vortex-career-project-api.php # Career & project API endpoints
│
├── 🟢 languages/                             # Translation files
│   ├── 🟢 vortex-ai-marketplace.pot          # Translation template
│   ├── 🟢 vortex-ai-marketplace-en_US.po     # English translation
│   ├── 🟢 vortex-ai-marketplace-en_US.mo     # Compiled English translation
│   ├── 🟢 vortex-ai-marketplace-es_ES.po     # Spanish translation
│   ├── 🟢 vortex-ai-marketplace-es_ES.mo     # Compiled Spanish translation
│   ├── 🟢 vortex-ai-marketplace-fr_FR.po     # French translation
│   ├── 🟢 vortex-ai-marketplace-fr_FR.mo     # Compiled French translation
│   ├── 🟢 vortex-ai-marketplace-de_DE.po     # German translation
│   ├── 🟢 vortex-ai-marketplace-de_DE.mo     # Compiled German translation
│   ├── 🟢 vortex-ai-marketplace-it_IT.po     # Italian translation
│   ├── 🟢 vortex-ai-marketplace-it_IT.mo     # Compiled Italian translation
│   ├── 🟢 vortex-ai-marketplace-ja.po        # Japanese translation
│   ├── 🟢 vortex-ai-marketplace-ja.mo        # Compiled Japanese translation
│   ├── 🟢 vortex-ai-marketplace-zh_CN.po     # Simplified Chinese translation
│   ├── 🟢 vortex-ai-marketplace-zh_CN.mo     # Compiled Simplified Chinese translation
│   └── 🟢 README.md                          # Translation guidelines
│
├── 🔵 assets/                                # General assets
│   ├── 🔵 images/                            # Plugin icons, logos
│   │   ├── 🔵 vortex-logo.png                # VORTEX logo
│   │   └── 🔵 vortex-icon.png                # Plugin icon
│   │
│   ├── 🔵 js/                                # Third-party JavaScript libraries
│   │   ├── 🟣 web3.min.js                    # Web3 integration
│   │   ├── 🟣 solana-web3.js                 # Solana Web3 integration
│   │   ├── 🟣 spl-token.js                   # SPL Token library for Solana
│   │   ├── 🟠 ai-processing.js               # AI processing library
│   │   ├── 🟠 image-processing.js            # Client-side image processing
│   │   └── 🟤 chart.min.js                   # Chart.js for visualizations
│   │
│   └── 🔵 css/                               # Third-party CSS
│       ├── 🔵 animations.css                 # Animation library
│       └── 🟤 charts.css                     # Chart styling
│
├── 🟣 blockchain/                            # Blockchain-specific files
│   ├── 🟣 TOLAToken.sol                      # TOLA token smart contract
│   ├── 🟣 tola-token-abi.json                # Contract ABI for Web3 interaction
│   ├── 🟣 class-vortex-token-handler.php     # Token handling class
│   └── 🟣 class-vortex-wallet-connect.php    # Wallet connection handler
│
└── 🟢 database/                              # Database-related functionality
    ├── 🟢 class-vortex-db.php                # Core database operations
    ├── 🟣 class-vortex-tola-db.php           # TOLA token database operations
    ├── 🟤 class-vortex-metrics-db.php        # Metrics database operations
    ├── 🟤 class-vortex-rankings-db.php       # Rankings database operations
    ├── 🟢 class-vortex-language-db.php       # Language preference storage
    ├── 🟨 class-vortex-db-setup.php          # Database setup (including career/collab tables)
    │
    └── 🟢 schemas/                           # Database schemas
        ├── 🟢 core-schema.php                # Core plugin tables
        ├── 🟣 tola-schema.php                # TOLA transactions tables
        ├── 🟤 metrics-schema.php             # Metrics tables
        ├── 🟤 rankings-schema.php            # Rankings tables
        ├── 🟢 language-schema.php            # Language tables
        └── 🟨 career-collab-schema.php       # Career and collaboration tables
```

## Database Schema

```
WordPress Database
├── WordPress Core Tables
│   ├── 🟡 wp_posts
│   │   ├── 🟡 post_type: vortex_artwork
│   │   ├── 🟡 post_type: vortex_artist
│   │   ├── 🟠 post_type: vortex_huraii_template
│   │   ├── 🟨 post_type: vortex_project
│   │   └── 🟨 post_type: vortex_collaboration
│   │
│   ├── 🟡 wp_postmeta
│   │   ├── 🟡 _vortex_artwork_price
│   │   ├── 🟡 _vortex_artwork_edition_size
│   │   ├── 🟠 _vortex_artwork_ai_prompt
│   │   ├── 🟠 _vortex_created_with_huraii
│   │   ├── 🟣 _vortex_blockchain_token_id
│   │   ├── 🟣 _vortex_blockchain_contract_address
│   │   ├── 🟣 _vortex_blockchain_name
│   │   ├── 🟣 _vortex_tola_price                 # TOLA price for product
│   │   ├── 🟣 _vortex_access_type                # One-time or subscription
│   │   ├── 🟣 _vortex_subscription_duration      # Duration number
│   │   ├── 🟣 _vortex_subscription_duration_unit # Duration unit (day/month/year)
│   │   ├── 🟣 _vortex_artist_wallet_address
│   │   ├── 🟨 _vortex_project_timeline           # Project timeline information
│   │   ├── 🟨 _vortex_project_budget             # Project budget
│   │   ├── 🟨 _vortex_skills_required            # Skills required for project
│   │   ├── 🟨 _vortex_project_status             # Project status (open, in-progress, completed)
│   │   ├── 🟨 _vortex_collaboration_type         # Type of collaboration
│   │   ├── 🟨 _vortex_collaboration_budget       # Collaboration budget
│   │   ├── 🟨 _vortex_collaboration_deadline     # Collaboration deadline
│   │   ├── 🟨 _vortex_collaboration_requirements # Collaboration requirements
│   │   └── 🟨 _vortex_collaboration_roles        # Required roles for the collaboration
│   │
│   ├── 🟡 wp_terms & wp_term_relationships
│   │   ├── 🟡 art_style
│   │   ├── 🟡 art_category
│   │   └── 🟨 project_category                   # Project categories taxonomy
│   │
│   ├── 🟢 wp_usermeta
│   │   ├── 🟣 vortex_wallet_address              # User's Solana wallet address
│   │   ├── 🟣 vortex_tola_balance                # Cached TOLA balance
│   │   ├── 🟣 vortex_purchased_products          # Array of purchased product IDs
│   │   └── 🟣 vortex_product_X_expiration        # Expiration date for subscriptions
│   │
│   └── 🟢 wp_options
│       ├── 🟡 vortex_marketplace_currency
│       ├── 🟡 vortex_marketplace_commission
│       ├── 🟣 vortex_blockchain_network
│       ├── 🟣 vortex_contract_address
│       ├── 🟣 vortex_solana_rpc_url              # Solana RPC URL
│       ├── 🟣 vortex_solana_network              # Solana network (mainnet/testnet/devnet)
│       ├── 🟣 vortex_tola_token_address          # TOLA token mint address
│       ├── 🟣 vortex_tola_decimals               # Token decimals (usually 9)
│       ├── 🟣 vortex_platform_wallet_address     # Platform wallet for fees
│       ├── 🟠 vortex_huraii_enabled
│       └── 🟠 vortex_huraii_default_style
│
└── Custom Tables
    ├── 🟣 vortex_transactions
    │   ├── id (PK)
    │   ├── transaction_id
    │   ├── from_address
    │   ├── to_address
    │   ├── amount
    │   ├── token_type
    │   ├── transaction_data (JSON)
    │   ├── status
    │   ├── blockchain_tx_hash
    │   ├── created_at
    │   └── updated_at
    │
    ├── 🟣 vortex_product_purchases
    │   ├── id (PK)
    │   ├── user_id
    │   ├── product_id
    │   ├── amount
    │   ├── transaction_id
    │   └── purchase_date
    │
    ├── 🟤 vortex_metrics
    │   ├── id (PK)
    │   └── object_id
    │
    ├── 🟤 vortex_rankings
    │   ├── id (PK)
    │   ├── artist_id
    │   ├── score
    │   └── updated_at
    │
    └── 🟤 vortex_sales
        ├── id (PK)
        ├── artwork_id
        ├── amount
        ├── currency
        └── sale_date
```

## TOLA Token Integration

The TOLA token is a Solana-based utility token that powers the VORTEX marketplace. Key integration points include:

### Wallet Integration Components

| Component | File Location | Description |
|-----------|---------------|-------------|
| Wallet Connection UI | `public/js/vortex-tola.js` | Interface for connecting Phantom wallet |
| Token Balance Display | `includes/blockchain/class-vortex-tola.php` | Token balance retrieval and display |
| Token Transaction Handler | `includes/blockchain/class-vortex-tola.php` | Process token transactions |
| Product Purchase Flow | `includes/blockchain/class-vortex-tola.php` | Handler for product purchases with TOLA |

### TOLA Database Interactions

TOLA transaction data is stored in two custom tables:

1. **vortex_transactions**: Records all TOLA transfers between wallets
2. **vortex_product_purchases**: Links product purchases to transactions and users

User wallet data and balances are stored in WordPress user meta tables.

### TOLA UI/UX Components

The TOLA integration includes several UI components:

1. Wallet connection interface (Connect/Disconnect)
2. Token balance display
3. Send token form for transfers
4. Purchase interface for products
5. Transaction history display

### AJAX Endpoints for TOLA

| Endpoint | Handler | Purpose |
|----------|---------|---------|
| vortex_get_tola_balance | `ajax_get_tola_balance()` | Get user's token balance |
| vortex_process_tola_transaction | `ajax_process_transaction()` | Process a token transfer |
| vortex_disconnect_wallet | `ajax_disconnect_wallet()` | Disconnect wallet session |
| vortex_purchase_product | `ajax_purchase_product()` | Process product purchase |

### Content Access Control

The TOLA integration includes a content access control system that:

1. Checks if content requires payment
2. Verifies if user has purchased access
3. Displays purchase form if access is required
4. Manages subscription expiration for time-limited content

## Architecture Diagrams

See [TOLA-Architecture.md](TOLA-Architecture.md) for a comprehensive architecture diagram of the TOLA token integration that includes:

1. UI/UX components and data flow
2. Server-side processing details
3. Blockchain interaction specifics
4. Database entity relationships

## Implementation Guidelines

### Adding New TOLA Features

When extending the TOLA functionality:

1. Backend PHP changes should be added to `class-vortex-tola.php`
2. JavaScript UI components should be added to `vortex-tola.js`
3. New database interactions should follow the pattern in `tola-schema.php`
4. New admin settings should be registered through `register_admin_settings()`

### TOLA Security Considerations

1. Always verify transactions server-side
2. Rate-limit balance checks and other RPC operations
3. Never store private keys
4. Always sanitize wallet addresses and amounts
5. Use nonces for all AJAX operations

### TOLA Testing Checklist

- [ ] Wallet connects properly
- [ ] Balance displays correctly
- [ ] Transfers complete successfully
- [ ] Purchases grant appropriate access
- [ ] Subscription expirations work correctly
- [ ] Admin settings save and load properly

## Main Feature Integration Points

### 1. Marketplace & Blockchain Connection
```
🟡 class-vortex-marketplace.php
   └── Calls → 🟣 class-vortex-blockchain.php
      ├── For → Transaction verification
      ├── For → Royalty payments
      └── For → Wallet validation
```

### 2. HURAII AI & Marketplace Connection
```
🟠 class-vortex-huraii.php
   └── Generates → 🟡 Artwork post type
      ├── With → AI metadata
      ├── With → Image generation parameters
      └── For → Marketplace listing
```

### 3. Analytics & Dashboard Connection
```
🟤 class-vortex-metrics.php
   └── Displays on → 🔴 Admin dashboard
      └── Visualized with → 🟤 Charts and metrics
```

### 4. Public & Private API Connections
```
🔵 API Endpoints
   ├── Connect → 🟣 Blockchain services
   ├── Connect → 🟠 AI generation services 
   └── Serve → 🟤 Metrics data
```

### 5. User Role Permissions
```
Account Types
├── 🟡 Artists
│   ├── Can → Create/manage artwork
│   ├── Can → Use HURAII AI (limited)
│   └── Can → Receive payments
│
├── 🟡 Collectors
│   ├── Can → Purchase artwork
│   ├── Can → View owned artwork
│   └── Can → Track collections
│
└── 🔴 Administrators
    ├── Can → Manage all settings
    ├── Can → View all analytics
    └── Can → Curate marketplace
```

## Development Guidelines

### Coding Standards
- Follow WordPress Coding Standards
- Use PHP DocBlocks for all functions
- Prefix all functions/classes with `vortex_`
- Sanitize inputs, escape outputs

### Security Considerations
- Use WordPress nonces for forms
- Validate user capabilities before operations
- Sanitize and validate all data from users
- Use prepared SQL statements

### Performance Best Practices
- Cache blockchain API responses
- Optimize database queries 
- Use transients for temporary data
- Lazy-load HURAII interface components

### Testing
- Unit test blockchain transactions in test networks
- Test HURAII AI generation with small models first
- Verify marketplace functionality in staging
- Cross-browser/device testing for frontend components

## Copyright Information

All content, code, design, and intellectual property contained in this plugin are the exclusive property of Marianne Nems (aka Mariana Villard), Founder and CEO of VortexArtec. All rights reserved.

For inquiries, please contact: Marianne@VortexArtec.com 