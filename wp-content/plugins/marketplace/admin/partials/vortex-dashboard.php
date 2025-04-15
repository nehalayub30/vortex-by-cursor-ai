<?php
// Security check
if (!defined('ABSPATH')) exit;

$active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'overview';
?>

<div class="wrap vortex-dashboard">
    <h1><?php esc_html_e('VORTEX Marketplace & AI Agents Dashboard', 'vortex'); ?></h1>

    <!-- Navigation Tabs -->
    <nav class="nav-tab-wrapper">
        <a href="?page=vortex-dashboard&tab=overview" class="nav-tab <?php echo $active_tab === 'overview' ? 'nav-tab-active' : ''; ?>">
            <?php esc_html_e('Overview', 'vortex'); ?>
        </a>
        <a href="?page=vortex-dashboard&tab=ai-agents" class="nav-tab <?php echo $active_tab === 'ai-agents' ? 'nav-tab-active' : ''; ?>">
            <?php esc_html_e('AI Agents', 'vortex'); ?>
        </a>
        <a href="?page=vortex-dashboard&tab=blockchain" class="nav-tab <?php echo $active_tab === 'blockchain' ? 'nav-tab-active' : ''; ?>">
            <?php esc_html_e('Blockchain Metrics', 'vortex'); ?>
        </a>
        <a href="?page=vortex-dashboard&tab=dao" class="nav-tab <?php echo $active_tab === 'dao' ? 'nav-tab-active' : ''; ?>">
            <?php esc_html_e('DAO Governance', 'vortex'); ?>
        </a>
        <a href="?page=vortex-dashboard&tab=gamification" class="nav-tab <?php echo $active_tab === 'gamification' ? 'nav-tab-active' : ''; ?>">
            <?php esc_html_e('Gamification', 'vortex'); ?>
        </a>
    </nav>

    <!-- Real-time Status Bar -->
    <div class="vortex-status-bar">
        <div class="status-item">
            <span class="label"><?php esc_html_e('Active AI Agents:', 'vortex'); ?></span>
            <span class="value" id="active-agents-count">4/4</span>
        </div>
        <div class="status-item">
            <span class="label"><?php esc_html_e('Tokenized Artworks:', 'vortex'); ?></span>
            <span class="value" id="tokenized-artworks-count">Loading...</span>
        </div>
        <div class="status-item">
            <span class="label"><?php esc_html_e('TOLA Network Status:', 'vortex'); ?></span>
            <span class="value" id="tola-network-status">
                <span class="status-indicator active"></span> Active
            </span>
        </div>
    </div>

    <?php
    // Load appropriate tab content
    switch ($active_tab) {
        case 'overview':
            include(plugin_dir_path(__FILE__) . 'dashboard/overview.php');
            break;
        case 'ai-agents':
            include(plugin_dir_path(__FILE__) . 'dashboard/ai-agents.php');
            break;
        case 'blockchain':
            include(plugin_dir_path(__FILE__) . 'dashboard/blockchain.php');
            break;
        case 'dao':
            include(plugin_dir_path(__FILE__) . 'dashboard/dao.php');
            break;
        case 'gamification':
            include(plugin_dir_path(__FILE__) . 'dashboard/gamification.php');
            break;
    }
    ?>

    <!-- Quick Actions Panel -->
    <div class="vortex-quick-actions">
        <h3><?php esc_html_e('Quick Actions', 'vortex'); ?></h3>
        <div class="action-buttons">
            <button class="button" id="trigger-learning-cycle">
                <?php esc_html_e('Trigger Learning Cycle', 'vortex'); ?>
            </button>
            <button class="button" id="sync-blockchain">
                <?php esc_html_e('Sync Blockchain', 'vortex'); ?>
            </button>
            <button class="button" id="generate-report">
                <?php esc_html_e('Generate Report', 'vortex'); ?>
            </button>
        </div>
    </div>

    <!-- System Health Monitor -->
    <div class="vortex-health-monitor">
        <h3><?php esc_html_e('System Health', 'vortex'); ?></h3>
        <div class="health-grid">
            <!-- AI Agents Health -->
            <div class="health-card">
                <h4><?php esc_html_e('AI Agents', 'vortex'); ?></h4>
                <div class="agent-status" data-agent="huraii">
                    <span class="agent-name">HURAII</span>
                    <span class="health-indicator"></span>
                </div>
                <div class="agent-status" data-agent="cloe">
                    <span class="agent-name">CLOE</span>
                    <span class="health-indicator"></span>
                </div>
                <div class="agent-status" data-agent="business-strategist">
                    <span class="agent-name">Business Strategist</span>
                    <span class="health-indicator"></span>
                </div>
                <div class="agent-status" data-agent="thorius">
                    <span class="agent-name">THORIUS</span>
                    <span class="health-indicator"></span>
                </div>
            </div>

            <!-- Blockchain Health -->
            <div class="health-card">
                <h4><?php esc_html_e('Blockchain', 'vortex'); ?></h4>
                <div class="metric">
                    <span class="label"><?php esc_html_e('Smart Contracts:', 'vortex'); ?></span>
                    <span class="value" id="smart-contracts-count">Loading...</span>
                </div>
                <div class="metric">
                    <span class="label"><?php esc_html_e('Transaction Success Rate:', 'vortex'); ?></span>
                    <span class="value" id="transaction-success-rate">Loading...</span>
                </div>
            </div>

            <!-- Integration Health -->
            <div class="health-card">
                <h4><?php esc_html_e('Integrations', 'vortex'); ?></h4>
                <div class="integration-status">
                    <span class="label">TOLA Network</span>
                    <span class="status-indicator"></span>
                </div>
                <div class="integration-status">
                    <span class="label">DAO Governance</span>
                    <span class="status-indicator"></span>
                </div>
                <div class="integration-status">
                    <span class="label">Gamification</span>
                    <span class="status-indicator"></span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Initialize WebSocket connection for real-time updates
    const socket = new WebSocket('ws://' + window.location.hostname + ':8080');
    
    socket.onmessage = function(event) {
        const data = JSON.parse(event.data);
        updateDashboardMetrics(data);
    };

    // Quick Action handlers
    $('#trigger-learning-cycle').on('click', function() {
        $.post(ajaxurl, {
            action: 'vortex_trigger_learning_cycle',
            nonce: vortexAdmin.nonce
        }, function(response) {
            if (response.success) {
                alert('Learning cycle initiated successfully');
            }
        });
    });

    // Initialize real-time metrics updates
    function updateDashboardMetrics(data) {
        if (data.type === 'metrics_update') {
            $('#tokenized-artworks-count').text(data.data.total_artworks);
            $('#smart-contracts-count').text(data.data.total_contracts);
            $('#transaction-success-rate').text(data.data.success_rate + '%');
            
            // Update agent health indicators
            updateAgentHealth(data.data.agent_health);
        }
    }

    // Update agent health indicators
    function updateAgentHealth(healthData) {
        Object.keys(healthData).forEach(agent => {
            const indicator = $(`.agent-status[data-agent="${agent}"] .health-indicator`);
            indicator.removeClass().addClass('health-indicator ' + healthData[agent].status);
            indicator.attr('title', `Health Score: ${healthData[agent].score}%`);
        });
    }

    // Initialize tooltips
    $('[title]').tooltip();

    // Initial data load
    $.get(ajaxurl, {
        action: 'vortex_get_dashboard_data',
        nonce: vortexAdmin.nonce
    }, function(response) {
        if (response.success) {
            updateDashboardMetrics({
                type: 'metrics_update',
                data: response.data
            });
        }
    });
});
</script>

<style>
.vortex-dashboard {
    margin: 20px;
}

.vortex-status-bar {
    display: flex;
    justify-content: space-between;
    background: #fff;
    padding: 15px;
    margin: 20px 0;
    border-radius: 4px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.health-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.health-card {
    background: #fff;
    padding: 20px;
    border-radius: 4px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.agent-status {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin: 10px 0;
}

.health-indicator {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    display: inline-block;
}

.health-indicator.healthy {
    background: #4CAF50;
}

.health-indicator.warning {
    background: #FFC107;
}

.health-indicator.critical {
    background: #F44336;
}

.vortex-quick-actions {
    margin: 20px 0;
    padding: 20px;
    background: #fff;
    border-radius: 4px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.action-buttons {
    display: flex;
    gap: 10px;
}

.integration-status {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin: 10px 0;
}

.status-indicator {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    display: inline-block;
    margin-left: 5px;
}

.status-indicator.active {
    background: #4CAF50;
}

.status-indicator.inactive {
    background: #F44336;
}
</style> 