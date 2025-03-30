/**
 * VORTEX AI Marketplace Blocks
 *
 * Main entry point for all Gutenberg blocks
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/blocks
 */

// WordPress dependencies
import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';

// Block registration
import * as marketplaceBlock from './marketplace';
import * as artworkGridBlock from './artwork-grid';
import * as artistGridBlock from './artist-grid';
import * as artworkSearchBlock from './artwork-search';
import * as shoppingCartBlock from './shopping-cart';
import * as checkoutBlock from './checkout';
import * as userDashboardBlock from './user-dashboard';
import * as artworkGeneratorBlock from './artwork-generator';

// Category registration
import { vortexIcon } from './icons';

wp.blocks.updateCategory('vortex-marketplace', {
    icon: vortexIcon,
});

// Register all blocks
const registerBlock = (blockData) => {
    const { name, settings } = blockData;
    registerBlockType(name, {
        category: 'vortex-marketplace',
        icon: vortexIcon,
        supports: {
            html: false,
            align: ['wide', 'full'],
        },
        ...settings,
    });
};

// Register all VORTEX blocks
[
    marketplaceBlock,
    artworkGridBlock,
    artistGridBlock,
    artworkSearchBlock,
    shoppingCartBlock,
    checkoutBlock,
    userDashboardBlock,
    artworkGeneratorBlock
].forEach(registerBlock); 