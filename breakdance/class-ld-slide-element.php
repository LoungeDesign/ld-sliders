<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if (
	class_exists( '\Breakdance\Elements\Element' ) ||
	interface_exists( '\Breakdance\Elements\Element' )
) :

class LD_Slide_Element extends \Breakdance\Elements\Element {

	public static function tag() {
		return 'ld-slide';
	}

	public static function tagCategory() {
		return 'Interactive';
	}

	public static function name() {
		return 'LD Slide';
	}

	public static function icon() {
		return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="5" width="18" height="14" rx="2"/></svg>';
	}

	public static function template() {
		return '<div class="ld-carousel-cell">{children}</div>';
	}

	public static function allowChildren() {
		return true;
	}

	public static function getSettingsSchema() {
		return array();
	}

	public static function render( $properties, $children_html ) {
		return '<div class="ld-carousel-cell">' . $children_html . '</div>';
	}

	public static function defaultCss() {
		return '.ld-carousel-cell { box-sizing: border-box; }';
	}
}

endif;
