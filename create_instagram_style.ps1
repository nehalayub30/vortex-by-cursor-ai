# Create Instagram-inspired interface
$ErrorActionPreference = "Stop"

# Add Instagram-inspired CSS
$instagramStyleCSS = @"
/* Instagram-inspired Chat Interface */
:root {
    --ig-gradient: linear-gradient(45deg, #833AB4, #FD1D1D, #F77737);
    --ig-background: #FAFAFA;
    --ig-border: #DBDBDB;
    --ig-text: #262626;
    --ig-secondary: #8E8E8E;
    --story-gradient: linear-gradient(45deg, #FFC107, #F77737, #FF1744);
}

.vortex-app {
    max-width: 935px;
    margin: 0 auto;
    background: white;
    border: 1px solid var(--ig-border);
    border-radius: 3px;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
}

/* Stories/Featured Artists Bar */
.stories-bar {
    display: flex;
    padding: 16px;
    border-bottom: 1px solid var(--ig-border);
    overflow-x: auto;
    gap: 15px;
}

.story-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    cursor: pointer;
}

.story-avatar {
    width: 66px;
    height: 66px;
    border-radius: 50%;
    padding: 3px;
    background: var(--story-gradient);
    margin-bottom: 8px;
}

.story-avatar img {
    border: 3px solid white;
    border-radius: 50%;
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.story-username {
    font-size: 12px;
    color: var(--ig-text);
    max-width: 74px;
    overflow: hidden;
    text-overflow: ellipsis;
    text-align: center;
}

/* Main Feed */
.artwork-feed {
    border-bottom: 1px solid var(--ig-border);
}

.post {
    border-bottom: 1px solid var(--ig-border);
    margin-bottom: 12px;
}

.post-header {
    display: flex;
    align-items: center;
    padding: 14px 16px;
}

.post-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    margin-right: 12px;
}

.post-user-info {
    flex: 1;
}

.post-username {
    font-weight: 600;
    color: var(--ig-text);
    font-size: 14px;
}

.post-location {
    font-size: 12px;
    color: var(--ig-text);
}

.post-options {
    padding: 8px;
}

.post-image {
    width: 100%;
    aspect-ratio: 1;
    object-fit: cover;
}

.post-actions {
    padding: 6px 16px;
    display: flex;
    align-items: center;
    gap: 16px;
}

.action-button {
    background: none;
    border: none;
    padding: 8px;
    cursor: pointer;
}

.action-button i {
    font-size: 24px;
    color: var(--ig-text);
}

.action-button:hover i {
    color: var(--ig-secondary);
}

/* Chat Interface */
.chat-container {
    display: flex;
    height: 100vh;
    max-height: 800px;
}

.chat-sidebar {
    width: 350px;
    border-right: 1px solid var(--ig-border);
    background: white;
}

.chat-header {
    padding: 16px;
    border-bottom: 1px solid var(--ig-border);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.chat-username {
    font-weight: 600;
    font-size: 16px;
}

.chat-list {
    overflow-y: auto;
}

.chat-item {
    display: flex;
    padding: 12px 16px;
    align-items: center;
    cursor: pointer;
    transition: background-color 0.2s;
}

.chat-item:hover {
    background-color: #FAFAFA;
}

.chat-item-avatar {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    margin-right: 12px;
}

.chat-item-content {
    flex: 1;
}

.chat-item-name {
    font-weight: 600;
    margin-bottom: 4px;
}

.chat-item-preview {
    color: var(--ig-secondary);
    font-size: 14px;
}

/* Message Area */
.message-area {
    flex: 1;
    display: flex;
    flex-direction: column;
}

.message-header {
    padding: 16px;
    border-bottom: 1px solid var(--ig-border);
    display: flex;
    align-items: center;
}

.messages {
    flex: 1;
    padding: 20px;
    overflow-y: auto;
    background: white;
}

.message {
    max-width: 60%;
    margin-bottom: 10px;
    padding: 12px;
    border-radius: 22px;
    font-size: 14px;
}

.message.sent {
    background: #3897F0;
    color: white;
    margin-left: auto;
    border-bottom-right-radius: 4px;
}

.message.received {
    background: #EFEFEF;
    color: var(--ig-text);
    border-bottom-left-radius: 4px;
}

.message-input {
    padding: 20px;
    border-top: 1px solid var(--ig-border);
    display: flex;
    align-items: center;
    gap: 12px;
}

.message-input input {
    flex: 1;
    border: 1px solid var(--ig-border);
    border-radius: 22px;
    padding: 12px 16px;
    font-size: 14px;
}

.message-input button {
    color: #3897F0;
    font-weight: 600;
    background: none;
    border: none;
    cursor: pointer;
}

/* AI Assistant Badge */
.ai-badge {
    background: var(--ig-gradient);
    color: white;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 500;
}
"@

# Add HTML structure
$instagramHTML = @"
<div class="vortex-app">
    <!-- Stories Bar -->
    <div class="stories-bar">
        <!-- Featured Artists -->
    </div>

    <!-- Main Feed / Chat Interface -->
    <div class="main-container">
        <div class="chat-container">
            <!-- Chat Sidebar -->
            <div class="chat-sidebar">
                <!-- Chat List -->
            </div>

            <!-- Message Area -->
            <div class="message-area">
                <!-- Messages -->
            </div>
        </div>
    </div>
</div>
"@

# Create animation effects
$instagramAnimationsCSS = @"
/* Instagram-style Animations */
@keyframes likeAnimation {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.2); }
}

@keyframes storyGradient {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.like-button.active i {
    animation: likeAnimation 0.45s ease-in-out;
    color: #ED4956;
}

.story-avatar::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    border-radius: 50%;
    background: var(--story-gradient);
    animation: storyGradient 8s linear infinite;
    z-index: -1;
}
"@

# Create files
New-Item -ItemType Directory -Path "demo/css" -Force | Out-Null
Add-Content -Path "demo/css/instagram-style.css" -Value $instagramStyleCSS
Add-Content -Path "demo/css/animations.css" -Value $instagramAnimationsCSS
Add-Content -Path "demo/index.html" -Value $instagramHTML

Write-Host "Instagram-inspired design implemented!" -ForegroundColor Green
Write-Host "`nFeatures added:" -ForegroundColor Yellow
Write-Host "1. Stories bar for featured artists" -ForegroundColor Cyan
Write-Host "2. Clean, modern chat interface" -ForegroundColor Cyan
Write-Host "3. Instagram-style animations" -ForegroundColor Cyan
Write-Host "4. AI assistant integration" -ForegroundColor Cyan

# Offer to open demo
$response = Read-Host "`nWould you like to view the updated design? (y/n)"
if ($response -eq "y") {
    Start-Process "demo/index.html"
} 