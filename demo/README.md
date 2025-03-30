# VORTEX AI AGENTS: Artist Dashboard Demo

This demo showcases the artist experience within the VORTEX AI AGENTS plugin, featuring an interactive dashboard with TOLA gamification elements and HURAII generative AI capabilities.

## Overview

This demonstration simulates the experience of an artist named "Priya" using the VORTEX AI AGENTS platform to:

1. **Understand Market Position**: View analytics about market fit, audience growth, and optimal pricing
2. **Track Achievements**: Monitor progress through the TOLA gamification system
3. **Analyze Artistic Style**: Visualize style characteristics and distinctive elements
4. **Generate Artwork Concepts**: Use HURAII to create new artwork based on descriptions and style preferences
5. **View Audience Insights**: Examine collector segments and geographic distribution
6. **Receive Strategic Recommendations**: Get AI-powered suggestions for career development

## Setup Instructions

### Prerequisites

- WordPress installation with PHP 7.4+
- VORTEX AI AGENTS plugin installed and activated
- Web server with proper permissions to execute PHP

### Installation

1. **Copy the demo files to your WordPress site**:
   ```
   demo/
   ├── artist-experience.php
   ├── css/
   │   └── demo-styles.css
   ├── js/
   │   └── demo-scripts.js
   ├── img/
   │   ├── artist-avatar.jpg
   │   └── generated/
   │       ├── abstract-1.jpg
   │       ├── abstract-2.jpg
   │       └── ... (more demo images)
   └── README.md
   ```

2. **Create necessary directories**:
   ```bash
   mkdir -p wp-content/plugins/vortex-ai-agents/demo/css
   mkdir -p wp-content/plugins/vortex-ai-agents/demo/js
   mkdir -p wp-content/plugins/vortex-ai-agents/demo/img/generated
   ```

3. **Add sample images**:
   - Place a profile image at `img/artist-avatar.jpg`
   - Add sample generated artwork images in the `img/generated/` directory following this naming pattern:
     - abstract-1.jpg, abstract-2.jpg
     - impressionist-1.jpg, impressionist-2.jpg
     - cubist-1.jpg, cubist-2.jpg
     - surreal-1.jpg, surreal-2.jpg
     - minimal-1.jpg, minimal-2.jpg

4. **Register the demo page in WordPress**:
   Add the following code to your plugin's main file:

   ```php
   // Register a custom page for the Artist Experience Demo
   add_action('admin_menu', function() {
       add_menu_page(
           'Artist Experience Demo',
           'Artist Demo',
           'manage_options',
           'vortex-artist-demo',
           function() {
               include_once plugin_dir_path(__FILE__) . 'demo/artist-experience.php';
           },
           'dashicons-art',
           30
       );
   });
   ```

5. **Ensure Chart.js is available**:
   The demo will attempt to load Chart.js from CDN if it's not already available.

## Using the Demo

1. Navigate to the "Artist Demo" menu item in your WordPress admin menu
2. Explore the various panels in the dashboard:
   - Market Position
   - TOLA Achievements
   - Style Analysis
   - HURAII Creator
   - Audience Insights
   - Strategic Recommendations

3. **Interact with the HURAII Creator**:
   - Enter a concept description or use the provided default
   - Select an art style, medium, and artist influence
   - Choose the number of variations
   - Click "Generate Concepts" to see the AI-generated artwork
   - Download, refine, or save the generated concepts

4. **Experience TOLA Gamification**:
   - Generate artwork to earn TOLA points
   - Interact with recommendation buttons to earn additional points
   - Watch as achievements unlock when you reach point thresholds

## Demo Features

### Interactive Elements

- **HURAII Generator**: Creates artwork concepts based on user parameters
- **Achievement System**: Unlocks achievements as users reach point thresholds
- **TOLA Points**: Animated point counter that increases with user actions
- **Toast Notifications**: Pop-up messages for feedback and gamification events
- **Interactive Charts**: Visualizations of style analysis and audience segments

### Dashboard Panels

1. **Market Position**: Shows market fit score with a circular gauge and lists recent insights
2. **TOLA Achievements**: Displays earned and locked achievements with point values
3. **Style Analysis**: Radar chart comparing the artist's style to market trends
4. **HURAII Creator**: Form for generating AI artwork with various parameters
5. **Audience Insights**: Doughnut chart of collector segments and geographic distribution bars
6. **Strategic Recommendations**: Action cards with strategic career advice

## Customization

You can customize this demo by:

- Replacing demo images with your own generated artwork
- Adjusting the achievement data in `artist-experience.php`
- Modifying market insights and recommendations to fit your use case
- Changing the color scheme by editing CSS variables in `demo-styles.css`

## Technical Implementation

This demo showcases several technical implementations:

1. **SVG Animation**: The market fit gauge uses SVG with CSS animations
2. **Chart.js Integration**: Data visualization using Chart.js library
3. **Interactive HURAII Generation**: Simulated AI artwork generation
4. **TOLA Gamification System**: Points, achievements, and rewards
5. **Responsive Design**: Mobile-friendly dashboard layout
6. **Toast Notification System**: Custom notification display

## Notes for Developers

- This is a demonstration and does not include actual backend functionality
- In a real implementation, AJAX calls would be made to PHP endpoints
- The HURAII generation is simulated with preloaded images
- The achievement system would normally use database storage

## Troubleshooting

If you encounter issues:

1. Check browser console for JavaScript errors
2. Ensure all demo files are in the correct directories
3. Verify that Chart.js is loading properly
4. Check that sample images exist in the specified paths

For additional support, please contact the VORTEX AI AGENTS support team. 