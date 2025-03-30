/**
 * VORTEX AI Marketplace - Language Administration
 * Manages language settings and translations for the marketplace
 * Integrated with VORTEX AI Agents for intelligent translation assistance and learning
 */

class VortexLanguageAdmin {
    constructor() {
        this.languages = [];
        this.currentLanguage = null;
        this.translationStrings = {};
        this.unsavedChanges = false;
        this.aiAgent = null;
        this.aiSuggestionCache = {};
        this.learningEnabled = true;
        this.pendingAiSuggestions = 0;
        this.translationMemory = {};
        this.learningQueue = [];
        this.aiFeatureHints = {
            shown: {},
            dismissed: []
        };
        this.init();
    }

    init() {
        this.loadLanguages()
            .then(() => {
                this.bindEvents();
                this.setupConfirmOnLeave();
                this.connectToAIAgentSystem();
                this.initializeTranslationMemory();
            })
            .catch(error => {
                console.error('Failed to initialize language admin:', error);
                this.showNotification('Initialization failed. Please refresh the page.', 'error');
            });
    }

    /**
     * Establishes connection to the AI Agent System for intelligent translation assistance
     * This allows real-time translation suggestions and learning from user edits
     */
    connectToAIAgentSystem() {
        try {
            // Subscribe to AI translation agent
            if (typeof VortexAIBridge !== 'undefined') {
                this.aiAgent = VortexAIBridge.connectAgent('translation-assistant', {
                    onSuggestion: this.handleAISuggestion.bind(this),
                    onLearningUpdate: this.handleAILearningUpdate.bind(this),
                    onError: this.handleAIError.bind(this)
                });
                
                console.log('Successfully connected to VORTEX AI Translation Agent');
                
                // Enable continuous learning from translation patterns
                if (this.learningEnabled) {
                    this.startContinuousLearning();
                }
            } else {
                console.warn('VortexAIBridge not found. AI translation features will be unavailable.');
            }
        } catch (error) {
            console.error('Error connecting to AI Translation Agent:', error);
            this.showNotification('Could not connect to AI Translation system. Some features will be limited.', 'warning');
        }
    }
    
    /**
     * Initializes the AI translation memory from previous translations
     */
    async initializeTranslationMemory() {
        if (!this.aiAgent) return;
        
        try {
            const response = await fetch('/wp-json/vortex/v1/ai/translation-memory');
            if (!response.ok) throw new Error('Failed to load translation memory');
            
            this.translationMemory = await response.json();
            console.log('Translation memory initialized with', Object.keys(this.translationMemory).length, 'entries');
            
            // Inform the AI system about the loaded memory
            this.aiAgent.send({
                action: 'initialize_memory',
                data: this.translationMemory
            });
        } catch (error) {
            console.warn('Failed to initialize translation memory:', error);
        }
    }
    
    /**
     * Starts continuous learning from translation patterns
     */
    startContinuousLearning() {
        // Setup event tracking for learning from user edits
        document.addEventListener('input', this.captureTranslationLearning.bind(this));
        
        // Periodically sync learning data with the AI system
        this.learningInterval = setInterval(() => {
            this.syncAILearningData();
        }, 300000); // Every 5 minutes
        
        console.log('Continuous translation learning activated');
    }
    
    /**
     * Captures user translation input for AI learning
     */
    captureTranslationLearning(event) {
        if (!this.aiAgent || !this.learningEnabled) return;
        
        const target = event.target;
        if (target.tagName !== 'TEXTAREA' || !target.parentElement.classList.contains('vortex-translation-input')) {
            return;
        }
        
        // Get the original string
        const row = target.closest('tr');
        if (!row || !row.dataset.stringId) return;
        
        const stringId = row.dataset.stringId;
        const originalString = this.translationStrings[stringId]?.original;
        const translation = target.value;
        
        if (!originalString || !translation || translation.trim() === '') return;
        
        // Add to learning queue if it's a substantial change
        if (translation.length > 2) {
            this.queueTranslationLearning(originalString, translation, this.currentLanguage);
        }
    }
    
    /**
     * Queues a translation pair for AI learning
     */
    queueTranslationLearning(original, translation, language) {
        if (!this.aiAgent) return;
        
        // Add to local learning queue
        if (!this.learningQueue) {
            this.learningQueue = [];
        }
        
        // Check if we already have this in the queue
        const exists = this.learningQueue.some(item => 
            item.original === original && 
            item.language === language
        );
        
        if (!exists) {
            this.learningQueue.push({
                original,
                translation,
                language,
                timestamp: Date.now()
            });
            
            // If queue gets too large, sync immediately
            if (this.learningQueue.length >= 20) {
                this.syncAILearningData();
            }
        }
    }
    
    /**
     * Syncs collected translation learning data with the AI system
     */
    async syncAILearningData() {
        if (!this.aiAgent || !this.learningQueue || this.learningQueue.length === 0) return;
        
        try {
            const learningData = [...this.learningQueue];
            this.learningQueue = [];
            
            const response = await fetch('/wp-json/vortex/v1/ai/learn-translations', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': vortexSettings?.nonce || '',
                    'X-VORTEX-AI-Request': 'true'
                },
                body: JSON.stringify({
                    translations: learningData
                })
            });
            
            if (!response.ok) throw new Error('Failed to sync learning data');
            
            const result = await response.json();
            console.log(`AI system processed ${result.processed} translation patterns`);
            
            // Update local translation memory with new patterns
            if (result.updatedMemory) {
                Object.assign(this.translationMemory, result.updatedMemory);
            }
        } catch (error) {
            console.warn('Error syncing AI learning data:', error);
            // Return items to the queue for next attempt
            if (this.learningQueue) {
                this.learningQueue = [...this.learningQueue, ...learningData];
            }
        }
    }
    
    /**
     * Handles translation suggestions from the AI agent
     */
    handleAISuggestion(suggestion) {
        if (!suggestion || !suggestion.stringId || !suggestion.translation) return;
        
        // Store suggestion in cache
        this.aiSuggestionCache[suggestion.stringId] = suggestion;
        
        // Find the translation field and add the suggestion
        const row = document.querySelector(`tr[data-string-id="${suggestion.stringId}"]`);
        if (!row) return;
        
        const textarea = row.querySelector('textarea');
        if (!textarea) return;
        
        // Only show suggestion if the field is empty
        if (textarea.value.trim() === '') {
            const suggestionDiv = row.querySelector('.vortex-ai-suggestion') || document.createElement('div');
            suggestionDiv.className = 'vortex-ai-suggestion';
            suggestionDiv.innerHTML = `
                <div class="vortex-suggestion-header">
                    <i class="dashicons dashicons-translation"></i>
                    <span>AI Suggestion</span>
                    <span class="vortex-confidence">${Math.round(suggestion.confidence * 100)}%</span>
                </div>
                <div class="vortex-suggestion-text">${suggestion.translation}</div>
                <div class="vortex-suggestion-actions">
                    <button type="button" class="vortex-use-suggestion">Use</button>
                    <button type="button" class="vortex-dismiss-suggestion">Dismiss</button>
                </div>
            `;
            
            // Decrement pending counter when suggestion is received
            this.pendingAiSuggestions = Math.max(0, this.pendingAiSuggestions - 1);
            this.updateAIStatus();
            
            const inputCell = row.querySelector('.vortex-translation-input');
            if (inputCell && !row.querySelector('.vortex-ai-suggestion')) {
                // Insert before the textarea
                inputCell.insertBefore(suggestionDiv, textarea);
                
                // Bind events
                suggestionDiv.querySelector('.vortex-use-suggestion').addEventListener('click', () => {
                    textarea.value = suggestion.translation;
                    textarea.dispatchEvent(new Event('input', { bubbles: true }));
                    suggestionDiv.remove();
                    this.handleTranslationChange(suggestion.stringId, suggestion.translation);
                    
                    // Record positive feedback for AI learning
                    this.recordAIFeedback(suggestion.stringId, 'accepted');
                });
                
                suggestionDiv.querySelector('.vortex-dismiss-suggestion').addEventListener('click', () => {
                    suggestionDiv.remove();
                    
                    // Record negative feedback for AI learning
                    this.recordAIFeedback(suggestion.stringId, 'rejected');
                });
            }
        }
    }
    
    /**
     * Records user feedback on AI suggestions for model improvement
     */
    async recordAIFeedback(stringId, feedback) {
        if (!this.aiAgent) return;
        
        try {
            const suggestion = this.aiSuggestionCache[stringId];
            if (!suggestion) return;
            
            await fetch('/wp-json/vortex/v1/ai/translation-feedback', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': vortexSettings?.nonce || '',
                    'X-VORTEX-AI-Request': 'true'
                },
                body: JSON.stringify({
                    stringId,
                    feedback,
                    suggestion,
                    language: this.currentLanguage
                })
            });
        } catch (error) {
            console.warn('Error recording AI feedback:', error);
        }
    }
    
    /**
     * Handles learning updates from the AI system
     */
    handleAILearningUpdate(update) {
        console.log('AI Translation Learning Update:', update);
        
        // Show learning progress notification for substantial updates
        if (update.newPatterns > 10) {
            this.showNotification(
                `AI Translation system learned ${update.newPatterns} new translation patterns`,
                'ai-update'
            );
        }
    }
    
    /**
     * Handles errors from the AI agent
     */
    handleAIError(error) {
        console.error('AI Translation Agent Error:', error);
        
        if (error.critical) {
            this.showNotification(
                `AI Translation Error: ${error.message || 'Unknown error occurred'}`,
                'error'
            );
        }
    }
    
    /**
     * Updates the AI status display
     */
    updateAIStatus() {
        const statusElement = document.getElementById('vortex-ai-translation-status');
        if (!statusElement) return;
        
        if (!this.aiAgent) {
            statusElement.innerHTML = '<span class="vortex-ai-status-offline">AI Offline</span>';
            return;
        }
        
        if (this.pendingAiSuggestions > 0) {
            statusElement.innerHTML = `
                <span class="vortex-ai-status-working">
                    <i class="dashicons dashicons-update vortex-spin"></i>
                    AI Working (${this.pendingAiSuggestions} pending)
                </span>
            `;
        } else {
            statusElement.innerHTML = '<span class="vortex-ai-status-ready">AI Ready</span>';
        }
    }

    bindEvents() {
        document.getElementById('vortex-add-language')?.addEventListener('click', () => {
            this.showAddLanguageModal();
        });

        document.getElementById('vortex-language-selector')?.addEventListener('change', (e) => {
            this.loadTranslationsForLanguage(e.target.value);
        });

        document.getElementById('vortex-save-translations')?.addEventListener('click', () => {
            this.saveTranslations();
        });

        document.getElementById('vortex-import-translations')?.addEventListener('click', () => {
            this.showImportModal();
        });

        document.getElementById('vortex-export-translations')?.addEventListener('click', () => {
            this.exportTranslations();
        });

        document.getElementById('vortex-auto-translate')?.addEventListener('click', () => {
            this.showAutoTranslateModal();
        });

        // Handle filter change events
        document.getElementById('vortex-filter-strings')?.addEventListener('input', (e) => {
            this.filterTranslationStrings(e.target.value);
        });

        document.getElementById('vortex-filter-untranslated')?.addEventListener('change', (e) => {
            this.toggleUntranslatedFilter(e.target.checked);
        });
        
        // Add new AI-specific events
        document.getElementById('vortex-toggle-ai')?.addEventListener('change', (e) => {
            this.toggleAITranslationAssistant(e.target.checked);
        });
        
        document.getElementById('vortex-request-ai-review')?.addEventListener('click', () => {
            this.requestAITranslationReview();
        });
        
        document.getElementById('vortex-generate-missing')?.addEventListener('click', () => {
            this.generateMissingTranslations();
        });
    }
    
    /**
     * Toggles the AI translation assistant on/off
     */
    toggleAITranslationAssistant(enabled) {
        this.learningEnabled = enabled;
        
        if (enabled && !this.aiAgent) {
            // Try to reconnect
            this.connectToAIAgentSystem();
        }
        
        if (enabled && this.aiAgent) {
            this.startContinuousLearning();
            this.showNotification('AI Translation Assistant activated', 'success');
            
            // Request suggestions for current untranslated strings
            this.requestSuggestionsForUntranslated();
        } else {
            // Clear any existing intervals
            if (this.learningInterval) {
                clearInterval(this.learningInterval);
            }
            
            document.removeEventListener('input', this.captureTranslationLearning);
            this.showNotification('AI Translation Assistant deactivated', 'info');
        }
    }
    
    /**
     * Requests suggestions for currently untranslated strings
     */
    requestSuggestionsForUntranslated() {
        if (!this.aiAgent || !this.currentLanguage) return;
        
        const untranslated = Object.values(this.translationStrings).filter(
            string => !string.translation || string.translation.trim() === ''
        );
        
        if (untranslated.length === 0) return;
        
        // Set pending suggestions count for status display
        this.pendingAiSuggestions = untranslated.length;
        this.updateAIStatus();
        
        // Request suggestions from AI system
        fetch('/wp-json/vortex/v1/ai/suggest-translations', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': vortexSettings?.nonce || '',
                'X-VORTEX-AI-Request': 'true'
            },
            body: JSON.stringify({
                strings: untranslated.map(s => ({ 
                    id: s.id, 
                    original: s.original,
                    context: s.context 
                })),
                language: this.currentLanguage
            })
        })
        .then(response => {
            if (!response.ok) throw new Error('Failed to request suggestions');
            return response.json();
        })
        .then(result => {
            console.log(`Requested ${result.requested} AI translation suggestions`);
        })
        .catch(error => {
            console.error('Error requesting AI suggestions:', error);
            this.pendingAiSuggestions = 0;
            this.updateAIStatus();
        });
    }
    
    /**
     * Requests AI review of current translations for quality and consistency
     */
    async requestAITranslationReview() {
        if (!this.aiAgent || !this.currentLanguage) {
            this.showNotification('AI Translation system not available', 'error');
            return;
        }
        
        this.showLoadingIndicator(true);
        
        try {
            const response = await fetch('/wp-json/vortex/v1/ai/review-translations', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': vortexSettings?.nonce || '',
                    'X-VORTEX-AI-Request': 'true'
                },
                body: JSON.stringify({
                    language: this.currentLanguage,
                    strings: this.translationStrings
                })
            });
            
            if (!response.ok) throw new Error('Translation review failed');
            
            const result = await response.json();
            
            if (result.issues && result.issues.length > 0) {
                this.displayAIReviewResults(result.issues);
                this.showNotification(`AI Review completed: Found ${result.issues.length} potential issues`, 'info');
            } else {
                this.showNotification('AI Review completed: No issues found', 'success');
            }
        } catch (error) {
            console.error('Error during AI translation review:', error);
            this.showNotification('AI translation review failed', 'error');
        } finally {
            this.showLoadingIndicator(false);
        }
    }
    
    /**
     * Displays AI review results in the UI
     */
    displayAIReviewResults(issues) {
        const modal = document.getElementById('vortex-ai-review-modal') || this.createAIReviewModal();
        const container = modal.querySelector('.vortex-review-issues');
        
        container.innerHTML = '';
        
        issues.forEach(issue => {
            const issueElement = document.createElement('div');
            issueElement.className = `vortex-review-issue vortex-issue-${issue.severity}`;
            
            let actionHTML = '';
            if (issue.suggestion) {
                actionHTML = `
                    <div class="vortex-issue-action">
                        <button type="button" class="vortex-apply-suggestion" data-id="${issue.stringId}" data-suggestion="${this.escapeHtml(issue.suggestion)}">
                            Apply Suggestion
                        </button>
                    </div>
                `;
            }
            
            issueElement.innerHTML = `
                <div class="vortex-issue-header">
                    <span class="vortex-issue-severity">${issue.severity}</span>
                    <span class="vortex-issue-type">${issue.type}</span>
                </div>
                <div class="vortex-issue-content">
                    <p>${issue.description}</p>
                    <div class="vortex-issue-strings">
                        <div class="vortex-issue-original">
                            <strong>Original:</strong> ${issue.original}
                        </div>
                        <div class="vortex-issue-translation">
                            <strong>Current Translation:</strong> ${issue.translation || '<em>empty</em>'}
                        </div>
                        ${issue.suggestion ? `<div class="vortex-issue-suggestion">
                            <strong>Suggested Translation:</strong> ${issue.suggestion}
                        </div>` : ''}
                    </div>
                    ${actionHTML}
                </div>
            `;
            
            container.appendChild(issueElement);
        });
        
        // Bind events to action buttons
        container.querySelectorAll('.vortex-apply-suggestion').forEach(button => {
            button.addEventListener('click', () => {
                const stringId = button.dataset.id;
                const suggestion = button.dataset.suggestion;
                
                // Find and update the translation
                const row = document.querySelector(`tr[data-string-id="${stringId}"]`);
                if (row) {
                    const textarea = row.querySelector('textarea');
                    if (textarea) {
                        textarea.value = suggestion;
                        textarea.dispatchEvent(new Event('input', { bubbles: true }));
                        this.handleTranslationChange(stringId, suggestion);
                        
                        // Mark as addressed
                        button.textContent = 'Applied';
                        button.disabled = true;
                        button.closest('.vortex-review-issue').classList.add('vortex-issue-addressed');
                    }
                }
            });
        });
        
        modal.style.display = 'block';
    }
    
    /**
     * Creates the AI review modal if it doesn't exist
     */
    createAIReviewModal() {
        const modal = document.createElement('div');
        modal.id = 'vortex-ai-review-modal';
        modal.className = 'vortex-modal';
        
        modal.innerHTML = `
            <div class="vortex-modal-content vortex-review-modal-content">
                <span class="vortex-modal-close">&times;</span>
                <h2 class="vortex-modal-title">
                    <i class="dashicons dashicons-admin-customizer"></i>
                    AI Translation Review Results
                </h2>
                <div class="vortex-review-issues"></div>
                <div class="vortex-modal-footer">
                    <button type="button" class="vortex-button vortex-button-primary vortex-close-review">Close</button>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        // Bind close events
        const closeBtn = modal.querySelector('.vortex-modal-close');
        const closeButton = modal.querySelector('.vortex-close-review');
        
        [closeBtn, closeButton].forEach(el => {
            el?.addEventListener('click', () => {
                modal.style.display = 'none';
            });
        });
        
        return modal;
    }
    
    /**
     * Escapes HTML entities for safe DOM insertion
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    /**
     * Generates translations for all missing strings using AI deep learning
     */
    async generateMissingTranslations() {
        if (!this.aiAgent || !this.currentLanguage) {
            this.showNotification('AI Translation system not available', 'error');
            return;
        }
        
        const untranslated = Object.values(this.translationStrings).filter(
            string => !string.translation || string.translation.trim() === ''
        );
        
        if (untranslated.length === 0) {
            this.showNotification('No missing translations to generate', 'info');
            return;
        }
        
        if (!confirm(`Generate translations for ${untranslated.length} strings using AI? This may take a while.`)) {
            return;
        }
        
        this.showLoadingIndicator(true);
        
        try {
            const progressModal = this.showProgressModal(untranslated.length);
            let processed = 0;
            
            // Process in batches of 20 for better UX
            const batchSize = 20;
            for (let i = 0; i < untranslated.length; i += batchSize) {
                const batch = untranslated.slice(i, i + batchSize);
                
                const response = await fetch('/wp-json/vortex/v1/ai/batch-translate', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': vortexSettings?.nonce || '',
                        'X-VORTEX-AI-Request': 'true'
                    },
                    body: JSON.stringify({
                        strings: batch.map(s => ({ 
                            id: s.id, 
                            original: s.original,
                            context: s.context 
                        })),
                        language: this.currentLanguage
                    })
                });
                
                if (!response.ok) throw new Error('Batch translation failed');
                
                const result = await response.json();
                
                // Update the translation strings
                result.translations.forEach(item => {
                    if (item.id && item.translation) {
                        this.translationStrings[item.id].translation = item.translation;
                        this.translationStrings[item.id].aiGenerated = true;
                        this.translationStrings[item.id].aiConfidence = item.confidence || 0.7;
                        
                        // Update the UI
                        const row = document.querySelector(`tr[data-string-id="${item.id}"]`);
                        if (row) {
                            const textarea = row.querySelector('textarea');
                            if (textarea) {
                                textarea.value = item.translation;
                                row.classList.remove('vortex-untranslated');
                                row.classList.add('vortex-ai-generated');
                            }
                        }
                    }
                });
                
                // Update progress
                processed += batch.length;
                this.updateProgressModal(progressModal, processed, untranslated.length);
            }
            
            // Close progress modal
            progressModal.style.display = 'none';
            
            this.unsavedChanges = true;
            this.updateTranslationStats();
            
            this.showNotification(`AI generated ${processed} translations successfully`, 'success');
        } catch (error) {
            console.error('Error generating translations:', error);
            this.showNotification('Failed to generate translations', 'error');
        } finally {
            this.showLoadingIndicator(false);
        }
    }
    
    /**
     * Shows a progress modal for batch operations
     */
    showProgressModal(total) {
        const modal = document.createElement('div');
        modal.className = 'vortex-modal vortex-progress-modal';
        
        modal.innerHTML = `
            <div class="vortex-modal-content vortex-progress-modal-content">
                <h3>Generating Translations</h3>
                <div class="vortex-progress-info">
                    <span class="vortex-progress-status">Processing 0/${total} translations...</span>
                </div>
                <div class="vortex-progress-bar-container">
                    <div class="vortex-progress-bar">
                        <div class="vortex-progress" style="width: 0%"></div>
                    </div>
                </div>
                <p class="vortex-progress-note">This may take a while. Please don't close this window.</p>
            </div>
        `;
        
        document.body.appendChild(modal);
        modal.style.display = 'block';
        
        return modal;
    }
    
    /**
     * Updates the progress modal with current progress
     */
    updateProgressModal(modal, current, total) {
        const percentage = Math.round((current / total) * 100);
        
        const statusElement = modal.querySelector('.vortex-progress-status');
        if (statusElement) {
            statusElement.textContent = `Processing ${current}/${total} translations...`;
        }
        
        const progressBar = modal.querySelector('.vortex-progress');
        if (progressBar) {
            progressBar.style.width = `${percentage}%`;
        }
    }

    async loadLanguages() {
        try {
            const response = await fetch('/wp-json/vortex/v1/languages');
            if (!response.ok) throw new Error('Failed to load languages');
            
            this.languages = await response.json();
            this.renderLanguageSelector();
            
            // Load default language translations
            if (this.languages.length > 0) {
                const defaultLang = this.languages.find(lang => lang.isDefault) || this.languages[0];
                this.loadTranslationsForLanguage(defaultLang.code);
            }
        } catch (error) {
            console.error('Error loading languages:', error);
            this.showNotification('Failed to load languages', 'error');
            throw error;
        }
    }

    renderLanguageSelector() {
        const selector = document.getElementById('vortex-language-selector');
        if (!selector) return;
        
        selector.innerHTML = '';
        
        this.languages.forEach(language => {
            const option = document.createElement('option');
            option.value = language.code;
            option.textContent = `${language.name} (${language.code})${language.isDefault ? ' - Default' : ''}`;
            selector.appendChild(option);
        });
    }

    async loadTranslationsForLanguage(languageCode) {
        if (this.unsavedChanges) {
            const confirm = window.confirm('You have unsaved changes. Do you want to discard them?');
            if (!confirm) {
                // Revert the selector to previous value
                const selector = document.getElementById('vortex-language-selector');
                if (selector) selector.value = this.currentLanguage;
                return;
            }
        }

        this.showLoadingIndicator(true);
        
        try {
            const response = await fetch(`/wp-json/vortex/v1/translations/${languageCode}`);
            if (!response.ok) throw new Error('Failed to load translations');
            
            this.currentLanguage = languageCode;
            this.translationStrings = await response.json();
            this.renderTranslationStrings();
            this.unsavedChanges = false;
            
            // Update UI elements
            document.getElementById('vortex-language-info').textContent = 
                `Editing translations for ${this.getLanguageName(languageCode)}`;
            
            const isDefault = this.languages.find(lang => lang.code === languageCode)?.isDefault;
            document.getElementById('vortex-set-default').style.display = isDefault ? 'none' : 'inline-block';
            
            // Clear AI suggestions cache when changing language
            this.aiSuggestionCache = {};
            
            // Update AI status display
            this.updateAIStatus();
            
            // Request AI suggestions for untranslated strings if AI is enabled
            if (this.aiAgent && this.learningEnabled) {
                this.requestSuggestionsForUntranslated();
            }
        } catch (error) {
            console.error('Error loading translations:', error);
            this.showNotification('Failed to load translations', 'error');
        } finally {
            this.showLoadingIndicator(false);
        }
    }

    getLanguageName(code) {
        const language = this.languages.find(lang => lang.code === code);
        return language ? language.name : code;
    }

    renderTranslationStrings() {
        const container = document.getElementById('vortex-translation-strings');
        if (!container) return;
        
        container.innerHTML = '';
        
        // Group translations by context
        const groupedStrings = this.groupTranslationsByContext();
        
        Object.entries(groupedStrings).forEach(([context, strings]) => {
            const contextHeading = document.createElement('h3');
            contextHeading.className = 'vortex-translation-context';
            contextHeading.textContent = context || 'General';
            container.appendChild(contextHeading);
            
            const table = document.createElement('table');
            table.className = 'vortex-translation-table';
            
            const thead = document.createElement('thead');
            thead.innerHTML = `
                <tr>
                    <th>Original</th>
                    <th>Translation</th>
                </tr>
            `;
            table.appendChild(thead);
            
            const tbody = document.createElement('tbody');
            
            strings.forEach(string => {
                const tr = document.createElement('tr');
                tr.dataset.stringId = string.id;
                if (!string.translation) tr.classList.add('vortex-untranslated');
                
                // Add AI-optimized flag if string was created by AI
                if (string.aiGenerated) {
                    tr.classList.add('vortex-ai-generated');
                }
                
                const tdOriginal = document.createElement('td');
                tdOriginal.className = 'vortex-original-string';
                tdOriginal.textContent = string.original;
                
                // Add information icons for AI context
                if (string.aiContext) {
                    const aiContextIcon = document.createElement('div');
                    aiContextIcon.className = 'vortex-ai-context-icon';
                    aiContextIcon.innerHTML = '<i class="dashicons dashicons-info-outline"></i>';
                    aiContextIcon.title = 'AI Context: ' + string.aiContext;
                    tdOriginal.appendChild(aiContextIcon);
                }
                
                if (string.description) {
                    const description = document.createElement('div');
                    description.className = 'vortex-string-description';
                    description.textContent = string.description;
                    tdOriginal.appendChild(description);
                }
                
                const tdTranslation = document.createElement('td');
                tdTranslation.className = 'vortex-translation-input';
                
                const textarea = document.createElement('textarea');
                textarea.value = string.translation || '';
                textarea.rows = Math.min(5, (string.original.match(/\n/g) || []).length + 1);
                textarea.placeholder = 'Enter translation here';
                
                // Add AI-powered placeholder if available
                if (!string.translation && this.aiAgent && this.learningEnabled) {
                    textarea.placeholder = 'Enter translation (or wait for AI suggestion)';
                }
                
                textarea.addEventListener('input', () => {
                    this.handleTranslationChange(string.id, textarea.value);
                });
                
                tdTranslation.appendChild(textarea);
                
                // Add AI confidence indicator if available
                if (string.aiConfidence !== undefined) {
                    const confidenceIndicator = document.createElement('div');
                    confidenceIndicator.className = 'vortex-ai-confidence';
                    
                    let confidenceLevel = 'low';
                    if (string.aiConfidence >= 0.7) confidenceLevel = 'high';
                    else if (string.aiConfidence >= 0.4) confidenceLevel = 'medium';
                    
                    confidenceIndicator.innerHTML = `
                        <span class="vortex-confidence-indicator vortex-confidence-${confidenceLevel}">
                            AI Confidence: ${Math.round(string.aiConfidence * 100)}%
                        </span>
                    `;
                    
                    tdTranslation.appendChild(confidenceIndicator);
                }
                
                tr.appendChild(tdOriginal);
                tr.appendChild(tdTranslation);
                tbody.appendChild(tr);
            });
            
            table.appendChild(tbody);
            container.appendChild(table);
        });
        
        // Update translation stats
        this.updateTranslationStats();
    }

    groupTranslationsByContext() {
        const grouped = {};
        
        Object.values(this.translationStrings).forEach(string => {
            const context = string.context || 'General';
            if (!grouped[context]) {
                grouped[context] = [];
            }
            grouped[context].push(string);
        });
        
        return grouped;
    }

    handleTranslationChange(stringId, newTranslation) {
        this.translationStrings[stringId].translation = newTranslation;
        this.unsavedChanges = true;
        
        const row = document.querySelector(`tr[data-string-id="${stringId}"]`);
        if (row) {
            if (newTranslation) {
                row.classList.remove('vortex-untranslated');
            } else {
                row.classList.add('vortex-untranslated');
            }
        }
        
        // Submit the translation for AI learning if it's substantial
        if (this.aiAgent && this.learningEnabled && newTranslation && newTranslation.length > 2) {
            const original = this.translationStrings[stringId].original;
            this.queueTranslationLearning(original, newTranslation, this.currentLanguage);
        }
        
        this.updateTranslationStats();
    }

    updateTranslationStats() {
        const stats = document.getElementById('vortex-translation-stats');
        if (!stats) return;
        
        const total = Object.keys(this.translationStrings).length;
        const translated = Object.values(this.translationStrings)
            .filter(string => string.translation && string.translation.trim() !== '').length;
        
        const percentage = total > 0 ? Math.round((translated / total) * 100) : 0;
        
        // Count AI-assisted translations if available
        const aiGenerated = Object.values(this.translationStrings)
            .filter(string => string.aiGenerated).length;
        
        const aiGeneratedText = aiGenerated > 0 ? 
            `<span class="vortex-ai-stats">AI-assisted: ${aiGenerated}</span>` : '';
        
        stats.innerHTML = `
            <div class="vortex-stat-item">
                <span class="vortex-stat-label">Translated:</span>
                <span class="vortex-stat-value">${translated}/${total} (${percentage}%)</span>
                ${aiGeneratedText}
            </div>
            <div class="vortex-progress-bar">
                <div class="vortex-progress" style="width: ${percentage}%"></div>
            </div>
        `;
        
        // Update AI system status display
        this.updateAIStatus();
    }

    showLoadingIndicator(show) {
        const loader = document.getElementById('vortex-loader');
        if (loader) {
            loader.style.display = show ? 'flex' : 'none';
        }
    }

    showNotification(message, type = 'info') {
        const notification = document.getElementById('vortex-notification');
        if (!notification) return;
        
        notification.textContent = message;
        notification.className = `vortex-notification vortex-${type}`;
        notification.style.display = 'block';
        
        setTimeout(() => {
            notification.style.display = 'none';
        }, 5000);
    }

    showAddLanguageModal() {
        // Implementation of showAddLanguageModal method
    }

    showImportModal() {
        // Implementation of showImportModal method
    }

    showAutoTranslateModal() {
        // Implementation of showAutoTranslateModal method
    }

    saveTranslations() {
        // Implementation of saveTranslations method
    }

    exportTranslations() {
        // Implementation of exportTranslations method
    }

    filterTranslationStrings(value) {
        const container = document.getElementById('vortex-translation-strings');
        if (!container) return;
        
        const rows = container.querySelectorAll('table tbody tr');
        rows.forEach(row => {
            const originalCell = row.querySelector('td:nth-child(1)');
            const translationCell = row.querySelector('td:nth-child(2)');
            
            if (originalCell.textContent.includes(value) || translationCell.textContent.includes(value)) {
                row.style.display = 'table-row';
            } else {
                row.style.display = 'none';
            }
        });
    }

    toggleUntranslatedFilter(checked) {
        const container = document.getElementById('vortex-translation-strings');
        if (!container) return;
        
        const rows = container.querySelectorAll('table tbody tr');
        rows.forEach(row => {
            const originalCell = row.querySelector('td:nth-child(1)');
            const translationCell = row.querySelector('td:nth-child(2)');
            
            if (checked) {
                if (originalCell.textContent.trim() === '' && translationCell.textContent.trim() === '') {
                    row.style.display = 'table-row';
                } else {
                    row.style.display = 'none';
                }
            } else {
                row.style.display = 'table-row';
            }
        });
    }

    /**
     * Shows contextual AI hints based on user activity
     */
    showAIContextualHint(hintId, message) {
        // Don't show dismissed hints or recently shown ones
        if (this.aiFeatureHints.dismissed.includes(hintId) || this.aiFeatureHints.shown[hintId]) {
            return;
        }
        
        const hintContainer = document.createElement('div');
        hintContainer.className = 'vortex-ai-hint';
        hintContainer.dataset.hintId = hintId;
        
        hintContainer.innerHTML = `
            <div class="vortex-hint-icon">
                <i class="dashicons dashicons-lightbulb"></i>
            </div>
            <div class="vortex-hint-content">
                <p>${message}</p>
            </div>
            <button type="button" class="vortex-hint-dismiss">
                <i class="dashicons dashicons-no-alt"></i>
            </button>
        `;
        
        document.body.appendChild(hintContainer);
        
        // Mark as shown and set timeout to auto-dismiss
        this.aiFeatureHints.shown[hintId] = true;
        setTimeout(() => {
            hintContainer.classList.add('vortex-hint-hiding');
            setTimeout(() => {
                hintContainer.remove();
                delete this.aiFeatureHints.shown[hintId];
            }, 500);
        }, 10000);
        
        // Bind dismiss button
        hintContainer.querySelector('.vortex-hint-dismiss').addEventListener('click', () => {
            this.aiFeatureHints.dismissed.push(hintId);
            hintContainer.remove();
            delete this.aiFeatureHints.shown[hintId];
        });
    }
    
    /**
     * Adds AI translation quality indicators to translated strings
     */
    renderQualityIndicators() {
        if (!this.aiAgent) return;
        
        // Request quality assessment for all translations
        const strings = Object.values(this.translationStrings)
            .filter(string => string.translation && string.translation.trim() !== '');
        
        if (strings.length === 0) return;
        
        fetch('/wp-json/vortex/v1/ai/quality-assessment', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': vortexSettings?.nonce || '',
                'X-VORTEX-AI-Request': 'true'
            },
            body: JSON.stringify({
                strings: strings.map(s => ({
                    id: s.id,
                    original: s.original,
                    translation: s.translation,
                    context: s.context
                })),
                language: this.currentLanguage
            })
        })
        .then(response => {
            if (!response.ok) throw new Error('Quality assessment failed');
            return response.json();
        })
        .then(results => {
            results.assessments.forEach(assessment => {
                const row = document.querySelector(`tr[data-string-id="${assessment.id}"]`);
                if (!row) return;
                
                const qualityIndicator = document.createElement('div');
                qualityIndicator.className = `vortex-quality-indicator vortex-quality-${assessment.quality}`;
                
                let qualityLabel = 'Unknown';
                switch (assessment.quality) {
                    case 'excellent': qualityLabel = 'Excellent'; break;
                    case 'good': qualityLabel = 'Good'; break;
                    case 'needs-review': qualityLabel = 'Needs Review'; break;
                    case 'poor': qualityLabel = 'Poor'; break;
                }
                
                qualityIndicator.innerHTML = `
                    <span class="vortex-quality-dot"></span>
                    <span class="vortex-quality-label">${qualityLabel}</span>
                `;
                
                if (assessment.issues && assessment.issues.length > 0) {
                    const issuesList = document.createElement('div');
                    issuesList.className = 'vortex-quality-issues';
                    
                    const issuesItems = assessment.issues.map(issue => 
                        `<li>${issue}</li>`
                    ).join('');
                    
                    issuesList.innerHTML = `
                        <ul>${issuesItems}</ul>
                    `;
                    
                    qualityIndicator.appendChild(issuesList);
                    qualityIndicator.classList.add('vortex-has-issues');
                    
                    // Show issues on hover
                    qualityIndicator.addEventListener('mouseenter', () => {
                        issuesList.style.display = 'block';
                    });
                    
                    qualityIndicator.addEventListener('mouseleave', () => {
                        issuesList.style.display = 'none';
                    });
                }
                
                const inputCell = row.querySelector('.vortex-translation-input');
                if (inputCell) {
                    inputCell.appendChild(qualityIndicator);
                }
            });
        })
        .catch(error => {
            console.warn('Error during quality assessment:', error);
        });
    }
    
    /**
     * Creates AI shortcuts toolbar for common translation actions
     */
    createAIShortcutsToolbar() {
        const container = document.getElementById('vortex-ai-shortcuts');
        if (!container) return;
        
        container.innerHTML = `
            <div class="vortex-ai-toolbar">
                <div class="vortex-ai-toolbar-title">
                    <i class="dashicons dashicons-translation"></i>
                    AI Translation Tools
                </div>
                <div class="vortex-ai-toolbar-buttons">
                    <button type="button" class="vortex-ai-button" id="vortex-ai-translate-selection">
                        <i class="dashicons dashicons-editor-paste-text"></i>
                        Translate Selection
                    </button>
                    <button type="button" class="vortex-ai-button" id="vortex-ai-fix-highlighted">
                        <i class="dashicons dashicons-admin-tools"></i>
                        Fix Selected Text
                    </button>
                    <button type="button" class="vortex-ai-button" id="vortex-ai-glossary">
                        <i class="dashicons dashicons-book"></i>
                        AI Glossary
                    </button>
                    <button type="button" class="vortex-ai-button" id="vortex-ai-rewrite">
                        <i class="dashicons dashicons-controls-repeat"></i>
                        Alternative Translation
                    </button>
                </div>
            </div>
        `;
        
        // Bind events to the toolbar buttons
        document.getElementById('vortex-ai-translate-selection')?.addEventListener('click', () => {
            this.translateSelectedText();
        });
        
        document.getElementById('vortex-ai-fix-highlighted')?.addEventListener('click', () => {
            this.fixSelectedTranslation();
        });
        
        document.getElementById('vortex-ai-glossary')?.addEventListener('click', () => {
            this.showAIGlossary();
        });
        
        document.getElementById('vortex-ai-rewrite')?.addEventListener('click', () => {
            this.getAlternativeTranslation();
        });
        
        // Add keyboard shortcuts for these actions
        document.addEventListener('keydown', e => {
            // Only process if we're focused on a translation textarea
            const activeElement = document.activeElement;
            if (activeElement.tagName !== 'TEXTAREA' || !activeElement.closest('.vortex-translation-input')) {
                return;
            }
            
            // Alt+T: Translate selection
            if (e.altKey && e.key === 't') {
                e.preventDefault();
                this.translateSelectedText();
            }
            
            // Alt+F: Fix highlighted text
            if (e.altKey && e.key === 'f') {
                e.preventDefault();
                this.fixSelectedTranslation();
            }
            
            // Alt+A: Alternative translation
            if (e.altKey && e.key === 'a') {
                e.preventDefault();
                this.getAlternativeTranslation();
            }
        });
    }
    
    /**
     * Provides AI translation for selected text
     */
    translateSelectedText() {
        const activeTextarea = document.activeElement;
        if (activeTextarea.tagName !== 'TEXTAREA') {
            this.showNotification('Please select text in a translation field first', 'info');
            return;
        }
        
        const selectedText = activeTextarea.value.substring(
            activeTextarea.selectionStart, 
            activeTextarea.selectionEnd
        );
        
        if (!selectedText) {
            this.showNotification('Please select text to translate', 'info');
            return;
        }
        
        const row = activeTextarea.closest('tr');
        if (!row || !row.dataset.stringId) return;
        
        const stringId = row.dataset.stringId;
        const originalString = this.translationStrings[stringId]?.original;
        
        if (!originalString) return;
        
        this.showLoadingIndicator(true);
        
        fetch('/wp-json/vortex/v1/ai/translate-text', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': vortexSettings?.nonce || '',
                'X-VORTEX-AI-Request': 'true'
            },
            body: JSON.stringify({
                text: selectedText,
                context: originalString,
                stringId: stringId,
                language: this.currentLanguage
            })
        })
        .then(response => {
            if (!response.ok) throw new Error('Translation failed');
            return response.json();
        })
        .then(result => {
            const translatedText = result.translation;
            
            // Replace the selected text with the translation
            const currentValue = activeTextarea.value;
            activeTextarea.value = 
                currentValue.substring(0, activeTextarea.selectionStart) +
                translatedText +
                currentValue.substring(activeTextarea.selectionEnd);
            
            // Trigger change event
            activeTextarea.dispatchEvent(new Event('input', { bubbles: true }));
            
            // Set selection to the newly inserted text
            const newSelectionStart = activeTextarea.selectionStart;
            activeTextarea.setSelectionRange(
                newSelectionStart - translatedText.length,
                newSelectionStart
            );
            
            this.showNotification('Text translated successfully', 'success');
        })
        .catch(error => {
            console.error('Error translating text:', error);
            this.showNotification('Failed to translate text', 'error');
        })
        .finally(() => {
            this.showLoadingIndicator(false);
        });
    }
    
    /**
     * Shows AI-generated glossary based on current translations
     */
    showAIGlossary() {
        if (!this.aiAgent) {
            this.showNotification('AI Translation system not available', 'error');
            return;
        }
        
        this.showLoadingIndicator(true);
        
        fetch('/wp-json/vortex/v1/ai/translation-glossary', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': vortexSettings?.nonce || '',
                'X-VORTEX-AI-Request': 'true'
            },
            body: JSON.stringify({
                language: this.currentLanguage
            })
        })
        .then(response => {
            if (!response.ok) throw new Error('Failed to generate glossary');
            return response.json();
        })
        .then(result => {
            this.displayAIGlossary(result.terms);
        })
        .catch(error => {
            console.error('Error generating AI glossary:', error);
            this.showNotification('Failed to generate translation glossary', 'error');
        })
        .finally(() => {
            this.showLoadingIndicator(false);
        });
    }
    
    /**
     * Displays the AI-generated glossary in a modal
     */
    displayAIGlossary(terms) {
        if (!terms || terms.length === 0) {
            this.showNotification('No glossary terms available', 'info');
            return;
        }
        
        const modal = document.createElement('div');
        modal.className = 'vortex-modal';
        
        // Group terms by category
        const categorized = {};
        terms.forEach(term => {
            const category = term.category || 'General';
            if (!categorized[category]) {
                categorized[category] = [];
            }
            categorized[category].push(term);
        });
        
        let termsHTML = '';
        
        Object.entries(categorized).forEach(([category, categoryTerms]) => {
            termsHTML += `
                <div class="vortex-glossary-category">
                    <h3>${category}</h3>
                    <div class="vortex-glossary-terms">
                        <table>
                            <thead>
                                <tr>
                                    <th>Original</th>
                                    <th>Translation</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
            `;
            
            categoryTerms.forEach(term => {
                termsHTML += `
                    <tr>
                        <td>${term.original}</td>
                        <td>${term.translation}</td>
                        <td>
                            <button type="button" class="vortex-glossary-action" data-action="apply" data-original="${this.escapeHtml(term.original)}" data-translation="${this.escapeHtml(term.translation)}">
                                <i class="dashicons dashicons-yes"></i>
                                Apply
                            </button>
                        </td>
                    </tr>
                `;
            });
            
            termsHTML += `
                            </tbody>
                        </table>
                    </div>
                </div>
            `;
        });
        
        modal.innerHTML = `
            <div class="vortex-modal-content vortex-glossary-modal">
                <span class="vortex-modal-close">&times;</span>
                <h2 class="vortex-modal-title">
                    <i class="dashicons dashicons-book"></i>
                    AI Translation Glossary
                </h2>
                <div class="vortex-glossary-search">
                    <input type="text" placeholder="Search glossary terms..." class="vortex-glossary-search-input">
                </div>
                <div class="vortex-glossary-content">
                    ${termsHTML}
                </div>
                <div class="vortex-modal-footer">
                    <button type="button" class="vortex-button vortex-button-primary vortex-close-glossary">Close</button>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        modal.style.display = 'block';
        
        // Bind close events
        const closeElements = modal.querySelectorAll('.vortex-modal-close, .vortex-close-glossary');
        closeElements.forEach(el => {
            el.addEventListener('click', () => {
                modal.style.display = 'none';
                setTimeout(() => {
                    modal.remove();
                }, 300);
            });
        });
        
        // Bind search functionality
        const searchInput = modal.querySelector('.vortex-glossary-search-input');
        searchInput.addEventListener('input', () => {
            const searchValue = searchInput.value.toLowerCase();
            
            modal.querySelectorAll('.vortex-glossary-terms tr').forEach(row => {
                if (row.parentElement.tagName === 'THEAD') return;
                
                const original = row.cells[0].textContent.toLowerCase();
                const translation = row.cells[1].textContent.toLowerCase();
                
                if (original.includes(searchValue) || translation.includes(searchValue)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
            
            // Hide empty categories
            modal.querySelectorAll('.vortex-glossary-category').forEach(category => {
                const hasVisibleRows = Array.from(category.querySelectorAll('tbody tr'))
                    .some(row => row.style.display !== 'none');
                
                category.style.display = hasVisibleRows ? '' : 'none';
            });
        });
        
        // Bind apply term actions
        modal.querySelectorAll('.vortex-glossary-action[data-action="apply"]').forEach(button => {
            button.addEventListener('click', () => {
                const original = button.dataset.original;
                const translation = button.dataset.translation;
                
                // Find all textarea inputs
                document.querySelectorAll('.vortex-translation-input textarea').forEach(textarea => {
                    if (!textarea.value) {
                        const row = textarea.closest('tr');
                        if (!row) return;
                        
                        const originalCell = row.querySelector('.vortex-original-string');
                        if (!originalCell) return;
                        
                        const stringOriginal = originalCell.textContent;
                        
                        if (stringOriginal.includes(original)) {
                            // Apply the glossary term to matching untranslated strings
                            textarea.value = stringOriginal.replace(
                                new RegExp(this.escapeRegExp(original), 'g'), 
                                translation
                            );
                            
                            textarea.dispatchEvent(new Event('input', { bubbles: true }));
                        }
                    }
                });
                
                this.showNotification('Applied glossary term to matching strings', 'success');
            });
        });
    }
    
    /**
     * Escapes special characters for RegExp
     */
    escapeRegExp(string) {
        return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }
    
    /**
     * Adds activity insights dashboard for translation progress
     */
    renderActivityInsights() {
        const container = document.getElementById('vortex-activity-insights');
        if (!container || !this.aiAgent) return;
        
        fetch('/wp-json/vortex/v1/ai/translation-insights', {
            headers: {
                'X-WP-Nonce': vortexSettings?.nonce || '',
                'X-VORTEX-AI-Request': 'true'
            }
        })
        .then(response => {
            if (!response.ok) throw new Error('Failed to load insights');
            return response.json();
        })
        .then(insights => {
            const statisticsHtml = `
                <div class="vortex-insights-header">
                    <h3>Translation Insights</h3>
                    <div class="vortex-insights-toggle">
                        <button type="button" class="vortex-button vortex-button-small" id="vortex-refresh-insights">
                            <i class="dashicons dashicons-update"></i>
                            Refresh
                        </button>
                    </div>
                </div>
                
                <div class="vortex-insights-metrics">
                    <div class="vortex-insight-metric">
                        <div class="vortex-insight-value">${insights.totalStrings.toLocaleString()}</div>
                        <div class="vortex-insight-label">Total Strings</div>
                    </div>
                    
                    <div class="vortex-insight-metric">
                        <div class="vortex-insight-value">${insights.translatedStrings.toLocaleString()}</div>
                        <div class="vortex-insight-label">Translated</div>
                    </div>
                    
                    <div class="vortex-insight-metric">
                        <div class="vortex-insight-value">${insights.aiAssistedCount.toLocaleString()}</div>
                        <div class="vortex-insight-label">AI-Assisted</div>
                    </div>
                    
                    <div class="vortex-insight-metric">
                        <div class="vortex-insight-value">${insights.lastUpdated}</div>
                        <div class="vortex-insight-label">Last Update</div>
                    </div>
                </div>
                
                <div class="vortex-insights-chart">
                    <canvas id="vortex-translation-progress-chart"></canvas>
                </div>
                
                <div class="vortex-insights-ai-efficiency">
                    <h4>AI Learning Efficiency</h4>
                    <div class="vortex-efficiency-bar" title="${insights.aiEfficiency}% learning efficiency">
                        <div class="vortex-efficiency-progress" style="width: ${insights.aiEfficiency}%"></div>
                    </div>
                    <div class="vortex-efficiency-label">
                        ${insights.aiEfficiencyLabel}
                    </div>
                </div>
            `;
            
            container.innerHTML = statisticsHtml;
            
            // Initialize the chart if the canvas exists
            const chartCanvas = document.getElementById('vortex-translation-progress-chart');
            if (chartCanvas && typeof Chart !== 'undefined') {
                new Chart(chartCanvas, {
                    type: 'line',
                    data: {
                        labels: insights.progressChart.labels,
                        datasets: [
                            {
                                label: 'Manual Translations',
                                data: insights.progressChart.manual,
                                borderColor: '#2271b1',
                                backgroundColor: 'rgba(34, 113, 177, 0.1)',
                                fill: true
                            },
                            {
                                label: 'AI-Assisted Translations',
                                data: insights.progressChart.aiAssisted,
                                borderColor: '#9b59b6',
                                backgroundColor: 'rgba(155, 89, 182, 0.1)',
                                fill: true
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Translations'
                                }
                            },
                            x: {
                                title: {
                                    display: true,
                                    text: 'Date'
                                }
                            }
                        }
                    }
                });
            }
            
            // Bind refresh button
            document.getElementById('vortex-refresh-insights')?.addEventListener('click', () => {
                this.renderActivityInsights();
            });
        })
        .catch(error => {
            console.warn('Error loading translation insights:', error);
            container.innerHTML = `
                <div class="vortex-insights-error">
                    <p>Failed to load translation insights.</p>
                    <button type="button" class="vortex-button" id="vortex-retry-insights">
                        Retry
                    </button>
                </div>
            `;
            
            document.getElementById('vortex-retry-insights')?.addEventListener('click', () => {
                this.renderActivityInsights();
            });
        });
    }
}

// Initialize language admin when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.vortexLanguageAdmin = new VortexLanguageAdmin();
}); 