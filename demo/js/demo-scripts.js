/**
 * VORTEX AI AGENTS Artist Demo Scripts
 * 
 * Handles functionality for the artist dashboard, HURAII generator,
 * and visualization of data using Chart.js.
 */

// Load Chart.js from CDN if not already included
(function loadChartJs() {
    if (typeof Chart === 'undefined') {
        const script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js';
        script.integrity = 'sha256-+8RZJLOWupRJwHN+lZvsGzPWOcUCGBmv9GGP4BDe2YU=';
        script.crossOrigin = 'anonymous';
        script.onload = initializeCharts;
        document.head.appendChild(script);
    } else {
        document.addEventListener('DOMContentLoaded', initializeCharts);
    }
})();

/**
 * Initialize all charts when DOM and Chart.js are loaded
 */
function initializeCharts() {
    // Check if we should initialize charts (page might not have them)
    if (document.getElementById('styleChart')) {
        initializeStyleChart();
    }
    
    if (document.getElementById('audienceChart')) {
        initializeAudienceChart();
    }
    
    // Setup interactive elements
    setupHuraiiGenerator();
    setupRecommendationActions();
    setupAchievementHovers();
}

/**
 * Initialize the Style Analysis radar chart
 */
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
                backgroundColor: 'rgba(78, 122, 169, 0.2)',
                borderColor: 'rgba(78, 122, 169, 1)',
                pointBackgroundColor: 'rgba(78, 122, 169, 1)',
                pointBorderColor: '#fff',
                pointHoverBackgroundColor: '#fff',
                pointHoverBorderColor: 'rgba(78, 122, 169, 1)'
            }, {
                label: 'Market Trend',
                data: [8, 8, 7, 7, 6, 9],
                backgroundColor: 'rgba(225, 123, 88, 0.2)',
                borderColor: 'rgba(225, 123, 88, 1)',
                pointBackgroundColor: 'rgba(225, 123, 88, 1)',
                pointBorderColor: '#fff',
                pointHoverBackgroundColor: '#fff',
                pointHoverBorderColor: 'rgba(225, 123, 88, 1)'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                r: {
                    beginAtZero: true,
                    max: 10,
                    ticks: {
                        stepSize: 2,
                        showLabelBackdrop: false,
                        font: {
                            size: 10
                        }
                    },
                    pointLabels: {
                        font: {
                            size: 11
                        }
                    }
                }
            },
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        boxWidth: 12,
                        padding: 15,
                        usePointStyle: true
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + context.raw + '/10';
                        }
                    }
                }
            }
        }
    });
}

/**
 * Initialize the Audience Insights doughnut chart
 */
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
                    'rgba(78, 122, 169, 0.8)',
                    'rgba(225, 123, 88, 0.8)',
                    'rgba(93, 201, 148, 0.8)',
                    'rgba(185, 160, 230, 0.8)'
                ],
                borderColor: [
                    'rgba(78, 122, 169, 1)',
                    'rgba(225, 123, 88, 1)',
                    'rgba(93, 201, 148, 1)',
                    'rgba(185, 160, 230, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '70%',
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        padding: 15,
                        usePointStyle: true,
                        font: {
                            size: 11
                        }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.label + ': ' + context.raw + '%';
                        }
                    }
                }
            }
        }
    });
}

/**
 * Setup HURAII generator functionality
 */
function setupHuraiiGenerator() {
    // Get generator elements
    const generateButton = document.getElementById('generate-artwork');
    if (!generateButton) return;
    
    // Add click handler to generate button
    generateButton.addEventListener('click', function() {
        // Get form values
        const description = document.getElementById('concept-description').value;
        const artStyle = document.getElementById('art-style').value;
        const medium = document.getElementById('medium').value;
        const artistInfluence = document.getElementById('influence-artist').value;
        const variations = parseInt(document.getElementById('variations').value);
        
        // Display elements
        const huraiiResults = document.querySelector('.huraii-results');
        const huraiiLoading = document.querySelector('.huraii-loading');
        const generatedArtworks = document.querySelector('.generated-artworks');
        const artworkGrid = document.querySelector('.artwork-grid');
        
        // Show results section with loading spinner
        huraiiResults.style.display = 'block';
        huraiiLoading.style.display = 'block';
        generatedArtworks.style.display = 'none';
        
        // Simulate generation API call - in a real implementation, this would
        // make an AJAX request to the backend to generate the artwork
        setTimeout(function() {
            // Hide loading, show results
            huraiiLoading.style.display = 'none';
            generatedArtworks.style.display = 'block';
            
            // Clear previous results
            artworkGrid.innerHTML = '';
            
            // Add generated artworks
            for (let i = 0; i < variations; i++) {
                const artworkElement = document.createElement('div');
                artworkElement.className = 'artwork-item';
                
                // Select proper demo image based on style
                const imageIndex = i + 1;
                const imageUrl = getArtworkImage(artStyle, imageIndex);
                
                artworkElement.innerHTML = `
                    <div class="artwork-image">
                        <img src="${imageUrl}" alt="Generated Concept ${imageIndex}">
                    </div>
                    <div class="artwork-actions">
                        <button class="action-btn download-btn">Download</button>
                        <button class="action-btn refine-btn">Refine</button>
                        <button class="action-btn save-btn">Save to Portfolio</button>
                    </div>
                `;
                
                artworkGrid.appendChild(artworkElement);
                
                // Add event listeners to buttons
                const downloadBtn = artworkElement.querySelector('.download-btn');
                const refineBtn = artworkElement.querySelector('.refine-btn');
                const saveBtn = artworkElement.querySelector('.save-btn');
                
                downloadBtn.addEventListener('click', () => downloadArtwork(imageUrl, `concept-${artStyle}-${imageIndex}.jpg`));
                refineBtn.addEventListener('click', () => refineArtwork(i, artStyle, description));
                saveBtn.addEventListener('click', () => saveToPortfolio(i, artStyle, description));
            }
            
            // Reward TOLA points for using HURAII
            awardTolaPoints(15, 'HURAII Creator Usage');
            
        }, 3000); // 3 second delay to simulate generation
    });
}

/**
 * Get artwork image URL based on style and index
 * In a real implementation, this would be generated by HURAII
 * 
 * @param {string} style - Selected art style
 * @param {number} index - Image variation index
 * @returns {string} Image URL
 */
function getArtworkImage(style, index) {
    // Base URL for demo images
    const baseUrl = '../img/generated/';
    
    // Map styles to folder names
    const styleFolders = {
        'abstract-expressionism': 'abstract',
        'impressionism': 'impressionist',
        'cubism': 'cubist',
        'surrealism': 'surreal',
        'minimalism': 'minimal'
    };
    
    // Use default if style not found
    const folder = styleFolders[style] || 'abstract';
    return `${baseUrl}${folder}-${index}.jpg`;
}

/**
 * Award TOLA points to the user and animate the counter
 * 
 * @param {number} points - Number of points to award
 * @param {string} reason - Reason for the award
 */
function awardTolaPoints(points, reason) {
    const tolaCount = document.querySelector('.tola-count');
    if (!tolaCount) return;
    
    const currentPoints = parseInt(tolaCount.textContent);
    const newPoints = currentPoints + points;
    
    // Animate the counter
    tolaCount.classList.add('tola-increased');
    setTimeout(() => {
        tolaCount.textContent = newPoints;
        
        // Remove animation class after a delay
        setTimeout(() => {
            tolaCount.classList.remove('tola-increased');
            
            // Display toast notification
            showToast(`+${points} TOLA points awarded for ${reason}!`);
            
            // In a real implementation, this would check if any achievements were unlocked
            checkForAchievements(newPoints);
        }, 500);
    }, 500);
}

/**
 * Display a toast notification
 * 
 * @param {string} message - Message to display
 */
function showToast(message) {
    // Create toast element
    const toast = document.createElement('div');
    toast.className = 'toast-notification';
    toast.textContent = message;
    
    // Add to document
    document.body.appendChild(toast);
    
    // Trigger animation
    setTimeout(() => {
        toast.classList.add('show');
        
        // Remove after display
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => {
                document.body.removeChild(toast);
            }, 300);
        }, 3000);
    }, 100);
}

/**
 * Check if any achievements were unlocked
 * 
 * @param {number} currentPoints - Current TOLA points
 */
function checkForAchievements(currentPoints) {
    // In a real implementation, this would query the backend for newly unlocked achievements
    
    // For demo purposes, simulate unlocking an achievement
    if (currentPoints >= 800 && !document.querySelector('.achievement-item:nth-child(4).earned')) {
        // Update the fourth achievement (Gallery Featured) to earned status
        const galleryAchievement = document.querySelector('.achievement-item:nth-child(4)');
        if (galleryAchievement) {
            galleryAchievement.classList.remove('locked');
            galleryAchievement.classList.add('earned');
            
            const icon = galleryAchievement.querySelector('.achievement-icon');
            icon.innerHTML = '<span class="earned-icon">✓</span>';
            
            const details = galleryAchievement.querySelector('.achievement-details');
            const pointsSpan = details.querySelector('.potential-points');
            
            if (pointsSpan) {
                // Get points value
                const pointsValue = pointsSpan.textContent.match(/\d+/)[0];
                
                // Update display
                pointsSpan.remove();
                
                // Create date element
                const dateSpan = document.createElement('span');
                dateSpan.className = 'earned-date';
                
                // Create today's date in YYYY-MM-DD format
                const today = new Date();
                const dateString = today.toISOString().split('T')[0];
                
                dateSpan.textContent = `Earned: ${dateString}`;
                
                // Create points element
                const earnedPoints = document.createElement('span');
                earnedPoints.className = 'earned-points';
                earnedPoints.textContent = `+${pointsValue} TOLA`;
                
                details.appendChild(dateSpan);
                details.appendChild(earnedPoints);
            }
            
            // Show achievement unlock notification
            showToast('🏆 Achievement Unlocked: Gallery Featured!');
        }
    }
}

/**
 * Setup recommendation action button handlers
 */
function setupRecommendationActions() {
    document.querySelectorAll('.recommendation-action').forEach(button => {
        button.addEventListener('click', function() {
            const actionText = this.textContent.trim();
            const recommendationTitle = this.closest('.recommendation-details').querySelector('h3').textContent;
            
            // In a real implementation, this would take different actions based on the button
            alert(`This would open the ${actionText} tool for: ${recommendationTitle}`);
            
            // Award TOLA points for engaging with recommendations
            awardTolaPoints(5, 'Recommendation Engagement');
        });
    });
}

/**
 * Setup achievement hover effects and interactions
 */
function setupAchievementHovers() {
    document.querySelectorAll('.achievement-item').forEach(item => {
        item.addEventListener('mouseenter', function() {
            if (this.classList.contains('locked')) {
                const title = this.querySelector('h3').textContent;
                showToast(`Complete "${title}" to earn TOLA points!`);
            }
        });
    });
}

/**
 * Download artwork image
 * 
 * @param {string} imageUrl - URL of the image to download
 * @param {string} filename - Filename to save as
 */
function downloadArtwork(imageUrl, filename) {
    // Create a temporary link and trigger download
    const link = document.createElement('a');
    link.href = imageUrl;
    link.download = filename;
    link.target = '_blank';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    showToast('Artwork downloaded successfully!');
}

/**
 * Refine artwork with additional parameters
 * 
 * @param {number} index - Index of the artwork to refine
 * @param {string} style - Art style
 * @param {string} description - Original description
 */
function refineArtwork(index, style, description) {
    // In a real implementation, this would open a refinement interface
    // For demo purposes, show a simple dialog
    alert(`This would open a refinement interface for concept ${index + 1}.\n\nYou could adjust parameters like color balance, composition, detail level, etc.`);
}

/**
 * Save artwork to user portfolio
 * 
 * @param {number} index - Index of the artwork to save
 * @param {string} style - Art style
 * @param {string} description - Original description
 */
function saveToPortfolio(index, style, description) {
    // In a real implementation, this would save to the user's portfolio
    showToast('Concept saved to your portfolio!');
    
    // Award TOLA points for portfolio growth
    awardTolaPoints(10, 'Portfolio Growth');
}

// CSS for toast notifications
(function addToastStyles() {
    const style = document.createElement('style');
    style.textContent = `
        .toast-notification {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background-color: #4e7aa9;
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            z-index: 1000;
            transform: translateY(100px);
            opacity: 0;
            transition: all 0.3s ease;
        }
        
        .toast-notification.show {
            transform: translateY(0);
            opacity: 1;
        }
    `;
    document.head.appendChild(style);
})(); 