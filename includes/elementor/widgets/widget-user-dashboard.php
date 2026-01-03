<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\Elementor\Widget_Base' ) ) {
	return;
}

class BSC_Elementor_Dashboard_Widget extends \Elementor\Widget_Base {

	public function get_name() {
		return 'bsc_user_dashboard';
	}

	public function get_title() {
		return __( 'Tableau de Bord Utilisateur', 'bsc-brew-manager' );
	}

	public function get_icon() {
		return 'eicon-dashboard';
	}

	public function get_categories() {
		return array( 'general' );
	}

	protected function register_controls() {
		// Content Tab
		$this->start_controls_section(
			'section_content',
			array(
				'label' => __( 'Contenu', 'bsc-brew-manager' ),
				'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'show_breweries',
			array(
				'label' => __( 'Afficher Mes Brasseries', 'bsc-brew-manager' ),
				'type' => \Elementor\Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->add_control(
			'show_beers',
			array(
				'label' => __( 'Afficher Mes Bières', 'bsc-brew-manager' ),
				'type' => \Elementor\Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->end_controls_section();

		// Style Tab
		$this->start_controls_section(
			'section_style_headings',
			array(
				'label' => __( 'Titres', 'bsc-brew-manager' ),
				'tab' => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'heading_color',
			array(
				'label' => __( 'Couleur', 'bsc-brew-manager' ),
				'type' => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} h2' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name' => 'heading_typography',
				'selector' => '{{WRAPPER}} h2',
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_table',
			array(
				'label' => __( 'Tableaux', 'bsc-brew-manager' ),
				'tab' => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'table_header_bg',
			array(
				'label' => __( 'Fond En-tête', 'bsc-brew-manager' ),
				'type' => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} table thead th' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'table_row_hover',
			array(
				'label' => __( 'Survol Ligne', 'bsc-brew-manager' ),
				'type' => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} table tbody tr:hover' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();

		if ( 'yes' === $settings['show_breweries'] ) {
			echo do_shortcode( '[bsc_my_breweries]' );
		}

		if ( 'yes' === $settings['show_beers'] ) {
			echo do_shortcode( '[bsc_my_beers]' );
		}
	}
}
