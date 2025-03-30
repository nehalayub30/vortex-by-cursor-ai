<?php
/**
 * Set up internationalization for VORTEX AI Marketplace
 * 
 * Ensures AI agents maintain deep learning capabilities across languages
 * 
 * @package VORTEX_AI_Marketplace
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Load plugin textdomain
 * 
 * @since 1.0.0
 */
function vortex_load_textdomain() {
    load_plugin_textdomain(
        'vortex-marketplace',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages'
    );
    
    // Ensure AI agents are aware of current language for proper deep learning
    $current_locale = get_locale();
    do_action('vortex_ai_language_context', $current_locale);
}
add_action('plugins_loaded', 'vortex_load_textdomain');

/**
 * Initialize AI agents with language context
 * 
 * @since 1.0.0
 * @param string $locale Current WordPress locale
 * @return void
 */
function vortex_init_ai_language_context($locale) {
    // Initialize HURAII with language awareness
    do_action('vortex_ai_agent_init', 'language_context', 
        array('HURAII', 'CLOE', 'BusinessStrategist'), 
        'active',
        array(
            'locale' => $locale,
            'context' => 'internationalization',
            'language_session' => uniqid('lang_')
        )
    );
    
    // Log language change for AI learning
    do_action('vortex_ai_interaction', 'language_change', array(
        'locale' => $locale,
        'direction' => is_rtl() ? 'rtl' : 'ltr'
    ), get_current_user_id());
}
add_action('vortex_ai_language_context', 'vortex_init_ai_language_context');

/**
 * Get localized welcome messages for AI agents
 * 
 * @since 1.0.0
 * @param string $agent_name AI agent name (HURAII, CLOE, BusinessStrategist)
 * @return array Welcome messages
 */
function vortex_get_ai_welcome_messages($agent_name) {
    $messages = array();
    
    switch ($agent_name) {
        case 'HURAII':
            $messages = array(
                __('Welcome to HURAII!', 'vortex-marketplace'),
                __('Let\'s create something amazing together.', 'vortex-marketplace'),
                __('Ready to bring your vision to life with Seed Art.', 'vortex-marketplace'),
                __('Your artistic journey begins now!', 'vortex-marketplace')
            );
            break;
            
        case 'CLOE':
            $messages = array(
                __('CLOE is here to curate your experience.', 'vortex-marketplace'),
                __('Discover amazing artwork tailored to your taste.', 'vortex-marketplace'),
                __('Let\'s explore the world of digital art together.', 'vortex-marketplace')
            );
            break;
            
        case 'BusinessStrategist':
            $messages = array(
                __('Ready to analyze market trends for you.', 'vortex-marketplace'),
                __('Let\'s optimize your art business strategy.', 'vortex-marketplace'),
                __('Discover new revenue opportunities in the art market.', 'vortex-marketplace')
            );
            break;
    }
    
    return $messages;
} 