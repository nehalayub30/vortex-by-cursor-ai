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
        document.getElementById(${chatType}Chat).classList.add('active');
    }

    async sendMessage(chatId) {
        const input = document.querySelector(# input);
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
        const messagesContainer = document.querySelector(# .chat-messages);
        const messageElement = document.createElement('div');
        messageElement.className = message message-;
        messageElement.textContent = message;
        messagesContainer.appendChild(messageElement);
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }

    showTypingIndicator(chatId) {
        const indicator = document.createElement('div');
        indicator.className = 'typing-indicator';
        indicator.innerHTML = '<span>.</span><span>.</span><span>.</span>';
        document.querySelector(# .chat-messages).appendChild(indicator);
    }

    hideTypingIndicator(chatId) {
        const indicator = document.querySelector(# .typing-indicator);
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
