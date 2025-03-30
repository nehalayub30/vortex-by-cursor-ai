# Enhance user journey with collector path and additional features
$ErrorActionPreference = "Stop"

# Add enhanced HURAII inspiration messages
$huraiiiMessages = @"
const huraiiiInspiration = {
    artist: [
        'Your creativity is a unique fingerprint in the digital universe...',
        'Every pixel you create tells a story waiting to be discovered...',
        'Transform your imagination into digital masterpieces...',
        'Your art has the power to inspire generations...',
        'Let your creativity flow through the digital canvas...',
        'Bridge the gap between dreams and reality through your art...',
        'Your artistic vision is a gateway to new dimensions...',
        'Create without boundaries, inspire without limits...'
    ],
    collector: [
        'Discover the art that speaks to your soul...',
        'Your collection tells the story of your vision...',
        'Every piece you collect adds to your legacy...',
        'Be part of the digital art revolution...',
        'Your taste shapes the future of digital art...',
        'Connect with artists who inspire you...',
        'Build a collection that reflects your unique vision...',
        'Your patronage empowers artistic innovation...'
    ]
};
"@

# Add collector journey HTML
$collectorJourneyHTML = @"
<!-- Add Collector Journey Section -->
<div class="journey-step" id="step-collector" style="display: none;">
    <div class="collector-dashboard">
        <div class="wallet-section">
            <h3>TOLA Wallet</h3>
            <div class="tola-balance">
                <span class="balance-amount">0 TOLA</span>
                <button class="purchase-tola">Purchase TOLA</button>
            </div>
        </div>

        <div class="ai-strategist-section">
            <div class="ai-chat">
                <div class="ai-message typing">Let's discover your collecting style...</div>
                <div class="collector-quiz">
                    <!-- Dynamic quiz questions -->
                </div>
            </div>
        </div>

        <div class="art-discovery">
            <h3>Discover Artworks</h3>
            <div class="swipe-container">
                <div class="artwork-card">
                    <img src="" alt="Artwork">
                    <div class="artwork-info">
                        <h4 class="artist-name"></h4>
                        <p class="artwork-description"></p>
                    </div>
                    <div class="swipe-actions">
                        <button class="swipe-left"><i class="fas fa-times"></i></button>
                        <button class="swipe-right"><i class="fas fa-heart"></i></button>
                    </div>
                </div>
            </div>
        </div>

        <div class="collector-libraries">
            <div class="library-section">
                <h3>Your Libraries</h3>
                <div class="library-tabs">
                    <button class="tab-btn active" data-tab="liked">Liked Artworks</button>
                    <button class="tab-btn" data-tab="owned">Owned Collection</button>
                    <button class="tab-btn" data-tab="market">Marketplace</button>
                </div>
                <div class="library-content">
                    <!-- Dynamic library content -->
                </div>
            </div>
        </div>

        <div class="networking-hub">
            <h3>Networking</h3>
            <div class="network-tabs">
                <button class="network-btn" data-network="collectors">Collector Network</button>
                <button class="network-btn" data-network="artists">Artist Network</button>
            </div>
            <div class="network-feed">
                <!-- Dynamic networking content -->
            </div>
        </div>

        <div class="metrics-dashboard">
            <h3>Collection Metrics</h3>
            <div class="metrics-grid">
                <div class="metric-card">
                    <h4>Collection Value</h4>
                    <span class="metric-value">0 TOLA</span>
                </div>
                <div class="metric-card">
                    <h4>Ranking</h4>
                    <span class="metric-value">Level 1</span>
                </div>
                <div class="metric-card">
                    <h4>Engagement Score</h4>
                    <span class="metric-value">0</span>
                </div>
            </div>
        </div>
    </div>
</div>
"@

# Add enhanced CSS
$enhancedCSS = @"
/* Enhanced Styles */
.collector-dashboard {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 2rem;
    padding: 2rem;
}

.swipe-container {
    position: relative;
    height: 500px;
    perspective: 1000px;
}

.artwork-card {
    position: absolute;
    width: 100%;
    height: 100%;
    transform-style: preserve-3d;
    transition: transform 0.5s;
}

.artwork-card.swiped-left {
    transform: translateX(-150%) rotateZ(-30deg);
    opacity: 0;
}

.artwork-card.swiped-right {
    transform: translateX(150%) rotateZ(30deg);
    opacity: 0;
}

.swipe-actions {
    position: absolute;
    bottom: 20px;
    left: 0;
    right: 0;
    display: flex;
    justify-content: center;
    gap: 2rem;
}

.swipe-actions button {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    border: none;
    background: var(--primary);
    color: white;
    font-size: 24px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.swipe-actions button:hover {
    transform: scale(1.1);
}

.metrics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.metric-card {
    background: rgba(255, 255, 255, 0.1);
    padding: 1rem;
    border-radius: 10px;
    text-align: center;
}

.metric-value {
    font-size: 1.5rem;
    color: var(--accent);
}

/* Animation for typing effect */
.typing {
    overflow: hidden;
    white-space: nowrap;
    animation: typing 3s steps(40, end);
}

@keyframes typing {
    from { width: 0 }
    to { width: 100% }
}
"@

# Add enhanced JavaScript
$enhancedJS = @"
// Enhanced functionality
class CollectorJourney {
    constructor() {
        this.collectorProfile = {
            style: [],
            preferences: [],
            budget: 0,
            ranking: 1,
            engagementScore: 0
        };
        
        this.quizQuestions = [
            {
                question: 'What types of art resonate with you the most?',
                options: ['Abstract', 'Digital', 'Contemporary', 'Traditional']
            },
            {
                question: 'What is your primary motivation for collecting?',
                options: ['Investment', 'Personal Interest', 'Supporting Artists', 'Building Legacy']
            },
            {
                question: 'What is your preferred price range for acquisitions?',
                options: ['0-100 TOLA', '101-500 TOLA', '501-1000 TOLA', '1000+ TOLA']
            },
            {
                question: 'How would you describe your collecting style?',
                options: ['Focused', 'Diverse', 'Experimental', 'Traditional']
            },
            {
                question: 'What aspects of digital art interest you most?',
                options: ['Technology', 'Creativity', 'Innovation', 'Community']
            }
        ];
    }

    initializeCollector() {
        this.setupWallet();
        this.startQuiz();
        this.initializeArtDiscovery();
        this.setupMetrics();
    }

    setupWallet() {
        document.querySelector('.purchase-tola').addEventListener('click', () => {
            // Implement TOLA purchase flow
        });
    }

    startQuiz() {
        let currentQuestion = 0;
        const quizContainer = document.querySelector('.collector-quiz');
        
        const showQuestion = () => {
            if (currentQuestion >= this.quizQuestions.length) {
                this.completeProfile();
                return;
            }

            const question = this.quizQuestions[currentQuestion];
            quizContainer.innerHTML = this.createQuestionHTML(question);
        };

        showQuestion();
    }

    initializeArtDiscovery() {
        const swipeContainer = document.querySelector('.swipe-container');
        
        // Implement swipe functionality
        let startX = 0;
        let currentX = 0;

        swipeContainer.addEventListener('touchstart', (e) => {
            startX = e.touches[0].clientX;
        });

        swipeContainer.addEventListener('touchmove', (e) => {
            currentX = e.touches[0].clientX;
            const diff = currentX - startX;
            const card = document.querySelector('.artwork-card');
            card.style.transform = `translateX(${diff}px) rotate(${diff * 0.1}deg)`;
        });

        swipeContainer.addEventListener('touchend', () => {
            const diff = currentX - startX;
            if (Math.abs(diff) > 100) {
                this.handleSwipe(diff > 0);
            } else {
                this.resetCard();
            }
        });
    }

    setupMetrics() {
        // Initialize metrics tracking
        this.updateMetrics({
            collectionValue: 0,
            ranking: 'Level 1',
            engagementScore: 0
        });
    }

    updateMetrics(metrics) {
        Object.entries(metrics).forEach(([key, value]) => {
            document.querySelector(`[data-metric="${key}"]`).textContent = value;
        });
    }

    handleSwipe(isRight) {
        const card = document.querySelector('.artwork-card');
        card.classList.add(isRight ? 'swiped-right' : 'swiped-left');
        
        if (isRight) {
            this.sendArtistNotification();
            this.addToLikedLibrary();
        }

        setTimeout(() => {
            this.loadNextArtwork();
        }, 300);
    }

    sendArtistNotification() {
        // Implement notification system
    }

    addToLikedLibrary() {
        // Implement library management
    }

    loadNextArtwork() {
        // Implement artwork loading
    }
}

// Initialize collector journey
document.addEventListener('DOMContentLoaded', () => {
    const collectorJourney = new CollectorJourney();
    collectorJourney.initializeCollector();
});
"@

# Update existing files
Add-Content -Path "demo/js/demo.js" -Value $huraiiiMessages
Add-Content -Path "demo/index.html" -Value $collectorJourneyHTML
Add-Content -Path "demo/css/style.css" -Value $enhancedCSS
Add-Content -Path "demo/js/demo.js" -Value $enhancedJS

Write-Host "Enhanced user journey with collector path!" -ForegroundColor Green
Write-Host "`nNew features added:" -ForegroundColor Yellow
Write-Host "1. Enhanced HURAII inspiration messages" -ForegroundColor Cyan
Write-Host "2. Comprehensive collector journey" -ForegroundColor Cyan
Write-Host "3. Art discovery system with swipe functionality" -ForegroundColor Cyan
Write-Host "4. Multiple libraries management" -ForegroundColor Cyan
Write-Host "5. Networking hub" -ForegroundColor Cyan
Write-Host "6. Metrics and ranking system" -ForegroundColor Cyan

# Offer to open updated demo
$response = Read-Host "`nWould you like to open the updated demo? (y/n)"
if ($response -eq "y") {
    Start-Process "demo/index.html"
}