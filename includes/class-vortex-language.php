<?php
namespace Vortex\AI;

use Vortex\AI\Interfaces\LanguageInterface;

class Language implements LanguageInterface {
    private $current_language;
    private $available_languages;
    private $translations;

    public function __construct() {
        $this->current_language = get_locale();
        $this->available_languages = [
            'en_US' => 'English',
            'es_ES' => 'Español',
            'fr_FR' => 'Français',
            'de_DE' => 'Deutsch',
            'it_IT' => 'Italiano',
            'ja_JP' => '日本語',
            'zh_CN' => '简体中文',
            'ru_RU' => 'Русский'
        ];
        $this->translations = [];
        $this->load_language_file($this->current_language);
    }

    public function get_available_languages() {
        return $this->available_languages;
    }

    public function get_current_language() {
        return $this->current_language;
    }

    public function set_language($language_code) {
        if (isset($this->available_languages[$language_code])) {
            $this->current_language = $language_code;
            return $this->load_language_file($language_code);
        }
        return false;
    }

    public function translate($text, $context = '', $target_lang = null) {
        $lang = $target_lang ?: $this->current_language;
        
        if (isset($this->translations[$lang][$context][$text])) {
            return $this->translations[$lang][$context][$text];
        }
        
        // Fallback to WordPress translation
        return __($text, 'vortex-ai');
    }

    public function load_language_file($language) {
        $file_path = VORTEX_PLUGIN_DIR . 'languages/' . $language . '.php';
        
        if (file_exists($file_path)) {
            $translations = include $file_path;
            if (is_array($translations)) {
                $this->translations[$language] = $translations;
                return true;
            }
        }
        
        // Load WordPress translations
        load_textdomain('vortex-ai', VORTEX_PLUGIN_DIR . 'languages/vortex-ai-' . $language . '.mo');
        
        return false;
    }

    public function register_hooks() {
        add_action('init', [$this, 'load_plugin_textdomain']);
        add_filter('locale', [$this, 'filter_plugin_locale']);
    }

    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            'vortex-ai',
            false,
            dirname(plugin_basename(VORTEX_PLUGIN_FILE)) . '/languages/'
        );
    }

    public function filter_plugin_locale($locale) {
        if (is_admin()) {
            return $locale;
        }
        
        // Allow URL parameter to override
        if (isset($_GET['lang']) && array_key_exists($_GET['lang'], $this->available_languages)) {
            return sanitize_text_field($_GET['lang']);
        }
        
        return $this->current_language;
    }
} 