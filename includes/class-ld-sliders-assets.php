<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class LD_Sliders_Assets {

	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );
		add_action( 'wp_footer',          array( $this, 'print_config' ), 5 );
	}

	public function enqueue() {
		wp_enqueue_style(
			'ld-sliders',
			LD_SLIDERS_URL . 'public/css/ld-sliders.css',
			array(),
			LD_SLIDERS_VERSION
		);
		wp_enqueue_script(
			'ld-sliders',
			LD_SLIDERS_URL . 'public/js/ld-sliders.js',
			array(),
			LD_SLIDERS_VERSION,
			true
		);
	}

	/**
	 * Print all slider configs as a single JSON object in the footer.
	 * JS reads this to initialise each slider by its class.
	 */
	public function print_config() {
		$sliders = LD_Sliders_DB::get_all();
		if ( empty( $sliders ) ) return;

		$configs = array();
		foreach ( $sliders as $slider ) {
			$settings = json_decode( $slider->settings, true );
			if ( ! is_array( $settings ) ) $settings = array();
			$settings = wp_parse_args( $settings, LD_Sliders_DB::default_settings() );
			$configs[ 'ld-slider-' . absint( $slider->id ) ] = $this->build_config( $settings );
		}

		echo '<script>window.LDSliderConfigs = ' . wp_json_encode( $configs ) . ';</script>' . "\n";
		echo '<style>' . $this->build_all_css( $sliders ) . '</style>' . "\n";
	}

	/**
	 * Build the JS config object for a slider.
	 */
	private function build_config( $s ) {
		$config = array(
			'autoPlay'             => $s['autoPlay'] ? absint( $s['autoPlaySpeed'] ) : false,
			'pauseAutoPlayOnHover' => (bool) $s['pauseAutoPlayOnHover'],
			'wrapAround'           => (bool) $s['wrapAround'],
			'freeScroll'           => (bool) $s['freeScroll'],
			'dragThreshold'        => absint( $s['dragThreshold'] ),
			'selectedAttraction'   => floatval( $s['selectedAttraction'] ),
			'friction'             => floatval( $s['friction'] ),
			'resize'               => (bool) $s['resize'],
			'prevNextButtons'      => (bool) $s['prevNextButtons'],
			'pageDots'             => (bool) $s['pageDots'],
			'arrowShape'           => ( $s['arrowShape'] === 'custom' && ! empty( $s['arrowShapeCustom'] ) )
			                          ? sanitize_text_field( $s['arrowShapeCustom'] ) : 'default',
			'cellAlign'            => sanitize_text_field( $s['cellAlign'] ),
			'contain'              => (bool) $s['contain'],
			'overflowVisible'      => (bool) $s['overflowVisible'],
			'groupCells'           => $s['groupCells'] ? absint( $s['groupCellsCount'] ) : false,
			'initialIndex'         => absint( $s['initialIndex'] ),
			'rightToLeft'          => (bool) $s['rightToLeft'],
			'adaptiveHeight'       => (bool) $s['adaptiveHeight'],
			'percentPosition'      => (bool) $s['percentPosition'],
			'imagesLoaded'         => (bool) $s['imagesLoaded'],
			'lazyLoad'             => $s['lazyLoad'] ? absint( $s['lazyLoadCount'] ) : false,
			'accessibility'        => (bool) $s['accessibility'],
			'watchCSS'             => (bool) $s['watchCSS'],
			'asNavFor'             => ! empty( $s['asNavFor'] ) ? sanitize_text_field( $s['asNavFor'] ) : false,
			// Responsive group cells
			'_tabletGroupCells'    => ! empty( $s['tablet_groupCells'] ) ? absint( $s['tablet_groupCellsCount'] ) : false,
			'_mobileGroupCells'    => ! empty( $s['mobile_groupCells'] ) ? absint( $s['mobile_groupCellsCount'] ) : false,
			// Overlays
			'overlayLeft'         => (bool) $s['overlayLeft'],
			'overlayLeftColor'    => sanitize_hex_color( $s['overlayLeftColor'] ) ?: '#ffffff',
			'overlayLeftOpacity'  => absint( $s['overlayLeftOpacity'] ),
			'overlayLeftWidth'    => absint( $s['overlayLeftWidth'] ),
			'overlayRight'        => (bool) $s['overlayRight'],
			'overlayRightColor'   => sanitize_hex_color( $s['overlayRightColor'] ) ?: '#ffffff',
			'overlayRightOpacity' => absint( $s['overlayRightOpacity'] ),
			'overlayRightWidth'   => absint( $s['overlayRightWidth'] ),
		);
		return $config;
	}

	/**
	 * Build scoped CSS for all sliders — cell sizing, gaps, responsive, overlays.
	 */
	private function build_all_css( $sliders ) {
		$css = '';
		foreach ( $sliders as $slider ) {
			$settings = json_decode( $slider->settings, true );
			if ( ! is_array( $settings ) ) $settings = array();
			$settings = wp_parse_args( $settings, LD_Sliders_DB::default_settings() );
			$sel      = '.ld-slider-' . absint( $slider->id );

			// Overflow: the wrapper clips left, track overflows right
			if ( ! empty( $settings['overflowVisible'] ) ) {
				$css .= "{$sel}.ld-slider-wrapper{overflow:visible;clip-path:none;}";
				// Hard clip on the correct edge depending on RTL
				if ( ! empty( $settings['rightToLeft'] ) ) {
					$css .= "{$sel}.ld-slider-wrapper{-webkit-mask-image:linear-gradient(to left,transparent 0,black 0);mask-image:linear-gradient(to left,transparent 0,black 0);}";
				} else {
					// Clip left edge hard, allow right to bleed
					$css .= "{$sel}.ld-slider-wrapper{-webkit-mask-image:linear-gradient(to right,black 0,black 100%,transparent 100%);mask-image:linear-gradient(to right,black 0,black 100%,transparent 100%);}";
				}
			}

			// Cell sizing — desktop
			$cell_css = '';
			if ( ! empty( $settings['cellWidth'] ) )
				$cell_css .= 'width:'        . floatval( $settings['cellWidth'] )  . esc_attr( $settings['cellWidthUnit'] )  . ';';
			if ( ! empty( $settings['cellHeight'] ) )
				$cell_css .= 'height:'       . floatval( $settings['cellHeight'] ) . esc_attr( $settings['cellHeightUnit'] ) . ';';
			if ( ! empty( $settings['cellGap'] ) )
				$cell_css .= 'margin-right:' . floatval( $settings['cellGap'] )    . esc_attr( $settings['cellGapUnit'] )    . ';';
			if ( $cell_css )
				$css .= "{$sel} .ld-carousel-cell{{$cell_css}}";

			// Tablet
			$t = '';
			if ( ! empty( $settings['tablet_cellWidth'] ) )  $t .= 'width:'        . floatval( $settings['tablet_cellWidth'] )  . 'px;';
			if ( ! empty( $settings['tablet_cellHeight'] ) ) $t .= 'height:'       . floatval( $settings['tablet_cellHeight'] ) . 'px;';
			if ( ! empty( $settings['tablet_cellGap'] ) )    $t .= 'margin-right:' . floatval( $settings['tablet_cellGap'] )    . 'px;';
			if ( $t ) $css .= "@media(max-width:1024px){{$sel} .ld-carousel-cell{{$t}}}";

			// Mobile
			$m = '';
			if ( ! empty( $settings['mobile_cellWidth'] ) )  $m .= 'width:'        . floatval( $settings['mobile_cellWidth'] )  . 'px;';
			if ( ! empty( $settings['mobile_cellHeight'] ) ) $m .= 'height:'       . floatval( $settings['mobile_cellHeight'] ) . 'px;';
			if ( ! empty( $settings['mobile_cellGap'] ) )    $m .= 'margin-right:' . floatval( $settings['mobile_cellGap'] )    . 'px;';
			if ( $m ) $css .= "@media(max-width:767px){{$sel} .ld-carousel-cell{{$m}}}";

			// Custom CSS
			if ( ! empty( $settings['customCSS'] ) )
				$css .= wp_strip_all_tags( $settings['customCSS'] );
		}
		return $css;
	}
}
