<?php
/**
 * Admin template for VORTEX Integrations
 */

// Get integration registry instance
$registry = VORTEX_Integration_Registry::get_instance();
$integrations = $registry->get_integrations();
?>

<div class="wrap vortex-integrations-dashboard">
    <h1><?php esc_html_e('VORTEX Integrations', 'vortex'); ?></h1>
    
    <div class="vortex-integration-header">
        <p><?php esc_html_e('Manage connections between components of the VORTEX platform. This page shows the status of all component integrations and allows you to enable, disable, or reload them.', 'vortex'); ?></p>
    </div>
    
    <div class="vortex-integration-status-overview">
        <div class="card">
            <h2><?php esc_html_e('Integration Status Overview', 'vortex'); ?></h2>
            
            <?php
            $active_count = 0;
            $disabled_count = 0;
            $error_count = 0;
            
            foreach ($integrations as $integration) {
                if (!$integration['enabled']) {
                    $disabled_count++;
                } elseif ($integration['status'] === 'active') {
                    $active_count++;
                } elseif ($integration['status'] === 'error') {
                    $error_count++;
                }
            }
            ?>
            
            <div class="vortex-status-summary">
                <div class="status-item status-active">
                    <span class="count"><?php echo $active_count; ?></span>
                    <span class="label"><?php esc_html_e('Active', 'vortex'); ?></span>
                </div>
                <div class="status-item status-disabled">
                    <span class="count"><?php echo $disabled_count; ?></span>
                    <span class="label"><?php esc_html_e('Disabled', 'vortex'); ?></span>
                </div>
                <div class="status-item status-error">
                    <span class="count"><?php echo $error_count; ?></span>
                    <span class="label"><?php esc_html_e('Error', 'vortex'); ?></span>
                </div>
            </div>
        </div>
    </div>
    
    <table class="wp-list-table widefat fixed striped vortex-integrations-table">
        <thead>
            <tr>
                <th class="column-name"><?php esc_html_e('Integration', 'vortex'); ?></th>
                <th class="column-status"><?php esc_html_e('Status', 'vortex'); ?></th>
                <th class="column-provides"><?php esc_html_e('Provides', 'vortex'); ?></th>
                <th class="column-dependencies"><?php esc_html_e('Dependencies', 'vortex'); ?></th>
                <th class="column-actions"><?php esc_html_e('Actions', 'vortex'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($integrations as $id => $integration): ?>
                <tr class="<?php echo $integration['enabled'] ? ($integration['status'] === 'active' ? 'active' : 'error') : 'disabled'; ?>">
                    <td class="column-name">
                        <strong><?php echo esc_html($integration['title']); ?></strong>
                        <div class="description"><?php echo esc_html($integration['description']); ?></div>
                    </td>
                    <td class="column-status">
                        <?php if ($integration['enabled']): ?>
                            <?php if ($integration['status'] === 'active'): ?>
                                <span class="status-indicator active"><?php esc_html_e('Active', 'vortex'); ?></span>
                            <?php elseif ($integration['status'] === 'error'): ?>
                                <span class="status-indicator error"><?php esc_html_e('Error', 'vortex'); ?></span>
                                <?php if (isset($integration['error'])): ?>
                                    <div class="error-message"><?php echo esc_html($integration['error']); ?></div>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="status-indicator inactive"><?php esc_html_e('Inactive', 'vortex'); ?></span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="status-indicator disabled"><?php esc_html_e('Disabled', 'vortex'); ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="column-provides">
                        <?php if (!empty($integration['provides'])): ?>
                            <ul class="comma-list">
                                <?php foreach ($integration['provides'] as $service): ?>
                                    <li><?php echo esc_html($service); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <span class="na">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="column-dependencies">
                        <?php if (!empty($integration['dependencies'])): ?>
                            <ul class="comma-list">
                                <?php foreach ($integration['dependencies'] as $dependency): ?>
                                    <li><?php echo esc_html($dependency); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <span class="na">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="column-actions">
                        <?php if ($integration['enabled']): ?>
                            <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=vortex-integrations&action=disable&integration=' . $id), 'vortex_integration_disable'); ?>" class="button-secondary"><?php esc_html_e('Disable', 'vortex'); ?></a>
                            <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=vortex-integrations&action=reload&integration=' . $id), 'vortex_integration_reload'); ?>" class="button-secondary"><?php esc_html_e('Reload', 'vortex'); ?></a>
                        <?php else: ?>
                            <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=vortex-integrations&action=enable&integration=' . $id), 'vortex_integration_enable'); ?>" class="button-primary"><?php esc_html_e('Enable', 'vortex'); ?></a>
                        <?php endif; ?>
                        <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=vortex-integrations&action=health_check&integration=' . $id), 'vortex_integration_health_check'); ?>" class="button-secondary"><?php esc_html_e('Health Check', 'vortex'); ?></a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <h2 class="title"><?php esc_html_e('Data Integration Monitor', 'vortex'); ?></h2>
    
    <p><?php esc_html_e('This view shows how data flows between components in real-time.', 'vortex'); ?></p>
    
    <div class="vortex-data-flow-monitor">
        <svg id="vortex-data-flow-chart" width="100%" height="400"></svg>
    </div>
    
    <script>
        // Simple D3.js visualization for integration data flow
        jQuery(document).ready(function($) {
            if (typeof d3 !== 'undefined') {
                initDataFlowVisualization();
            } else {
                // Load D3.js dynamically if not already loaded
                $.getScript('https://d3js.org/d3.v7.min.js', function() {
                    initDataFlowVisualization();
                });
            }
            
            function initDataFlowVisualization() {
                // Data for components and connections
                const nodes = [
                    <?php foreach ($integrations as $id => $integration): ?>
                        {
                            id: "<?php echo esc_js($id); ?>",
                            name: "<?php echo esc_js($integration['title']); ?>",
                            status: "<?php echo $integration['enabled'] ? $integration['status'] : 'disabled'; ?>",
                            x: Math.random() * 800,
                            y: Math.random() * 300
                        },
                    <?php endforeach; ?>
                ];
                
                const links = [
                    <?php foreach ($registry->get_data_bridges() as $id => $bridge): ?>
                        {
                            source: "<?php echo esc_js($bridge['source']); ?>",
                            target: "<?php echo esc_js($bridge['target']); ?>",
                            value: "<?php echo esc_js($bridge['data_type']); ?>"
                        },
                    <?php endforeach; ?>
                ];
                
                // Set up the SVG
                const svg = d3.select("#vortex-data-flow-chart");
                const width = svg.node().getBoundingClientRect().width;
                const height = 400;
                
                // Create a force simulation
                const simulation = d3.forceSimulation(nodes)
                    .force("link", d3.forceLink(links).id(d => d.id).distance(150))
                    .force("charge", d3.forceManyBody().strength(-300))
                    .force("center", d3.forceCenter(width / 2, height / 2));
                
                // Create the links
                const link = svg.append("g")
                    .selectAll("line")
                    .data(links)
                    .enter().append("line")
                    .attr("stroke", "#999")
                    .attr("stroke-opacity", 0.6)
                    .attr("stroke-width", 2);
                
                // Add arrow markers for the links
                svg.append("defs").selectAll("marker")
                    .data(links)
                    .enter().append("marker")
                    .attr("id", d => `arrow-${d.source}-${d.target}`)
                    .attr("viewBox", "0 -5 10 10")
                    .attr("refX", 15)
                    .attr("refY", 0)
                    .attr("markerWidth", 6)
                    .attr("markerHeight", 6)
                    .attr("orient", "auto")
                    .append("path")
                    .attr("d", "M0,-5L10,0L0,5")
                    .attr("fill", "#999");
                
                // Add flow labels
                const linkText = svg.append("g")
                    .selectAll("text")
                    .data(links)
                    .enter().append("text")
                    .text(d => d.value)
                    .attr("font-size", 10)
                    .attr("text-anchor", "middle")
                    .attr("dy", -5);
                
                // Create the nodes
                const node = svg.append("g")
                    .selectAll("g")
                    .data(nodes)
                    .enter().append("g");
                
                // Add circles for each node
                node.append("circle")
                    .attr("r", 10)
                    .attr("fill", d => {
                        if (d.status === "active") return "#4CAF50";
                        if (d.status === "error") return "#F44336";
                        return "#9E9E9E";
                    });
                
                // Add labels for each node
                node.append("text")
                    .text(d => d.name)
                    .attr("font-size", 12)
                    .attr("dx", 15)
                    .attr("dy", 4);
                
                // Define tick behavior
                simulation.on("tick", () => {
                    link
                        .attr("x1", d => d.source.x)
                        .attr("y1", d => d.source.y)
                        .attr("x2", d => d.target.x)
                        .attr("y2", d => d.target.y);
                    
                    linkText
                        .attr("x", d => (d.source.x + d.target.x) / 2)
                        .attr("y", d => (d.source.y + d.target.y) / 2);
                    
                    node.attr("transform", d => `translate(${d.x},${d.y})`);
                });
                
                // Add drag capability
                node.call(d3.drag()
                    .on("start", dragstarted)
                    .on("drag", dragged)
                    .on("end", dragended));
                
                function dragstarted(event, d) {
                    if (!event.active) simulation.alphaTarget(0.3).restart();
                    d.fx = d.x;
                    d.fy = d.y;
                }
                
                function dragged(event, d) {
                    d.fx = event.x;
                    d.fy = event.y;
                }
                
                function dragended(event, d) {
                    if (!event.active) simulation.alphaTarget(0);
                    d.fx = null;
                    d.fy = null;
                }
            }
        });
    </script>
</div>

<style>
.vortex-status-summary {
    display: flex;
    justify-content: space-around;
    margin-top: 20px;
}

.status-item {
    text-align: center;
    padding: 15px;
    border-radius: 5px;
}

.status-item .count {
    display: block;
    font-size: 32px;
    font-weight: bold;
}

.status-item .label {
    display: block;
    margin-top: 5px;
}

.status-active {
    color: #4CAF50;
}

.status-disabled {
    color: #757575;
}

.status-error {
    color: #F44336;
}

.status-indicator {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: bold;
}

.status-indicator.active {
    background-color: #E8F5E9;
    color: #2E7D32;
}

.status-indicator.inactive {
    background-color: #EEEEEE;
    color: #616161;
}

.status-indicator.error {
    background-color: #FFEBEE;
    color: #C62828;
}

.status-indicator.disabled {
    background-color: #F5F5F5;
    color: #9E9E9E;
}

.error-message {
    margin-top: 5px;
    font-size: 12px;
    color: #F44336;
}

.comma-list {
    margin: 0;
    padding: 0;
    list-style: none;
    display: inline;
}

.comma-list li {
    display: inline;
}

.comma-list li:after {
    content: ", ";
}

.comma-list li:last-child:after {
    content: "";
}

.vortex-data-flow-monitor {
    margin-top: 20px;
    border: 1px solid #ddd;
    background: #f9f9f9;
    border-radius: 3px;
}
</style> 