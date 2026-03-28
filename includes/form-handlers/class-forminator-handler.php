<?php
/**
 * Forminator handler
 *
 * @package ADHUB
 * @subpackage ADHUB/includes/form-handlers
 */

class Forminator_Handler extends Base_Handler {

    /**
     * Initialize the handler
     */
    public function init() {
        // Form Submission Hooks - After entry saved (from Forminator docs)
        add_action('forminator_custom_form_after_save_entry', array($this, 'handle_submission'), 10, 3);
        
        // Alternative hooks for backup compatibility
        add_action('forminator_custom_form_after_handle_submit', array($this, 'handle_submission_alt'), 10, 4);
        add_action('forminator_custom_form_submit_after', array($this, 'handle_submission_alt'), 10, 4);
        
        // Poll and Quiz hooks
        add_action('forminator_poll_submit_after', array($this, 'handle_poll_submission'), 10, 3);
        add_action('forminator_quiz_submit_after', array($this, 'handle_quiz_submission'), 10, 4);
    }

    /**
     * Handle form submission
     *
     * @param array $submitted_data
     * @param int $form_id
     * @param object $form
     */
    public function handle_submission($submitted_data, $form_id, $form) {
        $form_data_formatted = array();
        
        foreach ($submitted_data as $field_name => $value) {
            if (is_array($value)) {
                $form_data_formatted[$field_name] = implode(', ', $value);
            } else {
                $form_data_formatted[$field_name] = $value;
            }
        }

        if (!$this->handle_api_result($form_data_formatted, 'forminator', $form_id)) {
            $this->last_error = $this->api_sender->get_last_error();
        }
    }

    /**
     * Alternative handler for other hooks
     *
     * @param array $submitted_data
     * @param int $form_id
     * @param object $form
     * @param array $response_args
     */
    public function handle_submission_alt($submitted_data, $form_id, $form, $response_args = array()) {
        $form_data_formatted = array();
        
        foreach ($submitted_data as $field_name => $value) {
            if (is_array($value)) {
                $form_data_formatted[$field_name] = implode(', ', $value);
            } else {
                $form_data_formatted[$field_name] = $value;
            }
        }

        if (!$this->handle_api_result($form_data_formatted, 'forminator', $form_id)) {
            $this->last_error = $this->api_sender->get_last_error();
        }
    }

    /**
     * Handle poll submission
     *
     * @param array $submitted_data
     * @param int $poll_id
     * @param object $poll
     */
    public function handle_poll_submission($submitted_data, $poll_id, $poll) {
        $form_data_formatted = array();
        
        foreach ($submitted_data as $field_name => $value) {
            $form_data_formatted[$field_name] = is_array($value) ? implode(', ', $value) : $value;
        }

        if (!$this->handle_api_result($form_data_formatted, 'forminator_poll', $poll_id)) {
            $this->last_error = $this->api_sender->get_last_error();
        }
    }

    /**
     * Handle quiz submission
     *
     * @param array $submitted_data
     * @param int $quiz_id
     * @param object $quiz
     * @param array $response_args
     */
    public function handle_quiz_submission($submitted_data, $quiz_id, $quiz, $response_args = array()) {
        $form_data_formatted = array();
        
        foreach ($submitted_data as $field_name => $value) {
            $form_data_formatted[$field_name] = is_array($value) ? implode(', ', $value) : $value;
        }

        if (!$this->handle_api_result($form_data_formatted, 'forminator_quiz', $quiz_id)) {
            $this->last_error = $this->api_sender->get_last_error();
        }
    }
}
