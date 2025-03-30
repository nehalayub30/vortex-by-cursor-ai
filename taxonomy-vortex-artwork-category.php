<?php
/**
 * The template for displaying Artwork Category taxonomy archives
 *
 * @package VORTEX_AI_Marketplace
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

get_header();

// Get current category
$current_category = get_queried_object();

// Get user's info and status
$user_id = get_current_user_id();
$is_new_user = $user_id ? get_user_meta($user_id, 'vortex_registration_completed', true) !== 'yes' : false;
$onboarding_step = $user_id ? get_user_meta($user_id, 'vortex_onboarding_step', true) : '';
$has_business_plan = $user_id ? get_user_meta($user_id, 'vortex_business_plan_created', true) === 'yes' : false;
$business_plan_data = $user_id ? get_user_meta($user_id, 'vortex_business_plan', true) : array();
$upcoming_milestones = $user_id ? apply_filters('vortex_get_upcoming_milestones', array('user_id' => $user_id, 'limit' => 3)) : array();

// Initialize AI agents with their specialized capabilities
do_action('vortex_ai_agent_init', array(
    'context' => 'artwork_category',
    'agents' => array('HURAII', 'CLOE', 'BusinessStrategist'),
    'learning_mode' => 'active',
    'category_id' => $current_category->term_id
));

// Get Business Strategist market analysis for this category
$market_analysis = apply_filters('vortex_ai_get_business_insights', array(
    'term_id' => $current_category->term_id,
    'taxonomy' => $current_category->taxonomy,
    'analysis_type' => 'market_trends'
));

// Get CLOE's curated exhibition recommendations for this category
$curated_selections = apply_filters('vortex_ai_get_cloe_curation', array(
    'category_id' => $current_category->term_id,
    'curation_type' => 'thematic_showcase',
    'limit' => 4
));

// Get HURAII-generated artwork samples and seed art analysis based on this category's style
$ai_generated_samples = apply_filters('vortex_ai_get_huraii_samples', array(
    'category_id' => $current_category->term_id,
    'generation_type' => 'category_inspired',
    'seed_art_analysis' => true, // Enable Seed Art technique analysis
    'limit' => 3
));

// Get CLOE's personalized recommendations for current user
$personalized_gallery = apply_filters('vortex_ai_get_cloe_recommendations', array(
    'category_id' => $current_category->term_id,
    'user_id' => $user_id ?: 0,
    'limit' => 3
));

// Get Business Strategist's opportunities related to this category
$business_opportunities = apply_filters('vortex_ai_get_opportunities', array(
    'category_id' => $current_category->term_id,
    'opportunity_types' => array('exhibition', 'competition', 'collaboration')
));

// Track category view for AI learning
do_action('vortex_ai_track_interaction', array(
    'entity_type' => 'category',
    'entity_id' => $current_category->term_id,
    'action' => 'view',
    'user_id' => $user_id ?: 0
));

// Update user's last visit timestamp
if ($user_id) {
    update_user_meta($user_id, 'vortex_last_visit', current_time('timestamp'));
}

// Get current user's artist type (painter, sculptor, photographer, etc.)
$user_artist_type = $user_id ? get_user_meta($user_id, 'vortex_artist_type', true) : '';

// Get CLOE's internet trend analysis for this category and artist type
$internet_trends = apply_filters('vortex_ai_get_cloe_internet_trends', array(
    'category_id' => $current_category->term_id,
    'artist_type' => $user_artist_type,
    'limit' => 3
));

// Get CLOE's collector-artist matching recommendations
$collector_matches = $user_id ? apply_filters('vortex_ai_get_cloe_collector_matches', array(
    'user_id' => $user_id,
    'artist_type' => $user_artist_type,
    'category_id' => $current_category->term_id,
    'limit' => 3
)) : array();

// Get CLOE's marketing recommendations for the artist
$marketing_recommendations = $user_id ? apply_filters('vortex_ai_get_cloe_marketing_recommendations', array(
    'user_id' => $user_id,
    'artist_type' => $user_artist_type,
    'category_id' => $current_category->term_id,
    'limit' => 3
)) : array();

// Check if we need to show CLOE's welcome/registration assistant
$show_cloe_welcome = (!$user_id || $is_new_user);

// Check if we need to show BusinessStrategist's questionnaire
$show_business_strategist_questionnaire = ($user_id && $onboarding_step === 'business_questionnaire');

// Get CLOE's welcome message
$cloe_welcome = apply_filters('vortex_ai_get_cloe_welcome', array(
    'user_id' => $user_id ?: 0,
    'is_new_user' => $is_new_user,
    'onboarding_step' => $onboarding_step
));

// Get BusinessStrategist's questionnaire
$business_questionnaire = apply_filters('vortex_ai_get_business_questionnaire', array(
    'user_id' => $user_id ?: 0,
    'step' => isset($_GET['questionnaire_step']) ? intval($_GET['questionnaire_step']) : 1
));
?>

<div id="primary" class="vortex-content-area vortex-category-archive">
    <main id="main" class="vortex-site-main">
        
        <?php if ($show_cloe_welcome && !empty($cloe_welcome)) : ?>
            <div class="vortex-cloe-welcome-modal" id="cloeWelcomeModal">
                <div class="vortex-cloe-welcome-content">
                    <div class="vortex-cloe-welcome-header">
                        <div class="vortex-cloe-avatar">
                            <img src="<?php echo esc_url(VORTEX_PLUGIN_URL . 'assets/images/cloe-avatar.png'); ?>" alt="CLOE" />
                        </div>
                        <div class="vortex-cloe-identity">
                            <h2><?php esc_html_e('Welcome to VORTEX', 'vortex'); ?></h2>
                            <p><?php esc_html_e('I\'m CLOE, your AI host and guide', 'vortex'); ?></p>
                        </div>
                    </div>
                    
                    <div class="vortex-cloe-welcome-message">
                        <p><?php echo esc_html($cloe_welcome['greeting']); ?></p>
                        <?php echo wp_kses_post($cloe_welcome['message']); ?>
                    </div>
                    
                    <?php if (empty($user_id)) : // Not logged in, show registration form ?>
                        <div class="vortex-registration-container" id="cloeRegistrationForm">
                            <h3><?php esc_html_e('Let\'s Get Started', 'vortex'); ?></h3>
                            <p><?php esc_html_e('Create your account to unlock the full potential of the VORTEX AI Marketplace', 'vortex'); ?></p>
                            
                            <form id="vortexRegistrationForm" class="vortex-step-form" data-step="1">
                                <div class="vortex-form-step" data-step="1">
                                    <div class="vortex-form-field">
                                        <label for="reg_email"><?php esc_html_e('Email address', 'vortex'); ?> *</label>
                                        <input type="email" name="email" id="reg_email" required />
                                    </div>
                                    <div class="vortex-form-field">
                                        <label for="reg_username"><?php esc_html_e('Username', 'vortex'); ?> *</label>
                                        <input type="text" name="username" id="reg_username" required />
                                    </div>
                                    <div class="vortex-form-field">
                                        <label for="reg_password"><?php esc_html_e('Password', 'vortex'); ?> *</label>
                                        <input type="password" name="password" id="reg_password" required />
                                    </div>
                                    <div class="vortex-form-actions">
                                        <button type="button" class="vortex-next-step"><?php esc_html_e('Next', 'vortex'); ?></button>
                                    </div>
                                </div>
                                
                                <div class="vortex-form-step" data-step="2" style="display: none;">
                                    <div class="vortex-form-field">
                                        <label for="reg_first_name"><?php esc_html_e('First Name', 'vortex'); ?> *</label>
                                        <input type="text" name="first_name" id="reg_first_name" required />
                                    </div>
                                    <div class="vortex-form-field">
                                        <label for="reg_last_name"><?php esc_html_e('Last Name', 'vortex'); ?> *</label>
                                        <input type="text" name="last_name" id="reg_last_name" required />
                                    </div>
                                    <div class="vortex-form-field">
                                        <label for="reg_user_type"><?php esc_html_e('I am a:', 'vortex'); ?> *</label>
                                        <select name="user_type" id="reg_user_type" required>
                                            <option value=""><?php esc_html_e('Select...', 'vortex'); ?></option>
                                            <option value="artist"><?php esc_html_e('Artist', 'vortex'); ?></option>
                                            <option value="collector"><?php esc_html_e('Collector', 'vortex'); ?></option>
                                            <option value="gallery"><?php esc_html_e('Gallery Owner', 'vortex'); ?></option>
                                            <option value="business"><?php esc_html_e('Business', 'vortex'); ?></option>
                                        </select>
                                    </div>
                                    <div class="vortex-form-actions">
                                        <button type="button" class="vortex-prev-step"><?php esc_html_e('Back', 'vortex'); ?></button>
                                        <button type="button" class="vortex-next-step"><?php esc_html_e('Next', 'vortex'); ?></button>
                                    </div>
                                </div>
                                
                                <div class="vortex-form-step" data-step="3" style="display: none;">
                                    <div class="vortex-form-field">
                                        <label for="reg_interests"><?php esc_html_e('My interests include:', 'vortex'); ?> *</label>
                                        <div class="vortex-checkbox-group">
                                            <label><input type="checkbox" name="interests[]" value="painting" /> <?php esc_html_e('Painting', 'vortex'); ?></label>
                                            <label><input type="checkbox" name="interests[]" value="sculpture" /> <?php esc_html_e('Sculpture', 'vortex'); ?></label>
                                            <label><input type="checkbox" name="interests[]" value="photography" /> <?php esc_html_e('Photography', 'vortex'); ?></label>
                                            <label><input type="checkbox" name="interests[]" value="digital_art" /> <?php esc_html_e('Digital Art', 'vortex'); ?></label>
                                            <label><input type="checkbox" name="interests[]" value="ai_art" /> <?php esc_html_e('AI-Generated Art', 'vortex'); ?></label>
                                            <label><input type="checkbox" name="interests[]" value="mixed_media" /> <?php esc_html_e('Mixed Media', 'vortex'); ?></label>
                                        </div>
                                    </div>
                                    <div class="vortex-form-field">
                                        <label for="reg_goals"><?php esc_html_e('What are your primary goals?', 'vortex'); ?> *</label>
                                        <select name="primary_goal" id="reg_goals" required>
                                            <option value=""><?php esc_html_e('Select...', 'vortex'); ?></option>
                                            <option value="sell_art"><?php esc_html_e('Sell my artwork', 'vortex'); ?></option>
                                            <option value="collect_art"><?php esc_html_e('Collect artwork', 'vortex'); ?></option>
                                            <option value="learning"><?php esc_html_e('Learn new techniques', 'vortex'); ?></option>
                                            <option value="networking"><?php esc_html_e('Network with other artists', 'vortex'); ?></option>
                                            <option value="business"><?php esc_html_e('Grow my art business', 'vortex'); ?></option>
                                        </select>
                                    </div>
                                    <div class="vortex-form-actions">
                                        <button type="button" class="vortex-prev-step"><?php esc_html_e('Back', 'vortex'); ?></button>
                                        <button type="submit" class="vortex-submit-registration"><?php esc_html_e('Create Account', 'vortex'); ?></button>
                                    </div>
                                    <div class="vortex-form-note">
                                        <p><?php esc_html_e('By registering, you agree to our Terms & Privacy Policy', 'vortex'); ?></p>
                                    </div>
                                </div>
                            </form>
                            
                            <div class="vortex-login-option">
                                <p><?php esc_html_e('Already have an account?', 'vortex'); ?> <a href="<?php echo esc_url(wp_login_url()); ?>"><?php esc_html_e('Log in', 'vortex'); ?></a></p>
                            </div>
                        </div>
                    <?php elseif ($is_new_user) : // Logged in but new user, show onboarding steps ?>
                        <div class="vortex-onboarding-container">
                            <h3><?php esc_html_e('Let\'s Complete Your Profile', 'vortex'); ?></h3>
                            
                            <div class="vortex-onboarding-steps">
                                <div class="vortex-step-indicator <?php echo $onboarding_step === 'profile' ? 'active' : ($onboarding_step === 'interests' || $onboarding_step === 'business_questionnaire' ? 'completed' : ''); ?>">
                                    <span class="vortex-step-number">1</span>
                                    <span class="vortex-step-label"><?php esc_html_e('Profile', 'vortex'); ?></span>
                                </div>
                                <div class="vortex-step-indicator <?php echo $onboarding_step === 'interests' ? 'active' : ($onboarding_step === 'business_questionnaire' ? 'completed' : ''); ?>">
                                    <span class="vortex-step-number">2</span>
                                    <span class="vortex-step-label"><?php esc_html_e('Interests', 'vortex'); ?></span>
                                </div>
                                <div class="vortex-step-indicator <?php echo $onboarding_step === 'business_questionnaire' ? 'active' : ''; ?>">
                                    <span class="vortex-step-number">3</span>
                                    <span class="vortex-step-label"><?php esc_html_e('Business Plan', 'vortex'); ?></span>
                                </div>
                            </div>
                            
                            <?php if ($onboarding_step === 'profile') : ?>
                                <form id="vortexProfileForm" class="vortex-onboarding-form">
                                    <div class="vortex-form-field">
                                        <label for="profile_bio"><?php esc_html_e('Bio', 'vortex'); ?> *</label>
                                        <textarea name="bio" id="profile_bio" rows="4" required></textarea>
                                    </div>
                                    <div class="vortex-form-field">
                                        <label for="profile_website"><?php esc_html_e('Website', 'vortex'); ?></label>
                                        <input type="url" name="website" id="profile_website" />
                                    </div>
                                    <div class="vortex-form-field">
                                        <label for="profile_social"><?php esc_html_e('Social Media Links', 'vortex'); ?></label>
                                        <input type="text" name="social_media" id="profile_social" placeholder="Instagram, Twitter, etc." />
                                    </div>
                                    <div class="vortex-form-actions">
                                        <button type="submit" class="vortex-submit-profile"><?php esc_html_e('Save & Continue', 'vortex'); ?></button>
                                    </div>
                                </form>
                            <?php elseif ($onboarding_step === 'interests') : ?>
                                <form id="vortexInterestsForm" class="vortex-onboarding-form">
                                    <div class="vortex-form-field">
                                        <label><?php esc_html_e('What type of art do you create or collect?', 'vortex'); ?> *</label>
                                        <div class="vortex-checkbox-group">
                                            <label><input type="checkbox" name="art_types[]" value="painting" /> <?php esc_html_e('Painting', 'vortex'); ?></label>
                                            <label><input type="checkbox" name="art_types[]" value="sculpture" /> <?php esc_html_e('Sculpture', 'vortex'); ?></label>
                                            <label><input type="checkbox" name="art_types[]" value="photography" /> <?php esc_html_e('Photography', 'vortex'); ?></label>
                                            <label><input type="checkbox" name="art_types[]" value="digital_art" /> <?php esc_html_e('Digital Art', 'vortex'); ?></label>
                                            <label><input type="checkbox" name="art_types[]" value="ai_art" /> <?php esc_html_e('AI-Generated Art', 'vortex'); ?></label>
                                            <label><input type="checkbox" name="art_types[]" value="mixed_media" /> <?php esc_html_e('Mixed Media', 'vortex'); ?></label>
                                        </div>
                                    </div>
                                    <div class="vortex-form-field">
                                        <label><?php esc_html_e('What styles are you interested in?', 'vortex'); ?> *</label>
                                        <div class="vortex-checkbox-group">
                                            <label><input type="checkbox" name="art_styles[]" value="abstract" /> <?php esc_html_e('Abstract', 'vortex'); ?></label>
                                            <label><input type="checkbox" name="art_styles[]" value="contemporary" /> <?php esc_html_e('Contemporary', 'vortex'); ?></label>
                                            <label><input type="checkbox" name="art_styles[]" value="impressionism" /> <?php esc_html_e('Impressionism', 'vortex'); ?></label>
                                            <label><input type="checkbox" name="art_styles[]" value="minimalism" /> <?php esc_html_e('Minimalism', 'vortex'); ?></label>
                                            <label><input type="checkbox" name="art_styles[]" value="surrealism" /> <?php esc_html_e('Surrealism', 'vortex'); ?></label>
                                            <label><input type="checkbox" name="art_styles[]" value="pop_art" /> <?php esc_html_e('Pop Art', 'vortex'); ?></label>
                                        </div>
                                    </div>
                                    <div class="vortex-form-actions">
                                        <button type="submit" class="vortex-submit-interests"><?php esc_html_e('Save & Continue', 'vortex'); ?></button>
                                    </div>
                                </form>
                            <?php endif; ?>
                            
                            <div class="vortex-cloe-next-steps">
                                <p><?php esc_html_e('After completing these steps, our Business Strategist AI will help you create a personalized 30-day plan.', 'vortex'); ?></p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($show_business_strategist_questionnaire && !empty($business_questionnaire)) : ?>
            <div class="vortex-business-strategist-modal" id="businessStrategistModal">
                <div class="vortex-business-strategist-content">
                    <div class="vortex-business-strategist-header">
                        <div class="vortex-business-strategist-avatar">
                            <img src="<?php echo esc_url(VORTEX_PLUGIN_URL . 'assets/images/business-strategist-avatar.png'); ?>" alt="Business Strategist" />
                        </div>
                        <div class="vortex-business-strategist-identity">
                            <h2><?php esc_html_e('Business Strategist', 'vortex'); ?></h2>
                            <p><?php esc_html_e('Your AI Business Planning Assistant', 'vortex'); ?></p>
                        </div>
                    </div>
                    
                    <div class="vortex-business-strategist-message">
                        <p><?php echo esc_html($business_questionnaire['greeting']); ?></p>
                        <?php echo wp_kses_post($business_questionnaire['introduction']); ?>
                    </div>
                    
                    <div class="vortex-questionnaire-container">
                        <div class="vortex-questionnaire-progress">
                            <div class="vortex-progress-bar">
                                <div class="vortex-progress-fill" style="width: <?php echo esc_attr($business_questionnaire['progress_percentage']); ?>%"></div>
                            </div>
                            <div class="vortex-progress-text">
                                <?php echo esc_html(sprintf(__('Question %d of %d', 'vortex'), $business_questionnaire['current_step'], $business_questionnaire['total_steps'])); ?>
                            </div>
                        </div>
                        
                        <form id="vortexBusinessQuestionnaireForm" class="vortex-questionnaire-form" data-step="<?php echo esc_attr($business_questionnaire['current_step']); ?>">
                            <input type="hidden" name="questionnaire_step" value="<?php echo esc_attr($business_questionnaire['current_step']); ?>" />
                            
                            <div class="vortex-question-container">
                                <h3 class="vortex-question-title"><?php echo esc_html($business_questionnaire['current_question']['title']); ?></h3>
                                
                                <?php if (!empty($business_questionnaire['current_question']['description'])) : ?>
                                    <p class="vortex-question-description"><?php echo esc_html($business_questionnaire['current_question']['description']); ?></p>
                                <?php endif; ?>
                                
                                <div class="vortex-question-field">
                                    <?php switch ($business_questionnaire['current_question']['type']) :
                                        case 'text': ?>
                                            <input type="text" name="<?php echo esc_attr($business_questionnaire['current_question']['id']); ?>" id="question_<?php echo esc_attr($business_questionnaire['current_question']['id']); ?>" <?php echo $business_questionnaire['current_question']['required'] ? 'required' : ''; ?> />
                                            <?php break;
                                        case 'textarea': ?>
                                            <textarea name="<?php echo esc_attr($business_questionnaire['current_question']['id']); ?>" id="question_<?php echo esc_attr($business_questionnaire['current_question']['id']); ?>" rows="4" <?php echo $business_questionnaire['current_question']['required'] ? 'required' : ''; ?>></textarea>
                                            <?php break;
                                        case 'select': ?>
                                            <select name="<?php echo esc_attr($business_questionnaire['current_question']['id']); ?>" id="question_<?php echo esc_attr($business_questionnaire['current_question']['id']); ?>" <?php echo $business_questionnaire['current_question']['required'] ? 'required' : ''; ?>>
                                                <option value=""><?php esc_html_e('Select...', 'vortex'); ?></option>
                                                <?php foreach ($business_questionnaire['current_question']['options'] as $option_value => $option_label) : ?>
                                                    <option value="<?php echo esc_attr($option_value); ?>"><?php echo esc_html($option_label); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <?php break;
                                        case 'radio': ?>
                                            <div class="vortex-radio-group">
                                                <?php foreach ($business_questionnaire['current_question']['options'] as $option_value => $option_label) : ?>
                                                    <label>
                                                        <input type="radio" name="<?php echo esc_attr($business_questionnaire['current_question']['id']); ?>" value="<?php echo esc_attr($option_value); ?>" <?php echo $business_questionnaire['current_question']['required'] ? 'required' : ''; ?> />
                                                        <?php echo esc_html($option_label); ?>
                                                    </label>
                                                <?php endforeach; ?>
                                            </div>
                                            <?php break;
                                        case 'checkbox': ?>
                                            <div class="vortex-checkbox-group">
                                                <?php foreach ($business_questionnaire['current_question']['options'] as $option_value => $option_label) : ?>
                                                    <label>
                                                        <input type="checkbox" name="<?php echo esc_attr($business_questionnaire['current_question']['id']); ?>[]" value="<?php echo esc_attr($option_value); ?>" />
                                                        <?php echo esc_html($option_label); ?>
                                                    </label>
                                                <?php endforeach; ?>
                                            </div>
                                            <?php break;
                                        case 'range': ?>
                                            <div class="vortex-range-field">
                                                <input type="range" name="<?php echo esc_attr($business_questionnaire['current_question']['id']); ?>" id="question_<?php echo esc_attr($business_questionnaire['current_question']['id']); ?>" min="<?php echo esc_attr($business_questionnaire['current_question']['min']); ?>" max="<?php echo esc_attr($business_questionnaire['current_question']['max']); ?>" step="<?php echo esc_attr($business_questionnaire['current_question']['step']); ?>" value="<?php echo esc_attr($business_questionnaire['current_question']['default']); ?>" <?php echo $business_questionnaire['current_question']['required'] ? 'required' : ''; ?> />
                                                <div class="vortex-range-labels">
                                                    <span><?php echo esc_html($business_questionnaire['current_question']['min_label']); ?></span>
                                                    <span><?php echo esc_html($business_questionnaire['current_question']['max_label']); ?></span>
                                                </div>
                                                <div class="vortex-range-value">
                                                    <span id="question_<?php echo esc_attr($business_questionnaire['current_question']['id']); ?>_value"><?php echo esc_html($business_questionnaire['current_question']['default']); ?></span>
                                                </div>
                                            </div>
                                            <?php break;
                                    endswitch; ?>
                                </div>
                            </div>
                            
                            <div class="vortex-form-actions">
                                <?php if ($business_questionnaire['current_step'] > 1) : ?>
                                    <button type="button" class="vortex-prev-question"><?php esc_html_e('Previous', 'vortex'); ?></button>
                                <?php endif; ?>
                                
                                <?php if ($business_questionnaire['current_step'] < $business_questionnaire['total_steps']) : ?>
                                    <button type="submit" class="vortex-next-question"><?php esc_html_e('Next', 'vortex'); ?></button>
                                <?php else : ?>
                                    <button type="submit" class="vortex-finish-questionnaire"><?php esc_html_e('Finish & Create My Business Plan', 'vortex'); ?></button>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($user_id && $has_business_plan) : ?>
            <div class="vortex-business-plan-summary">
                <div class="vortex-business-plan-header">
                    <div class="vortex-business-strategist-avatar">
                        <img src="<?php echo esc_url(VORTEX_PLUGIN_URL . 'assets/images/business-strategist-avatar.png'); ?>" alt="Business Strategist" />
                    </div>
                    <div class="vortex-business-plan-title">
                        <h3><?php esc_html_e('Your 30-Day Business Plan', 'vortex'); ?></h3>
                        <p><?php esc_html_e('Created by Business Strategist AI', 'vortex'); ?></p>
                    </div>
                    <div class="vortex-business-plan-actions">
                        <a href="<?php echo esc_url(home_url('/my-account/business-plan/')); ?>" class="vortex-view-full-plan"><?php esc_html_e('View Full Plan', 'vortex'); ?></a>
                    </div>
                </div>
                
                <?php if (!empty($upcoming_milestones)) : ?>
                    <div class="vortex-upcoming-milestones">
                        <h4><?php esc_html_e('Upcoming Milestones', 'vortex'); ?></h4>
                        <div class="vortex-milestones-list">
                            <?php foreach ($upcoming_milestones as $milestone) : ?>
                                <div class="vortex-milestone-item <?php echo esc_attr($milestone['status']); ?>">
                                    <div class="vortex-milestone-date">
                                        <span class="vortex-day"><?php echo esc_html(date_i18n('d', strtotime($milestone['due_date']))); ?></span>
                                        <span class="vortex-month"><?php echo esc_html(date_i18n('M', strtotime($milestone['due_date']))); ?></span>
                                    </div>
                                    <div class="vortex-milestone-content">
                                        <h5><?php echo esc_html($milestone['title']); ?></h5>
                                        <p><?php echo esc_html($milestone['description']); ?></p>
                                        <?php if ($milestone['status'] === 'upcoming') : ?>
                                            <a href="<?php echo esc_url($milestone['action_url']); ?>" class="vortex-milestone-action"><?php echo esc_html($milestone['action_text']); ?></a>
                                        <?php elseif ($milestone['status'] === 'completed') : ?>
                                            <span class="vortex-milestone-completed"><?php esc_html_e('Completed', 'vortex'); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="vortex-business-plan-progress">
                    <div class="vortex-progress-label">
                        <span><?php esc_html_e('Plan Progress', 'vortex'); ?></span>
                        <span class="vortex-progress-percentage"><?php echo esc_html($business_plan_data['progress_percentage']); ?>%</span>
                    </div>
                    <div class="vortex-progress-bar">
                        <div class="vortex-progress-fill" style="width: <?php echo esc_attr($business_plan_data['progress_percentage']); ?>%"></div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <header class="vortex-category-header">
            <h1 class="vortex-category-title"><?php echo esc_html($current_category->name); ?></h1>
            
            <?php if (!empty($current_category->description)) : ?>
                <div class="vortex-category-description">
                    <?php echo wp_kses_post($current_category->description); ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($market_analysis)) : ?>
                <div class="vortex-market-analysis">
                    <div class="vortex-insight-content">
                        <h3><?php esc_html_e('Art Market Analysis', 'vortex'); ?></h3>
                        <p><?php echo esc_html($market_analysis['summary']); ?></p>
                        
                        <?php if (!empty($market_analysis['key_indicators'])) : ?>
                            <div class="vortex-market-indicators">
                                <?php foreach ($market_analysis['key_indicators'] as $indicator) : ?>
                                    <div class="vortex-indicator">
                                        <span class="vortex-indicator-label"><?php echo esc_html($indicator['label']); ?>:</span>
                                        <span class="vortex-indicator-value <?php echo esc_attr($indicator['trend']); ?>">
                                            <?php echo esc_html($indicator['value']); ?>
                                            
                                            <?php if ($indicator['trend'] === 'up') : ?>
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16"><path d="M4 12l1.41 1.41L11 7.83V20h2V7.83l5.58 5.59L20 12l-8-8-8 8z" fill="currentColor"/></svg>
                                            <?php elseif ($indicator['trend'] === 'down') : ?>
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16"><path d="M20 12l-1.41-1.41L13 16.17V4h-2v12.17l-5.58-5.59L4 12l8 8 8-8z" fill="currentColor"/></svg>
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="vortex-ai-label">
                        <?php esc_html_e('Market analysis by Business Strategist AI', 'vortex'); ?>
                    </div>
                </div>
            <?php endif; ?>
        </header>

        <?php if (!empty($ai_generated_samples)) : ?>
            <section class="vortex-huraii-showcase">
                <h2><?php esc_html_e('AI Art Inspirations', 'vortex'); ?></h2>
                <p class="vortex-showcase-description">
                    <?php esc_html_e('HURAII-generated artwork samples inspired by this category\'s style.', 'vortex'); ?>
                </p>
                <div class="vortex-huraii-grid">
                    <?php foreach ($ai_generated_samples as $sample) : ?>
                        <div class="vortex-huraii-item">
                            <div class="vortex-sample-image">
                                <img src="<?php echo esc_url($sample['image_url']); ?>" alt="<?php echo esc_attr($sample['prompt']); ?>">
                            </div>
                            <div class="vortex-sample-details">
                                <h3><?php echo esc_html($sample['title']); ?></h3>
                                <div class="vortex-prompt">
                                    <span class="vortex-prompt-label"><?php esc_html_e('Prompt:', 'vortex'); ?></span>
                                    <span class="vortex-prompt-text"><?php echo esc_html($sample['prompt']); ?></span>
                                </div>
                                
                                <?php if (!empty($sample['seed_art_analysis'])) : ?>
                                    <div class="vortex-seed-art-analysis">
                                        <h4><?php esc_html_e('Seed Art Analysis', 'vortex'); ?></h4>
                                        <div class="vortex-seed-art-components">
                                            <?php if (!empty($sample['seed_art_analysis']['sacred_geometry'])) : ?>
                                                <div class="vortex-seed-component">
                                                    <span class="vortex-component-label"><?php esc_html_e('Sacred Geometry:', 'vortex'); ?></span>
                                                    <span class="vortex-component-value"><?php echo esc_html($sample['seed_art_analysis']['sacred_geometry']); ?></span>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <?php if (!empty($sample['seed_art_analysis']['color_weight'])) : ?>
                                                <div class="vortex-seed-component">
                                                    <span class="vortex-component-label"><?php esc_html_e('Color Weight:', 'vortex'); ?></span>
                                                    <span class="vortex-component-value"><?php echo esc_html($sample['seed_art_analysis']['color_weight']); ?></span>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <?php if (!empty($sample['seed_art_analysis']['light_shadow'])) : ?>
                                                <div class="vortex-seed-component">
                                                    <span class="vortex-component-label"><?php esc_html_e('Light & Shadow:', 'vortex'); ?></span>
                                                    <span class="vortex-component-value"><?php echo esc_html($sample['seed_art_analysis']['light_shadow']); ?></span>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <?php if (!empty($sample['seed_art_analysis']['texture'])) : ?>
                                                <div class="vortex-seed-component">
                                                    <span class="vortex-component-label"><?php esc_html_e('Texture:', 'vortex'); ?></span>
                                                    <span class="vortex-component-value"><?php echo esc_html($sample['seed_art_analysis']['texture']); ?></span>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <?php if (!empty($sample['seed_art_analysis']['perspective'])) : ?>
                                                <div class="vortex-seed-component">
                                                    <span class="vortex-component-label"><?php esc_html_e('Perspective:', 'vortex'); ?></span>
                                                    <span class="vortex-component-value"><?php echo esc_html($sample['seed_art_analysis']['perspective']); ?></span>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <?php if (!empty($sample['seed_art_analysis']['artwork_size'])) : ?>
                                                <div class="vortex-seed-component">
                                                    <span class="vortex-component-label"><?php esc_html_e('Artwork Size:', 'vortex'); ?></span>
                                                    <span class="vortex-component-value"><?php echo esc_html($sample['seed_art_analysis']['artwork_size']); ?></span>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <?php if (!empty($sample['seed_art_analysis']['movement_layering'])) : ?>
                                                <div class="vortex-seed-component vortex-movement-layering">
                                                    <span class="vortex-component-label"><?php esc_html_e('Movement & Layering:', 'vortex'); ?></span>
                                                    <span class="vortex-component-value"><?php echo esc_html($sample['seed_art_analysis']['movement_layering']); ?></span>
                                                    
                                                    <?php if (!empty($sample['seed_art_analysis']['layer_count'])) : ?>
                                                        <div class="vortex-layer-details">
                                                            <span class="vortex-layer-count"><?php echo esc_html(sprintf(__('Layers: %d', 'vortex'), $sample['seed_art_analysis']['layer_count'])); ?></span>
                                                        </div>
                                                    <?php endif; ?>
                                                    
                                                    <?php if (!empty($sample['seed_art_analysis']['transparency_analysis'])) : ?>
                                                        <div class="vortex-transparency-details">
                                                            <span class="vortex-transparency-level"><?php echo esc_html(sprintf(__('Transparency: %s', 'vortex'), $sample['seed_art_analysis']['transparency_analysis'])); ?></span>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <?php if (!empty($sample['seed_art_analysis']['art_history_context'])) : ?>
                                            <div class="vortex-art-history">
                                                <h4><?php esc_html_e('Art History Context', 'vortex'); ?></h4>
                                                <p><?php echo esc_html($sample['seed_art_analysis']['art_history_context']); ?></p>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($sample['seed_art_analysis']['efficiency_analysis'])) : ?>
                                            <div class="vortex-efficiency-analysis">
                                                <h4><?php esc_html_e('Efficiency Analysis', 'vortex'); ?></h4>
                                                <p><?php echo esc_html($sample['seed_art_analysis']['efficiency_analysis']); ?></p>
                                                
                                                <?php if (!empty($sample['seed_art_analysis']['time_estimate'])) : ?>
                                                    <div class="vortex-time-estimate">
                                                        <span class="vortex-time-label"><?php esc_html_e('Estimated Creation Time:', 'vortex'); ?></span>
                                                        <span class="vortex-time-value"><?php echo esc_html($sample['seed_art_analysis']['time_estimate']); ?></span>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <?php if (!empty($sample['seed_art_analysis']['optimization_advice'])) : ?>
                                                    <div class="vortex-optimization-advice">
                                                        <h5><?php esc_html_e('HURAII Optimization Advice', 'vortex'); ?></h5>
                                                        <div class="vortex-advice-content">
                                                            <?php echo wp_kses_post($sample['seed_art_analysis']['optimization_advice']); ?>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($sample['technique'])) : ?>
                                    <div class="vortex-technique">
                                        <span class="vortex-technique-label"><?php esc_html_e('Technique:', 'vortex'); ?></span>
                                        <span class="vortex-technique-text"><?php echo esc_html($sample['technique']); ?></span>
                                    </div>
                                <?php endif; ?>
                                <a href="<?php echo esc_url($sample['creation_url']); ?>" class="vortex-create-similar">
                                    <?php esc_html_e('Create Similar', 'vortex'); ?>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="vortex-huraii-actions">
                    <a href="<?php echo esc_url(home_url('/create-with-huraii/')); ?>" class="vortex-huraii-button">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18"><path d="M17.5 12a5.5 5.5 0 1 1 0-11 5.5 5.5 0 0 1 0 11zm0-9a3.5 3.5 0 1 0 0 7 3.5 3.5 0 0 0 0-7zm-4.7 12c.4.4.7 1.5.7 2v3c0 .8-.7 2-1.5 2h-8c-.8 0-1.5-1.2-1.5-2v-3c0-.5.3-1.6.7-2l2.8-2.8c.3-.3.7-.2 1.1 0l1.5 1.5 2.8-2.8c.3-.3.7-.3 1.1 0l2.8 2.8m-9.3.1L2 16.6V19h13v-2.4l-1.5-1.5-2.8 2.8c-.3.3-.7.3-1.1 0L6.6 15l-3.1 3.1z" fill="currentColor"/></svg>
                        <?php esc_html_e('Create Your Own AI Artwork', 'vortex'); ?>
                    </a>
                </div>
                <div class="vortex-ai-label">
                    <?php esc_html_e('Generated by HURAII AI Art System', 'vortex'); ?>
                </div>
                
                <div class="vortex-privacy-note">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zM9 6c0-1.66 1.34-3 3-3s3 1.34 3 3v2H9V6zm9 14H6V10h12v10zm-6-3c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2z" fill="currentColor"/></svg>
                    <span><?php esc_html_e('Your artwork and personal data remain private and secure', 'vortex'); ?></span>
                </div>
            </section>
        <?php endif; ?>

        <?php if (!empty($curated_selections)) : ?>
            <section class="vortex-cloe-curation">
                <h2><?php esc_html_e('Curated Exhibition', 'vortex'); ?></h2>
                <p class="vortex-curation-theme">
                    <?php echo esc_html($curated_selections['theme_description']); ?>
                </p>
                <div class="vortex-curation-grid">
                    <?php foreach ($curated_selections['artworks'] as $artwork) : ?>
                        <div class="vortex-curation-item">
                            <a href="<?php echo esc_url(get_permalink($artwork->ID)); ?>">
                                <?php echo get_the_post_thumbnail($artwork->ID, 'medium'); ?>
                                <div class="vortex-curation-details">
                                    <h3><?php echo esc_html(get_the_title($artwork->ID)); ?></h3>
                                    <?php if (!empty($artwork->artist_name)) : ?>
                                        <p class="vortex-artist-name"><?php echo esc_html($artwork->artist_name); ?></p>
                                    <?php endif; ?>
                                </div>
                            </a>
                            <?php if (!empty($artwork->curatorial_note)) : ?>
                                <div class="vortex-curatorial-note">
                                    <span class="vortex-note-icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16"><path d="M21 6h-2v9H6v2c0 .55.45 1 1 1h11l4 4V7c0-.55-.45-1-1-1zm-4 6V3c0-.55-.45-1-1-1H3c-.55 0-1 .45-1 1v14l4-4h10c.55 0 1-.45 1-1z" fill="currentColor"/></svg>
                                    </span>
                                    <?php echo esc_html($artwork->curatorial_note); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="vortex-view-exhibition">
                    <a href="<?php echo esc_url($curated_selections['exhibition_url']); ?>" class="vortex-exhibition-button">
                        <?php esc_html_e('View Full Exhibition', 'vortex'); ?>
                    </a>
                </div>
                <div class="vortex-ai-label">
                    <?php esc_html_e('Curated by CLOE AI', 'vortex'); ?>
                </div>
            </section>
        <?php endif; ?>

        <?php if (!empty($personalized_gallery) && get_current_user_id()) : ?>
            <section class="vortex-personal-gallery">
                <h2><?php esc_html_e('Recommended For Your Collection', 'vortex'); ?></h2>
                <div class="vortex-personal-grid">
                    <?php foreach ($personalized_gallery['artworks'] as $artwork) : ?>
                        <div class="vortex-recommendation-item">
                            <a href="<?php echo esc_url(get_permalink($artwork->ID)); ?>">
                                <?php echo get_the_post_thumbnail($artwork->ID, 'medium'); ?>
                                <div class="vortex-recommendation-details">
                                    <h3><?php echo esc_html(get_the_title($artwork->ID)); ?></h3>
                                    <?php if (!empty($artwork->artist_name)) : ?>
                                        <p class="vortex-artist-name"><?php echo esc_html($artwork->artist_name); ?></p>
                                    <?php endif; ?>
                                </div>
                            </a>
                            <?php if (!empty($artwork->recommendation_reason)) : ?>
                                <div class="vortex-recommendation-reason">
                                    <span class="vortex-ai-icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z" fill="currentColor"/></svg>
                                    </span>
                                    <?php echo esc_html($artwork->recommendation_reason); ?>
                                </div>
                            <?php endif; ?>
                            <div class="vortex-recommendation-actions">
                                <a href="<?php echo esc_url(get_permalink($artwork->ID)); ?>" class="vortex-view-button">
                                    <?php esc_html_e('View Artwork', 'vortex'); ?>
                                </a>
                                <?php if (function_exists('vortex_add_to_wishlist_button')) : ?>
                                    <?php vortex_add_to_wishlist_button($artwork->ID); ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="vortex-ai-label">
                    <?php esc_html_e('Personalized by CLOE AI Curator', 'vortex'); ?>
                </div>
            </section>
        <?php endif; ?>

        <?php if ($user_id && !empty($internet_trends)) : ?>
            <section class="vortex-cloe-trends">
                <h2><?php esc_html_e('Market Trends & Opportunities', 'vortex'); ?></h2>
                
                <div class="vortex-cloe-header">
                    <div class="vortex-cloe-avatar">
                        <img src="<?php echo esc_url(VORTEX_PLUGIN_URL . 'assets/images/cloe-avatar.png'); ?>" alt="CLOE" />
                    </div>
                    <div class="vortex-cloe-intro">
                        <h3><?php esc_html_e('CLOE\'s Market Intelligence', 'vortex'); ?></h3>
                        <p><?php echo esc_html(sprintf(__('Trending insights for %s artists in %s', 'vortex'), $user_artist_type, $current_category->name)); ?></p>
                        </div>
                </div>
                
                <div class="vortex-internet-trends">
                    <h3><?php esc_html_e('Current Artistic Trends', 'vortex'); ?></h3>
                    
                    <?php foreach ($internet_trends as $trend) : ?>
                        <div class="vortex-trend-item">
                            <div class="vortex-trend-header">
                                <h4><?php echo esc_html($trend['title']); ?></h4>
                                <span class="vortex-trend-score">
                                    <?php echo esc_html(sprintf(__('Trend Score: %s', 'vortex'), $trend['trend_score'])); ?>
                                    <?php if (!empty($trend['trend_direction'])) : ?>
                                        <span class="vortex-trend-direction <?php echo esc_attr($trend['trend_direction']); ?>">
                                            <?php if ($trend['trend_direction'] === 'up') : ?>
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16"><path d="M4 12l1.41 1.41L11 7.83V20h2V7.83l5.58 5.59L20 12l-8-8-8 8z" fill="currentColor"/></svg>
                                            <?php elseif ($trend['trend_direction'] === 'down') : ?>
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16"><path d="M20 12l-1.41-1.41L13 16.17V4h-2v12.17l-5.58-5.59L4 12l8 8 8-8z" fill="currentColor"/></svg>
                                            <?php endif; ?>
                                            </span>
                                        <?php endif; ?>
                                </span>
                                    </div>
                            
                            <div class="vortex-trend-description">
                                <p><?php echo esc_html($trend['description']); ?></p>
            </div>
            
                            <?php if (!empty($trend['relevance_for_artist'])) : ?>
                                <div class="vortex-trend-relevance">
                                    <h5><?php esc_html_e('Relevance for You', 'vortex'); ?></h5>
                                    <p><?php echo esc_html($trend['relevance_for_artist']); ?></p>
                                            </div>
                                        <?php endif; ?>
                                        
                            <?php if (!empty($trend['market_data'])) : ?>
                                <div class="vortex-trend-market-data">
                                    <div class="vortex-trend-data-grid">
                                        <?php foreach ($trend['market_data'] as $data_point) : ?>
                                            <div class="vortex-trend-data-point">
                                                <span class="vortex-data-label"><?php echo esc_html($data_point['label']); ?>:</span>
                                                <span class="vortex-data-value"><?php echo esc_html($data_point['value']); ?></span>
                                            </div>
                                        <?php endforeach; ?>
                                            </div>
                                            </div>
                                        <?php endif; ?>
                                        
                            <?php if (!empty($trend['example_url'])) : ?>
                                <a href="<?php echo esc_url($trend['example_url']); ?>" class="vortex-trend-example" target="_blank">
                                    <?php esc_html_e('View Examples', 'vortex'); ?>
                                </a>
                                            <?php endif; ?>
                                        </div>
                    <?php endforeach; ?>
                    
                    <div class="vortex-ai-label">
                        <?php esc_html_e('Internet trends analyzed by CLOE AI', 'vortex'); ?>
            </div>
        </div>

                <?php if (!empty($marketing_recommendations)) : ?>
                    <div class="vortex-marketing-recommendations">
                        <h3><?php esc_html_e('Marketing Recommendations', 'vortex'); ?></h3>
                        
                        <div class="vortex-marketing-grid">
                            <?php foreach ($marketing_recommendations as $recommendation) : ?>
                                <div class="vortex-marketing-item">
                                    <div class="vortex-marketing-icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24">
                                            <path d="<?php echo esc_attr($recommendation['icon_path']); ?>" fill="currentColor"/>
                                        </svg>
                                    </div>
                                    
                                    <div class="vortex-marketing-content">
                                        <h4><?php echo esc_html($recommendation['title']); ?></h4>
                                        <p><?php echo esc_html($recommendation['description']); ?></p>
                                        
                                        <?php if (!empty($recommendation['action_steps'])) : ?>
                                            <div class="vortex-action-steps">
                                                <h5><?php esc_html_e('Action Steps', 'vortex'); ?></h5>
                                                <ol>
                                                    <?php foreach ($recommendation['action_steps'] as $step) : ?>
                                                        <li><?php echo esc_html($step); ?></li>
                                                    <?php endforeach; ?>
                                                </ol>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($recommendation['estimated_impact'])) : ?>
                                            <div class="vortex-estimated-impact">
                                                <span class="vortex-impact-label"><?php esc_html_e('Estimated Impact:', 'vortex'); ?></span>
                                                <span class="vortex-impact-value <?php echo esc_attr($recommendation['impact_level']); ?>">
                                                    <?php echo esc_html($recommendation['estimated_impact']); ?>
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <a href="<?php echo esc_url(home_url('/marketing-assistance/')); ?>" class="vortex-full-marketing-plan">
                            <?php esc_html_e('Get Your Full Marketing Plan', 'vortex'); ?>
                        </a>
                        
                        <div class="vortex-ai-label">
                            <?php esc_html_e('Marketing insights by CLOE AI', 'vortex'); ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($collector_matches)) : ?>
                    <div class="vortex-collector-matches">
                        <h3><?php esc_html_e('Potential Collector Matches', 'vortex'); ?></h3>
                        
                        <div class="vortex-matches-grid">
                            <?php foreach ($collector_matches as $match) : ?>
                                <div class="vortex-match-item">
                                    <div class="vortex-match-score">
                                        <div class="vortex-score-circle" style="--match-score: <?php echo esc_attr($match['match_percentage']); ?>%">
                                            <span class="vortex-score-text"><?php echo esc_html($match['match_percentage']); ?>%</span>
                                        </div>
                                    </div>
                                    
                                    <div class="vortex-match-details">
                                        <h4><?php echo esc_html($match['collector_type']); ?></h4>
                                        <p><?php echo esc_html($match['description']); ?></p>
                                        
                                        <?php if (!empty($match['preferences'])) : ?>
                                            <div class="vortex-match-preferences">
                                                <h5><?php esc_html_e('Collector Preferences', 'vortex'); ?></h5>
                                                <ul>
                                                    <?php foreach ($match['preferences'] as $preference) : ?>
                                                        <li><?php echo esc_html($preference); ?></li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($match['recommendation'])) : ?>
                                            <div class="vortex-match-recommendation">
                                                <h5><?php esc_html_e('How to Appeal to This Collector', 'vortex'); ?></h5>
                                                <p><?php echo esc_html($match['recommendation']); ?></p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <a href="<?php echo esc_url(home_url('/collector-connections/')); ?>" class="vortex-find-collectors">
                            <?php esc_html_e('Find More Collector Connections', 'vortex'); ?>
                        </a>
                        
                        <div class="vortex-ai-label">
                            <?php esc_html_e('Collector matching by CLOE AI', 'vortex'); ?>
                        </div>
                        
                        <div class="vortex-privacy-note">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 10.99h7c-.53 4.12-3.28 7.79-7 8.94V12H5V6.3l7-3.11v8.8z" fill="currentColor"/></svg>
                            <span><?php esc_html_e('Collector information is anonymized for privacy', 'vortex'); ?></span>
                        </div>
                    </div>
                <?php endif; ?>
            </section>
        <?php endif; ?>

        <?php if (current_user_can('administrator')) : ?>
            <section class="vortex-cloe-admin-insights">
                <div class="vortex-admin-panel-header">
                    <h3><?php esc_html_e('CLOE Admin Insights', 'vortex'); ?></h3>
                    <p><?php esc_html_e('Admin-only data insights for platform optimization', 'vortex'); ?></p>
                </div>
                
                <div class="vortex-admin-insights-grid">
                    <div class="vortex-admin-insight-card">
                        <h4><?php esc_html_e('Category Performance', 'vortex'); ?></h4>
                        <?php 
                        // Get category performance metrics from CLOE
                        $category_performance = apply_filters('vortex_ai_get_cloe_admin_insights', array(
                            'insight_type' => 'category_performance',
                            'category_id' => $current_category->term_id
                        ));
                        
                        if (!empty($category_performance)) :
                        ?>
                            <div class="vortex-performance-metrics">
                                <?php foreach ($category_performance as $metric) : ?>
                                    <div class="vortex-metric-item">
                                        <span class="vortex-metric-label"><?php echo esc_html($metric['label']); ?>:</span>
                                        <span class="vortex-metric-value <?php echo esc_attr($metric['trend']); ?>">
                                            <?php echo esc_html($metric['value']); ?>
                                            
                                            <?php if ($metric['trend'] === 'up') : ?>
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="14" height="14"><path d="M4 12l1.41 1.41L11 7.83V20h2V7.83l5.58 5.59L20 12l-8-8-8 8z" fill="currentColor"/></svg>
                                            <?php elseif ($metric['trend'] === 'down') : ?>
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="14" height="14"><path d="M20 12l-1.41-1.41L13 16.17V4h-2v12.17l-5.58-5.59L4 12l8 8 8-8z" fill="currentColor"/></svg>
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        
                        <a href="<?php echo esc_url(admin_url('admin.php?page=vortex-ai-category-performance')); ?>" class="vortex-admin-link">
                            <?php esc_html_e('View Full Report', 'vortex'); ?>
                        </a>
                    </div>
                    
                    <div class="vortex-admin-insight-card">
                        <h4><?php esc_html_e('User Behavior Analysis', 'vortex'); ?></h4>
                        <?php 
                        // Get user behavior insights from CLOE
                        $user_behavior = apply_filters('vortex_ai_get_cloe_admin_insights', array(
                            'insight_type' => 'user_behavior',
                            'category_id' => $current_category->term_id
                        ));
                        
                        if (!empty($user_behavior) && !empty($user_behavior['summary'])) :
                        ?>
                            <div class="vortex-behavior-summary">
                                <p><?php echo esc_html($user_behavior['summary']); ?></p>
                            </div>
                            
                            <?php if (!empty($user_behavior['key_patterns'])) : ?>
                                <div class="vortex-behavior-patterns">
                                    <h5><?php esc_html_e('Key Behavior Patterns', 'vortex'); ?></h5>
                                    <ul>
                                        <?php foreach ($user_behavior['key_patterns'] as $pattern) : ?>
                                            <li><?php echo esc_html($pattern); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <a href="<?php echo esc_url(admin_url('admin.php?page=vortex-ai-user-behavior')); ?>" class="vortex-admin-link">
                            <?php esc_html_e('View Full Report', 'vortex'); ?>
                        </a>
                    </div>
                    
                    <div class="vortex-admin-insight-card">
                        <h4><?php esc_html_e('Marketing Optimization', 'vortex'); ?></h4>
                        <?php 
                        // Get marketing optimization insights from CLOE
                        $marketing_insights = apply_filters('vortex_ai_get_cloe_admin_insights', array(
                            'insight_type' => 'marketing_optimization',
                            'category_id' => $current_category->term_id
                        ));
                        
                        if (!empty($marketing_insights) && !empty($marketing_insights['recommendations'])) :
                        ?>
                            <div class="vortex-marketing-optimization">
                                <?php foreach ($marketing_insights['recommendations'] as $index => $recommendation) : ?>
                                    <?php if ($index < 2) : // Only show top 2 recommendations in summary ?>
                                        <div class="vortex-optimization-item">
                                            <h5><?php echo esc_html($recommendation['title']); ?></h5>
                                            <p><?php echo esc_html($recommendation['description']); ?></p>
                                            
                                            <?php if (!empty($recommendation['impact'])) : ?>
                                                <div class="vortex-impact-estimate">
                                                    <span class="vortex-impact-label"><?php esc_html_e('Estimated Impact:', 'vortex'); ?></span>
                                                    <span class="vortex-impact-value <?php echo esc_attr($recommendation['impact_level']); ?>">
                                                        <?php echo esc_html($recommendation['impact']); ?>
                                                    </span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                                
                                <?php if (count($marketing_insights['recommendations']) > 2) : ?>
                                    <div class="vortex-more-recommendations">
                                        <p><?php echo esc_html(sprintf(__('%d more recommendations available in full report', 'vortex'), count($marketing_insights['recommendations']) - 2)); ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        
                        <a href="<?php echo esc_url(admin_url('admin.php?page=vortex-ai-marketing-optimization')); ?>" class="vortex-admin-link">
                            <?php esc_html_e('View Full Report', 'vortex'); ?>
                        </a>
                    </div>
                </div>
                
                <div class="vortex-admin-actions">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=vortex-ai-cloe-dashboard')); ?>" class="vortex-admin-dashboard-link">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z" fill="currentColor"/></svg>
                        <?php esc_html_e('CLOE AI Admin Dashboard', 'vortex'); ?>
                    </a>
                </div>
            </section>
        <?php endif; ?>

        <div class="vortex-category-container">
            <div class="vortex-category-sidebar">
                <div class="vortex-filter-section">
                    <h3><?php esc_html_e('Filter Artworks', 'vortex'); ?></h3>
                    <form method="get" class="vortex-filter-form">
                        <?php 
                        // Output filter form
                        echo apply_filters('vortex_ai_render_filter_form', array(
                            'entity_type' => 'artwork',
                            'category_id' => $current_category->term_id,
                            'taxonomy' => $current_category->taxonomy
                        ));
                        ?>
                        <div class="vortex-filter-actions">
                            <button type="submit" class="vortex-filter-button">
                                <?php esc_html_e('Apply Filters', 'vortex'); ?>
                            </button>
                            <a href="<?php echo esc_url(get_term_link($current_category)); ?>" class="vortex-reset-button">
                                <?php esc_html_e('Reset', 'vortex'); ?>
                            </a>
                        </div>
                    </form>
                </div>
                
                <?php if (!empty($business_opportunities)) : ?>
                    <div class="vortex-business-opportunities">
                        <h3><?php esc_html_e('Opportunities', 'vortex'); ?></h3>
                        <ul class="vortex-opportunity-list">
                            <?php foreach ($business_opportunities as $opportunity) : ?>
                                <li class="vortex-opportunity-item">
                                    <div class="vortex-opportunity-type">
                                        <span class="vortex-opportunity-badge <?php echo esc_attr($opportunity['type']); ?>">
                                            <?php echo esc_html(ucfirst($opportunity['type'])); ?>
                                        </span>
                                        <?php if (!empty($opportunity['deadline'])) : ?>
                                            <span class="vortex-deadline">
                                                <?php echo esc_html(human_time_diff(strtotime($opportunity['deadline']), current_time('timestamp'))); ?> left
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <h4 class="vortex-opportunity-title">
                                        <a href="<?php echo esc_url($opportunity['url']); ?>">
                                            <?php echo esc_html($opportunity['title']); ?>
                                        </a>
                                    </h4>
                                    <p class="vortex-opportunity-description">
                                        <?php echo esc_html($opportunity['description']); ?>
                                    </p>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <div class="vortex-ai-label vortex-ai-small">
                            <?php esc_html_e('Identified by Business Strategist AI', 'vortex'); ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="vortex-category-content">
                <?php if (have_posts()) : ?>
                    <div class="vortex-artwork-grid" id="vortex-artwork-container">
                        <?php while (have_posts()) : the_post(); 
                            // Get artwork metadata and HURAII analysis
                            $artwork_id = get_the_ID();
                            $artwork_meta = apply_filters('vortex_ai_get_artwork_meta', $artwork_id);
                            
                            // Get HURAII Seed Art analysis for this artwork
                            $seed_art_analysis = apply_filters('vortex_ai_get_huraii_seed_art_analysis', $artwork_id);
                        ?>
                            <article id="post-<?php the_ID(); ?>" <?php post_class('vortex-artwork-item'); ?>>
                                <div class="vortex-artwork-inner">
                                    <a href="<?php the_permalink(); ?>" class="vortex-artwork-thumb">
                                        <?php 
                                        if (has_post_thumbnail()) {
                                            the_post_thumbnail('medium');
                                        } else {
                                            echo '<div class="vortex-no-thumb"></div>';
                                        }
                                        ?>
                                        <?php if (!empty($artwork_meta['style'])) : ?>
                                            <div class="vortex-artwork-style">
                                                <span class="vortex-style-tag"><?php echo esc_html($artwork_meta['style']); ?></span>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($seed_art_analysis) && !empty($seed_art_analysis['primary_element'])) : ?>
                                            <div class="vortex-seed-art-badge" title="<?php esc_attr_e('Analyzed with Seed Art Technique', 'vortex'); ?>">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16"><path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10zm0-18c4.418 0 8 3.582 8 8s-3.582 8-8 8-8-3.582-8-8 3.582-8 8-8zm-5 8.5a5 5 0 0 1 10 0c0 2.76-2.24 5-5 5s-5-2.24-5-5z" fill="currentColor"/></svg>
                                                <span><?php echo esc_html($seed_art_analysis['primary_element']); ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </a>
                                    
                                    <div class="vortex-artwork-details">
                                        <h2 class="vortex-artwork-title">
                                            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                        </h2>
                                        
                                        <?php if (!empty($artwork_meta['artist_id'])) : ?>
                                            <div class="vortex-artwork-artist">
                                                <?php esc_html_e('By', 'vortex'); ?> 
                                                <a href="<?php echo esc_url(get_permalink($artwork_meta['artist_id'])); ?>">
                                                    <?php echo esc_html($artwork_meta['artist_name']); ?>
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($artwork_meta['price'])) : ?>
                                            <div class="vortex-artwork-price">
                                                <?php echo esc_html(vortex_format_price($artwork_meta['price'])); ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="vortex-artwork-meta">
                                            <?php if (!empty($artwork_meta['dimensions'])) : ?>
                                                <span class="vortex-artwork-dimensions"><?php echo esc_html($artwork_meta['dimensions']); ?></span>
                                            <?php endif; ?>
                                            
                                            <?php if (!empty($artwork_meta['year'])) : ?>
                                                <span class="vortex-artwork-year"><?php echo esc_html($artwork_meta['year']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="vortex-artwork-actions">
                                            <a href="<?php the_permalink(); ?>" class="vortex-view-button">
                                                <?php esc_html_e('View Details', 'vortex'); ?>
                                            </a>
                                            
                                            <?php if (function_exists('vortex_add_to_cart_button')) : ?>
                                                <?php vortex_add_to_cart_button($artwork_id); ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </article>
                        <?php endwhile; ?>
                    </div>
                    
                    <?php
                    // Pagination
                    echo apply_filters('vortex_ai_enhanced_pagination', array(
                        'show_load_more' => true,
                        'preload_next' => true
                    ));
                    ?>
                    
                <?php else : ?>
                    <div class="vortex-no-artworks">
                        <h2><?php esc_html_e('No artworks found', 'vortex'); ?></h2>
                        <p><?php esc_html_e('Try adjusting your filters or browse our other categories.', 'vortex'); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </main>
</div>

<?php
// Initialize real-time deep learning for AI agents with BusinessStrategist & CLOE enhanced functionalities
add_action('wp_footer', function() use ($current_category, $user_id, $is_new_user, $onboarding_step, $has_business_plan) {
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof VortexAISystem !== 'undefined') {
            // Initialize CLOE as the welcome/registration host
            VortexAISystem.initAgentModule('CLOE', {
                context: 'user_welcome',
                userStatus: <?php echo $is_new_user ? "'new'" : "'returning'"; ?>,
                onboardingStep: <?php echo json_encode($onboarding_step); ?>,
                preserveUserPrivacy: true,
                hostRole: true, // Enable host capabilities
                registrationAssistant: true // Enable registration guidance
            });
            
            // Initialize BusinessStrategist for business planning and follow-up
            VortexAISystem.initAgentModule('BusinessStrategist', {
                context: 'business_planning',
                userStatus: <?php echo $is_new_user ? "'new'" : "'returning'"; ?>,
                onboardingStep: <?php echo json_encode($onboarding_step); ?>,
                businessPlanCreated: <?php echo $has_business_plan ? 'true' : 'false'; ?>,
                questionnaireEnabled: true,
                milestoneTracking: true,
                followUpEnabled: true,
                notificationsEnabled: true
            });
            
            // Handle CLOE's registration form functionality
            if (document.getElementById('vortexRegistrationForm')) {
                const registrationForm = document.getElementById('vortexRegistrationForm');
                const formSteps = registrationForm.querySelectorAll('.vortex-form-step');
                const nextButtons = registrationForm.querySelectorAll('.vortex-next-step');
                const prevButtons = registrationForm.querySelectorAll('.vortex-prev-step');
                
                nextButtons.forEach(button => {
                    button.addEventListener('click', function() {
                        const currentStep = parseInt(registrationForm.dataset.step);
                        const nextStep = currentStep + 1;
                        
                        // Validate current step
                        const currentFields = document.querySelector(`.vortex-form-step[data-step="${currentStep}"]`).querySelectorAll('[required]');
                        let isValid = true;
                        
                        currentFields.forEach(field => {
                            if (!field.value.trim()) {
                                isValid = false;
                                field.classList.add('vortex-error');
                            } else {
                                field.classList.remove('vortex-error');
                            }
                        });
                        
                        if (isValid) {
                            // Hide current step
                            document.querySelector(`.vortex-form-step[data-step="${currentStep}"]`).style.display = 'none';
                            
                            // Show next step
                            document.querySelector(`.vortex-form-step[data-step="${nextStep}"]`).style.display = 'block';
                            
                            // Update current step
                            registrationForm.dataset.step = nextStep;
                            
                            // Track step progression with CLOE
                            VortexAISystem.trackCloeInteraction({
                                interactionType: 'registration_step',
                                step: nextStep,
                                timestamp: Date.now()
                            });
                        }
                    });
                });
                
                prevButtons.forEach(button => {
                    button.addEventListener('click', function() {
                        const currentStep = parseInt(registrationForm.dataset.step);
                        const prevStep = currentStep - 1;
                        
                        // Hide current step
                        document.querySelector(`.vortex-form-step[data-step="${currentStep}"]`).style.display = 'none';
                        
                        // Show previous step
                        document.querySelector(`.vortex-form-step[data-step="${prevStep}"]`).style.display = 'block';
                        
                        // Update current step
                        registrationForm.dataset.step = prevStep;
                    });
                });
                
                registrationForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const formData = new FormData(registrationForm);
                    
                    // Track registration submission with CLOE
                    VortexAISystem.trackCloeInteraction({
                        interactionType: 'registration_submit',
                        timestamp: Date.now()
                    });
                    
                    // Process registration
                    fetch(vortex_ajax.ajax_url, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Redirect to onboarding
                            window.location.href = data.redirect;
                        } else {
                            // Show error
                            alert(data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                    });
                });
            }
            
            // Handle BusinessStrategist's questionnaire functionality
            if (document.getElementById('vortexBusinessQuestionnaireForm')) {
                const questionnaireForm = document.getElementById('vortexBusinessQuestionnaireForm');
                
                // Handle range input display
                const rangeInputs = questionnaireForm.querySelectorAll('input[type="range"]');
                rangeInputs.forEach(range => {
                    range
        }
    });
    </script>
    <?php
}, 20);

get_sidebar();
get_footer(); 