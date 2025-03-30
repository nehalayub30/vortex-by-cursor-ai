                <!-- Find Artists Tab -->
                <div id="find-artists" class="tab-content">
                    <h3><?php _e('Find Artists for Swapping', 'vortex-ai-marketplace'); ?></h3>
                    
                    <div class="artists-search-container">
                        <div class="search-filters">
                            <form id="artists-search-form" class="vortex-form">
                                <div class="search-field">
                                    <input type="text" id="artist-search" name="artist_search" placeholder="<?php esc_attr_e('Search by artist name or style...', 'vortex-ai-marketplace'); ?>">
                                    <button type="submit" class="search-button">
                                        <span class="dashicons dashicons-search"></span>
                                    </button>
                                </div>
                                
                                <div class="filter-fields">
                                    <div class="filter-field">
                                        <label for="artwork-style"><?php _e('Artwork Style', 'vortex-ai-marketplace'); ?></label>
                                        <select id="artwork-style" name="artwork_style">
                                            <option value=""><?php _e('All Styles', 'vortex-ai-marketplace'); ?></option>
                                            <option value="abstract"><?php _e('Abstract', 'vortex-ai-marketplace'); ?></option>
                                            <option value="contemporary"><?php _e('Contemporary', 'vortex-ai-marketplace'); ?></option>
                                            <option value="digital"><?php _e('Digital Art', 'vortex-ai-marketplace'); ?></option>
                                            <option value="illustration"><?php _e('Illustration', 'vortex-ai-marketplace'); ?></option>
                                            <option value="painting"><?php _e('Painting', 'vortex-ai-marketplace'); ?></option>
                                            <option value="photography"><?php _e('Photography', 'vortex-ai-marketplace'); ?></option>
                                            <option value="sculpture"><?php _e('Sculpture', 'vortex-ai-marketplace'); ?></option>
                                        </select>
                                    </div>
                                    
                                    <div class="filter-field">
                                        <label for="artwork-medium"><?php _e('Medium', 'vortex-ai-marketplace'); ?></label>
                                        <select id="artwork-medium" name="artwork_medium">
                                            <option value=""><?php _e('All Mediums', 'vortex-ai-marketplace'); ?></option>
                                            <option value="acrylic"><?php _e('Acrylic', 'vortex-ai-marketplace'); ?></option>
                                            <option value="digital"><?php _e('Digital', 'vortex-ai-marketplace'); ?></option>
                                            <option value="oil"><?php _e('Oil', 'vortex-ai-marketplace'); ?></option>
                                            <option value="mixed"><?php _e('Mixed Media', 'vortex-ai-marketplace'); ?></option>
                                            <option value="photography"><?php _e('Photography', 'vortex-ai-marketplace'); ?></option>
                                            <option value="watercolor"><?php _e('Watercolor', 'vortex-ai-marketplace'); ?></option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="ai-recommendation">
                                    <label>
                                        <input type="checkbox" id="ai-recommended" name="ai_recommended">
                                        <?php _e('Show AI-recommended artists based on my style', 'vortex-ai-marketplace'); ?>
                                    </label>
                                </div>
                            </form>
                        </div>
                        
                        <div class="artists-results">
                            <div class="loading-artists" style="display: none;">
                                <div class="spinner"></div>
                                <p><?php _e('Finding artists...', 'vortex-ai-marketplace'); ?></p>
                            </div>
                            
                            <div class="artists-list">
                                <?php
                                // Get featured artists (for initial display)
                                $artist_args = array(
                                    'role' => 'vortex_artist',
                                    'meta_query' => array(
                                        array(
                                            'key' => 'vortex_artist_verified',
                                            'value' => 'yes',
                                            'compare' => '='
                                        )
                                    ),
                                    'exclude' => array($user_id), // Exclude current user
                                    'number' => 8,
                                    'orderby' => 'registered',
                                    'order' => 'DESC'
                                );
                                
                                $featured_artists = get_users($artist_args);
                                
                                if (empty($featured_artists)) :
                                ?>
                                    <div class="empty-state">
                                        <p><?php _e('No artists found. Try adjusting your search criteria.', 'vortex-ai-marketplace'); ?></p>
                                    </div>
                                <?php else : ?>
                                    <h4 class="artists-section-title"><?php _e('Featured Artists', 'vortex-ai-marketplace'); ?></h4>
                                    <div class="artists-grid">
                                        <?php foreach ($featured_artists as $artist) : 
                                            // Get artist data
                                            $artist_id = $artist->ID;
                                            $display_name = $artist->display_name;
                                            $artist_bio = get_user_meta($artist_id, 'vortex_artist_bio', true);
                                            $artist_portfolio = get_user_meta($artist_id, 'vortex_artist_portfolio_url', true);
                                            
                                            // Get artist's verified artworks
                                            $artist_artworks = $artwork_verification->get_verified_artworks($artist_id);
                                            $artwork_count = count($artist_artworks);
                                            
                                            // Get artist avatar
                                            $avatar = get_avatar($artist_id, 96);
                                            
                                            // Get random artwork to display
                                            $random_artwork_id = !empty($artist_artworks) ? $artist_artworks[array_rand($artist_artworks)] : 0;
                                            $artwork_image = $random_artwork_id ? get_the_post_thumbnail($random_artwork_id, 'medium') : '';
                                        ?>
                                            <div class="artist-card" data-artist-id="<?php echo esc_attr($artist_id); ?>">
                                                <div class="artist-header">
                                                    <div class="artist-avatar">
                                                        <?php echo $avatar; ?>
                                                    </div>
                                                    <div class="artist-info">
                                                        <h5 class="artist-name"><?php echo esc_html($display_name); ?></h5>
                                                        <p class="artist-artwork-count">
                                                            <?php echo sprintf(_n('%s Artwork', '%s Artworks', $artwork_count, 'vortex-ai-marketplace'), number_format($artwork_count)); ?>
                                                        </p>
                                                    </div>
                                                </div>
                                                
                                                <?php if ($artwork_image) : ?>
                                                <div class="artist-artwork-preview">
                                                    <?php echo $artwork_image; ?>
                                                </div>
                                                <?php endif; ?>
                                                
                                                <div class="artist-details">
                                                    <?php if ($artist_bio) : ?>
                                                    <div class="artist-bio">
                                                        <p><?php echo wp_trim_words(esc_html($artist_bio), 15, '...'); ?></p>
                                                    </div>
                                                    <?php endif; ?>
                                                    
                                                    <div class="artist-actions">
                                                        <button class="button view-artist-button" data-artist-id="<?php echo esc_attr($artist_id); ?>">
                                                            <?php _e('View Profile', 'vortex-ai-marketplace'); ?>
                                                        </button>
                                                        <button class="button view-artworks-button" data-artist-id="<?php echo esc_attr($artist_id); ?>">
                                                            <?php _e('View Artworks', 'vortex-ai-marketplace'); ?>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="ai-recommended-artists" style="display: none;">
                                <h4 class="artists-section-title">
                                    <span class="ai-badge">AI</span> <?php _e('Recommended Artists Based on Your Style', 'vortex-ai-marketplace'); ?>
                                </h4>
                                <div class="artists-grid ai-recommendations-grid"></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Artist Profile Modal -->
                    <div id="artist-profile-modal" class="vortex-modal" style="display: none;">
                        <div class="modal-content">
                            <span class="close-modal">&times;</span>
                            <div class="artist-profile-container">
                                <div class="artist-profile-header">
                                    <div class="artist-avatar-large"></div>
                                    <div class="artist-profile-info">
                                        <h3 class="artist-name"></h3>
                                        <p class="artist-meta"></p>
                                        <div class="artist-social-links"></div>
                                    </div>
                                </div>
                                
                                <div class="artist-profile-bio"></div>
                                
                                <div class="artist-artworks-container">
                                    <h4><?php _e('Available Artworks for Swap', 'vortex-ai-marketplace'); ?></h4>
                                    <div class="artist-artworks-grid"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Swap Proposal Modal -->
                    <div id="swap-proposal-modal" class="vortex-modal" style="display: none;">
                        <div class="modal-content">
                            <span class="close-modal">&times;</span>
                            <div class="swap-proposal-container">
                                <h3><?php _e('Propose Artwork Swap', 'vortex-ai-marketplace'); ?></h3>
                                
                                <div class="swap-artworks-selection">
                                    <div class="their-artwork">
                                        <h4><?php _e('Their Artwork', 'vortex-ai-marketplace'); ?></h4>
                                        <div class="selected-artwork-container"></div>
                                    </div>
                                    
                                    <div class="swap-arrows">
                                        <span class="dashicons dashicons-arrow-right-alt"></span>
                                        <span class="dashicons dashicons-arrow-left-alt"></span>
                                    </div>
                                    
                                    <div class="your-artwork">
                                        <h4><?php _e('Your Artwork', 'vortex-ai-marketplace'); ?></h4>
                                        <div class="select-your-artwork">
                                            <select id="your-artwork-selection" name="your_artwork_id">
                                                <option value=""><?php _e('-- Select Your Artwork --', 'vortex-ai-marketplace'); ?></option>
                                                <?php
                                                // Get only artworks not currently in swap
                                                $available_artworks = array_filter($verified_artworks, function($artwork_id) {
                                                    return !get_post_meta($artwork_id, 'vortex_in_swap', true);
                                                });
                                                
                                                foreach ($available_artworks as $artwork_id) {
                                                    echo '<option value="' . esc_attr($artwork_id) . '">' . esc_html(get_the_title($artwork_id)) . '</option>';
                                                }
                                                ?>
                                            </select>
                                        </div>
                                        <div class="selected-your-artwork"></div>
                                    </div>
                                </div>
                                
                                <div class="swap-message-container">
                                    <label for="swap-message"><?php _e('Message to Artist (Optional)', 'vortex-ai-marketplace'); ?></label>
                                    <textarea id="swap-message" name="swap_message" rows="3" placeholder="<?php esc_attr_e('Enter a message to the other artist about why you'd like to swap...', 'vortex-ai-marketplace'); ?>"></textarea>
                                </div>
                                
                                <div class="swap-confirmation">
                                    <label>
                                        <input type="checkbox" id="swap-confirm" name="swap_confirm" required>
                                        <?php _e('I confirm that I want to propose swapping these artworks and understand that both artworks will be transferred on the blockchain if accepted.', 'vortex-ai-marketplace'); ?>
                                    </label>
                                </div>
                                
                                <div class="swap-actions">
                                    <button id="submit-swap-proposal" class="button button-primary" disabled>
                                        <?php _e('Submit Swap Proposal', 'vortex-ai-marketplace'); ?>
                                    </button>
                                    <button class="button cancel-proposal">
                                        <?php _e('Cancel', 'vortex-ai-marketplace'); ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Swap History Tab -->
                <div id="swap-history" class="tab-content">
                    <h3><?php _e('Artwork Swap History', 'vortex-ai-marketplace'); ?></h3>
                    
                    <?php if (empty($swap_history)) : ?>
                        <div class="empty-state">
                            <p><?php _e('You haven\'t completed any artwork swaps yet.', 'vortex-ai-marketplace'); ?></p>
                            <p><?php _e('When you complete swaps, they will appear here with details about the transaction.', 'vortex-ai-marketplace'); ?></p>
                        </div>
                    <?php else : ?>
                        <div class="swap-history-container">
                            <div class="swap-history-filters">
                                <div class="filter-group">
                                    <label for="history-filter"><?php _e('Show:', 'vortex-ai-marketplace'); ?></label>
                                    <select id="history-filter" name="history_filter">
                                        <option value="all"><?php _e('All Swaps', 'vortex-ai-marketplace'); ?></option>
                                        <option value="initiated"><?php _e('Initiated by Me', 'vortex-ai-marketplace'); ?></option>
                                        <option value="received"><?php _e('Received from Others', 'vortex-ai-marketplace'); ?></option>
                                    </select>
                                </div>
                                
                                <div class="sort-group">
                                    <label for="history-sort"><?php _e('Sort by:', 'vortex-ai-marketplace'); ?></label>
                                    <select id="history-sort" name="history_sort">
                                        <option value="recent"><?php _e('Most Recent', 'vortex-ai-marketplace'); ?></option>
                                        <option value="oldest"><?php _e('Oldest First', 'vortex-ai-marketplace'); ?></option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="swap-history-list">
                                <?php foreach ($swap_history as $swap) : 
                                    $initiator_artwork_id = $swap->initiator_artwork_id;
                                    $recipient_artwork_id = $swap->recipient_artwork_id;
                                    $initiator_id = $swap->initiator_id;
                                    $recipient_id = $swap->recipient_id;
                                    $completed_date = $swap->completed_date;
                                    $transaction_hash = get_post_meta($swap->ID, 'vortex_swap_transaction', true);
                                    
                                    // Get blockchain connection
                                    $blockchain = new Vortex_Blockchain_Connection();
                                    $transaction_url = $blockchain->get_explorer_url($transaction_hash);
                                    
                                    // Get artworks data
                                    $initiator_artwork = get_post($initiator_artwork_id);
                                    $recipient_artwork = get_post($recipient_artwork_id);
                                    
                                    // Get user data
                                    $initiator = get_user_by('id', $initiator_id);
                                    $recipient = get_user_by('id', $recipient_id);
                                    
                                    // Determine if current user was initiator or recipient
                                    $is_initiator = $initiator_id == $user_id;
                                    $other_user = $is_initiator ? $recipient : $initiator;
                                    $user_artwork = $is_initiator ? $initiator_artwork : $recipient_artwork;
                                    $other_artwork = $is_initiator ? $recipient_artwork : $initiator_artwork;
                                    $swap_type = $is_initiator ? 'initiated' : 'received';
                                ?>
                                    <div class="swap-history-item" data-swap-id="<?php echo esc_attr($swap->ID); ?>" data-swap-type="<?php echo esc_attr($swap_type); ?>">
                                        <div class="swap-history-header">
                                            <h4 class="swap-history-title">
                                                <?php 
                                                if ($is_initiator) {
                                                    echo sprintf(__('Swap with %s', 'vortex-ai-marketplace'), esc_html($recipient->display_name));
                                                } else {
                                                    echo sprintf(__('Swap with %s', 'vortex-ai-marketplace'), esc_html($initiator->display_name));
                                                }
                                                ?>
                                            </h4>
                                            <span class="swap-date"><?php echo date_i18n(get_option('date_format'), strtotime($completed_date)); ?></span>
                                        </div>
                                        
                                        <div class="swap-history-artworks">
                                            <div class="swap-artwork your-artwork">
                                                <div class="artwork-image">
                                                    <?php echo get_the_post_thumbnail($user_artwork->ID, 'thumbnail'); ?>
                                                </div>
                                                <div class="artwork-info">
                                                    <h5><?php echo esc_html($user_artwork->post_title); ?></h5>
                                                    <p class="artwork-owner"><?php _e('You Traded', 'vortex-ai-marketplace'); ?></p>
                                                </div>
                                            </div>
                                            
                                            <div class="swap-direction">
                                                <span class="dashicons dashicons-arrow-right-alt"></span>
                                                <span class="dashicons dashicons-arrow-left-alt"></span>
                                            </div>
                                            
                                            <div class="swap-artwork other-artwork">
                                                <div class="artwork-image">
                                                    <?php echo get_the_post_thumbnail($other_artwork->ID, 'thumbnail'); ?>
                                                </div>
                                                <div class="artwork-info">
                                                    <h5><?php echo esc_html($other_artwork->post_title); ?></h5>
                                                    <p class="artwork-owner"><?php echo sprintf(__('Received from %s', 'vortex-ai-marketplace'), esc_html($other_user->display_name)); ?></p>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="swap-transaction-info">
                                            <p class="transaction-label"><?php _e('Blockchain Transaction:', 'vortex-ai-marketplace'); ?></p>
                                            <a href="<?php echo esc_url($transaction_url); ?>" target="_blank" class="transaction-link">
                                                <span class="transaction-id"><?php echo esc_html(substr($transaction_hash, 0, 10) . '...' . substr($transaction_hash, -8)); ?></span>
                                                <span class="dashicons dashicons-external"></span>
                                            </a>
                                        </div>
                                        
                                        <div class="swap-history-actions">
                                            <button class="button view-details-button" data-swap-id="<?php echo esc_attr($swap->ID); ?>">
                                                <?php _e('View Details', 'vortex-ai-marketplace'); ?>
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <!-- Swap Details Modal -->
                        <div id="swap-details-modal" class="vortex-modal" style="display: none;">
                            <div class="modal-content">
                                <span class="close-modal">&times;</span>
                                <div class="swap-details-container">
                                    <h3><?php _e('Swap Transaction Details', 'vortex-ai-marketplace'); ?></h3>
                                    
                                    <div class="swap-details-content">
                                        <!-- Will be populated dynamically -->
                                    </div>
                                    
                                    <div class="blockchain-details">
                                        <h4><?php _e('Blockchain Transaction', 'vortex-ai-marketplace'); ?></h4>
                                        <div class="blockchain-info">
                                            <div class="transaction-hash-container">
                                                <span class="label"><?php _e('Transaction Hash:', 'vortex-ai-marketplace'); ?></span>
                                                <span class="transaction-hash-value"></span>
                                            </div>
                                            <div class="transaction-date-container">
                                                <span class="label"><?php _e('Timestamp:', 'vortex-ai-marketplace'); ?></span>
                                                <span class="transaction-date-value"></span>
                                            </div>
                                            <div class="transaction-network-container">
                                                <span class="label"><?php _e('Network:', 'vortex-ai-marketplace'); ?></span>
                                                <span class="network-badge tola-badge">Tola Network</span>
                                            </div>
                                            <div class="transaction-link-container">
                                                <a href="#" class="transaction-explorer-link" target="_blank">
                                                    <?php _e('View on Blockchain Explorer', 'vortex-ai-marketplace'); ?>
                                                    <span class="dashicons dashicons-external"></span>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="tola-rewards">
                                        <h4><?php _e('TOLA Rewards', 'vortex-ai-marketplace'); ?></h4>
                                        <div class="reward-info">
                                            <span class="tola-icon"></span>
                                            <span class="tola-amount">+100 TOLA</span>
                                            <span class="reward-description"><?php _e('Earned for completing this artwork swap', 'vortex-ai-marketplace'); ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div> 