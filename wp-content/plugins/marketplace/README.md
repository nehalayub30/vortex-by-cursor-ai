# VORTEX AI Marketplace

A WordPress plugin for a blockchain-based AI marketplace with DAO governance and investment functionality.

## Features

- AI agent marketplace with buying, selling, and licensing capabilities
- DAO governance system for decentralized decision making
- Investment platform with equity token features
- Blockchain integration with Solana
- Token-based rewards and incentives
- Fully responsive and theme-agnostic frontend

## Installation

1. Upload the `marketplace` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure the plugin settings under 'Marketplace' in the WordPress admin

## Frontend Integration

### Shortcodes

The plugin provides several shortcodes to display components on your WordPress site:

#### Main Marketplace Shortcode

```
[marketplace_output]
```

This shortcode displays the main marketplace interface. You can customize the display by using parameters:

```
[marketplace_output type="investor_application" class="custom-class"]
```

**Parameters:**
- `type`: The display type (default, investor_application, investor_dashboard)
- `class`: Additional CSS classes to add to the container

#### Investor-specific Shortcodes

```
[vortex_investor_application]
[vortex_investor_dashboard]
```

These shortcodes display the investor application form and dashboard respectively.

### Gutenberg Blocks

The plugin also provides Gutenberg blocks for easier integration with the block editor:

1. In the editor, click the "+" button to add a new block
2. Search for "VORTEX Marketplace"
3. Select the block and choose the desired display type in the block settings

### Theme Compatibility

The plugin is designed to work with any WordPress theme. All frontend components use:

- Namespaced CSS classes to avoid conflicts
- Responsive design for all screen sizes
- Neutral color scheme that adapts to various themes
- Contained styles that don't leak into other page elements

### Demo Pages

Upon activation, the plugin creates the following pages:
- Investor Application
- Investor Dashboard

You can customize these pages or link to them from other parts of your site.

## Investor Features

### Investor Application

The plugin provides an investor application form that allows users to apply to invest in the marketplace. 

#### Usage

Add the investor application form to any page using the shortcode:

```
[vortex_investor_application]
```

Or use the Gutenberg block with "Investor Application" display type.

This will display a form where users can enter:
- Personal information (name, email, phone)
- Investment amount
- Wallet address (with automatic connection to Solana wallets)
- Agreement to terms and conditions

#### Configuration

The investment terms can be customized in the DAO settings page in the WordPress admin. Settings include:
- Minimum investment amount
- Token price
- Vesting period and cliff
- Governance voting power multipliers

### Investor Dashboard

Existing investors can view their investment details using the investor dashboard.

#### Usage

Add the investor dashboard to any page using the shortcode:

```
[vortex_investor_dashboard]
```

Or use the Gutenberg block with "Investor Dashboard" display type.

The dashboard displays:
- Investment summary with amount invested and current value
- Token allocation and vesting schedule visualization
- Transaction history
- Governance voting power
- Relevant documents

## Customization

### CSS Customization

To customize the appearance of the frontend components, you can add custom CSS to your theme:

```css
/* Example customization */
.marketplace-frontend-wrapper {
    /* Your custom styles */
}

.vortex-btn-primary {
    background-color: #your-theme-color;
}
```

### Template Customization

Advanced users can override the plugin templates by copying them to your theme directory:

1. Create a `marketplace` folder in your theme directory
2. Copy the template files you want to customize from the plugin's `templates` directory
3. Modify the copied templates as needed

The plugin will automatically use your customized templates instead of the default ones.

## Development

### Directory Structure

```
marketplace/
├── assets/
│   ├── css/
│   │   ├── marketplace-frontend.css        # Core frontend styles
│   │   ├── vortex-investor-application.css # Investor application styles
│   │   ├── vortex-investor-dashboard.css   # Investor dashboard styles
│   │   └── blocks/
│   │       └── marketplace-block-editor.css # Gutenberg block styles
│   ├── js/
│   │   ├── marketplace-frontend.js         # Core frontend JS
│   │   ├── vortex-solana-wallet.js        # Wallet integration
│   │   ├── vortex-dao.js                  # DAO functionality
│   │   └── blocks/
│   │       └── marketplace-block.js       # Gutenberg block JS
├── includes/
│   ├── class-vortex-marketplace-frontend.php # Main frontend controller
│   ├── dao/
│   │   ├── class-vortex-dao-investment.php
│   │   ├── class-vortex-dao-shortcodes.php
│   │   ├── class-vortex-dao-token.php
│   │   ├── partials/
│   │   │   └── investor-dashboard.php
│   │   └── templates/
│   │       ├── investor-agreement-template.php
│   │       └── investor-application-form.php
│   └── class-vortex-dao-manager.php
├── marketplace.php                         # Main plugin file
└── README.md                               # Documentation
```

### Extending

To extend the plugin functionality:

1. **Custom Fields**: Add custom fields to the application form by modifying `investor-application-form.php` and the corresponding AJAX handler in `class-vortex-dao-investment.php`.

2. **Additional Dashboard Widgets**: Extend the investor dashboard by adding new card components to `investor-dashboard.php`.

3. **Integration with Other Services**: Add integration with KYC providers or additional blockchain networks by creating new handler classes and adding them to the workflow.

4. **Custom Frontend Components**: Register new shortcodes and components by extending the `VORTEX_Marketplace_Frontend` class.

### Hooks and Filters

The plugin provides various hooks and filters to customize its behavior:

#### Actions

- `vortex_marketplace_register_frontend_modules`: Register custom frontend modules
- `vortex_marketplace_register_frontend_assets`: Register custom frontend assets
- `vortex_marketplace_register_shortcodes`: Register custom shortcodes
- `vortex_proposal_created`: Fired when a governance proposal is created
- `vortex_proposal_vote_cast`: Fired when a vote is cast on a proposal
- `vortex_proposal_finalized`: Fired when a proposal is finalized

#### Filters

- `vortex_dao_config`: Modify DAO configuration values
- `vortex_marketplace_localization_strings`: Modify localization strings
- `vortex_marketplace_frontend_display`: Modify frontend display output

## Cross-Theme Testing

The frontend components have been tested with the following themes:

- Twenty Twenty-Four
- Astra
- OceanWP
- Kadence
- GeneratePress

No compatibility issues were found with these themes. If you experience any issues with your specific theme, please contact support.

## Performance Considerations

To ensure optimal performance:

1. The plugin loads assets only on pages where they are needed
2. CSS and JavaScript files are minified in production
3. Front-end dependencies are kept to a minimum
4. Blockchain interactions are batched where possible

## License

This plugin is licensed under the GPL v2 or later.

## Credits

Developed by VORTEX AI Marketplace Team. 