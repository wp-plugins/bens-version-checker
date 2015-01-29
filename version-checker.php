<?php

/**
 * This file is read by WordPress to generate the plugin information in the plugin
 * Dashboard. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @since             1.0.0
 * @package           Version_Checker
 *
 * @wordpress-plugin
 * Plugin Name:       Ben's Version Checker
 * Description:       Checks the version numbers of wordpress sites.
 * Version:           1.0.0
 * Author:            Ben Alderson
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       version-checker
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-version-checker-activator.php
 */
function activate_version_checker() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-version-checker-activator.php';
	Version_Checker_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-version-checker-deactivator.php
 */
function deactivate_version_checker() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-version-checker-deactivator.php';
	Version_Checker_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_version_checker' );
register_deactivation_hook( __FILE__, 'deactivate_version_checker' );

/**
 * The core plugin class that is used to define internationalization,
 * dashboard-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-version-checker.php';

/**
 * Begins execution of the plugin.
 *
 * @since    1.0.0
 */
function run_version_checker() {

	$plugin = new Version_Checker();
	$plugin->run();

}
run_version_checker();
