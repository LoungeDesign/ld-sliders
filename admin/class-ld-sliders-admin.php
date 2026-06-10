<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LD_Sliders_Admin {

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
		add_action( 'admin_post_ld_slider_save', array( $this, 'handle_save' ) );
		add_action( 'admin_post_ld_slider_delete', array( $this, 'handle_delete' ) );
	}

	public function register_menu() {
		add_menu_page( 'LD Sliders', 'Sliders', 'manage_options', 'ld-sliders', array( $this, 'page_list' ), 'dashicons-slides', 25 );
		add_submenu_page( 'ld-sliders', 'All Sliders', 'All Sliders', 'manage_options', 'ld-sliders', array( $this, 'page_list' ) );
		add_submenu_page( 'ld-sliders', 'Add New Slider', 'Add New', 'manage_options', 'ld-sliders-new', array( $this, 'page_edit' ) );
	}

	public function enqueue( $hook ) {
		if ( strpos( $hook, 'ld-sliders' ) === false ) return;
		wp_enqueue_style(  'ld-sliders-admin', LD_SLIDERS_URL . 'admin/css/ld-sliders-admin.css', array(), LD_SLIDERS_VERSION );
		wp_enqueue_script( 'ld-sliders-admin', LD_SLIDERS_URL . 'admin/js/ld-sliders-admin.js',  array(), LD_SLIDERS_VERSION, true );
	}

	public function page_list() {
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Insufficient permissions.' );
		$sliders = LD_Sliders_DB::get_all();
		include LD_SLIDERS_PATH . 'admin/views/list.php';
	}

	public function page_edit() {
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Insufficient permissions.' );
		$id       = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		$slider   = $id ? LD_Sliders_DB::get( $id ) : null;
		$defaults = LD_Sliders_DB::default_settings();
		$settings = $slider ? wp_parse_args( json_decode( $slider->settings, true ), $defaults ) : $defaults;
		include LD_SLIDERS_PATH . 'admin/views/edit.php';
	}

	public function handle_save() {
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Insufficient permissions.' );
		check_admin_referer( 'ld_slider_save', '_ld_nonce' );
		$id   = isset( $_POST['slider_id'] ) ? absint( $_POST['slider_id'] ) : 0;
		$name = isset( $_POST['slider_name'] ) ? sanitize_text_field( wp_unslash( $_POST['slider_name'] ) ) : '';
		if ( empty( $name ) ) {
			wp_safe_redirect( add_query_arg( array( 'page' => 'ld-sliders', 'error' => 'name_required' ), admin_url( 'admin.php' ) ) );
			exit;
		}
		$settings    = $this->sanitize_settings( isset( $_POST['settings'] ) ? wp_unslash( $_POST['settings'] ) : array() );
		$redirect_id = $id ? $id : LD_Sliders_DB::insert( $name, $settings );
		if ( $id ) LD_Sliders_DB::update( $id, $name, $settings );
		wp_safe_redirect( add_query_arg( array( 'page' => 'ld-sliders-new', 'id' => $redirect_id, 'saved' => 1 ), admin_url( 'admin.php' ) ) );
		exit;
	}

	public function handle_delete() {
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Insufficient permissions.' );
		$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		check_admin_referer( 'ld_slider_delete_' . $id );
		LD_Sliders_DB::delete( $id );
		wp_safe_redirect( add_query_arg( array( 'page' => 'ld-sliders', 'deleted' => 1 ), admin_url( 'admin.php' ) ) );
		exit;
	}

	private function sanitize_settings( $raw ) {
		if ( ! is_array( $raw ) ) return LD_Sliders_DB::default_settings();
		$d = LD_Sliders_DB::default_settings();
		$s = array();

		// Booleans
		foreach ( array(
			'autoPlay','pauseAutoPlayOnHover','wrapAround','freeScroll','prevNextButtons','pageDots',
			'contain','groupCells','rightToLeft','adaptiveHeight','accessibility','setGallerySize',
			'watchCSS','resize','percentPosition','overflowVisible','imagesLoaded','lazyLoad',
			'tablet_groupCells','mobile_groupCells',
			'overlayLeft','overlayRight',
		) as $k ) {
			$s[$k] = !empty($raw[$k]);
		}

		// Integers
		foreach ( array('autoPlaySpeed','dragThreshold','groupCellsCount','initialIndex',
			'tablet_groupCellsCount','mobile_groupCellsCount','lazyLoadCount',
			'overlayLeftOpacity','overlayLeftWidth','overlayRightOpacity','overlayRightWidth') as $k ) {
			$s[$k] = isset($raw[$k]) ? absint($raw[$k]) : $d[$k];
		}

		// Floats
		foreach ( array('selectedAttraction','friction') as $k ) {
			$s[$k] = isset($raw[$k]) ? floatval($raw[$k]) : $d[$k];
		}

		// Numeric sizes
		foreach ( array('cellWidth','cellHeight','cellGap',
			'tablet_cellWidth','tablet_cellHeight','tablet_cellGap',
			'mobile_cellWidth','mobile_cellHeight','mobile_cellGap') as $k ) {
			$v = isset($raw[$k]) ? floatval($raw[$k]) : '';
			$s[$k] = ($v === 0.0) ? '' : $v;
		}

		// Units
		$allowed_units = array('px','%','vw','vh','em','rem');
		foreach ( array('cellWidthUnit','cellHeightUnit','cellGapUnit') as $k ) {
			$s[$k] = (isset($raw[$k]) && in_array($raw[$k],$allowed_units,true)) ? $raw[$k] : 'px';
		}

		// Enums
		$s['cellAlign']   = (isset($raw['cellAlign'])   && in_array($raw['cellAlign'],array('left','center','right'),true)) ? $raw['cellAlign'] : 'left';
		$s['arrowShape']  = (isset($raw['arrowShape'])  && in_array($raw['arrowShape'],array('default','custom'),true))     ? $raw['arrowShape'] : 'default';

		// Text
		$s['arrowShapeCustom'] = isset($raw['arrowShapeCustom']) ? sanitize_text_field($raw['arrowShapeCustom']) : '';
		$s['asNavFor']         = isset($raw['asNavFor'])         ? sanitize_text_field($raw['asNavFor'])         : '';

		// Colours
		$s['overlayLeftColor']  = isset($raw['overlayLeftColor'])  ? (sanitize_hex_color($raw['overlayLeftColor'])  ?: '#ffffff') : '#ffffff';
		$s['overlayRightColor'] = isset($raw['overlayRightColor']) ? (sanitize_hex_color($raw['overlayRightColor']) ?: '#ffffff') : '#ffffff';

		// Custom CSS
		$s['customCSS'] = isset($raw['customCSS']) ? wp_strip_all_tags($raw['customCSS']) : '';

		return $s;
	}
}
