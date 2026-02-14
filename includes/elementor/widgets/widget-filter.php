<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\Elementor\Widget_Base' ) ) {
	return;
}

class BSC_Elementor_Filter_Widget extends \Elementor\Widget_Base {

	public function get_name() {
		return 'bsc_filter';
	}

	public function get_title() {
		return __( 'Filtres & Tri', 'bsc-brew-manager' );
	}

	public function get_icon() {
		return 'eicon-filter';
	}

	public function get_categories() {
		return array( 'general' );
	}

	protected function register_controls() {
		$this->start_controls_section(
			'content_section',
			array(
				'label' => __( 'Configuration', 'bsc-brew-manager' ),
				'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'filter_target',
			array(
				'label' => __( 'Cible', 'bsc-brew-manager' ),
				'type' => \Elementor\Controls_Manager::SELECT,
				'default' => 'beers',
				'options' => array(
					'beers' => __( 'Bières', 'bsc-brew-manager' ),
					'breweries' => __( 'Brasseries', 'bsc-brew-manager' ),
				),
			)
		);

		$this->end_controls_section();

		// Style Tab
		$this->start_controls_section(
			'section_style_fields',
			array(
				'label' => __( 'Champs', 'bsc-brew-manager' ),
				'tab' => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'field_bg_color',
			array(
				'label' => __( 'Fond', 'bsc-brew-manager' ),
				'type' => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} select' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'field_text_color',
			array(
				'label' => __( 'Texte', 'bsc-brew-manager' ),
				'type' => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} select' => 'color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_button',
			array(
				'label' => __( 'Bouton', 'bsc-brew-manager' ),
				'tab' => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'button_bg_color',
			array(
				'label' => __( 'Fond', 'bsc-brew-manager' ),
				'type' => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} button' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'button_text_color',
			array(
				'label' => __( 'Texte', 'bsc-brew-manager' ),
				'type' => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} button' => 'color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$target = $settings['filter_target'];

		echo '<div class="bsc-filter-widget">';
		echo '<form method="get" class="bsc-filter-form" style="display:flex; gap:10px; align-items:center;">';

		// If filtering beers, show Style dropdown
		if ( $target === 'beers' ) {
			$styles = array( 'IPA', 'Stout', 'Lager', 'Pale Ale', 'Porter', 'Saison', 'Sour', 'Wheat Beer', 'Belgian', 'Other' );
			$current_style = isset( $_GET['bsc_style'] ) ? sanitize_text_field( $_GET['bsc_style'] ) : '';

			echo '<select name="bsc_style">';
			echo '<option value="">' . __( 'Tous les styles', 'bsc-brew-manager' ) . '</option>';
			foreach ( $styles as $style ) {
				echo '<option value="' . esc_attr( $style ) . '" ' . selected( $current_style, $style, false ) . '>' . esc_html( $style ) . '</option>';
			}
			echo '</select>';

			// Sort options for beers
			$sort_options = array(
				'date' => __( 'Date', 'bsc-brew-manager' ),
				'name' => __( 'Nom', 'bsc-brew-manager' ),
				'rating' => __( 'Note', 'bsc-brew-manager' ),
			);
		} else {
			// Sort options for breweries
			$sort_options = array(
				'name' => __( 'Nom', 'bsc-brew-manager' ),
				'rating' => __( 'Note Moyenne', 'bsc-brew-manager' ),
				'beers' => __( 'Nombre de bières', 'bsc-brew-manager' ),
			);
		}

		$current_sort = isset( $_GET['bsc_sort'] ) ? sanitize_text_field( $_GET['bsc_sort'] ) : '';

		echo '<select name="bsc_sort">';
		foreach ( $sort_options as $val => $label ) {
			echo '<option value="' . esc_attr( $val ) . '" ' . selected( $current_sort, $val, false ) . '>' . esc_html( $label ) . '</option>';
		}
		echo '</select>';

		echo '<button type="submit" class="elementor-button">' . __( 'Filtrer', 'bsc-brew-manager' ) . '</button>';

		echo '</form>';
		echo '</div>';
	}
}
