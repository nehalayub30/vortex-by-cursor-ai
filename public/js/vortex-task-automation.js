/**
 * Task Automation functionality
 */
(function($) {
    'use strict';
    
    // Initialize when document is ready
    $(document).ready(function() {
        // Show/hide task parameter fields based on selected task type
        $('#task_type').on('change', function() {
            var taskType = $(this).val();
            $('.vortex-task-params').hide();
            $('#params-' + taskType).show();
        });
        
        // Initialize task type change to show correct parameters
        $('#task_type').trigger('change');
        
        // Handle task creation form submission
        $('.vortex-create-task-form').on('submit', function(e) {
            e.preventDefault();
            
            var form = $(this);
            var message = form.find('.vortex-form-message');
            var submitBtn = form.find('button[type="submit"]');
            
            // Get task parameters based on task type
            var taskType = form.find('#task_type').val();
            var taskParams = {};
            
            switch (taskType) {
                case 'artwork_generation':
                    taskParams.prompt = form.find('#artwork_prompt').val();
                    taskParams.style = form.find('#artwork_style').val();
                    break;
                case 'market_analysis':
                    taskParams.market = form.find('#market_type').val();
                    taskParams.timeframe = form.find('#market_timeframe').val();
                    break;
                case 'strategy_recommendation':
                    taskParams.industry = form.find('#strategy_industry').val();
                    taskParams.focus = form.find('#strategy_focus').val();
                    break;
            }
            
            // Show loading message
            message.html('<div class="vortex-loading">Creating task...</div>').show();
            submitBtn.prop('disabled', true);
            
            // Send AJAX request
            $.ajax({
                url: vortex_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'vortex_create_automation_task',
                    task_name: form.find('#task_name').val(),
                    task_type: taskType,
                    task_params: JSON.stringify(taskParams),
                    frequency: form.find('#task_frequency').val(),
                    nonce: form.find('[name="automation_nonce"]').val()
                },
                success: function(response) {
                    if (response.success) {
                        message.html('<div class="vortex-notice vortex-notice-success">Task created successfully!</div>');
                        
                        // Clear form
                        form.find('#task_name').val('');
                        form.find('#artwork_prompt').val('');
                        
                        // Reload task list
                        reloadTasksList();
                    } else {
                        message.html('<div class="vortex-notice vortex-notice-error">' + response.data.message + '</div>');
                    }
                    submitBtn.prop('disabled', false);
                },
                error: function() {
                    message.html('<div class="vortex-notice vortex-notice-error">Error creating task. Please try again.</div>');
                    submitBtn.prop('disabled', false);
                }
            });
        });
        
        // Handle task toggle
        $(document).on('click', '.vortex-toggle-task', function() {
            var button = $(this);
            var taskId = button.data('id');
            var active = button.data('active') === 1 ? 0 : 1;
            var taskItem = button.closest('.vortex-task-item');
            
            // Disable button during request
            button.prop('disabled', true);
            
            // Send AJAX request
            $.ajax({
                url: vortex_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'vortex_toggle_automation_task',
                    task_id: taskId,
                    active: active,
                    nonce: $('.vortex-create-task-form [name="automation_nonce"]').val()
                },
                success: function(response) {
                    if (response.success) {
                        // Update button and status
                        button.data('active', active);
                        button.text(active ? 'Deactivate' : 'Activate');
                        
                        var statusSpan = taskItem.find('.vortex-task-status');
                        statusSpan.removeClass('vortex-active vortex-inactive')
                                 .addClass(active ? 'vortex-active' : 'vortex-inactive')
                                 .text(active ? 'Active' : 'Inactive');
                        
                        // Update next run time
                        if (response.data.next_run) {
                            taskItem.find('.vortex-task-next-run').html(
                                '<strong>Next Run:</strong> ' + response.data.next_run
                            );
                        }
                    } else {
                        alert(response.data.message);
                    }
                    button.prop('disabled', false);
                },
                error: function() {
                    alert('Error updating task. Please try again.');
                    button.prop('disabled', false);
                }
            });
        });
        
        /**
         * Reload tasks list via AJAX
         */
        function reloadTasksList() {
            var container = $('.vortex-existing-tasks-section');
            
            $.ajax({
                url: vortex_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'vortex_get_automation_tasks',
                    nonce: $('.vortex-create-task-form [name="automation_nonce"]').val()
                },
                success: function(response) {
                    if (response.success) {
                        if (response.data.tasks && response.data.tasks.length > 0) {
                            container.find('.vortex-no-tasks-message').hide();
                            container.find('.vortex-tasks-list').html(response.data.html);
                        } else {
                            container.find('.vortex-no-tasks-message').show();
                            container.find('.vortex-tasks-list').empty();
                        }
                    }
                }
            });
        }
    });
    
})(jQuery); 