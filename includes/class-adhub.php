<?php
/**
 * The core plugin class
 *
 * @package ADHUB
 * @subpackage ADHUB/includes
 */

class Adhub {

    /**
     * The loader that's responsible for maintaining and registering all hooks
     *
     * @since    1.0.0
     * @access   protected
     * @var      Adhub_Loader    $loader    Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $plugin_name    The string used to uniquely identify this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $version    The current version of the plugin.
     */
    protected $version;

    /**
     * Define the core functionality of the plugin
     *
     * @since    1.0.0
     */
    public function __construct() {
        $this->plugin_name = 'adhub';
        $this->version = ADHUB_VERSION;

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    /**
     * Load the required dependencies for this plugin
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies() {
        /**
         * The class responsible for orchestrating the actions and filters of the core plugin
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-adhub-loader.php';

        /**
         * The class responsible for defining internationalization functionality
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-adhub-i18n.php';

        /**
         * The class responsible for defining all actions that occur in the admin area
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-adhub-admin.php';

        /**
         * The class responsible for defining all actions that occur in the public-facing side
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'public/class-adhub-public.php';

        $this->loader = new Adhub_Loader();
    }

    /**
     * Define the locale for this plugin for internationalization
     *
     * @since    1.0.0
     * @access   private
     */
    private function set_locale() {
        $plugin_i18n = new Adhub_i18n();

        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
    }

    /**
     * Register all of the hooks related to the admin area functionality
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks() {
        $plugin_admin = new Adhub_Admin($this->get_plugin_name(), $this->get_version());

        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
        $this->loader->add_action('admin_menu', $plugin_admin, 'add_plugin_admin_menu');
        
        // Add action link to plugins page
        $plugin_basename = plugin_basename(dirname(__FILE__) . '/../adhub.php');
        $this->loader->add_filter('plugin_action_links_' . $plugin_basename, $plugin_admin, 'add_action_links');
    }

    /**
     * Register all of the hooks related to the public-facing functionality
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_public_hooks() {
        $plugin_public = new Adhub_Public($this->get_plugin_name(), $this->get_version());

        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
        $this->loader->add_action('init', $plugin_public, 'register_shortcodes');
    }

    /**
     * Run the loader to execute all of the hooks with WordPress
     *
     * @since    1.0.0
     */
    public function run() {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it
     *
     * @since     1.0.0
     * @return    string    The name of the plugin
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin
     *
     * @since     1.0.0
     * @return    Adhub_Loader    Orchestrates the hooks of the plugin
     */
    public function get_loader() {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin
     *
     * @since     1.0.0
     * @return    string    The version number of the plugin
     */
    public function get_version() {
        return $this->version;
    }
}
