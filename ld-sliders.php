<?php
/**
 * Plugin Name: LD Sliders
 * Plugin URI:  https://www.loungedesign.co.uk
 * Description: Create and manage beautiful, fully customisable sliders — no third-party dependencies.
 * Version:     1.2.0
 * Author:      Lounge Design
 * Author URI:  https://www.loungedesign.co.uk
 * License:     GPL-2.0+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: ld-sliders
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'LD_SLIDERS_VERSION', '1.2.0' );
define( 'LD_SLIDERS_PATH',    plugin_dir_path( __FILE__ ) );
define( 'LD_SLIDERS_URL',     plugin_dir_url( __FILE__ ) );
define( 'LD_SLIDERS_TABLE',   'ld_sliders' );
define( 'LD_SLIDERS_GITHUB_USER', 'LoungeDesign' );
define( 'LD_SLIDERS_GITHUB_REPO', 'ld-sliders' );

require_once LD_SLIDERS_PATH . 'includes/class-ld-sliders-db.php';
require_once LD_SLIDERS_PATH . 'includes/class-ld-sliders-assets.php';
require_once LD_SLIDERS_PATH . 'includes/class-ld-sliders-updater.php';
require_once LD_SLIDERS_PATH . 'admin/class-ld-sliders-admin.php';

register_activation_hook( __FILE__, array( 'LD_Sliders_DB', 'create_table' ) );
register_uninstall_hook( __FILE__,  array( 'LD_Sliders_DB', 'drop_table' ) );

function ld_sliders_init() {
	new LD_Sliders_Assets();
	if ( is_admin() ) new LD_Sliders_Admin();
}
add_action( 'plugins_loaded', 'ld_sliders_init' );
