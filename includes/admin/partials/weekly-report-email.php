<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Thorius AI Weekly Synthesis</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #6200ea; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background-color: #f9f9f9; }
        .section { margin-bottom: 20px; background-color: white; padding: 15px; border-radius: 5px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        h1, h2, h3 { margin-top: 0; color: #333; }
        .header h1 { color: white; margin: 0; }
        .metric { font-size: 24px; font-weight: bold; color: #6200ea; margin: 5px 0; }
        .metric-label { font-size: 14px; color: #666; }
        .highlight { background-color: #f5f0ff; border-left: 4px solid #6200ea; padding-left: 10px; }
        .footer { padding: 20px; text-align: center; font-size: 12px; color: #666; }
        .cta { background-color: #6200ea; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px; display: inline-block; margin-top: 15px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Thorius AI Weekly Synthesis</h1>
            <p><?php echo date('F j, Y', strtotime('-7 days')); ?> - <?php echo date('F j, Y'); ?></p>
        </div>
        
        <div class="content">
            <div class="section">
                <h2>Usage Summary</h2>
                <div class="metric"><?php echo $report_data['summary']['engagement']['total_sessions']; ?></div>
                <div class="metric-label">Total Sessions</div>
                
                <div class="metric"><?php echo $report_data['summary']['feature_usage']['total_requests']; ?></div>
                <div class="metric-label">Total AI Requests</div>
                
                <div class="highlight">
                    <p><strong>Trend:</strong> <?php echo $report_data['trends']['usage_trend']; ?></p>
                </div>
            </div>
            
            <div class="section">
                <h2>Agent Performance</h2>
                <p><strong>Most Used Agent:</strong> <?php echo $report_data['trends']['most_used_agent']; ?></p>
                <p><strong>Top Use Case:</strong> <?php echo $report_data['trends']['top_use_case']; ?></p>
                
                <?php if(!empty($report_data['trends']['emerging_topics'])): ?>
                <div class="highlight">
                    <p><strong>Emerging Topics:</strong> <?php echo $report_data['trends']['emerging_topics']; ?></p>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="section">
                <h2>Key Recommendations</h2>
                <ul>
                    <?php foreach($report_data['recommendations'] as $recommendation): ?>
                    <li><?php echo $recommendation; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <a href="<?php echo admin_url('admin.php?page=vortex-thorius-synthesis'); ?>" class="cta">View Full Report</a>
        </div>
        
        <div class="footer">
            <p>Thorius AI Concierge - Advanced Behavioral Analytics</p>
            <p>You're receiving this email because you're an administrator of <?php echo get_bloginfo('name'); ?>.</p>
        </div>
    </div>
</body>
</html> 