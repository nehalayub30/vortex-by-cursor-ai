<?php
/**
 * Language Settings template.
 *
 * @link       https://vortexartec.com
 * @since      1.0.0
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/admin/partials/settings
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Get settings from the database
$default_language = get_option('vortex_default_language', 'en');
$enabled_languages = get_option('vortex_enabled_languages', 'en,es,fr,de,it,ja,zh,ru');
$translation_service = get_option('vortex_translation_service', 'google');
$translation_api_key = get_option('vortex_translation_api_key', '');
$auto_translate_content = get_option('vortex_auto_translate_content', 1);
$translation_cache_days = get_option('vortex_translation_cache_days', 30);
$user_language_selection = get_option('vortex_user_language_selection', 1);
$show_language_switcher = get_option('vortex_show_language_switcher', 1);
$translate_marketplace_pages = get_option('vortex_translate_marketplace_pages', 1);
$translate_artwork_content = get_option('vortex_translate_artwork_content', 1);
$translate_artist_profiles = get_option('vortex_translate_artist_profiles', 1);

// Convert enabled languages string to array
$enabled_languages_array = explode(',', $enabled_languages);

// Available languages
$available_languages = array(
    'en' => array('name' => 'English', 'native_name' => 'English', 'flag' => 'us', 'rtl' => false),
    'es' => array('name' => 'Spanish', 'native_name' => 'Español', 'flag' => 'es', 'rtl' => false),
    'fr' => array('name' => 'French', 'native_name' => 'Français', 'flag' => 'fr', 'rtl' => false),
    'de' => array('name' => 'German', 'native_name' => 'Deutsch', 'flag' => 'de', 'rtl' => false),
    'it' => array('name' => 'Italian', 'native_name' => 'Italiano', 'flag' => 'it', 'rtl' => false),
    'ja' => array('name' => 'Japanese', 'native_name' => '日本語', 'flag' => 'jp', 'rtl' => false),
    'zh' => array('name' => 'Chinese (Simplified)', 'native_name' => '简体中文', 'flag' => 'cn', 'rtl' => false),
    'ru' => array('name' => 'Russian', 'native_name' => 'Русский', 'flag' => 'ru', 'rtl' => false),
    'ar' => array('name' => 'Arabic', 'native_name' => 'العربية', 'flag' => 'sa', 'rtl' => true),
    'pt' => array('name' => 'Portuguese', 'native_name' => 'Português', 'flag' => 'pt', 'rtl' => false),
    'ko' => array('name' => 'Korean', 'native_name' => '한국어', 'flag' => 'kr', 'rtl' => false),
    'hi' => array('name' => 'Hindi', 'native_name' => 'हिन्दी', 'flag' => 'in', 'rtl' => false),
    'tr' => array('name' => 'Turkish', 'native_name' => 'Türkçe', 'flag' => 'tr', 'rtl' => false),
    'nl' => array('name' => 'Dutch', 'native_name' => 'Nederlands', 'flag' => 'nl', 'rtl' => false),
    'pl' => array('name' => 'Polish', 'native_name' => 'Polski', 'flag' => 'pl', 'rtl' => false),
    'he' => array('name' => 'Hebrew', 'native_name' => 'עברית', 'flag' => 'il', 'rtl' => true)
);

// Translation services
$translation_services = array(
    'google' => 'Google Cloud Translation',
    'deepl' => 'DeepL API',
    'microsoft' => 'Microsoft Translator',
    'custom' => 'Custom Integration'
);
?>

<div class="vortex-settings-section" id="language-general-settings">
    <h2><?php esc_html_e('Language Settings', 'vortex-ai-marketplace'); ?></h2>
    <p class="description"><?php esc_html_e('Configure the language settings for your marketplace.', 'vortex-ai-marketplace'); ?></p>
    
    <table class="form-table">
        <tr>
            <th scope="row">
                <label for="vortex_default_language"><?php esc_html_e('Default Language', 'vortex-ai-marketplace'); ?></label>
            </th>
            <td>
                <select id="vortex_default_language" name="vortex_default_language">
                    <?php foreach ($available_languages as $code => $language) : ?>
                        <option value="<?php echo esc_attr($code); ?>" <?php selected($default_language, $code); ?>>
                            <?php echo esc_html($language['name'] . ' (' . $language['native_name'] . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="description"><?php esc_html_e('The default language for your marketplace content.', 'vortex-ai-marketplace'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label><?php esc_html_e('Enabled Languages', 'vortex-ai-marketplace'); ?></label>
            </th>
            <td>
                <div class="vortex-language-list">
                    <?php foreach ($available_languages as $code => $language) : ?>
                        <label class="vortex-language-item">
                            <input type="checkbox" name="vortex_enabled_languages_array[]" value="<?php echo esc_attr($code); ?>" 
                                <?php checked(in_array($code, $enabled_languages_array), true); ?> />
                            <span class="vortex-language-flag flag-icon flag-icon-<?php echo esc_attr($language['flag']); ?>"></span>
                            <span class="vortex-language-name"><?php echo esc_html($language['name']); ?></span>
                            <span class="vortex-language-native">(<?php echo esc_html($language['native_name']); ?>)</span>
                            <?php if ($language['rtl']) : ?>
                                <span class="vortex-language-rtl">[RTL]</span>
                            <?php endif; ?>
                        </label>
                    <?php endforeach; ?>
                </div>
                <p class="description"><?php esc_html_e('Select which languages to enable for your marketplace.', 'vortex-ai-marketplace'); ?></p>
                <input type="hidden" id="vortex_enabled_languages" name="vortex_enabled_languages" value="<?php echo esc_attr($enabled_languages); ?>" />
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="vortex_user_language_selection"><?php esc_html_e('Allow User Language Selection', 'vortex-ai-marketplace'); ?></label>
            </th>
            <td>
                <label for="vortex_user_language_selection">
                    <input type="checkbox" id="vortex_user_language_selection" name="vortex_user_language_selection" 
                           value="1" <?php checked($user_language_selection, 1); ?> />
                    <?php esc_html_e('Allow users to select their preferred language', 'vortex-ai-marketplace'); ?>
                </label>
                <p class="description"><?php esc_html_e('When enabled, users can select their preferred language for the marketplace.', 'vortex-ai-marketplace'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="vortex_show_language_switcher"><?php esc_html_e('Show Language Switcher', 'vortex-ai-marketplace'); ?></label>
            </th>
            <td>
                <label for="vortex_show_language_switcher">
                    <input type="checkbox" id="vortex_show_language_switcher" name="vortex_show_language_switcher" 
                           value="1" <?php checked($show_language_switcher, 1); ?> />
                    <?php esc_html_e('Display language switcher in the marketplace', 'vortex-ai-marketplace'); ?>
                </label>
                <p class="description"><?php esc_html_e('When enabled, a language switcher will be displayed in the marketplace header.', 'vortex-ai-marketplace'); ?></p>
            </td>
        </tr>
    </table>
</div>

<div class="vortex-settings-section" id="translation-settings">
    <h2><?php esc_html_e('Translation Settings', 'vortex-ai-marketplace'); ?></h2>
    <p class="description"><?php esc_html_e('Configure the translation service and settings for your marketplace.', 'vortex-ai-marketplace'); ?></p>
    
    <table class="form-table">
        <tr>
            <th scope="row">
                <label for="vortex_auto_translate_content"><?php esc_html_e('Auto-Translate Content', 'vortex-ai-marketplace'); ?></label>
            </th>
            <td>
                <label for="vortex_auto_translate_content">
                    <input type="checkbox" id="vortex_auto_translate_content" name="vortex_auto_translate_content" 
                           value="1" <?php checked($auto_translate_content, 1); ?> />
                    <?php esc_html_e('Automatically translate content to user\'s preferred language', 'vortex-ai-marketplace'); ?>
                </label>
                <p class="description"><?php esc_html_e('When enabled, content will be automatically translated to the user\'s preferred language.', 'vortex-ai-marketplace'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="vortex_translation_service"><?php esc_html_e('Translation Service', 'vortex-ai-marketplace'); ?></label>
            </th>
            <td>
                <select id="vortex_translation_service" name="vortex_translation_service">
                    <?php foreach ($translation_services as $value => $label) : ?>
                        <option value="<?php echo esc_attr($value); ?>" <?php selected($translation_service, $value); ?>>
                            <?php echo esc_html($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="description"><?php esc_html_e('The translation service to use for translating content.', 'vortex-ai-marketplace'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="vortex_translation_api_key"><?php esc_html_e('Translation API Key', 'vortex-ai-marketplace'); ?></label>
            </th>
            <td>
                <input type="password" id="vortex_translation_api_key" name="vortex_translation_api_key" 
                       value="<?php echo esc_attr($translation_api_key); ?>" class="regular-text" autocomplete="new-password" />
                <p class="description"><?php esc_html_e('API key for the selected translation service.', 'vortex-ai-marketplace'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="vortex_translation_cache_days"><?php esc_html_e('Translation Cache (Days)', 'vortex-ai-marketplace'); ?></label>
            </th>
            <td>
                <input type="number" id="vortex_translation_cache_days" name="vortex_translation_cache_days" 
                       value="<?php echo esc_attr($translation_cache_days); ?>" min="1" max="365" class="small-text" />
                <p class="description"><?php esc_html_e('Number of days to cache translations before refreshing them.', 'vortex-ai-marketplace'); ?></p>
            </td>
        </tr>
    </table>
</div>

<div class="vortex-settings-section" id="content-translation-settings">
    <h2><?php esc_html_e('Content Translation Settings', 'vortex-ai-marketplace'); ?></h2>
    <p class="description"><?php esc_html_e('Configure which content should be translated in your marketplace.', 'vortex-ai-marketplace'); ?></p>
    
    <table class="form-table">
        <tr>
            <th scope="row">
                <label for="vortex_translate_marketplace_pages"><?php esc_html_e('Translate Marketplace Pages', 'vortex-ai-marketplace'); ?></label>
            </th>
            <td>
                <label for="vortex_translate_marketplace_pages">
                    <input type="checkbox" id="vortex_translate_marketplace_pages" name="vortex_translate_marketplace_pages" 
                           value="1" <?php checked($translate_marketplace_pages, 1); ?> />
                    <?php esc_html_e('Translate main marketplace pages content', 'vortex-ai-marketplace'); ?>
                </label>
                <p class="description"><?php esc_html_e('When enabled, content on main marketplace pages will be translated.', 'vortex-ai-marketplace'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="vortex_translate_artwork_content"><?php esc_html_e('Translate Artwork Content', 'vortex-ai-marketplace'); ?></label>
            </th>
            <td>
                <label for="vortex_translate_artwork_content">
                    <input type="checkbox" id="vortex_translate_artwork_content" name="vortex_translate_artwork_content" 
                           value="1" <?php checked($translate_artwork_content, 1); ?> />
                    <?php esc_html_e('Translate artwork titles, descriptions, and tags', 'vortex-ai-marketplace'); ?>
                </label>
                <p class="description"><?php esc_html_e('When enabled, artwork titles, descriptions, and tags will be translated.', 'vortex-ai-marketplace'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="vortex_translate_artist_profiles"><?php esc_html_e('Translate Artist Profiles', 'vortex-ai-marketplace'); ?></label>
            </th>
            <td>
                <label for="vortex_translate_artist_profiles">
                    <input type="checkbox" id="vortex_translate_artist_profiles" name="vortex_translate_artist_profiles" 
                           value="1" <?php checked($translate_artist_profiles, 1); ?> />
                    <?php esc_html_e('Translate artist bios and profile information', 'vortex-ai-marketplace'); ?>
                </label>
                <p class="description"><?php esc_html_e('When enabled, artist bios and profile information will be translated.', 'vortex-ai-marketplace'); ?></p>
            </td>
        </tr>
    </table>
</div>

<div class="vortex-settings-section" id="translation-tools">
    <h2><?php esc_html_e('Translation Tools', 'vortex-ai-marketplace'); ?></h2>
    <p class="description"><?php esc_html_e('Tools for managing translations in your marketplace.', 'vortex-ai-marketplace'); ?></p>
    
    <table class="form-table">
        <tr>
            <th scope="row">
                <label><?php esc_html_e('Clear Translation Cache', 'vortex-ai-marketplace'); ?></label>
            </th>
            <td>
                <button type="button" id="vortex-clear-translation-cache" class="button">
                    <?php esc_html_e('Clear All Translation Cache', 'vortex-ai-marketplace'); ?>
                </button>
                <p class="description"><?php esc_html_e('Clear the cached translations to force regeneration on next request.', 'vortex-ai-marketplace'); ?></p>
                <div id="vortex-clear-cache-status" style="margin-top: 10px; display: none;">
                    <span class="spinner is-active" style="float: left;"></span>
                    <span class="status-message"><?php esc_html_e('Clearing translation cache...', 'vortex-ai-marketplace'); ?></span>
                </div>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label><?php esc_html_e('Re-Translate All Content', 'vortex-ai-marketplace'); ?></label>
            </th>
            <td>
                <button type="button" id="vortex-retranslate-content" class="button">
                    <?php esc_html_e('Re-Translate All Content', 'vortex-ai-marketplace'); ?>
                </button>
                <p class="description"><?php esc_html_e('Force re-translation of all content. This process may take some time.', 'vortex-ai-marketplace'); ?></p>
                <div id="vortex-retranslate-status" style="margin-top: 10px; display: none;">
                    <span class="spinner is-active" style="float: left;"></span>
                    <span class="status-message"><?php esc_html_e('Queuing content for re-translation...', 'vortex-ai-marketplace'); ?></span>
                </div>
            </td>
        </tr>
    </table>
</div>

<script type="text/javascript">
    jQuery(document).ready(function($) {
        // Update hidden field with enabled languages
        $('input[name="vortex_enabled_languages_array[]"]').on('change', function() {
            var selectedLanguages = [];
            $('input[name="vortex_enabled_languages_array[]"]:checked').each(function() {
                selectedLanguages.push($(this).val());
            });
            $('#vortex_enabled_languages').val(selectedLanguages.join(','));
        });
        
        // Clear translation cache
        $('#vortex-clear-translation-cache').on('click', function() {
            $('#vortex-clear-cache-status').show();
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'vortex_clear_translation_cache',
                    nonce: vortex_admin.nonce
                },
                success: function(response) {
                    $('#vortex-clear-cache-status').hide();
                    
                    if (response.success) {
                        alert(response.data.message);
                    } else {
                        alert(response.data.message || 'An error occurred while clearing the translation cache.');
                    }
                },
                error: function() {
                    $('#vortex-clear-cache-status').hide();
                    alert('An error occurred while communicating with the server.');
                }
            });
        });
        
        // Re-translate all content
        $('#vortex-retranslate-content').on('click', function() {
            if (confirm('<?php esc_html_e('This will queue all content for re-translation, which may take some time and use translation API credits. Are you sure?', 'vortex-ai-marketplace'); ?>')) {
                $('#vortex-retranslate-status').show();
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'vortex_retranslate_all_content',
                        nonce: vortex_admin.nonce
                    },
                    success: function(response) {
                        $('#vortex-retranslate-status').hide();
                        
                        if (response.success) {
                            alert(response.data.message);
                        } else {
                            alert(response.data.message || 'An error occurred while queuing content for re-translation.');
                        }
                    },
                    error: function() {
                        $('#vortex-retranslate-status').hide();
                        alert('An error occurred while communicating with the server.');
                    }
                });
            }
        });
    });
</script> 