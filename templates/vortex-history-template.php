                        <th><?php _e('Details', 'vortex-marketplace'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($history['records'] as $record): 
                        $details = json_decode($record->action_details, true);
                        $formatted_details = '';
                        
                        if (is_array($details)) {
                            foreach ($details as $key => $value) {
                                if ($key === 'price' && isset($details['currency'])) {
                                    $formatted_details .= "$key: $value {$details['currency']}, ";
                                } else if (!in_array($key, array('artwork_id', 'collection_id', 'currency'))) {
                                    $formatted_details .= "$key: $value, ";
                                }
                            }
                            $formatted_details = rtrim($formatted_details, ', ');
                        }
                    ?>
                    <tr>
                        <td><?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($record->created_at)); ?></td>
                        <td><?php echo ucwords(str_replace('_', ' ', $record->action_type)); ?></td>
                        <td>
                            <?php if ($record->item_id > 0): ?>
                                <a href="<?php echo esc_url(get_permalink($record->item_id)); ?>"><?php echo esc_html($record->item_title); ?></a>
                            <?php else: ?>
                                <?php echo esc_html($record->item_title); ?>
                            <?php endif; ?>
                        </td>
                        <td><?php echo esc_html($formatted_details); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <?php if ($history['total_pages'] > 1): ?>
                <div class="vortex-history-pagination">
                    <?php
                    $big = 999999999;
                    echo paginate_links(array(
                        'base' => str_replace($big, '%#%', esc_url(get_pagenum_link($big))),
                        'format' => '?hpage=%#%',
                        'current' => max(1, $page),
                        'total' => $history['total_pages'],
                        'prev_text' => __('&laquo; Previous', 'vortex-marketplace'),
                        'next_text' => __('Next &raquo;', 'vortex-marketplace'),
                    ));
                    ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<script>
    jQuery(document).ready(function($) {
        // Initialize datepickers
        $('.datepicker').datepicker({
            dateFormat: 'yy-mm-dd',
            maxDate: '0'
        });
    });
</script> 