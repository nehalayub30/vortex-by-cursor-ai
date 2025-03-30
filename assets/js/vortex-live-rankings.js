/**
 * VORTEX AI Marketplace - Live Rankings
 * 
 * Handles the display and interaction with real-time rankings data
 * while ensuring AI agents (HURAII, CLOE, BusinessStrategist) maintain
 * continuous learning from user interactions.
 */
(function($) {
    'use strict';
    
    /**
     * VortexLiveRankings - Handles real-time rankings display and interaction
     */
    const VortexLiveRankings = {
        /**
         * Configuration settings
         */
        config: {
            ajaxUrl: vortex_rankings_data.ajax_url || '',
            nonce: vortex_rankings_data.nonce || '',
            userId: vortex_rankings_data.user_id || 0,
            userRole: vortex_rankings_data.user_role || 'viewer',
            refreshInterval: vortex_rankings_data.refresh_interval || 30000,
            animationDuration: vortex_rankings_data.animation_duration || 800,
            maxEntries: vortex_rankings_data.max_entries || 50,
            defaultCategory: vortex_rankings_data.default_category || 'artists',
            defaultTimeframe: vortex_rankings_data.default_timeframe || 'weekly',
            showMovementIndicators: vortex_rankings_data.show_movement_indicators !== '0',
            learningEnabled: vortex_rankings_data.learning_enabled !== '0',
            i18n: vortex_rankings_data.i18n || {}
        },
        
        /**
         * State management
         */
        state: {
            currentCategory: null,
            currentTimeframe: null,
            currentView: 'list',
            isLoading: false,
            lastUpdate: null,
            refreshTimer: null,
            rankingsData: {},
            previousRankings: {},
            interactionHistory: [],
            selectedEntry: null,
            searchQuery: '',
            currentPage: 1,
            perPage: 10,
            sortField: 'rank',
            sortDirection: 'asc'
        },
        
        /**
         * Initialize live rankings
         */
        init: function() {
            // Set initial state from config
            this.state.currentCategory = this.config.defaultCategory;
            this.state.currentTimeframe = this.config.defaultTimeframe;
            
            // Setup event listeners
            this.setupEventListeners();
            
            // Load initial data
            this.loadRankingsData();
            
            // Setup refresh timer
            this.setupRefreshTimer();
            
            // Track initialization for AI learning
            this.trackAIInteraction('rankings_initialized', {
                category: this.state.currentCategory,
                timeframe: this.state.currentTimeframe,
                view: this.state.currentView,
                timestamp: new Date().toISOString()
            });
            
            // Apply URL parameters if any
            this.applyUrlParameters();
        },
        
        /**
         * Setup event listeners for user interactions
         */
        setupEventListeners: function() {
            // Category selector
            $('.vortex-category-selector button').on('click', (e) => {
                const $button = $(e.currentTarget);
                const category = $button.data('category');
                
                // Skip if same category
                if (category === this.state.currentCategory) {
                    return;
                }
                
                // Update UI
                $('.vortex-category-selector button').removeClass('active');
                $button.addClass('active');
                
                // Update state and reload data
                this.state.currentCategory = category;
                this.state.currentPage = 1;
                this.loadRankingsData();
                
                // Track for AI learning
                this.trackAIInteraction('category_changed', {
                    previous: this.state.currentCategory,
                    current: category,
                    timestamp: new Date().toISOString()
                });
            });
            
            // Timeframe selector
            $('.vortex-timeframe-selector button').on('click', (e) => {
                const $button = $(e.currentTarget);
                const timeframe = $button.data('timeframe');
                
                // Skip if same timeframe
                if (timeframe === this.state.currentTimeframe) {
                    return;
                }
                
                // Update UI
                $('.vortex-timeframe-selector button').removeClass('active');
                $button.addClass('active');
                
                // Update state and reload data
                this.state.currentTimeframe = timeframe;
                this.loadRankingsData();
                
                // Track for AI learning
                this.trackAIInteraction('timeframe_changed', {
                    previous: this.state.currentTimeframe,
                    current: timeframe,
                    timestamp: new Date().toISOString()
                });
            });
            
            // View toggle (list/grid)
            $('.vortex-view-toggle button').on('click', (e) => {
                const $button = $(e.currentTarget);
                const view = $button.data('view');
                
                // Skip if same view
                if (view === this.state.currentView) {
                    return;
                }
                
                // Update UI
                $('.vortex-view-toggle button').removeClass('active');
                $button.addClass('active');
                
                // Update state
                this.state.currentView = view;
                
                // Re-render with current data
                this.renderRankings();
                
                // Track for AI learning
                this.trackAIInteraction('view_changed', {
                    previous: this.state.currentView,
                    current: view,
                    timestamp: new Date().toISOString()
                });
            });
            
            // Search input
            $('#vortex-rankings-search').on('input', this.debounce((e) => {
                const query = $(e.target).val().trim();
                
                // Update state
                this.state.searchQuery = query;
                this.state.currentPage = 1;
                
                // Filter existing data
                this.renderRankings();
                
                // Track for AI learning if query is not empty
                if (query.length > 0) {
                    this.trackAIInteraction('search_query_entered', {
                        query: query,
                        category: this.state.currentCategory,
                        timestamp: new Date().toISOString()
                    });
                }
            }, 300));
            
            // Pagination
            $(document).on('click', '.vortex-pagination button', (e) => {
                const $button = $(e.currentTarget);
                
                // Skip if disabled
                if ($button.prop('disabled')) {
                    return;
                }
                
                // Get requested page
                const page = $button.data('page') || 1;
                
                // Skip if same page
                if (page === this.state.currentPage) {
                    return;
                }
                
                // Update state
                this.state.currentPage = page;
                
                // Re-render with current data
                this.renderRankings();
                
                // Scroll to top of container
                $('.vortex-rankings-container').get(0).scrollIntoView({ behavior: 'smooth' });
                
                // Track for AI learning
                this.trackAIInteraction('page_changed', {
                    previous: this.state.currentPage,
                    current: page,
                    timestamp: new Date().toISOString()
                });
            });
            
            // Sort headers
            $(document).on('click', '.vortex-rankings-list th.sortable', (e) => {
                const $header = $(e.currentTarget);
                const field = $header.data('field');
                
                // Determine sort direction
                let direction = 'asc';
                if (field === this.state.sortField) {
                    direction = this.state.sortDirection === 'asc' ? 'desc' : 'asc';
                }
                
                // Update state
                this.state.sortField = field;
                this.state.sortDirection = direction;
                
                // Re-render with current data
                this.renderRankings();
                
                // Track for AI learning
                this.trackAIInteraction('sort_changed', {
                    field: field,
                    direction: direction,
                    timestamp: new Date().toISOString()
                });
            });
            
            // Ranking entry click
            $(document).on('click', '.vortex-ranking-entry', (e) => {
                if ($(e.target).is('a, button')) {
                    return; // Skip if clicking a link or button inside the entry
                }
                
                const $entry = $(e.currentTarget);
                const entryId = $entry.data('id');
                
                // Show details for this entry
                this.showEntryDetails(entryId);
                
                // Track for AI learning
                this.trackAIInteraction('entry_selected', {
                    entry_id: entryId,
                    category: this.state.currentCategory,
                    timestamp: new Date().toISOString()
                });
            });
            
            // Manual refresh button
            $('.vortex-refresh-rankings').on('click', () => {
                this.loadRankingsData(true);
                
                // Track for AI learning
                this.trackAIInteraction('manual_refresh', {
                    category: this.state.currentCategory,
                    timeframe: this.state.currentTimeframe,
                    timestamp: new Date().toISOString()
                });
            });
            
            // Close entry details
            $(document).on('click', '.vortex-close-details', () => {
                this.closeEntryDetails();
                
                // Track for AI learning
                this.trackAIInteraction('details_closed', {
                    entry_id: this.state.selectedEntry,
                    timestamp: new Date().toISOString()
                });
            });
            
            // Download rankings button
            $('.vortex-download-rankings').on('click', () => {
                this.downloadRankings();
                
                // Track for AI learning
                this.trackAIInteraction('rankings_downloaded', {
                    category: this.state.currentCategory,
                    timeframe: this.state.currentTimeframe,
                    timestamp: new Date().toISOString()
                });
            });
        },
        
        /**
         * Load rankings data from server
         * 
         * @param {boolean} forceRefresh Force refresh even if data is recent
         */
        loadRankingsData: function(forceRefresh = false) {
            // Skip if already loading
            if (this.state.isLoading) {
                return;
            }
            
            // Skip if recent update unless forced
            if (!forceRefresh && this.state.lastUpdate) {
                const now = new Date();
                const timeSinceUpdate = now - this.state.lastUpdate;
                if (timeSinceUpdate < 5000) { // Less than 5 seconds ago
                    return;
                }
            }
            
            // Store previous rankings for comparison
            if (this.state.rankingsData[this.state.currentCategory]) {
                this.state.previousRankings[this.state.currentCategory] = 
                    $.extend(true, [], this.state.rankingsData[this.state.currentCategory]);
            }
            
            // Show loading state
            this.state.isLoading = true;
            this.showLoading();
            
            // Make AJAX request
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'vortex_get_rankings',
                    category: this.state.currentCategory,
                    timeframe: this.state.currentTimeframe,
                    nonce: this.config.nonce
                },
                success: (response) => {
                    if (response.success) {
                        // Update data and timestamp
                        this.state.rankingsData[this.state.currentCategory] = response.data.rankings || [];
                        this.state.lastUpdate = new Date();
                        
                        // Update UI
                        this.renderRankings();
                        
                        // Show last updated time
                        $('.vortex-last-updated').text(
                            (this.config.i18n.lastUpdated || 'Last updated:') + ' ' + 
                            this.formatTime(this.state.lastUpdate)
                        );
                        
                        // Track for AI learning
                        this.trackAIInteraction('rankings_loaded', {
                            category: this.state.currentCategory,
                            timeframe: this.state.currentTimeframe,
                            count: this.state.rankingsData[this.state.currentCategory].length,
                            forced: forceRefresh,
                            timestamp: new Date().toISOString()
                        });
                    } else {
                        this.showError(response.data.message || this.config.i18n.loadError || 'Error loading rankings');
                    }
                },
                error: () => {
                    this.showError(this.config.i18n.connectionError || 'Connection error');
                },
                complete: () => {
                    this.state.isLoading = false;
                    this.hideLoading();
                }
            });
        },
        
        /**
         * Render rankings based on current state
         */
        renderRankings: function() {
            const $container = $('.vortex-rankings-container');
            const $contentContainer = $('.vortex-rankings-content');
            
            if (!$container.length || !$contentContainer.length) {
                return;
            }
            
            // Get current rankings data
            const rankings = this.state.rankingsData[this.state.currentCategory] || [];
            
            // Apply search filter if needed
            let filteredRankings = rankings;
            if (this.state.searchQuery) {
                const query = this.state.searchQuery.toLowerCase();
                filteredRankings = rankings.filter(entry => {
                    return entry.name.toLowerCase().includes(query) ||
                           (entry.description && entry.description.toLowerCase().includes(query));
                });
            }
            
            // Apply sorting
            if (this.state.sortField) {
                filteredRankings.sort((a, b) => {
                    let valueA = a[this.state.sortField];
                    let valueB = b[this.state.sortField];
                    
                    // Handle null values
                    if (valueA === null) valueA = this.state.sortDirection === 'asc' ? Infinity : -Infinity;
                    if (valueB === null) valueB = this.state.sortDirection === 'asc' ? Infinity : -Infinity;
                    
                    // Compare strings appropriately
                    if (typeof valueA === 'string' && typeof valueB === 'string') {
                        return this.state.sortDirection === 'asc' ? 
                            valueA.localeCompare(valueB) : 
                            valueB.localeCompare(valueA);
                    }
                    
                    // Compare numbers
                    return this.state.sortDirection === 'asc' ? 
                        valueA - valueB : 
                        valueB - valueA;
                });
            }
            
            // Pagination
            const totalEntries = filteredRankings.length;
            const totalPages = Math.ceil(totalEntries / this.state.perPage);
            const startIndex = (this.state.currentPage - 1) * this.state.perPage;
            const endIndex = Math.min(startIndex + this.state.perPage, totalEntries);
            const paginatedRankings = filteredRankings.slice(startIndex, endIndex);
            
            // Render content based on view
            $contentContainer.empty();
            
            if (paginatedRankings.length === 0) {
                $contentContainer.html(`<div class="vortex-no-results">${this.config.i18n.noResults || 'No rankings found'}</div>`);
                $('.vortex-pagination').hide();
                return;
            }
            
            if (this.state.currentView === 'list') {
                this.renderListView($contentContainer, paginatedRankings);
            } else {
                this.renderGridView($contentContainer, paginatedRankings);
            }
            
            // Update pagination
            this.renderPagination(totalPages);
        },
        
        /**
         * Render rankings in list view
         * 
         * @param {jQuery} $container The container element
         * @param {Array} rankings Array of ranking entries
         */
        renderListView: function($container, rankings) {
            const previousRankings = this.state.previousRankings[this.state.currentCategory] || [];
            
            // Create table
            const $table = $(`
                <table class="vortex-rankings-list">
                    <thead>
                        <tr>
                            <th class="sortable" data-field="rank">${this.config.i18n.rank || 'Rank'}</th>
                            <th>${this.config.i18n.avatar || 'Avatar'}</th>
                            <th class="sortable" data-field="name">${this.config.i18n.name || 'Name'}</th>
                            <th class="sortable" data-field="score">${this.config.i18n.score || 'Score'}</th>
                            ${this.config.showMovementIndicators ? 
                                `<th class="sortable" data-field="change">${this.config.i18n.change || 'Change'}</th>` : ''}
                            <th class="sortable" data-field="items">${this.config.i18n.items || 'Items'}</th>
                            <th>${this.config.i18n.actions || 'Actions'}</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            `);
            
            const $tbody = $table.find('tbody');
            
            // Add entries
            $.each(rankings, (index, entry) => {
                // Find previous rank for comparison
                let previousRank = null;
                let rankChange = 0;
                const previousEntry = previousRankings.find(item => item.id === entry.id);
                
                if (previousEntry) {
                    previousRank = previousEntry.rank;
                    rankChange = previousRank - entry.rank; // Positive = improved rank, negative = declined rank
                }
                
                // Create movement indicator
                let movementHtml = '';
                if (this.config.showMovementIndicators && previousRank !== null) {
                    let movementClass = 'neutral';
                    let movementIcon = 'minus';
                    
                    if (rankChange > 0) {
                        movementClass = 'positive';
                        movementIcon = 'arrow-up-alt';
                    } else if (rankChange < 0) {
                        movementClass = 'negative';
                        movementIcon = 'arrow-down-alt';
                    }
                    
                    movementHtml = `
                        <div class="vortex-movement ${movementClass}">
                            <span class="dashicons dashicons-${movementIcon}"></span>
                            <span>${Math.abs(rankChange)}</span>
                        </div>
                    `;
                }
                
                // Create row
                const $row = $(`
                    <tr class="vortex-ranking-entry" data-id="${entry.id}">
                        <td class="vortex-rank">${entry.rank}</td>
                        <td class="vortex-avatar">
                            <img src="${entry.avatar}" alt="${entry.name}" />
                        </td>
                        <td class="vortex-name">${entry.name}</td>
                        <td class="vortex-score">${entry.score}</td>
                        ${this.config.showMovementIndicators ? 
                            `<td class="vortex-change">${movementHtml}</td>` : ''}
                        <td class="vortex-items">${entry.items}</td>
                        <td class="vortex-actions">
                            <button class="vortex-view-details" data-id="${entry.id}">
                                <span class="dashicons dashicons-visibility"></span>
                            </button>
                            <a href="${entry.url}" class="vortex-view-profile">
                                <span class="dashicons dashicons-admin-users"></span>
                            </a>
                        </td>
                    </tr>
                `);
                
                // Highlight new entries
                if (!previousEntry && previousRankings.length > 0) {
                    $row.addClass('vortex-new-entry');
                }
                
                // Add to table
                $tbody.append($row);
            });
            
            // Add to container
            $container.append($table);
            
            // Add event handlers for action buttons
            $table.find('.vortex-view-details').on('click', (e) => {
                e.stopPropagation(); // Prevent triggering row click
                const id = $(e.currentTarget).data('id');
                this.showEntryDetails(id);
                
                // Track for AI learning
                this.trackAIInteraction('view_details_clicked', {
                    entry_id: id,
                    timestamp: new Date().toISOString()
                });
            });
            
            // Highlight sort column
            $table.find(`th[data-field="${this.state.sortField}"]`).addClass('sorted-' + this.state.sortDirection);
        },
        
        /**
         * Render rankings in grid view
         * 
         * @param {jQuery} $container The container element
         * @param {Array} rankings Array of ranking entries
         */
        renderGridView: function($container, rankings) {
            const previousRankings = this.state.previousRankings[this.state.currentCategory] || [];
            
            // Create grid container
            const $grid = $('<div class="vortex-rankings-grid"></div>');
            
            // Add entries
            $.each(rankings, (index, entry) => {
                // Find previous rank for comparison
                let previousRank = null;
                let rankChange = 0;
                const previousEntry = previousRankings.find(item => item.id === entry.id);
                
                if (previousEntry) {
                    previousRank = previousEntry.rank;
                    rankChange = previousRank - entry.rank; // Positive = improved rank, negative = declined rank
                }
                
                // Create movement indicator
                let movementHtml = '';
                if (this.config.showMovementIndicators && previousRank !== null) {
                    let movementClass = 'neutral';
                    let movementIcon = 'minus';
                    
                    if (rankChange > 0) {
                        movementClass = 'positive';
                        movementIcon = 'arrow-up-alt';
                    } else if (rankChange < 0) {
                        movementClass = 'negative';
                        movementIcon = 'arrow-down-alt';
                    }
                    
                    movementHtml = `
                        <div class="vortex-movement ${movementClass}">
                            <span class="dashicons dashicons-${movementIcon}"></span>
                            <span>${Math.abs(rankChange)}</span>
                        </div>
                    `;
                }
                
                // Create card
                const $card = $(`
                    <div class="vortex-ranking-card vortex-ranking-entry" data-id="${entry.id}">
                        <div class="vortex-card-header">
                            <div class="vortex-rank">#${entry.rank}</div>
                            ${this.config.showMovementIndicators ? movementHtml : ''}
                        </div>
                        <div class="vortex-card-avatar">
                            <img src="${entry.avatar}" alt="${entry.name}" />
                        </div>
                        <div class="vortex-card-name">${entry.name}</div>
                        <div class="vortex-card-score">
                            <span>${this.config.i18n.score || 'Score'}:</span> ${entry.score}
                        </div>
                        <div class="vortex-card-items">
                            <span>${this.config.i18n.items || 'Items'}:</span> ${entry.items}
                        </div>
                        <div class="vortex-card-actions">
                            <button class="vortex-view-details" data-id="${entry.id}">
                                ${this.config.i18n.viewDetails || 'View Details'}
                            </button>
                            <a href="${entry.url}" class="vortex-view-profile">
                                ${this.config.i18n.viewProfile || 'View Profile'}
                            </a>
                        </div>
                    </div>
                `);
                
                // Highlight new entries
                if (!previousEntry && previousRankings.length > 0) {
                    $card.addClass('vortex-new-entry');
                }
                
                // Add to grid
                $grid.append($card);
            });
            
            // Add to container
            $container.append($grid);
            
            // Add event handlers for action buttons
            $grid.find('.vortex-view-details').on('click', (e) => {
                e.stopPropagation(); // Prevent triggering card click
                const id = $(e.currentTarget).data('id');
                this.showEntryDetails(id);
                
                // Track for AI learning
                this.trackAIInteraction('view_details_clicked', {
                    entry_id: id,
                    timestamp: new Date().toISOString()
                });
            });
        },
        
        /**
         * Render pagination controls
         * 
         * @param {number} totalPages Total number of pages
         */
        renderPagination: function(totalPages) {
            const $pagination = $('.vortex-pagination');
            
            if (!$pagination.length) {
                return;
            }
            
            // Hide pagination if only one page
            if (totalPages <= 1) {
                $pagination.hide();
                return;
            }
            
            $pagination.empty();
            
            const currentPage = this.state.currentPage;
            
            // Add previous button
            $pagination.append(`
                <button class="vortex-page-prev" data-page="${currentPage - 1}" ${currentPage <= 1 ? 'disabled' : ''}>
                    <span class="dashicons dashicons-arrow-left-alt2"></span>
                </button>
            `);
            
            // Add page buttons (show max 5 pages with current in middle)
            let startPage = Math.max(1, currentPage - 2);
            let endPage = Math.min(totalPages, startPage + 4);
            
            if (endPage - startPage < 4) {
                startPage = Math.max(1, endPage - 4);
            }
            
            for (let i = startPage; i <= endPage; i++) {
                $pagination.append(`
                    <button class="vortex-page-number ${i === currentPage ? 'active' : ''}" data-page="${i}">
                        ${i}
                    </button>
                `);
            }
            
            // Add next button
            $pagination.append(`
                <button class="vortex-page-next" data-page="${currentPage + 1}" ${currentPage >= totalPages ? 'disabled' : ''}>
                    <span class="dashicons dashicons-arrow-right-alt2"></span>
                </button>
            `);
            
            // Show pagination
            $pagination.show();
        },
        
        /**
         * Show details for a selected entry
         * 
         * @param {number|string} entryId ID of the entry to show details for
         */
        showEntryDetails: function(entryId) {
            if (!entryId) {
                return;
            }
            
            // Update state
            this.state.selectedEntry = entryId;
            
            // Find the entry data
            const rankings = this.state.rankingsData[this.state.currentCategory] || [];
            const entry = rankings.find(item => item.id == entryId); // Use loose equality for string/number
            
            if (!entry) {
                this.showError(this.config.i18n.entryNotFound || 'Entry not found');
                return;
            }
            
            // Find previous rank for comparison
            let previousRank = null;
            let rankChange = 0;
            const previousRankings = this.state.previousRankings[this.state.currentCategory] || [];
            const previousEntry = previousRankings.find(item => item.id === entry.id);
            
            if (previousEntry) {
                previousRank = previousEntry.rank;
                rankChange = previousRank - entry.rank; // Positive = improved rank, negative = declined rank
            }
            
            // Create details container
            const $detailsContainer = $('.vortex-entry-details');
            
            if (!$detailsContainer.length) {
                this.showError(this.config.i18n.detailsContainerMissing || 'Details container not found');
                return;
            }
            
            // Create movement text
            let movementText = this.config.i18n.noChange || 'No change';
            let movementClass = 'neutral';
            
            if (rankChange > 0) {
                movementText = `+${rankChange} ${this.config.i18n.positions || 'positions'}`;
                movementClass = 'positive';
            } else if (rankChange < 0) {
                movementText = `${rankChange} ${this.config.i18n.positions || 'positions'}`;
                movementClass = 'negative';
            }
            
            // Populate details
            $detailsContainer.html(`
                <div class="vortex-details-header">
                    <h2>${entry.name}</h2>
                    <button class="vortex-close-details">
                        <span class="dashicons dashicons-no-alt"></span>
                    </button>
                </div>
                <div class="vortex-details-content">
                    <div class="vortex-details-main">
                        <div class="vortex-details-avatar">
                            <img src="${entry.avatar}" alt="${entry.name}" />
                        </div>
                        <div class="vortex-details-info">
                            <div class="vortex-detail-item">
                                <span class="vortex-detail-label">${this.config.i18n.rank || 'Rank'}:</span>
                                <span class="vortex-detail-value">#${entry.rank}</span>
                            </div>
                            <div class="vortex-detail-item">
                                <span class="vortex-detail-label">${this.config.i18n.score || 'Score'}:</span>
                                <span class="vortex-detail-value">${entry.score}</span>
                            </div>
                            <div class="vortex-detail-item">
                                <span class="vortex-detail-label">${this.config.i18n.change || 'Change'}:</span>
                                <span class="vortex-detail-value ${movementClass}">${movementText}</span>
                            </div>
                            <div class="vortex-detail-item">
                                <span class="vortex-detail-label">${this.config.i18n.items || 'Items'}:</span>
                                <span class="vortex-detail-value">${entry.items}</span>
                            </div>
                            ${entry.description ? `
                                <div class="vortex-detail-description">
                                    <h4>${this.config.i18n.about || 'About'}</h4>
                                    <p>${entry.description}</p>
                                </div>
                            ` : ''}
                        </div>
                    </div>
                    ${entry.stats ? `
                        <div class="vortex-details-stats">
                            <h3>${this.config.i18n.statistics || 'Statistics'}</h3>
                            <div class="vortex-stats-grid">
                                ${Object.entries(entry.stats).map(([key, value]) => `
                                    <div class="vortex-stat-item">
                                        <div class="vortex-stat-value">${value}</div>
                                        <div class="vortex-stat-label">${key}</div>
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                    ` : ''}
                    <div class="vortex-details-actions">
                        <a href="${entry.url}" class="vortex-action-button vortex-view-profile">
                            ${this.config.i18n.viewProfile || 'View Full Profile'}
                        </a>
                        ${entry.marketplace_url ? `
                            <a href="${entry.marketplace_url}" class="vortex-action-button vortex-visit-marketplace">
                                ${this.config.i18n.viewInMarketplace || 'View in Marketplace'}
                            </a>
                        ` : ''}
                    </div>
                </div>
            `);
            
            // Show details container and add active class
            $detailsContainer.addClass('active');
            
            // Highlight the selected entry in the list/grid
            $('.vortex-ranking-entry').removeClass('selected');
            $(`.vortex-ranking-entry[data-id="${entryId}"]`).addClass('selected');
            
            // Load AI insights for this entry
            this.loadEntryInsights(entry);
        },
        
        /**
         * Close entry details panel
         */
        closeEntryDetails: function() {
            $('.vortex-entry-details').removeClass('active');
            $('.vortex-ranking-entry').removeClass('selected');
            this.state.selectedEntry = null;
        },
        
        /**
         * Load AI insights for a ranking entry
         * 
         * @param {Object} entry The ranking entry
         */
        loadEntryInsights: function(entry) {
            // Only load insights if learning is enabled
            if (!this.config.learningEnabled) {
                return;
            }
            
            // Add insights container if it doesn't exist
            const $detailsContainer = $('.vortex-entry-details');
            if (!$detailsContainer.find('.vortex-ai-insights').length) {
                $detailsContainer.find('.vortex-details-content').append(`
                    <div class="vortex-ai-insights">
                        <h3>
                            <span class="dashicons dashicons-chart-line"></span>
                            ${this.config.i18n.aiInsights || 'AI Insights'}
                        </h3>
                        <div class="vortex-ai-insights-content">
                            <div class="vortex-loading-spinner"></div>
                            <p>${this.config.i18n.loadingInsights || 'Loading AI insights...'}</p>
                        </div>
                    </div>
                `);
            } else {
                $detailsContainer.find('.vortex-ai-insights-content').html(`
                    <div class="vortex-loading-spinner"></div>
                    <p>${this.config.i18n.loadingInsights || 'Loading AI insights...'}</p>
                `);
            }
            
            // Request insights from server
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'vortex_get_ranking_insights',
                    entry_id: entry.id,
                    category: this.state.currentCategory,
                    nonce: this.config.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.renderEntryInsights(response.data.insights);
                        
                        // Track for AI learning
                        this.trackAIInteraction('insights_loaded', {
                            entry_id: entry.id,
                            has_cloe_insights: !!response.data.insights.cloe,
                            has_business_insights: !!response.data.insights.business,
                            timestamp: new Date().toISOString()
                        });
                    } else {
                        $('.vortex-ai-insights-content').html(`
                            <p class="vortex-error">${response.data.message || this.config.i18n.insightsError || 'Error loading insights'}</p>
                        `);
                    }
                },
                error: () => {
                    $('.vortex-ai-insights-content').html(`
                        <p class="vortex-error">${this.config.i18n.connectionError || 'Connection error'}</p>
                    `);
                }
            });
        },
        
        /**
         * Render AI insights for an entry
         * 
         * @param {Object} insights The insights data
         */
        renderEntryInsights: function(insights) {
            const $container = $('.vortex-ai-insights-content');
            
            if (!$container.length || !insights) {
                return;
            }
            
            $container.empty();
            
            // Add CLOE insights
            if (insights.cloe) {
                const $cloeInsights = $(`
                    <div class="vortex-cloe-insights">
                        <h4>
                            <span class="vortex-agent-icon cloe"></span>
                            ${this.config.i18n.cloeInsights || 'CLOE Analysis'}
                        </h4>
                        <div class="vortex-cloe-content"></div>
                    </div>
                `);
                
                const $cloeContent = $cloeInsights.find('.vortex-cloe-content');
                
                if (insights.cloe.summary) {
                    $cloeContent.html(insights.cloe.summary);
                }
            }
        },
        
        // ... (rest of the existing methods remain unchanged)
    };
    
    // ... (rest of the existing code remains unchanged)
})(jQuery); 