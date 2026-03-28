<?php
/**
 * Render Forms Section - Renders the forms management section
 *
 * @package ADHUB
 * @subpackage ADHUB/admin/includes
 */

class RenderFormsSection {

    public function execute($forms_scanner) {
        $installed_plugins = $forms_scanner->get_installed_plugins();
        $forms = $forms_scanner->scan_all_forms();
        $enabled_forms = $forms_scanner->get_enabled_forms();
        
        // Count enabled vs disabled
        $enabled_count = count($enabled_forms);
        $total_forms = count($forms);
        $disabled_count = $total_forms - $enabled_count;
        ?>
        
        <!-- Forms Management Section -->
        <div class="adhub-forms-section">
            <div class="adhub-section-header">
                <div class="adhub-section-title">
                    <h2>
                        <span class="dashicons dashicons-forms"></span>
                        <?php echo esc_html__('Manage Forms', 'adhub'); ?>
                    </h2>
                    <p class="adhub-section-description">
                        <?php echo esc_html__('Enable or disable forms to track their submissions in ADHUB.', 'adhub'); ?>
                    </p>
                </div>
                <div class="adhub-section-actions">
                    <button type="button" id="adhub-refresh-forms" class="adhub-btn adhub-btn-secondary">
                        <span class="dashicons dashicons-update"></span>
                        <?php echo esc_html__('Refresh', 'adhub'); ?>
                    </button>
                </div>
            </div>
            
            <!-- Stats Cards -->
            <div class="adhub-forms-stats">
                <div class="adhub-stat-card">
                    <div class="adhub-stat-icon">
                        <span class="dashicons dashicons-forms"></span>
                    </div>
                    <div class="adhub-stat-content">
                        <span class="adhub-stat-number"><?php echo esc_html($total_forms); ?></span>
                        <span class="adhub-stat-label"><?php echo esc_html__('Total Forms', 'adhub'); ?></span>
                    </div>
                </div>
                <div class="adhub-stat-card adhub-stat-card-success">
                    <div class="adhub-stat-icon">
                        <span class="dashicons dashicons-yes-alt"></span>
                    </div>
                    <div class="adhub-stat-content">
                        <span class="adhub-stat-number"><?php echo esc_html($enabled_count); ?></span>
                        <span class="adhub-stat-label"><?php echo esc_html__('Enabled', 'adhub'); ?></span>
                    </div>
                </div>
                <div class="adhub-stat-card adhub-stat-card-warning">
                    <div class="adhub-stat-icon">
                        <span class="dashicons dashicons-dismiss"></span>
                    </div>
                    <div class="adhub-stat-content">
                        <span class="adhub-stat-number"><?php echo esc_html($disabled_count); ?></span>
                        <span class="adhub-stat-label"><?php echo esc_html__('Disabled', 'adhub'); ?></span>
                    </div>
                </div>
            </div>
            
            <?php if (empty($installed_plugins)): ?>
                <!-- Empty State - No Plugins -->
                <div class="adhub-empty-state">
                    <div class="adhub-empty-icon">
                        <span class="dashicons dashicons-admin-plugins"></span>
                    </div>
                    <h3><?php echo esc_html__('No Form Plugins Detected', 'adhub'); ?></h3>
                    <p><?php echo esc_html__('Install and activate one or more form plugins to see them here.', 'adhub'); ?></p>
                    <a href="<?php echo esc_url(admin_url('plugin-install.php?tab=search&s=wordpress+forms')); ?>" class="adhub-btn adhub-btn-primary">
                        <?php echo esc_html__('Browse Form Plugins', 'adhub'); ?>
                    </a>
                    
                    <div class="adhub-supported-plugins">
                        <h4><?php echo esc_html__('Supported Form Plugins', 'adhub'); ?></h4>
                        <ul>
                            <li>Contact Form 7</li>
                            <li>WPForms</li>
                            <li>Gravity Forms</li>
                            <li>Formidable Forms</li>
                            <li>Fluent Forms</li>
                            <li>Ninja Forms</li>
                            <li>Caldera Forms</li>
                            <li>Everest Forms</li>
                            <li>Forminator</li>
                            <li>Elementor Pro</li>
                        </ul>
                    </div>
                </div>
            <?php elseif (empty($forms)): ?>
                <!-- Empty State - No Forms -->
                <div class="adhub-empty-state">
                    <div class="adhub-empty-icon">
                        <span class="dashicons dashicons-forms"></span>
                    </div>
                    <h3><?php echo esc_html__('No Forms Found', 'adhub'); ?></h3>
                    <p><?php echo esc_html__('Create some forms in your form plugins to see them here.', 'adhub'); ?></p>
                </div>
            <?php else: ?>
                <!-- Forms Table -->
                <div class="adhub-forms-table-container">
                    <table class="adhub-forms-table">
                        <thead>
                            <tr>
                                <th class="adhub-col-status"><?php echo esc_html__('Status', 'adhub'); ?></th>
                                <th class="adhub-col-form"><?php echo esc_html__('Form Name', 'adhub'); ?></th>
                                <th class="adhub-col-plugin"><?php echo esc_html__('Plugin', 'adhub'); ?></th>
                                <th class="adhub-col-shortcode"><?php echo esc_html__('Shortcode', 'adhub'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            // Group forms by plugin
                            $forms_by_plugin = array();
                            foreach ($forms as $form) {
                                $plugin_key = $form['plugin_key'];
                                if (!isset($forms_by_plugin[$plugin_key])) {
                                    $forms_by_plugin[$plugin_key] = array(
                                        'name' => $form['plugin_name'],
                                        'forms' => array()
                                    );
                                }
                                $forms_by_plugin[$plugin_key]['forms'][] = $form;
                            }
                            
                            foreach ($forms_by_plugin as $plugin_key => $plugin_data): 
                            ?>
                                <!-- Plugin Header -->
                                <tr class="adhub-plugin-header-row">
                                    <td colspan="4">
                                        <div class="adhub-plugin-header">
                                            <span class="adhub-plugin-badge">
                                                <span class="dashicons dashicons-forms"></span>
                                                <?php echo esc_html($plugin_data['name']); ?>
                                            </span>
                                            <span class="adhub-forms-count">(<?php echo count($plugin_data['forms']); ?> <?php echo esc_html__('forms', 'adhub'); ?>)</span>
                                        </div>
                                    </td>
                                </tr>
                                <?php foreach ($plugin_data['forms'] as $form): 
                                    $is_enabled = in_array($form['form_key'], $enabled_forms);
                                ?>
                                    <tr class="adhub-form-row" data-form-key="<?php echo esc_attr($form['form_key']); ?>">
                                        <td class="adhub-col-status">
                                            <label class="adhub-toggle">
                                                <input type="checkbox" 
                                                       class="adhub-form-toggle" 
                                                       data-form-key="<?php echo esc_attr($form['form_key']); ?>"
                                                       <?php checked($is_enabled); ?>>
                                                <span class="adhub-toggle-slider"></span>
                                            </label>
                                        </td>
                                        <td class="adhub-col-form">
                                            <div class="adhub-form-name">
                                                <strong><?php echo esc_html($form['title']); ?></strong>
                                                <span class="adhub-form-id">ID: <?php echo esc_html($form['id']); ?></span>
                                            </div>
                                        </td>
                                        <td class="adhub-col-plugin">
                                            <?php echo esc_html($form['plugin_name']); ?>
                                        </td>
                                        <td class="adhub-col-shortcode">
                                            <code class="adhub-shortcode"><?php echo esc_html($form['shortcode']); ?></code>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        </div> <!-- End wrap -->
        
        <!-- Toast Container -->
        <div class="adhub-toast-container" id="adhub-toast-container"></div>
        
        <?php
    }
}
