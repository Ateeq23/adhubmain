<?php
/**
 * Form scanner for detecting installed form plugins and their forms
 *
 * @package ADHUB
 * @subpackage ADHUB/includes
 */

class Adhub_Forms_Scanner {

    /**
     * Singleton instance
     *
     * @var Adhub_Forms_Scanner
     */
    private static $instance = null;

    /**
     * Form handlers registry
     *
     * @var array
     */
    private $form_handlers = array();

    /**
     * Supported form plugins
     *
     * @var array
     */
    private $supported_plugins = array();

    /**
     * Get singleton instance
     *
     * @return Adhub_Forms_Scanner
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->init_supported_plugins();
    }

    /**
     * Initialize supported plugins
     */
    private function init_supported_plugins() {
        $this->supported_plugins = array(
            'contact-form-7' => array(
                'name' => 'Contact Form 7',
                'slug' => 'contact-form-7',
                'file' => 'contact-form-7/wp-contact-form-7.php',
                'scanner' => 'scan_cf7_forms'
            ),
            'wpforms' => array(
                'name' => 'WPForms',
                'slug' => 'wpforms-lite',
                'file' => 'wpforms-lite/wpforms.php',
                'scanner' => 'scan_wpforms'
            ),
            'gravityforms' => array(
                'name' => 'Gravity Forms',
                'slug' => 'gravityforms',
                'file' => 'gravityforms/gravityforms.php',
                'scanner' => 'scan_gravityforms'
            ),
            'formidable' => array(
                'name' => 'Formidable Forms',
                'slug' => 'formidable',
                'file' => 'formidable/formidable.php',
                'scanner' => 'scan_formidable'
            ),
            'fluentform' => array(
                'name' => 'Fluent Forms',
                'slug' => 'fluentform',
                'file' => 'fluentform/fluentform.php',
                'scanner' => 'scan_fluentform'
            ),
            'ninjaforms' => array(
                'name' => 'Ninja Forms',
                'slug' => 'ninja-forms',
                'file' => 'ninja-forms/ninja-forms.php',
                'scanner' => 'scan_ninjaforms'
            ),
            'calderaforms' => array(
                'name' => 'Caldera Forms',
                'slug' => 'caldera-forms',
                'file' => 'caldera-forms/caldera-core.php',
                'scanner' => 'scan_calderaforms'
            ),
            'everestforms' => array(
                'name' => 'Everest Forms',
                'slug' => 'everest-forms',
                'file' => 'everest-forms/everest-forms.php',
                'scanner' => 'scan_everestforms'
            ),
            'forminator' => array(
                'name' => 'Forminator',
                'slug' => 'forminator',
                'file' => 'forminator/forminator.php',
                'scanner' => 'scan_forminator'
            ),
            'happyforms' => array(
                'name' => 'HappyForms',
                'slug' => 'happyforms',
                'file' => 'happyforms/happyforms.php',
                'scanner' => 'scan_happyforms'
            ),
            'quform' => array(
                'name' => 'Quform',
                'slug' => 'quform',
                'file' => 'quform/quform.php',
                'scanner' => 'scan_quform'
            ),
            'superforms' => array(
                'name' => 'Super Forms',
                'slug' => 'super-forms',
                'file' => 'super-forms/super-forms.php',
                'scanner' => 'scan_superforms'
            ),
            'metform' => array(
                'name' => 'MetForm',
                'slug' => 'metform',
                'file' => 'metform/metform.php',
                'scanner' => 'scan_metform'
            ),
            'weforms' => array(
                'name' => 'weForms',
                'slug' => 'weforms',
                'file' => 'weforms/weforms.php',
                'scanner' => 'scan_weforms'
            ),
            'divi' => array(
                'name' => 'Divi Forms',
                'slug' => 'divi',
                'file' => 'divi/divi.php',
                'scanner' => 'scan_divi'
            ),
            'elementor-pro' => array(
                'name' => 'Elementor Pro Forms',
                'slug' => 'elementor-pro',
                'file' => 'elementor-pro/elementor-pro.php',
                'scanner' => 'scan_elementor_pro'
            )
        );
    }

    /**
     * Check if a plugin is installed
     *
     * @param string $plugin_file Plugin file path
     * @return bool
     */
    public function is_plugin_installed($plugin_file) {
        $plugins = get_plugins();
        return isset($plugins[$plugin_file]);
    }

    /**
     * Check if a plugin is active
     *
     * @param string $plugin_file Plugin file path
     * @return bool
     */
    public function is_plugin_active($plugin_file) {
        return is_plugin_active($plugin_file);
    }

    /**
     * Get installed form plugins
     *
     * @return array
     */
    public function get_installed_plugins() {
        $installed = array();
        
        foreach ($this->supported_plugins as $key => $plugin) {
            if ($this->is_plugin_active($plugin['file'])) {
                $installed[$key] = $plugin;
            }
        }
        
        return $installed;
    }

    /**
     * Scan all forms from all active plugins
     *
     * @return array
     */
    public function scan_all_forms() {
        $forms = array();
        $installed_plugins = $this->get_installed_plugins();
        
        foreach ($installed_plugins as $key => $plugin) {
            $scanner_method = $plugin['scanner'];
            if (method_exists($this, $scanner_method)) {
                $plugin_forms = $this->$scanner_method();
                foreach ($plugin_forms as $form) {
                    $form['plugin_name'] = $plugin['name'];
                    $form['plugin_key'] = $key;
                    $form['form_key'] = $key . '_' . $form['id'];
                    $forms[] = $form;
                }
            }
        }
        
        return $forms;
    }

    /**
     * Scan Contact Form 7 forms
     *
     * @return array
     */
    private function scan_cf7_forms() {
        $forms = array();
        
        if (!function_exists('get_posts')) {
            return $forms;
        }
        
        $cf7_posts = get_posts(array(
            'post_type' => 'wpcf7_contact_form',
            'post_status' => 'publish',
            'numberposts' => -1
        ));
        
        foreach ($cf7_posts as $post) {
            // Get the CF7 form ID from post meta - CF7 uses the post ID but also stores a shorter ID
            $cf7_shortcode_id = get_post_meta($post->ID, '_wpcf7', true);
            $form_id = !empty($cf7_shortcode_id) ? $cf7_shortcode_id : $post->ID;
            
            $forms[] = array(
                'id' => $post->ID,
                'title' => $post->post_title,
                'shortcode' => '[contact-form-7 id="' . $form_id . '" title="' . esc_attr($post->post_title) . '"]'
            );
        }
        
        return $forms;
    }

    /**
     * Scan WPForms
     *
     * @return array
     */
    private function scan_wpforms() {
        $forms = array();
        
        if (!function_exists('wpforms')) {
            return $forms;
        }
        
        $wpforms = wpforms()->get();
        
        if (method_exists($wpforms, 'get')) {
            $forms_list = $wpforms->get('', array('number' => -1));
            
            if (!empty($forms_list)) {
                foreach ($forms_list as $form) {
                    $forms[] = array(
                        'id' => $form->ID,
                        'title' => $form->post_title,
                        'shortcode' => '[wpforms id="' . $form->ID . '"]'
                    );
                }
            }
        }
        
        return $forms;
    }

    /**
     * Scan Gravity Forms
     *
     * @return array
     */
    private function scan_gravityforms() {
        $forms = array();
        
        if (!class_exists('GFAPI')) {
            return $forms;
        }
        
        $gf_forms = GFAPI::get_forms();
        
        if (!empty($gf_forms)) {
            foreach ($gf_forms as $form) {
                $forms[] = array(
                    'id' => $form['id'],
                    'title' => $form['title'],
                    'shortcode' => '[gravityform id="' . $form['id'] . '"]'
                );
            }
        }
        
        return $forms;
    }

    /**
     * Scan Formidable Forms
     *
     * @return array
     */
    private function scan_formidable() {
        $forms = array();
        
        if (!function_exists('get_posts')) {
            return $forms;
        }
        
        $frm_posts = get_posts(array(
            'post_type' => 'frm_form',
            'post_status' => 'publish',
            'numberposts' => -1
        ));
        
        foreach ($frm_posts as $post) {
            $forms[] = array(
                'id' => $post->ID,
                'title' => $post->post_title,
                'shortcode' => '[formidable id="' . $post->ID . '"]'
            );
        }
        
        return $forms;
    }

    /**
     * Scan Fluent Forms
     *
     * @return array
     */
    private function scan_fluentform() {
        $forms = array();
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'fluentform_forms';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name) {
            $results = $wpdb->get_results("SELECT id, title FROM $table_name WHERE status = 'published'");
            
            if (!empty($results)) {
                foreach ($results as $form) {
                    $forms[] = array(
                        'id' => $form->id,
                        'title' => $form->title,
                        'shortcode' => '[fluentform id="' . $form->id . '"]'
                    );
                }
            }
        }
        
        return $forms;
    }

    /**
     * Scan Ninja Forms
     *
     * @return array
     */
    private function scan_ninjaforms() {
        $forms = array();
        
        if (!class_exists('Ninja_Forms')) {
            return $forms;
        }
        
        $nf = Ninja_Forms();
        
        if (method_exists($nf, 'storage')) {
            $form_posts = $nf->storage()->get();
            
            if (!empty($form_posts)) {
                foreach ($form_posts as $form) {
                    $forms[] = array(
                        'id' => $form->get_id(),
                        'title' => $form->get_setting('title'),
                        'shortcode' => '[ninja_form id="' . $form->get_id() . '"]'
                    );
                }
            }
        }
        
        return $forms;
    }

    /**
     * Scan Caldera Forms
     *
     * @return array
     */
    private function scan_calderaforms() {
        $forms = array();
        
        if (!class_exists('Caldera_Forms_Forms')) {
            return $forms;
        }
        
        $cf_forms = Caldera_Forms_Forms::get_forms();
        
        if (!empty($cf_forms)) {
            foreach ($cf_forms as $form) {
                $forms[] = array(
                    'id' => $form['ID'],
                    'title' => $form['name'],
                    'shortcode' => '[caldera_form id="' . $form['ID'] . '"]'
                );
            }
        }
        
        return $forms;
    }

    /**
     * Scan Everest Forms
     *
     * @return array
     */
    private function scan_everestforms() {
        $forms = array();
        
        if (!function_exists('get_posts')) {
            return $forms;
        }
        
        $ev_posts = get_posts(array(
            'post_type' => 'everest_form',
            'post_status' => 'publish',
            'numberposts' => -1
        ));
        
        foreach ($ev_posts as $post) {
            $forms[] = array(
                'id' => $post->ID,
                'title' => $post->post_title,
                'shortcode' => '[everest_form id="' . $post->ID . '"]'
            );
        }
        
        return $forms;
    }

    /**
     * Scan Forminator
     *
     * @return array
     */
    private function scan_forminator() {
        $forms = array();
        
        if (!function_exists('get_posts')) {
            return $forms;
        }
        
        $forminator_posts = get_posts(array(
            'post_type' => 'forminator_forms',
            'post_status' => 'publish',
            'numberposts' => -1
        ));
        
        foreach ($forminator_posts as $post) {
            $forms[] = array(
                'id' => $post->ID,
                'title' => $post->post_title,
                'shortcode' => '[forminator_form id="' . $post->ID . '"]'
            );
        }
        
        return $forms;
    }

    /**
     * Scan HappyForms
     *
     * @return array
     */
    private function scan_happyforms() {
        $forms = array();
        
        if (!function_exists('get_posts')) {
            return $forms;
        }
        
        $happy_posts = get_posts(array(
            'post_type' => 'happyform',
            'post_status' => 'publish',
            'numberposts' => -1
        ));
        
        foreach ($happy_posts as $post) {
            $forms[] = array(
                'id' => $post->ID,
                'title' => $post->post_title,
                'shortcode' => '[happyform id="' . $post->ID . '"]'
            );
        }
        
        return $forms;
    }

    /**
     * Scan Quform
     *
     * @return array
     */
    private function scan_quform() {
        $forms = array();
        
        if (!class_exists('Quform')) {
            return $forms;
        }
        
        // Quform stores forms in database
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'quform_forms';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name) {
            $results = $wpdb->get_results("SELECT id, name FROM $table_name");
            
            if (!empty($results)) {
                foreach ($results as $form) {
                    $forms[] = array(
                        'id' => $form->id,
                        'title' => $form->name,
                        'shortcode' => '[quform id="' . $form->id . '"]'
                    );
                }
            }
        }
        
        return $forms;
    }

    /**
     * Scan Super Forms
     *
     * @return array
     */
    private function scan_superforms() {
        $forms = array();
        
        if (!function_exists('get_posts')) {
            return $forms;
        }
        
        $super_posts = get_posts(array(
            'post_type' => 'super_form',
            'post_status' => 'publish',
            'numberposts' => -1
        ));
        
        foreach ($super_posts as $post) {
            $forms[] = array(
                'id' => $post->ID,
                'title' => $post->post_title,
                'shortcode' => '[super_form id="' . $post->ID . '"]'
            );
        }
        
        return $forms;
    }

    /**
     * Scan MetForm
     *
     * @return array
     */
    private function scan_metform() {
        $forms = array();
        
        if (!function_exists('get_posts')) {
            return $forms;
        }
        
        $metform_posts = get_posts(array(
            'post_type' => 'metform-form',
            'post_status' => 'publish',
            'numberposts' => -1
        ));
        
        foreach ($metform_posts as $post) {
            $forms[] = array(
                'id' => $post->ID,
                'title' => $post->post_title,
                'shortcode' => '[metform id="' . $post->ID . '"]'
            );
        }
        
        return $forms;
    }

    /**
     * Scan weForms
     *
     * @return array
     */
    private function scan_weforms() {
        $forms = array();
        
        if (!function_exists('get_posts')) {
            return $forms;
        }
        
        $weform_posts = get_posts(array(
            'post_type' => 'wpuf_form',
            'post_status' => 'publish',
            'numberposts' => -1
        ));
        
        foreach ($weform_posts as $post) {
            $forms[] = array(
                'id' => $post->ID,
                'title' => $post->post_title,
                'shortcode' => '[weform id="' . $post->ID . '"]'
            );
        }
        
        return $forms;
    }

    /**
     * Scan Divi Forms
     *
     * @return array
     */
    private function scan_divi() {
        $forms = array();
        
        // Divi theme forms are handled via theme integration
        // We'll check if Divi theme is active
        if (!defined('ET_BUILDER_THEME')) {
            return $forms;
        }
        
        // Divi stores forms as module settings in layouts
        // This is a simplified scan
        $divi_posts = get_posts(array(
            'post_type' => 'et_pb_layout',
            'post_status' => 'publish',
            'numberposts' => -1,
            'meta_key' => '_et_pb_uses_template',
            'meta_value' => 'on'
        ));
        
        foreach ($divi_posts as $post) {
            // Check if it contains form module
            if (has_shortcode($post->post_content, 'et_pb_contact_form')) {
                $forms[] = array(
                    'id' => $post->ID,
                    'title' => $post->post_title,
                    'shortcode' => '[et_pb_contact_form module_id="' . $post->ID . '"]'
                );
            }
        }
        
        return $forms;
    }

    /**
     * Scan Elementor Pro Forms
     *
     * @return array
     */
    private function scan_elementor_pro() {
        $forms = array();
        
        if (!defined('ELEMENTOR_PRO_VERSION')) {
            return $forms;
        }
        
        // Elementor Pro forms are stored as post meta
        $elementor_posts = get_posts(array(
            'post_type' => array('page', 'post'),
            'post_status' => 'publish',
            'numberposts' => -1,
            'meta_key' => '_elementor_data',
            'meta_compare' => 'EXISTS'
        ));
        
        foreach ($elementor_posts as $post) {
            $elementor_data = get_post_meta($post->ID, '_elementor_data', true);
            
            if (!empty($elementor_data) && is_string($elementor_data)) {
                $data = json_decode($elementor_data, true);
                
                if (!empty($data)) {
                    foreach ($data as $section) {
                        if (isset($section['widgets'])) {
                            foreach ($section['widgets'] as $widget) {
                                if (isset($widget['widgetType']) && $widget['widgetType'] === 'form') {
                                    $forms[] = array(
                                        'id' => $post->ID . '_' . $widget['id'],
                                        'title' => $post->post_title . ' - Form',
                                        'shortcode' => '[elementor-widget id="' . $widget['id'] . '"]'
                                    );
                                }
                            }
                        }
                    }
                }
            }
        }
        
        return $forms;
    }

    /**
     * Get enabled forms from database
     *
     * @return array
     */
    public function get_enabled_forms() {
        return get_option('adhub_enabled_forms', array());
    }

    /**
     * Toggle form status
     *
     * @param string $form_key Form key
     * @param bool $enable Enable or disable
     * @return bool
     */
    public function toggle_form($form_key, $enable) {
        $enabled_forms = $this->get_enabled_forms();
        
        if ($enable) {
            if (!in_array($form_key, $enabled_forms)) {
                $enabled_forms[] = $form_key;
            }
        } else {
            $enabled_forms = array_diff($enabled_forms, array($form_key));
            $enabled_forms = array_values($enabled_forms);
        }
        
        update_option('adhub_enabled_forms', $enabled_forms);
        return true;
    }

    /**
     * Check if form is enabled
     *
     * @param string $form_key Form key
     * @return bool
     */
    public function is_form_enabled($form_key) {
        $enabled_forms = $this->get_enabled_forms();
        return in_array($form_key, $enabled_forms);
    }

    /**
     * Enable a form
     *
     * @param string $form_key Form key
     * @return bool
     */
    public function enable_form($form_key) {
        return $this->toggle_form($form_key, true);
    }

    /**
     * Disable a form
     *
     * @param string $form_key Form key
     * @return bool
     */
    public function disable_form($form_key) {
        return $this->toggle_form($form_key, false);
    }
}
