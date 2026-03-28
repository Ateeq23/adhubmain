<?php
/**
 * API communication handler
 *
 * @package ADHUB
 * @subpackage ADHUB/includes
 */

class API_Sender {

    /**
     * API base URL
     *
     * @var string
     */
    private $api_base_url;

    /**
     * Last error message
     *
     * @var string
     */
    private $last_error;

    /**
     * Constructor
     *
     * @param string $api_base_url
     */
    public function __construct($api_base_url) {
        $this->api_base_url = $api_base_url;
    }

    /**
     * Send form data to API
     *
     * @param array $form_data Form data
     * @param string $form_type Form type
     * @return bool
     */
    public function send_to_api($form_data) {
        $token = get_option('adhub_token');

        if (!$token) {
            $this->last_error = 'API token not found.';
            return false;
        }

        // Prepare payload
        $payload = $this->prepare_payload($form_data);

        // Send to API (create new lead)
        return $this->make_api_request($payload, $token);
    }
    
    /**
     * Prepare payload from form data
     *
     * @param array $form_data Raw form data
     * @return array
     */
    private function prepare_payload($form_data) {
        // Main field mappings for name, email, phone
        $main_field_mappings = array(
            'first_name' => array('name', 'names', 'your-name', 'your_name', 'full-name', 'full_name', 'first-name', 'first_name', 'fullname', 'contact-name', 'customer-name', 'user-name', 'full_name', 'Name', 'fullname'),
            'last_name' => array('last-name', 'last_name', 'surname'),
            'email' => array('email', 'your-email', 'your_email', 'user_email', 'email-address', 'email_address', 'contact-email', 'customer-email', 'user-email', 'Email'),
            'mobile_number' => array('phone', 'your-phone', 'your_phone', 'tel', 'telephone', 'mobile', 'contact-phone', 'phone-number', 'phone_number', 'customer-phone', 'user-phone', 'Phone', 'mobile_number')
        );

        // Extract main fields
        $lead_data = array();
        
        // Get full name
        $full_name = $this->get_field_value($form_data, $main_field_mappings['first_name']);
        if (!empty($full_name)) {
            $name_parts = explode(' ', $full_name, 2);
            $lead_data['first_name'] = $name_parts[0];
            $lead_data['last_name'] = isset($name_parts[1]) ? $name_parts[1] : '';
        }
        
        // Get email
        $lead_data['email'] = $this->get_field_value($form_data, $main_field_mappings['email']);
        
        // Get phone/mobile
        $phone = $this->get_field_value($form_data, $main_field_mappings['mobile_number']);
        if (!empty($phone)) {
            $lead_data['mobile_number'] = $this->format_phone_number($phone);
        }

        // Track used keys
        $used_keys = array_merge(
            $main_field_mappings['first_name'],
            $main_field_mappings['last_name'],
            $main_field_mappings['email'],
            $main_field_mappings['mobile_number']
        );
        
        // Collect remaining fields as internal notes
        $internal_notes = '';
        
        foreach ($form_data as $key => $value) {
            if (empty($value)) {
                continue;
            }
            
            $key_lower = strtolower($key);
            
            // Skip used keys and internal fields
            if (in_array($key_lower, array_map('strtolower', $used_keys))) {
                continue;
            }
            
            if (strpos($key_lower, '_fluentform_') === 0) {
                continue;
            }
            
            if (strpos($key_lower, 'g-recaptcha-response') !== false) {
                continue;
            }
            
            if (strpos($key_lower, '_wpnonce') !== false) {
                continue;
            }
            
            $clean_key = ucwords(str_replace(array('_', '-'), ' ', $key));
            $clean_value = is_array($value) ? implode(', ', $value) : $value;
            $internal_notes .= $clean_key . ': ' . $clean_value . "\n";
        }

        if (!empty($internal_notes)) {
            $lead_data['internal_notes'] = trim($internal_notes);
        }
        
        // Get valid status_id and source_id from API
        $field_options = $this->get_lead_field_options();
        
        if (!empty($field_options['status_id'])) {
            $lead_data['status_id'] = $field_options['status_id'];
        }
        
        if (!empty($field_options['source_id'])) {
            $lead_data['source_id'] = $field_options['source_id'];
        }

        return $lead_data;
    }

    /**
     * Get field value from form data
     *
     * @param array $form_data Form data
     * @param array $possible_keys Possible field keys
     * @return string
     */
    private function get_field_value($form_data, $possible_keys, $default = '') {
        foreach ($possible_keys as $key) {
            $key_lower = strtolower($key);
            
            // Check exact match
            foreach ($form_data as $form_key => $value) {
                if (strtolower($form_key) === $key_lower && !empty($value)) {
                    return is_array($value) ? implode(' ', $value) : $value;
                }
            }
        }
        
        return $default;
    }

    /**
     * Format phone number
     *
     * @param string $phone
     * @return string
     */
    private function format_phone_number($phone) {
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
     * Make API request
     *
     * @param array $payload Data to send
     * @param string $token JWT token
     * @return bool
     */
    private function make_api_request($payload, $token) {
        $api_url = $this->api_base_url . '/leads';
        
        $request_args = array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode($payload),
            'timeout' => 30,
            'sslverify' => true
        );

        error_log('ADHUB DEBUG: API Request URL = ' . $api_url);
        error_log('ADHUB DEBUG: API Request body = ' . json_encode($payload));

        $response = wp_remote_post($api_url, $request_args);

        if (is_wp_error($response)) {
            $this->last_error = $response->get_error_message();
            error_log('ADHUB DEBUG: API WP Error = ' . $this->last_error);
            return false;
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        error_log('ADHUB DEBUG: API Response status = ' . $status_code);
        error_log('ADHUB DEBUG: API Response body = ' . $body);
        
        if (!in_array($status_code, array(200, 201))) {
            $error_data = json_decode($body, true);
            
            if (isset($error_data['message'])) {
                $this->last_error = $error_data['message'];
            } else {
                $this->last_error = 'API request failed with status: ' . $status_code;
            }
            
            return false;
        }

        return true;
    }
    
    /**
     * Get available status and source options from API
     * 
     * @return array
     */
    private function get_lead_field_options() {
        $token = get_option('adhub_token');
        
        if (!$token) {
            return array('status_id' => null, 'source_id' => null);
        }
        
        // First try to get statuses from lead-statuses endpoint (returns UUIDs)
        $status_id = $this->get_first_status_id($token);
        $source_id = $this->get_first_source_id($token);
        
        // Fallback to query-builder if needed
        if (!$status_id || !$source_id) {
            $fallback = $this->get_lead_field_options_fallback($token);
            $status_id = $status_id ?? $fallback['status_id'];
            $source_id = $source_id ?? $fallback['source_id'];
        }
        
        return array('status_id' => $status_id, 'source_id' => $source_id);
    }
    
    /**
     * Get first status ID from lead-statuses endpoint
     * 
     * @param string $token
     * @return string|null
     */
    private function get_first_status_id($token) {
        $api_url = $this->api_base_url . '/lead-statuses';
        
        $response = wp_remote_get($api_url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json'
            ),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            return null;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        
        if ($status_code !== 200) {
            return null;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (!isset($data['data']) || !is_array($data['data']) || empty($data['data'])) {
            return null;
        }
        
        // Get first status's UUID
        $first_status = $data['data'][0];
        $status_id = $first_status['id'] ?? null;
        
        return $status_id;
    }
    
    /**
     * Get first source ID from lead-sources endpoint
     * 
     * @param string $token
     * @return string|null
     */
    private function get_first_source_id($token) {
        $api_url = $this->api_base_url . '/lead-sources';
        
        $response = wp_remote_get($api_url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json'
            ),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            return null;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        
        if ($status_code !== 200) {
            return null;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (!isset($data['data']) || !is_array($data['data']) || empty($data['data'])) {
            return null;
        }
        
        // Get first source's UUID
        $first_source = $data['data'][0];
        $source_id = $first_source['id'] ?? null;
        
        return $source_id;
    }
    
    /**
     * Fallback: Get field options from query-builder
     * 
     * @param string $token
     * @return array
     */
    private function get_lead_field_options_fallback($token) {
        $api_url = $this->api_base_url . '/query-builder/fields?context=lead.list';
        
        $response = wp_remote_get($api_url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json'
            ),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            return array('status_id' => null, 'source_id' => null);
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        
        if ($status_code !== 200) {
            return array('status_id' => null, 'source_id' => null);
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (!is_array($data)) {
            return array('status_id' => null, 'source_id' => null);
        }
        
        $status_id = null;
        $source_id = null;
        
        foreach ($data as $field) {
            if (isset($field['key']) && $field['key'] === 'lead.status' && !empty($field['options'])) {
                $status_id = $field['options'][0]['value'] ?? null;
            }
            if (isset($field['key']) && $field['key'] === 'lead.source' && !empty($field['options'])) {
                $source_id = $field['options'][0]['value'] ?? null;
            }
        }
        
        return array('status_id' => $status_id, 'source_id' => $source_id);
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
