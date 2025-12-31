<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\Elementor\Widget_Base' ) ) {
	return;
}

class BSC_Elementor_Brewery_Info_Widget extends \Elementor\Widget_Base {

	public function get_name() {
		return 'bsc_brewery_info';
	}

	public function get_title() {
		return __( 'Infos Brasserie', 'bsc-brew-manager' );
	}

	public function get_icon() {
		return 'eicon-info-box';
	}

	public function get_categories() {
		return array( 'general' );
	}

	protected function register_controls() {
		$this->start_controls_section(
			'content_section',
			array(
				'label' => __( 'Contenu', 'bsc-brew-manager' ),
				'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'brewery_id',
			array(
				'label' => __( 'ID Brasserie (Laisser vide pour auto)', 'bsc-brew-manager' ),
				'type' => \Elementor\Controls_Manager::TEXT,
			)
		);

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$brewery_id = $settings['brewery_id'];

		if ( empty( $brewery_id ) ) {
			$brewery_id = get_the_ID();
		}

		if ( get_post_type( $brewery_id ) !== 'bsc_brewery' ) {
			echo 'Brasserie non trouvée.';
			return;
		}

		$avg_rating = get_post_meta( $brewery_id, 'bsc_average_rating', true );
		$logo_url = get_the_post_thumbnail_url( $brewery_id, 'medium' );

		echo '<div class="bsc-brewery-info">';
		if ( $logo_url ) {
			echo '<img src="' . esc_url( $logo_url ) . '" alt="' . esc_attr( get_the_title( $brewery_id ) ) . '">';
		}
		echo '<h3>' . get_the_title( $brewery_id ) . '</h3>';
		echo '<div class="bsc-rating">Note: ' . esc_html( $avg_rating ) . '/5</div>';
		echo '<div class="bsc-desc">' . get_the_excerpt( $brewery_id ) . '</div>';
		echo '</div>';
	}
}
