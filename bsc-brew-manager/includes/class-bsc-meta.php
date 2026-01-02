<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BSC_Meta {

	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post', array( $this, 'save_meta_boxes' ) );
	}

	public function add_meta_boxes() {
		add_meta_box(
			'bsc_recipe_details',
			__( 'Détails de la Recette', 'bsc-brew-manager' ),
			array( $this, 'render_meta_box' ),
			'bsc_recipe',
			'normal',
			'high'
		);
	}

	public function render_meta_box( $post ) {
		wp_nonce_field( 'bsc_save_recipe_data', 'bsc_recipe_nonce' );

		// Standard numeric/text fields
		$fields = array(
			'bsc_batch_volume'      => __( 'Volume Brassé (L)', 'bsc-brew-manager' ),
			'bsc_og'                => __( 'OG (Densité Initiale)', 'bsc-brew-manager' ),
			'bsc_fg'                => __( 'FG (Densité Finale)', 'bsc-brew-manager' ),
			'bsc_abv'               => __( 'ABV (%)', 'bsc-brew-manager' ),
			'bsc_ibu'               => __( 'IBU (Amertume)', 'bsc-brew-manager' ),
			'bsc_color'             => __( 'Couleur (EBC/SRM)', 'bsc-brew-manager' ),
			'bsc_boil_time'         => __( 'Temps d\'ébullition (min)', 'bsc-brew-manager' ),
			'bsc_fermentation_temp' => __( 'Température de fermentation (°C)', 'bsc-brew-manager' ),
			'bsc_method'            => __( 'Méthode (Tout Grain, BIAB, etc)', 'bsc-brew-manager' ),
		);

		// Complex text areas
		$textareas = array(
			'bsc_ingredients_list' => __( 'Ingrédients (Format JSON pour frontend, texte libre ici)', 'bsc-brew-manager' ),
			'bsc_mash_schedule'    => __( 'Étapes de Brassage / Paliers', 'bsc-brew-manager' ),
			'bsc_equipment'        => __( 'Matériel utilisé', 'bsc-brew-manager' ),
			'bsc_taste_notes'      => __( 'Notes de dégustation', 'bsc-brew-manager' ),
		);

		echo '<div class="bsc-meta-box" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">';

		// Column 1: Metrics
		echo '<div><h3>' . __( 'Métriques', 'bsc-brew-manager' ) . '</h3>';
		foreach ( $fields as $key => $label ) {
			$value = get_post_meta( $post->ID, $key, true );
			echo '<p><label for="' . esc_attr( $key ) . '"><strong>' . esc_html( $label ) . '</strong></label><br />';
			echo '<input type="text" id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '" style="width:100%;" /></p>';
		}
		echo '</div>';

		// Column 2: Details & Textareas
		echo '<div><h3>' . __( 'Détails & Processus', 'bsc-brew-manager' ) . '</h3>';
		foreach ( $textareas as $key => $label ) {
			$value = get_post_meta( $post->ID, $key, true );
			echo '<p><label for="' . esc_attr( $key ) . '"><strong>' . esc_html( $label ) . '</strong></label><br />';
			echo '<textarea id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" rows="4" style="width:100%;">' . esc_textarea( $value ) . '</textarea></p>';
		}

		// Image ID
		$label_img_id = get_post_meta( $post->ID, 'bsc_label_image_id', true );
		echo '<p><label for="bsc_label_image_id"><strong>' . __( 'ID Image (Label)', 'bsc-brew-manager' ) . '</strong></label><br />';
		echo '<input type="number" id="bsc_label_image_id" name="bsc_label_image_id" value="' . esc_attr( $label_img_id ) . '" style="width:100px;" />';
		if ( $label_img_id ) {
			echo '<br>' . wp_get_attachment_image( $label_img_id, 'thumbnail' );
		}
		echo '</p>';

		echo '</div>'; // End Col 2

		echo '</div>'; // End Grid
	}

	public function save_meta_boxes( $post_id ) {
		if ( ! isset( $_POST['bsc_recipe_nonce'] ) || ! wp_verify_nonce( $_POST['bsc_recipe_nonce'], 'bsc_save_recipe_data' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$fields = array(
			'bsc_batch_volume',
			'bsc_og',
			'bsc_fg',
			'bsc_abv',
			'bsc_ibu',
			'bsc_color',
			'bsc_boil_time',
			'bsc_fermentation_temp',
			'bsc_method',
			'bsc_ingredients_list',
			'bsc_mash_schedule',
			'bsc_equipment',
			'bsc_taste_notes',
			'bsc_label_image_id',
		);

		foreach ( $fields as $field ) {
			if ( isset( $_POST[ $field ] ) ) {
				update_post_meta( $post_id, $field, sanitize_text_field( $_POST[ $field ] ) );
			}
		}
	}
}
