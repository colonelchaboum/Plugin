<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BSC_Form_Handler {

	public function __construct() {
		// Keep the shortcode for backward compatibility or direct use
		add_shortcode( 'bsc_add_recipe', array( $this, 'render_form' ) );
		// Listen for the generic submission hook (used by both Shortcode and Elementor Widget)
		add_action( 'init', array( $this, 'handle_form_submission' ) );
	}

	public function render_form() {
		if ( ! is_user_logged_in() ) {
			return '<p>' . __( 'Vous devez être connecté pour ajouter une recette.', 'bsc-brew-manager' ) . '</p>';
		}

		ob_start();
		?>
		<div class="bsc-submission-form-container">
			<form id="bsc-recipe-form-shortcode" method="post" enctype="multipart/form-data">
				<?php wp_nonce_field( 'bsc_new_recipe_action', 'bsc_new_recipe_nonce' ); ?>
				<input type="hidden" name="action" value="bsc_submit_recipe">

				<p>
					<label for="recipe_title"><?php _e( 'Nom de la Recette', 'bsc-brew-manager' ); ?> *</label><br>
					<input type="text" id="recipe_title" name="recipe_title" required style="width:100%;">
				</p>

				<div style="display:flex; gap:10px; margin-bottom:15px;">
					<div style="flex:1;">
						<label for="bsc_style"><?php _e( 'Style', 'bsc-brew-manager' ); ?></label><br>
						<?php
						wp_dropdown_categories( array(
							'taxonomy' => 'bsc_style',
							'name' => 'bsc_style',
							'hide_empty' => false,
							'show_option_none' => __( 'Sélectionner un style', 'bsc-brew-manager' ),
							'style' => 'width:100%;',
						) );
						?>
					</div>
					<div style="flex:1;">
						<label for="bsc_level"><?php _e( 'Niveau', 'bsc-brew-manager' ); ?></label><br>
						<?php
						wp_dropdown_categories( array(
							'taxonomy' => 'bsc_level',
							'name' => 'bsc_level',
							'hide_empty' => false,
							'show_option_none' => __( 'Sélectionner un niveau', 'bsc-brew-manager' ),
							'style' => 'width:100%;',
						) );
						?>
					</div>
				</div>

				<div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(100px, 1fr)); gap:10px; margin-bottom:15px;">
					<div><label>Volume (L)</label><br><input type="text" name="bsc_batch_volume" style="width:100%;"></div>
					<div><label>OG</label><br><input type="text" name="bsc_og" style="width:100%;"></div>
					<div><label>FG</label><br><input type="text" name="bsc_fg" style="width:100%;"></div>
					<div><label>ABV (%)</label><br><input type="text" name="bsc_abv" style="width:100%;"></div>
					<div><label>IBU</label><br><input type="text" name="bsc_ibu" style="width:100%;"></div>
				</div>

				<p>
					<label><?php _e( 'Méthode', 'bsc-brew-manager' ); ?></label><br>
					<select name="bsc_method" style="width:100%;">
						<option value="All Grain">Tout Grain</option>
						<option value="BIAB">BIAB</option>
						<option value="Extract">Extrait</option>
						<option value="Partial Mash">Partiel</option>
					</select>
				</p>

				<div style="margin-bottom:15px; border:1px solid #ddd; padding:10px;">
					<label><strong><?php _e( 'Ingrédients', 'bsc-brew-manager' ); ?></strong></label>
					<div id="bsc-ingredients-wrapper-sc">
						<div class="bsc-ingredient-row" style="display:flex; gap:5px; margin-bottom:5px;">
							<input type="text" name="ing_name[]" placeholder="Nom" style="flex:2;">
							<input type="text" name="ing_qty[]" placeholder="Qté" style="flex:1;">
							<select name="ing_type[]" style="flex:1;">
								<option value="Malt">Malt</option>
								<option value="Houblon">Houblon</option>
								<option value="Levure">Levure</option>
								<option value="Autre">Autre</option>
							</select>
						</div>
					</div>
					<button type="button" onclick="var w=document.getElementById('bsc-ingredients-wrapper-sc'); var r=w.firstElementChild.cloneNode(true); r.querySelectorAll('input').forEach(i=>i.value=''); w.appendChild(r);"><?php _e( '+', 'bsc-brew-manager' ); ?></button>
				</div>

				<p>
					<label><?php _e( 'Instructions', 'bsc-brew-manager' ); ?></label><br>
					<textarea name="bsc_mash_schedule" rows="5" style="width:100%;"></textarea>
				</p>

				<p>
					<label><?php _e( 'Photo', 'bsc-brew-manager' ); ?></label><br>
					<input type="file" name="bsc_recipe_image" accept="image/*">
				</p>

				<p>
					<button type="submit" class="button button-primary"><?php _e( 'Publier', 'bsc-brew-manager' ); ?></button>
				</p>
			</form>
		</div>
		<?php
		return ob_get_clean();
	}

	public function handle_form_submission() {
        // Check for our custom action trigger (from Elementor widget or Shortcode)
		if ( ! isset( $_POST['action'] ) || $_POST['action'] !== 'bsc_submit_recipe' ) {
            // Also check legacy check
            if ( ! isset( $_POST['bsc_submit_recipe'] ) ) {
			    return;
            }
		}

		if ( ! is_user_logged_in() ) {
			return;
		}

        // Verify Nonce (Check both new and old nonce names for compatibility)
        $nonce_valid = false;
        if ( isset( $_POST['bsc_new_recipe_nonce'] ) && wp_verify_nonce( $_POST['bsc_new_recipe_nonce'], 'bsc_new_recipe_action' ) ) {
            $nonce_valid = true;
        } elseif ( isset( $_POST['bsc_add_recipe_nonce'] ) && wp_verify_nonce( $_POST['bsc_add_recipe_nonce'], 'bsc_add_recipe_action' ) ) {
            $nonce_valid = true;
        }

		if ( ! $nonce_valid ) {
			return; // Invalid security token
		}

        // --- Data Processing ---

		// Check for Deletion
		if ( isset( $_POST['bsc_delete_recipe'] ) && ! empty( $_POST['bsc_delete_recipe'] ) ) {
			$delete_id = intval( $_POST['bsc_delete_recipe'] );
			$post = get_post( $delete_id );
			if ( $post && $post->post_type === 'bsc_recipe' && intval( $post->post_author ) === get_current_user_id() ) {
				wp_delete_post( $delete_id, true );
				// Redirect to profile or home
				wp_redirect( home_url() );
				exit;
			}
			return;
		}

		$edit_id = isset( $_POST['edit_post_id'] ) ? intval( $_POST['edit_post_id'] ) : 0;
		$title = isset($_POST['recipe_title']) ? sanitize_text_field( $_POST['recipe_title'] ) : ( isset($_POST['bsc_title']) ? sanitize_text_field($_POST['bsc_title']) : 'Untitled' );

        // Content/Description might not be in the Elementor form?
        // Let's check if 'bsc_description' or maybe just empty content
        $content = isset($_POST['bsc_description']) ? wp_kses_post( $_POST['bsc_description'] ) : '';

		$post_status = isset($_POST['post_status']) ? sanitize_text_field($_POST['post_status']) : 'publish';

		$post_args = array(
			'post_title'   => $title,
			'post_content' => $content,
			'post_status'  => $post_status,
			'post_type'    => 'bsc_recipe',
			'post_author'  => get_current_user_id(),
		);

		if ( $edit_id > 0 ) {
			// Update Existing
			$existing_post = get_post( $edit_id );
			if ( $existing_post && intval( $existing_post->post_author ) === get_current_user_id() ) {
				$post_args['ID'] = $edit_id;
				$post_id = wp_update_post( $post_args );
			} else {
				return; // Unauthorized
			}
		} else {
			// Create New
			$post_id = wp_insert_post( $post_args );
		}

		if ( is_wp_error( $post_id ) ) {
			return;
		}

		// Save Meta Fields
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
            'bsc_mash_schedule',
			'bsc_equipment',
            'bsc_taste_notes'
		);

		foreach ( $fields as $field ) {
			if ( isset( $_POST[ $field ] ) ) {
				update_post_meta( $post_id, $field, sanitize_textarea_field( $_POST[ $field ] ) );
			}
		}

        // Handle Ingredients (Construct JSON from dynamic fields if present)
        if ( isset($_POST['ing_name']) && is_array($_POST['ing_name']) ) {
            $ingredients = array();
            $names = $_POST['ing_name'];
            $qtys = $_POST['ing_qty'];
            $types = $_POST['ing_type'];

            for ( $i = 0; $i < count($names); $i++ ) {
                if ( ! empty( $names[$i] ) ) {
                    $ingredients[] = array(
                        'name' => sanitize_text_field( $names[$i] ),
                        'qty'  => sanitize_text_field( $qtys[$i] ),
                        'type' => sanitize_text_field( $types[$i] ),
                    );
                }
            }
            update_post_meta( $post_id, 'bsc_ingredients_list', json_encode( $ingredients, JSON_UNESCAPED_UNICODE ) );
        } elseif ( isset( $_POST['bsc_ingredients_list'] ) ) {
            // Legacy Textarea fallback
            update_post_meta( $post_id, 'bsc_ingredients_list', sanitize_textarea_field( $_POST['bsc_ingredients_list'] ) );
        }

        // Handle Taxonomies (Style and Level)
        if ( isset( $_POST['bsc_style'] ) && $_POST['bsc_style'] != '-1' ) {
            wp_set_object_terms( $post_id, intval( $_POST['bsc_style'] ), 'bsc_style' );
        }
        if ( isset( $_POST['bsc_level'] ) && $_POST['bsc_level'] != '-1' ) {
            wp_set_object_terms( $post_id, intval( $_POST['bsc_level'] ), 'bsc_level' );
        }


		// Handle File Uploads
		require_once( ABSPATH . 'wp-admin/includes/image.php' );
		require_once( ABSPATH . 'wp-admin/includes/file.php' );
		require_once( ABSPATH . 'wp-admin/includes/media.php' );

		// Beer Image (Featured)
        // Check both potential input names
        $image_input_name = isset($_FILES['bsc_recipe_image']) ? 'bsc_recipe_image' : 'bsc_beer_image';

		if ( ! empty( $_FILES[$image_input_name]['name'] ) ) {
			$attachment_id = media_handle_upload( $image_input_name, $post_id );
			if ( ! is_wp_error( $attachment_id ) ) {
				set_post_thumbnail( $post_id, $attachment_id );
			}
		}

		// Label Image (If used)
		if ( ! empty( $_FILES['bsc_label_image']['name'] ) ) {
			$attachment_id = media_handle_upload( 'bsc_label_image', $post_id );
			if ( ! is_wp_error( $attachment_id ) ) {
				update_post_meta( $post_id, 'bsc_label_image_id', $attachment_id );
			}
		}

		// Redirect
        if ( isset( $_POST['redirect_to'] ) && ! empty( $_POST['redirect_to'] ) ) {
            wp_redirect( esc_url( $_POST['redirect_to'] ) );
            exit;
        } else {
            wp_redirect( get_permalink( $post_id ) );
            exit;
        }
	}
}
