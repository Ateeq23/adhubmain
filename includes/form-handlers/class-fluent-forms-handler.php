<?php
/**
 * Fluent Forms handler
 *
 * @package ADHUB
 * @subpackage ADHUB/includes/form-handlers
 */

class FluentForm_Handler extends Base_Handler {

    /**
     * Initialize the handler
     */
    public function init() {
        // Multiple hooks for reliability
        add_action('fluentform/after_submission_inserted', array($this, 'handle_submission'), 10, 3);
        add_action('fluentform/submission_inserted', array($this, 'handle_submission'), 10, 3);
        add_action('fluentform/submission_created', array($this, 'handle_submission'), 10, 3);
    }

    /**
     * Handle form submission
     *
     * @param int $insert_id
     * @param array $form_data
     * @param object $form
     */
    public function handle_submission($insert_id, $form_data, $form) {
        $form_data_formatted = array();
        
        // Flatten nested arrays
        foreach ($form_data as $field_name => $value) {
            if (is_array($value)) {
                // Handle checkboxes and multi-select
                $form_data_formatted[$field_name] = implode(', ', $value);
            } else {
                $form_data_formatted[$field_name] = $value;
            }
        }

        $form_id = $form->id;

        if (!$this->handle_api_result($form_data_formatted, 'fluentform', $form_id)) {
            $this->last_error = $this->api_sender->get_last_error();
            
            // Optionally update submission status to failed
            global $wpdb;
            
            $table_name = $wpdb->prefix . 'fluentform_submissions';
            
            if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name) {
                $wpdb->update(
                    $table_name,
                    array('status' => 'failed'),
                    array('id' => $insert_id)
                );
            }
        }
    }
}
