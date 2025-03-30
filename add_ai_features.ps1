# Add AI features and Midjourney-style widget
$ErrorActionPreference = "Stop"

# Add AI Chat Widget HTML
$aiWidgetHTML = @"
<div class="ai-generation-widget">
    <!-- AI Chat Interface -->
    <div class="ai-chat-interface">
        <div class="ai-chat-header">
            <div class="ai-assistant-info">
                <img src="images/huraii-avatar.png" alt="HURAII" class="ai-avatar">
                <div class="ai-status">
                    <span class="ai-name">HURAII</span>
                    <span class="ai-mode">Art Generation Mode</span>
                </div>
            </div>
        </div>

        <div class="generation-workspace">
            <!-- Generation Grid -->
            <div class="generation-grid">
                <div class="generation-slot" data-slot="1">
                    <div class="generation-preview"></div>
                    <div class="generation-actions">
                        <button class="action-btn upscale">U1</button>
                        <button class="action-btn vary">V1</button>
                    </div>
                </div>
                <!-- Repeat for slots 2-4 -->
            </div>

            <!-- Command Interface -->
            <div class="command-interface">
                <div class="prompt-area">
                    <textarea 
                        placeholder="/imagine prompt: A digital artwork featuring the Tree of Life with neon energy flows, cyberpunk style --ar 1:1 --v 5.2"
                        class="prompt-input"
                    ></textarea>
                </div>
                <div class="command-buttons">
                    <button class="command-btn imagine">
                        <i class="fas fa-wand-magic-sparkles"></i> Imagine
                    </button>
                    <button class="command-btn variation">
                        <i class="fas fa-shuffle"></i> Variation
                    </button>
                    <button class="command-btn upscale">
                        <i class="fas fa-up-right-and-down-left-from-center"></i> Upscale
                    </button>
                </div>
            </div>
        </div>

        <!-- Generation Progress -->
        <div class="generation-progress">
            <div class="progress-bar">
                <div class="progress-fill"></div>
            </div>
            <div class="progress-status">Generating variations...</div>
        </div>
    </div>
</div>
"@

# Add AI Widget Styles
$aiWidgetCSS = @"
.ai-generation-widget {
    background: rgba(13, 13, 13, 0.95);
    border-radius: 12px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    padding: 20px;
    margin: 20px;
    color: #ffffff;
}

.ai-chat-header {
    display: flex;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.ai-assistant-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.ai-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    border: 2px solid #ff00ea;
}

.ai-name {
    font-weight: 600;
    font-size: 16px;
    color: #ff00ea;
}

.ai-mode {
    font-size: 12px;
    color: rgba(255, 255, 255, 0.6);
}

.generation-workspace {
    margin: 20px 0;
}

.generation-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
    margin-bottom: 20px;
}

.generation-slot {
    aspect-ratio: 1;
    background: rgba(0, 0, 0, 0.3);
    border-radius: 8px;
    overflow: hidden;
    position: relative;
}

.generation-preview {
    width: 100%;
    height: 100%;
    background-size: cover;
    background-position: center;
    transition: transform 0.3s ease;
}

.generation-actions {
    position: absolute;
    bottom: 10px;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    gap: 8px;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.generation-slot:hover .generation-actions {
    opacity: 1;
}

.action-btn {
    background: rgba(0, 0, 0, 0.7);
    color: white;
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 4px;
    padding: 4px 8px;
    font-size: 12px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.action-btn:hover {
    background: #ff00ea;
    border-color: #ff00ea;
}

.command-interface {
    margin-top: 20px;
}

.prompt-area {
    margin-bottom: 15px;
}

.prompt-input {
    width: 100%;
    background: rgba(0, 0, 0, 0.3);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 8px;
    padding: 12px;
    color: white;
    font-size: 14px;
    resize: vertical;
    min-height: 60px;
}

.command-buttons {
    display: flex;
    gap: 10px;
}

.command-btn {
    padding: 10px 20px;
    border-radius: 6px;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    transition: all 0.3s ease;
}

.command-btn.imagine {
    background: linear-gradient(45deg, #ff00ea, #6600ff);
    color: white;
}

.command-btn.variation {
    background: rgba(255, 255, 255, 0.1);
    color: white;
}

.command-btn.upscale {
    background: rgba(255, 255, 255, 0.1);
    color: white;
}

.generation-progress {
    margin-top: 20px;
}

.progress-bar {
    height: 4px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 2px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(45deg, #ff00ea, #6600ff);
    width: 0%;
    transition: width 0.3s ease;
}

.progress-status {
    margin-top: 8px;
    font-size: 12px;
    color: rgba(255, 255, 255, 0.6);
}

@keyframes progressAnimation {
    0% { width: 0%; }
    100% { width: 100%; }
}

.generating .progress-fill {
    animation: progressAnimation 3s ease-in-out infinite;
}
"@

# Add AI Widget JavaScript
$aiWidgetJS = @"
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
"@

# Create files
Add-Content -Path "demo/index.html" -Value $aiWidgetHTML
Add-Content -Path "demo/css/ai-widget.css" -Value $aiWidgetCSS
Add-Content -Path "demo/js/ai-widget.js" -Value $aiWidgetJS

Write-Host "AI Generation Widget added!" -ForegroundColor Green
Write-Host "`nNew features:" -ForegroundColor Yellow
Write-Host "1. Midjourney-style interface" -ForegroundColor Cyan
Write-Host "2. Generation grid with 4 slots" -ForegroundColor Cyan
Write-Host "3. Command-based interaction" -ForegroundColor Cyan
Write-Host "4. Progress visualization" -ForegroundColor Cyan

# Offer to view updates
$response = Read-Host "`nWould you like to view the updated demo? (y/n)"
if ($response -eq "y") {
    Start-Process "demo/index.html"
} 