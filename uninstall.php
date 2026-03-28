<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * When populating this file, consider the following flow
 * of control:
 *
 * - This method should be static
 * - Check if the $_REQUEST content actually is the plugin name
 * - Run an admin referrer check to make sure it goes through authentication
 * - Verify the output of $_GET is in a valid order
 *
 * @package ADHUB
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete all plugin options
delete_option('adhub_api_key');
delete_option('adhub_token');
delete_option('adhub_tenant_id');
delete_option('adhub_verification_status');
delete_option('adhub_enabled_forms');
delete_option('adhub_activation_redirect');
delete_option('adhub_plugin_version');

// Delete any cached data
delete_transient('adhub_forms_cache');
delete_transient('adhub_installed_plugins_cache');
