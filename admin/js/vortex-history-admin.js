jQuery(document).ready(function($) {
    // Initialize datepickers
    $('.datepicker').datepicker({
        dateFormat: 'yy-mm-dd',
        maxDate: '0'
    });
    
    // Tab navigation
    $('.nav-tab').on('click', function(e) {
        e.preventDefault();
        var target = $(this).attr('href').replace('#', '');
        
        // Update active tab
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        
        // Show target tab content
        $('.tab-content').hide();
        $('#' + target + '-tab').show();
    });
    
    // Load history data
    function loadHistoryData(page = 1) {
        var filters = {
            user_id: $('#user-filter').val(),
            action_type: $('#action-type').val(),
            date_from: $('#date-from').val(),
            date_to: $('#date-to').val(),
            page: page,
            per_page: 20,
            nonce: vortexHistoryData.nonce
        };
        
        $('#history-table tbody').html('<tr class="no-items"><td colspan="5">Loading history data...</td></tr>');
        
        $.ajax({
            url: vortexHistoryData.ajax_url,
            type: 'POST',
            data: {
                action: 'vortex_get_history_data',
                ...filters
            },
            success: function(response) {
                if (response.success) {
                    displayHistoryData(response.data);
                    updatePagination(response.data.current_page, response.data.total_pages);
                } else {
                    $('#history-table tbody').html('<tr class="no-items"><td colspan="5">Error: ' + response.data.message + '</td></tr>');
                }
            },
            error: function() {
                $('#history-table tbody').html('<tr class="no-items"><td colspan="5">Error loading history data. Please try again.</td></tr>');
            }
        });
    }
    
    // Display history data
    function displayHistoryData(data) {
        var tbody = $('#history-table tbody');
        tbody.empty();
        
        if (data.data.length === 0) {
            tbody.html('<tr class="no-items"><td colspan="5">No history records found.</td></tr>');
            return;
        }
        
        $.each(data.data, function(index, record) {
            var row = $('<tr></tr>');
            row.append('<td>' + record.date + '</td>');
            row.append('<td>' + record.user + '</td>');
            row.append('<td>' + record.action + '</td>');
            row.append('<td>' + record.item + '</td>');
            row.append('<td>' + record.details + '</td>');
            tbody.append(row);
        });
    }
    
    // Update pagination
    function updatePagination(currentPage, totalPages) {
        var pagination = $('.tablenav-pages');
        
        // Update page numbers
        $('.current-page').text(currentPage);
        $('.total-pages').text('/ ' + totalPages);
        
        // Update pagination buttons
        if (currentPage <= 1) {
            $('.tablenav-pages-navspan').addClass('disabled');
            $('.prev-page, .first-page').addClass('disabled').attr('aria-hidden', 'true');
        } else {
            $('.tablenav-pages-navspan').removeClass('disabled');
            $('.prev-page, .first-page').removeClass('disabled').removeAttr('aria-hidden');
        }
        
        if (currentPage >= totalPages) {
            $('.next-page, .last-page').addClass('disabled').attr('aria-hidden', 'true');
        } else {
            $('.next-page, .last-page').removeClass('disabled').removeAttr('aria-hidden');
        }
        
        // Set data attributes for pagination
        pagination.attr('data-current', currentPage);
        pagination.attr('data-total', totalPages);
    }
    
    // Handle pagination clicks
    $(document).on('click', '.next-page:not(.disabled)', function(e) {
        e.preventDefault();
        var currentPage = parseInt($('.tablenav-pages').attr('data-current'));
        loadHistoryData(currentPage + 1);
    });
    
    $(document).on('click', '.prev-page:not(.disabled)', function(e) {
        e.preventDefault();
        var currentPage = parseInt($('.tablenav-pages').attr('data-current'));
        loadHistoryData(currentPage - 1);
    });
    
    $(document).on('click', '.last-page:not(.disabled)', function(e) {
        e.preventDefault();
        var totalPages = parseInt($('.tablenav-pages').attr('data-total'));
        loadHistoryData(totalPages);
    });
    
    $(document).on('click', '.first-page:not(.disabled)', function(e) {
        e.preventDefault();
        loadHistoryData(1);
    });
    
    // Apply filters
    $('#apply-filters').on('click', function() {
        loadHistoryData(1);
    });
    
    // Reset filters
    $('#reset-filters').on('click', function() {
        $('#user-filter').val('');
        $('#action-type').val('');
        $('#date-from').val('');
        $('#date-to').val('');
        loadHistoryData(1);
    });
    
    // Export to CSV
    $('#export-csv').on('click', function() {
        var filters = {
            user_id: $('#user-filter').val(),
            action_type: $('#action-type').val(),
            date_from: $('#date-from').val(),
            date_to: $('#date-to').val(),
            nonce: vortexHistoryData.nonce
        };
        
        $(this).text('Exporting...').prop('disabled', true);
        
        $.ajax({
            url: vortexHistoryData.ajax_url,
            type: 'POST',
            data: {
                action: 'vortex_export_history',
                ...filters
            },
            success: function(response) {
                $('#export-csv').text('Export to CSV').prop('disabled', false);
                
                if (response.success) {
                    // Create a download link
                    var blob = new Blob([response.data.data], { type: 'text/csv' });
                    var link = document.createElement('a');
                    link.href = window.URL.createObjectURL(blob);
                    link.download = response.data.filename;
                    link.click();
                } else {
                    alert('Error: ' + response.data.message);
                }
            },
            error: function() {
                $('#export-csv').text('Export to CSV').prop('disabled', false);
                alert('Error exporting data. Please try again.');
            }
        });
    });
    
    // Manual cleanup
    $('#manual-cleanup').on('click', function() {
        if (!confirm('Are you sure you want to delete history records older than the retention period?')) {
            return;
        }
        
        $(this).text('Running Cleanup...').prop('disabled', true);
        
        $.ajax({
            url: vortexHistoryData.ajax_url,
            type: 'POST',
            data: {
                action: 'vortex_manual_cleanup',
                nonce: vortexHistoryData.nonce
            },
            success: function(response) {
                $('#manual-cleanup').text('Run Cleanup Now').prop('disabled', false);
                
                if (response.success) {
                    $('#cleanup-results').html('<div class="notice notice-success"><p>' + response.data.message + '</p></div>');
                    loadHistoryData(1); // Reload history data
                } else {
                    $('#cleanup-results').html('<div class="notice notice-error"><p>Error: ' + response.data.message + '</p></div>');
                }
            },
            error: function() {
                $('#manual-cleanup').text('Run Cleanup Now').prop('disabled', false);
                $('#cleanup-results').html('<div class="notice notice-error"><p>Error running cleanup. Please try again.</p></div>');
            }
        });
    });
    
    // Load initial data
    loadHistoryData();
}); 