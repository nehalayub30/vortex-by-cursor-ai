# VORTEX Marketplace Shortcode Documentation Guide

This guide explains how to properly register and document shortcodes in the VORTEX Marketplace plugin.

## Introduction

The VORTEX Marketplace plugin uses a centralized shortcode registry system to:

1. Document all shortcodes in a consistent format
2. Make shortcodes discoverable for users
3. Track usage for analytics
4. Provide exportable documentation

## Registering a Shortcode

Instead of using WordPress's native `add_shortcode()` function, use the VORTEX Shortcode Registry:

```php
// Get the shortcode registry instance
$registry = VORTEX_Shortcode_Registry::get_instance();

// Register your shortcode with documentation
$registry->register('vortex_example', 'callback_function', [
    'name' => 'Example Shortcode',
    'description' => 'Displays an example element with customizable properties.',
    'usage' => '[vortex_example title="My Example" color="blue"]',
    'parameters' => [
        'title' => [
            'default' => '',
            'description' => 'The title to display above the element.'
        ],
        'color' => [
            'default' => 'blue',
            'description' => 'The color of the element. Options: blue, green, red.'
        ],
        'size' => [
            'default' => 'medium',
            'description' => 'The size of the element. Options: small, medium, large.'
        ]
    ],
    'examples' => [
        [
            'title' => 'Basic Usage',
            'description' => 'Simple example with default settings.',
            'code' => '[vortex_example]'
        ],
        [
            'title' => 'Custom Title and Color',
            'description' => 'Example with a custom title and red color.',
            'code' => '[vortex_example title="Custom Example" color="red"]'
        ]
    ],
    'category' => 'display',
    'since' => '1.2.0',
    'required_capabilities' => ['publish_posts']
]);
```

## Documentation Parameters

When registering a shortcode, include these documentation parameters:

| Parameter | Description | Required |
|-----------|-------------|----------|
| `name` | Human-readable name of the shortcode | Yes |
| `description` | Detailed description of what the shortcode does | Yes |
| `usage` | Example of how to use the shortcode with common parameters | Yes |
| `parameters` | Array of parameters the shortcode accepts | No |
| `examples` | Array of example usages with titles and descriptions | No |
| `category` | Category for grouping related shortcodes | Yes |
| `since` | Version when the shortcode was introduced | Yes |
| `required_capabilities` | WordPress capabilities required to use the shortcode | No |

### Parameter Format

For each parameter, include:

```php
'parameter_name' => [
    'default' => 'Default value',
    'description' => 'Description of what this parameter does.'
]
```

### Example Format

For each example, include:

```php
[
    'title' => 'Example Title',
    'description' => 'Description of what this example demonstrates.',
    'code' => '[vortex_example param="value"]'
]
```

## Categories

Organize shortcodes into these predefined categories:

- `marketplace` - Core marketplace features
- `artists` - Artist-related shortcodes
- `artworks` - Artwork display and management
- `blockchain` - Blockchain and token related features
- `dao` - DAO governance features
- `ai` - AI agent-related shortcodes
- `gamification` - Gamification features
- `display` - Visual elements and layout components
- `user` - User profiles and management
- `integration` - Third-party integrations
- `metrics` - Analytics and reporting
- `utility` - Helper and utility shortcodes

## Hooking into the Registry

To register your shortcodes at the appropriate time, use the `vortex_register_shortcodes` action:

```php
add_action('vortex_register_shortcodes', function($registry) {
    // Register your shortcodes here
    $registry->register(/* ... */);
});
```

## Documentation Page

All properly registered shortcodes will appear in the admin menu under:

**VORTEX AI > Shortcodes**

This page allows filtering by category and exporting documentation for reference.

## Best Practices

1. **Be thorough**: Include detailed descriptions and examples
2. **Categorize properly**: Use the most appropriate category
3. **Document all parameters**: Include every parameter with defaults
4. **Version tracking**: Update the 'since' value when adding parameters
5. **Include required capabilities**: If a shortcode requires specific permissions
6. **Examples matter**: Include multiple examples showing different use cases

## Creating Shortcode Documentation Content for Users

When creating general documentation for end users:

1. Use the shortcode documentation page to export HTML documentation
2. Screenshot examples of the shortcode output for visual reference
3. Group related shortcodes together in your documentation
4. Include real-world use cases that solve common problems

By following this guide, you ensure all VORTEX shortcodes are consistent, well-documented, and discoverable. 