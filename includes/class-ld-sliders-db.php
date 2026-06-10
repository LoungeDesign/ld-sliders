<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LD_Sliders_DB {

	public static function create_table() {
		global $wpdb;
		$table   = $wpdb->prefix . LD_SLIDERS_TABLE;
		$charset = $wpdb->get_charset_collate();
		$sql     = "CREATE TABLE IF NOT EXISTS {$table} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			name VARCHAR(255) NOT NULL DEFAULT '',
			slug VARCHAR(255) NOT NULL DEFAULT '',
			settings LONGTEXT NOT NULL DEFAULT '',
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY slug (slug)
		) {$charset};";
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
		update_option( 'ld_sliders_db_version', LD_SLIDERS_VERSION );
	}

	public static function drop_table() {
		global $wpdb;
		$table = $wpdb->prefix . LD_SLIDERS_TABLE;
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( "DROP TABLE IF EXISTS {$table}" );
		delete_option( 'ld_sliders_db_version' );
	}

	public static function get_all() {
		global $wpdb;
		$table = $wpdb->prefix . LD_SLIDERS_TABLE;
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_results( "SELECT * FROM {$table} ORDER BY created_at DESC" );
	}

	public static function get( $id ) {
		global $wpdb;
		$table = $wpdb->prefix . LD_SLIDERS_TABLE;
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", (int) $id ) );
	}

	public static function get_by_slug( $slug ) {
		global $wpdb;
		$table = $wpdb->prefix . LD_SLIDERS_TABLE;
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE slug = %s", sanitize_key( $slug ) ) );
	}

	public static function insert( $name, $settings = array() ) {
		global $wpdb;
		$table = $wpdb->prefix . LD_SLIDERS_TABLE;
		$slug  = self::unique_slug( sanitize_title( $name ) );
		$wpdb->insert(
			$table,
			array(
				'name'     => sanitize_text_field( $name ),
				'slug'     => $slug,
				'settings' => wp_json_encode( $settings ),
			),
			array( '%s', '%s', '%s' )
		);
		return $wpdb->insert_id;
	}

	public static function update( $id, $name, $settings ) {
		global $wpdb;
		$table = $wpdb->prefix . LD_SLIDERS_TABLE;
		return $wpdb->update(
			$table,
			array(
				'name'     => sanitize_text_field( $name ),
				'settings' => wp_json_encode( $settings ),
			),
			array( 'id' => (int) $id ),
			array( '%s', '%s' ),
			array( '%d' )
		);
	}

	public static function delete( $id ) {
		global $wpdb;
		$table = $wpdb->prefix . LD_SLIDERS_TABLE;
		return $wpdb->delete( $table, array( 'id' => (int) $id ), array( '%d' ) );
	}

	private static function unique_slug( $slug ) {
		global $wpdb;
		$table    = $wpdb->prefix . LD_SLIDERS_TABLE;
		$original = $slug;
		$counter  = 1;
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		while ( $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE slug = %s", $slug ) ) ) {
			$slug = $original . '-' . $counter;
			$counter++;
		}
		return $slug;
	}

	public static function default_settings() {
		return array(
			// ── Behaviour
			'autoPlay'               => false,
			'autoPlaySpeed'          => 3000,
			'pauseAutoPlayOnHover'   => true,
			'wrapAround'             => true,
			'freeScroll'             => false,
			'dragThreshold'          => 3,
			'selectedAttraction'     => 0.025,
			'friction'               => 0.28,
			'resize'                 => true,
			// ── Navigation
			'prevNextButtons'        => true,
			'pageDots'               => true,
			'arrowShape'             => 'default',
			'arrowShapeCustom'       => '',
			// ── Layout
			'cellAlign'              => 'left',
			'contain'                => true,
			'groupCells'             => false,
			'groupCellsCount'        => 1,
			'initialIndex'           => 0,
			'rightToLeft'            => false,
			'adaptiveHeight'         => false,
			'percentPosition'        => false,
			'overflowVisible'        => false,
			// ── Images
			'imagesLoaded'           => true,
			'lazyLoad'               => false,
			'lazyLoadCount'          => 1,
			// ── Sync
			'asNavFor'               => '',
			// ── Accessibility
			'accessibility'          => true,
			'setGallerySize'         => true,
			'watchCSS'               => false,
			// ── Cell sizing — desktop
			'cellWidth'              => '',
			'cellHeight'             => '',
			'cellWidthUnit'          => 'px',
			'cellHeightUnit'         => 'px',
			// ── Gaps
			'cellGap'                => 16,
			'cellGapUnit'            => 'px',
			// ── Responsive — tablet (max 1024px)
			'tablet_cellWidth'       => '',
			'tablet_cellHeight'      => '',
			'tablet_cellGap'         => '',
			'tablet_groupCells'      => false,
			'tablet_groupCellsCount' => 1,
			// ── Responsive — mobile (max 767px)
			'mobile_cellWidth'       => '',
			'mobile_cellHeight'      => '',
			'mobile_cellGap'         => '',
			'mobile_groupCells'      => false,
			'mobile_groupCellsCount' => 1,
			// ── Left overlay
			'overlayLeft'            => false,
			'overlayLeftColor'       => '#ffffff',
			'overlayLeftOpacity'     => 100,
			'overlayLeftWidth'       => 120,
			// ── Right overlay
			'overlayRight'           => false,
			'overlayRightColor'      => '#ffffff',
			'overlayRightOpacity'    => 100,
			'overlayRightWidth'      => 120,
			// ── Custom CSS
			'customCSS'              => '',
		);
	}
}
