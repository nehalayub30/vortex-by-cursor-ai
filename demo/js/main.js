document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
});

function initializeApp() {
    setupNavigation();
    initializeHURAII();
    setupMarketplace();
    initializeWallet();
}

function setupNavigation() {
    const navLinks = document.querySelectorAll('.nav-center a');
    navLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            const section = e.target.getAttribute('href').substring(1);
            navigateToSection(section);
        });
    });
}

function initializeHURAII() {
    const assistant = document.querySelector('.huraii-assistant');
    const toggle = assistant.querySelector('.assistant-toggle');
    
    toggle.addEventListener('click', () => {
        const panel = assistant.querySelector('.assistant-panel');
        panel.classList.toggle('active');
    });
}

function setupMarketplace() {
    // Initialize marketplace functionality
    loadFeaturedArtworks();
    setupFilters();
    initializeTransactions();
}

function initializeWallet() {
    const walletBtn = document.querySelector('.wallet-btn');
    walletBtn.addEventListener('click', async () => {
        try {
            // Initialize TOLA wallet connection
            await connectWallet();
        } catch (error) {
            console.error('Wallet connection failed:', error);
        }
    });
}

async function connectWallet() {
    // Implement wallet connection logic
}

function loadFeaturedArtworks() {
    // Load featured artworks
    const artworkGrid = document.querySelector('.artwork-grid');
    // Implement artwork loading logic
}

function setupFilters() {
    // Initialize marketplace filters
    const filters = document.querySelector('.market-filters');
    // Implement filter logic
}

function initializeTransactions() {
    // Setup transaction handling
    // Implement transaction logic
}
