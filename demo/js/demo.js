document.addEventListener('DOMContentLoaded', function() {
    const demoButtons = document.querySelectorAll('.demo-nav button');
    const demoViews = document.querySelectorAll('.demo-view');

    demoButtons.forEach(button => {
        button.addEventListener('click', () => {
            const view = button.dataset.view;
            
            demoButtons.forEach(btn => btn.classList.remove('active'));
            demoViews.forEach(view => view.classList.remove('active'));
            
            button.classList.add('active');
            document.getElementById(demo-).classList.add('active');
        });
    });
});
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
        document.getElementById(step-).style.display = 'block';
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
        nft.innerHTML = 
            <img src="generated-art.png" alt="NFT">
            <div class="nft-info">
                <span class="nft-id">#</span>
                <button class="publish-nft">Publish</button>
            </div>
        ;
        document.querySelector('.nft-grid').appendChild(nft);
    }

    // Initialize journey
    initializeWallet();
    initializeQuiz();
    initializeStudio();
});
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
            card.style.transform = 	ranslateX(px) rotate(deg);
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
            document.querySelector([data-metric=""]).textContent = value;
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
