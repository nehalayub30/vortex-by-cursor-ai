# Add user journey components to existing demo
$ErrorActionPreference = "Stop"

# Add new HTML sections to existing demo
$userJourneyHTML = @"
<!-- Add after existing gamified-interface div -->
<div class="user-journey-container">
    <!-- Step 1: Registration & TOLA Wallet -->
    <div class="journey-step" id="step-registration">
        <h3>Welcome to VORTEX AI</h3>
        <div class="wallet-setup">
            <div class="tola-balance">
                <i class="fas fa-wallet"></i>
                <span class="balance-amount">1000 TOLA</span>
            </div>
            <button class="connect-wallet">Connect Wallet</button>
        </div>
    </div>

    <!-- Step 2: Business Profile -->
    <div class="journey-step" id="step-profile" style="display: none;">
        <div class="ai-strategist">
            <div class="ai-avatar">
                <img src="images/ai-strategist.png" alt="AI Business Strategist">
            </div>
            <div class="ai-chat">
                <p class="typing-effect">Please tell me your business idea...</p>
            </div>
        </div>
        <div class="profile-quiz">
            <!-- Quiz questions appear here dynamically -->
        </div>
    </div>

    <!-- Step 3: Role Selection -->
    <div class="journey-step" id="step-role" style="display: none;">
        <h3>Choose Your Path</h3>
        <div class="role-options">
            <div class="role-card" data-role="artist">
                <i class="fas fa-paint-brush"></i>
                <h4>Artist</h4>
                <p>Create and sell unique AI-enhanced artwork</p>
            </div>
            <div class="role-card" data-role="collector">
                <i class="fas fa-gem"></i>
                <h4>Collector</h4>
                <p>Discover and collect unique digital art</p>
            </div>
        </div>
    </div>

    <!-- Step 4: Artist Studio -->
    <div class="journey-step" id="step-artist-studio" style="display: none;">
        <div class="studio-container">
            <div class="private-cloud">
                <h4>Your Private Cloud</h4>
                <div class="upload-area">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <p>Drop your portfolio here</p>
                </div>
            </div>
            <div class="huraii-inspiration">
                <div class="ai-message">
                    <!-- HURAII inspiration messages appear here -->
                </div>
            </div>
            <div class="art-generation">
                <h4>Generate Artwork</h4>
                <div class="generation-controls">
                    <button class="generate-btn">Generate from Portfolio</button>
                </div>
                <div class="generated-preview">
                    <!-- Generated artwork preview -->
                </div>
            </div>
            <div class="private-library">
                <h4>Your Private Collection</h4>
                <div class="nft-grid">
                    <!-- Generated NFTs appear here -->
                </div>
            </div>
            <div class="marketplace-actions">
                <button class="publish-btn">Publish to Marketplace</button>
                <button class="swap-btn">Artist Swap Zone</button>
            </div>
        </div>
    </div>
</div>
"@

# Add new CSS styles
$userJourneyCSS = @"
/* Add to existing CSS */
.user-journey-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem;
}

.journey-step {
    background: rgba(114, 9, 183, 0.1);
    border-radius: 15px;
    padding: 2rem;
    margin-bottom: 2rem;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(225, 0, 255, 0.2);
}

.wallet-setup {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 10px;
}

.tola-balance {
    font-size: 1.5rem;
    color: var(--accent);
}

.ai-strategist {
    display: flex;
    gap: 2rem;
    margin-bottom: 2rem;
}

.ai-avatar img {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    border: 2px solid var(--primary);
}

.typing-effect {
    animation: typing 2s steps(40, end);
}

.role-options {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 2rem;
}

.role-card {
    text-align: center;
    padding: 2rem;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.role-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(225, 0, 255, 0.3);
}

.studio-container {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 2rem;
}

.upload-area {
    border: 2px dashed var(--primary);
    padding: 2rem;
    text-align: center;
    border-radius: 10px;
    cursor: pointer;
}

.huraii-inspiration {
    background: rgba(114, 9, 183, 0.2);
    padding: 1rem;
    border-radius: 10px;
    margin: 1rem 0;
}

.nft-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 1rem;
}

@keyframes typing {
    from { width: 0 }
    to { width: 100% }
}
"@

# Add new JavaScript functionality
$userJourneyJS = @"
// Add to existing JavaScript
document.addEventListener('DOMContentLoaded', function() {
    let currentStep = 'registration';
    const huraiiiInspiration = [
        'Your creativity knows no bounds...',
        'Let your imagination flow freely...',
        'Transform your vision into reality...'
    ];

    function showStep(step) {
        document.querySelectorAll('.journey-step').forEach(s => s.style.display = 'none');
        document.getElementById(`step-${step}`).style.display = 'block';
    }

    function initializeWallet() {
        document.querySelector('.connect-wallet').addEventListener('click', () => {
            // Simulate wallet connection
            document.querySelector('.tola-balance').classList.add('active');
            setTimeout(() => showStep('profile'), 1000);
        });
    }

    function initializeQuiz() {
        const questions = [
            'What inspires your artistic vision?',
            'How would you describe your artistic style?',
            'What are your creative goals?'
        ];
        // Add quiz logic here
    }

    function initializeStudio() {
        document.querySelector('.upload-area').addEventListener('dragover', (e) => {
            e.preventDefault();
            e.target.classList.add('dragover');
        });

        document.querySelector('.generate-btn').addEventListener('click', () => {
            // Simulate AI art generation
            showGenerationProcess();
        });
    }

    function showGenerationProcess() {
        const preview = document.querySelector('.generated-preview');
        preview.innerHTML = '<div class="generating-animation">Generating...</div>';
        setTimeout(() => {
            preview.innerHTML = '<img src="generated-art.png" alt="Generated Artwork">';
            addToPrivateLibrary();
        }, 3000);
    }

    function addToPrivateLibrary() {
        const nft = document.createElement('div');
        nft.className = 'nft-item';
        nft.innerHTML = `
            <img src="generated-art.png" alt="NFT">
            <div class="nft-info">
                <span class="nft-id">#${Math.random().toString(36).substr(2, 9)}</span>
                <button class="publish-nft">Publish</button>
            </div>
        `;
        document.querySelector('.nft-grid').appendChild(nft);
    }

    // Initialize journey
    initializeWallet();
    initializeQuiz();
    initializeStudio();
});
"@

# Update existing files
Add-Content -Path "demo/index.html" -Value $userJourneyHTML
Add-Content -Path "demo/css/style.css" -Value $userJourneyCSS
Add-Content -Path "demo/js/demo.js" -Value $userJourneyJS

Write-Host "Added user journey components to demo!" -ForegroundColor Green
Write-Host "The demo now includes:" -ForegroundColor Yellow
Write-Host "1. Registration & TOLA wallet integration" -ForegroundColor Cyan
Write-Host "2. Business profile creation with AI strategist" -ForegroundColor Cyan
Write-Host "3. Role selection (Artist/Collector)" -ForegroundColor Cyan
Write-Host "4. Artist studio with:" -ForegroundColor Cyan
Write-Host "   - Private cloud upload" -ForegroundColor Cyan
Write-Host "   - HURAII inspiration system" -ForegroundColor Cyan
Write-Host "   - AI art generation" -ForegroundColor Cyan
Write-Host "   - Private NFT library" -ForegroundColor Cyan
Write-Host "   - Marketplace integration" -ForegroundColor Cyan

# Offer to open updated demo
$response = Read-Host "`nWould you like to open the updated demo? (y/n)"
if ($response -eq "y") {
    Start-Process "demo/index.html"
} 