<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BSC_Form_Handler {

	public function __construct() {
		add_shortcode( 'bsc_add_recipe', array( $this, 'render_form' ) );
		add_action( 'init', array( $this, 'handle_form_submission' ) );
	}

	public function render_form() {
		if ( ! is_user_logged_in() ) {
			return '<p>' . __( 'Vous devez être connecté pour ajouter une recette.', 'bsc-brew-manager' ) . '</p>';
		}

		ob_start();
		?>
		<div class="bsc-recipe-form">
			<form method="post" enctype="multipart/form-data">
				<?php wp_nonce_field( 'bsc_add_recipe_action', 'bsc_add_recipe_nonce' ); ?>

				<p>
					<label for="bsc_title"><?php _e( 'Nom de la Bière', 'bsc-brew-manager' ); ?></label>
					<input type="text" name="bsc_title" id="bsc_title" required class="widefat">
				</p>

				<p>
					<label for="bsc_description"><?php _e( 'Description / Histoire', 'bsc-brew-manager' ); ?></label>
					<?php wp_editor( '', 'bsc_description', array( 'media_buttons' => false, 'textarea_rows' => 5 ) ); ?>
				</p>

				<h3><?php _e( 'Détails techniques', 'bsc-brew-manager' ); ?></h3>

				<div class="bsc-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
					<p>
						<label><?php _e( 'Volume (L)', 'bsc-brew-manager' ); ?></label>
						<input type="number" step="0.1" name="bsc_batch_volume">
					</p>
					<p>
						<label><?php _e( 'ABV (%)', 'bsc-brew-manager' ); ?></label>
						<input type="number" step="0.1" name="bsc_abv">
					</p>
					<p>
						<label><?php _e( 'Couleur (EBC)', 'bsc-brew-manager' ); ?></label>
						<input type="text" name="bsc_color">
					</p>
					<p>
						<label><?php _e( 'Temps d\'ébullition (min)', 'bsc-brew-manager' ); ?></label>
						<input type="number" name="bsc_boil_time">
					</p>
					<p>
						<label><?php _e( 'Temp. Fermentation (°C)', 'bsc-brew-manager' ); ?></label>
						<input type="text" name="bsc_fermentation_temp">
					</p>
				</div>

				<p>
					<label><?php _e( 'Ingrédients (Malt, Houblon, Levure)', 'bsc-brew-manager' ); ?></label>
					<textarea name="bsc_ingredients_list" rows="5" class="widefat" placeholder="<?php _e('Lister les ingrédients et quantités...', 'bsc-brew-manager'); ?>"></textarea>
				</p>

				<p>
					<label><?php _e( 'Processus / Paliers', 'bsc-brew-manager' ); ?></label>
					<textarea name="bsc_mash_schedule" rows="5" class="widefat" placeholder="<?php _e('Détails du brassage...', 'bsc-brew-manager'); ?>"></textarea>
				</p>

				<p>
					<label><?php _e( 'Matériel utilisé', 'bsc-brew-manager' ); ?></label>
					<textarea name="bsc_equipment" rows="3" class="widefat" placeholder="<?php _e('Marque, Cuve, etc...', 'bsc-brew-manager'); ?>"></textarea>
				</p>

				<p>
					<label><?php _e( 'Notes de dégustation (Goût)', 'bsc-brew-manager' ); ?></label>
					<textarea name="bsc_taste_notes" rows="3" class="widefat"></textarea>
				</p>

				<h3><?php _e( 'Photos', 'bsc-brew-manager' ); ?></h3>
				<p>
					<label><?php _e( 'Photo de la Bière', 'bsc-brew-manager' ); ?></label>
					<input type="file" name="bsc_beer_image" accept="image/*">
				</p>
				<p>
					<label><?php _e( 'Photo de l\'étiquette', 'bsc-brew-manager' ); ?></label>
					<input type="file" name="bsc_label_image" accept="image/*">
				</p>

				<p>
					<input type="submit" name="bsc_submit_recipe" value="<?php _e( 'Enregistrer la recette', 'bsc-brew-manager' ); ?>" class="button button-primary">
				</p>
			</form>
		</div>
		<?php
		return ob_get_clean();
	}

	public function handle_form_submission() {
		if ( ! isset( $_POST['bsc_submit_recipe'] ) ) {
			return;
		}

		if ( ! is_user_logged_in() ) {
			return;
		}

		if ( ! isset( $_POST['bsc_add_recipe_nonce'] ) || ! wp_verify_nonce( $_POST['bsc_add_recipe_nonce'], 'bsc_add_recipe_action' ) ) {
			return;
		}

		$title = sanitize_text_field( $_POST['bsc_title'] );
		$content = wp_kses_post( $_POST['bsc_description'] );

		$post_id = wp_insert_post( array(
			'post_title'   => $title,
			'post_content' => $content,
			'post_status'  => 'publish', // Or 'pending' if review is needed
			'post_type'    => 'bsc_recipe',
			'post_author'  => get_current_user_id(),
		) );

		if ( is_wp_error( $post_id ) ) {
			// Handle error - ideally show a message to user
			return;
		}

		// Save Meta
		$fields = array(
			'bsc_batch_volume', 'bsc_abv', 'bsc_color', 'bsc_boil_time',
			'bsc_fermentation_temp', 'bsc_ingredients_list', 'bsc_mash_schedule',
			'bsc_equipment', 'bsc_taste_notes'
		);

		foreach ( $fields as $field ) {
			if ( isset( $_POST[ $field ] ) ) {
				update_post_meta( $post_id, $field, sanitize_textarea_field( $_POST[ $field ] ) );
			}
		}

		// Handle File Uploads
		require_once( ABSPATH . 'wp-admin/includes/image.php' );
		require_once( ABSPATH . 'wp-admin/includes/file.php' );
		require_once( ABSPATH . 'wp-admin/includes/media.php' );

		// Beer Image (Featured)
		if ( ! empty( $_FILES['bsc_beer_image']['name'] ) ) {
			$attachment_id = media_handle_upload( 'bsc_beer_image', $post_id );
			if ( ! is_wp_error( $attachment_id ) ) {
				set_post_thumbnail( $post_id, $attachment_id );
			}
		}

		// Label Image
		if ( ! empty( $_FILES['bsc_label_image']['name'] ) ) {
			$attachment_id = media_handle_upload( 'bsc_label_image', $post_id );
			if ( ! is_wp_error( $attachment_id ) ) {
				update_post_meta( $post_id, 'bsc_label_image_id', $attachment_id );
			}
		}

		// Redirect to the new post
		wp_redirect( get_permalink( $post_id ) );
		exit;
	}
}
