<?php
/**
 * Elementor Pro Forms handler
 *
 * @package ADHUB
 * @subpackage ADHUB/includes/form-handlers
 */

class ElementorProForms_Handler extends Base_Handler {

    /**
     * Initialize the handler
     */
    public function init() {
        add_action('elementor_pro/forms/new_record', array($this, 'handle_submission'), 10, 2);
    }

    /**
     * Handle form submission
     *
     * @param object $record
     * @param object $handler
     */
    public function handle_submission($record, $handler) {
        $form_data = $record->get_form_settings('form_fields');
        $submitted_fields = $record->get('fields');
        
        $form_data_formatted = array();
        
        foreach ($submitted_fields as $field) {
            $field_name = $field['name'];
            $field_value = $field['value'];
            
            if (is_array($field_value)) {
                $form_data_formatted[$field_name] = implode(', ', $field_value);
            } else {
                $form_data_formatted[$field_name] = $field_value;
            }
        }
        
        // Get form ID
        $form_id = $record->get('form_id');

        if (!$this->handle_api_result($form_data_formatted, 'elementor-pro', $form_id)) {
            $this->last_error = $this->api_sender->get_last_error();
            
            // Add error to Elementor form
            $handler->add_error($form_id, $this->last_error);
        }
    }
}
