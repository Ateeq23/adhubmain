<?php
/**
 * Fired during plugin activation
 *
 * @package ADHUB
 * @subpackage ADHUB/includes
 */

/**
 * Fired during plugin activation
 */
class Adhub_Activator {

    /**
     * Short Description. (use period)
     *
     * Long Description.
     *
     * @since    1.0.0
     */
    public static function activate() {
        // Set verification status to pending
        update_option('adhub_verification_status', 'pending');
        
        // Set flag for redirect after activation
        update_option('adhub_activation_redirect', true);
        
        // Set plugin version
        update_option('adhub_plugin_version', ADHUB_VERSION);
        
        // Initialize empty enabled forms array
        if (false === get_option('adhub_enabled_forms')) {
            update_option('adhub_enabled_forms', array());
        }
        
        // Flush rewrite rules for custom post types (if any)
        flush_rewrite_rules();
    }
}
