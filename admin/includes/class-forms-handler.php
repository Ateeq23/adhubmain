<?php
/**
 * Handles AJAX requests for form management
 *
 * @package ADHUB
 * @subpackage ADHUB/admin/includes
 */

class FormsHandler {

    /**
     * Nonce action name
     */
    const NONCE_ACTION = 'adhub_forms_nonce';

    /**
     * Toggle form action
     */
    const ACTION_TOGGLE_FORM = 'adhub_toggle_form';

    /**
     * Get forms action
     */
    const ACTION_GET_FORMS = 'adhub_get_forms';

    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_ajax_' . self::ACTION_TOGGLE_FORM, array($this, 'handle_toggle_form'));
        add_action('wp_ajax_' . self::ACTION_GET_FORMS, array($this, 'handle_get_forms'));
    }

    /**
     * Handle form toggle request
     */
    public function handle_toggle_form() {
        // Verify nonce
        if (!check_ajax_referer(self::NONCE_ACTION, 'security', false)) {
            wp_send_json_error(array('message' => __('Invalid security token', 'adhub')));
            return;
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission', 'adhub')));
            return;
        }

        $form_key = sanitize_text_field(wp_unslash($_POST['form_key']));
        $enable = isset($_POST['enable']) ? (bool) $_POST['enable'] : false;

        if (empty($form_key)) {
            wp_send_json_error(array('message' => __('Form key is required', 'adhub')));
            return;
        }

        // Include forms scanner
        require_once plugin_dir_path(dirname(__FILE__, 2)) . 'includes/class-adhub-forms-scanner.php';
        $forms_scanner = Adhub_Forms_Scanner::get_instance();
        
        $result = $forms_scanner->toggle_form($form_key, $enable);

        if ($result) {
            $enabled_forms = $forms_scanner->get_enabled_forms();
            wp_send_json_success(array(
                'message' => $enable ? __('Form enabled', 'adhub') : __('Form disabled', 'adhub'),
                'enabled_count' => count($enabled_forms)
            ));
        } else {
            wp_send_json_error(array('message' => __('Failed to update form status', 'adhub')));
        }
    }

    /**
     * Handle get forms request
     */
    public function handle_get_forms() {
        // Verify nonce
        if (!check_ajax_referer(self::NONCE_ACTION, 'security', false)) {
            wp_send_json_error(array('message' => __('Invalid security token', 'adhub')));
            return;
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission', 'adhub')));
            return;
        }

        // Include forms scanner
        require_once plugin_dir_path(dirname(__FILE__, 2)) . 'includes/class-adhub-forms-scanner.php';
        $forms_scanner = Adhub_Forms_Scanner::get_instance();
        
        // Get installed plugins
        $installed_plugins = $forms_scanner->get_installed_plugins();
        
        // Get all forms
        $forms = $forms_scanner->scan_all_forms();
        
        // Get enabled forms
        $enabled_forms = $forms_scanner->get_enabled_forms();

        // Organize forms by plugin
        $forms_by_plugin = array();
        
        foreach ($forms as $form) {
            $plugin_key = $form['plugin_key'];
            
            if (!isset($forms_by_plugin[$plugin_key])) {
                $forms_by_plugin[$plugin_key] = array(
                    'name' => $form['plugin_name'],
                    'forms' => array()
                );
            }
            
            $forms_by_plugin[$plugin_key]['forms'][] = array(
                'id' => $form['id'],
                'title' => $form['title'],
                'shortcode' => $form['shortcode'],
                'form_key' => $form['form_key'],
                'enabled' => in_array($form['form_key'], $enabled_forms)
            );
        }

        wp_send_json_success(array(
            'plugins' => $installed_plugins,
            'forms' => $forms,
            'forms_by_plugin' => $forms_by_plugin,
            'enabled_forms' => $enabled_forms,
            'enabled_count' => count($enabled_forms)
        ));
    }
}
