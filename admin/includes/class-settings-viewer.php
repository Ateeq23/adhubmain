<?php
/**
 * ADHUB Plugin Settings Viewer
 * 
 * Use this file to view your ADHUB API credentials stored in database.
 * Access via: Tools > ADHUB Settings
 *
 * @package ADHUB
 * @subpackage ADHUB/admin/includes
 */

// Prevent direct access
if (!defined('ABSPATH')) exit;

class Adhub_Settings_Viewer {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
    }

    public function add_admin_menu() {
        add_management_page(
            'ADHUB Settings',
            'ADHUB Settings',
            'manage_options',
            'adhub-settings-viewer',
            array($this, 'render_settings_page')
        );
    }

    public function render_settings_page() {
        // Handle form actions
        if (isset($_POST['adhub_action']) && check_admin_referer('adhub_actions', 'adhub_nonce')) {
            $action = sanitize_text_field($_POST['adhub_action']);
            
            if ($action === 'clear') {
                delete_option('adhub_api_key');
                delete_option('adhub_token');
                delete_option('adhub_tenant_id');
                delete_option('adhub_verification_status');
                delete_option('adhub_verified_at');
                delete_option('adhub_connected_forms');
                echo '<div class="notice notice-success"><p>All ADHUB settings cleared!</p></div>';
            } else {
                echo '<div class="notice notice-info"><p>Settings refreshed!</p></div>';
            }
        }
        
        ?>
        <div class="wrap">
            <h1>ADHUB Plugin Settings Viewer</h1>
            
            <div class="card" style="max-width: 600px; margin-top: 20px;">
                <h2>Your Current Settings</h2>
                
                <?php
                $options = [
                    'adhub_api_key' => 'API Key',
                    'adhub_token' => 'JWT Token',
                    'adhub_tenant_id' => 'Tenant ID',
                    'adhub_verification_status' => 'Verification Status',
                    'adhub_verified_at' => 'Verified At',
                    'adhub_connected_forms' => 'Connected Forms'
                ];
                
                echo '<table class="widefat" style="margin-top: 15px;">';
                echo '<thead><tr><th>Setting</th><th>Value</th></tr></thead>';
                echo '<tbody>';
                
                foreach ($options as $option_name => $label) {
                    $value = get_option($option_name);
                    
                    echo '<tr>';
                    echo '<td style="font-weight: bold;">' . esc_html($label) . '</td>';
                    echo '<td>';
                    
                    if ($value === false) {
                        echo '<em style="color: #999;">Not set</em>';
                    } elseif (is_array($value)) {
                        echo '<pre style="margin: 0; font-size: 11px;">' . esc_html(print_r($value, true)) . '</pre>';
                    } elseif (in_array($option_name, ['adhub_token'])) {
                        // Mask token for security
                        $masked = substr($value, 0, 20) . '...' . substr($value, -10);
                        echo esc_html($masked) . ' <span style="color: #999;">(Token hidden for security)</span>';
                    } else {
                        echo esc_html($value);
                    }
                    
                    echo '</td>';
                    echo '</tr>';
                }
                
                echo '</tbody>';
                echo '</table>';
                ?>
            </div>
            
            <div class="card" style="max-width: 600px; margin-top: 20px; background: #fff3cd; border-color: #ffc107;">
                <h2 style="color: #856404;">How to Get API Key</h2>
                <ol style="color: #856404;">
                    <li>Go to <strong>https://adhubapp.com</strong></li>
                    <li>Login to your account or create new one</li>
                    <li>Find <strong>API Settings</strong> in your dashboard</li>
                    <li>Copy your <strong>API Key</strong></li>
                    <li>Paste it in WordPress Admin > <strong>AdHub</strong> settings</li>
                    <li>Click <strong>Verify</strong> button</li>
                </ol>
            </div>
            
            <div class="card" style="max-width: 600px; margin-top: 20px;">
                <h2>Quick Actions</h2>
                <form method="post" style="margin: 15px 0;">
                    <?php wp_nonce_field('adhub_actions', 'adhub_nonce'); ?>
                    <button type="submit" name="adhub_action" value="refresh" class="button button-primary">
                        Refresh Settings
                    </button>
                    <button type="submit" name="adhub_action" value="clear" class="button button-secondary" onclick="return confirm('Are you sure? This will disconnect ADHUB.');">
                        Clear All Settings
                    </button>
                </form>
            </div>
        </div>
        
        <style>
            .card {
                background: #fff;
                border: 1px solid #ccd0d4;
                padding: 15px;
                border-radius: 4px;
            }
            .widefat td, .widefat th {
                padding: 10px;
            }
        </style>
        <?php
    }
}

// Initialize the settings viewer
new Adhub_Settings_Viewer();
