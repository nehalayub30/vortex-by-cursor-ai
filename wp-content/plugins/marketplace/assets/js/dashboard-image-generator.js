/**
 * Dashboard Widget JavaScript for HURAII Image Generator
 */
(function($) {
    'use strict';
    
    $(document).ready(function() {
        var $widget = $('.vortex-dashboard-image-generator');
        var $quickGenerate = $widget.find('.quick-generate');
        var $resultsContainer = $widget.find('.results-container');
        var $loadingContainer = $widget.find('.loading-container');
        var $resultsGrid = $widget.find('.results-grid');
        
        // Generate button
        $widget.find('.generate-button').on('click', function() {
            var prompt = $widget.find('textarea').val().trim();
            var style = $widget.find('.style-select').val();
            
            if (!prompt) {
                alert('Please enter a prompt to generate an image');
                return;
            }
            
            // Show loading
            $quickGenerate.hide();
            $loadingContainer.show();
            
            // Generate images
            $.ajax({
                url: vortexDashboardImageGenerator.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'vortex_generate_image',
                    nonce: vortexDashboardImageGenerator.nonce,
                    prompt: prompt,
                    num_variations: 2,
                    width: 512,
                    height: 512,
                    style: style
                },
                success: function(response) {
                    $loadingContainer.hide();
                    
                    if (response.success) {
                        displayResults(response.data);
                    } else {
                        alert(vortexDashboardImageGenerator.i18n.error + ' ' + response.data.message);
                        $quickGenerate.show();
                    }
                },
                error: function(xhr, status, error) {
                    $loadingContainer.hide();
                    $quickGenerate.show();
                    alert(vortexDashboardImageGenerator.i18n.error + ' ' + error);
                }
            });
        });
        
        // Back button
        $widget.find('.back-button').on('click', function() {
            $resultsContainer.hide();
            $quickGenerate.show();
        });
        
        // Display generated images
        function displayResults(data) {
            $resultsGrid.empty();
            
            // Add each image to the grid
            data.images.forEach(function(image) {
                var $img = $('<img src="' + image.url + '" alt="Generated image">');
                $img.on('click', function() {
                    window.open(image.url, '_blank');
                });
                $resultsGrid.append($img);
            });
            
            $resultsContainer.show();
        }
    });
    
})(jQuery); 