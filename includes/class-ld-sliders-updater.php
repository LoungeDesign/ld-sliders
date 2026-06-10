<?php
/**
 * LD Sliders — GitHub Update Checker
 * Lounge Design (loungedesign.co.uk)
 *
 * Hooks directly into WordPress's transient-based update system.
 * Never caches the GitHub response independently — always defers
 * to WordPress's own update cycle so force-checks always work.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class LD_Sliders_Updater {

	private $file;
	private $plugin_slug;
	private $plugin_data;
	private $github_user;
	private $github_repo;
	private $api_url;

	public function __construct( $file, $github_user, $github_repo ) {
		$this->file        = $file;
		$this->github_user = $github_user;
		$this->github_repo = $github_repo;
		$this->plugin_slug = plugin_basename( $file );
		$this->api_url     = 'https://api.github.com/repos/' . $github_user . '/' . $github_repo . '/releases/latest';

		// Hook into the transient WordPress sets when it checks for updates.
		// This fires every time WP checks — including forced checks.
		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_for_update' ) );

		// Provide plugin info for the "View version x.x details" popup
		add_filter( 'plugins_api', array( $this, 'plugin_info' ), 20, 3 );

		// Fix folder name after install — GitHub ZIPs extract with wrong folder name
		add_filter( 'upgrader_post_install', array( $this, 'fix_folder' ), 10, 3 );

		// Show Breakdance version warning if needed
		add_action( 'admin_notices', array( $this, 'version_notice' ) );
	}

	/**
	 * Fetch the latest release from GitHub.
	 * No caching — called fresh every time WP checks for updates.
	 */
	private function fetch_release() {
		$response = wp_remote_get(
			$this->api_url,
			array(
				'timeout'    => 10,
				'user-agent' => 'WordPress/' . get_bloginfo('version') . '; ' . get_bloginfo('url'),
				'headers'    => array( 'Accept' => 'application/vnd.github.v3+json' ),
			)
		);

		if ( is_wp_error( $response ) ) return false;
		if ( wp_remote_retrieve_response_code( $response ) !== 200 ) return false;

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( empty( $body ) || ! is_array( $body ) ) return false;

		// Skip pre-releases and drafts
		if ( ! empty( $body['prerelease'] ) || ! empty( $body['draft'] ) ) return false;

		return $body;
	}

	/**
	 * Get the download URL from the release.
	 * Prefers an attached ZIP asset; falls back to GitHub's auto-generated source ZIP.
	 */
	private function get_zip_url( $release ) {
		// Look for an attached .zip asset first
		if ( ! empty( $release['assets'] ) ) {
			foreach ( $release['assets'] as $asset ) {
				if ( substr( $asset['name'], -4 ) === '.zip' ) {
					return $asset['browser_download_url'];
				}
			}
		}
		// Fall back to GitHub's auto-generated source ZIP
		return 'https://github.com/' . $this->github_user . '/' . $this->github_repo
			. '/archive/refs/tags/' . $release['tag_name'] . '.zip';
	}

	/**
	 * Main update check — fires every time WordPress checks for plugin updates.
	 */
	public function check_for_update( $transient ) {
		// WordPress passes an empty object before it's populated — bail early
		if ( empty( $transient->checked ) ) return $transient;

		$release = $this->fetch_release();
		if ( ! $release || empty( $release['tag_name'] ) ) return $transient;

		$latest_version  = ltrim( $release['tag_name'], 'v' );
		$current_version = $this->get_plugin_data()['Version'];

		if ( version_compare( $latest_version, $current_version, '>' ) ) {
			$transient->response[ $this->plugin_slug ] = (object) array(
				'id'          => $this->plugin_slug,
				'slug'        => dirname( $this->plugin_slug ),
				'plugin'      => $this->plugin_slug,
				'new_version' => $latest_version,
				'url'         => 'https://github.com/' . $this->github_user . '/' . $this->github_repo,
				'package'     => $this->get_zip_url( $release ),
				'icons'       => array(),
				'banners'     => array(),
				'tested'      => '6.8',
				'requires_php'=> '7.4',
			);
		} else {
			// Explicitly mark as no update needed so WP doesn't show stale notices
			$transient->no_update[ $this->plugin_slug ] = (object) array(
				'id'          => $this->plugin_slug,
				'slug'        => dirname( $this->plugin_slug ),
				'plugin'      => $this->plugin_slug,
				'new_version' => $current_version,
				'url'         => 'https://github.com/' . $this->github_user . '/' . $this->github_repo,
				'package'     => '',
			);
		}

		return $transient;
	}

	/**
	 * Populate the plugin info popup (View version details).
	 */
	public function plugin_info( $result, $action, $args ) {
		if ( $action !== 'plugin_information' ) return $result;
		if ( empty( $args->slug ) || $args->slug !== dirname( $this->plugin_slug ) ) return $result;

		$release = $this->fetch_release();
		if ( ! $release ) return $result;

		$data = $this->get_plugin_data();

		return (object) array(
			'name'          => $data['Name'],
			'slug'          => dirname( $this->plugin_slug ),
			'version'       => ltrim( $release['tag_name'], 'v' ),
			'author'        => '<a href="https://www.loungedesign.co.uk">Lounge Design</a>',
			'homepage'      => 'https://www.loungedesign.co.uk',
			'requires'      => '5.8',
			'tested'        => '6.8',
			'requires_php'  => '7.4',
			'last_updated'  => $release['published_at'] ?? '',
			'sections'      => array(
				'description' => $data['Description'],
				'changelog'   => ! empty( $release['body'] )
					? '<pre>' . esc_html( $release['body'] ) . '</pre>'
					: 'See GitHub for full changelog.',
			),
			'download_link' => $this->get_zip_url( $release ),
		);
	}

	/**
	 * After install, rename the extracted folder to match the plugin slug.
	 * GitHub ZIPs extract as "ld-sliders-1.2.1" instead of "ld-sliders".
	 */
	public function fix_folder( $response, $hook_extra, $result ) {
		if ( empty( $hook_extra['plugin'] ) || $hook_extra['plugin'] !== $this->plugin_slug ) {
			return $result;
		}

		global $wp_filesystem;

		$correct_folder = WP_PLUGIN_DIR . '/' . dirname( $this->plugin_slug );

		// If extracted to a different folder name, move it to the correct one
		if ( $result['destination'] !== $correct_folder ) {
			if ( $wp_filesystem->exists( $correct_folder ) ) {
				$wp_filesystem->delete( $correct_folder, true );
			}
			$wp_filesystem->move( $result['destination'], $correct_folder );
			$result['destination']       = $correct_folder;
			$result['destination_name']  = dirname( $this->plugin_slug );
		}

		return $result;
	}

	/**
	 * Admin notice if Breakdance updates beyond last tested version.
	 */
	public function version_notice() {
		if ( ! current_user_can( 'manage_options' ) ) return;
		if ( ! defined( 'BREAKDANCE_VERSION' ) ) return;

		$last_tested = '2.7.2';
		if ( ! version_compare( BREAKDANCE_VERSION, $last_tested, '>' ) ) return;

		$dismissed = get_option( 'ld_sliders_bd_notice_dismissed', '' );
		if ( $dismissed === BREAKDANCE_VERSION ) return;

		echo '<div class="notice notice-warning is-dismissible" id="ld-bd-notice">
			<p><strong>LD Sliders:</strong> Breakdance has updated to ' . esc_html( BREAKDANCE_VERSION ) . '. Last tested version was ' . esc_html( $last_tested ) . '. Check your sliders and <a href="https://www.loungedesign.co.uk" target="_blank">contact Lounge Design</a> if anything looks off.</p>
		</div>
		<script>
		(function(){
			var n = document.getElementById("ld-bd-notice");
			if(!n) return;
			n.addEventListener("click", function(e){
				if(!e.target.classList.contains("notice-dismiss")) return;
				fetch("' . esc_url( admin_url('admin-ajax.php') ) . '?action=ld_dismiss_bd_notice&version=' . esc_js( BREAKDANCE_VERSION ) . '&_wpnonce=' . wp_create_nonce('ld_dismiss_bd') . '");
			});
		})();
		</script>';
	}

	private function get_plugin_data() {
		if ( empty( $this->plugin_data ) ) {
			$this->plugin_data = get_plugin_data( $this->file );
		}
		return $this->plugin_data;
	}
}

// Dismiss handler
add_action( 'wp_ajax_ld_dismiss_bd_notice', function() {
	check_ajax_referer( 'ld_dismiss_bd' );
	if ( current_user_can('manage_options') && ! empty( $_GET['version'] ) ) {
		update_option( 'ld_sliders_bd_notice_dismissed', sanitize_text_field( wp_unslash( $_GET['version'] ) ) );
	}
	wp_die();
});
