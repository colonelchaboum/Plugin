<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\Elementor\Widget_Base' ) ) {
	return;
}

class BSC_Elementor_Form_Beer_Widget extends \Elementor\Widget_Base {

	public function get_name() {
		return 'bsc_form_beer';
	}

	public function get_title() {
		return __( 'Formulaire Bière', 'bsc-brew-manager' );
	}

	public function get_icon() {
		return 'eicon-form-horizontal';
	}

	public function get_categories() {
		return array( 'general' );
	}

	protected function register_controls() {
		// Style Tab
		$this->start_controls_section(
			'section_style_labels',
			array(
				'label' => __( 'Labels', 'bsc-brew-manager' ),
				'tab' => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'label_color',
			array(
				'label' => __( 'Couleur', 'bsc-brew-manager' ),
				'type' => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} label' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name' => 'label_typography',
				'selector' => '{{WRAPPER}} label',
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_inputs',
			array(
				'label' => __( 'Champs', 'bsc-brew-manager' ),
				'tab' => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'input_bg_color',
			array(
				'label' => __( 'Fond', 'bsc-brew-manager' ),
				'type' => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} input:not([type="submit"]), {{WRAPPER}} textarea, {{WRAPPER}} select' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'input_text_color',
			array(
				'label' => __( 'Texte', 'bsc-brew-manager' ),
				'type' => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} input:not([type="submit"]), {{WRAPPER}} textarea, {{WRAPPER}} select' => 'color: {{VALUE}};',
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
					'{{WRAPPER}} input[type="submit"]' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'button_text_color',
			array(
				'label' => __( 'Texte', 'bsc-brew-manager' ),
				'type' => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} input[type="submit"]' => 'color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();
	}

	protected function render() {
		echo do_shortcode( '[bsc_add_recipe]' );
	}
}
