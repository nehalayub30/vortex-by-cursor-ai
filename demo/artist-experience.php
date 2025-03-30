<?php
/**
 * Artist Experience Demo
 *
 * @package VortexAiAgents
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Enqueue necessary styles and scripts
wp_enqueue_style( 'vortex-demo-styles', plugin_dir_url( __FILE__ ) . 'css/demo-styles.css', array(), '1.0.0' );
wp_enqueue_script( 'vortex-demo-scripts', plugin_dir_url( __FILE__ ) . 'js/demo-scripts.js', array( 'jquery' ), '1.0.0', true );

// Get current user info
$current_user = wp_get_current_user();
$artist_name = $current_user->display_name ? $current_user->display_name : 'Priya Sharma';

// Demo data
$tola_points = 785;
$market_position_score = 82;
$artist_level = 'Mid-Career Contemporary';
$style_category = 'Abstract Landscapes';
$audience_growth = '+14%';
$optimal_price_range = '$1,200 - $2,800';
$active_streak = 12; // Days

// Achievement data
$achievements = array(
    array(
        'title' => 'Style Pioneer',
        'description' => 'Develop a distinctive artistic style recognized by the HURAII analysis system',
        'earned' => true,
        'date' => '2023-11-15',
        'points' => 100,
    ),
    array(
        'title' => 'Market Fit Achiever',
        'description' => 'Reach 80% market fit score based on AI analysis of your portfolio',
        'earned' => true,
        'date' => '2024-01-05',
        'points' => 150,
    ),
    array(
        'title' => 'Consistent Creator',
        'description' => 'Maintain a 10+ day streak of artistic creation or portfolio updates',
        'earned' => true,
        'date' => '2024-04-20',
        'points' => 75,
    ),
    array(
        'title' => 'Gallery Featured',
        'description' => 'Have your work featured in a gallery exhibition',
        'earned' => false,
        'date' => null,
        'points' => 200,
    ),
    array(
        'title' => 'Collector\'s Choice',
        'description' => 'Have your work acquired by 5 different collectors',
        'earned' => false,
        'date' => null,
        'points' => 175,
    ),
);

// Recent market insights
$market_insights = array(
    'Your abstract landscapes with vibrant color palettes show 27% higher engagement',
    'Your pricing is currently 15% below optimal for your career stage',
    'Three distinct collector segments identified for your work',
    'Similar artists in your category have seen 22% growth in the past quarter',
    'Recommended focal points: larger scale works and textural elements',
);

// Generate a nonce for AJAX security
$huraii_nonce = wp_create_nonce( 'huraii_generation_nonce' );
?>

<div class="vortex-demo-container">
    <!-- Header with user info and TOLA stats -->
    <header class="vortex-demo-header">
        <div class="artist-profile">
            <div class="artist-avatar">
                <img src="<?php echo plugin_dir_url( __FILE__ ) . 'img/artist-avatar.jpg'; ?>" alt="Artist Avatar">
            </div>
            <div class="artist-info">
                <h1><?php echo esc_html( $artist_name ); ?></h1>
                <p class="artist-level"><?php echo esc_html( $artist_level ); ?></p>
            </div>
        </div>
        <div class="tola-stats">
            <div class="tola-points">
                <span class="tola-icon">🏆</span>
                <span class="tola-count"><?php echo esc_html( $tola_points ); ?></span>
                <span class="tola-label">TOLA Points</span>
            </div>
            <div class="active-streak">
                <span class="streak-icon">🔥</span>
                <span class="streak-count"><?php echo esc_html( $active_streak ); ?></span>
                <span class="streak-label">Day Streak</span>
            </div>
        </div>
    </header>

    <!-- Main dashboard content -->
    <div class="dashboard-grid">
        <!-- Market Position Panel -->
        <div class="dashboard-panel market-position">
            <h2>Market Position</h2>
            <div class="panel-content">
                <div class="market-score">
                    <div class="score-gauge">
                        <svg viewBox="0 0 120 120">
                            <circle class="score-background" cx="60" cy="60" r="54" />
                            <circle class="score-progress" cx="60" cy="60" r="54" style="--score-value: <?php echo intval( $market_position_score ); ?>;" />
                            <text x="60" y="65" class="score-text"><?php echo esc_html( $market_position_score ); ?>%</text>
                        </svg>
                    </div>
                    <div class="score-details">
                        <h3>Market Fit</h3>
                        <ul>
                            <li><strong>Style:</strong> <?php echo esc_html( $style_category ); ?></li>
                            <li><strong>Audience Growth:</strong> <?php echo esc_html( $audience_growth ); ?></li>
                            <li><strong>Optimal Price:</strong> <?php echo esc_html( $optimal_price_range ); ?></li>
                        </ul>
                    </div>
                </div>
                <div class="market-insights">
                    <h3>Recent Insights</h3>
                    <ul>
                        <?php foreach ( $market_insights as $insight ) : ?>
                            <li><?php echo esc_html( $insight ); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Achievements Panel -->
        <div class="dashboard-panel achievements">
            <h2>TOLA Achievements</h2>
            <div class="panel-content">
                <div class="achievement-list">
                    <?php foreach ( $achievements as $achievement ) : ?>
                        <div class="achievement-item <?php echo $achievement['earned'] ? 'earned' : 'locked'; ?>">
                            <div class="achievement-icon">
                                <?php if ( $achievement['earned'] ) : ?>
                                    <span class="earned-icon">✓</span>
                                <?php else : ?>
                                    <span class="locked-icon">🔒</span>
                                <?php endif; ?>
                            </div>
                            <div class="achievement-details">
                                <h3><?php echo esc_html( $achievement['title'] ); ?></h3>
                                <p><?php echo esc_html( $achievement['description'] ); ?></p>
                                <?php if ( $achievement['earned'] ) : ?>
                                    <span class="earned-date">Earned: <?php echo esc_html( $achievement['date'] ); ?></span>
                                    <span class="earned-points">+<?php echo esc_html( $achievement['points'] ); ?> TOLA</span>
                                <?php else : ?>
                                    <span class="potential-points">+<?php echo esc_html( $achievement['points'] ); ?> TOLA</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Style Analysis Panel -->
        <div class="dashboard-panel style-analysis">
            <h2>Style Analysis</h2>
            <div class="panel-content">
                <div class="style-visualization">
                    <div class="style-chart">
                        <canvas id="styleChart"></canvas>
                    </div>
                </div>
                <div class="style-traits">
                    <h3>Distinctive Elements</h3>
                    <div class="trait-item">
                        <span class="trait-label">Color Intensity</span>
                        <div class="trait-bar">
                            <div class="trait-progress" style="width: 85%;"></div>
                        </div>
                        <span class="trait-value">85%</span>
                    </div>
                    <div class="trait-item">
                        <span class="trait-label">Texture Complexity</span>
                        <div class="trait-bar">
                            <div class="trait-progress" style="width: 72%;"></div>
                        </div>
                        <span class="trait-value">72%</span>
                    </div>
                    <div class="trait-item">
                        <span class="trait-label">Compositional Balance</span>
                        <div class="trait-bar">
                            <div class="trait-progress" style="width: 90%;"></div>
                        </div>
                        <span class="trait-value">90%</span>
                    </div>
                    <div class="trait-item">
                        <span class="trait-label">Subject Abstraction</span>
                        <div class="trait-bar">
                            <div class="trait-progress" style="width: 68%;"></div>
                        </div>
                        <span class="trait-value">68%</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- HURAII Generator Panel -->
        <div class="dashboard-panel huraii-generator">
            <h2>HURAII Creator</h2>
            <div class="panel-content">
                <div class="huraii-form">
                    <div class="form-group">
                        <label for="concept-description">Concept Description</label>
                        <textarea id="concept-description" placeholder="Describe your artistic concept...">Abstract landscape with vibrant colors, focusing on urban elements with textural details and larger scale.</textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-group half">
                            <label for="art-style">Art Style</label>
                            <select id="art-style">
                                <option value="abstract-expressionism" selected>Abstract Expressionism</option>
                                <option value="impressionism">Impressionism</option>
                                <option value="cubism">Cubism</option>
                                <option value="surrealism">Surrealism</option>
                                <option value="minimalism">Minimalism</option>
                            </select>
                        </div>
                        <div class="form-group half">
                            <label for="medium">Medium</label>
                            <select id="medium">
                                <option value="oil-painting" selected>Oil Paint</option>
                                <option value="acrylic">Acrylic</option>
                                <option value="watercolor">Watercolor</option>
                                <option value="digital">Digital</option>
                                <option value="mixed-media">Mixed Media</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group half">
                            <label for="influence-artist">Artist Influence</label>
                            <select id="influence-artist">
                                <option value="none">None</option>
                                <option value="your-style" selected>Your Style</option>
                                <option value="vincent-van-gogh">Vincent van Gogh</option>
                                <option value="georgia-okeeffe">Georgia O'Keeffe</option>
                                <option value="wassily-kandinsky">Wassily Kandinsky</option>
                            </select>
                        </div>
                        <div class="form-group half">
                            <label for="variations">Variations</label>
                            <select id="variations">
                                <option value="1">1</option>
                                <option value="2" selected>2</option>
                                <option value="3">3</option>
                                <option value="4">4</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <button id="generate-artwork" class="huraii-generate-btn" data-nonce="<?php echo esc_attr( $huraii_nonce ); ?>">
                            <span class="btn-icon">✨</span>
                            <span class="btn-text">Generate Concepts</span>
                        </button>
                    </div>
                </div>
                <div class="huraii-results" style="display: none;">
                    <div class="huraii-loading">
                        <div class="loading-spinner"></div>
                        <p>HURAII is creating your concept...</p>
                    </div>
                    <div class="generated-artworks">
                        <div class="generation-message">
                            <h3>Concepts Generated Successfully</h3>
                            <p>Here are concept visualizations based on your artistic direction.</p>
                            <div class="tola-reward">
                                <span class="reward-icon">🏆</span>
                                <span class="reward-text">+15 TOLA points for using HURAII Creator</span>
                            </div>
                        </div>
                        <div class="artwork-grid">
                            <!-- Artworks will be added here dynamically -->
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Audience Insights Panel -->
        <div class="dashboard-panel audience-insights">
            <h2>Audience Insights</h2>
            <div class="panel-content">
                <div class="audience-segments">
                    <h3>Collector Segments</h3>
                    <div class="segment-chart">
                        <canvas id="audienceChart"></canvas>
                    </div>
                </div>
                <div class="geographic-distribution">
                    <h3>Geographic Distribution</h3>
                    <div class="geo-list">
                        <div class="geo-item">
                            <span class="geo-name">New York</span>
                            <div class="geo-bar">
                                <div class="geo-progress" style="width: 35%;"></div>
                            </div>
                            <span class="geo-percentage">35%</span>
                        </div>
                        <div class="geo-item">
                            <span class="geo-name">Los Angeles</span>
                            <div class="geo-bar">
                                <div class="geo-progress" style="width: 22%;"></div>
                            </div>
                            <span class="geo-percentage">22%</span>
                        </div>
                        <div class="geo-item">
                            <span class="geo-name">Chicago</span>
                            <div class="geo-bar">
                                <div class="geo-progress" style="width: 18%;"></div>
                            </div>
                            <span class="geo-percentage">18%</span>
                        </div>
                        <div class="geo-item">
                            <span class="geo-name">Miami</span>
                            <div class="geo-bar">
                                <div class="geo-progress" style="width: 15%;"></div>
                            </div>
                            <span class="geo-percentage">15%</span>
                        </div>
                        <div class="geo-item">
                            <span class="geo-name">Other</span>
                            <div class="geo-bar">
                                <div class="geo-progress" style="width: 10%;"></div>
                            </div>
                            <span class="geo-percentage">10%</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Strategic Recommendations Panel -->
        <div class="dashboard-panel strategic-recommendations">
            <h2>Strategic Recommendations</h2>
            <div class="panel-content">
                <div class="recommendation-list">
                    <div class="recommendation-item">
                        <div class="recommendation-icon">🎨</div>
                        <div class="recommendation-details">
                            <h3>Style Development</h3>
                            <p>Continue exploring larger scale works with textural elements to enhance your distinctive style. HURAII analysis shows this direction aligns with current market interests.</p>
                            <button class="recommendation-action">Explore with HURAII</button>
                        </div>
                    </div>
                    <div class="recommendation-item">
                        <div class="recommendation-icon">💰</div>
                        <div class="recommendation-details">
                            <h3>Pricing Strategy</h3>
                            <p>Gradually increase prices by 15% over the next three months to align with optimal market position. Start with larger works.</p>
                            <button class="recommendation-action">View Price Analysis</button>
                        </div>
                    </div>
                    <div class="recommendation-item">
                        <div class="recommendation-icon">🏢</div>
                        <div class="recommendation-details">
                            <h3>Exhibition Opportunity</h3>
                            <p>Three galleries in your area are featuring abstract landscape artists this summer. Present your portfolio to Gallery Modern Space for highest compatibility.</p>
                            <button class="recommendation-action">View Gallery Match</button>
                        </div>
                    </div>
                    <div class="recommendation-item">
                        <div class="recommendation-icon">👥</div>
                        <div class="recommendation-details">
                            <h3>Network Connection</h3>
                            <p>Connect with collector Jordan Williams who has acquired similar works and is actively expanding their collection.</p>
                            <button class="recommendation-action">View Collector Profile</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Demo JavaScript to handle interactions and visualizations -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize charts when page loads
    initializeStyleChart();
    initializeAudienceChart();
    
    // Handle HURAII generation button click
    document.getElementById('generate-artwork').addEventListener('click', function() {
        const huraiiResults = document.querySelector('.huraii-results');
        const huraiiLoading = document.querySelector('.huraii-loading');
        const generatedArtworks = document.querySelector('.generated-artworks');
        const artworkGrid = document.querySelector('.artwork-grid');
        
        // Show results section with loading spinner
        huraiiResults.style.display = 'block';
        huraiiLoading.style.display = 'block';
        generatedArtworks.style.display = 'none';
        
        // Simulate API call delay
        setTimeout(function() {
            // Hide loading, show results
            huraiiLoading.style.display = 'none';
            generatedArtworks.style.display = 'block';
            
            // Clear previous results
            artworkGrid.innerHTML = '';
            
            // Add generated artworks
            const variations = parseInt(document.getElementById('variations').value);
            for (let i = 0; i < variations; i++) {
                const artworkElement = document.createElement('div');
                artworkElement.className = 'artwork-item';
                
                // Select proper demo image based on style
                const style = document.getElementById('art-style').value;
                const imageIndex = i + 1;
                
                artworkElement.innerHTML = `
                    <div class="artwork-image">
                        <img src="${getArtworkImage(style, imageIndex)}" alt="Generated Concept ${imageIndex}">
                    </div>
                    <div class="artwork-actions">
                        <button class="action-btn download-btn">Download</button>
                        <button class="action-btn refine-btn">Refine</button>
                        <button class="action-btn save-btn">Save to Portfolio</button>
                    </div>
                `;
                
                artworkGrid.appendChild(artworkElement);
            }
            
            // Update TOLA points in header
            const tolaCount = document.querySelector('.tola-count');
            const currentPoints = parseInt(tolaCount.textContent);
            tolaCount.textContent = currentPoints + 15;
            
            // Add animation class to TOLA counter
            tolaCount.classList.add('tola-increased');
            setTimeout(() => {
                tolaCount.classList.remove('tola-increased');
            }, 2000);
            
        }, 3000); // 3 second delay to simulate generation
    });
    
    // Helper function to get artwork image path based on style
    function getArtworkImage(style, index) {
        const baseUrl = '<?php echo plugin_dir_url( __FILE__ ) . 'img/generated/'; ?>';
        
        // Map of styles to folder names
        const styleFolders = {
            'abstract-expressionism': 'abstract',
            'impressionism': 'impressionist',
            'cubism': 'cubist',
            'surrealism': 'surreal',
            'minimalism': 'minimal'
        };
        
        const folder = styleFolders[style] || 'abstract';
        return `${baseUrl}${folder}-${index}.jpg`;
    }
    
    // Initialize Style Analysis Chart
    function initializeStyleChart() {
        const ctx = document.getElementById('styleChart').getContext('2d');
        
        // Example data for radar chart showing style characteristics
        const styleChart = new Chart(ctx, {
            type: 'radar',
            data: {
                labels: ['Abstraction', 'Color Vibrancy', 'Texture', 'Composition', 'Movement', 'Emotional Impact'],
                datasets: [{
                    label: 'Your Style',
                    data: [7, 9, 8, 6, 7, 8],
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    pointBackgroundColor: 'rgba(75, 192, 192, 1)',
                    pointBorderColor: '#fff',
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: 'rgba(75, 192, 192, 1)'
                }, {
                    label: 'Market Trend',
                    data: [8, 8, 7, 7, 6, 9],
                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    pointBackgroundColor: 'rgba(255, 99, 132, 1)',
                    pointBorderColor: '#fff',
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: 'rgba(255, 99, 132, 1)'
                }]
            },
            options: {
                scales: {
                    r: {
                        beginAtZero: true,
                        max: 10,
                        ticks: {
                            stepSize: 2
                        }
                    }
                }
            }
        });
    }
    
    // Initialize Audience Chart
    function initializeAudienceChart() {
        const ctx = document.getElementById('audienceChart').getContext('2d');
        
        // Example data for doughnut chart showing audience segments
        const audienceChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Art Enthusiasts', 'Interior Designers', 'Corporate Collectors', 'Private Collectors'],
                datasets: [{
                    data: [42, 23, 15, 20],
                    backgroundColor: [
                        'rgba(75, 192, 192, 0.7)',
                        'rgba(255, 99, 132, 0.7)',
                        'rgba(255, 206, 86, 0.7)',
                        'rgba(54, 162, 235, 0.7)'
                    ],
                    borderColor: [
                        'rgba(75, 192, 192, 1)',
                        'rgba(255, 99, 132, 1)',
                        'rgba(255, 206, 86, 1)',
                        'rgba(54, 162, 235, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'right',
                    }
                }
            }
        });
    }
    
    // Handle recommendation button clicks
    document.querySelectorAll('.recommendation-action').forEach(button => {
        button.addEventListener('click', function() {
            alert('This feature would open the corresponding tool or analysis in a real implementation.');
        });
    });
});
</script> 