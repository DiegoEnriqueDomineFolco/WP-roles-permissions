<?php

/**
 * The core plugin class.
 *
 */
class Roles_Permissions_Plugin
{

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Roles_Permissions_Loader    $loader    Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $plugin_title    The string used to uniquely identify this plugin.
     */
    protected $plugin_title;

    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $version    The current version of the plugin.
     */
    protected $version;

    /**
     * The mysqli database connection object instance.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $conn    The mysqli database connection object instance.
     */
    protected $conn;

    /*  ************************* CONSTRUCTOR **************************  */

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function __construct()
    {

        if (defined('ROLES_PERMISSIONS_VERSION')) {
            $this->version = ROLES_PERMISSIONS_VERSION;
        } else {
            $this->version = '1.0.0';
        }

        $this->plugin_title = 'Roles and Permissions';

        // Add required files to set_dependencies():
        $this->load_dependencies($this->set_dependencies());

        // Instantiate the Loader object:
        $this->loader = new Roles_Permissions_Loader();

        // Define the hooks and pass them to the Loader:
        $this->define_hooks();
    }

    /**
     * Sets the file dependencies.
     * Manually enter file paths here.
     *
     * @since    1.0.0
     * @access   private
     * @return   array       $dependencies       The files to load, relative
     *                                           to the plugin directory.
     */
    private function set_dependencies()
    {

        /* Manually enter file paths here.
        *  Paths must be relative to the plugin directory.
        *  No leading slash: 'admin/Assets.php', not '/admin/Assets.php'.
        */
        $dependencies = array(

            // Includes:
            'includes/Loader.php',

            // // Admin:
            'admin/roles-permissions-admin.php',
        );

        return $dependencies;
    }

    /**
     * Define all of the hooks for the admin area and public-facing side of the site.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_hooks()
    {
        $module = new Roles_Permissions_Admin($this->get_plugin_title(), $this->get_version());

        // Filtrar usuarios y roles en la pantalla de usuarios para site_manager
        $this->loader->add_action('pre_get_users', $module, 'filter_users_list_for_site_manager');
        $this->loader->add_filter('editable_roles', $module, 'filter_editable_roles_for_site_manager');
        $this->loader->add_action('user_register', $module, 'force_content_manager_role_on_create');

        // Remover los links de filtro por rol en la lista de usuarios para site_manager
        $this->loader->add_filter('views_users', $module, 'remove_role_filters_for_site_manager');

        // Redirigir acceso al dashboard para roles personalizados (usar current_screen para que get_current_screen() no sea null)
        $this->loader->add_action('current_screen', $module, 'restrict_dashboard_access');

        // Encolar CSS solo para los roles personalizados
        $this->loader->add_action('admin_enqueue_scripts', $module, 'enqueue_roles_permissions_assets');
        $this->loader->add_action('wp_enqueue_scripts', $module, 'enqueue_roles_permissions_assets');

        // Registrar roles y capacidades
        $this->loader->add_action('init', $module, 'register_roles');

        // Restricciones de gestión de usuarios
        $this->loader->add_action('init', $module, 'restrict_user_management');

        // Limpiar menú admin para roles personalizados (prioridad 99 para WooCommerce)
        $this->loader->add_action('admin_menu', $module, 'cleanup_admin_menu', 99);

        // Limpiar barra admin para roles personalizados
        $this->loader->add_action('wp_before_admin_bar_render', $module, 'cleanup_admin_bar');

        // Redirección de login según rol
        $this->loader->add_filter('login_redirect', $module, 'fix_login_redirect', 10, 3);
    }

    // ************************* UTILITY METHODS ************************* //

    // ********************* NOTE: NO NEED TO CHANGE ********************* //
    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    1.0.0
     */
    public function run()
    {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @since     1.0.0
     * @return    string    The name of the plugin.
     */
    public function get_plugin_title()
    {

        return $this->plugin_title;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @since     1.0.0
     * @return        Orchestrates the hooks of the plugin.
     */
    public function get_loader()
    {

        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @since     1.0.0
     * @return    string    The version number of the plugin.
     */
    public function get_version()
    {
        return $this->version;
    }

    // /**
    // * Loads all file dependencies.
    // * @since    1.0.0
    // * @access   private
    // */
    private function load_dependencies($files)
    {

        foreach ($files as $file) {
            require_once plugin_dir_path(__DIR__) . $file;
        }
    }
}
