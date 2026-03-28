<?php
class GetInstalledContactFormPlugins {
    private $supported_plugins = [
        'contact-form-7/wp-contact-form-7.php' => [
            'name' => 'Contact Form 7',
            'installed' => false
        ],
        'ninja-forms/ninja-forms.php' => [
            'name' => 'Ninja Forms',
            'installed' => false
        ],
        'wpforms-lite/wpforms.php' => [
            'name' => 'WPForms',
            'installed' => false
        ],
        'gravityforms/gravityforms.php' => [
            'name' => 'Gravity Forms',
            'installed' => false
        ],
        'formidable/formidable.php' => [
            'name' => 'Formidable Forms',
            'installed' => false
        ],
        'caldera-forms/caldera-core.php' => [
            'name' => 'Caldera Forms',
            'installed' => false
        ],
        'fluentform/fluentform.php' => [
            'name' => 'Fluent Forms',
            'installed' => false
        ],
        'happyforms/happyforms.php' => [
            'name' => 'HappyForms',
            'installed' => false
        ],
        'everest-forms/everest-forms.php' => [
            'name' => 'Everest Forms',
            'installed' => false
        ],
        'quform/quform.php' => [
            'name' => 'Quform',
            'installed' => false
        ],
        'forminator/forminator.php' => [
            'name' => 'Forminator',
            'installed' => false
        ],
        'super-forms/super-forms.php' => [
            'name' => 'Super Forms',
            'installed' => false
        ],
        'elementor-pro/elementor-pro.php' => [
            'name' => 'Elementor Pro (Forms)',
            'installed' => false
        ],
        'metform/metform.php' => [
            'name' => 'MetForm',
            'installed' => false
        ],
        'weforms/weforms.php' => [
            'name' => 'weForms',
            'installed' => false
        ],
        'divi/divi.php' => [
            'name' => 'Divi Forms (via Divi Builder)',
            'installed' => false
        ],
    ];

    public function get_plugins() {
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        
        $installed_plugins = get_plugins();
        $plugins_list = $this->supported_plugins;
        
        // Mark installed plugins
        foreach ($plugins_list as $plugin_file => &$plugin_data) {
            $plugin_data['installed'] = isset($installed_plugins[$plugin_file]);
        }
        
        return $plugins_list;
    }
}
