<?php
/**
 * Plugin Name: LD Sliders
 * Plugin URI:  https://www.loungedesign.co.uk
 * Description: Create and manage beautiful, fully customisable sliders — no third-party dependencies.
 * Version:     1.1.0
 * Author:      Lounge Design
 * Author URI:  https://www.loungedesign.co.uk
 * License:     GPL-2.0+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: ld-sliders
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'LD_SLIDERS_VERSION', '1.1.0' );
define( 'LD_SLIDERS_PATH', plugin_dir_path( __FILE__ ) );
define( 'LD_SLIDERS_URL', plugin_dir_url( __FILE__ ) );
define( 'LD_SLIDERS_TABLE', 'ld_sliders' );
define( 'LD_SLIDERS_GITHUB_USER', 'LoungeDesign' );
define( 'LD_SLIDERS_GITHUB_REPO', 'ld-sliders' );

require_once LD_SLIDERS_PATH . 'includes/class-ld-sliders-db.php';
require_once LD_SLIDERS_PATH . 'includes/class-ld-sliders-post-type.php';
require_once LD_SLIDERS_PATH . 'includes/class-ld-sliders-shortcode.php';
require_once LD_SLIDERS_PATH . 'includes/class-ld-sliders-assets.php';
require_once LD_SLIDERS_PATH . 'includes/class-ld-sliders-updater.php';
require_once LD_SLIDERS_PATH . 'admin/class-ld-sliders-admin.php';

register_activation_hook( __FILE__, array( 'LD_Sliders_DB', 'create_table' ) );
register_uninstall_hook( __FILE__, array( 'LD_Sliders_DB', 'drop_table' ) );

function ld_sliders_init() {
	new LD_Sliders_Post_Type();
	new LD_Sliders_Shortcode();
	new LD_Sliders_Assets();
	new LD_Sliders_Updater( __FILE__, LD_SLIDERS_GITHUB_USER, LD_SLIDERS_GITHUB_REPO );
	if ( is_admin() ) {
		new LD_Sliders_Admin();
	}
	// Breakdance integration
	add_action( 'breakdance_loaded', 'ld_sliders_register_breakdance_element' );
}
add_action( 'plugins_loaded', 'ld_sliders_init' );

function ld_sliders_register_breakdance_element() {
	$bd_file = LD_SLIDERS_PATH . 'breakdance/class-ld-sliders-breakdance.php';
	if ( file_exists( $bd_file ) ) {
		require_once $bd_file;
		if ( class_exists( 'LD_Sliders_Breakdance' ) ) {
			LD_Sliders_Breakdance::register();
		}
	}
}
