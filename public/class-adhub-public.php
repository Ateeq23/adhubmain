<?php
/**
 * The public-facing functionality of the plugin
 *
 * @package ADHUB
 * @subpackage ADHUB/public
 */

class Adhub_Public {

    /**
     * The ID of this plugin
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin
     */
    private $plugin_name;

    /**
     * The version of this plugin
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin
     */
    private $version;

    /**
     * Initialize the class and set its properties
     *
     * @since    1.0.0
     * @param    string    $plugin_name       The name of the plugin
     * @param    string    $version           The version of this plugin
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Register the stylesheets for the public-facing side of the site
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        // Currently no public styles needed
        // This can be used for frontend styling if needed in the future
        
        /*
        wp_enqueue_style(
            $this->plugin_name,
            plugin_dir_url(__FILE__) . 'css/adhub-public.css',
            array(),
            $this->version,
            'all'
        );
        */
    }

    /**
     * Register the JavaScript for the public-facing side of the site
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        // Currently no public scripts needed
        // This can be used for frontend functionality if needed in the future
        
        /*
        wp_enqueue_script(
            $this->plugin_name,
            plugin_dir_url(__FILE__) . 'js/adhub-public.js',
            array('jquery'),
            $this->version,
            false
        );
        */
    }

    /**
     * Register shortcodes
     *
     * @since    1.0.0
     */
    public function register_shortcodes() {
        add_shortcode('adhub_debug', array($this, 'adhub_debug_shortcode'));
    }

    /**
     * Debug shortcode handler
     *
     * @since    1.0.0
     * @return string
     */
    public function adhub_debug_shortcode() {
        $output = '<div style="background: #f5f5f5; padding: 15px; border: 1px solid #ddd; margin: 20px 0;">';
        $output .= '<h3>ADHUB Debug Info</h3>';
        
        // API Token
        $token = get_option('adhub_token');
        $output .= '<p><strong>API Token:</strong> ' . ($token ? '✓ Set (' . substr($token, 0, 20) . '...)' : '✗ Not set') . '</p>';
        
        // Verification Status
        $status = get_option('adhub_verification_status');
        $output .= '<p><strong>Verification Status:</strong> ' . ($status ? $status : 'Not verified') . '</p>';
        
        // Connected Forms
        $connected = get_option('adhub_connected_forms', []);
        $output .= '<p><strong>Connected Form Plugins:</strong> ' . (is_array($connected) && !empty($connected) ? implode(', ', $connected) : 'None') . '</p>';
        
        // Enabled Forms
        $enabled = get_option('adhub_enabled_forms', []);
        $output .= '<p><strong>Enabled Forms:</strong> ' . (is_array($enabled) && !empty($enabled) ? count($enabled) . ' forms enabled' : 'None') . '</p>';
        
        // API Base URL
        $api_url = defined('ADHUB_API_BASE_URL') ? ADHUB_API_BASE_URL : 'Not defined';
        $output .= '<p><strong>API Base URL:</strong> ' . esc_html($api_url) . '</p>';
        
        $output .= '<hr style="margin: 15px 0;">';
        $output .= '<p style="font-size: 12px; color: #666;"><em>To view API call logs, enable WP_DEBUG_LOG in wp-config.php</em></p>';
        
        $output .= '</div>';
        
        return $output;
    }
}
