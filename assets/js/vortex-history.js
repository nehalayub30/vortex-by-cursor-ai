jQuery(document).ready(function($) {
    // Initialize datepickers with localization
    if (typeof $.datepicker !== 'undefined') {
        $('.datepicker').datepicker({
            dateFormat: 'yy-mm-dd',
            maxDate: '0',
            changeMonth: true,
            changeYear: true
        });
    }
    
    // Form submission handling
    $('.vortex-filter-form').on('submit', function() {
        // Validate date ranges if both are provided
        var dateFrom = $('#date-from').val();
        var dateTo = $('#date-to').val();
        
        if (dateFrom && dateTo) {
            var fromDate = new Date(dateFrom);
            var toDate = new Date(dateTo);
            
            if (fromDate > toDate) {
                alert(vortexHistoryData.date_error_message);
                return false;
            }
        }
        
        return true;
    });
}); 