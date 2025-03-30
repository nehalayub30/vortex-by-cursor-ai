<div class="wrap vortex-thorius-test">
    <h1><?php _e('Thorius AI Integration Test', 'vortex-ai-marketplace'); ?></h1>
    
    <p class="description"><?php _e('This page tests all components of Thorius AI to ensure they are functioning correctly.', 'vortex-ai-marketplace'); ?></p>
    
    <div class="test-controls">
        <button id="run-integration-test" class="button button-primary"><?php _e('Run Integration Test', 'vortex-ai-marketplace'); ?></button>
        <span class="spinner" style="float: none; margin-top: 0; margin-left: 10px;"></span>
    </div>
    
    <div class="test-results-container" style="display: none;">
        <h2><?php _e('Test Results', 'vortex-ai-marketplace'); ?></h2>
        
        <div class="test-summary">
            <div class="test-status"></div>
            <div class="test-message"></div>
            <div class="test-timestamp"><?php _e('Tested at:', 'vortex-ai-marketplace'); ?> <span></span></div>
        </div>
        
        <h3><?php _e('Detailed Results', 'vortex-ai-marketplace'); ?></h3>
        
        <div class="test-details">
            <div class="test-section api-connections">
                <h4><?php _e('API Connections', 'vortex-ai-marketplace'); ?></h4>
                <div class="test-result"></div>
                <div class="test-details-container"></div>
            </div>
            
            <div class="test-section agent-tests">
                <h4><?php _e('AI Agents', 'vortex-ai-marketplace'); ?></h4>
                <div class="agent-cloe">
                    <h5><?php _e('CLOE', 'vortex-ai-marketplace'); ?></h5>
                    <div class="test-result"></div>
                    <div class="test-details-container"></div>
                </div>
                <div class="agent-huraii">
                    <h5><?php _e('HURAII', 'vortex-ai-marketplace'); ?></h5>
                    <div class="test-result"></div>
                    <div class="test-details-container"></div>
                </div>
                <div class="agent-strategist">
                    <h5><?php _e('Business Strategist', 'vortex-ai-marketplace'); ?></h5>
                    <div class="test-result"></div>
                    <div class="test-details-container"></div>
                </div>
            </div>
            
            <div class="test-section database-tables">
                <h4><?php _e('Database Tables', 'vortex-ai-marketplace'); ?></h4>
                <div class="test-result"></div>
                <div class="test-details-container"></div>
            </div>
            
            <div class="test-section admin-features">
                <h4><?php _e('Admin Features', 'vortex-ai-marketplace'); ?></h4>
                <div class="test-result"></div>
                <div class="test-details-container"></div>
            </div>
            
            <div class="test-section shortcodes">
                <h4><?php _e('Shortcodes', 'vortex-ai-marketplace'); ?></h4>
                <div class="test-result"></div>
                <div class="test-details-container"></div>
            </div>
            
            <div class="test-section intelligence">
                <h4><?php _e('Intelligence Features', 'vortex-ai-marketplace'); ?></h4>
                <div class="test-result"></div>
                <div class="test-details-container"></div>
            </div>
        </div>
    </div>
</div>

<script>
    jQuery(document).ready(function($) {
        $('#run-integration-test').on('click', function() {
            var $button = $(this);
            var $spinner = $('.test-controls .spinner');
            var $results = $('.test-results-container');
            
            // Disable button and show spinner
            $button.prop('disabled', true);
            $spinner.addClass('is-active');
            
            // Make AJAX request
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'vortex_thorius_run_integration_test',
                    nonce: '<?php echo wp_create_nonce('vortex_thorius_test_nonce'); ?>'
                },
                success: function(response) {
                    // Update UI with results
                    if (response.success) {
                        updateTestResults(response.data);
                        $results.show();
                    } else {
                        alert('<?php _e('Error running tests:', 'vortex-ai-marketplace'); ?> ' + response.data.message);
                    }
                },
                error: function() {
                    alert('<?php _e('Error connecting to server.', 'vortex-ai-marketplace'); ?>');
                },
                complete: function() {
                    // Enable button and hide spinner
                    $button.prop('disabled', false);
                    $spinner.removeClass('is-active');
                }
            });
        });
        
        function updateTestResults(results) {
            // Update summary
            $('.test-summary .test-status')
                .removeClass('success error')
                .addClass(results.success ? 'success' : 'error')
                .html(results.success ? 
                    '<span class="dashicons dashicons-yes"></span> <?php _e('All tests passed', 'vortex-ai-marketplace'); ?>' : 
                    '<span class="dashicons dashicons-no"></span> <?php _e('Some tests failed', 'vortex-ai-marketplace'); ?>');
                
            $('.test-timestamp span').text(new Date().toLocaleString());
            
            // Update detailed results
            for (var key in results.tests) {
                var test = results.tests[key];
                var $section = $('.test-section.' + key);
                
                if (!$section.length && key.indexOf('agent_') === 0) {
                    var agent = key.replace('agent_', '');
                    $section = $('.agent-' + agent);
                }
                
                if ($section.length) {
                    $section.find('.test-result')
                        .removeClass('success error')
                        .addClass(test.success ? 'success' : 'error')
                        .html(test.message);
                    
                    var $details = $section.find('.test-details-container').empty();
                    
                    if (test.details) {
                        var $table = $('<table class="widefat fixed striped"></table>');
                        var $thead = $('<thead><tr><th><?php _e('Test', 'vortex-ai-marketplace'); ?></th><th><?php _e('Result', 'vortex-ai-marketplace'); ?></th></tr></thead>');
                        var $tbody = $('<tbody></tbody>');
                        
                        $table.append($thead).append($tbody);
                        
                        for (var detailKey in test.details) {
                            var detail = test.details[detailKey];
                            
                            var $row = $('<tr></tr>');
                            var statusIcon = detail.success ? 
                                '<span class="dashicons dashicons-yes success"></span>' : 
                                '<span class="dashicons dashicons-no error"></span>';
                            
                            $row.append('<td>' + detailKey + '</td>');
                            $row.append('<td>' + statusIcon + ' ' + detail.message + '</td>');
                            
                            $tbody.append($row);
                        }
                        
                        $details.append($table);
                    }
                }
            }
        }
    });
</script>

<style>
    .vortex-thorius-test .test-controls {
        margin: 20px 0;
    }
    
    .vortex-thorius-test .test-results-container {
        margin-top: 30px;
    }
    
    .vortex-thorius-test .test-summary {
        background: #fff;
        padding: 15px;
        border-radius: 4px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        margin-bottom: 20px;
    }
    
    .vortex-thorius-test .test-status {
        font-size: 16px;
        font-weight: bold;
        margin-bottom: 10px;
    }
    
    .vortex-thorius-test .test-timestamp {
        color: #666;
        font-size: 12px;
        margin-top: 10px;
    }
    
    .vortex-thorius-test .test-section {
        background: #fff;
        padding: 15px;
        border-radius: 4px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        margin-bottom: 20px;
    }
    
    .vortex-thorius-test .test-section h4 {
        margin-top: 0;
        border-bottom: 1px solid #eee;
        padding-bottom: 10px;
    }
    
    .vortex-thorius-test .test-result {
        padding: 8px;
        margin: 10px 0;
        border-radius: 4px;
    }
    
    .vortex-thorius-test .success {
        color: #2a6a12;
    }
    
    .vortex-thorius-test .error {
        color: #b32d2e;
    }
    
    .vortex-thorius-test .test-result.success {
        background-color: #f0fff2;
    }
    
    .vortex-thorius-test .test-result.error {
        background-color: #fff0f0;
    }
    
    .vortex-thorius-test .agent-cloe,
    .vortex-thorius-test .agent-huraii,
    .vortex-thorius-test .agent-strategist {
        margin-bottom: 15px;
        border-left: 3px solid #eee;
        padding-left: 15px;
    }
    
    .vortex-thorius-test .test-details-container {
        margin-top: 10px;
    }
    
    .vortex-thorius-test .test-details-container table {
        border-collapse: collapse;
        width: 100%;
    }
</style> 