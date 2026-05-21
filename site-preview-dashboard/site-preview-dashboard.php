<?php
/**
 * Plugin Name: Site Preview Dashboard
 * Plugin URI:  https://github.com/LasseAupperle/Site-Preview-Dashboard
 * Description: Display a visual grid of screenshot previews of your managed WordPress sites.
 * Version:     1.1.0
 * Author:      LaunchUp
 * License:     GPL-2.0-or-later
 * Text Domain: site-preview-dashboard
 */

defined( 'ABSPATH' ) or exit;

define( 'SPD_VERSION', '1.1.0' );
define( 'SPD_PATH',    plugin_dir_path( __FILE__ ) );
define( 'SPD_URL',     plugin_dir_url( __FILE__ ) );
define( 'SPD_CAP',     'manage_options' );

require_once SPD_PATH . 'includes/class-counter.php';
require_once SPD_PATH . 'includes/class-screenshot.php';
require_once SPD_PATH . 'includes/class-cron.php';
require_once SPD_PATH . 'includes/class-admin.php';
require_once SPD_PATH . 'includes/class-shortcode.php';

// Cron schedule must be registered early.
add_filter( 'cron_schedules', array( 'SPD_Cron', 'add_weekly_schedule' ) );
add_action( 'spd_weekly_refresh', array( 'SPD_Cron', 'refresh_all_sites' ) );

register_activation_hook( __FILE__, array( 'SPD_Cron', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'SPD_Cron', 'deactivate' ) );
register_uninstall_hook( __FILE__, 'spd_uninstall' );

function spd_uninstall() {
	global $wpdb;

	// Delete all spd_* options.
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'spd\_%'" );

	// Delete screenshot directory.
	$upload_dir   = wp_upload_dir();
	$previews_dir = $upload_dir['basedir'] . '/site-previews/';
	if ( is_dir( $previews_dir ) ) {
		$files = glob( $previews_dir . '*' );
		if ( is_array( $files ) ) {
			foreach ( $files as $file ) {
				if ( is_file( $file ) ) {
					unlink( $file );
				}
			}
		}
		@rmdir( $previews_dir );
	}
}

add_action( 'plugins_loaded', function () {
	new SPD_Admin();
	$shortcode = new SPD_Shortcode();
	$shortcode->register();
} );
