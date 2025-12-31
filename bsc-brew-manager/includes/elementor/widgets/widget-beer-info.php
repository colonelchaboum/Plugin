<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\Elementor\Widget_Base' ) ) {
	return;
}

class BSC_Elementor_Beer_Info_Widget extends \Elementor\Widget_Base {

	public function get_name() {
		return 'bsc_beer_info';
	}

	public function get_title() {
		return __( 'Infos Bière', 'bsc-brew-manager' );
	}

	public function get_icon() {
		return 'eicon-product-title';
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
			'beer_id',
			array(
				'label' => __( 'ID Bière (Laisser vide pour auto)', 'bsc-brew-manager' ),
				'type' => \Elementor\Controls_Manager::TEXT,
			)
		);

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$beer_id = $settings['beer_id'];

		if ( empty( $beer_id ) ) {
			$beer_id = get_the_ID();
		}

		if ( get_post_type( $beer_id ) !== 'bsc_recipe' ) {
			echo 'Bière non trouvée.';
			return;
		}

		$avg_rating = get_post_meta( $beer_id, 'bsc_average_rating', true );
		$abv = get_post_meta( $beer_id, 'bsc_abv', true );
		$style = get_post_meta( $beer_id, 'bsc_style', true );
		$brewery_id = get_post_meta( $beer_id, 'bsc_brewery_id', true );
		$brewery_name = $brewery_id ? get_the_title( $brewery_id ) : '';

		echo '<div class="bsc-beer-info">';
		echo '<h3>' . get_the_title( $beer_id ) . '</h3>';
		if ( $brewery_name ) {
			echo '<h4>Brasserie: ' . esc_html( $brewery_name ) . '</h4>';
		}
		echo '<p>Style: ' . esc_html( $style ) . ' | ABV: ' . esc_html( $abv ) . '%</p>';
		echo '<div class="bsc-rating">Note: ' . esc_html( $avg_rating ) . '/5</div>';

		// Recipe (Ingredients etc) - Only show if user has permission or public? Prompt says "visible uniquement sur le site"
		$ingredients = get_post_meta( $beer_id, 'bsc_ingredients_list', true );
		if ( $ingredients ) {
			echo '<div class="bsc-ingredients"><strong>Ingrédients:</strong><br>' . nl2br( esc_html( $ingredients ) ) . '</div>';
		}

		echo '</div>';
	}
}
