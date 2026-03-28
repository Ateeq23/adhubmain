<?php
/**
 * Ninja Forms handler
 *
 * @package ADHUB
 * @subpackage ADHUB/includes/form-handlers
 */

class NinjaForms_Handler extends Base_Handler {

    /**
     * Initialize the handler
     */
    public function init() {
        add_action('ninja_forms_after_submission', array($this, 'handle_submission'), 10, 1);
    }

    /**
     * Handle form submission
     *
     * @param array $form_data Ninja Forms submission data
     */
    public function handle_submission($form_data) {
        $form_data_formatted = array();
        
        // Process fields from form_data
        if (isset($form_data['fields']) && is_array($form_data['fields'])) {
            foreach ($form_data['fields'] as $field) {
                // Get field key and value
                $field_key = isset($field['key']) ? $field['key'] : '';
                $field_value = isset($field['value']) ? $field['value'] : '';
                
                if (!empty($field_key) && !empty($field_value)) {
                    $form_data_formatted[$field_key] = $field_value;
                }
            }
        }

        // Get form ID from settings
        $form_id = isset($form_data['form_id']) ? $form_data['form_id'] : 0;

        if (!$this->handle_api_result($form_data_formatted, 'ninjaforms', $form_id)) {
            $this->last_error = $this->api_sender->get_last_error();
        }
    }
}