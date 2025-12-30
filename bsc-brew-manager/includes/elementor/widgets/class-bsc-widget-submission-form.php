<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BSC_Widget_Submission_Form extends \Elementor\Widget_Base {

	public function get_name() {
		return 'bsc_submission_form';
	}

	public function get_title() {
		return __( 'Formulaire de Soumission', 'bsc-brew-manager' );
	}

	public function get_icon() {
		return 'eicon-form-horizontal';
	}

	public function get_categories() {
		return [ 'bsc-brew-manager' ];
	}

	protected function register_controls() {
		$this->start_controls_section(
			'content_section',
			[
				'label' => __( 'Paramètres', 'bsc-brew-manager' ),
				'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'redirect_url',
			[
				'label' => __( 'Redirection après succès', 'bsc-brew-manager' ),
				'type' => \Elementor\Controls_Manager::TEXT,
				'placeholder' => __( 'https://...', 'bsc-brew-manager' ),
			]
		);

        $this->add_control(
			'default_status',
			[
				'label' => __( 'Statut par défaut', 'bsc-brew-manager' ),
				'type' => \Elementor\Controls_Manager::SELECT,
				'default' => 'publish',
                'options' => [
                    'publish' => __( 'Publié', 'bsc-brew-manager' ),
                    'draft' => __( 'Brouillon', 'bsc-brew-manager' ),
                ]
			]
		);

        $this->add_control(
			'allowed_roles',
			[
				'label' => __( 'Rôles autorisés', 'bsc-brew-manager' ),
				'type' => \Elementor\Controls_Manager::SELECT2,
                'multiple' => true,
				'default' => [],
                'description' => __( 'Laisser vide pour autoriser tous les utilisateurs connectés.', 'bsc-brew-manager' ),
                'options' => [
                    'subscriber' => 'Subscriber',
                    'editor' => 'Editor',
                    'administrator' => 'Administrator',
                    // Note: Ideally fetch roles dynamically
                ]
			]
		);

		$this->end_controls_section();
	}

	protected function render() {
		if ( ! is_user_logged_in() ) {
			echo '<p>' . __( 'Vous devez être connecté pour soumettre une recette.', 'bsc-brew-manager' ) . '</p>';
			return;
		}

        $settings = $this->get_settings_for_display();

        // Check Roles
        if ( ! empty( $settings['allowed_roles'] ) ) {
            $user = wp_get_current_user();
            $allowed = false;
            foreach ( $settings['allowed_roles'] as $role ) {
                if ( in_array( $role, (array) $user->roles ) ) {
                    $allowed = true;
                    break;
                }
            }
            if ( ! $allowed ) {
                echo '<p>' . __( 'Vous n\'avez pas la permission de soumettre une recette.', 'bsc-brew-manager' ) . '</p>';
                return;
            }
        }

        // Check for Edit Mode
        $edit_id = isset( $_GET['edit_id'] ) ? intval( $_GET['edit_id'] ) : 0;
        $recipe_data = null;
        $ingredients_data = [];

        if ( $edit_id > 0 ) {
            $post = get_post( $edit_id );
            if ( $post && $post->post_type === 'bsc_recipe' && intval($post->post_author) === get_current_user_id() ) {
                $recipe_data = $post;
                $ingredients_json = get_post_meta( $edit_id, 'bsc_ingredients_list', true );
                $ingredients_data = json_decode( $ingredients_json, true );
            }
        }

        // Helpers for values
        $val = function($key) use ($edit_id) {
            return $edit_id ? get_post_meta($edit_id, $key, true) : '';
        };

		?>
		<div class="bsc-submission-form-container">
			<form id="bsc-recipe-form" method="post" enctype="multipart/form-data">
				<?php wp_nonce_field( 'bsc_new_recipe_action', 'bsc_new_recipe_nonce' ); ?>
				<input type="hidden" name="action" value="bsc_submit_recipe">
                <?php if ( $edit_id ) : ?>
                    <input type="hidden" name="edit_post_id" value="<?php echo $edit_id; ?>">
                <?php endif; ?>

				<?php if ( ! empty( $settings['redirect_url'] ) ) : ?>
					<input type="hidden" name="redirect_to" value="<?php echo esc_url( $settings['redirect_url'] ); ?>">
				<?php endif; ?>

                <?php if ( ! empty( $settings['default_status'] ) ) : ?>
					<input type="hidden" name="post_status" value="<?php echo esc_attr( $settings['default_status'] ); ?>">
				<?php endif; ?>

				<div class="bsc-form-group">
					<label for="recipe_title"><?php _e( 'Nom de la Recette', 'bsc-brew-manager' ); ?> *</label>
					<input type="text" id="recipe_title" name="recipe_title" value="<?php echo $recipe_data ? esc_attr($recipe_data->post_title) : ''; ?>" required class="elementor-field">
				</div>

				<div class="bsc-form-row" style="display:flex; gap:10px;">
					<div class="bsc-form-group" style="flex:1;">
						<label for="bsc_style"><?php _e( 'Style', 'bsc-brew-manager' ); ?></label>
						<?php
                        $selected_style = $edit_id ? wp_get_post_terms($edit_id, 'bsc_style', ['fields' => 'ids']) : [];
                        $selected_style = !empty($selected_style) ? $selected_style[0] : 0;

						wp_dropdown_categories( array(
							'taxonomy' => 'bsc_style',
							'name' => 'bsc_style',
							'hide_empty' => false,
							'show_option_none' => __( 'Sélectionner un style', 'bsc-brew-manager' ),
							'class' => 'elementor-field',
                            'selected' => $selected_style,
						) );
						?>
					</div>
					<div class="bsc-form-group" style="flex:1;">
						<label for="bsc_level"><?php _e( 'Niveau', 'bsc-brew-manager' ); ?></label>
						<?php
                        $selected_level = $edit_id ? wp_get_post_terms($edit_id, 'bsc_level', ['fields' => 'ids']) : [];
                        $selected_level = !empty($selected_level) ? $selected_level[0] : 0;

						wp_dropdown_categories( array(
							'taxonomy' => 'bsc_level',
							'name' => 'bsc_level',
							'hide_empty' => false,
							'show_option_none' => __( 'Sélectionner un niveau', 'bsc-brew-manager' ),
							'class' => 'elementor-field',
                            'selected' => $selected_level,
						) );
						?>
					</div>
				</div>

				<div class="bsc-form-row" style="display:flex; gap:10px; flex-wrap:wrap;">
					<div class="bsc-form-group"><label>Volume (L)</label><input type="text" name="bsc_batch_volume" value="<?php echo esc_attr($val('bsc_batch_volume')); ?>" class="elementor-field"></div>
					<div class="bsc-form-group"><label>OG</label><input type="text" name="bsc_og" value="<?php echo esc_attr($val('bsc_og')); ?>" class="elementor-field"></div>
					<div class="bsc-form-group"><label>FG</label><input type="text" name="bsc_fg" value="<?php echo esc_attr($val('bsc_fg')); ?>" class="elementor-field"></div>
					<div class="bsc-form-group"><label>ABV (%)</label><input type="text" name="bsc_abv" value="<?php echo esc_attr($val('bsc_abv')); ?>" class="elementor-field"></div>
					<div class="bsc-form-group"><label>IBU</label><input type="text" name="bsc_ibu" value="<?php echo esc_attr($val('bsc_ibu')); ?>" class="elementor-field"></div>
				</div>

				<div class="bsc-form-group">
					<label><?php _e( 'Méthode', 'bsc-brew-manager' ); ?></label>
                    <?php $method = $val('bsc_method'); ?>
					<select name="bsc_method" class="elementor-field">
						<option value="All Grain" <?php selected($method, 'All Grain'); ?>>Tout Grain</option>
						<option value="BIAB" <?php selected($method, 'BIAB'); ?>>BIAB</option>
						<option value="Extract" <?php selected($method, 'Extract'); ?>>Extrait</option>
						<option value="Partial Mash" <?php selected($method, 'Partial Mash'); ?>>Partiel</option>
					</select>
				</div>

				<div class="bsc-form-group">
					<label><?php _e( 'Ingrédients', 'bsc-brew-manager' ); ?></label>
					<div id="bsc-ingredients-wrapper">
                        <?php
                        if ( ! empty($ingredients_data) && is_array($ingredients_data) ) :
                            foreach($ingredients_data as $ing) :
                        ?>
                            <div class="bsc-ingredient-row" style="display:flex; gap:5px; margin-bottom:5px;">
                                <input type="text" name="ing_name[]" value="<?php echo esc_attr($ing['name']); ?>" placeholder="Nom" style="flex:2;">
                                <input type="text" name="ing_qty[]" value="<?php echo esc_attr($ing['qty']); ?>" placeholder="Qté" style="flex:1;">
                                <select name="ing_type[]" style="flex:1;">
                                    <option value="Malt" <?php selected($ing['type'], 'Malt'); ?>>Malt</option>
                                    <option value="Houblon" <?php selected($ing['type'], 'Houblon'); ?>>Houblon</option>
                                    <option value="Levure" <?php selected($ing['type'], 'Levure'); ?>>Levure</option>
                                    <option value="Autre" <?php selected($ing['type'], 'Autre'); ?>>Autre</option>
                                </select>
                            </div>
                        <?php
                            endforeach;
                        else:
                        ?>
						    <div class="bsc-ingredient-row" style="display:flex; gap:5px; margin-bottom:5px;">
							    <input type="text" name="ing_name[]" placeholder="Nom (ex: Malt Pale Ale)" style="flex:2;">
							    <input type="text" name="ing_qty[]" placeholder="Qté (ex: 5kg)" style="flex:1;">
							    <select name="ing_type[]" style="flex:1;">
								    <option value="Malt">Malt</option>
								    <option value="Houblon">Houblon</option>
								    <option value="Levure">Levure</option>
								    <option value="Autre">Autre</option>
							    </select>
						    </div>
                        <?php endif; ?>
					</div>
					<button type="button" id="add-ingredient-btn" style="margin-top:5px;"><?php _e( '+ Ajouter un ingrédient', 'bsc-brew-manager' ); ?></button>
				</div>

				<div class="bsc-form-group">
					<label><?php _e( 'Instructions / Étapes de brassage', 'bsc-brew-manager' ); ?></label>
					<textarea name="bsc_mash_schedule" rows="5" class="elementor-field"><?php echo esc_textarea($val('bsc_mash_schedule')); ?></textarea>
				</div>

                <div class="bsc-form-group">
					<label><?php _e( 'Photo', 'bsc-brew-manager' ); ?></label>
                    <?php if ( has_post_thumbnail( $edit_id ) ) : ?>
                        <br><?php echo get_the_post_thumbnail( $edit_id, 'thumbnail' ); ?><br>
                        <em><?php _e( 'Uploader une nouvelle photo pour remplacer', 'bsc-brew-manager' ); ?></em>
                    <?php endif; ?>
					<input type="file" name="bsc_recipe_image" accept="image/*" class="elementor-field">
				</div>

				<div class="bsc-form-submit" style="margin-top:20px;">
					<button type="submit" class="button button-primary"><?php echo $edit_id ? __( 'Mettre à jour', 'bsc-brew-manager' ) : __( 'Publier la Recette', 'bsc-brew-manager' ); ?></button>
				</div>
			</form>

			<script>
			document.getElementById('add-ingredient-btn').addEventListener('click', function() {
				var wrapper = document.getElementById('bsc-ingredients-wrapper');
				var row = wrapper.firstElementChild.cloneNode(true);
				var inputs = row.querySelectorAll('input');
				inputs.forEach(function(input) { input.value = ''; });
				wrapper.appendChild(row);
			});
			</script>
		</div>
		<?php
	}
}
