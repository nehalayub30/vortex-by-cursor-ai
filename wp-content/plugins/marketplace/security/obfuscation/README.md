# Code Obfuscation Guidelines for Marketplace Plugin

This document outlines the process for obfuscating critical parts of the Marketplace plugin to protect intellectual property.

## Files to Obfuscate

The following critical files contain sensitive algorithms and should be prioritized for obfuscation:

- `includes/ai/class-vortex-ai-governance-advisor.php` - Contains AI prediction algorithms
- `includes/dao/class-vortex-dao-token.php` - Contains token calculation logic
- `includes/blockchain/class-vortex-tola-integration.php` - Contains blockchain integration logic

## Obfuscation Process

### Using IonCube Encoder

1. Install IonCube Encoder from https://www.ioncube.com/
2. Create a batch file to process the files:

```bash
#!/bin/bash
# Example batch script for IonCube encoding

# Set source and target directories
SOURCE_DIR="./wp-content/plugins/marketplace"
TARGET_DIR="./wp-content/plugins/marketplace-encoded"

# Ensure target directory exists
mkdir -p $TARGET_DIR

# IonCube encoder path (adjust as needed)
IONCUBE_PATH="/path/to/ioncube/encoder"

# Files to encode
$IONCUBE_PATH/ioncube_encoder --into $TARGET_DIR \
  --encrypt all --optimize max \
  --expire 365 \
  $SOURCE_DIR/includes/ai/class-vortex-ai-governance-advisor.php \
  $SOURCE_DIR/includes/dao/class-vortex-dao-token.php \
  $SOURCE_DIR/includes/blockchain/class-vortex-tola-integration.php

# Copy non-encoded files
cp -R $SOURCE_DIR/* $TARGET_DIR/
```

### Using Zend Guard

1. Install Zend Guard from https://www.zend.com/products/zend-guard
2. Use the GUI or command line to encode the files:

```bash
/path/to/zendguard/zendenc --target-dir=/path/to/target --keep-source --short-tags=Off \
  --obfuscation-level=highest --license-path=/path/to/license.zl \
  /path/to/source/includes/ai/class-vortex-ai-governance-advisor.php
```

### Using SourceGuardian

1. Install SourceGuardian from https://www.sourceguardian.com/
2. Encode the files:

```bash
/path/to/sourceguardian/ixedsgl --output encoded-files/ --with-loader --loader-path loaders/ \
  --php81 --license-check \
  includes/ai/class-vortex-ai-governance-advisor.php \
  includes/dao/class-vortex-dao-token.php
```

## Server Requirements

- For IonCube: Install the IonCube Loader extension for PHP (https://www.ioncube.com/loaders.php)
- For Zend Guard: Install Zend Loader (https://www.zend.com/products/zend-guard)
- For SourceGuardian: Install the appropriate SourceGuardian loader for your PHP version

## Integration Notes

1. Once encoded, use the encoded files in your production environment. 
2. Keep original files in a secure location for future development.
3. Update plugin with encoded files before distribution.
4. Include the appropriate loader verification in your plugin:

```php
<?php
// Check if the loader is installed
if (!extension_loaded('ionCube Loader')) {
    die('The ionCube loader is not installed. Please contact your hosting provider to install it.');
}
?>
```

## Important Security Considerations

- Obfuscation is not unbreakable but adds a significant barrier to reverse engineering
- Combine with other security measures (API endpoints, compiled extensions) for layered security
- Regular re-obfuscation with updated encoders increases security
- Different obfuscation keys/settings for different clients can help track unauthorized distribution 