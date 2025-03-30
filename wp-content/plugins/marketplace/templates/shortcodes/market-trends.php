<?php
/**
 * Market Trends template for all users
 * Shows valuable insights without sensitive data
 */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
?>

<div class="vortex-market-trends">
    <h2><?php esc_html_e('Art Market Trends', 'vortex-marketplace'); ?></h2>
    
    <?php if (!empty($trending_styles)): ?>
        <div class="vortex-trend-section">
            <h3><?php esc_html_e('Trending Art Styles', 'vortex-marketplace'); ?></h3>
            <div class="vortex-trend-items">
                <?php foreach ($trending_styles as $index => $style): ?>
                    <div class="vortex-trend-item">
                        <span class="trend-rank"><?php echo $index + 1; ?></span>
                        <div class="trend-details">
                            <h4><?php echo esc_html($style->style_name); ?></h4>
                            <div class="trend-growth">
                                <?php if (isset($style->growth_percentage) && $style->growth_percentage > 0): ?>
                                    <span class="trend-up">↑ <?php echo esc_html($style->growth_percentage); ?>%</span>
                                <?php elseif (isset($style->growth_percentage) && $style->growth_percentage < 0): ?>
                                    <span class="trend-down">↓ <?php echo esc_html(abs($style->growth_percentage)); ?>%</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($market_data['emerging_themes'])): ?>
        <div class="vortex-trend-section">
            <h3><?php esc_html_e('Emerging Themes', 'vortex-marketplace'); ?></h3>
            <div class="vortex-theme-cloud">
                <?php foreach ($market_data['emerging_themes'] as $theme): ?>
                    <span class="theme-tag" style="font-size: <?php echo 100 + min(($theme->growth_percentage * 2), 100); ?>%">
                        <?php echo esc_html($theme->theme_name); ?>
                    </span>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($market_data['most_successful_artists'])): ?>
        <div class="vortex-trend-section">
            <h3><?php esc_html_e('Artists to Watch', 'vortex-marketplace'); ?></h3>
            <div class="vortex-artist-list">
                <?php foreach ($market_data['most_successful_artists'] as $artist): ?>
                    <div class="vortex-artist-card">
                        <?php if (!empty($artist->avatar_url)): ?>
                            <img src="<?php echo esc_url($artist->avatar_url); ?>" alt="<?php echo esc_attr($artist->display_name); ?>" class="artist-avatar">
                        <?php endif; ?>
                        <h4><?php echo esc_html($artist->display_name); ?></h4>
                        <p class="artist-specialty"><?php echo esc_html($artist->specialty); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
    
    <div class="vortex-trend-section">
        <h3><?php esc_html_e('Apply These Insights', 'vortex-marketplace'); ?></h3>
        <div class="vortex-insights-tips">
            <div class="insight-tip">
                <span class="dashicons dashicons-art"></span>
                <h4><?php esc_html_e('For Artists', 'vortex-marketplace'); ?></h4>
                <p><?php esc_html_e('Consider incorporating trending styles and themes into your upcoming work to increase visibility and sales potential.', 'vortex-marketplace'); ?></p>
            </div>
            
            <div class="insight-tip">
                <span class="dashicons dashicons-money-alt"></span>
                <h4><?php esc_html_e('For Collectors', 'vortex-marketplace'); ?></h4>
                <p><?php esc_html_e('Emerging artists and themes often represent good investment opportunities. Consider diversifying your collection with these trends.', 'vortex-marketplace'); ?></p>
            </div>
            
            <div class="insight-tip">
                <span class="dashicons dashicons-groups"></span>
                <h4><?php esc_html_e('For Galleries', 'vortex-marketplace'); ?></h4>
                <p><?php esc_html_e('These insights can help inform your curation and exhibition planning to attract more visitors and increase sales.', 'vortex-marketplace'); ?></p>
            </div>
        </div>
    </div>
</div>

<style>
/* Attractive styling for market trends display */
.vortex-market-trends {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    padding: 30px;
    margin: 20px 0;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
}

.vortex-trend-section {
    margin-bottom: 30px;
}

.vortex-trend-items {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 15px;
}

.vortex-trend-item {
    display: flex;
    align-items: center;
    background: #f8f9fa;
    border-radius: 6px;
    padding: 15px;
    transition: transform 0.2s ease;
}

.vortex-trend-item:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.trend-rank {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 30px;
    height: 30px;
    background: #4a6cf7;
    color: white;
    border-radius: 50%;
    margin-right: 15px;
    font-weight: bold;
}

.trend-details {
    flex-grow: 1;
}

.trend-details h4 {
    margin: 0 0 5px 0;
}

.trend-up {
    color: #28a745;
    font-weight: bold;
}

.trend-down {
    color: #dc3545;
    font-weight: bold;
}

.vortex-theme-cloud {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 6px;
}

.theme-tag {
    padding: 5px 10px;
    background: #4a6cf7;
    color: white;
    border-radius: 20px;
    transition: all 0.2s ease;
}

.theme-tag:hover {
    transform: scale(1.05);
    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
}

.vortex-artist-list {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 20px;
}

.vortex-artist-card {
    text-align: center;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 6px;
    transition: transform 0.2s ease;
}

.vortex-artist-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.artist-avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    object-fit: cover;
    margin-bottom: 10px;
}

.artist-specialty {
    color: #6c757d;
    font-size: 0.9em;
}

.vortex-insights-tips {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
}

.insight-tip {
    background: #f8f9fa;
    border-radius: 6px;
    padding: 20px;
    text-align: center;
}

.insight-tip .dashicons {
    font-size: 2em;
    width: auto;
    height: auto;
    color: #4a6cf7;
    margin-bottom: 10px;
}
</style> 