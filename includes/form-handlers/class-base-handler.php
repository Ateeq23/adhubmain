<?php
/**
 * Base form handler abstract class
 *
 * @package ADHUB
 * @subpackage ADHUB/includes/form-handlers
 */

abstract class Base_Handler {

    /**
     * API sender instance
     *
     * @var API_Sender
     */
    protected $api_sender;

    /**
     * Last error message
     *
     * @var string
     */
    protected $last_error;

    /**
     * Standard field mappings
     *
     * @var array
     */
    protected $field_mappings = array(
        'first_name' => array(
            'name', 'names', 'your-name', 'your_name', 'full-name', 'full_name', 
            'first-name', 'first_name', 'fullname', 'contact-name', 'customer-name', 
            'user-name', 'full_name', 'Name', 'fullname', 'full-name', 'contactname'
        ),
        'last_name' => array(
            'last-name', 'last_name', 'surname'
        ),
        'email' => array(
            'email', 'your-email', 'your_email', 'user_email', 'email-address', 
            'email_address', 'contact-email', 'customer-email', 'user-email', 
            'Email', 'useremail', 'contactemail'
        ),
        'mobile_number' => array(
            'phone', 'your-phone', 'your_phone', 'tel', 'telephone', 'mobile', 
            'contact-phone', 'phone-number', 'phone_number', 'customer-phone', 
            'user-phone', 'Phone', 'userphone', 'contactphone', 'mobilephone', 'mobile_number'
        )
    );

    /**
     * Constructor
     *
     * @param API_Sender $api_sender
     */
    public function __construct($api_sender) {
        $this->api_sender = $api_sender;
    }

    /**
     * Initialize the handler (must be implemented by subclasses)
     */
    abstract public function init();

    /**
     * Format form data
     *
     * @param array $raw_data Raw form data
     * @return array
     */
    protected function format_form_data($raw_data) {
        $formatted_data = array();
        
        // Get full name and split into first_name and last_name
        $full_name = $this->extract_field_value($raw_data, $this->field_mappings['first_name']);
        if (!empty($full_name)) {
            $name_parts = explode(' ', $full_name, 2);
            $formatted_data['first_name'] = $name_parts[0];
            $formatted_data['last_name'] = isset($name_parts[1]) ? $name_parts[1] : '';
        }
        
        // Get email
        $formatted_data['email'] = $this->extract_field_value($raw_data, $this->field_mappings['email']);
        
        // Get phone/mobile and format
        $phone = $this->extract_field_value($raw_data, $this->field_mappings['mobile_number']);
        if (!empty($phone)) {
            $formatted_data['mobile_number'] = $this->format_phone_number($phone);
        }

        // Collect remaining fields as internal_notes
        $internal_notes = '';
        $used_keys = array();
        
        foreach ($this->field_mappings as $api_field => $possible_names) {
            $used_keys = array_merge($used_keys, $possible_names);
        }
        
        foreach ($raw_data as $field_name => $value) {
            if (empty($value)) {
                continue;
            }
            
            $field_name_lower = strtolower($field_name);
            
            // Skip empty values and used keys
            if (in_array($field_name_lower, array_map('strtolower', $used_keys))) {
                continue;
            }
            
            // Skip internal fields
            if (strpos($field_name_lower, '_nonce') !== false) {
                continue;
            }
            
            if (strpos($field_name_lower, 'g-recaptcha') !== false) {
                continue;
            }
            
            if (strpos($field_name_lower, '_fluentform') === 0) {
                continue;
            }
            
            $clean_name = $this->clean_field_name($field_name);
            $clean_value = is_array($value) ? implode(', ', $value) : $value;
            $internal_notes .= $clean_name . ': ' . $clean_value . "\n";
        }

        if (!empty($internal_notes)) {
            $formatted_data['internal_notes'] = trim($internal_notes);
        }

        return $formatted_data;
    }

    /**
     * Extract field value from form data
     *
     * @param array $form_data Form data
     * @param array $possible_keys Possible field keys
     * @return string
     */
    protected function extract_field_value($form_data, $possible_keys) {
        foreach ($possible_keys as $key) {
            $key_lower = strtolower($key);
            
            foreach ($form_data as $field_name => $value) {
                if (strtolower($field_name) === $key_lower && !empty($value)) {
                    return is_array($value) ? implode(' ', $value) : $value;
                }
            }
        }
        
        return '';
    }

    /**
     * Clean field name for display
     *
     * @param string $field_name
     * @param bool $for_notes
     * @return string
     */
    protected function clean_field_name($field_name, $for_notes = false) {
        if ($for_notes) {
            return ucwords(str_replace(array('_', '-'), ' ', $field_name));
        }
        
        return str_replace(array('_', '-'), ' ', $field_name);
    }

    /**
     * Format phone number
     *
     * @param string $phone
     * @return string
     */
    protected function format_phone_number($phone) {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // If it's 10 digits, format as (XXX) XXX-XXXX
        if (strlen($phone) === 10) {
            return '(' . substr($phone, 0, 3) . ') ' . substr($phone, 3, 3) . '-' . substr($phone, 6);
        }
        
        // If it's 11 digits and starts with 1, format as +1 (XXX) XXX-XXXX
        if (strlen($phone) === 11 && substr($phone, 0, 1) === '1') {
            return '+1 (' . substr($phone, 1, 3) . ') ' . substr($phone, 4, 3) . '-' . substr($phone, 7);
        }
        
        return $phone;
    }

    /**
     * Handle API result
     *
     * @param array $form_data Form data
     * @param string $form_type Form type
     * @param int|string $form_id Form ID
     * @return bool
     */
    protected function handle_api_result($form_data, $form_type, $form_id) {
        // First check if plugin is connected
        $connected_plugins = get_option('adhub_connected_forms', []);
        
        // Map form type to plugin name
        $plugin_name_map = [
            'contact-form-7' => 'Contact Form 7',
            'wpforms' => 'WPForms',
            'fluentform' => 'Fluent Forms',
            'formidable' => 'Formidable Forms',
            'forminator' => 'Forminator',
            'elementor-pro' => 'Elementor Pro (Forms)',
            'ninjaforms' => 'Ninja Forms',
            'gravityforms' => 'Gravity Forms'
        ];
        
        $plugin_name = isset($plugin_name_map[$form_type]) ? $plugin_name_map[$form_type] : '';
        
        // DEBUG: Log what we're checking
        error_log('ADHUB DEBUG: form_type=' . $form_type . ', form_id=' . $form_id);
        error_log('ADHUB DEBUG: connected_plugins=' . print_r($connected_plugins, true));
        
        // If plugin is not connected, skip
        if (!empty($plugin_name) && !in_array($plugin_name, $connected_plugins, true)) {
            error_log('ADHUB DEBUG: Plugin not connected, skipping');
            return true; // Skip silently if plugin not connected
        }
        
        // Check if form is enabled
        $form_key = $form_type . '_' . $form_id;
        $forms_scanner = Adhub_Forms_Scanner::get_instance();
        $is_enabled = $forms_scanner->is_form_enabled($form_key);
        
        error_log('ADHUB DEBUG: form_key=' . $form_key . ', is_enabled=' . ($is_enabled ? 'true' : 'false'));
        
        if (!$is_enabled) {
            error_log('ADHUB DEBUG: Form not enabled, skipping');
            return true; // Skip silently if not enabled
        }
        
        error_log('ADHUB DEBUG: Sending data to API for form_key=' . $form_key);

        // Format and send data
        $formatted_data = $this->format_form_data($form_data);
        
        // Check if we have at least email or name
        if (empty($formatted_data['email']) && empty($formatted_data['first_name'])) {
            // Still send but log it
            $this->last_error = 'No email or name found in form submission';
        }
        
        return $this->api_sender->send_to_api($formatted_data);
    }

    /**
     * Get last error
     *
     * @return string
     */
    public function get_last_error() {
        return $this->last_error;
    }
}
