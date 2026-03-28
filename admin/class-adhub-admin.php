<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://https://simsin.com.pk/
 * @since      1.0.0
 *
 * @package    Adhub
 * @subpackage Adhub/admin
 */

class Adhub_Admin {

    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        
        // Include required classes
        require_once plugin_dir_path(__FILE__) . 'includes/class-verification-handler.php';
        require_once plugin_dir_path(__FILE__) . 'includes/class-forms-handler.php';
        
        // Initialize handlers
        new VerificationHandler();
        new FormsHandler();
        
        // Register AJAX hooks
        $this->register_ajax_hooks();
    }

    public function enqueue_styles() {
        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/adhub-admin.css', array(), $this->version, 'all');
    }

    public function enqueue_scripts() {
        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/adhub-admin.js', array('jquery'), $this->version, false);

        // Add the nonce and other data for the AJAX requests
        wp_localize_script($this->plugin_name, 'adhubAdmin', array(
            'nonce' => wp_create_nonce('adhub_update_form_connection'),
            'disconnect_nonce' => wp_create_nonce('adhub_disconnect'),
            'forms_nonce' => wp_create_nonce('adhub_forms_nonce'),
            'ajax_url' => admin_url('admin-ajax.php'),
            'toggle_form_action' => 'adhub_toggle_form',
            'get_forms_action' => 'adhub_get_forms',
            'verify_api_action' => 'adhub_verify_api_key',
            'disconnect_action' => 'adhub_disconnect',
            'update_connection_action' => 'adhub_update_form_connection',
        ));
    }

    /**
     * Register AJAX hooks for updating form connections
     */
    public function register_ajax_hooks() {
        add_action('wp_ajax_adhub_update_form_connection', array($this, 'update_form_connection'));
    }

    public function update_form_connection() {
        // Validate the request
        if (!check_ajax_referer('adhub_update_form_connection', 'security', false)) {
            wp_send_json_error(['message' => 'Invalid nonce.'], 400);
        }

        $form_name = sanitize_text_field($_POST['form_name'] ?? '');
        $connected = filter_var($_POST['connected'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if (empty($form_name)) {
            wp_send_json_error(['message' => 'Form name is required.'], 400);
        }

        // Fetch existing connected forms
        $connected_forms = get_option('adhub_connected_forms', []);
        if (!is_array($connected_forms)) {
            $connected_forms = [];
        }

        if ($connected) {
            if (!in_array($form_name, $connected_forms, true)) {
                $connected_forms[] = $form_name;
            }
        } else {
            $connected_forms = array_diff($connected_forms, [$form_name]);
        }

        // Update the option in the database
        update_option('adhub_connected_forms', $connected_forms);

        wp_send_json_success(['message' => 'Connection status updated.']);
    }

    public function add_plugin_admin_menu() {
        add_menu_page(
            __('ADHUB', 'adhub'),
            __('ADHUB', 'adhub'),
            'manage_options',
            'adhub',
            array($this, 'display_plugin_admin_page'),
            'dashicons-admin-network',
            100
        );
    }

    public function display_plugin_admin_page() {
        // Check verification status
        $verification_status = get_option('adhub_verification_status', 'pending');
        $api_key = get_option('adhub_api_key', '');
        
        // Check if we're viewing a specific plugin's forms
        $view = isset($_GET['view']) ? sanitize_text_field($_GET['view']) : 'plugins';
        $selected_plugin = isset($_GET['plugin']) ? sanitize_text_field($_GET['plugin']) : '';
        
        // Include required files for rendering
        require_once plugin_dir_path(__FILE__) . 'includes/class-render-header.php';
        require_once plugin_dir_path(__FILE__) . 'includes/class-render-notification.php';
        
        // Load installed form plugins
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-adhub-forms-scanner.php';
        $forms_scanner = Adhub_Forms_Scanner::get_instance();
        
        // Render header
        $header = new RenderHeader();
        $header->execute();
        
        // Render notification section (only on plugins page, not on forms page)
        $notification = new RenderNotification();
        $show_notification = ($view !== 'forms');
        $notification->execute($verification_status, $show_notification);
        
        if ($verification_status === 'verified') {
            if ($view === 'forms' && !empty($selected_plugin)) {
                // Show forms for specific plugin
                require_once plugin_dir_path(__FILE__) . 'includes/class-render-contact-forms-connecting-section.php';
                $forms_section = new RenderContactFormsConnectingSection();
                $forms_section->execute_for_plugin($selected_plugin);
            } else {
                // Show plugins list
                require_once plugin_dir_path(__FILE__) . 'includes/class-render-contact-forms-connecting-section.php';
                $forms_section = new RenderContactFormsConnectingSection();
                $forms_section->execute();
            }
        } else {
            // Render verification section
            require_once plugin_dir_path(__FILE__) . 'includes/class-render-verification-section.php';
            $verification_section = new RenderVerificationSection();
            $verification_section->execute($verification_status, $api_key);
        }
    }

    /**
     * Add settings action link to the plugins list
     */
    public function add_action_links($links) {
        $settings_link = array(
            '<a href="' . admin_url('admin.php?page=adhub') . '">' . __('Settings', 'adhub') . '</a>'
        );
        
        return array_merge($settings_link, $links);
    }
}
