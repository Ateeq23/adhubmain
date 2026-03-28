<?php
/**
 * Render Header - Renders the admin page header
 *
 * @package ADHUB
 * @subpackage ADHUB/admin/includes
 */

class RenderHeader {

    public function execute($logo_url = '') {
        ?>
        <div class="adhub-admin-wrap">
            <div class="adhub-settings-wrap">
                <div class="adhub-settings-header">
                    <a href="<?php echo admin_url('admin.php?page=adhub&view=forms'); ?>" class="adhub-logo-link">
                        <img src="<?php echo plugin_dir_url(__FILE__) . 'images/logo.png'; ?>" alt="ADHUB" class="adhub-logo">
                    </a>
                    <p><?php echo esc_html__('Connect your WordPress forms to ADHUB lead management system', 'adhub'); ?></p>
                </div>
        <?php
    }
}
