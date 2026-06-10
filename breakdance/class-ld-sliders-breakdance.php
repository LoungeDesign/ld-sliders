<?php
/**
 * LD Sliders — Breakdance Element
 * Compatible with Breakdance 2.7.x
 * Gracefully degrades if Breakdance API changes.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LD_Sliders_Breakdance {

	const LAST_TESTED_BD = '2.7.2';

	public static function register() {
		// Safety check — ensure the Breakdance element API exists
		if ( ! function_exists( '\Breakdance\Elements\register_element_from_class' ) ) {
			return;
		}
		try {
			\Breakdance\Elements\register_element_from_class( 'LD_Sliders_Element' );
		} catch ( \Throwable $e ) {
			// Breakdance API changed — fail silently, shortcode still works
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'LD Sliders: Breakdance element registration failed — ' . $e->getMessage() );
			}
		}
	}
}

/**
 * The Breakdance element class.
 * Uses the EssentialElement base which is available in Breakdance 2.x.
 */
if (
	class_exists( '\Breakdance\Elements\Element' ) ||
	interface_exists( '\Breakdance\Elements\Element' )
) :

class LD_Sliders_Element extends \Breakdance\Elements\Element {

	/* ─── Identity ─────────────────────────────────────────── */

	public static function tag() {
		return 'ld-slider';
	}

	public static function tagCategory() {
		return 'Interactive';
	}

	public static function name() {
		return 'LD Slider';
	}

	public static function icon() {
		return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="2" y="6" width="20" height="12" rx="2"/><polyline points="9 10 5 12 9 14"/><polyline points="15 10 19 12 15 14"/><line x1="12" y1="9" x2="12" y2="15"/></svg>';
	}

	public static function template() {
		return file_get_contents( LD_SLIDERS_PATH . 'breakdance/templates/slider.html' );
	}

	/* ─── Children ─────────────────────────────────────────── */

	public static function childType() {
		return 'ld-slide';
	}

	public static function allowChildren() {
		return true;
	}

	public static function defaultChildren() {
		return array(
			array(
				'type' => 'ld-slide',
				'children' => array(
					array( 'type' => 'text', 'properties' => array( 'content' => array( 'text' => 'Slide 1' ) ) ),
				),
			),
			array(
				'type' => 'ld-slide',
				'children' => array(
					array( 'type' => 'text', 'properties' => array( 'content' => array( 'text' => 'Slide 2' ) ) ),
				),
			),
		);
	}

	/* ─── Properties / Settings ────────────────────────────── */

	public static function getSettingsSchema() {
		return array(

			// ── Behaviour tab
			array(
				'label' => 'Behaviour',
				'type'  => 'section',
				'children' => array(
					array(
						'label'   => 'Wrap Around',
						'slug'    => 'wrapAround',
						'type'    => 'toggle',
						'default' => true,
					),
					array(
						'label'   => 'Auto Play',
						'slug'    => 'autoPlay',
						'type'    => 'toggle',
						'default' => false,
					),
					array(
						'label'      => 'Auto Play Speed (ms)',
						'slug'       => 'autoPlaySpeed',
						'type'       => 'number',
						'default'    => 3000,
						'conditions' => array( array( 'slug' => 'autoPlay', 'operator' => '==', 'value' => true ) ),
					),
					array(
						'label'      => 'Pause on Hover',
						'slug'       => 'pauseAutoPlayOnHover',
						'type'       => 'toggle',
						'default'    => true,
						'conditions' => array( array( 'slug' => 'autoPlay', 'operator' => '==', 'value' => true ) ),
					),
					array(
						'label'   => 'Free Scroll',
						'slug'    => 'freeScroll',
						'type'    => 'toggle',
						'default' => false,
					),
					array(
						'label'   => 'Adaptive Height',
						'slug'    => 'adaptiveHeight',
						'type'    => 'toggle',
						'default' => false,
					),
					array(
						'label'   => 'Right to Left',
						'slug'    => 'rightToLeft',
						'type'    => 'toggle',
						'default' => false,
					),
					array(
						'label'   => 'Resize on Window Change',
						'slug'    => 'resize',
						'type'    => 'toggle',
						'default' => true,
					),
					array(
						'label'   => 'Watch CSS',
						'slug'    => 'watchCSS',
						'type'    => 'toggle',
						'default' => false,
					),
				),
			),

			// ── Navigation tab
			array(
				'label' => 'Navigation',
				'type'  => 'section',
				'children' => array(
					array(
						'label'   => 'Prev / Next Buttons',
						'slug'    => 'prevNextButtons',
						'type'    => 'toggle',
						'default' => true,
					),
					array(
						'label'   => 'Page Dots',
						'slug'    => 'pageDots',
						'type'    => 'toggle',
						'default' => true,
					),
					array(
						'label'   => 'Arrow Shape',
						'slug'    => 'arrowShape',
						'type'    => 'dropdown',
						'default' => 'default',
						'options' => array(
							array( 'label' => 'Default', 'value' => 'default' ),
							array( 'label' => 'Custom SVG Path', 'value' => 'custom' ),
						),
					),
					array(
						'label'      => 'Custom SVG Path',
						'slug'       => 'arrowShapeCustom',
						'type'       => 'text',
						'default'    => '',
						'conditions' => array( array( 'slug' => 'arrowShape', 'operator' => '==', 'value' => 'custom' ) ),
					),
					array(
						'label'   => 'Sync with Slider (asNavFor)',
						'slug'    => 'asNavFor',
						'type'    => 'text',
						'default' => '',
						'description' => 'CSS selector of the slider this one controls, e.g. #ld-slider-2',
					),
				),
			),

			// ── Layout tab
			array(
				'label' => 'Layout',
				'type'  => 'section',
				'children' => array(
					array(
						'label'   => 'Cell Alignment',
						'slug'    => 'cellAlign',
						'type'    => 'dropdown',
						'default' => 'left',
						'options' => array(
							array( 'label' => 'Left',   'value' => 'left' ),
							array( 'label' => 'Centre', 'value' => 'center' ),
							array( 'label' => 'Right',  'value' => 'right' ),
						),
					),
					array(
						'label'   => 'Contain',
						'slug'    => 'contain',
						'type'    => 'toggle',
						'default' => true,
					),
					array(
						'label'   => 'Overflow Visible (off-screen)',
						'slug'    => 'overflowVisible',
						'type'    => 'toggle',
						'default' => false,
					),
					array(
						'label'   => 'Group Cells',
						'slug'    => 'groupCells',
						'type'    => 'toggle',
						'default' => false,
					),
					array(
						'label'      => 'Cells Per Group',
						'slug'       => 'groupCellsCount',
						'type'       => 'number',
						'default'    => 1,
						'conditions' => array( array( 'slug' => 'groupCells', 'operator' => '==', 'value' => true ) ),
					),
					array(
						'label'   => 'Initial Slide Index',
						'slug'    => 'initialIndex',
						'type'    => 'number',
						'default' => 0,
					),
					array(
						'label'   => 'Percent Position',
						'slug'    => 'percentPosition',
						'type'    => 'toggle',
						'default' => false,
					),
				),
			),

			// ── Cell Sizing tab
			array(
				'label' => 'Cell Sizing',
				'type'  => 'section',
				'children' => array(
					array(
						'label'   => 'Cell Width',
						'slug'    => 'cellWidth',
						'type'    => 'text',
						'default' => '',
						'description' => 'e.g. 300px or 50%',
					),
					array(
						'label'   => 'Cell Height',
						'slug'    => 'cellHeight',
						'type'    => 'text',
						'default' => '',
						'description' => 'e.g. 200px',
					),
					array(
						'label'   => 'Gap Between Cells',
						'slug'    => 'cellGap',
						'type'    => 'text',
						'default' => '16px',
					),
				),
			),

			// ── Responsive tab
			array(
				'label' => 'Responsive',
				'type'  => 'section',
				'children' => array(
					// Tablet
					array( 'label' => 'Tablet (≤ 1024px)', 'type' => 'label' ),
					array( 'label' => 'Cell Width (px)',    'slug' => 'tablet_cellWidth',       'type' => 'number', 'default' => '' ),
					array( 'label' => 'Cell Height (px)',   'slug' => 'tablet_cellHeight',      'type' => 'number', 'default' => '' ),
					array( 'label' => 'Gap (px)',           'slug' => 'tablet_cellGap',         'type' => 'number', 'default' => '' ),
					array( 'label' => 'Cells Per Group',    'slug' => 'tablet_groupCellsCount', 'type' => 'number', 'default' => 1  ),
					// Mobile
					array( 'label' => 'Mobile (≤ 767px)', 'type' => 'label' ),
					array( 'label' => 'Cell Width (px)',   'slug' => 'mobile_cellWidth',       'type' => 'number', 'default' => '' ),
					array( 'label' => 'Cell Height (px)',  'slug' => 'mobile_cellHeight',      'type' => 'number', 'default' => '' ),
					array( 'label' => 'Gap (px)',          'slug' => 'mobile_cellGap',         'type' => 'number', 'default' => '' ),
					array( 'label' => 'Cells Per Group',   'slug' => 'mobile_groupCellsCount', 'type' => 'number', 'default' => 1  ),
				),
			),

			// ── Overlays tab
			array(
				'label' => 'Overlays',
				'type'  => 'section',
				'children' => array(
					// Left
					array( 'label' => 'Left Overlay',        'slug' => 'overlayLeft',        'type' => 'toggle', 'default' => false ),
					array( 'label' => 'Left Colour',         'slug' => 'overlayLeftColor',   'type' => 'color',  'default' => '#ffffff',
						'conditions' => array( array( 'slug' => 'overlayLeft', 'operator' => '==', 'value' => true ) ) ),
					array( 'label' => 'Left Opacity (%)',    'slug' => 'overlayLeftOpacity', 'type' => 'number', 'default' => 100,
						'conditions' => array( array( 'slug' => 'overlayLeft', 'operator' => '==', 'value' => true ) ) ),
					array( 'label' => 'Left Width (px)',     'slug' => 'overlayLeftWidth',   'type' => 'number', 'default' => 120,
						'conditions' => array( array( 'slug' => 'overlayLeft', 'operator' => '==', 'value' => true ) ) ),
					// Right
					array( 'label' => 'Right Overlay',       'slug' => 'overlayRight',        'type' => 'toggle', 'default' => false ),
					array( 'label' => 'Right Colour',        'slug' => 'overlayRightColor',   'type' => 'color',  'default' => '#ffffff',
						'conditions' => array( array( 'slug' => 'overlayRight', 'operator' => '==', 'value' => true ) ) ),
					array( 'label' => 'Right Opacity (%)',   'slug' => 'overlayRightOpacity', 'type' => 'number', 'default' => 100,
						'conditions' => array( array( 'slug' => 'overlayRight', 'operator' => '==', 'value' => true ) ) ),
					array( 'label' => 'Right Width (px)',    'slug' => 'overlayRightWidth',   'type' => 'number', 'default' => 120,
						'conditions' => array( array( 'slug' => 'overlayRight', 'operator' => '==', 'value' => true ) ) ),
				),
			),

			// ── Images tab
			array(
				'label' => 'Images',
				'type'  => 'section',
				'children' => array(
					array(
						'label'   => 'Wait for Images to Load',
						'slug'    => 'imagesLoaded',
						'type'    => 'toggle',
						'default' => true,
					),
					array(
						'label'   => 'Lazy Load Images',
						'slug'    => 'lazyLoad',
						'type'    => 'toggle',
						'default' => false,
					),
					array(
						'label'      => 'Cells Ahead to Preload',
						'slug'       => 'lazyLoadCount',
						'type'       => 'number',
						'default'    => 1,
						'conditions' => array( array( 'slug' => 'lazyLoad', 'operator' => '==', 'value' => true ) ),
					),
				),
			),

			// ── Advanced tab
			array(
				'label' => 'Advanced',
				'type'  => 'section',
				'children' => array(
					array( 'label' => 'Attraction (0–1)',    'slug' => 'selectedAttraction', 'type' => 'text', 'default' => '0.025' ),
					array( 'label' => 'Friction (0–1)',      'slug' => 'friction',           'type' => 'text', 'default' => '0.28'  ),
					array( 'label' => 'Drag Threshold (px)', 'slug' => 'dragThreshold',      'type' => 'number', 'default' => 3     ),
					array(
						'label'   => 'Keyboard Navigation',
						'slug'    => 'accessibility',
						'type'    => 'toggle',
						'default' => true,
					),
					array(
						'label'   => 'Custom CSS',
						'slug'    => 'customCSS',
						'type'    => 'textarea',
						'default' => '',
					),
				),
			),

		);
	}

	/* ─── Render ───────────────────────────────────────────── */

	public static function render( $properties, $children_html ) {
		$p = $properties['content'] ?? array();

		// Map Breakdance properties to our settings array
		$settings = array_merge(
			LD_Sliders_DB::default_settings(),
			array(
				'wrapAround'           => !empty($p['wrapAround']),
				'autoPlay'             => !empty($p['autoPlay']),
				'autoPlaySpeed'        => isset($p['autoPlaySpeed']) ? absint($p['autoPlaySpeed']) : 3000,
				'pauseAutoPlayOnHover' => !empty($p['pauseAutoPlayOnHover']),
				'freeScroll'           => !empty($p['freeScroll']),
				'adaptiveHeight'       => !empty($p['adaptiveHeight']),
				'rightToLeft'          => !empty($p['rightToLeft']),
				'resize'               => isset($p['resize']) ? (bool)$p['resize'] : true,
				'watchCSS'             => !empty($p['watchCSS']),
				'prevNextButtons'      => isset($p['prevNextButtons']) ? (bool)$p['prevNextButtons'] : true,
				'pageDots'             => isset($p['pageDots']) ? (bool)$p['pageDots'] : true,
				'arrowShape'           => $p['arrowShape'] ?? 'default',
				'arrowShapeCustom'     => $p['arrowShapeCustom'] ?? '',
				'asNavFor'             => $p['asNavFor'] ?? '',
				'cellAlign'            => $p['cellAlign'] ?? 'left',
				'contain'              => isset($p['contain']) ? (bool)$p['contain'] : true,
				'overflowVisible'      => !empty($p['overflowVisible']),
				'groupCells'           => !empty($p['groupCells']),
				'groupCellsCount'      => isset($p['groupCellsCount']) ? absint($p['groupCellsCount']) : 1,
				'initialIndex'         => isset($p['initialIndex']) ? absint($p['initialIndex']) : 0,
				'percentPosition'      => !empty($p['percentPosition']),
				'imagesLoaded'         => isset($p['imagesLoaded']) ? (bool)$p['imagesLoaded'] : true,
				'lazyLoad'             => !empty($p['lazyLoad']),
				'lazyLoadCount'        => isset($p['lazyLoadCount']) ? absint($p['lazyLoadCount']) : 1,
				'selectedAttraction'   => isset($p['selectedAttraction']) ? floatval($p['selectedAttraction']) : 0.025,
				'friction'             => isset($p['friction']) ? floatval($p['friction']) : 0.28,
				'dragThreshold'        => isset($p['dragThreshold']) ? absint($p['dragThreshold']) : 3,
				'accessibility'        => isset($p['accessibility']) ? (bool)$p['accessibility'] : true,
				'customCSS'            => $p['customCSS'] ?? '',
				// Cell sizing
				'cellWidth'            => $p['cellWidth'] ?? '',
				'cellHeight'           => $p['cellHeight'] ?? '',
				'cellGap'              => $p['cellGap'] ?? '16px',
				// Responsive
				'tablet_cellWidth'       => $p['tablet_cellWidth'] ?? '',
				'tablet_cellHeight'      => $p['tablet_cellHeight'] ?? '',
				'tablet_cellGap'         => $p['tablet_cellGap'] ?? '',
				'tablet_groupCellsCount' => isset($p['tablet_groupCellsCount']) ? absint($p['tablet_groupCellsCount']) : 1,
				'mobile_cellWidth'       => $p['mobile_cellWidth'] ?? '',
				'mobile_cellHeight'      => $p['mobile_cellHeight'] ?? '',
				'mobile_cellGap'         => $p['mobile_cellGap'] ?? '',
				'mobile_groupCellsCount' => isset($p['mobile_groupCellsCount']) ? absint($p['mobile_groupCellsCount']) : 1,
				// Overlays
				'overlayLeft'         => !empty($p['overlayLeft']),
				'overlayLeftColor'    => sanitize_hex_color($p['overlayLeftColor'] ?? '#ffffff') ?: '#ffffff',
				'overlayLeftOpacity'  => isset($p['overlayLeftOpacity'])  ? absint($p['overlayLeftOpacity'])  : 100,
				'overlayLeftWidth'    => isset($p['overlayLeftWidth'])    ? absint($p['overlayLeftWidth'])    : 120,
				'overlayRight'        => !empty($p['overlayRight']),
				'overlayRightColor'   => sanitize_hex_color($p['overlayRightColor'] ?? '#ffffff') ?: '#ffffff',
				'overlayRightOpacity' => isset($p['overlayRightOpacity']) ? absint($p['overlayRightOpacity']) : 100,
				'overlayRightWidth'   => isset($p['overlayRightWidth'])   ? absint($p['overlayRightWidth'])   : 120,
			)
		);

		// For Breakdance elements we generate a unique ID from the element UID
		static $bd_counter = 0;
		$bd_counter++;
		$id   = 'bd-' . $bd_counter;
		$slug = 'breakdance-slider-' . $bd_counter;

		return LD_Sliders_Shortcode::render_slider( $id, $slug, $settings, $children_html );
	}

	public static function defaultCss() {
		return '';
	}
}

endif; // class_exists check

// Register the child LD Slide element
add_action( 'breakdance_loaded', function() {
	$slide_file = LD_SLIDERS_PATH . 'breakdance/class-ld-slide-element.php';
	if ( file_exists( $slide_file ) && function_exists( '\Breakdance\Elements\register_element_from_class' ) ) {
		require_once $slide_file;
		try {
			if ( class_exists( 'LD_Slide_Element' ) ) {
				\Breakdance\Elements\register_element_from_class( 'LD_Slide_Element' );
			}
		} catch ( \Throwable $e ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'LD Sliders: LD Slide element registration failed — ' . $e->getMessage() );
			}
		}
	}
} );
