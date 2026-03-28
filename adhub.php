<?php
/**
 * Plugin Name:       ADHUB
 * Plugin URI:        https://adhubapp.com
 * Description:       Connect your WordPress forms to ADHUB lead management system
 * Version:           1.0.1
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            ADHUB Team
 * Author URI:        https://adhubapp.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       adhub
 * Domain Path:       /languages
 */

// Prevent direct access to this file
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin version
define('ADHUB_VERSION', '1.0.1');

// Define plugin paths
define('ADHUB_PLUGIN_FILE', __FILE__);
define('ADHUB_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('ADHUB_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ADHUB_PLUGIN_URL', plugin_dir_url(__FILE__));

// Define API base URL
define('ADHUB_API_BASE_URL', 'https://adhub-main-d1fcap.laravel.cloud/api/v1');

/**
 * Activation hook handler
 */
function activate_adhub() {
    require_once plugin_dir_path(__FILE__) . 'includes/class-adhub-activator.php';
    Adhub_Activator::activate();
}

/**
 * Deactivation hook handler
 */
function deactivate_adhub() {
    require_once plugin_dir_path(__FILE__) . 'includes/class-adhub-deactivator.php';
    Adhub_Deactivator::deactivate();
}

/**
 * Handle redirect after activation
 */
function adhub_do_activation_redirect() {
    if (get_option('adhub_activation_redirect', false)) {
        delete_option('adhub_activation_redirect');
        if (!isset($_GET['activate-multi'])) {
            wp_redirect(admin_url('admin.php?page=adhub'));
        }
    }
}

register_activation_hook(__FILE__, 'activate_adhub');
register_deactivation_hook(__FILE__, 'deactivate_adhub');
add_action('admin_init', 'adhub_do_activation_redirect');

/**
 * Run the plugin
 */
function run_adhub() {
    // Load required files
    require_once plugin_dir_path(__FILE__) . 'includes/class-adhub.php';
    require_once plugin_dir_path(__FILE__) . 'includes/class-adhub-loader.php';
    require_once plugin_dir_path(__FILE__) . 'includes/class-adhub-i18n.php';
    require_once plugin_dir_path(__FILE__) . 'includes/class-adhub-form-hooks.php';
    require_once plugin_dir_path(__FILE__) . 'includes/class-adhub-forms-scanner.php';
    require_once plugin_dir_path(__FILE__) . 'includes/class-api-sender.php';
    require_once plugin_dir_path(__FILE__) . 'includes/class-adhub-lead-api.php';

    // Initialize main plugin class
    $plugin = new Adhub();
    $plugin->run();

    // Initialize form hooks
    $adhub_form_hooks = new Adhub_Form_Hooks();
    $adhub_form_hooks->init();
}

// Run the plugin
run_adhub();
