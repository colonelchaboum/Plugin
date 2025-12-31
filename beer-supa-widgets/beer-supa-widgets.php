<?php
/**
 * Plugin Name: Beer Supa Widgets
 * Description: Two Elementor widgets (Beer Hub Dashboard & Project Manager) connected to Supabase.
 * Version: 1.0.0
 * Author: Jules
 * Text Domain: beer-supa-widgets
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Beer_Supa_Widgets {

	const VERSION = '1.0.0';
	const MINIMUM_ELEMENTOR_VERSION = '3.0.0';
	const MINIMUM_PHP_VERSION = '7.4';

	private static $_instance = null;

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	public function __construct() {
		add_action( 'plugins_loaded', [ $this, 'on_plugins_loaded' ] );
	}

	public function on_plugins_loaded() {
		if ( ! did_action( 'elementor/loaded' ) ) {
			add_action( 'admin_notices', [ $this, 'admin_notice_missing_main_plugin' ] );
			return;
		}

		add_action( 'elementor/widgets/register', [ $this, 'register_widgets' ] );
		add_action( 'elementor/frontend/after_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
	}

	public function register_widgets( $widgets_manager ) {
		require_once( __DIR__ . '/includes/widgets/class-widget-dashboard.php' );
		require_once( __DIR__ . '/includes/widgets/class-widget-project-manager.php' );

		$widgets_manager->register( new \Beer_Supa_Widgets\Widgets\Widget_Dashboard() );
		$widgets_manager->register( new \Beer_Supa_Widgets\Widgets\Widget_Project_Manager() );
	}

	public function enqueue_scripts() {
		wp_enqueue_script(
			'beer-supa-widgets-js',
			plugins_url( 'dist/main.js', __FILE__ ),
			[ 'wp-element' ], // wp-element provides React
			self::VERSION,
			true
		);

		wp_enqueue_style(
			'beer-supa-widgets-css',
			plugins_url( 'dist/main.css', __FILE__ ),
			[],
			self::VERSION
		);

		// Pass Supabase Config to Frontend
		wp_localize_script( 'beer-supa-widgets-js', 'supaData', [
			'supabaseUrl' => 'REPLACE_WITH_YOUR_SUPABASE_URL',
			'supabaseKey' => 'REPLACE_WITH_YOUR_SUPABASE_KEY',
		]);
	}

	public function admin_notice_missing_main_plugin() {
		if ( isset( $_GET['activate'] ) ) unset( $_GET['activate'] );
		$message = sprintf(
			/* translators: 1: Plugin name 2: Elementor */
			esc_html__( '"%1$s" requires "%2$s" to be installed and activated.', 'beer-supa-widgets' ),
			'<strong>' . esc_html__( 'Beer Supa Widgets', 'beer-supa-widgets' ) . '</strong>',
			'<strong>' . esc_html__( 'Elementor', 'beer-supa-widgets' ) . '</strong>'
		);
		printf( '<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message );
	}
}

Beer_Supa_Widgets::instance();
