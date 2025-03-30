<?php
namespace Vortex\AI\Interfaces;

interface LanguageInterface {
    /**
     * Get available languages
     * @return array List of available languages
     */
    public function get_available_languages();

    /**
     * Get current language
     * @return string Current language code
     */
    public function get_current_language();

    /**
     * Set current language
     * @param string $language_code Language code to set
     * @return bool Success status
     */
    public function set_language($language_code);

    /**
     * Translate text
     * @param string $text Text to translate
     * @param string $context Context for translation
     * @param string $target_lang Target language code
     * @return string Translated text
     */
    public function translate($text, $context = '', $target_lang = null);

    /**
     * Load language file
     * @param string $language Language code
     * @return bool Success status
     */
    public function load_language_file($language);
} 