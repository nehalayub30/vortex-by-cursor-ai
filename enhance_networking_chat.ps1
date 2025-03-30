# Enhance networking visualization and chat features
$ErrorActionPreference = "Stop"

# Add network visualization HTML
$networkVisualsHTML = @"
<div class="network-visualization">
    <div class="visualization-controls">
        <button class="view-toggle" data-view="force">Force Graph</button>
        <button class="view-toggle" data-view="circular">Circular</button>
        <button class="view-toggle" data-view="tree">Tree</button>
    </div>
    <div class="network-canvas">
        <canvas id="networkGraph"></canvas>
    </div>
    <div class="network-stats">
        <div class="stat-card">
            <h4>Network Size</h4>
            <span class="stat-value">0</span>
        </div>
        <div class="stat-card">
            <h4>Active Connections</h4>
            <span class="stat-value">0</span>
        </div>
        <div class="stat-card">
            <h4>Engagement Rate</h4>
            <span class="stat-value">0%</span>
        </div>
    </div>
</div>

<!-- Enhanced Chat Interface -->
<div class="chat-system">
    <div class="chat-tabs">
        <button class="chat-tab active" data-chat="users">User Chat</button>
        <button class="chat-tab" data-chat="ai">AI Assistant</button>
    </div>
    
    <div class="chat-container">
        <!-- User to User Chat -->
        <div class="chat-window active" id="userChat">
            <div class="chat-header">
                <div class="chat-user">
                    <img src="images/user-avatar.png" alt="User" class="user-avatar">
                    <span class="user-name">Connected User</span>
                </div>
                <div class="chat-actions">
                    <button class="action-btn"><i class="fas fa-video"></i></button>
                    <button class="action-btn"><i class="fas fa-phone"></i></button>
                    <button class="action-btn"><i class="fas fa-ellipsis-v"></i></button>
                </div>
            </div>
            <div class="chat-messages">
                <!-- Dynamic messages -->
            </div>
            <div class="chat-input">
                <input type="text" placeholder="Type your message...">
                <button class="send-btn"><i class="fas fa-paper-plane"></i></button>
            </div>
        </div>

        <!-- AI Assistant Chat -->
        <div class="chat-window" id="aiChat">
            <div class="chat-header ai-header">
                <div class="ai-assistant">
                    <img src="images/ai-avatar.png" alt="AI" class="ai-avatar">
                    <span class="ai-name">HURAII Assistant</span>
                </div>
            </div>
            <div class="chat-messages ai-messages">
                <!-- Dynamic AI messages -->
            </div>
            <div class="chat-input">
                <input type="text" placeholder="Ask HURAII...">
                <button class="send-btn"><i class="fas fa-robot"></i></button>
            </div>
        </div>
    </div>
</div>
"@

# Add enhanced network visualization CSS
$networkVisualsCSS = @"
.network-visualization {
    position: relative;
    height: 600px;
    background: rgba(0,0,0,0.02);
    border-radius: 15px;
    overflow: hidden;
}

.visualization-controls {
    position: absolute;
    top: 20px;
    right: 20px;
    z-index: 10;
    display: flex;
    gap: 10px;
}

.view-toggle {
    padding: 8px 15px;
    background: rgba(255,255,255,0.9);
    border: 1px solid rgba(0,0,0,0.1);
    border-radius: 20px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.view-toggle:hover {
    background: var(--accent-purple);
    color: white;
}

.network-canvas {
    width: 100%;
    height: 100%;
}

/* Chat System Styling */
.chat-system {
    position: fixed;
    bottom: 20px;
    right: 20px;
    width: 350px;
    background: white;
    border-radius: 15px;
    box-shadow: 0 5px 25px rgba(0,0,0,0.1);
    z-index: 1000;
}

.chat-tabs {
    display: flex;
    border-bottom: 1px solid rgba(0,0,0,0.1);
}

.chat-tab {
    flex: 1;
    padding: 15px;
    text-align: center;
    cursor: pointer;
    background: none;
    border: none;
    color: var(--text-gray);
    transition: all 0.3s ease;
}

.chat-tab.active {
    color: var(--accent-purple);
    border-bottom: 2px solid var(--accent-purple);
}

.chat-window {
    display: none;
    height: 450px;
    flex-direction: column;
}

.chat-window.active {
    display: flex;
}

.chat-header {
    padding: 15px;
    border-bottom: 1px solid rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.chat-messages {
    flex: 1;
    overflow-y: auto;
    padding: 15px;
}

.message {
    margin-bottom: 15px;
    max-width: 80%;
}

.message-sent {
    margin-left: auto;
    background: var(--accent-purple);
    color: white;
    border-radius: 15px 15px 0 15px;
    padding: 10px 15px;
}

.message-received {
    background: #f0f0f0;
    border-radius: 15px 15px 15px 0;
    padding: 10px 15px;
}

.chat-input {
    padding: 15px;
    display: flex;
    gap: 10px;
    border-top: 1px solid rgba(0,0,0,0.1);
}

.chat-input input {
    flex: 1;
    padding: 10px;
    border: 1px solid rgba(0,0,0,0.1);
    border-radius: 20px;
    outline: none;
}

.send-btn {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: var(--accent-purple);
    color: white;
    border: none;
    cursor: pointer;
    transition: all 0.3s ease;
}

.send-btn:hover {
    transform: scale(1.1);
}

/* Animation Effects */
@keyframes messageSlideIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes typingIndicator {
    0% { opacity: 0.2; }
    20% { opacity: 1; }
    100% { opacity: 0.2; }
}

.message {
    animation: messageSlideIn 0.3s ease;
}

.typing-indicator span {
    animation: typingIndicator 1.5s infinite;
}
"@

# Add network visualization JavaScript
$networkVisualsJS = @"
class NetworkVisualizer {
    constructor(canvasId) {
        this.canvas = document.getElementById(canvasId);
        this.ctx = this.canvas.getContext('2d');
        this.nodes = [];
        this.edges = [];
        this.simulation = null;
        this.initialize();
    }

    initialize() {
        this.setupCanvas();
        this.setupSimulation();
        this.animate();
    }

    setupCanvas() {
        this.canvas.width = this.canvas.offsetWidth;
        this.canvas.height = this.canvas.offsetHeight;
    }

    setupSimulation() {
        // Initialize D3 force simulation
        this.simulation = d3.forceSimulation(this.nodes)
            .force('charge', d3.forceManyBody().strength(-50))
            .force('center', d3.forceCenter(this.canvas.width / 2, this.canvas.height / 2))
            .force('collision', d3.forceCollide().radius(30))
            .on('tick', () => this.draw());
    }

    draw() {
        this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
        
        // Draw edges
        this.edges.forEach(edge => {
            this.ctx.beginPath();
            this.ctx.moveTo(edge.source.x, edge.source.y);
            this.ctx.lineTo(edge.target.x, edge.target.y);
            this.ctx.strokeStyle = 'rgba(0,0,0,0.1)';
            this.ctx.stroke();
        });

        // Draw nodes
        this.nodes.forEach(node => {
            this.ctx.beginPath();
            this.ctx.arc(node.x, node.y, 5, 0, 2 * Math.PI);
            this.ctx.fillStyle = node.color || '#e100ff';
            this.ctx.fill();
        });
    }

    animate() {
        requestAnimationFrame(() => this.animate());
        this.simulation.alpha(0.1);
    }

    addNode(node) {
        this.nodes.push(node);
        this.simulation.nodes(this.nodes);
    }

    addEdge(source, target) {
        this.edges.push({ source, target });
        this.simulation.force('link', d3.forceLink(this.edges));
    }
}

// Initialize chat functionality
class ChatSystem {
    constructor() {
        this.initialize();
    }

    initialize() {
        this.setupEventListeners();
        this.initializeAI();
    }

    setupEventListeners() {
        document.querySelectorAll('.chat-tab').forEach(tab => {
            tab.addEventListener('click', () => this.switchChat(tab.dataset.chat));
        });

        document.querySelectorAll('.send-btn').forEach(btn => {
            btn.addEventListener('click', () => this.sendMessage(btn.closest('.chat-window').id));
        });
    }

    switchChat(chatType) {
        document.querySelectorAll('.chat-window').forEach(window => {
            window.classList.remove('active');
        });
        document.getElementById(`${chatType}Chat`).classList.add('active');
    }

    async sendMessage(chatId) {
        const input = document.querySelector(`#${chatId} input`);
        const message = input.value.trim();
        
        if (!message) return;

        this.addMessage(chatId, message, 'sent');
        input.value = '';

        if (chatId === 'aiChat') {
            this.showTypingIndicator(chatId);
            const response = await this.getAIResponse(message);
            this.hideTypingIndicator(chatId);
            this.addMessage(chatId, response, 'received');
        }
    }

    addMessage(chatId, message, type) {
        const messagesContainer = document.querySelector(`#${chatId} .chat-messages`);
        const messageElement = document.createElement('div');
        messageElement.className = `message message-${type}`;
        messageElement.textContent = message;
        messagesContainer.appendChild(messageElement);
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }

    showTypingIndicator(chatId) {
        const indicator = document.createElement('div');
        indicator.className = 'typing-indicator';
        indicator.innerHTML = '<span>.</span><span>.</span><span>.</span>';
        document.querySelector(`#${chatId} .chat-messages`).appendChild(indicator);
    }

    hideTypingIndicator(chatId) {
        const indicator = document.querySelector(`#${chatId} .typing-indicator`);
        if (indicator) indicator.remove();
    }

    async getAIResponse(message) {
        // Implement AI response logic
        return new Promise(resolve => {
            setTimeout(() => {
                resolve('This is an AI response to your message.');
            }, 1000);
        });
    }
}

// Initialize components
document.addEventListener('DOMContentLoaded', () => {
    const networkVisualizer = new NetworkVisualizer('networkGraph');
    const chatSystem = new ChatSystem();
});
"@

# Create directories if they don't exist
$directories = @(
    "demo/images/avatars",
    "demo/js/network",
    "demo/css/chat"
)

foreach ($dir in $directories) {
    New-Item -ItemType Directory -Path $dir -Force | Out-Null
}

# Create files
Add-Content -Path "demo/index.html" -Value $networkVisualsHTML
Add-Content -Path "demo/css/network-visuals.css" -Value $networkVisualsCSS
Add-Content -Path "demo/js/network-visuals.js" -Value $networkVisualsJS

Write-Host "Enhanced networking and chat features added!" -ForegroundColor Green
Write-Host "`nNew features include:" -ForegroundColor Yellow
Write-Host "1. Interactive network visualization" -ForegroundColor Cyan
Write-Host "2. Real-time chat system" -ForegroundColor Cyan
Write-Host "3. AI chat integration" -ForegroundColor Cyan
Write-Host "4. Enhanced animations" -ForegroundColor Cyan

# Offer to open demo
$response = Read-Host "`nWould you like to view the enhanced demo? (y/n)"
if ($response -eq "y") {
    Start-Process "demo/index.html"
} 