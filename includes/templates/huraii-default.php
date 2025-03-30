<?php
/**
 * Default template for HURAII artwork generator
 *
 * @package VortexAiAgents
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$container_id = 'vortex-huraii-generator-' . uniqid();
$container_class = 'vortex-huraii-generator ' . esc_attr( $atts['class'] );
$container_style = 'width: ' . esc_attr( $atts['width'] ) . 'px; max-width: 100%;';
?>

<div id="<?php echo esc_attr( $container_id ); ?>" class="<?php echo esc_attr( $container_class ); ?>" style="<?php echo esc_attr( $container_style ); ?>">
    <div class="huraii-generator-header">
        <h3><?php esc_html_e( 'HURAII Artwork Generator', 'vortex-ai-agents' ); ?></h3>
        <p class="huraii-description">
            <?php esc_html_e( 'Create unique artwork using AI. Describe what you want to see, select style options, and generate your vision.', 'vortex-ai-agents' ); ?>
        </p>
    </div>

    <div class="huraii-generator-form">
        <div class="huraii-form-group">
            <label for="<?php echo esc_attr( $container_id ); ?>-prompt" class="huraii-label">
                <?php esc_html_e( 'Describe your artwork', 'vortex-ai-agents' ); ?>
                <span class="huraii-required">*</span>
            </label>
            <textarea 
                id="<?php echo esc_attr( $container_id ); ?>-prompt" 
                class="huraii-prompt-input" 
                rows="3" 
                placeholder="<?php esc_attr_e( 'E.g., A serene landscape with mountains and a lake at sunset', 'vortex-ai-agents' ); ?>"
                required
            ></textarea>
            <p class="huraii-hint">
                <?php esc_html_e( 'Be specific about subject, mood, lighting, and composition for best results.', 'vortex-ai-agents' ); ?>
            </p>
        </div>

        <div class="huraii-form-row">
            <div class="huraii-form-group huraii-form-col">
                <label for="<?php echo esc_attr( $container_id ); ?>-style" class="huraii-label">
                    <?php esc_html_e( 'Art Style', 'vortex-ai-agents' ); ?>
                </label>
                <select id="<?php echo esc_attr( $container_id ); ?>-style" class="huraii-style-select">
                    <option value=""><?php esc_html_e( 'Select a style (optional)', 'vortex-ai-agents' ); ?></option>
                    <option value="impressionism" <?php selected( $atts['style'], 'impressionism' ); ?>>
                        <?php esc_html_e( 'Impressionism', 'vortex-ai-agents' ); ?>
                    </option>
                    <option value="abstract" <?php selected( $atts['style'], 'abstract' ); ?>>
                        <?php esc_html_e( 'Abstract', 'vortex-ai-agents' ); ?>
                    </option>
                    <option value="surrealism" <?php selected( $atts['style'], 'surrealism' ); ?>>
                        <?php esc_html_e( 'Surrealism', 'vortex-ai-agents' ); ?>
                    </option>
                    <option value="pop-art" <?php selected( $atts['style'], 'pop-art' ); ?>>
                        <?php esc_html_e( 'Pop Art', 'vortex-ai-agents' ); ?>
                    </option>
                    <option value="minimalism" <?php selected( $atts['style'], 'minimalism' ); ?>>
                        <?php esc_html_e( 'Minimalism', 'vortex-ai-agents' ); ?>
                    </option>
                    <option value="cubism" <?php selected( $atts['style'], 'cubism' ); ?>>
                        <?php esc_html_e( 'Cubism', 'vortex-ai-agents' ); ?>
                    </option>
                    <option value="expressionism" <?php selected( $atts['style'], 'expressionism' ); ?>>
                        <?php esc_html_e( 'Expressionism', 'vortex-ai-agents' ); ?>
                    </option>
                    <option value="digital-art" <?php selected( $atts['style'], 'digital-art' ); ?>>
                        <?php esc_html_e( 'Digital Art', 'vortex-ai-agents' ); ?>
                    </option>
                </select>
            </div>

            <div class="huraii-form-group huraii-form-col">
                <label for="<?php echo esc_attr( $container_id ); ?>-medium" class="huraii-label">
                    <?php esc_html_e( 'Medium', 'vortex-ai-agents' ); ?>
                </label>
                <select id="<?php echo esc_attr( $container_id ); ?>-medium" class="huraii-medium-select">
                    <option value=""><?php esc_html_e( 'Select a medium (optional)', 'vortex-ai-agents' ); ?></option>
                    <option value="oil" <?php selected( $atts['medium'], 'oil' ); ?>>
                        <?php esc_html_e( 'Oil Paint', 'vortex-ai-agents' ); ?>
                    </option>
                    <option value="watercolor" <?php selected( $atts['medium'], 'watercolor' ); ?>>
                        <?php esc_html_e( 'Watercolor', 'vortex-ai-agents' ); ?>
                    </option>
                    <option value="acrylic" <?php selected( $atts['medium'], 'acrylic' ); ?>>
                        <?php esc_html_e( 'Acrylic', 'vortex-ai-agents' ); ?>
                    </option>
                    <option value="digital" <?php selected( $atts['medium'], 'digital' ); ?>>
                        <?php esc_html_e( 'Digital', 'vortex-ai-agents' ); ?>
                    </option>
                    <option value="pencil" <?php selected( $atts['medium'], 'pencil' ); ?>>
                        <?php esc_html_e( 'Pencil Drawing', 'vortex-ai-agents' ); ?>
                    </option>
                    <option value="charcoal" <?php selected( $atts['medium'], 'charcoal' ); ?>>
                        <?php esc_html_e( 'Charcoal', 'vortex-ai-agents' ); ?>
                    </option>
                    <option value="pastel" <?php selected( $atts['medium'], 'pastel' ); ?>>
                        <?php esc_html_e( 'Pastel', 'vortex-ai-agents' ); ?>
                    </option>
                    <option value="ink" <?php selected( $atts['medium'], 'ink' ); ?>>
                        <?php esc_html_e( 'Ink', 'vortex-ai-agents' ); ?>
                    </option>
                </select>
            </div>
        </div>

        <div class="huraii-form-row">
            <div class="huraii-form-group huraii-form-col">
                <label for="<?php echo esc_attr( $container_id ); ?>-artist" class="huraii-label">
                    <?php esc_html_e( 'Artist Influence', 'vortex-ai-agents' ); ?>
                </label>
                <input 
                    type="text" 
                    id="<?php echo esc_attr( $container_id ); ?>-artist" 
                    class="huraii-artist-input" 
                    placeholder="<?php esc_attr_e( 'E.g., Vincent van Gogh (optional)', 'vortex-ai-agents' ); ?>"
                >
            </div>

            <div class="huraii-form-group huraii-form-col">
                <label for="<?php echo esc_attr( $container_id ); ?>-variations" class="huraii-label">
                    <?php esc_html_e( 'Variations', 'vortex-ai-agents' ); ?>
                </label>
                <select id="<?php echo esc_attr( $container_id ); ?>-variations" class="huraii-variations-select">
                    <option value="1"><?php esc_html_e( '1 image', 'vortex-ai-agents' ); ?></option>
                    <option value="2"><?php esc_html_e( '2 images', 'vortex-ai-agents' ); ?></option>
                    <option value="3"><?php esc_html_e( '3 images', 'vortex-ai-agents' ); ?></option>
                    <option value="4"><?php esc_html_e( '4 images', 'vortex-ai-agents' ); ?></option>
                </select>
            </div>
        </div>

        <div class="huraii-form-actions">
            <button type="button" class="huraii-generate-button">
                <?php esc_html_e( 'Generate Artwork', 'vortex-ai-agents' ); ?>
            </button>
            <div class="huraii-loading" style="display: none;">
                <div class="huraii-spinner"></div>
                <span><?php esc_html_e( 'Creating your artwork...', 'vortex-ai-agents' ); ?></span>
            </div>
        </div>
    </div>

    <div class="huraii-results" style="display: none;">
        <h4 class="huraii-results-title"><?php esc_html_e( 'Your Generated Artwork', 'vortex-ai-agents' ); ?></h4>
        <div class="huraii-gallery"></div>
        <div class="huraii-actions">
            <button type="button" class="huraii-save-button">
                <?php esc_html_e( 'Save to Gallery', 'vortex-ai-agents' ); ?>
            </button>
            <button type="button" class="huraii-new-button">
                <?php esc_html_e( 'Create New Artwork', 'vortex-ai-agents' ); ?>
            </button>
        </div>
    </div>

    <div class="huraii-error" style="display: none;">
        <div class="huraii-error-message"></div>
        <button type="button" class="huraii-retry-button">
            <?php esc_html_e( 'Try Again', 'vortex-ai-agents' ); ?>
        </button>
    </div>
</div>

<script type="text/javascript">
    (function() {
        document.addEventListener('DOMContentLoaded', function() {
            const container = document.getElementById('<?php echo esc_js( $container_id ); ?>');
            if (!container) return;

            const promptInput = container.querySelector('.huraii-prompt-input');
            const styleSelect = container.querySelector('.huraii-style-select');
            const mediumSelect = container.querySelector('.huraii-medium-select');
            const artistInput = container.querySelector('.huraii-artist-input');
            const variationsSelect = container.querySelector('.huraii-variations-select');
            const generateButton = container.querySelector('.huraii-generate-button');
            const loadingElement = container.querySelector('.huraii-loading');
            const resultsElement = container.querySelector('.huraii-results');
            const galleryElement = container.querySelector('.huraii-gallery');
            const errorElement = container.querySelector('.huraii-error');
            const errorMessageElement = container.querySelector('.huraii-error-message');
            const retryButton = container.querySelector('.huraii-retry-button');
            const newButton = container.querySelector('.huraii-new-button');
            const saveButton = container.querySelector('.huraii-save-button');

            // Initialize
            function init() {
                generateButton.addEventListener('click', handleGenerate);
                retryButton.addEventListener('click', handleRetry);
                newButton.addEventListener('click', handleNew);
                saveButton.addEventListener('click', handleSave);
            }

            // Handle generate button click
            function handleGenerate() {
                const prompt = promptInput.value.trim();
                if (!prompt) {
                    showError('<?php echo esc_js( __( 'Please describe the artwork you want to generate.', 'vortex-ai-agents' ) ); ?>');
                    return;
                }

                showLoading();
                hideResults();
                hideError();

                const data = {
                    prompt: prompt,
                    variations: parseInt(variationsSelect.value, 10)
                };

                if (styleSelect.value) {
                    data.style = styleSelect.value;
                }

                if (mediumSelect.value) {
                    data.medium = mediumSelect.value;
                }

                if (artistInput.value.trim()) {
                    data.artist_influence = artistInput.value.trim();
                }

                // Make API request
                fetch(vortexHuraiiParams.apiUrl + 'generate', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': vortexHuraiiParams.nonce
                    },
                    body: JSON.stringify(data)
                })
                .then(response => {
                    if (!response.ok) {
                        return response.json().then(err => {
                            throw new Error(err.message || '<?php echo esc_js( __( 'Error generating artwork', 'vortex-ai-agents' ) ); ?>');
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    hideLoading();
                    if (data.images && data.images.length > 0) {
                        displayResults(data);
                    } else {
                        showError('<?php echo esc_js( __( 'No images were generated. Please try again.', 'vortex-ai-agents' ) ); ?>');
                    }
                })
                .catch(error => {
                    hideLoading();
                    showError(error.message);
                });
            }

            // Display generated images
            function displayResults(data) {
                galleryElement.innerHTML = '';
                
                data.images.forEach(image => {
                    const imgContainer = document.createElement('div');
                    imgContainer.className = 'huraii-gallery-item';
                    
                    const img = document.createElement('img');
                    img.src = image.url;
                    img.alt = data.prompt;
                    img.dataset.id = image.id;
                    
                    const actions = document.createElement('div');
                    actions.className = 'huraii-image-actions';
                    
                    const downloadLink = document.createElement('a');
                    downloadLink.href = image.url;
                    downloadLink.download = 'huraii-artwork.png';
                    downloadLink.className = 'huraii-download-button';
                    downloadLink.innerHTML = '<?php echo esc_js( __( 'Download', 'vortex-ai-agents' ) ); ?>';
                    
                    actions.appendChild(downloadLink);
                    imgContainer.appendChild(img);
                    imgContainer.appendChild(actions);
                    galleryElement.appendChild(imgContainer);
                });
                
                showResults();
            }

            // Handle retry button click
            function handleRetry() {
                hideError();
                handleGenerate();
            }

            // Handle new button click
            function handleNew() {
                hideResults();
                hideError();
            }

            // Handle save button click
            function handleSave() {
                // This would typically save to a user's gallery or collection
                // For now, just show a success message
                alert('<?php echo esc_js( __( 'Artwork saved to your gallery!', 'vortex-ai-agents' ) ); ?>');
            }

            // Show/hide UI elements
            function showLoading() {
                loadingElement.style.display = 'flex';
                generateButton.style.display = 'none';
            }

            function hideLoading() {
                loadingElement.style.display = 'none';
                generateButton.style.display = 'block';
            }

            function showResults() {
                resultsElement.style.display = 'block';
            }

            function hideResults() {
                resultsElement.style.display = 'none';
            }

            function showError(message) {
                errorMessageElement.textContent = message;
                errorElement.style.display = 'block';
            }

            function hideError() {
                errorElement.style.display = 'none';
            }

            // Initialize the component
            init();
        });
    })();
</script>

<style>
    .vortex-huraii-generator {
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
        background-color: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        padding: 24px;
        margin: 20px 0;
    }

    .huraii-generator-header {
        margin-bottom: 20px;
        text-align: center;
    }

    .huraii-generator-header h3 {
        margin-top: 0;
        margin-bottom: 10px;
        font-size: 24px;
        color: #333;
    }

    .huraii-description {
        color: #666;
        margin-bottom: 20px;
    }

    .huraii-form-group {
        margin-bottom: 16px;
    }

    .huraii-form-row {
        display: flex;
        flex-wrap: wrap;
        margin: 0 -8px;
    }

    .huraii-form-col {
        flex: 1;
        padding: 0 8px;
        min-width: 200px;
    }

    .huraii-label {
        display: block;
        margin-bottom: 6px;
        font-weight: 500;
        color: #333;
    }

    .huraii-required {
        color: #e53935;
    }

    .huraii-hint {
        font-size: 12px;
        color: #666;
        margin-top: 4px;
    }

    .huraii-prompt-input,
    .huraii-style-select,
    .huraii-medium-select,
    .huraii-artist-input,
    .huraii-variations-select {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 14px;
        transition: border-color 0.2s;
    }

    .huraii-prompt-input:focus,
    .huraii-style-select:focus,
    .huraii-medium-select:focus,
    .huraii-artist-input:focus,
    .huraii-variations-select:focus {
        border-color: #4a90e2;
        outline: none;
    }

    .huraii-form-actions {
        display: flex;
        justify-content: center;
        margin-top: 24px;
    }

    .huraii-generate-button,
    .huraii-retry-button,
    .huraii-new-button,
    .huraii-save-button {
        background-color: #4a90e2;
        color: white;
        border: none;
        border-radius: 4px;
        padding: 12px 24px;
        font-size: 16px;
        font-weight: 500;
        cursor: pointer;
        transition: background-color 0.2s;
    }

    .huraii-generate-button:hover,
    .huraii-retry-button:hover,
    .huraii-new-button:hover,
    .huraii-save-button:hover {
        background-color: #3a7bc8;
    }

    .huraii-loading {
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .huraii-spinner {
        border: 3px solid rgba(0, 0, 0, 0.1);
        border-top: 3px solid #4a90e2;
        border-radius: 50%;
        width: 24px;
        height: 24px;
        animation: huraii-spin 1s linear infinite;
        margin-right: 10px;
    }

    @keyframes huraii-spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    .huraii-results {
        margin-top: 30px;
    }

    .huraii-results-title {
        margin-bottom: 16px;
        text-align: center;
        font-size: 20px;
        color: #333;
    }

    .huraii-gallery {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 16px;
        margin-bottom: 20px;
    }

    .huraii-gallery-item {
        position: relative;
        border-radius: 4px;
        overflow: hidden;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    }

    .huraii-gallery-item img {
        width: 100%;
        height: auto;
        display: block;
    }

    .huraii-image-actions {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        background: rgba(0, 0, 0, 0.7);
        padding: 8px;
        display: flex;
        justify-content: center;
        opacity: 0;
        transition: opacity 0.2s;
    }

    .huraii-gallery-item:hover .huraii-image-actions {
        opacity: 1;
    }

    .huraii-download-button {
        color: white;
        text-decoration: none;
        font-size: 14px;
        padding: 4px 8px;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 4px;
    }

    .huraii-download-button:hover {
        background: rgba(255, 255, 255, 0.3);
    }

    .huraii-actions {
        display: flex;
        justify-content: center;
        gap: 12px;
    }

    .huraii-save-button {
        background-color: #4caf50;
    }

    .huraii-save-button:hover {
        background-color: #3d8b40;
    }

    .huraii-new-button {
        background-color: #f5f5f5;
        color: #333;
    }

    .huraii-new-button:hover {
        background-color: #e0e0e0;
    }

    .huraii-error {
        margin-top: 20px;
        padding: 16px;
        background-color: #ffebee;
        border-radius: 4px;
        text-align: center;
    }

    .huraii-error-message {
        color: #c62828;
        margin-bottom: 12px;
    }

    .huraii-retry-button {
        background-color: #e53935;
    }

    .huraii-retry-button:hover {
        background-color: #c62828;
    }

    @media (max-width: 600px) {
        .huraii-form-col {
            flex: 100%;
            margin-bottom: 12px;
        }
        
        .huraii-gallery {
            grid-template-columns: 1fr;
        }
    }
</style> 