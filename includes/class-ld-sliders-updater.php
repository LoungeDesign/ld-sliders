<?php
/**
 * GitHub Update Checker for LD Sliders
 * Checks the LoungeDesign/ld-sliders GitHub repo for new releases
 * and surfaces the standard WordPress "Update Available" notice.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LD_Sliders_Updater {

	private $file;
	private $github_user;
	private $github_repo;
	private $plugin_data;
	private $github_response;
	private $plugin_slug;

	public function __construct( $file, $github_user, $github_repo ) {
		$this->file        = $file;
		$this->github_user = $github_user;
		$this->github_repo = $github_repo;
		$this->plugin_slug = plugin_basename( $file );

		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_update' ) );
		add_filter( 'plugins_api', array( $this, 'plugin_info' ), 10, 3 );
		add_filter( 'upgrader_post_install', array( $this, 'after_install' ), 10, 3 );
		add_action( 'admin_notices', array( $this, 'version_notice' ) );
	}

	private function get_plugin_data() {
		if ( empty( $this->plugin_data ) ) {
			$this->plugin_data = get_plugin_data( $this->file );
		}
		return $this->plugin_data;
	}

	private function get_github_release() {
		if ( ! empty( $this->github_response ) ) {
			return $this->github_response;
		}

		$transient_key = 'ld_sliders_github_release';
		$cached        = get_transient( $transient_key );
		if ( $cached ) {
			$this->github_response = $cached;
			return $cached;
		}

		$url      = "https://api.github.com/repos/{$this->github_user}/{$this->github_repo}/releases/latest";
		$response = wp_remote_get(
			$url,
			array(
				'headers' => array(
					'Accept'     => 'application/vnd.github.v3+json',
					'User-Agent' => 'WordPress/' . get_bloginfo( 'version' ) . '; ' . get_bloginfo( 'url' ),
				),
				'timeout' => 10,
			)
		);

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return false;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		// Skip pre-releases / betas
		if ( ! empty( $body['prerelease'] ) || ! empty( $body['draft'] ) ) {
			return false;
		}

		$this->github_response = $body;
		set_transient( $transient_key, $body, HOUR_IN_SECONDS * 6 );

		return $body;
	}

	public function check_update( $transient ) {
		if ( empty( $transient->checked ) ) {
			return $transient;
		}

		$release     = $this->get_github_release();
		$plugin_data = $this->get_plugin_data();

		if ( ! $release || empty( $release['tag_name'] ) ) {
			return $transient;
		}

		$latest_version  = ltrim( $release['tag_name'], 'v' );
		$current_version = $plugin_data['Version'];

		if ( version_compare( $latest_version, $current_version, '>' ) ) {
			$zip_url = '';
			if ( ! empty( $release['assets'] ) ) {
				foreach ( $release['assets'] as $asset ) {
					if ( substr( $asset['name'], -4 ) === '.zip' ) {
						$zip_url = $asset['browser_download_url'];
						break;
					}
				}
			}
			// Fallback to source ZIP
			if ( empty( $zip_url ) ) {
				$zip_url = "https://github.com/{$this->github_user}/{$this->github_repo}/archive/refs/tags/{$release['tag_name']}.zip";
			}

			$transient->response[ $this->plugin_slug ] = (object) array(
				'slug'        => dirname( $this->plugin_slug ),
				'plugin'      => $this->plugin_slug,
				'new_version' => $latest_version,
				'url'         => "https://github.com/{$this->github_user}/{$this->github_repo}",
				'package'     => $zip_url,
			);
		}

		return $transient;
	}

	public function plugin_info( $result, $action, $args ) {
		if ( $action !== 'plugin_information' ) {
			return $result;
		}
		if ( ! isset( $args->slug ) || $args->slug !== dirname( $this->plugin_slug ) ) {
			return $result;
		}

		$release     = $this->get_github_release();
		$plugin_data = $this->get_plugin_data();

		if ( ! $release ) {
			return $result;
		}

		return (object) array(
			'name'          => $plugin_data['Name'],
			'slug'          => dirname( $this->plugin_slug ),
			'version'       => ltrim( $release['tag_name'], 'v' ),
			'author'        => $plugin_data['Author'],
			'homepage'      => $plugin_data['PluginURI'],
			'requires'      => '5.8',
			'tested'        => '6.8',
			'last_updated'  => $release['published_at'],
			'sections'      => array(
				'description' => $plugin_data['Description'],
				'changelog'   => ! empty( $release['body'] ) ? nl2br( esc_html( $release['body'] ) ) : 'See GitHub for changelog.',
			),
			'download_link' => "https://github.com/{$this->github_user}/{$this->github_repo}/archive/refs/tags/{$release['tag_name']}.zip",
		);
	}

	public function after_install( $response, $hook_extra, $result ) {
		global $wp_filesystem;
		if ( empty( $hook_extra['plugin'] ) || $hook_extra['plugin'] !== $this->plugin_slug ) {
			return $result;
		}
		$install_directory = plugin_dir_path( $this->file );
		$wp_filesystem->move( $result['destination'], $install_directory );
		$result['destination'] = $install_directory;
		return $result;
	}

	/**
	 * Show an admin notice if Breakdance has updated beyond the last tested version.
	 */
	public function version_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( ! defined( 'BREAKDANCE_VERSION' ) ) {
			return;
		}
		$last_tested = '2.7.2';
		if ( version_compare( BREAKDANCE_VERSION, $last_tested, '>' ) ) {
			$dismissed = get_option( 'ld_sliders_bd_notice_dismissed', '' );
			if ( $dismissed === BREAKDANCE_VERSION ) {
				return;
			}
			echo '<div class="notice notice-warning is-dismissible" id="ld-sliders-bd-notice">
				<p><strong>LD Sliders:</strong> Breakdance has been updated to version ' . esc_html( BREAKDANCE_VERSION ) . '. The last tested version is ' . esc_html( $last_tested ) . '. The slider element should still work, but check your sliders and <a href="https://www.loungedesign.co.uk" target="_blank">contact Lounge Design</a> if you notice any issues.</p>
			</div>
			<script>
			document.addEventListener("DOMContentLoaded", function() {
				var n = document.getElementById("ld-sliders-bd-notice");
				if (n) {
					n.querySelector(".notice-dismiss") && n.querySelector(".notice-dismiss").addEventListener("click", function() {
						fetch("' . esc_url( admin_url( 'admin-ajax.php' ) ) . '?action=ld_dismiss_bd_notice&version=' . esc_js( BREAKDANCE_VERSION ) . '&_wpnonce=' . wp_create_nonce( 'ld_dismiss_bd' ) . '");
					});
				}
			});
			</script>';
		}
	}
}

// AJAX handler for dismissing the Breakdance version notice
add_action( 'wp_ajax_ld_dismiss_bd_notice', function() {
	check_ajax_referer( 'ld_dismiss_bd' );
	if ( current_user_can( 'manage_options' ) && ! empty( $_GET['version'] ) ) {
		update_option( 'ld_sliders_bd_notice_dismissed', sanitize_text_field( wp_unslash( $_GET['version'] ) ) );
	}
	wp_die();
} );
