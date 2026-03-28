<?php
/**
 * Fired during plugin deactivation
 *
 * @package ADHUB
 * @subpackage ADHUB/includes
 */

/**
 * Fired during plugin deactivation
 */
class Adhub_Deactivator {

    /**
     * Short Description. (use period)
     *
     * Long Description.
     *
     * @since    1.0.0
     */
    public static function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Optionally delete options on deactivation
        // Note: We keep the options in case the plugin is reactivated
        // If you want to remove all options on deactivation, uncomment below:
        // delete_option('adhub_api_key');
        // delete_option('adhub_token');
        // delete_option('adhub_tenant_id');
        // delete_option('adhub_verification_status');
        // delete_option('adhub_enabled_forms');
        // delete_option('adhub_activation_redirect');
    }
}
