<?php
/**
 * Template for the Business Quiz Widget
 *
 * @package Vortex_AI_Marketplace
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="vortex-business-quiz">
    <form id="vortex-business-quiz-form">
        <div class="quiz-section">
            <h3><?php esc_html_e('Business Goals', 'vortex-ai-marketplace'); ?></h3>
            <div class="quiz-question">
                <p><?php esc_html_e('What is your primary goal as an artist/collector?', 'vortex-ai-marketplace'); ?></p>
                <select name="primary_goal" required>
                    <option value=""><?php esc_html_e('Select an option', 'vortex-ai-marketplace'); ?></option>
                    <option value="sell_art"><?php esc_html_e('Sell my artwork', 'vortex-ai-marketplace'); ?></option>
                    <option value="collect_art"><?php esc_html_e('Build an art collection', 'vortex-ai-marketplace'); ?></option>
                    <option value="both"><?php esc_html_e('Both sell and collect', 'vortex-ai-marketplace'); ?></option>
                </select>
            </div>
        </div>

        <div class="quiz-section">
            <h3><?php esc_html_e('Experience Level', 'vortex-ai-marketplace'); ?></h3>
            <div class="quiz-question">
                <p><?php esc_html_e('How would you describe your experience with digital art?', 'vortex-ai-marketplace'); ?></p>
                <select name="experience_level" required>
                    <option value=""><?php esc_html_e('Select an option', 'vortex-ai-marketplace'); ?></option>
                    <option value="beginner"><?php esc_html_e('Beginner', 'vortex-ai-marketplace'); ?></option>
                    <option value="intermediate"><?php esc_html_e('Intermediate', 'vortex-ai-marketplace'); ?></option>
                    <option value="advanced"><?php esc_html_e('Advanced', 'vortex-ai-marketplace'); ?></option>
                    <option value="professional"><?php esc_html_e('Professional', 'vortex-ai-marketplace'); ?></option>
                </select>
            </div>
        </div>

        <div class="quiz-section">
            <h3><?php esc_html_e('Market Focus', 'vortex-ai-marketplace'); ?></h3>
            <div class="quiz-question">
                <p><?php esc_html_e('What type of art interests you most?', 'vortex-ai-marketplace'); ?></p>
                <select name="art_focus" required>
                    <option value=""><?php esc_html_e('Select an option', 'vortex-ai-marketplace'); ?></option>
                    <option value="digital_paintings"><?php esc_html_e('Digital Paintings', 'vortex-ai-marketplace'); ?></option>
                    <option value="3d_art"><?php esc_html_e('3D Art', 'vortex-ai-marketplace'); ?></option>
                    <option value="ai_generated"><?php esc_html_e('AI-Generated Art', 'vortex-ai-marketplace'); ?></option>
                    <option value="mixed_media"><?php esc_html_e('Mixed Media', 'vortex-ai-marketplace'); ?></option>
                </select>
            </div>
        </div>

        <div class="quiz-section">
            <h3><?php esc_html_e('Investment Level', 'vortex-ai-marketplace'); ?></h3>
            <div class="quiz-question">
                <p><?php esc_html_e('What is your planned monthly investment in digital art?', 'vortex-ai-marketplace'); ?></p>
                <select name="investment_level" required>
                    <option value=""><?php esc_html_e('Select an option', 'vortex-ai-marketplace'); ?></option>
                    <option value="minimal"><?php esc_html_e('Less than $100', 'vortex-ai-marketplace'); ?></option>
                    <option value="moderate"><?php esc_html_e('$100 - $500', 'vortex-ai-marketplace'); ?></option>
                    <option value="significant"><?php esc_html_e('$500 - $2000', 'vortex-ai-marketplace'); ?></option>
                    <option value="professional"><?php esc_html_e('More than $2000', 'vortex-ai-marketplace'); ?></option>
                </select>
            </div>
        </div>

        <div class="quiz-section">
            <h3><?php esc_html_e('Time Commitment', 'vortex-ai-marketplace'); ?></h3>
            <div class="quiz-question">
                <p><?php esc_html_e('How much time can you dedicate to your art business weekly?', 'vortex-ai-marketplace'); ?></p>
                <select name="time_commitment" required>
                    <option value=""><?php esc_html_e('Select an option', 'vortex-ai-marketplace'); ?></option>
                    <option value="minimal"><?php esc_html_e('Less than 5 hours', 'vortex-ai-marketplace'); ?></option>
                    <option value="part_time"><?php esc_html_e('5-15 hours', 'vortex-ai-marketplace'); ?></option>
                    <option value="full_time"><?php esc_html_e('15-40 hours', 'vortex-ai-marketplace'); ?></option>
                    <option value="dedicated"><?php esc_html_e('More than 40 hours', 'vortex-ai-marketplace'); ?></option>
                </select>
            </div>
        </div>

        <?php wp_nonce_field('vortex_business_quiz_nonce', 'vortex_business_quiz_nonce'); ?>
        
        <div class="quiz-submit">
            <button type="submit" class="vortex-button primary">
                <?php esc_html_e('Generate My Business Plan', 'vortex-ai-marketplace'); ?>
            </button>
        </div>
    </form>
</div> 