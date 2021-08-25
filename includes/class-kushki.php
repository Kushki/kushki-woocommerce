<?php

class Kushki_WC {

	protected $loader;
	protected $plugin_name;
	protected $version;

	public function __construct() {

		$this->plugin_name = 'kushki-gateway';
		$this->version     = '2.0.0';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
	}

	private function load_dependencies() {
		require_once dirname( dirname( __FILE__ ) ) . '/includes/class-kushki-loader.php';
		require_once dirname( dirname( __FILE__ ) ) . '/admin/class-kushki-admin.php';
		require_once dirname( dirname( __FILE__ ) ) . '/vendor/autoload.php';

		$this->loader = new Kushki_Loader();

	}

	private function set_locale() {


	}

	private function define_admin_hooks() {

		$plugin_admin = new Kushki_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
		$this->loader->add_action( 'plugins_loaded', $plugin_admin, 'load_class', 0 );
		$this->loader->add_filter( 'woocommerce_payment_gateways', $plugin_admin, 'add_gateway' );
		$this->loader->add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), $plugin_admin, 'action_links' );

	}

	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {

		$this->loader->run();
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Kushki_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

}
