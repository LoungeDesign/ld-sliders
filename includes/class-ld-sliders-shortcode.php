<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LD_Sliders_Shortcode {

	public function __construct() {
		add_shortcode( 'ld_slider', array( $this, 'render' ) );
	}

	public function render( $atts, $content = '' ) {
		$atts = shortcode_atts(
			array(
				'id'   => 0,
				'slug' => '',
			),
			$atts,
			'ld_slider'
		);

		$slider = null;
		if ( ! empty( $atts['slug'] ) ) {
			$slider = LD_Sliders_DB::get_by_slug( sanitize_key( $atts['slug'] ) );
		} elseif ( ! empty( $atts['id'] ) ) {
			$slider = LD_Sliders_DB::get( absint( $atts['id'] ) );
		}

		if ( ! $slider ) {
			return '<!-- LD Sliders: slider not found -->';
		}

		$settings = json_decode( $slider->settings, true );
		if ( ! is_array( $settings ) ) {
			$settings = array();
		}
		$settings = wp_parse_args( $settings, LD_Sliders_DB::default_settings() );

		return self::render_slider( $slider->id, $slider->slug, $settings, $content );
	}

	/**
	 * Static renderer — used by both shortcode and Breakdance element.
	 */
	public static function render_slider( $id, $slug, $settings, $content = '' ) {
		$wrapper_id = 'ld-slider-' . absint( $id );
		$js_config  = self::build_js_config( $settings );

		$wrapper_style = '';
		if ( ! empty( $settings['overflowVisible'] ) ) {
			$wrapper_style = ' style="overflow:visible;"';
		}

		ob_start();
		?>
		<div
			id="<?php echo esc_attr( $wrapper_id ); ?>"
			class="ld-slider-wrapper"
			data-ld-slider
			data-settings="<?php echo esc_attr( wp_json_encode( $js_config ) ); ?>"
			<?php echo $wrapper_style; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		>
			<div class="ld-carousel">
				<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>

			<?php self::render_overlays( $wrapper_id, $settings ); ?>

		</div>
		<?php
		self::output_inline_css( $wrapper_id, $settings );
		return ob_get_clean();
	}

	/**
	 * Render left / right gradient overlays.
	 */
	private static function render_overlays( $wrapper_id, $s ) {
		foreach ( array( 'Left', 'Right' ) as $side ) {
			$key = 'overlay' . $side;
			if ( empty( $s[ $key ] ) ) continue;

			$color   = sanitize_hex_color( $s[ $key . 'Color' ] ) ?: '#ffffff';
			$opacity = absint( $s[ $key . 'Opacity' ] ) / 100;
			$width   = absint( $s[ $key . 'Width' ] );
			$dir     = ( $side === 'Left' ) ? 'to left' : 'to right';
			$pos     = ( $side === 'Left' ) ? 'left:0;' : 'right:0;';

			// Convert hex to rgb for rgba()
			$rgb = self::hex_to_rgb( $color );

			printf(
				'<div class="ld-overlay ld-overlay--%s" style="position:absolute;top:0;%sbottom:0;width:%dpx;pointer-events:none;z-index:5;background:linear-gradient(%s, rgba(%d,%d,%d,%.2f) 0%%, rgba(%d,%d,%d,0) 100%%);"></div>',
				esc_attr( strtolower( $side ) ),
				esc_attr( $pos ),
				$width,
				esc_attr( $dir ),
				$rgb[0], $rgb[1], $rgb[2], $opacity,
				$rgb[0], $rgb[1], $rgb[2]
			);
		}
	}

	private static function hex_to_rgb( $hex ) {
		$hex = ltrim( $hex, '#' );
		if ( strlen( $hex ) === 3 ) {
			$hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
		}
		return array(
			hexdec( substr( $hex, 0, 2 ) ),
			hexdec( substr( $hex, 2, 2 ) ),
			hexdec( substr( $hex, 4, 2 ) ),
		);
	}

	/**
	 * Build JS config from saved settings.
	 */
	public static function build_js_config( $s ) {
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
			'cellAlign'            => sanitize_text_field( $s['cellAlign'] ),
			'contain'              => (bool) $s['contain'],
			'groupCells'           => $s['groupCells'] ? absint( $s['groupCellsCount'] ) : false,
			'initialIndex'         => absint( $s['initialIndex'] ),
			'rightToLeft'          => (bool) $s['rightToLeft'],
			'adaptiveHeight'       => (bool) $s['adaptiveHeight'],
			'percentPosition'      => (bool) $s['percentPosition'],
			'imagesLoaded'         => (bool) $s['imagesLoaded'],
			'lazyLoad'             => $s['lazyLoad'] ? absint( $s['lazyLoadCount'] ) : false,
			'accessibility'        => (bool) $s['accessibility'],
			'setGallerySize'       => (bool) $s['setGallerySize'],
			'watchCSS'             => (bool) $s['watchCSS'],
			'asNavFor'             => ! empty( $s['asNavFor'] ) ? sanitize_text_field( $s['asNavFor'] ) : false,
		);

		// Arrow shape
		if ( ! empty( $s['arrowShape'] ) && $s['arrowShape'] === 'custom' && ! empty( $s['arrowShapeCustom'] ) ) {
			$config['arrowShape'] = sanitize_text_field( $s['arrowShapeCustom'] );
		}

		// Responsive group cells
		if ( ! empty( $s['tablet_groupCells'] ) ) {
			$config['_tabletGroupCells'] = absint( $s['tablet_groupCellsCount'] );
		}
		if ( ! empty( $s['mobile_groupCells'] ) ) {
			$config['_mobileGroupCells'] = absint( $s['mobile_groupCellsCount'] );
		}

		return $config;
	}

	/**
	 * Output scoped inline CSS for sizing, gaps, overlays and responsive overrides.
	 */
	private static function output_inline_css( $id, $s ) {
		$css = '';

		// Wrapper — overflow visible
		if ( ! empty( $s['overflowVisible'] ) ) {
			$css .= "#{$id} { overflow: visible; }";
		}

		// Cell sizing — desktop
		$cell_css = '';
		if ( ! empty( $s['cellWidth'] ) ) {
			$cell_css .= 'width:' . floatval( $s['cellWidth'] ) . esc_attr( $s['cellWidthUnit'] ) . ';';
		}
		if ( ! empty( $s['cellHeight'] ) ) {
			$cell_css .= 'height:' . floatval( $s['cellHeight'] ) . esc_attr( $s['cellHeightUnit'] ) . ';';
		}
		if ( ! empty( $s['cellGap'] ) ) {
			$cell_css .= 'margin-right:' . floatval( $s['cellGap'] ) . esc_attr( $s['cellGapUnit'] ) . ';';
		}
		if ( $cell_css ) {
			$css .= "#{$id} .ld-carousel-cell{{$cell_css}}";
		}

		// Tablet
		$tablet_css = '';
		if ( ! empty( $s['tablet_cellWidth'] ) )  $tablet_css .= 'width:'        . floatval( $s['tablet_cellWidth'] )  . 'px;';
		if ( ! empty( $s['tablet_cellHeight'] ) )  $tablet_css .= 'height:'       . floatval( $s['tablet_cellHeight'] ) . 'px;';
		if ( ! empty( $s['tablet_cellGap'] ) )     $tablet_css .= 'margin-right:' . floatval( $s['tablet_cellGap'] )    . 'px;';
		if ( $tablet_css ) {
			$css .= "@media(max-width:1024px){#{$id} .ld-carousel-cell{{$tablet_css}}}";
		}

		// Mobile
		$mobile_css = '';
		if ( ! empty( $s['mobile_cellWidth'] ) )   $mobile_css .= 'width:'        . floatval( $s['mobile_cellWidth'] )  . 'px;';
		if ( ! empty( $s['mobile_cellHeight'] ) )   $mobile_css .= 'height:'       . floatval( $s['mobile_cellHeight'] ) . 'px;';
		if ( ! empty( $s['mobile_cellGap'] ) )      $mobile_css .= 'margin-right:' . floatval( $s['mobile_cellGap'] )    . 'px;';
		if ( $mobile_css ) {
			$css .= "@media(max-width:767px){#{$id} .ld-carousel-cell{{$mobile_css}}}";
		}

		// Custom CSS
		if ( ! empty( $s['customCSS'] ) ) {
			$css .= wp_strip_all_tags( $s['customCSS'] );
		}

		if ( $css ) {
			echo '<style>' . $css . '</style>'; // phpcs:ignore
		}
	}
}
