<?php
/**
 * Template for displaying DAO grants
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="vortex-dao-grants">
    <div class="vortex-dao-grants-header">
        <h2><?php esc_html_e('DAO Grants', 'vortex'); ?></h2>
        
        <?php if (current_user_can('manage_vortex_dao')): ?>
        <div class="vortex-dao-grants-stats">
            <div class="stats-card">
                <span class="stats-label"><?php esc_html_e('Total Granted', 'vortex'); ?></span>
                <span class="stats-value"><?php echo esc_html(number_format($stats['total_granted'], 2)); ?> TOLA</span>
            </div>
            <div class="stats-card">
                <span class="stats-label"><?php esc_html_e('Pending Grants', 'vortex'); ?></span>
                <span class="stats-value"><?php echo esc_html(number_format($stats['total_pending'], 2)); ?> TOLA</span>
            </div>
            <div class="stats-card">
                <span class="stats-label"><?php esc_html_e('Total Grants', 'vortex'); ?></span>
                <span class="stats-value"><?php echo esc_html($stats['grant_count']['total']); ?></span>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="vortex-dao-grants-filters">
        <select id="grant-status-filter">
            <option value=""><?php esc_html_e('All Statuses', 'vortex'); ?></option>
            <option value="pending"><?php esc_html_e('Pending', 'vortex'); ?></option>
            <option value="completed"><?php esc_html_e('Completed', 'vortex'); ?></option>
            <option value="failed"><?php esc_html_e('Failed', 'vortex'); ?></option>
        </select>
        
        <input type="text" id="grant-recipient-filter" placeholder="<?php esc_attr_e('Search by recipient...', 'vortex'); ?>">
    </div>
    
    <div class="vortex-dao-grants-list">
        <table class="vortex-dao-grants-table">
            <thead>
                <tr>
                    <th><?php esc_html_e('Proposal', 'vortex'); ?></th>
                    <th><?php esc_html_e('Recipient', 'vortex'); ?></th>
                    <th><?php esc_html_e('Amount', 'vortex'); ?></th>
                    <th><?php esc_html_e('Purpose', 'vortex'); ?></th>
                    <th><?php esc_html_e('Status', 'vortex'); ?></th>
                    <th><?php esc_html_e('Created', 'vortex'); ?></th>
                    <th><?php esc_html_e('Transaction', 'vortex'); ?></th>
                </tr>
            </thead>
            <tbody id="grants-list">
                <?php foreach ($grants as $grant): ?>
                <tr>
                    <td>
                        <a href="?view=proposal&id=<?php echo esc_attr($grant['proposal_id']); ?>">
                            <?php echo esc_html($grant['proposal_title']); ?>
                        </a>
                    </td>
                    <td title="<?php echo esc_attr($grant['recipient']); ?>">
                        <?php echo esc_html($grant['recipient_short']); ?>
                    </td>
                    <td><?php echo esc_html($grant['amount_formatted']); ?></td>
                    <td><?php echo esc_html($grant['purpose']); ?></td>
                    <td>
                        <span class="grant-status grant-status-<?php echo esc_attr($grant['status']); ?>">
                            <?php echo esc_html(ucfirst($grant['status'])); ?>
                        </span>
                    </td>
                    <td><?php echo esc_html($grant['created_at_formatted']); ?></td>
                    <td>
                        <?php if (!empty($grant['transaction_url'])): ?>
                            <a href="<?php echo esc_url($grant['transaction_url']); ?>" target="_blank">
                                <?php esc_html_e('View', 'vortex'); ?>
                            </a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <div class="vortex-dao-grants-pagination">
        <button id="load-more-grants" class="button"><?php esc_html_e('Load More', 'vortex'); ?></button>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    var offset = <?php echo count($grants); ?>;
    var loading = false;
    
    // Handle status filter change
    $('#grant-status-filter').on('change', function() {
        offset = 0;
        loadGrants(true);
    });
    
    // Handle recipient filter input
    var recipientTimer;
    $('#grant-recipient-filter').on('input', function() {
        clearTimeout(recipientTimer);
        recipientTimer = setTimeout(function() {
            offset = 0;
            loadGrants(true);
        }, 500);
    });
    
    // Handle load more button
    $('#load-more-grants').on('click', function() {
        loadGrants(false);
    });
    
    function loadGrants(reset) {
        if (loading) return;
        loading = true;
        
        if (reset) {
            $('#grants-list').empty();
            offset = 0;
        }
        
        $.ajax({
            url: vortex_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'vortex_get_grants',
                nonce: vortex_ajax.nonce,
                status: $('#grant-status-filter').val(),
                recipient: $('#grant-recipient-filter').val(),
                offset: offset,
                limit: 20
            },
            success: function(response) {
                if (response.success && response.data.grants.length > 0) {
                    response.data.grants.forEach(function(grant) {
                        $('#grants-list').append(createGrantRow(grant));
                    });
                    offset += response.data.grants.length;
                    
                    if (response.data.grants.length < 20) {
                        $('#load-more-grants').hide();
                    } else {
                        $('#load-more-grants').show();
                    }
                } else {
                    if (reset) {
                        $('#grants-list').html('<tr><td colspan="7"><?php esc_html_e('No grants found', 'vortex'); ?></td></tr>');
                    }
                    $('#load-more-grants').hide();
                }
                loading = false;
            },
            error: function() {
                loading = false;
                alert('<?php esc_html_e('Error loading grants', 'vortex'); ?>');
            }
        });
    }
    
    function createGrantRow(grant) {
        var row = $('<tr></tr>');
        
        row.append('<td><a href="?view=proposal&id=' + grant.proposal_id + '">' + grant.proposal_title + '</a></td>');
        row.append('<td title="' + grant.recipient + '">' + grant.recipient_short + '</td>');
        row.append('<td>' + grant.amount_formatted + '</td>');
        row.append('<td>' + grant.purpose + '</td>');
        row.append('<td><span class="grant-status grant-status-' + grant.status + '">' + grant.status.charAt(0).toUpperCase() + grant.status.slice(1) + '</span></td>');
        row.append('<td>' + grant.created_at_formatted + '</td>');
        
        var txCell = '<td>';
        if (grant.transaction_url) {
            txCell += '<a href="' + grant.transaction_url + '" target="_blank"><?php esc_html_e('View', 'vortex'); ?></a>';
        }
        txCell += '</td>';
        row.append(txCell);
        
        return row;
    }
});
</script> 