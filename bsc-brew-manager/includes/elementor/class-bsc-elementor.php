<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BSC_Elementor_Manager {

	public static function init() {
		// Hook to 'elementor/widgets/register' which is the standard way to register widgets in Elementor 3.5+
		// For older versions it was 'elementor/widgets/widgets_registered'
		add_action( 'elementor/widgets/register', array( __CLASS__, 'register_widgets' ) );
	}

	public static function register_widgets( $widgets_manager ) {
		// Ensure Elementor is loaded
		if ( ! did_action( 'elementor/loaded' ) ) {
			return;
		}

		// Require Widget Classes
		require_once( __DIR__ . '/widgets/widget-brewery-info.php' );
		require_once( __DIR__ . '/widgets/widget-beer-info.php' );
		require_once( __DIR__ . '/widgets/widget-filter.php' );

		// Register Widgets if classes exist
		if ( class_exists( 'BSC_Elementor_Brewery_Info_Widget' ) ) {
			$widgets_manager->register( new \BSC_Elementor_Brewery_Info_Widget() );
		}

		if ( class_exists( 'BSC_Elementor_Beer_Info_Widget' ) ) {
			$widgets_manager->register( new \BSC_Elementor_Beer_Info_Widget() );
		}

		if ( class_exists( 'BSC_Elementor_Filter_Widget' ) ) {
			$widgets_manager->register( new \BSC_Elementor_Filter_Widget() );
		}
	}
}
