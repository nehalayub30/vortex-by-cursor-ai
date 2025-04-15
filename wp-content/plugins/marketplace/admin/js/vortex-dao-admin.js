/**
 * VORTEX DAO Admin JavaScript
 */
(function($) {
    'use strict';
    
    // Tab navigation
    function setupTabs() {
        // Show active tab content
        $('.vortex-admin-tabs .nav-tab').on('click', function(e) {
            e.preventDefault();
            
            const target = $(this).attr('href');
            
            // Update tabs
            $('.vortex-admin-tabs .nav-tab').removeClass('nav-tab-active');
            $(this).addClass('nav-tab-active');
            
            // Update content
            $('.vortex-admin-tab-content .tab-pane').removeClass('active');
            $(target).addClass('active');
        });
    }
    
    // Contract deployment wizard
    function setupDeploymentWizard() {
        $('#vortex-deploy-contracts').on('click', function() {
            // This would typically open a modal with a step-by-step wizard
            // For now, we'll just show a message
            const $status = $('#deployment-status');
            
            $status.html('<div class="notice notice-info"><p>Deployment wizard is not yet implemented. To deploy contracts, please use the provided Solidity files with Remix IDE or Truffle.</p></div>');
        });
    }
    
    // Initialize admin page
    $(document).ready(function() {
        setupTabs();
        setupDeploymentWizard();
    });
    
})(jQuery); 