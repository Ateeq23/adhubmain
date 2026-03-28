<?php
/**
 * Formidable Forms handler
 *
 * @package ADHUB
 * @subpackage ADHUB/includes/form-handlers
 */

class FormidableForms_Handler extends Base_Handler {

    /**
     * Initialize the handler
     */
    public function init() {
        add_action('frm_after_create_entry', array($this, 'handle_submission'), 20, 2);
        add_action('frm_after_update_entry', array($this, 'handle_submission'), 20, 2);
    }

    /**
     * Handle form submission
     *
     * @param int $entry_id
     * @param int $form_id
     */
    public function handle_submission($entry_id, $form_id) {
        // Get entry data
        $entry = FrmEntry::getOne($entry_id);
        
        if (!$entry) {
            return;
        }

        $form_data_formatted = array();
        
        // Get all field values
        $entry_meta = FrmEntryMeta::get_entry_meta_info($entry_id);
        
        if (!empty($entry_meta)) {
            foreach ($entry_meta as $meta) {
                $field = FrmField::getOne($meta->field_id);
                
                if ($field) {
                    $field_name = 'field_' . $field->id;
                    
                    // Handle different field types
                    if (is_array($meta->meta_value)) {
                        $form_data_formatted[$field_name] = implode(', ', $meta->meta_value);
                    } else {
                        $form_data_formatted[$field_name] = $meta->meta_value;
                    }
                    
                    // Also add by field key if available
                    if (!empty($field->field_key)) {
                        $form_data_formatted[$field->field_key] = $form_data_formatted[$field_name];
                    }
                }
            }
        }

        if (!$this->handle_api_result($form_data_formatted, 'formidable', $form_id)) {
            $this->last_error = $this->api_sender->get_last_error();
        }
    }
}
