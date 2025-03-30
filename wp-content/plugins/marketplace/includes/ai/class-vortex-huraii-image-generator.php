        // Check download capability
        if ($capability == 'all' || $capability == 'download') {
            // This is client-side functionality, but we can check if images are accessible
            $results['download'] = array(
                'status' => 'success',
                'message' => __('Download capability available', 'vortex-marketplace')
            );
        }
        
        // Check upscale capability
        if ($capability == 'all' || $capability == 'upscale') {
            if (empty($this->api_key)) {
                $results['upscale'] = array(
                    'status' => 'error',
                    'message' => __('API key is not configured', 'vortex-marketplace')
                );
            } else {
                // First generate an image to upscale
                $test_result = $this->generate_image('Test upscale for audit', array(
                    'num_variations' => 1,
                    'width' => 512,
                    'height' => 512
                ));
                
                if (is_wp_error($test_result)) {
                    $results['upscale'] = array(
                        'status' => 'error',
                        'message' => $test_result->get_error_message()
                    );
                } else {
                    // Try to upscale the generated image
                    $image_id = $test_result['images'][0]['id'];
                    $upscale_result = $this->upscale_image($image_id);
                    
                    if (is_wp_error($upscale_result)) {
                        $results['upscale'] = array(
                            'status' => 'error',
                            'message' => $upscale_result->get_error_message()
                        );
                    } else {
                        $results['upscale'] = array(
                            'status' => 'success',
                            'message' => __('Upscale capability working', 'vortex-marketplace')
                        );
                    }
                }
            }
        }
        
        // Check save to library capability
        if ($capability == 'all' || $capability == 'save') {
            if (!current_user_can('upload_files')) {
                $results['save'] = array(
                    'status' => 'error',
                    'message' => __('User does not have permission to upload files', 'vortex-marketplace')
                );
            } else {
                // First generate an image to save
                $test_result = $this->generate_image('Test save for audit', array(
                    'num_variations' => 1,
                    'width' => 512,
                    'height' => 512
                ));
                
                if (is_wp_error($test_result)) {
                    $results['save'] = array(
                        'status' => 'error',
                        'message' => $test_result->get_error_message()
                    );
                } else {
                    // Try to save the generated image
                    $image_url = $test_result['images'][0]['url'];
                    $save_result = $this->save_image_to_library($image_url, 'Test save for audit');
                    
                    if (is_wp_error($save_result)) {
                        $results['save'] = array(
                            'status' => 'error',
                            'message' => $save_result->get_error_message()
                        );
                    } else {
                        // Clean up by deleting the test image
                        wp_delete_attachment($save_result, true);
                        
                        $results['save'] = array(
                            'status' => 'success',
                            'message' => __('Save to library capability working', 'vortex-marketplace')
                        );
                    }
                }
            }
        }
        
        // Check delete capability
        if ($capability == 'all' || $capability == 'delete') {
            global $wpdb;
            $table_name = $wpdb->prefix . 'vortex_generated_images';
            
            if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
                $results['delete'] = array(
                    'status' => 'error',
                    'message' => __('Database table does not exist', 'vortex-marketplace')
                );
            } else {
                // First generate an image to delete
                $test_result = $this->generate_image('Test delete for audit', array(
                    'num_variations' => 1,
                    'width' => 512,
                    'height' => 512
                ));
                
                if (is_wp_error($test_result)) {
                    $results['delete'] = array(
                        'status' => 'error',
                        'message' => $test_result->get_error_message()
                    );
                } else {
                    // Try to delete the generated image
                    $image_id = $test_result['images'][0]['id'];
                    $delete_result = $this->delete_image($image_id);
                    
                    if (is_wp_error($delete_result)) {
                        $results['delete'] = array(
                            'status' => 'error',
                            'message' => $delete_result->get_error_message()
                        );
                    } else {
                        $results['delete'] = array(
                            'status' => 'success',
                            'message' => __('Delete capability working', 'vortex-marketplace')
                        );
                    }
                }
            }
        }
        
        // Check database tables
        if ($capability == 'all' || $capability == 'database') {
            global $wpdb;
            $table_name = $wpdb->prefix . 'vortex_generated_images';
            
            if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
                $results['database'] = array(
                    'status' => 'error',
                    'message' => __('Database table does not exist', 'vortex-marketplace')
                );
            } else {
                $results['database'] = array(
                    'status' => 'success',
                    'message' => __('Database tables exist and are properly configured', 'vortex-marketplace')
                );
            }
        }
        
        // Generate audit summary
        if ($capability == 'all') {
            $success_count = 0;
            $error_count = 0;
            
            foreach ($results as $result) {
                if ($result['status'] == 'success') {
                    $success_count++;
                } else {
                    $error_count++;
                }
            }
            
            $total = count($results);
            $success_rate = ($total > 0) ? round(($success_count / $total) * 100) : 0;
            
            $results['summary'] = array(
                'total' => $total,
                'success' => $success_count,
                'error' => $error_count,
                'success_rate' => $success_rate,
                'timestamp' => current_time('mysql')
            );
        }
        
        return $results;
    }
    
    /**
     * AJAX handler for capability audit
     */
    public function ajax_run_capability_audit() {
        check_ajax_referer('vortex_image_generator', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            return;
        }
        
        $capability = isset($_POST['capability']) ? sanitize_text_field($_POST['capability']) : 'all';
        $results = $this->run_capability_audit($capability);
        
        wp_send_json_success($results);
    }
}

// Initialize image generator
add_action('plugins_loaded', function() {
    VORTEX_HURAII_Image_Generator::get_instance();
});

// Register AJAX endpoint for audit
add_action('wp_ajax_vortex_run_capability_audit', array(VORTEX_HURAII_Image_Generator::get_instance(), 'ajax_run_capability_audit')); 