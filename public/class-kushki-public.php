<?php

class Kushki_Public {

	private $plugin_name;
	private $version;

	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	public function enqueue_styles(){}

	public function enqueue_scripts() {
        wp_enqueue_script( 'jquery.showandtell.js', plugin_dir_url( __FILE__ ) . 'js/jquery.showandtell.js', array( 'jquery' ), $this->version, false );
	}

}
