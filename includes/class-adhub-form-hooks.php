<?php
/**
 * Form hooks for intercepting form submissions
 *
 * @package ADHUB
 * @subpackage ADHUB/includes
 */

class Adhub_Form_Hooks {

    /**
     * API sender instance
     *
     * @var API_Sender
     */
    private $api_sender;

    /**
     * Form handlers
     *
     * @var array
     */
    private $handlers = array();

    /**
     * Flag to prevent double initialization
     *
     * @var bool
     */
    private static $initialized = false;

    /**
     * Constructor
     */
    public function __construct() {
        $this->api_sender = new API_Sender(ADHUB_API_BASE_URL);
        $this->register_handlers();
    }

    /**
     * Register all form handlers
     */
    private function register_handlers() {
        // Include form handlers
        require_once plugin_dir_path(__FILE__) . 'form-handlers/class-base-handler.php';
        require_once plugin_dir_path(__FILE__) . 'form-handlers/class-contact-form-7-handler.php';
        require_once plugin_dir_path(__FILE__) . 'form-handlers/class-wpforms-handler.php';
        require_once plugin_dir_path(__FILE__) . 'form-handlers/class-fluent-forms-handler.php';
        require_once plugin_dir_path(__FILE__) . 'form-handlers/class-formidable-forms-handler.php';
        require_once plugin_dir_path(__FILE__) . 'form-handlers/class-forminator-handler.php';
        require_once plugin_dir_path(__FILE__) . 'form-handlers/class-elementor-pro-forms-handler.php';
        require_once plugin_dir_path(__FILE__) . 'form-handlers/class-ninja-forms-handler.php';
        require_once plugin_dir_path(__FILE__) . 'form-handlers/class-gravity-forms-handler.php';

        // Register handlers
        $this->handlers['contact-form-7'] = new CF7_Handler($this->api_sender);
        $this->handlers['wpforms'] = new WPForms_Handler($this->api_sender);
        $this->handlers['fluentform'] = new FluentForm_Handler($this->api_sender);
        $this->handlers['formidable'] = new FormidableForms_Handler($this->api_sender);
        $this->handlers['forminator'] = new Forminator_Handler($this->api_sender);
        $this->handlers['elementor-pro'] = new ElementorProForms_Handler($this->api_sender);
        $this->handlers['ninjaforms'] = new NinjaForms_Handler($this->api_sender);
        $this->handlers['gravityforms'] = new GravityForms_Handler($this->api_sender);
        
        // Note: Additional handlers can be added as needed
    }

    /**
     * Initialize form hooks
     */
    public function init() {
        if (self::$initialized) {
            return;
        }

        $verification_status = get_option('adhub_verification_status');
        
        if ($verification_status !== 'verified') {
            return;
        }

        foreach ($this->handlers as $handler) {
            if (method_exists($handler, 'init')) {
                $handler->init();
            }
        }

        self::$initialized = true;
    }

    /**
     * Get API sender instance
     *
     * @return API_Sender
     */
    public function get_api_sender() {
        return $this->api_sender;
    }
}
