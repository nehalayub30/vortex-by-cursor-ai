<?php
/**
 * Template for HURAII interface
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/public/partials
 */

// Exit if accessed directly
if (!defined('WPINC')) {
    die;
}
?>

<div class="vortex-huraii-container <?php echo esc_attr($atts['class']); ?>">
    <!-- Initial loading screen -->
    <div id="vortex-huraii-loading" class="vortex-huraii-screen">
        <div class="vortex-huraii-loading-animation">
            <div class="vortex-huraii-logo-pulse"></div>
        </div>
        <p><?php _e('Initializing HURAII...', 'vortex-ai-marketplace'); ?></p>
    </div>
    
    <!-- Access denied screen (for non-artists) -->
    <div id="vortex-huraii-access-denied" class="vortex-huraii-screen" style="display:none;">
        <div class="vortex-huraii-access-icon">
            <i class="dashicons dashicons-lock"></i>
        </div>
        <h2><?php _e('HURAII is for Artists', 'vortex-ai-marketplace'); ?></h2>
        <p><?php _e('HURAII is exclusively available for users with the Artist role.', 'vortex-ai-marketplace'); ?></p>
        <p><?php _e('If you\'re an artist, please update your profile settings.', 'vortex-ai-marketplace'); ?></p>
        <div class="vortex-huraii-actions">
            <a href="<?php echo esc_url(get_permalink(get_option('vortex_profile_page_id'))); ?>" class="vortex-button"><?php _e('Update Profile', 'vortex-ai-marketplace'); ?></a>
        </div>
    </div>
    
    <!-- Seed artwork required screen -->
    <div id="vortex-huraii-seed-required" class="vortex-huraii-screen" style="display:none;">
        <div class="vortex-huraii-seed-icon">
            <i class="dashicons dashicons-upload"></i>
        </div>
        <h2><?php _e('Upload Seed Artwork', 'vortex-ai-marketplace'); ?></h2>
        <p class="vortex-huraii-seed-message"></p>
        <div class="vortex-huraii-actions">
            <button id="vortex-upload-seed-button" class="vortex-button vortex-button-primary"><?php _e('Upload Seed Artwork', 'vortex-ai-marketplace'); ?></button>
        </div>
    </div>
    
    <!-- Seed artwork upload form -->
    <div id="vortex-seed-upload-form" class="vortex-huraii-screen" style="display:none;">
        <h2><?php _e('Upload Your Seed Artwork', 'vortex-ai-marketplace'); ?></h2>
        <p><?php _e('Seed artwork helps HURAII understand your unique style and vision.', 'vortex-ai-marketplace'); ?></p>
        
        <form id="vortex-seed-artwork-form" enctype="multipart/form-data">
            <div class="vortex-form-row">
                <label for="seed_artwork_file"><?php _e('Artwork Image', 'vortex-ai-marketplace'); ?></label>
                <input type="file" id="seed_artwork_file" name="seed_artwork" accept=".jpg,.jpeg,.png" required>
                <div class="vortex-file-preview">
                    <img id="seed-artwork-preview" src="" alt="" style="display:none;">
                </div>
            </div>
            
            <div class="vortex-form-row">
                <label for="seed_artwork_title"><?php _e('Artwork Title', 'vortex-ai-marketplace'); ?></label>
                <input type="text" id="seed_artwork_title" name="title" required>
            </div>
            
            <div class="vortex-form-row">
                <label for="seed_artwork_description"><?php _e('Artwork Description', 'vortex-ai-marketplace'); ?></label>
                <textarea id="seed_artwork_description" name="description" rows="4" required></textarea>
                <p class="description"><?php _e('Describe your artwork, including technique, medium, and artistic vision.', 'vortex-ai-marketplace'); ?></p>
            </div>
            
            <div class="vortex-form-row">
                <label for="seed_artwork_style"><?php _e('Artistic Style', 'vortex-ai-marketplace'); ?></label>
                <select id="seed_artwork_style" name="style">
                    <option value="abstract"><?php _e('Abstract', 'vortex-ai-marketplace'); ?></option>
                    <option value="impressionist"><?php _e('Impressionist', 'vortex-ai-marketplace'); ?></option>
                    <option value="realism"><?php _e('Realism', 'vortex-ai-marketplace'); ?></option>
                    <option value="surrealism"><?php _e('Surrealism', 'vortex-ai-marketplace'); ?></option>
                    <option value="expressionism"><?php _e('Expressionism', 'vortex-ai-marketplace'); ?></option>
                    <option value="minimalism"><?php _e('Minimalism', 'vortex-ai-marketplace'); ?></option>
                    <option value="pop-art"><?php _e('Pop Art', 'vortex-ai-marketplace'); ?></option>
                    <option value="digital-art"><?php _e('Digital Art', 'vortex-ai-marketplace'); ?></option>
                    <option value="mixed-media"><?php _e('Mixed Media', 'vortex-ai-marketplace'); ?></option>
                    <option value="other"><?php _e('Other', 'vortex-ai-marketplace'); ?></option>
                </select>
            </div>
            
            <div class="vortex-form-row vortex-checkbox-row">
                <input type="checkbox" id="seed_artwork_private" name="private" checked>
                <label for="seed_artwork_private"><?php _e('Keep this artwork private', 'vortex-ai-marketplace'); ?></label>
            </div>
            
            <div class="vortex-form-row">
                <label for="seed_artwork_technique"><?php _e('Artistic Technique', 'vortex-ai-marketplace'); ?></label>
                <select id="seed_artwork_technique" name="technique" required>
                    <option value=""><?php _e('Select technique...', 'vortex-ai-marketplace'); ?></option>
                    <option value="oil_painting"><?php _e('Oil Painting', 'vortex-ai-marketplace'); ?></option>
                    <option value="watercolor"><?php _e('Watercolor', 'vortex-ai-marketplace'); ?></option>
                    <option value="acrylic"><?php _e('Acrylic', 'vortex-ai-marketplace'); ?></option>
                    <option value="digital_painting"><?php _e('Digital Painting', 'vortex-ai-marketplace'); ?></option>
                    <option value="photography"><?php _e('Photography', 'vortex-ai-marketplace'); ?></option>
                    <option value="drawing"><?php _e('Drawing', 'vortex-ai-marketplace'); ?></option>
                    <option value="mixed_media"><?php _e('Mixed Media', 'vortex-ai-marketplace'); ?></option>
                    <option value="sculpture"><?php _e('Sculpture', 'vortex-ai-marketplace'); ?></option>
                    <option value="printmaking"><?php _e('Printmaking', 'vortex-ai-marketplace'); ?></option>
                    <option value="other"><?php _e('Other', 'vortex-ai-marketplace'); ?></option>
                </select>
            </div>
            
            <div class="vortex-seed-art-elements">
                <h4><?php _e('Seed-Art Elements', 'vortex-ai-marketplace'); ?></h4>
                <p class="description"><?php _e('These elements help HURAII understand your artistic style better.', 'vortex-ai-marketplace'); ?></p>
                
                <div class="vortex-form-row">
                    <label for="color_palette"><?php _e('Dominant Colors', 'vortex-ai-marketplace'); ?></label>
                    <input type="text" id="color_palette" name="color_palette" placeholder="e.g., deep blue, crimson, gold" />
                    <p class="description"><?php _e('List the main colors used in your artwork.', 'vortex-ai-marketplace'); ?></p>
                </div>
                
                <div class="vortex-form-row">
                    <label for="light_source"><?php _e('Light Source', 'vortex-ai-marketplace'); ?></label>
                    <select id="light_source" name="light_source">
                        <option value=""><?php _e('Select primary light source...', 'vortex-ai-marketplace'); ?></option>
                        <option value="natural"><?php _e('Natural/Daylight', 'vortex-ai-marketplace'); ?></option>
                        <option value="artificial"><?php _e('Artificial Light', 'vortex-ai-marketplace'); ?></option>
                        <option value="multiple"><?php _e('Multiple Sources', 'vortex-ai-marketplace'); ?></option>
                        <option value="atmospheric"><?php _e('Atmospheric Light', 'vortex-ai-marketplace'); ?></option>
                        <option value="none"><?php _e('No Defined Light Source', 'vortex-ai-marketplace'); ?></option>
                    </select>
                </div>
                
                <div class="vortex-form-row">
                    <label for="texture_description"><?php _e('Texture', 'vortex-ai-marketplace'); ?></label>
                    <input type="text" id="texture_description" name="texture_description" placeholder="e.g., smooth, rough, layered" />
                </div>
                
                <div class="vortex-form-row">
                    <label><?php _e('Geometric Elements', 'vortex-ai-marketplace'); ?></label>
                    <div class="vortex-checkbox-group">
                        <div class="vortex-checkbox-row">
                            <input type="checkbox" id="geometric_circles" name="geometric_elements[]" value="circles">
                            <label for="geometric_circles"><?php _e('Circles/Curves', 'vortex-ai-marketplace'); ?></label>
                        </div>
                        <div class="vortex-checkbox-row">
                            <input type="checkbox" id="geometric_triangles" name="geometric_elements[]" value="triangles">
                            <label for="geometric_triangles"><?php _e('Triangles', 'vortex-ai-marketplace'); ?></label>
                        </div>
                        <div class="vortex-checkbox-row">
                            <input type="checkbox" id="geometric_squares" name="geometric_elements[]" value="squares">
                            <label for="geometric_squares"><?php _e('Squares/Rectangles', 'vortex-ai-marketplace'); ?></label>
                        </div>
                        <div class="vortex-checkbox-row">
                            <input type="checkbox" id="geometric_spirals" name="geometric_elements[]" value="spirals">
                            <label for="geometric_spirals"><?php _e('Spirals', 'vortex-ai-marketplace'); ?></label>
                        </div>
                        <div class="vortex-checkbox-row">
                            <input type="checkbox" id="geometric_golden" name="geometric_elements[]" value="golden_ratio">
                            <label for="geometric_golden"><?php _e('Golden Ratio', 'vortex-ai-marketplace'); ?></label>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="vortex-form-actions">
                <button type="button" id="vortex-seed-upload-cancel" class="vortex-button vortex-button-secondary"><?php _e('Cancel', 'vortex-ai-marketplace'); ?></button>
                <button type="submit" class="vortex-button vortex-button-primary"><?php _e('Upload Artwork', 'vortex-ai-marketplace'); ?></button>
            </div>
        </form>
    </div>
    
    <!-- Main HURAII interface -->
    <div id="vortex-huraii-main" class="vortex-huraii-screen" style="display:none;">
        <div class="vortex-huraii-header">
            <div class="vortex-huraii-avatar">
                <div class="vortex-huraii-avatar-inner">
                    <span class="vortex-huraii-icon"></span>
                </div>
            </div>
            <div class="vortex-huraii-greeting">
                <h2 id="vortex-huraii-greeting-text"></h2>
                <p id="vortex-huraii-subgreeting-text"></p>
            </div>
        </div>
        
        <!-- Tabs navigation -->
        <div class="vortex-huraii-tabs">
            <div class="vortex-tab-nav">
                <button class="vortex-tab-button active" data-tab="artwork-generation"><?php _e('Generate Artwork', 'vortex-ai-marketplace'); ?></button>
                <button class="vortex-tab-button" data-tab="artwork-library"><?php _e('My Library', 'vortex-ai-marketplace'); ?></button>
                <button class="vortex-tab-button" data-tab="artwork-analysis"><?php _e('Analyze Artwork', 'vortex-ai-marketplace'); ?></button>
                <button class="vortex-tab-button" data-tab="download-center"><?php _e('Download Center', 'vortex-ai-marketplace'); ?></button>
            </div>
            
            <!-- Artwork Generation Tab -->
            <div class="vortex-tab-content active" data-tab="artwork-generation">
                <div class="vortex-panel">
                    <h3><?php _e('Create New Artwork', 'vortex-ai-marketplace'); ?></h3>
                    
                    <form id="vortex-artwork-generation-form">
                        <div class="vortex-form-row">
                            <label for="generation_source"><?php _e('Generation Source', 'vortex-ai-marketplace'); ?></label>
                            <select id="generation_source" name="generation_source">
                                <option value="seed_only"><?php _e('Seed Artwork Only', 'vortex-ai-marketplace'); ?></option>
                                <option value="seed_and_private"><?php _e('Seed + My Private Library', 'vortex-ai-marketplace'); ?></option>
                                <option value="all_artwork"><?php _e('All My Artwork (Including Marketplace)', 'vortex-ai-marketplace'); ?></option>
                            </select>
                            <p class="description"><?php _e('Select which of your artworks HURAII should use as inspiration.', 'vortex-ai-marketplace'); ?></p>
                        </div>
                        
                        <div class="vortex-form-row">
                            <label for="seed_artwork_select"><?php _e('Primary Seed Artwork', 'vortex-ai-marketplace'); ?></label>
                            <select id="seed_artwork_select" name="seed_artwork_id">
                                <option value=""><?php _e('Select a seed artwork...', 'vortex-ai-marketplace'); ?></option>
                                <!-- Options will be populated via JavaScript -->
                            </select>
                        </div>
                        
                        <div class="vortex-form-row">
                            <label for="generation_prompt"><?php _e('Artistic Direction', 'vortex-ai-marketplace'); ?></label>
                            <textarea id="generation_prompt" name="prompt" rows="4" placeholder="<?php esc_attr_e('Describe what you want HURAII to create based on your seed artwork...', 'vortex-ai-marketplace'); ?>"></textarea>
                        </div>
                        
                        <div class="vortex-form-row">
                            <label><?php _e('Output Size', 'vortex-ai-marketplace'); ?></label>
                            <div class="vortex-radio-group">
                                <label class="vortex-radio-label">
                                    <input type="radio" name="size" value="small" checked>
                                    <span><?php _e('Small', 'vortex-ai-marketplace'); ?></span>
                                    <small>(5 Tola)</small>
                                </label>
                                <label class="vortex-radio-label">
                                    <input type="radio" name="size" value="medium">
                                    <span><?php _e('Medium', 'vortex-ai-marketplace'); ?></span>
                                    <small>(10 Tola)</small>
                                </label>
                                <label class="vortex-radio-label">
                                    <input type="radio" name="size" value="large">
                                    <span><?php _e('Large', 'vortex-ai-marketplace'); ?></span>
                                    <small>(20 Tola)</small>
                                </label>
                            </div>
                        </div>
                        
                        <div class="vortex-form-row vortex-checkbox-row">
                            <input type="checkbox" id="generation_private" name="private" checked>
                            <label for="generation_private"><?php _e('Keep generated artwork private', 'vortex-ai-marketplace'); ?></label>
                        </div>
                        
                        <div class="vortex-form-actions">
                            <button type="submit" class="vortex-button vortex-button-primary"><?php _e('Generate Artwork', 'vortex-ai-marketplace'); ?></button>
                        </div>
                        
                        <div class="vortex-wallet-status">
                            <span><?php _e('Current Tola Balance:', 'vortex-ai-marketplace'); ?></span>
                            <span id="vortex-tola-balance">--</span>
                        </div>
                    </form>
                </div>
                
                <!-- Artwork generation results will appear here -->
                <div id="vortex-generation-results" style="display:none;">
                    <div class="vortex-generation-loading">
                        <div class="vortex-spinner"></div>
                        <p><?php _e('HURAII is creating your artwork...', 'vortex-ai-marketplace'); ?></p>
                    </div>
                    
                    <div class="vortex-generation-success" style="display:none;">
                        <h3><?php _e('Your New Artwork', 'vortex-ai-marketplace'); ?></h3>
                        <div class="vortex-generated-artwork">
                            <img id="vortex-generated-image" src="" alt="">
                        </div>
                        <div class="vortex-generated-info">
                            <h4 id="vortex-generated-title"></h4>
                            <p id="vortex-generated-description"></p>
                            <div class="vortex-generated-meta">
                                <span><?php _e('Generated from your seed artwork using ', 'vortex-ai-marketplace'); ?><strong><?php _e('HURAII', 'vortex-ai-marketplace'); ?></strong></span>
                            </div>
                        </div>
                        <div class="vortex-generated-actions">
                            <button id="vortex-download-artwork" class="vortex-button"><?php _e('Download', 'vortex-ai-marketplace'); ?></button>
                            <button id="vortex-share-artwork" class="vortex-button"><?php _e('Share', 'vortex-ai-marketplace'); ?></button>
                            <button id="vortex-create-new" class="vortex-button vortex-button-primary"><?php _e('Create Another', 'vortex-ai-marketplace'); ?></button>
                        </div>
                    </div>
                    
                    <div class="vortex-generation-error" style="display:none;">
                        <div class="vortex-error-icon">
                            <i class="dashicons dashicons-warning"></i>
                        </div>
                        <h3><?php _e('Generation Failed', 'vortex-ai-marketplace'); ?></h3>
                        <p id="vortex-generation-error-message"></p>
                        <button id="vortex-try-again" class="vortex-button vortex-button-primary"><?php _e('Try Again', 'vortex-ai-marketplace'); ?></button>
                    </div>
                </div>
            </div>
            
            <!-- Artwork Library Tab -->
            <div class="vortex-tab-content" data-tab="artwork-library">
                <div class="vortex-library-controls">
                    <div class="vortex-library-filter">
                        <select id="vortex-library-type">
                            <option value="seed"><?php _e('Seed Artwork', 'vortex-ai-marketplace'); ?></option>
                            <option value="generated"><?php _e('Generated Artwork', 'vortex-ai-marketplace'); ?></option>
                            <option value="private"><?php _e('Private Library', 'vortex-ai-marketplace'); ?></option>
                            <option value="marketplace"><?php _e('Marketplace Library', 'vortex-ai-marketplace'); ?></option>
                            <option value="all"><?php _e('All Artwork', 'vortex-ai-marketplace'); ?></option>
                        </select>
                    </div>
                    <div class="vortex-library-actions">
                        <button id="vortex-upload-artwork" class="vortex-button vortex-button-primary"><?php _e('Upload New', 'vortex-ai-marketplace'); ?></button>
                    </div>
                </div>
                
                <div class="vortex-library-grid" id="vortex-artwork-grid">
                    <!-- Artwork items will be populated via JavaScript -->
                </div>
                
                <div class="vortex-library-pagination">
                    <button id="vortex-prev-page" class="vortex-button vortex-button-small"><?php _e('Previous', 'vortex-ai-marketplace'); ?></button>
                    <span id="vortex-page-info"><?php _e('Page', 'vortex-ai-marketplace'); ?> <span id="vortex-current-page">1</span> <?php _e('of', 'vortex-ai-marketplace'); ?> <span id="vortex-total-pages">1</span></span>
                    <button id="vortex-next-page" class="vortex-button vortex-button-small"><?php _e('Next', 'vortex-ai-marketplace'); ?></button>
                </div>
            </div>
            
            <!-- Artwork Analysis Tab -->
            <div class="vortex-tab-content" data-tab="artwork-analysis">
                <div class="vortex-panel">
                    <h3><?php _e('Analyze Your Artwork', 'vortex-ai-marketplace'); ?></h3>
                    <p><?php _e('Select an artwork from your library to receive AI-powered insights about the style, technique, and artistic elements.', 'vortex-ai-marketplace'); ?></p>
                    
                    <form id="vortex-artwork-analysis-form">
                        <div class="vortex-form-row">
                            <label for="analysis_artwork_select"><?php _e('Select Artwork', 'vortex-ai-marketplace'); ?></label>
                            <select id="analysis_artwork_select" name="artwork_id">
                                <option value=""><?php _e('Select an artwork to analyze...', 'vortex-ai-marketplace'); ?></option>
                                <!-- Options will be populated via JavaScript -->
                            </select>
                        </div>
                        
                        <div class="vortex-form-row">
                            <label for="analysis_focus"><?php _e('Analysis Focus', 'vortex-ai-marketplace'); ?></label>
                            <select id="analysis_focus" name="focus">
                                <option value="general"><?php _e('General Analysis', 'vortex-ai-marketplace'); ?></option>
                                <option value="technique"><?php _e('Technique & Execution', 'vortex-ai-marketplace'); ?></option>
                                <option value="composition"><?php _e('Composition & Design', 'vortex-ai-marketplace'); ?></option>
                                <option value="emotional"><?php _e('Emotional Impact', 'vortex-ai-marketplace'); ?></option>
                                <option value="market"><?php _e('Market Potential', 'vortex-ai-marketplace'); ?></option>
                            </select>
                        </div>
                        
                        <div class="vortex-form-actions">
                            <button type="submit" class="vortex-button vortex-button-primary"><?php _e('Analyze Artwork', 'vortex-ai-marketplace'); ?></button>
                        </div>
                    </form>
                </div>
                
                <!-- Analysis results will appear here -->
                <div id="vortex-analysis-results" style="display:none;">
                    <div class="vortex-analysis-loading">
                        <div class="vortex-spinner"></div>
                        <p><?php _e('Analyzing your artwork...', 'vortex-ai-marketplace'); ?></p>
                    </div>
                    
                    <div class="vortex-analysis-content" style="display:none;">
                        <div class="vortex-analysis-artwork">
                            <img id="vortex-analyzed-image" src="" alt="">
                        </div>
                        <div class="vortex-analysis-details">
                            <h3 id="vortex-analysis-title"></h3>
                            <div id="vortex-analysis-text"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Download Center Tab -->
            <div class="vortex-tab-content" data-tab="download-center">
                <div class="vortex-panel">
                    <h3><?php _e('Download Center', 'vortex-ai-marketplace'); ?></h3>
                    <p><?php _e('Download, export, and manage all your generated content.', 'vortex-ai-marketplace'); ?></p>
                    
                    <div class="vortex-download-filters">
                        <div class="vortex-filter-group">
                            <label for="download-type-filter"><?php _e('Content Type', 'vortex-ai-marketplace'); ?></label>
                            <select id="download-type-filter">
                                <option value="all"><?php _e('All Content', 'vortex-ai-marketplace'); ?></option>
                                <option value="image"><?php _e('Images', 'vortex-ai-marketplace'); ?></option>
                                <option value="animation"><?php _e('Animations', 'vortex-ai-marketplace'); ?></option>
                                <option value="upscaled"><?php _e('Upscaled Artwork', 'vortex-ai-marketplace'); ?></option>
                            </select>
                        </div>
                        
                        <div class="vortex-filter-group">
                            <label for="download-date-filter"><?php _e('Time Period', 'vortex-ai-marketplace'); ?></label>
                            <select id="download-date-filter">
                                <option value="all"><?php _e('All Time', 'vortex-ai-marketplace'); ?></option>
                                <option value="today"><?php _e('Today', 'vortex-ai-marketplace'); ?></option>
                                <option value="week"><?php _e('This Week', 'vortex-ai-marketplace'); ?></option>
                                <option value="month"><?php _e('This Month', 'vortex-ai-marketplace'); ?></option>
                            </select>
                        </div>
                        
                        <div class="vortex-filter-group">
                            <button id="vortex-apply-download-filters" class="vortex-button"><?php _e('Apply Filters', 'vortex-ai-marketplace'); ?></button>
                        </div>
                    </div>
                    
                    <div class="vortex-download-grid" id="vortex-download-items">
                        <!-- Items will be loaded via AJAX -->
                        <div class="vortex-download-loading">
                            <div class="vortex-spinner"></div>
                            <p><?php _e('Loading your content...', 'vortex-ai-marketplace'); ?></p>
                        </div>
                    </div>
                    
                    <div class="vortex-download-pagination">
                        <button id="vortex-download-prev-page" class="vortex-button vortex-button-small"><?php _e('Previous', 'vortex-ai-marketplace'); ?></button>
                        <span id="vortex-download-page-info"><?php _e('Page', 'vortex-ai-marketplace'); ?> <span id="vortex-download-current-page">1</span> <?php _e('of', 'vortex-ai-marketplace'); ?> <span id="vortex-download-total-pages">1</span></span>
                        <button id="vortex-download-next-page" class="vortex-button vortex-button-small"><?php _e('Next', 'vortex-ai-marketplace'); ?></button>
                    </div>
                </div>
                
                <!-- Batch download section -->
                <div class="vortex-panel">
                    <h3><?php _e('Batch Actions', 'vortex-ai-marketplace'); ?></h3>
                    <div class="vortex-batch-actions">
                        <button id="vortex-download-selected" class="vortex-button vortex-button-primary" disabled><?php _e('Download Selected', 'vortex-ai-marketplace'); ?></button>
                        <button id="vortex-download-all" class="vortex-button"><?php _e('Download All', 'vortex-ai-marketplace'); ?></button>
                        <button id="vortex-create-portfolio" class="vortex-button"><?php _e('Create Portfolio', 'vortex-ai-marketplace'); ?></button>
                    </div>
                </div>
                
                <!-- Download progress modal -->
                <div id="vortex-download-modal" class="vortex-modal" style="display:none;">
                    <div class="vortex-modal-content">
                        <div class="vortex-modal-header">
                            <h3><?php _e('Preparing Downloads', 'vortex-ai-marketplace'); ?></h3>
                            <span class="vortex-modal-close">&times;</span>
                        </div>
                        <div class="vortex-modal-body">
                            <div class="vortex-download-progress">
                                <div class="vortex-spinner"></div>
                                <p id="vortex-download-status"><?php _e('Preparing your files...', 'vortex-ai-marketplace'); ?></p>
                                <div class="vortex-progress-bar">
                                    <div class="vortex-progress-fill" style="width: 0%"></div>
                                </div>
                                <p class="vortex-download-count"><span id="vortex-download-current">0</span> <?php _e('of', 'vortex-ai-marketplace'); ?> <span id="vortex-download-total">0</span> <?php _e('files prepared', 'vortex-ai-marketplace'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div> 