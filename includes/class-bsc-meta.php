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
			'normal', // Changed to normal to give more space for address etc.
			'high'
		);
	}

	public function render_brewery_meta_box( $post ) {
		wp_nonce_field( 'bsc_save_meta_data', 'bsc_meta_nonce' );

		$supabase_id = get_post_meta( $post->ID, 'bsc_supabase_id', true );
		$avg_rating = get_post_meta( $post->ID, 'bsc_average_rating', true );
		$followers = get_post_meta( $post->ID, 'bsc_followers', true );

		echo '<div class="bsc-meta-grid" style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">';

		echo '<div>';
		echo '<p><label><strong>' . __( 'ID Supabase:', 'bsc-brew-manager' ) . '</strong></label><br>';
		echo '<input type="text" value="' . esc_attr( $supabase_id ) . '" readonly style="background:#eee; width:100%;" /></p>';
		echo '</div>';

		echo '<div>';
		echo '<p><label><strong>' . __( 'Stats:', 'bsc-brew-manager' ) . '</strong></label><br>';
		echo __( 'Note Moyenne:', 'bsc-brew-manager' ) . ' <strong>' . esc_html( $avg_rating ) . '/5</strong><br>';
		echo __( 'Abonnés:', 'bsc-brew-manager' ) . ' <strong>' . esc_html( $followers ) . '</strong></p>';
		echo '</div>';

		echo '</div>'; // End grid

		// Address & Contact Fields
		$fields = array(
			'bsc_contact_name' => __( 'Nom du contact', 'bsc-brew-manager' ),
			'bsc_address'      => __( 'Adresse', 'bsc-brew-manager' ),
			'bsc_city'         => __( 'Ville', 'bsc-brew-manager' ),
			'bsc_postcode'     => __( 'Code Postal', 'bsc-brew-manager' ),
			'bsc_country'      => __( 'Pays', 'bsc-brew-manager' ),
			'bsc_phone'        => __( 'Téléphone', 'bsc-brew-manager' ),
			'bsc_website'      => __( 'Site Web', 'bsc-brew-manager' ),
		);

		echo '<hr>';
		echo '<h4>' . __( 'Coordonnées', 'bsc-brew-manager' ) . '</h4>';
		echo '<div class="bsc-meta-grid" style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">';
		foreach ( $fields as $key => $label ) {
			$value = get_post_meta( $post->ID, $key, true );
			echo '<p><label for="' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label><br />';
			echo '<input type="text" id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '" style="width:100%;" /></p>';
		}
		echo '</div>';
	}

	public function render_recipe_meta_box( $post ) {
		wp_nonce_field( 'bsc_save_meta_data', 'bsc_meta_nonce' );

		// Brewery Selection
		$brewery_id = get_post_meta( $post->ID, 'bsc_brewery_id', true );
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

		// Supabase ID & Stats
		$supabase_id = get_post_meta( $post->ID, 'bsc_supabase_id', true );
		$avg_rating = get_post_meta( $post->ID, 'bsc_average_rating', true );
		$checkins = get_post_meta( $post->ID, 'bsc_total_checkins', true );

		echo '<div class="bsc-meta-grid" style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">';
		echo '<div><p><label>' . __( 'ID Supabase:', 'bsc-brew-manager' ) . '</label><br>';
		echo '<input type="text" value="' . esc_attr( $supabase_id ) . '" readonly style="background:#eee; width:100%;" /></p></div>';
		echo '<div><p>' . __( 'Note:', 'bsc-brew-manager' ) . ' <strong>' . esc_html( $avg_rating ) . '/5</strong><br>';
		echo __( 'Checkins:', 'bsc-brew-manager' ) . ' <strong>' . esc_html( $checkins ) . '</strong></p></div>';
		echo '</div>';

		// Style
		$styles = array( 'IPA', 'Stout', 'Lager', 'Pale Ale', 'Porter', 'Saison', 'Sour', 'Wheat Beer', 'Belgian', 'Other' );
		$current_style = get_post_meta( $post->ID, 'bsc_style', true );

		echo '<p><label for="bsc_style"><strong>' . __( 'Style', 'bsc-brew-manager' ) . '</strong></label><br>';
		echo '<select name="bsc_style" id="bsc_style" style="width:100%;">';
		foreach ( $styles as $style ) {
			echo '<option value="' . esc_attr( $style ) . '" ' . selected( $current_style, $style, false ) . '>' . esc_html( $style ) . '</option>';
		}
		echo '</select></p>';

		// Technical Details
		$fields = array(
			'bsc_abv'               => __( 'Degrés d\'alcool (%)', 'bsc-brew-manager' ),
			'bsc_ibu'               => __( 'IBU (Amertume)', 'bsc-brew-manager' ),
			'bsc_color'             => __( 'Couleur (EBC/SRM)', 'bsc-brew-manager' ),
			'bsc_batch_volume'      => __( 'Volume Brassé (L)', 'bsc-brew-manager' ),
			'bsc_boil_time'         => __( 'Temps d\'ébullition (min)', 'bsc-brew-manager' ),
			'bsc_fermentation_temp' => __( 'Température de fermentation (°C)', 'bsc-brew-manager' ),
		);

		echo '<h4>' . __( 'Caractéristiques', 'bsc-brew-manager' ) . '</h4>';
		echo '<div class="bsc-meta-grid" style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">';
		foreach ( $fields as $key => $label ) {
			$value = get_post_meta( $post->ID, $key, true );
			echo '<p><label for="' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label><br />';
			echo '<input type="text" id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '" style="width:100%;" /></p>';
		}
		echo '</div>';

		// Ingredients Textareas
		$textareas = array(
			'bsc_hops'             => __( 'Houblons (Hops)', 'bsc-brew-manager' ),
			'bsc_malts'            => __( 'Malts', 'bsc-brew-manager' ),
			'bsc_ingredients_list' => __( 'Liste complète Ingrédients', 'bsc-brew-manager' ),
			'bsc_mash_schedule'    => __( 'Processus / Paliers', 'bsc-brew-manager' ),
			'bsc_equipment'        => __( 'Matériel utilisé', 'bsc-brew-manager' ),
			'bsc_taste_notes'      => __( 'Notes de dégustation', 'bsc-brew-manager' ),
		);

		foreach ( $textareas as $key => $label ) {
			$value = get_post_meta( $post->ID, $key, true );
			echo '<p><label for="' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label><br />';
			echo '<textarea id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" rows="3" style="width:100%;">' . esc_textarea( $value ) . '</textarea></p>';
		}

		// Image Label
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

		$post_type = get_post_type( $post_id );

		if ( $post_type === 'bsc_brewery' ) {
			$fields = array(
				'bsc_contact_name', 'bsc_address', 'bsc_city', 'bsc_postcode',
				'bsc_country', 'bsc_phone', 'bsc_website'
			);
			foreach ( $fields as $field ) {
				if ( isset( $_POST[ $field ] ) ) {
					update_post_meta( $post_id, $field, sanitize_text_field( $_POST[ $field ] ) );
				}
			}
		} elseif ( $post_type === 'bsc_recipe' ) {
			$fields = array(
				'bsc_brewery_id', 'bsc_style',
				'bsc_abv', 'bsc_ibu', 'bsc_color',
				'bsc_batch_volume', 'bsc_boil_time', 'bsc_fermentation_temp',
				'bsc_hops', 'bsc_malts', 'bsc_ingredients_list',
				'bsc_mash_schedule', 'bsc_equipment', 'bsc_taste_notes',
				'bsc_label_image_id'
			);
			foreach ( $fields as $field ) {
				if ( isset( $_POST[ $field ] ) ) {
					update_post_meta( $post_id, $field, sanitize_textarea_field( $_POST[ $field ] ) ); // textarea for safety on larger fields
				}
			}
		}

		// Trigger Sync on Save
		if ( class_exists( 'BSC_Supabase' ) ) {
			$supabase = new BSC_Supabase();
			if ( $post_type === 'bsc_brewery' ) {
				$supabase->sync_brewery( $post_id );
			} elseif ( $post_type === 'bsc_recipe' ) {
				$supabase->sync_beer( $post_id );
			}
		}
	}
}
