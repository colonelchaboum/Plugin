<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BSC_Form_Handler {

	public function __construct() {
		add_shortcode( 'bsc_add_recipe', array( $this, 'render_recipe_form' ) );
		add_shortcode( 'bsc_add_brewery', array( $this, 'render_brewery_form' ) );
		add_action( 'init', array( $this, 'handle_recipe_submission' ) );
		add_action( 'init', array( $this, 'handle_brewery_submission' ) );
	}

	public function render_brewery_form() {
		if ( ! is_user_logged_in() ) {
			return '<p>' . __( 'Vous devez être connecté pour gérer une brasserie.', 'bsc-brew-manager' ) . '</p>';
		}

		$post_id = 0;
		$title = '';
		$content = '';
		$meta = array();

		if ( isset( $_GET['edit_id'] ) ) {
			$post_id = intval( $_GET['edit_id'] );
			$post = get_post( $post_id );
			if ( $post && $post->post_type === 'bsc_brewery' && $post->post_author == get_current_user_id() ) {
				$title = $post->post_title;
				$content = $post->post_content;
				$meta = get_post_meta( $post_id );
			} else {
				$post_id = 0;
			}
		}

		$get_meta = function( $key ) use ( $meta ) {
			return isset( $meta[$key][0] ) ? $meta[$key][0] : '';
		};

		ob_start();
		?>
		<div class="bsc-brewery-form">
			<form method="post" enctype="multipart/form-data">
				<?php wp_nonce_field( 'bsc_add_brewery_action', 'bsc_add_brewery_nonce' ); ?>
				<?php if ( $post_id ) : ?>
					<input type="hidden" name="bsc_post_id" value="<?php echo esc_attr( $post_id ); ?>">
				<?php endif; ?>

				<p>
					<label for="bsc_brewery_title"><?php _e( 'Nom de la Brasserie', 'bsc-brew-manager' ); ?></label>
					<input type="text" name="bsc_brewery_title" id="bsc_brewery_title" value="<?php echo esc_attr( $title ); ?>" required class="widefat">
				</p>
				<p>
					<label for="bsc_brewery_desc"><?php _e( 'Description', 'bsc-brew-manager' ); ?></label>
					<?php wp_editor( $content, 'bsc_brewery_desc', array( 'media_buttons' => false, 'textarea_rows' => 5 ) ); ?>
				</p>

				<div class="bsc-grid" style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
					<p>
						<label><?php _e( 'Nom Contact', 'bsc-brew-manager' ); ?></label>
						<input type="text" name="bsc_contact_name" value="<?php echo esc_attr( $get_meta('bsc_contact_name') ); ?>" class="widefat">
					</p>
					<p>
						<label><?php _e( 'Téléphone', 'bsc-brew-manager' ); ?></label>
						<input type="text" name="bsc_phone" value="<?php echo esc_attr( $get_meta('bsc_phone') ); ?>" class="widefat">
					</p>
					<p>
						<label><?php _e( 'Site Web', 'bsc-brew-manager' ); ?></label>
						<input type="text" name="bsc_website" value="<?php echo esc_attr( $get_meta('bsc_website') ); ?>" class="widefat">
					</p>
					<p>
						<label><?php _e( 'Pays', 'bsc-brew-manager' ); ?></label>
						<input type="text" name="bsc_country" value="<?php echo esc_attr( $get_meta('bsc_country') ); ?>" class="widefat">
					</p>
				</div>

				<p>
					<label><?php _e( 'Adresse', 'bsc-brew-manager' ); ?></label>
					<input type="text" name="bsc_address" value="<?php echo esc_attr( $get_meta('bsc_address') ); ?>" class="widefat">
				</p>
				<div class="bsc-grid" style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
					<p>
						<label><?php _e( 'Ville', 'bsc-brew-manager' ); ?></label>
						<input type="text" name="bsc_city" value="<?php echo esc_attr( $get_meta('bsc_city') ); ?>" class="widefat">
					</p>
					<p>
						<label><?php _e( 'Code Postal', 'bsc-brew-manager' ); ?></label>
						<input type="text" name="bsc_postcode" value="<?php echo esc_attr( $get_meta('bsc_postcode') ); ?>" class="widefat">
					</p>
				</div>

				<p>
					<label><?php _e( 'Logo de la Brasserie', 'bsc-brew-manager' ); ?></label>
					<input type="file" name="bsc_brewery_logo" accept="image/*">
					<?php if ( $post_id && has_post_thumbnail( $post_id ) ) {
						echo '<br>' . get_the_post_thumbnail( $post_id, 'thumbnail' );
					} ?>
				</p>
				<p>
					<input type="submit" name="bsc_submit_brewery" value="<?php echo $post_id ? __( 'Mettre à jour', 'bsc-brew-manager' ) : __( 'Créer la Brasserie', 'bsc-brew-manager' ); ?>" class="button button-primary">
				</p>
			</form>
		</div>
		<?php
		return ob_get_clean();
	}

	public function handle_brewery_submission() {
		if ( ! isset( $_POST['bsc_submit_brewery'] ) ) return;
		if ( ! is_user_logged_in() ) return;
		if ( ! isset( $_POST['bsc_add_brewery_nonce'] ) || ! wp_verify_nonce( $_POST['bsc_add_brewery_nonce'], 'bsc_add_brewery_action' ) ) return;

		$title = sanitize_text_field( $_POST['bsc_brewery_title'] );
		$content = wp_kses_post( $_POST['bsc_brewery_desc'] );
		$post_id = 0;

		if ( isset( $_POST['bsc_post_id'] ) ) {
			$post_id = intval( $_POST['bsc_post_id'] );
			$existing_post = get_post( $post_id );
			if ( $existing_post && $existing_post->post_author == get_current_user_id() ) {
				wp_update_post( array(
					'ID'           => $post_id,
					'post_title'   => $title,
					'post_content' => $content,
				) );
			} else {
				return;
			}
		} else {
			$post_id = wp_insert_post( array(
				'post_title'   => $title,
				'post_content' => $content,
				'post_status'  => 'pending',
				'post_type'    => 'bsc_brewery',
				'post_author'  => get_current_user_id(),
			) );
		}

		if ( ! is_wp_error( $post_id ) && $post_id > 0 ) {
			// Save Meta
			$fields = array( 'bsc_contact_name', 'bsc_phone', 'bsc_website', 'bsc_country', 'bsc_address', 'bsc_city', 'bsc_postcode' );
			foreach ( $fields as $field ) {
				if ( isset( $_POST[ $field ] ) ) {
					update_post_meta( $post_id, $field, sanitize_text_field( $_POST[ $field ] ) );
				}
			}

			// Handle Image
			require_once( ABSPATH . 'wp-admin/includes/image.php' );
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
			require_once( ABSPATH . 'wp-admin/includes/media.php' );

			if ( ! empty( $_FILES['bsc_brewery_logo']['name'] ) ) {
				$attachment_id = media_handle_upload( 'bsc_brewery_logo', $post_id );
				if ( ! is_wp_error( $attachment_id ) ) {
					set_post_thumbnail( $post_id, $attachment_id );
				}
			}

			// Trigger Supabase Sync
			if ( class_exists( 'BSC_Supabase' ) ) {
				$supabase = new BSC_Supabase();
				$supabase->sync_brewery( $post_id );
			}

			$redirect_url = remove_query_arg( 'edit_id' );
			wp_redirect( add_query_arg( 'updated', 'true', $redirect_url ) );
			exit;
		}
	}

	public function render_recipe_form() {
		if ( ! is_user_logged_in() ) {
			return '<p>' . __( 'Vous devez être connecté pour gérer une bière.', 'bsc-brew-manager' ) . '</p>';
		}

		$post_id = 0;
		$title = '';
		$content = '';
		$meta = array();

		if ( isset( $_GET['edit_id'] ) ) {
			$post_id = intval( $_GET['edit_id'] );
			$post = get_post( $post_id );
			if ( $post && $post->post_type === 'bsc_recipe' && $post->post_author == get_current_user_id() ) {
				$title = $post->post_title;
				$content = $post->post_content;
				$meta = get_post_meta( $post_id );
			} else {
				$post_id = 0;
			}
		}

		$get_meta = function( $key ) use ( $meta ) {
			return isset( $meta[$key][0] ) ? $meta[$key][0] : '';
		};

		$user_id = get_current_user_id();
		$breweries = get_posts( array(
			'post_type' => 'bsc_brewery',
			'author'    => $user_id,
			'numberposts' => -1,
			'post_status' => array( 'publish', 'pending', 'draft' )
		) );

		if ( empty( $breweries ) ) {
			return '<p>' . __( 'Vous devez d\'abord créer une brasserie avant d\'ajouter une bière.', 'bsc-brew-manager' ) . '</p>';
		}

		$preselected_brewery_id = isset( $_GET['brewery_id'] ) ? intval( $_GET['brewery_id'] ) : 0;

		ob_start();
		?>
		<div class="bsc-recipe-form">
			<form method="post" enctype="multipart/form-data">
				<?php wp_nonce_field( 'bsc_add_recipe_action', 'bsc_add_recipe_nonce' ); ?>
				<?php if ( $post_id ) : ?>
					<input type="hidden" name="bsc_post_id" value="<?php echo esc_attr( $post_id ); ?>">
				<?php endif; ?>

				<p>
					<label for="bsc_brewery_id"><?php _e( 'Brasserie', 'bsc-brew-manager' ); ?></label>
					<select name="bsc_brewery_id" id="bsc_brewery_id" required class="widefat">
						<?php
						$current_brewery = $get_meta('bsc_brewery_id');
						if ( ! $current_brewery && $preselected_brewery_id ) {
							$current_brewery = $preselected_brewery_id;
						}
						foreach ( $breweries as $brewery ) : ?>
							<option value="<?php echo esc_attr( $brewery->ID ); ?>" <?php selected( $current_brewery, $brewery->ID ); ?>><?php echo esc_html( $brewery->post_title ); ?></option>
						<?php endforeach; ?>
					</select>
				</p>

				<p>
					<label for="bsc_title"><?php _e( 'Nom de la Bière', 'bsc-brew-manager' ); ?></label>
					<input type="text" name="bsc_title" id="bsc_title" value="<?php echo esc_attr( $title ); ?>" required class="widefat">
				</p>

				<p>
					<label for="bsc_style"><?php _e( 'Style', 'bsc-brew-manager' ); ?></label>
					<select name="bsc_style" id="bsc_style" class="widefat">
						<?php
						$styles = array( 'IPA', 'Stout', 'Lager', 'Pale Ale', 'Porter', 'Saison', 'Sour', 'Wheat Beer', 'Belgian', 'Other' );
						$current_style = $get_meta('bsc_style');
						foreach ( $styles as $style ) {
							echo '<option value="' . esc_attr( $style ) . '" ' . selected( $current_style, $style, false ) . '>' . esc_html( $style ) . '</option>';
						}
						?>
					</select>
				</p>

				<p>
					<label for="bsc_description"><?php _e( 'Description / Histoire', 'bsc-brew-manager' ); ?></label>
					<?php wp_editor( $content, 'bsc_description', array( 'media_buttons' => false, 'textarea_rows' => 5 ) ); ?>
				</p>

				<h3><?php _e( 'Détails techniques', 'bsc-brew-manager' ); ?></h3>

				<div class="bsc-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
					<p>
						<label><?php _e( 'Volume (L)', 'bsc-brew-manager' ); ?></label>
						<input type="number" step="0.1" name="bsc_batch_volume" value="<?php echo esc_attr( $get_meta('bsc_batch_volume') ); ?>">
					</p>
					<p>
						<label><?php _e( 'ABV (%)', 'bsc-brew-manager' ); ?></label>
						<input type="number" step="0.1" name="bsc_abv" value="<?php echo esc_attr( $get_meta('bsc_abv') ); ?>">
					</p>
					<p>
						<label><?php _e( 'IBU', 'bsc-brew-manager' ); ?></label>
						<input type="number" name="bsc_ibu" value="<?php echo esc_attr( $get_meta('bsc_ibu') ); ?>">
					</p>
					<p>
						<label><?php _e( 'Couleur (EBC)', 'bsc-brew-manager' ); ?></label>
						<input type="text" name="bsc_color" value="<?php echo esc_attr( $get_meta('bsc_color') ); ?>">
					</p>
					<p>
						<label><?php _e( 'Temps d\'ébullition (min)', 'bsc-brew-manager' ); ?></label>
						<input type="number" name="bsc_boil_time" value="<?php echo esc_attr( $get_meta('bsc_boil_time') ); ?>">
					</p>
					<p>
						<label><?php _e( 'Temp. Fermentation (°C)', 'bsc-brew-manager' ); ?></label>
						<input type="text" name="bsc_fermentation_temp" value="<?php echo esc_attr( $get_meta('bsc_fermentation_temp') ); ?>">
					</p>
				</div>

				<p>
					<label><?php _e( 'Houblons (Hops)', 'bsc-brew-manager' ); ?></label>
					<textarea name="bsc_hops" rows="3" class="widefat" placeholder="<?php _e('Citra, Mosaic...', 'bsc-brew-manager'); ?>"><?php echo esc_textarea( $get_meta('bsc_hops') ); ?></textarea>
				</p>
				<p>
					<label><?php _e( 'Malts', 'bsc-brew-manager' ); ?></label>
					<textarea name="bsc_malts" rows="3" class="widefat" placeholder="<?php _e('Pilsner, Munich...', 'bsc-brew-manager'); ?>"><?php echo esc_textarea( $get_meta('bsc_malts') ); ?></textarea>
				</p>

				<p>
					<label><?php _e( 'Liste Ingrédients Complète', 'bsc-brew-manager' ); ?></label>
					<textarea name="bsc_ingredients_list" rows="5" class="widefat" placeholder="<?php _e('Lister les ingrédients et quantités...', 'bsc-brew-manager'); ?>"><?php echo esc_textarea( $get_meta('bsc_ingredients_list') ); ?></textarea>
				</p>

				<p>
					<label><?php _e( 'Processus / Paliers', 'bsc-brew-manager' ); ?></label>
					<textarea name="bsc_mash_schedule" rows="5" class="widefat" placeholder="<?php _e('Détails du brassage...', 'bsc-brew-manager'); ?>"><?php echo esc_textarea( $get_meta('bsc_mash_schedule') ); ?></textarea>
				</p>

				<p>
					<label><?php _e( 'Matériel utilisé', 'bsc-brew-manager' ); ?></label>
					<textarea name="bsc_equipment" rows="3" class="widefat" placeholder="<?php _e('Marque, Cuve, etc...', 'bsc-brew-manager'); ?>"><?php echo esc_textarea( $get_meta('bsc_equipment') ); ?></textarea>
				</p>

				<p>
					<label><?php _e( 'Notes de dégustation (Goût)', 'bsc-brew-manager' ); ?></label>
					<textarea name="bsc_taste_notes" rows="3" class="widefat"><?php echo esc_textarea( $get_meta('bsc_taste_notes') ); ?></textarea>
				</p>

				<h3><?php _e( 'Photos', 'bsc-brew-manager' ); ?></h3>
				<p>
					<label><?php _e( 'Photo de la Bière', 'bsc-brew-manager' ); ?></label>
					<input type="file" name="bsc_beer_image" accept="image/*">
					<?php if ( $post_id && has_post_thumbnail( $post_id ) ) {
						echo '<br>' . get_the_post_thumbnail( $post_id, 'thumbnail' );
					} ?>
				</p>
				<p>
					<label><?php _e( 'Photo de l\'étiquette', 'bsc-brew-manager' ); ?></label>
					<input type="file" name="bsc_label_image" accept="image/*">
				</p>

				<p>
					<input type="submit" name="bsc_submit_recipe" value="<?php echo $post_id ? __( 'Mettre à jour', 'bsc-brew-manager' ) : __( 'Enregistrer la recette', 'bsc-brew-manager' ); ?>" class="button button-primary">
				</p>
			</form>
		</div>
		<?php
		return ob_get_clean();
	}

	public function handle_recipe_submission() {
		if ( ! isset( $_POST['bsc_submit_recipe'] ) ) return;
		if ( ! is_user_logged_in() ) return;
		if ( ! isset( $_POST['bsc_add_recipe_nonce'] ) || ! wp_verify_nonce( $_POST['bsc_add_recipe_nonce'], 'bsc_add_recipe_action' ) ) return;

		$title = sanitize_text_field( $_POST['bsc_title'] );
		$content = wp_kses_post( $_POST['bsc_description'] );
		$post_id = 0;

		if ( isset( $_POST['bsc_post_id'] ) ) {
			$post_id = intval( $_POST['bsc_post_id'] );
			$existing_post = get_post( $post_id );
			if ( $existing_post && $existing_post->post_author == get_current_user_id() ) {
				wp_update_post( array(
					'ID' => $post_id,
					'post_title' => $title,
					'post_content' => $content,
				) );
			} else {
				return;
			}
		} else {
			$post_id = wp_insert_post( array(
				'post_title'   => $title,
				'post_content' => $content,
				'post_status'  => 'pending',
				'post_type'    => 'bsc_recipe',
				'post_author'  => get_current_user_id(),
			) );
		}

		if ( is_wp_error( $post_id ) ) return;

		// Save Meta
		$fields = array(
			'bsc_brewery_id', 'bsc_style',
			'bsc_batch_volume', 'bsc_abv', 'bsc_ibu', 'bsc_color', 'bsc_boil_time',
			'bsc_fermentation_temp', 'bsc_ingredients_list', 'bsc_mash_schedule',
			'bsc_equipment', 'bsc_taste_notes', 'bsc_hops', 'bsc_malts'
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

		if ( ! empty( $_FILES['bsc_beer_image']['name'] ) ) {
			$attachment_id = media_handle_upload( 'bsc_beer_image', $post_id );
			if ( ! is_wp_error( $attachment_id ) ) {
				set_post_thumbnail( $post_id, $attachment_id );
			}
		}

		if ( ! empty( $_FILES['bsc_label_image']['name'] ) ) {
			$attachment_id = media_handle_upload( 'bsc_label_image', $post_id );
			if ( ! is_wp_error( $attachment_id ) ) {
				update_post_meta( $post_id, 'bsc_label_image_id', $attachment_id );
			}
		}

		// Trigger Supabase Sync
		if ( class_exists( 'BSC_Supabase' ) ) {
			$supabase = new BSC_Supabase();
			$supabase->sync_beer( $post_id );
		}

		$redirect_url = remove_query_arg( 'edit_id' );
		wp_redirect( add_query_arg( 'updated', 'true', get_permalink( $post_id ) ) );
		exit;
	}
}
