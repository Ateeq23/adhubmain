<?php
/**
 * ADHUB Lead Management API Handler
 *
 * @package ADHUB
 * @subpackage ADHUB/includes
 */

class Adhub_Lead_API {

    /**
     * API base URL
     *
     * @var string
     */
    private $api_base_url;

    /**
     * API token
     *
     * @var string
     */
    private $token;

    /**
     * Last error message
     *
     * @var string
     */
    private $last_error;

    /**
     * Constructor
     */
    public function __construct() {
        $this->api_base_url = defined('ADHUB_API_BASE_URL') ? ADHUB_API_BASE_URL : 'https://adhub-main-d1fcap.laravel.cloud/api/v1';
        $this->token = get_option('adhub_token', '');
    }

    /**
     * Check if API is authenticated
     *
     * @return bool
     */
    public function is_authenticated() {
        return !empty($this->token);
    }

    /**
     * Get query builder fields for lead filtering
     *
     * @param string $context Context key (lead.list, lead.assignment, task.list)
     * @return array|false
     */
    public function get_query_builder_fields($context = 'lead.list') {
        $endpoint = '/query-builder/fields';
        $url = $this->api_base_url . $endpoint . '?context=' . urlencode($context);

        $response = $this->make_request('GET', $url);

        if ($response === false) {
            return false;
        }

        return $response;
    }

    /**
     * List leads with filters, search, and pagination
     *
     * @param array $args {
     *     Optional arguments
     *
     *     @type int    $per_page  Number of results per page (default: 50)
     *     @type int    $page      Page number (default: 1)
     *     @type string $search    Search term
     *     @type array  $filter    Filter rules array
     *     @type string $sort_by    Field to sort by
     *     @type string $sort_dir   Sort direction (asc/desc)
     * }
     * @return array|false
     */
    public function list_leads($args = array()) {
        $defaults = array(
            'per_page'  => 50,
            'page'      => 1,
            'search'    => '',
            'filter'    => null,
            'sort_by'   => '',
            'sort_dir'  => 'asc'
        );

        $args = wp_parse_args($args, $defaults);

        $payload = array(
            'per_page' => intval($args['per_page']),
            'page'     => intval($args['page'])
        );

        if (!empty($args['search'])) {
            $payload['search'] = sanitize_text_field($args['search']);
        }

        if (!empty($args['filter'])) {
            $payload['filter'] = $args['filter'];
        }

        if (!empty($args['sort_by'])) {
            $payload['sort_by'] = sanitize_text_field($args['sort_by']);
            $payload['sort_dir'] = in_array($args['sort_dir'], array('asc', 'desc')) ? $args['sort_dir'] : 'asc';
        }

        $endpoint = '/leads/list';
        $url = $this->api_base_url . $endpoint;

        $response = $this->make_request('POST', $url, $payload);

        if ($response === false) {
            return false;
        }

        return $response;
    }

    /**
     * Get a single lead by ID
     *
     * @param string $lead_id Lead UUID
     * @return array|false
     */
    public function get_lead($lead_id) {
        $endpoint = '/leads/' . sanitize_text_field($lead_id);
        $url = $this->api_base_url . $endpoint;

        $response = $this->make_request('GET', $url);

        if ($response === false) {
            return false;
        }

        return $response;
    }

    /**
     * Update a lead
     *
     * @param string $lead_id Lead UUID
     * @param array  $data     Lead data to update
     * @return array|false
     */
    public function update_lead($lead_id, $data) {
        $endpoint = '/leads/' . sanitize_text_field($lead_id);
        $url = $this->api_base_url . $endpoint;

        // Sanitize input data
        $sanitized_data = $this->sanitize_lead_data($data);

        $response = $this->make_request('PUT', $url, $sanitized_data);

        if ($response === false) {
            return false;
        }

        return $response;
    }

    /**
     * Delete a lead
     *
     * @param string $lead_id Lead UUID
     * @return bool
     */
    public function delete_lead($lead_id) {
        $endpoint = '/leads/' . sanitize_text_field($lead_id);
        $url = $this->api_base_url . $endpoint;

        $response = $this->make_request('DELETE', $url);

        return $response !== false;
    }

    /**
     * Get lead timeline (notes and activities)
     *
     * @param string $lead_id Lead UUID
     * @param int    $limit    Maximum items to return (1-100)
     * @return array|false
     */
    public function get_lead_timeline($lead_id, $limit = 50) {
        $endpoint = '/leads/' . sanitize_text_field($lead_id) . '/timeline';
        $url = $this->api_base_url . $endpoint . '?limit=' . min(max(intval($limit), 1), 100);

        $response = $this->make_request('GET', $url);

        if ($response === false) {
            return false;
        }

        return $response;
    }

    /**
     * Get lead entries
     *
     * @param string $lead_id Lead UUID
     * @param int    $limit    Maximum entries to return (1-100)
     * @return array|false
     */
    public function get_lead_entries($lead_id, $limit = 20) {
        $endpoint = '/leads/' . sanitize_text_field($lead_id) . '/entries';
        $url = $this->api_base_url . $endpoint . '?limit=' . min(max(intval($limit), 1), 100);

        $response = $this->make_request('GET', $url);

        if ($response === false) {
            return false;
        }

        return $response;
    }

    /**
     * Create a new lead
     *
     * @param array $data Lead data
     * @return array|false
     */
    public function create_lead($data) {
        $endpoint = '/leads';
        $url = $this->api_base_url . $endpoint;

        // Sanitize input data
        $sanitized_data = $this->sanitize_lead_data($data);

        $response = $this->make_request('POST', $url, $sanitized_data);

        if ($response === false) {
            return false;
        }

        return $response;
    }

    /**
     * Build filter payload for lead queries
     *
     * @param array $rules  Filter rules
     * @param string $mode  Filter mode (AND/OR)
     * @return array
     */
    public function build_filter_payload($rules, $mode = 'AND') {
        return array(
            'mode'  => strtoupper($mode),
            'rules' => array_map(function($rule) {
                return array(
                    'field'    => sanitize_text_field($rule['field']),
                    'operator' => sanitize_text_field($rule['operator']),
                    'value'    => $rule['value']
                );
            }, $rules)
        );
    }

    /**
     * Make API request
     *
     * @param string $method HTTP method
     * @param string $url    Request URL
     * @param array  $data   Request body data
     * @return array|false
     */
    private function make_request($method, $url, $data = null) {
        if (empty($this->token)) {
            $this->last_error = 'API token not found. Please authenticate first.';
            return false;
        }

        $args = array(
            'method'  => strtoupper($method),
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->token,
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json'
            ),
            'timeout' => 30,
            'sslverify' => true
        );

        if ($data !== null) {
            $args['body'] = json_encode($data);
        }

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            $this->last_error = $response->get_error_message();
            return false;
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        // Handle empty response for DELETE
        if ($status_code === 204) {
            return array('success' => true);
        }

        $decoded_body = json_decode($body, true);

        if ($status_code >= 400) {
            $error_message = isset($decoded_body['message']) ? $decoded_body['message'] : 'API request failed';
            $this->last_error = $error_message . ' (Status: ' . $status_code . ')';
            return false;
        }

        return $decoded_body;
    }

    /**
     * Sanitize lead data
     *
     * @param array $data Raw lead data
     * @return array
     */
    private function sanitize_lead_data($data) {
        $sanitized = array();

        $allowed_fields = array(
            'first_name',
            'last_name',
            'email',
            'mobile_number',
            'company',
            'job_title',
            'service_interest',
            'monthly_budget',
            'timeline',
            'internal_notes',
            'status_id',
            'source_id',
            'owner_id',
            'tag_ids',
            'updated_at'
        );

        foreach ($allowed_fields as $field) {
            if (isset($data[$field])) {
                if ($field === 'tag_ids' && is_array($data[$field])) {
                    $sanitized[$field] = array_map('sanitize_text_field', $data[$field]);
                } else {
                    $sanitized[$field] = sanitize_text_field($data[$field]);
                }
            }
        }

        return $sanitized;
    }

    /**
     * Get last error message
     *
     * @return string
     */
    public function get_last_error() {
        return $this->last_error;
    }

    /**
     * Refresh token from options
     *
     * @return void
     */
    public function refresh_token() {
        $this->token = get_option('adhub_token', '');
    }
}
