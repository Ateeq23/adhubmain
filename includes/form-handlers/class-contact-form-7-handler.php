<?php
/**
 * Contact Form 7 handler
 *
 * @package ADHUB
 * @subpackage ADHUB/includes/form-handlers
 */

class CF7_Handler extends Base_Handler {

    /**
     * Initialize the handler
     */
    public function init() {
        add_action('wpcf7_before_send_mail', array($this, 'handle_submission'), 1);
        add_filter('wpcf7_ajax_json_echo', array($this, 'modify_response'), 999);
    }

    /**
     * Handle form submission
     *
     * @param WPCF7_ContactForm $contact_form
     * @return mixed
     */
    public function handle_submission($contact_form) {
        if (!class_exists('WPCF7_Submission')) {
            return;
        }

        $submission = WPCF7_Submission::get_instance();
        
        if (!$submission) {
            return;
        }

        $form_data = $submission->get_posted_data();
        $form_id = $contact_form->id();
        
        error_log('ADHUB DEBUG: CF7 handle_submission called with form_id=' . $form_id);
        
        if (!$this->handle_api_result($form_data, 'contact-form-7', $form_id)) {
            $this->last_error = $this->api_sender->get_last_error();
            error_log('ADHUB DEBUG: CF7 API call failed, error=' . $this->last_error);
            
            return false;
        }
        
        error_log('ADHUB DEBUG: CF7 API call succeeded');
        return true;
    }

    /**
     * Modify AJAX response
     *
     * @param array $response
     * @return array
     */
    public function modify_response($response) {
        // This can be used to modify the AJAX response if needed
        return $response;
    }
}
