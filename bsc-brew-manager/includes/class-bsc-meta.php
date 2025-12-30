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

		$fields = array(
			'bsc_batch_volume'      => __( 'Volume Brassé (L)', 'bsc-brew-manager' ),
			'bsc_abv'               => __( 'Degrés d\'alcool (%)', 'bsc-brew-manager' ),
			'bsc_color'             => __( 'Couleur (EBC/SRM)', 'bsc-brew-manager' ),
			'bsc_boil_time'         => __( 'Temps d\'ébullition (min)', 'bsc-brew-manager' ),
			'bsc_fermentation_temp' => __( 'Température de fermentation (°C)', 'bsc-brew-manager' ),
		);

		$textareas = array(
			'bsc_ingredients_list' => __( 'Liste des Ingrédients', 'bsc-brew-manager' ),
			'bsc_mash_schedule'    => __( 'Processus / Paliers de Température (Durée, Température)', 'bsc-brew-manager' ),
			'bsc_equipment'        => __( 'Matériel utilisé (Marque, Type)', 'bsc-brew-manager' ),
			'bsc_taste_notes'      => __( 'Goût / Notes de dégustation', 'bsc-brew-manager' ),
		);

		echo '<div class="bsc-meta-box">';

		// Simple Inputs
		foreach ( $fields as $key => $label ) {
			$value = get_post_meta( $post->ID, $key, true );
			echo '<p><label for="' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label><br />';
			echo '<input type="text" id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '" style="width:100%;" /></p>';
		}

		// Textareas
		foreach ( $textareas as $key => $label ) {
			$value = get_post_meta( $post->ID, $key, true );
			echo '<p><label for="' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label><br />';
			echo '<textarea id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" rows="5" style="width:100%;">' . esc_textarea( $value ) . '</textarea></p>';
		}

		// Image Label Handling (Simple version for now, assume ID or URL)
		// Ideally we use the WP Media Uploader JS here, but for brevity in this task,
		// I will create a basic input for Image URL or ID. The frontend will handle upload.
		// For the admin, let's just show an ID field or URL field for now.
		$label_img_id = get_post_meta( $post->ID, 'bsc_label_image_id', true );
		echo '<p><label for="bsc_label_image_id">' . __( 'ID Image de l\'étiquette (Upload via Frontend)', 'bsc-brew-manager' ) . '</label><br />';
		echo '<input type="number" id="bsc_label_image_id" name="bsc_label_image_id" value="' . esc_attr( $label_img_id ) . '" /></p>';
		if ( $label_img_id ) {
			echo wp_get_attachment_image( $label_img_id, 'thumbnail' );
		}

		echo '</div>';
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
			'bsc_abv',
			'bsc_color',
			'bsc_boil_time',
			'bsc_fermentation_temp',
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
