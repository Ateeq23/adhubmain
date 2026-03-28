<?php
/**
 * Gravity Forms handler
 *
 * @package ADHUB
 * @subpackage ADHUB/includes/form-handlers
 */

class GravityForms_Handler extends Base_Handler {

    /**
     * Initialize the handler
     */
    public function init() {
        add_action('gform_after_submission', array($this, 'handle_submission'), 10, 2);
    }

    /**
     * Handle form submission
     *
     * @param array $entry Gravity Forms entry object
     * @param array $form Gravity Forms form object
     */
    public function handle_submission($entry, $form) {
        $form_data_formatted = array();
        
        // Get form ID
        $form_id = $form['id'];
        
        // Loop through form fields to get all submitted values
        if (isset($form['fields']) && is_array($form['fields'])) {
            foreach ($form['fields'] as $field) {
                $field_id = $field->id;
                $field_label = isset($field['label']) ? $field['label'] : '';
                $field_inputs = $field->get_entry_inputs();
                
                if (is_array($field_inputs)) {
                    // For multi-input fields (like name, address)
                    foreach ($field_inputs as $input) {
                        $input_id = $input['id'];
                        $value = rgar($entry, (string) $input_id);
                        
                        if (!empty($value)) {
                            // Use field label + input label as key
                            $input_label = isset($input['label']) ? $input['label'] : '';
                            $key = !empty($input_label) ? sanitize_title($field_label . ' ' . $input_label) : $field_id . '_' . $input_id;
                            $form_data_formatted[$key] = $value;
                        }
                    }
                } else {
                    // Single value field
                    $value = rgar($entry, (string) $field_id);
                    
                    if (!empty($value)) {
                        // Use field label as key (sanitized)
                        $key = !empty($field_label) ? sanitize_title($field_label) : $field_id;
                        $form_data_formatted[$key] = $value;
                    }
                }
            }
        }

        if (!$this->handle_api_result($form_data_formatted, 'gravityforms', $form_id)) {
            $this->last_error = $this->api_sender->get_last_error();
        }
    }
}