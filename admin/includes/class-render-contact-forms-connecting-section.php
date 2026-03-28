<?php
if (!defined('ABSPATH')) {
    exit;
}

// Include the file with GetInstalledContactFormPlugins class
require_once plugin_dir_path(__FILE__) . 'class-get-installed-contact-form-plugins.php';

class RenderContactFormsConnectingSection {
    private $plugin_detector;
    private $supported_handlers = [
        'Contact Form 7' => 'CF7_Handler',
        'WPForms' => 'WPForms_Handler',
        'Fluent Forms' => 'FluentForm_Handler',
        'Formidable Forms' => 'FormidableForms_Handler',
        'Elementor Pro (Forms)' => 'Elementor_Handler',
        'Forminator' => 'Forminator_Handler',
    ];
    
    private $form_scanner;

    public function __construct() {
        $this->plugin_detector = new GetInstalledContactFormPlugins();
        
        // Include the forms scanner
        if (class_exists('Adhub_Forms_Scanner')) {
            $this->form_scanner = Adhub_Forms_Scanner::get_instance();
        }
    }

    /**
     * Execute - Show plugins list
     */
    public function execute() {
        // Logos for form plugins
        $logos = [
            'Contact Form 7' => 'cf7-logo.png',
            'WPForms' => 'wpforms-logo.png',
            'Formidable Forms' => 'formidable-form-logo.png',
            'Forminator' => 'forminator-form-logo.png',
            'Ninja Forms' => 'ninja-form-logo.png',
            'Gravity Forms' => 'gravity-form-logo.png',
            'Caldera Forms' => 'caldera-form-logo.png',
            'Fluent Forms' => 'fluent-form-logo.png',
            'HappyForms' => 'happy-forms-logo.png',
            'Everest Forms' => 'everest-forms-logo.png',
            'Quform' => 'quform-logo.png',
            'Divi Forms (via Divi Builder)' => 'divi-forms-logo.png',
            'weForms' => 'weforms-logo.png',
            'Super Forms' => 'superforms-logo.png',
            'Elementor Pro (Forms)' => 'elementor-form-logo.png',
            'MetForm' => 'metform-logo.png'
        ];

        $form_plugins = $this->plugin_detector->get_plugins();

        // Fetch connected forms from the database
        $connected_forms = get_option('adhub_connected_forms', []);
        if (!is_array($connected_forms)) {
            $connected_forms = [];
        }
        ?>
        <div class="contact-connecting-sections">
            <h2>Contact Forms</h2>
            <p>Select the WordPress contact form(s) you're using to connect them. Once connected, you can submit a test lead on your contact form to confirm it is received into your AdHub account.</p>

            <div class="form-plugins-list">
                <?php foreach ($form_plugins as $plugin_file => $plugin_data): 
                    $is_supported = isset($this->supported_handlers[$plugin_data['name']]);
                    $is_coming_soon = !$is_supported;
                    $is_connected = in_array($plugin_data['name'], $connected_forms, true);
                    ?>
                    <div class="form-plugin-item <?php echo $plugin_data['installed'] ? 'installed' : 'inactive-plugin'; ?> <?php echo $is_coming_soon ? 'coming-soon' : ''; ?>">
                        <?php if ($is_coming_soon): ?>
                            <div class="coming-soon-tooltip">Coming Soon</div>
                        <?php endif; ?>
                        <div class="form-plugin-details">
                            <img src="<?php echo plugin_dir_url(__FILE__) . 'images/' . ($logos[$plugin_data['name']] ?? 'default-logo.png'); ?>" 
                                 alt="<?php echo esc_attr($plugin_data['name']); ?>" 
                                 class="form-logo">
                            <span class="form-name">
                                <?php echo esc_html($plugin_data['name']); ?>
                                <?php if (!$plugin_data['installed']): ?>
                                    <span class="inactive-notice">(Not Installed)</span>
                                <?php endif; ?>
                            </span>
                        </div>

                        <div class="plugin-status-toggle" style="display: flex; flex-direction: column; align-items: center;">
                            <div class="status-toggle-row" style="display: flex; justify-content: space-between; width: 100%;">
                                <span class="status-text">
                                    <?php echo $is_connected ? 'Connected' : 'Not Connected'; ?>
                                </span>
                                <label class="switch">
                                    <input type="checkbox" 
                                           class="form-connection-toggle" 
                                           data-form="<?php echo esc_attr($plugin_data['name']); ?>"
                                           data-redirect="true"
                                           <?php checked($is_connected); ?>
                                           <?php echo (!$plugin_data['installed'] || $is_coming_soon) ? 'disabled' : ''; ?>>
                                    <span class="slider round"></span>
                                </label>
                            </div>
                            <?php if ($is_connected): ?>
                                <a href="<?php echo admin_url('admin.php?page=adhub&view=forms&plugin=' . urlencode($plugin_data['name'])); ?>" class="view-forms-link" style="width: 100%; margin-top: 10px;">View Forms</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Handle form connection toggles
            $('.form-connection-toggle').on('change', function() {
                const formName = $(this).data('form');
                const isConnected = $(this).prop('checked');
                const shouldRedirect = $(this).data('redirect');
                const toggle = $(this);

                toggle.prop('disabled', true);

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'adhub_update_form_connection',
                        form_name: formName,
                        connected: isConnected,
                        security: adhubAdmin.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            if (shouldRedirect && isConnected) {
                                // Redirect to forms page
                                window.location.href = '<?php echo admin_url('admin.php?page=adhub'); ?>&view=forms&plugin=' + encodeURIComponent(formName);
                            } else {
                                location.reload();
                            }
                        } else {
                            toggle.prop('checked', !isConnected);
                            alert('Failed to update connection status');
                        }
                    },
                    error: function() {
                        toggle.prop('checked', !isConnected);
                        alert('Failed to update connection status');
                    },
                    complete: function() {
                        toggle.prop('disabled', false);
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * Execute for specific plugin - Show forms list with individual toggles
     */
    public function execute_for_plugin($plugin_name) {
        // Plugin slug to name mapping
        $plugin_slug_map = [
            'Contact Form 7' => 'contact-form-7',
            'WPForms' => 'wpforms',
            'Fluent Forms' => 'fluentform',
            'Formidable Forms' => 'formidable',
            'Elementor Pro (Forms)' => 'elementor-pro',
            'Forminator' => 'forminator',
        ];
        
        $plugin_key = isset($plugin_slug_map[$plugin_name]) ? $plugin_slug_map[$plugin_name] : '';
        
        // Get forms for this plugin
        $plugin_forms = [];
        if ($this->form_scanner && method_exists($this->form_scanner, 'scan_all_forms')) {
            $all_forms = $this->form_scanner->scan_all_forms();
            foreach ($all_forms as $form) {
                if (isset($form['plugin_key']) && $form['plugin_key'] === $plugin_key) {
                    $plugin_forms[] = $form;
                }
            }
        }
        
        // Get enabled forms for this plugin
        $enabled_forms = get_option('adhub_enabled_forms', array());
        if (!is_array($enabled_forms)) {
            $enabled_forms = array();
        }
        
        // DEBUG: Remove this after testing
        if (current_user_can('manage_options')) {
            echo '<!-- DEBUG: enabled_forms = ' . print_r($enabled_forms, true) . ' -->';
        }
        
        // Logo
        $logos = [
            'Contact Form 7' => 'cf7-logo.png',
            'WPForms' => 'wpforms-logo.png',
            'Formidable Forms' => 'formidable-form-logo.png',
            'Forminator' => 'forminator-form-logo.png',
            'Fluent Forms' => 'fluent-form-logo.png',
            'Elementor Pro (Forms)' => 'elementor-form-logo.png',
        ];
        ?>
        <div class="contact-connecting-sections">
            <div class="back-link">
                <a href="<?php echo admin_url('admin.php?page=adhub'); ?>" class="button button-secondary">
                    ← Back to Plugins
                </a>
            </div>
            
            <h2><?php echo esc_html($plugin_name); ?> Forms</h2>
            <p>Select which forms from <?php echo esc_html($plugin_name); ?> you want to connect to ADHUB.</p>
            
            <?php if (empty($plugin_forms)): ?>
                <div class="no-forms-message">
                    <p>No forms found for <?php echo esc_html($plugin_name); ?>. Please create a form first.</p>
                </div>
            <?php else: ?>
                <div class="form-plugins-list">
                    <?php foreach ($plugin_forms as $form): 
                        $form_key = (string) $plugin_key . '_' . (string) $form['id'];
                        $is_enabled = in_array($form_key, $enabled_forms, true);
                        ?>
                        <div class="form-plugin-item">
                            <div class="form-plugin-details">
                                <span class="form-name">
                                    <?php echo esc_html($form['title']); ?>
                                </span>
                            </div>
                            
                            <div class="form-info">
                                <span class="form-shortcode"><?php echo esc_html($form['shortcode'] ?? ''); ?></span>
                            </div>

                            <div class="form-status-toggle" style="display: flex !important; flex-direction: row !important; justify-content: space-between !important; width: 100% !important;">
                                <span class="status-text">
                                    <?php echo $is_enabled ? 'Active' : 'Inactive'; ?>
                                </span>
                                <label class="switch">
                                    <input type="checkbox" 
                                           class="form-enable-toggle" 
                                           data-form-key="<?php echo esc_attr($form_key); ?>"
                                           data-plugin="<?php echo esc_attr($plugin_name); ?>"
                                           <?php echo $is_enabled ? 'checked' : ''; ?>>
                                    <span class="slider round"></span>
                                </label>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Handle form enable/disable toggles
            $('.form-enable-toggle').on('change', function() {
                const formKey = $(this).data('form-key');
                const pluginName = $(this).data('plugin');
                const isEnabled = $(this).prop('checked');
                const toggle = $(this);

                toggle.prop('disabled', true);

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'adhub_toggle_form',
                        form_key: formKey,
                        enable: isEnabled,
                        security: adhubAdmin.forms_nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            const statusText = toggle.closest('.form-status-toggle').find('.status-text');
                            statusText.text(isEnabled ? 'Active' : 'Inactive');
                        } else {
                            toggle.prop('checked', !isEnabled);
                            alert('Failed to update form status');
                        }
                    },
                    error: function() {
                        toggle.prop('checked', !isEnabled);
                        alert('Failed to update form status');
                    },
                    complete: function() {
                        toggle.prop('disabled', false);
                    }
                });
            });
        });
        </script>
        
        <style>
        .back-link {
            margin-bottom: 20px;
        }
        .form-info {
            margin: 10px 0;
            padding: 10px;
            background: #f5f5f5;
            border-radius: 6px;
        }
        .form-shortcode {
            font-family: monospace;
            font-size: 13px;
            color: #666;
        }
        .no-forms-message {
            padding: 30px;
            text-align: center;
            background: #f5f5f5;
            border-radius: 8px;
            color: #666;
        }
        </style>
        <?php
    }
}
