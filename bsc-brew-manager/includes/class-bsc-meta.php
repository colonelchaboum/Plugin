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
			__( 'Détails de la Bière', 'bsc-brew-manager' ),
			array( $this, 'render_recipe_meta_box' ),
			'bsc_recipe',
			'normal',
			'high'
		);

		add_meta_box(
			'bsc_brewery_details',
			__( 'Détails de la Brasserie', 'bsc-brew-manager' ),
			array( $this, 'render_brewery_meta_box' ),
			'bsc_brewery',
			'side',
			'default'
		);
	}

	public function render_brewery_meta_box( $post ) {
		wp_nonce_field( 'bsc_save_meta_data', 'bsc_meta_nonce' );

		$supabase_id = get_post_meta( $post->ID, 'bsc_supabase_id', true );
		$avg_rating = get_post_meta( $post->ID, 'bsc_average_rating', true );

		echo '<p><label>' . __( 'ID Supabase:', 'bsc-brew-manager' ) . '</label><br>';
		echo '<input type="text" value="' . esc_attr( $supabase_id ) . '" readonly style="background:#eee; width:100%;" /></p>';

		echo '<p><label>' . __( 'Note Moyenne:', 'bsc-brew-manager' ) . '</label><br>';
		echo '<input type="text" value="' . esc_attr( $avg_rating ) . '" readonly style="background:#eee; width:100%;" /></p>';
	}

	public function render_recipe_meta_box( $post ) {
		wp_nonce_field( 'bsc_save_meta_data', 'bsc_meta_nonce' );

		// Brewery Selection
		$brewery_id = get_post_meta( $post->ID, 'bsc_brewery_id', true );
		// Get all breweries (admin can see all, usually we limit to user but for admin screen show all)
		// For simplicity in admin, we show all breweries.
		$breweries = get_posts( array(
			'post_type' => 'bsc_brewery',
			'numberposts' => -1,
			'post_status' => 'publish'
		) );

		echo '<p><label for="bsc_brewery_id"><strong>' . __( 'Brasserie', 'bsc-brew-manager' ) . '</strong></label><br>';
		echo '<select name="bsc_brewery_id" id="bsc_brewery_id" style="width:100%;">';
		echo '<option value="">' . __( 'Sélectionner une brasserie', 'bsc-brew-manager' ) . '</option>';
		foreach ( $breweries as $brewery ) {
			echo '<option value="' . $brewery->ID . '" ' . selected( $brewery_id, $brewery->ID, false ) . '>';
			echo esc_html( $brewery->post_title ) . ' (ID: ' . $brewery->ID . ')';
			echo '</option>';
		}
		echo '</select></p>';

		// Supabase ID
		$supabase_id = get_post_meta( $post->ID, 'bsc_supabase_id', true );
		echo '<p><label>' . __( 'ID Supabase:', 'bsc-brew-manager' ) . '</label><br>';
		echo '<input type="text" value="' . esc_attr( $supabase_id ) . '" readonly style="background:#eee; width:100%;" /></p>';

		// Style
		// Styles List
		$styles = array( 'IPA', 'Stout', 'Lager', 'Pale Ale', 'Porter', 'Saison', 'Sour', 'Wheat Beer', 'Belgian', 'Other' );
		$current_style = get_post_meta( $post->ID, 'bsc_style', true ); // Using Meta for now as requested "Menu déroulant"

		echo '<p><label for="bsc_style"><strong>' . __( 'Style', 'bsc-brew-manager' ) . '</strong></label><br>';
		echo '<select name="bsc_style" id="bsc_style" style="width:100%;">';
		foreach ( $styles as $style ) {
			echo '<option value="' . esc_attr( $style ) . '" ' . selected( $current_style, $style, false ) . '>' . esc_html( $style ) . '</option>';
		}
		echo '</select></p>';


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

		// Image Label Handling
		$label_img_id = get_post_meta( $post->ID, 'bsc_label_image_id', true );
		echo '<p><label for="bsc_label_image_id">' . __( 'ID Image de l\'étiquette', 'bsc-brew-manager' ) . '</label><br />';
		echo '<input type="number" id="bsc_label_image_id" name="bsc_label_image_id" value="' . esc_attr( $label_img_id ) . '" /></p>';
		if ( $label_img_id ) {
			echo wp_get_attachment_image( $label_img_id, 'thumbnail' );
		}

		echo '</div>';
	}

	public function save_meta_boxes( $post_id ) {
		if ( ! isset( $_POST['bsc_meta_nonce'] ) || ! wp_verify_nonce( $_POST['bsc_meta_nonce'], 'bsc_save_meta_data' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Brewery Fields (none to save manually really, except maybe if we added editables, but Supabase ID is read only)
		// But if we wanted to save something:

		// Recipe Fields
		$fields = array(
			'bsc_brewery_id',
			'bsc_style',
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

		// Trigger Sync on Save
		if ( class_exists( 'BSC_Supabase' ) ) {
			$supabase = new BSC_Supabase();
			if ( get_post_type( $post_id ) === 'bsc_brewery' ) {
				$supabase->sync_brewery( $post_id );
			} elseif ( get_post_type( $post_id ) === 'bsc_recipe' ) {
				$supabase->sync_beer( $post_id );
			}
		}
	}
}
