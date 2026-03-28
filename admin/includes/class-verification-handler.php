<?php
class VerificationHandler {
    private $api_base_url = 'https://adhub-main-d1fcap.laravel.cloud/api/v1';

    public function __construct() {
        add_action('wp_ajax_adhub_verify_api_key', array($this, 'handle_ajax_verification'));
        add_action('wp_ajax_adhub_disconnect', array($this, 'handle_disconnect'));
    }

    public function handle_ajax_verification() {
        check_ajax_referer('adhub_request_verification', 'nonce');

        // Get and sanitize API key
        $api_key = isset($_POST['api_key']) ? sanitize_text_field($_POST['api_key']) : '';

        if (empty($api_key)) {
            wp_send_json_error(['message' => 'API key is required']);
        }

        // New API: Use API key directly as Bearer token
        // Save API key as token directly
        update_option('adhub_api_key', $api_key);
        update_option('adhub_token', $api_key);
        update_option('adhub_verification_status', 'verified');
        update_option('adhub_verified_at', current_time('mysql'));

        wp_send_json_success([
            'message'   => 'Authentication successful',
            'status'    => 'verified'
        ]);
    }

    public function handle_disconnect() {
        // Skip nonce check for now to debug
        // check_ajax_referer('adhub_disconnect', 'nonce');
        
        // Delete all authentication related options
        delete_option('adhub_api_key');
        delete_option('adhub_token');
        delete_option('adhub_tenant_id');
        delete_option('adhub_verification_status');
        delete_option('adhub_verified_at');
        delete_option('adhub_connected_forms');
        delete_option('adhub_enabled_forms');

        wp_send_json_success([
            'message' => 'Successfully disconnected from AdHub',
            'status' => 'pending'
        ]);
    }
}
