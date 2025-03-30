class AIGenerationWidget {
    constructor() {
        this.initialize();
    }

    initialize() {
        this.setupEventListeners();
        this.setupCommands();
    }

    setupEventListeners() {
        document.querySelector('.prompt-input').addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                this.handleGeneration();
            }
        });

        document.querySelector('.command-btn.imagine').addEventListener('click', () => {
            this.handleGeneration();
        });
    }

    setupCommands() {
        this.commands = {
            imagine: (prompt) => this.generateImage(prompt),
            upscale: (index) => this.upscaleImage(index),
            vary: (index) => this.createVariation(index)
        };
    }

    async handleGeneration() {
        const prompt = document.querySelector('.prompt-input').value;
        if (!prompt) return;

        this.startProgress();
        try {
            await this.commands.imagine(prompt);
        } finally {
            this.stopProgress();
        }
    }

    startProgress() {
        document.querySelector('.generation-progress').classList.add('generating');
        document.querySelector('.progress-status').textContent = 'Generating artwork...';
    }

    stopProgress() {
        document.querySelector('.generation-progress').classList.remove('generating');
        document.querySelector('.progress-status').textContent = 'Generation complete';
    }

    async generateImage(prompt) {
        // Simulate AI generation
        await new Promise(resolve => setTimeout(resolve, 3000));
        // Update preview slots with generated images
    }
}

// Initialize widget
document.addEventListener('DOMContentLoaded', () => {
    const aiWidget = new AIGenerationWidget();
});
