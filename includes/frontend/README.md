# VORTEX Frontend Components

This directory contains all frontend-related components for the VORTEX Marketplace plugin. The structure has been consolidated from multiple directories for better organization and maintainability.

## Directory Structure

- `components/` - Reusable frontend components
- `shortcodes/` - Frontend shortcodes for embedding marketplace features
- `templates/` - Template files for rendering frontend displays

## Key Files

- `class-vortex-language-switcher.php` - Language switcher component for multilingual support
- Additional files for frontend rendering and user interaction

## Usage

When adding new frontend components:

1. Place them in the appropriate subdirectory
2. Follow the naming convention: `class-vortex-frontend-*.php` for class files
3. Update the main plugin file to load the new component

## Development

The frontend components follow a modular architecture to allow for:

- Easy extension and customization
- Theme compatibility
- Responsive design across devices

All components should be properly documented and tested before deployment. 