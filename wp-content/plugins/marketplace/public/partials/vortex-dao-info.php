<?php
/**
 * Template for DAO Information
 *
 * Displays information about the VORTEX DAO structure, royalties, and fees
 *
 * @link       https://vortexmarketplace.io
 * @since      1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

?>
<div class="vortex-dao-info">
    <?php if ($atts['show_token'] === 'true'): ?>
    <div class="vortex-dao-section token-info">
        <h3>TOLA Token Integration</h3>
        <div class="token-details">
            <div class="token-badge">
                <img src="<?php echo VORTEX_PLUGIN_URL; ?>assets/images/tola-icon.png" alt="TOLA" class="token-icon">
                <div class="token-name">
                    <span class="symbol"><?php echo esc_html($config['token']['symbol']); ?></span>
                    <span class="name"><?php echo esc_html($config['token']['name']); ?></span>
                </div>
            </div>
            <div class="token-address">
                <span class="label">Token Address:</span>
                <span class="value"><?php echo esc_html($config['token']['address']); ?></span>
                <a href="https://explorer.solana.com/address/<?php echo esc_attr($config['token']['address']); ?>" target="_blank" class="explorer-link">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
                </a>
            </div>
            <div class="blockchain-info">
                <span class="label">Network:</span>
                <span class="value"><?php echo esc_html(ucfirst($config['token']['blockchain'])); ?></span>
            </div>
        </div>
        
        <div class="token-utility">
            <h4>TOLA Utility in the VORTEX Ecosystem</h4>
            <ul class="utility-list">
                <li>
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                    <span>Governance voting power for DAO decisions</span>
                </li>
                <li>
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="7"></circle><polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"></polyline></svg>
                    <span>Artist incentives and rewards</span>
                </li>
                <li>
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path></svg>
                    <span>Treasury funding for community grants</span>
                </li>
                <li>
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                    <span>Community engagement rewards</span>
                </li>
            </ul>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if ($atts['show_royalties'] === 'true'): ?>
    <div class="vortex-dao-section royalty-info">
        <h3>Royalty Structure</h3>
        
        <div class="royalty-cap-info">
            <div class="cap-badge">
                <span class="cap-value"><?php echo esc_html($config['royalties']['cap']); ?>%</span>
                <span class="cap-label">Total Royalty Cap</span>
            </div>
            <p class="cap-description">Smart contracts enforce a maximum total royalty of <?php echo esc_html($config['royalties']['cap']); ?>% per artwork transaction.</p>
        </div>
        
        <div class="royalty-distribution">
            <h4>Royalty Distribution</h4>
            <div class="royalty-grid">
                <div class="royalty-item artist">
                    <div class="royalty-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                    </div>
                    <div class="royalty-label">Artist Royalty</div>
                    <div class="royalty-value">Up to <?php echo esc_html($config['royalties']['artist_max']); ?>%</div>
                    <div class="royalty-description">Artist chooses (0%–<?php echo esc_html($config['royalties']['artist_max']); ?>%) when listing artwork</div>
                </div>
                
                <div class="royalty-item creator">
                    <div class="royalty-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path></svg>
                    </div>
                    <div class="royalty-label">VORTEX Creator Royalty</div>
                    <div class="royalty-value">Fixed <?php echo esc_html($config['royalties']['vortex_creator']); ?>%</div>
                    <div class="royalty-description">Always goes to Marianne Nems (IP creator of AI & system)</div>
                </div>
            </div>
        </div>
        
        <div class="royalty-examples">
            <h4>Example Scenarios</h4>
            
            <div class="example-tabs">
                <button class="example-tab active" data-tab="first-sale">First-time Sale</button>
                <button class="example-tab" data-tab="resale">Resale</button>
            </div>
            
            <div class="example-content">
                <div class="example-pane active" id="first-sale">
                    <h5>First-time Sale ($1,000)</h5>
                    <div class="example-breakdown">
                        <div class="breakdown-item">
                            <span class="item-label">15% Marketplace commission</span>
                            <span class="item-value">$150</span>
                        </div>
                        <div class="breakdown-item">
                            <span class="item-label">5% Creator royalty (Marianne Nems)</span>
                            <span class="item-value">$50</span>
                        </div>
                        <div class="breakdown-item">
                            <span class="item-label">Artist receives (as creator and seller)</span>
                            <span class="item-value">$850</span>
                        </div>
                        <div class="breakdown-note">
                            <small>* Artist royalty is included in the sale price for first-time sales</small>
                        </div>
                    </div>
                </div>
                
                <div class="example-pane" id="resale">
                    <h5>Resale ($1,000) with 10% Artist Royalty</h5>
                    <div class="example-breakdown">
                        <div class="breakdown-item">
                            <span class="item-label">15% Marketplace commission</span>
                            <span class="item-value">$150</span>
                        </div>
                        <div class="breakdown-item">
                            <span class="item-label">10% Artist royalty</span>
                            <span class="item-value">$100</span>
                        </div>
                        <div class="breakdown-item">
                            <span class="item-label">5% Creator royalty (Marianne Nems)</span>
                            <span class="item-value">$50</span>
                        </div>
                        <div class="breakdown-item">
                            <span class="item-label">Seller receives</span>
                            <span class="item-value">$700</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if ($atts['show_revenue'] === 'true'): ?>
    <div class="vortex-dao-section revenue-info">
        <h3>Revenue & Fee Structure</h3>
        
        <div class="revenue-channels">
            <h4>Revenue Channels</h4>
            <div class="channel-grid">
                <div class="channel-item">
                    <div class="channel-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"></path></svg>
                    </div>
                    <div class="channel-label">Swap Fee</div>
                    <div class="channel-value">$<?php echo esc_html($config['fees']['swap_fee_per_artist']); ?> per artist</div>
                    <div class="channel-recipient">VORTEX Inc.</div>
                </div>
                
                <div class="channel-item">
                    <div class="channel-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                    </div>
                    <div class="channel-label">Artwork Purchase</div>
                    <div class="channel-value">$<?php echo esc_html($config['fees']['artwork_purchase_fee']); ?> one-time fee</div>
                    <div class="channel-recipient">VORTEX Inc.</div>
                </div>
                
                <div class="channel-item">
                    <div class="channel-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path><line x1="3" y1="6" x2="21" y2="6"></line><path d="M16 10a4 4 0 0 1-8 0"></path></svg>
                    </div>
                    <div class="channel-label">Marketplace Commission</div>
                    <div class="channel-value"><?php echo esc_html($config['revenue']['marketplace_commission']); ?>% of total sale price</div>
                    <div class="channel-recipient">Split between VORTEX Inc. & DAO</div>
                </div>
                
                <div class="channel-item">
                    <div class="channel-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 13a5 5 0 1 0 0-10 5 5 0 0 0 0 10z"></path><path d="M19 21v-2a7 7 0 0 0-14 0v2"></path></svg>
                    </div>
                    <div class="channel-label">User Tiers</div>
                    <div class="channel-value">SaaS-style subscription</div>
                    <div class="channel-recipient">VORTEX Inc.</div>
                </div>
            </div>
        </div>
        
        <div class="commission-breakdown">
            <h4>Marketplace Commission Breakdown (<?php echo esc_html($config['revenue']['marketplace_commission']); ?>%)</h4>
            
            <div class="commission-chart">
                <div class="chart-segment creator" style="width: <?php echo esc_attr($config['revenue']['creator_allocation']); ?>%;">
                    <span><?php echo esc_html($config['revenue']['creator_allocation']); ?>%</span>
                </div>
                <div class="chart-segment admin" style="width: <?php echo esc_attr($config['revenue']['admin_allocation']); ?>%;">
                    <span><?php echo esc_html($config['revenue']['admin_allocation']); ?>%</span>
                </div>
                <div class="chart-segment dao" style="width: <?php echo esc_attr($config['revenue']['dao_allocation']); ?>%;">
                    <span><?php echo esc_html($config['revenue']['dao_allocation']); ?>%</span>
                </div>
            </div>
            
            <div class="commission-legend">
                <div class="legend-item creator">
                    <span class="legend-color"></span>
                    <span class="legend-label">Creator (Marianne Nems)</span>
                    <span class="legend-value"><?php echo esc_html($config['revenue']['creator_allocation']); ?>%</span>
                </div>
                <div class="legend-item admin">
                    <span class="legend-color"></span>
                    <span class="legend-label">VORTEX Inc.</span>
                    <span class="legend-value"><?php echo esc_html($config['revenue']['admin_allocation']); ?>%</span>
                </div>
                <div class="legend-item dao">
                    <span class="legend-color"></span>
                    <span class="legend-label">DAO Treasury</span>
                    <span class="legend-value"><?php echo esc_html($config['revenue']['dao_allocation']); ?>%</span>
                </div>
            </div>
        </div>
        
        <div class="dao-allocation">
            <h4>DAO Treasury Allocation</h4>
            <div class="allocation-grid">
                <div class="allocation-item">
                    <div class="allocation-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path></svg>
                    </div>
                    <div class="allocation-label">Artist Grants</div>
                    <div class="allocation-value"><?php echo esc_html($config['treasury']['grants']); ?>%</div>
                </div>
                
                <div class="allocation-item">
                    <div class="allocation-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                    </div>
                    <div class="allocation-label">Community Exhibitions</div>
                    <div class="allocation-value"><?php echo esc_html($config['treasury']['exhibitions']); ?>%</div>
                </div>
                
                <div class="allocation-item">
                    <div class="allocation-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path><line x1="7" y1="7" x2="7.01" y2="7"></line></svg>
                    </div>
                    <div class="allocation-label">Artist Support</div>
                    <div class="allocation-value"><?php echo esc_html($config['treasury']['artist_support']); ?>%</div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="vortex-dao-section governance-model">
        <h3>VORTEX Hybrid Model</h3>
        <div class="model-diagram">
            <div class="model-section dao">
                <div class="section-header">
                    <div class="section-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                    </div>
                    <h4>The DAO</h4>
                </div>
                <div class="section-content">
                    <p class="section-description">Governance & Cultural Stewardship</p>
                    <ul class="section-responsibilities">
                        <li>Grants for artists</li>
                        <li>Curation of featured works</li>
                        <li>Voting on community proposals</li>
                        <li>Treasury management</li>
                    </ul>
                    <div class="section-source">
                        <strong>Revenue Source:</strong> <?php echo esc_html($config['revenue']['dao_allocation']); ?>% from marketplace commissions
                    </div>
                </div>
            </div>
            
            <div class="model-section company">
                <div class="section-header">
                    <div class="section-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                    </div>
                    <h4>VORTEX Inc.</h4>
                </div>
                <div class="section-content">
                    <p class="section-description">For-Profit Operations & Ownership</p>
                    <ul class="section-responsibilities">
                        <li>Plugin development & AI maintenance</li>
                        <li>Hosting costs, scaling</li>
                        <li>Strategic partnerships</li>
                        <li>Profit reinvestment</li>
                    </ul>
                    <div class="section-source">
                        <strong>Revenue Sources:</strong>
                        <ul>
                            <li>Transaction fees (swaps & sales)</li>
                            <li>Marketplace commission (<?php echo esc_html($config['revenue']['admin_allocation']); ?>%)</li>
                            <li>SaaS plans / user tiers</li>
                            <li>TOLA token sales</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Example tab functionality
    $('.example-tab').on('click', function() {
        const tabId = $(this).data('tab');
        
        // Remove active class from all tabs and content
        $('.example-tab').removeClass('active');
        $('.example-pane').removeClass('active');
        
        // Add active class to clicked tab and corresponding content
        $(this).addClass('active');
        $('#' + tabId).addClass('active');
    });
});
</script> 