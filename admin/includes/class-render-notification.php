<?php
/**
 * Render Notification - Renders notification messages
 *
 * @package ADHUB
 * @subpackage ADHUB/admin/includes
 */

class RenderNotification {

    public function execute($verification_status, $show_notification = true) {
        // Don't show notification if explicitly set to false
        if (!$show_notification) {
            return;
        }
        // Check for URL parameters
        $error = isset($_GET['error']) ? sanitize_text_field($_GET['error']) : '';
        $success = isset($_GET['success']) ? sanitize_text_field($_GET['success']) : '';
        
        // Show error messages
        if (!empty($error)) {
            $messages = array(
                'invalid_nonce' => __('Security verification failed.', 'adhub'),
                'empty_key' => __('API key cannot be empty.', 'adhub'),
                'api_error' => __('Unable to connect to ADHUB API. Please try again.', 'adhub'),
                'verification_failed' => __('API key verification failed. Please check your key and try again.', 'adhub')
            );
            
            $message = isset($messages[$error]) ? $messages[$error] : __('An error occurred.', 'adhub');
            ?>
            <div class="notice notice-error" style="display: block;">
                <p><?php echo esc_html($message); ?></p>
            </div>
            <?php
        }
        
        // Show success messages
        if (!empty($success)) {
            $messages = array(
                'verified' => __('Successfully connected to ADHUB!', 'adhub')
            );
            
            $message = isset($messages[$success]) ? $messages[$success] : __('Operation completed successfully.', 'adhub');
            ?>
            <div class="notice notice-success" style="display: block;">
                <p><?php echo esc_html($message); ?></p>
            </div>
            <?php
        }
        
        // Show current status notification
        if ($verification_status === 'verified') {
            $tenant_id = get_option('adhub_tenant_id', '');
            $verified_at = get_option('adhub_verified_at', '');
            ?>
            <div class="current-status">
                <p>
                    <span class="dashicons dashicons-yes-alt" style="color: #35b850;"></span>
                    <?php echo esc_html__('Connected to ADHUB', 'adhub'); ?>
                </p>
                <?php if (!empty($tenant_id)): ?>
                <div id="tenant-info">
                    <p><?php echo esc_html__('Tenant ID:', 'adhub'); ?> <strong><?php echo esc_html($tenant_id); ?></strong></p>
                    <?php if (!empty($verified_at)): ?>
                    <p><?php echo esc_html__('Connected at:', 'adhub'); ?> <?php echo esc_html($verified_at); ?></p>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <div style="margin-top: 15px;">
                    <button type="button" id="adhub-disconnect-btn" class="button button-secondary">
                        <?php echo esc_html__('Disconnect', 'adhub'); ?>
                    </button>
                </div>
                <script>
                jQuery(document).ready(function($) {
                    $('#adhub-disconnect-btn').on('click', function() {
                        if (confirm('Are you sure you want to disconnect from ADHUB?')) {
                            $.ajax({
                                url: ajaxurl,
                                type: 'POST',
                                data: {
                                    action: 'adhub_disconnect',
                                    nonce: adhubAdmin.disconnect_nonce
                                },
                                success: function(response) {
                                    console.log('Disconnect response:', response);
                                    if (response.success) {
                                        location.reload();
                                    } else {
                                        alert('Failed to disconnect. Please try again.');
                                    }
                                },
                                error: function(xhr, status, error) {
                                    console.log('Disconnect error:', status, error);
                                    alert('Failed to disconnect. Please try again.');
                                }
                            });
                        }
                    });
                });
                </script>
            </div>
            <?php
        } else {
            ?>
            <div class="current-status">
                <p>
                    <span class="dashicons dashicons-warning" style="color: #fa641d;"></span>
                    <?php echo esc_html__('Not connected to ADHUB', 'adhub'); ?>
                </p>
            </div>
            <?php
        }
    }
}
