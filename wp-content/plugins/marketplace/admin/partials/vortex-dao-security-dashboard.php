        // Add filters if set
        <?php if (!empty($event_type_filter)): ?>
        exportUrl += '&event_type=<?php echo urlencode($event_type_filter); ?>';
        <?php endif; ?>
        
        <?php if (!empty($start_date)): ?>
        exportUrl += '&start_date=<?php echo urlencode($start_date); ?>';
        <?php endif; ?>
        
        <?php if (!empty($end_date)): ?>
        exportUrl += '&end_date=<?php echo urlencode($end_date); ?>';
        <?php endif; ?>
        
        // Redirect to export URL
        window.location.href = exportUrl;
    });
    
    // Clear filters
    $('#clear-filters').on('click', function() {
        window.location.href = '<?php echo admin_url('admin.php?page=vortex-dao-security'); ?>';
    });
});
</script>

<!-- CSS for the Security Dashboard -->
<style>
.vortex-dao-security-dashboard {
    margin: 20px 0;
}

/* Overview Cards */
.overview-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 25px;
}

.card {
    background-color: #fff;
    border-radius: 6px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.card-header {
    padding: 15px 20px;
    background-color: #f8f9fa;
    border-bottom: 1px solid #e0e0e0;
}

.card-header h2 {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
}

.card-body {
    padding: 20px;
}

.card-footer {
    padding: 12px 20px;
    background-color: #f8f9fa;
    border-top: 1px solid #e0e0e0;
    text-align: center;
}

/* Health Score */
.health-score {
    text-align: center;
    margin-bottom: 20px;
}

.health-score .score {
    font-size: 36px;
    font-weight: 700;
    display: block;
}

.health-score .status {
    font-size: 14px;
    font-weight: 600;
    text-transform: uppercase;
}

.health-excellent {
    color: #4caf50;
}

.health-good {
    color: #8bc34a;
}

.health-warning {
    color: #ff9800;
}

.health-critical {
    color: #f44336;
}

.health-details {
    margin-top: 15px;
}

.detail-item {
    display: flex;
    justify-content: space-between;
    margin-bottom: 8px;
    padding-bottom: 8px;
    border-bottom: 1px solid #f0f0f0;
}

.detail-item:last-child {
    margin-bottom: 0;
    padding-bottom: 0;
    border-bottom: none;
}

.detail-item .label {
    color: #555;
}

.detail-item .value {
    font-weight: 600;
}

.detail-item .value.warning {
    color: #ff9800;
}

.detail-item .value.good {
    color: #4caf50;
}

/* Activity Stats */
.activity-stats {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
}

.stat-item {
    text-align: center;
    padding: 15px;
    background-color: #f8f9fa;
    border-radius: 6px;
}

.stat-value {
    font-size: 24px;
    font-weight: 700;
    color: #2c3e50;
    display: block;
    margin-bottom: 5px;
}

.stat-label {
    font-size: 13px;
    color: #555;
}

/* Status Items */
.status-items {
    display: flex;
    flex-direction: column;
}

.status-item {
    display: flex;
    justify-content: space-between;
    padding: 10px 0;
    border-bottom: 1px solid #f0f0f0;
}

.status-item:last-child {
    border-bottom: none;
}

.status-item .label {
    color: #555;
}

.status-item .value {
    font-weight: 600;
}

.status-item .value.highlight {
    color: #2196f3;
    font-weight: 700;
}

.status-item .value.enabled {
    color: #4caf50;
}

.status-item .value.disabled {
    color: #f44336;
}

/* Security Controls */
.security-controls {
    display: flex;
    justify-content: space-between;
    margin-bottom: 25px;
    padding: 15px;
    background-color: #fff;
    border-radius: 6px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.security-actions {
    display: flex;
    gap: 10px;
}

.security-filters {
    display: flex;
}

.security-filters form {
    display: flex;
    align-items: center;
    gap: 15px;
}

.filter-group {
    display: flex;
    flex-direction: column;
}

.filter-group label {
    font-size: 12px;
    margin-bottom: 5px;
    color: #555;
}

/* Tab Navigation */
.tabbed-content {
    background-color: #fff;
    border-radius: 6px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    margin-bottom: 25px;
}

.tab-navigation {
    display: flex;
    background-color: #f8f9fa;
    border-bottom: 1px solid #e0e0e0;
}

.tab-button {
    padding: 15px 20px;
    border: none;
    background: none;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
    color: #555;
    border-bottom: 3px solid transparent;
}

.tab-button:hover {
    background-color: #f1f1f1;
}

.tab-button.active {
    color: #2196f3;
    border-bottom-color: #2196f3;
}

.tab-content {
    display: none;
    padding: 20px;
}

.tab-content.active {
    display: block;
}

/* Event Type Styling */
.event-type {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 500;
}

.event-normal {
    background-color: #e3f2fd;
    color: #1976d2;
}

.event-warning {
    background-color: #fff3e0;
    color: #e65100;
}

.event-critical {
    background-color: #ffebee;
    color: #c62828;
}

/* Status Indicators */
.status-indicator {
    display: inline-block;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    margin-left: 8px;
}

.status-indicator.active {
    background-color: #4caf50;
}

.status-indicator.inactive {
    background-color: #f44336;
}

.status-signed {
    color: #4caf50;
    font-weight: 500;
}

.status-unsigned {
    color: #f44336;
    font-weight: 500;
}

/* Control Cards */
.control-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
}

.control-card {
    background-color: #fff;
    border-radius: 6px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.control-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px;
    background-color: #f8f9fa;
    border-bottom: 1px solid #e0e0e0;
}

.control-header h3 {
    margin: 0;
    font-size: 15px;
    font-weight: 600;
}

.control-body {
    padding: 15px;
    min-height: 120px;
}

.control-body p {
    margin: 0 0 12px 0;
    color: #555;
}

.control-body p:last-child {
    margin-bottom: 0;
}

.control-actions {
    padding: 10px 15px;
    background-color: #f8f9fa;
    border-top: 1px solid #e0e0e0;
    text-align: center;
}

/* Equity Chart */
.equity-chart {
    height: 30px;
    border-radius: 4px;
    overflow: hidden;
    display: flex;
    margin: 15px 0 8px 0;
}

.founder-equity {
    height: 100%;
    background-color: #3f51b5;
}

.investor-equity {
    height: 100%;
    background-color: #2196f3;
}

.team-equity {
    height: 100%;
    background-color: #4caf50;
}

.reserve-equity {
    height: 100%;
    background-color: #9e9e9e;
}

.equity-legend {
    display: flex;
    justify-content: space-between;
    font-size: 12px;
    margin-bottom: 10px;
}

.founder-legend:before,
.investor-legend:before,
.team-legend:before,
.reserve-legend:before {
    content: '';
    display: inline-block;
    width: 10px;
    height: 10px;
    margin-right: 5px;
    border-radius: 2px;
}

.founder-legend:before {
    background-color: #3f51b5;
}

.investor-legend:before {
    background-color: #2196f3;
}

.team-legend:before {
    background-color: #4caf50;
}

.reserve-legend:before {
    background-color: #9e9e9e;
}

/* Modal */
.vortex-modal {
    display: none;
    position: fixed;
    z-index: 100000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0, 0, 0, 0.5);
}

.vortex-modal-content {
    position: relative;
    background-color: #fff;
    margin: 10% auto;
    padding: 20px;
    border-radius: 6px;
    width: 80%;
    max-width: 700px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
}

.vortex-modal-close {
    position: absolute;
    top: 10px;
    right: 20px;
    color: #aaa;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}

.vortex-modal-close:hover {
    color: #333;
}

#log-details-content {
    margin-top: 15px;
}

.loading {
    text-align: center;
    padding: 20px;
    color: #777;
}

/* Spinning Animation */
.spinning {
    animation: spin 2s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Responsive Adjustments */
@media screen and (max-width: 782px) {
    .security-controls {
        flex-direction: column;
        gap: 15px;
    }
    
    .security-actions {
        flex-wrap: wrap;
    }
    
    .security-filters form {
        flex-wrap: wrap;
    }
    
    .filter-group {
        width: 100%;
        margin-bottom: 10px;
    }
    
    .filter-actions {
        width: 100%;
        text-align: right;
    }
    
    .tab-navigation {
        overflow-x: auto;
    }
    
    .activity-stats {
        grid-template-columns: 1fr;
    }
}
</style> 