<?php

/**
 * Plugin Name: Roles & Permissions
 * Description: Plugin para gestionar roles y permisos personalizados. 'Admin - AGRO51' (site_manager) y 'Content - AGRO51' (content_manager).
 * Version: 1.0.0
 * Author: Diego & Chaty & Copi & Dios
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Current plugin version.
 * Rename this for your plugin and update it as you release new versions.
 */
define('ROLES_PERMISSIONS_VERSION', '1.0.0');

/*
* PLUGIN BASE DIR constant.
*/
define('ROLES_PERMISSIONS_DIR', plugin_dir_path(__FILE__));

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__) . 'includes/Main.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_roles_permissions()
{

    $plugin = new Roles_Permissions_Plugin();
    $plugin->run();
}

// Kick off the plugin.
run_roles_permissions();
