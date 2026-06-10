<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LD_Sliders_Assets {

	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_public' ) );
	}

	public function enqueue_public() {
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
}
