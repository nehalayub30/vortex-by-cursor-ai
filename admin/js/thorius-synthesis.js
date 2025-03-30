/**
 * Thorius Synthesis Report JavaScript
 * 
 * Handles the visualization and interactivity of synthesis reports
 */
(function($) {
    'use strict';
    
    // Chart instances
    var charts = {};
    
    // Initialize the synthesis report functionality
    $(document).ready(function() {
        // Handle report generation
        $('#generate-synthesis-report').on('click', function() {
            generateSynthesisReport();
        });
        
        // Handle download report
        $('#download-synthesis-report').on('click', function() {
            downloadSynthesisReport();
        });
        
        // Handle email report
        $('#email-synthesis-report').on('click', function() {
            emailSynthesisReport();
        });
        
        // Generate default report on page load
        generateSynthesisReport();
    });
    
    /**
     * Generate synthesis report
     */
    function generateSynthesisReport() {
        var period = $('#synthesis-period').val();
        var reportType = $('#synthesis-report-type').val();
        
        // Show loader
        $('.synthesis-report-loader').show();
        
        // Clear previous report content
        $('#synthesis-summary').html('');
        $('#usage-trends-details').html('');
        $('#behavioral-patterns-details').html('');
        $('#content-analysis-details').html('');
        $('#synthesis-recommendations').html('');
        
        // Clear previous charts
        destroyCharts();
        
        // Make AJAX request
        $.ajax({
            url: vortex_thorius_synthesis.ajax_url,
            type: 'POST',
            data: {
                action: 'vortex_thorius_get_synthesis_report',
                nonce: vortex_thorius_synthesis.nonce,
                period: period,
                report_type: reportType
            },
            success: function(response) {
                // Hide loader
                $('.synthesis-report-loader').hide();
                
                if (response.success && response.data) {
                    renderSynthesisReport(response.data);
                } else {
                    showError(response.data || vortex_thorius_synthesis.i18n.error);
                }
            },
            error: function() {
                // Hide loader
                $('.synthesis-report-loader').hide();
                
                // Show error
                showError(vortex_thorius_synthesis.i18n.error);
            }
        });
    }
    
    /**
     * Render synthesis report
     */
    function renderSynthesisReport(report) {
        // Check if we have data
        if (report.status === 'no_data') {
            showError(report.message);
            return;
        }
        
        // Render executive summary
        renderExecutiveSummary(report);
        
        // Render charts based on report type
        switch (report.report_type) {
            case 'usage':
                renderUsageCharts(report);
                break;
                
            case 'agent_performance':
                renderAgentPerformanceCharts(report);
                break;
                
            case 'content_analysis':
                renderContentAnalysisCharts(report);
                break;
                
            case 'comprehensive':
            default:
                renderUsageCharts(report);
                renderAgentPerformanceCharts(report);
                renderContentAnalysisCharts(report);
                renderBehavioralCharts(report);
                break;
        }
        
        // Render recommendations
        renderRecommendations(report.recommendations);
    }
    
    /**
     * Render executive summary
     */
    function renderExecutiveSummary(report) {
        var summaryHtml = '<div class="summary-grid">';
        
        // Add engagement metrics if available
        if (report.summary && report.summary.engagement) {
            var engagement = report.summary.engagement;
            
            summaryHtml += '<div class="summary-item">';
            summaryHtml += '<h3>' + engagement.total_sessions + '</h3>';
            summaryHtml += '<p>Total Sessions</p>';
            summaryHtml += '</div>';
            
            summaryHtml += '<div class="summary-item">';
            summaryHtml += '<h3>' + Math.round(engagement.avg_session_duration / 60) + ' min</h3>';
            summaryHtml += '<p>Avg. Session</p>';
            summaryHtml += '</div>';
            
            summaryHtml += '<div class="summary-item">';
            summaryHtml += '<h3>' + (engagement.returning_users + engagement.new_users) + '</h3>';
            summaryHtml += '<p>Unique Users</p>';
            summaryHtml += '</div>';
        }
        
        // Add feature usage summary if available
        if (report.summary && report.summary.feature_usage) {
            var featureUsage = report.summary.feature_usage;
            var totalRequests = featureUsage.cloe.total + featureUsage.huraii.total + featureUsage.strategist.total;
            
            summaryHtml += '<div class="summary-item">';
            summaryHtml += '<h3>' + totalRequests + '</h3>';
            summaryHtml += '<p>Total Requests</p>';
            summaryHtml += '</div>';
            
            // Get most used feature
            var mostUsed = 'cloe';
            if (featureUsage.huraii.total > featureUsage.cloe.total && featureUsage.huraii.total > featureUsage.strategist.total) {
                mostUsed = 'huraii';
            } else if (featureUsage.strategist.total > featureUsage.cloe.total && featureUsage.strategist.total > featureUsage.huraii.total) {
                mostUsed = 'strategist';
            }
            
            summaryHtml += '<div class="summary-item highlight">';
            summaryHtml += '<h3>' + mostUsed.toUpperCase() + '</h3>';
            summaryHtml += '<p>Most Used Agent</p>';
            summaryHtml += '</div>';
        }
        
        summaryHtml += '</div>';
        
        // Add narrative summary
        if (report.trends && report.trends.narrative) {
            summaryHtml += '<div class="narrative-summary">';
            summaryHtml += '<p>' + report.trends.narrative + '</p>';
            summaryHtml += '</div>';
        }
        
        $('#synthesis-summary').html(summaryHtml);
    }
    
    // ... Additional helper functions for rendering specific charts ...
    
    /**
     * Show error message
     */
    function showError(message) {
        $('#synthesis-summary').html('<p class="error-message">' + message + '</p>');
    }
    
    /**
     * Destroy all charts before creating new ones
     */
    function destroyCharts() {
        Object.keys(charts).forEach(function(key) {
            if (charts[key]) {
                charts[key].destroy();
                charts[key] = null;
            }
        });
    }
    
    /**
     * Download synthesis report as PDF
     */
    function downloadSynthesisReport() {
        // Implementation for PDF generation using chart.js
        alert('PDF download feature will be implemented');
    }
    
    /**
     * Email synthesis report
     */
    function emailSynthesisReport() {
        // Implementation for emailing the report
        alert('Email report feature will be implemented');
    }
    
})(jQuery); 