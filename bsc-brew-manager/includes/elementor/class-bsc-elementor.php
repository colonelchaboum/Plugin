<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BSC_Elementor {

	public function __construct() {
		add_action( 'elementor/widgets/register', array( $this, 'register_widgets' ) );
		add_action( 'elementor/elements/categories_registered', array( $this, 'register_categories' ) );
	}

	public function register_categories( $elements_manager ) {
		$elements_manager->add_category(
			'bsc-brew-manager',
			[
				'title' => __( 'Brewers Social Club', 'bsc-brew-manager' ),
				'icon'  => 'fa fa-beer',
			]
		);
	}

	public function register_widgets( $widgets_manager ) {
		// Require Widget files
		require_once __DIR__ . '/widgets/class-bsc-widget-recipe-list.php';
		require_once __DIR__ . '/widgets/class-bsc-widget-submission-form.php';
		require_once __DIR__ . '/widgets/class-bsc-widget-single-recipe.php';
		require_once __DIR__ . '/widgets/class-bsc-widget-top-recipes.php';
		require_once __DIR__ . '/widgets/class-bsc-widget-user-profile.php';

		// Register Widgets
		$widgets_manager->register( new \BSC_Widget_Recipe_List() );
		$widgets_manager->register( new \BSC_Widget_Submission_Form() );
		$widgets_manager->register( new \BSC_Widget_Single_Recipe() );
		$widgets_manager->register( new \BSC_Widget_Top_Recipes() );
		$widgets_manager->register( new \BSC_Widget_User_Profile() );
	}
}
