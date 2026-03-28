<?php
class RenderVerificationSection {
    public function execute($verification_status, $api_key) {
        $tenant_id = get_option('adhub_tenant_id', '');
        $token = get_option('adhub_token', '');
        ?>
        <!-- Loading Overlay -->
        <div class="loading-overlay">
            <div class="loading-spinner"></div>
            <div class="loading-text">Processing...</div>
        </div>

        <div class="current-status">
            <p>Current Status: <strong class="blink status-<?php echo esc_attr($verification_status); ?>" id="verification-status"><?php echo esc_html(ucfirst($verification_status)); ?></strong></p>
        </div>
        <div id="verification-message" class="notice" style="display:none;"></div>
        
        <?php if ($verification_status !== 'verified'): ?>
        <form id="adhub-verification-form" class="verification-form">
            <?php wp_nonce_field('adhub_request_verification', 'adhub_nonce'); ?>
            <table class="adhub-form-table">
                <tr>
                    <th><label for="adhub_api_key">API KEY</label></th>
                    <td><input type="text" name="adhub_api_key" id="adhub_api_key" value="<?php echo esc_attr($api_key); ?>" required></td>
                </tr>
            </table>
            <button type="submit" class="adhub-button" id="verify-button">
                <span class="button-text">Request Verification</span>
                <span class="button-loader"></span>
            </button>
        </form>
        <?php else: ?>
        <form id="adhub-disconnect-form" class="disconnect-form">
            <?php wp_nonce_field('adhub_disconnect', 'adhub_disconnect_nonce'); ?>
            <button type="submit" class="adhub-button" id="disconnect-button">
                <span class="button-text">Disconnect Plugin</span>
                <span class="button-loader"></span>
            </button>
        </form>
        <?php endif; ?>

        <script>
        jQuery(document).ready(function($) {
            const loadingOverlay = $('.loading-overlay');
            const loadingText = $('.loading-text');

            function showLoading(message = 'Processing...') {
                loadingText.text(message);
                loadingOverlay.addClass('active');
            }

            function hideLoading() {
                loadingOverlay.removeClass('active');
            }

            <?php if ($verification_status !== 'verified'): ?>
            $('#adhub-verification-form').on('submit', function(e) {
                e.preventDefault();
                
                const button = $('#verify-button');
                const buttonText = button.find('.button-text');
                const buttonLoader = button.find('.button-loader');
                const messageDiv = $('#verification-message');
                
                // Show loading overlay
                showLoading('Verifying API Key...');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'adhub_verify_api_key',
                        api_key: $('#adhub_api_key').val(),
                        nonce: $('#adhub_nonce').val()
                    },
                    success: function(response) {
                        if (response.success) {
                            messageDiv.removeClass('notice-error').addClass('notice-success')
                                .html(response.data.message).show();
                            
                            // Update status and tenant info with animations
                            $('#verification-status')
                                .removeClass('status-pending')
                                .addClass('status-verified')
                                .text('Verified');
                            
                            $('#tenant-id').text(response.data.tenant_id);
                            $('#tenant-info').slideDown();
                            
                            // Reload page to show disconnect button
                            setTimeout(function() {
                                location.reload();
                            }, 1500);
                        } else {
                            messageDiv.removeClass('notice-success').addClass('notice-error')
                                .html(response.data.message).show();
                        }
                    },
                    error: function() {
                        messageDiv.removeClass('notice-success').addClass('notice-error')
                            .html('Connection failed. Please try again.').show();
                    },
                    complete: function() {
                        hideLoading();
                    }
                });
            });
            <?php else: ?>
            $('#adhub-disconnect-form').on('submit', function(e) {
                e.preventDefault();
                
                const button = $('#disconnect-button');
                const buttonText = button.find('.button-text');
                const buttonLoader = button.find('.button-loader');
                const messageDiv = $('#verification-message');
                
                // Show loading overlay
                showLoading('Disconnecting Plugin...');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'adhub_disconnect',
                        nonce: $('#adhub_disconnect_nonce').val()
                    },
                    success: function(response) {
                        if (response.success) {
                            messageDiv.removeClass('notice-error').addClass('notice-success')
                                .html(response.data.message).show();
                            
                            // Update status with animation
                            $('#verification-status')
                                .removeClass('status-verified')
                                .addClass('status-pending')
                                .text('Pending');
                            
                            $('#tenant-info').slideUp();
                            
                            // Reload page to show verification form
                            setTimeout(function() {
                                location.reload();
                            }, 1500);
                        } else {
                            messageDiv.removeClass('notice-success').addClass('notice-error')
                                .html(response.data.message).show();
                        }
                    },
                    error: function() {
                        messageDiv.removeClass('notice-success').addClass('notice-error')
                            .html('Failed to disconnect. Please try again.').show();
                    },
                    complete: function() {
                        hideLoading();
                    }
                });
            });
            <?php endif; ?>
        });
        </script>
        <?php
    }
}
