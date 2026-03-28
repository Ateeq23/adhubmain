<?php
/**
 * WPForms handler
 *
 * @package ADHUB
 * @subpackage ADHUB/includes/form-handlers
 */

class WPForms_Handler extends Base_Handler {

    /**
     * Initialize the handler
     */
    public function init() {
        add_action('wpforms_process_complete', array($this, 'handle_submission'), 10, 4);
    }

    /**
     * Handle form submission
     *
     * @param array $fields
     * @param array $entry
     * @param array $form_data
     * @param int $entry_id
     */
    public function handle_submission($fields, $entry, $form_data, $entry_id) {
        $form_data_formatted = array();
        
        foreach ($fields as $field) {
            if (!empty($field['value']) && isset($field['name'])) {
                $form_data_formatted[$field['name']] = $field['value'];
            } elseif (!empty($field['value']) && isset($field['id'])) {
                // Fallback to field id if name not set
                $form_data_formatted['field_' . $field['id']] = $field['value'];
            }
        }

        $form_id = $form_data['id'];

        if (!$this->handle_api_result($form_data_formatted, 'wpforms', $form_id)) {
            $this->last_error = $this->api_sender->get_last_error();
            
            // Set error message
            if (wpforms()->get('process') && isset($form_id)) {
                wpforms()->process->errors[$form_id] = array(
                    'header' => 'API Error',
                    'footer' => $this->last_error
                );
            }
        }
    }
}
