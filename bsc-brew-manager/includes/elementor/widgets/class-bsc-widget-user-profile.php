<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BSC_Widget_User_Profile extends \Elementor\Widget_Base {

	public function get_name() {
		return 'bsc_user_profile';
	}

	public function get_title() {
		return __( 'Profil Brasseur', 'bsc-brew-manager' );
	}

	public function get_icon() {
		return 'eicon-user-circle-o';
	}

	public function get_categories() {
		return [ 'bsc-brew-manager' ];
	}

	protected function register_controls() {
		$this->start_controls_section(
			'content_section',
			[
				'label' => __( 'Configuration', 'bsc-brew-manager' ),
				'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);

        $this->add_control(
			'user_source',
			[
				'label' => __( 'Utilisateur à afficher', 'bsc-brew-manager' ),
				'type' => \Elementor\Controls_Manager::SELECT,
				'default' => 'current',
				'options' => [
					'current' => __( 'Utilisateur connecté', 'bsc-brew-manager' ),
                    'author' => __( 'Auteur de la page actuelle', 'bsc-brew-manager' ),
				],
			]
		);

        $this->add_control(
			'submission_page_url',
			[
				'label' => __( 'URL de la page de soumission (pour édition)', 'bsc-brew-manager' ),
				'type' => \Elementor\Controls_Manager::TEXT,
                'placeholder' => 'https://...',
			]
		);

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
        $user = null;

        if ( 'current' === $settings['user_source'] ) {
            $user = wp_get_current_user();
        } elseif ( 'author' === $settings['user_source'] ) {
            $user = get_user_by( 'ID', get_queried_object_id() ); // Works if on author archive
            if ( ! $user && is_singular() ) {
                $user = get_user_by( 'ID', get_post_field( 'post_author', get_the_ID() ) );
            }
        }

        if ( ! $user || ! $user->exists() ) {
             if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
                echo '<p>' . __( 'Aucun utilisateur trouvé.', 'bsc-brew-manager' ) . '</p>';
            }
            return;
        }

        // Stats
        $args = [
            'post_type' => 'bsc_recipe',
            'author' => $user->ID,
            'post_status' => 'publish',
            'posts_per_page' => -1,
        ];
        $query = new \WP_Query($args);
        $count = $query->found_posts;

        // Calculate average rating across all recipes
        $total_rating = 0;
        $rated_recipes = 0;
        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();
                $r = get_post_meta( get_the_ID(), 'bsc_average_rating', true );
                if ( $r ) {
                    $total_rating += floatval($r);
                    $rated_recipes++;
                }
            }
            // Rewind for the list
            $query->rewind_posts();
        }
        $global_avg = $rated_recipes > 0 ? round( $total_rating / $rated_recipes, 2 ) : 'N/A';

		?>
		<div class="bsc-profile-card" style="padding:20px; border:1px solid #ddd; border-radius:10px; background:#fff;">
            <div style="text-align:center;">
                <div class="bsc-profile-avatar" style="margin-bottom:15px;">
                    <?php echo get_avatar( $user->ID, 96, '', '', ['class' => 'style="border-radius:50%;"'] ); ?>
                </div>
                <h2 class="bsc-profile-name"><?php echo esc_html( $user->display_name ); ?></h2>
                <p class="bsc-profile-bio" style="font-style:italic; color:#666;">
                    <?php echo esc_html( get_user_meta( $user->ID, 'description', true ) ); ?>
                </p>

                <div class="bsc-profile-stats" style="display:flex; justify-content:center; gap:30px; margin-top:20px;">
                    <div class="stat-box">
                        <strong style="display:block; font-size:1.5em;"><?php echo $count; ?></strong>
                        <span style="color:#666; font-size:0.8em;"><?php _e('Recettes', 'bsc-brew-manager'); ?></span>
                    </div>
                    <div class="stat-box">
                        <strong style="display:block; font-size:1.5em;"><?php echo $global_avg; ?></strong>
                        <span style="color:#666; font-size:0.8em;"><?php _e('Note Moyenne', 'bsc-brew-manager'); ?></span>
                    </div>
                </div>
            </div>

            <?php
            // Display User's Recipes if requested (basic list)
            if ( $query->have_posts() ) : ?>
                <div class="bsc-profile-recipes" style="margin-top:30px; border-top:1px solid #eee; padding-top:20px;">
                    <h4 style="margin-bottom:15px;"><?php _e('Dernières Recettes', 'bsc-brew-manager'); ?></h4>
                    <ul style="list-style:none; padding:0; margin:0;">
                    <?php while ( $query->have_posts() ) : $query->the_post(); ?>
                        <li style="display:flex; justify-content:space-between; align-items:center; padding:10px; border-bottom:1px solid #f5f5f5;">
                            <div>
                                <a href="<?php the_permalink(); ?>" style="text-decoration:none; font-weight:bold;"><?php the_title(); ?></a>
                                <span style="font-size:0.8em; color:#999; margin-left:10px;"><?php echo get_the_date(); ?></span>
                            </div>
                            <?php if ( get_current_user_id() === $user->ID ) : ?>
                                <div class="bsc-actions">
                                    <?php
                                    $edit_url = !empty($settings['submission_page_url']) ? add_query_arg('edit_id', get_the_ID(), $settings['submission_page_url']) : '#';
                                    ?>
                                    <?php if ( $edit_url !== '#' ) : ?>
                                        <a href="<?php echo esc_url($edit_url); ?>" style="margin-right:10px; font-size:0.9em;"><?php _e('Modifier', 'bsc-brew-manager'); ?></a>
                                    <?php endif; ?>

                                    <form method="post" action="" style="display:inline;" onsubmit="return confirm('<?php _e('Êtes-vous sûr ?', 'bsc-brew-manager'); ?>');">
                                        <input type="hidden" name="action" value="bsc_submit_recipe">
                                        <input type="hidden" name="bsc_delete_recipe" value="<?php the_ID(); ?>">
                                        <?php wp_nonce_field( 'bsc_new_recipe_action', 'bsc_new_recipe_nonce' ); ?>
                                        <button type="submit" style="background:none; border:none; color:red; cursor:pointer; font-size:0.9em; padding:0;"><?php _e('Supprimer', 'bsc-brew-manager'); ?></button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </li>
                    <?php endwhile; ?>
                    </ul>
                </div>
            <?php wp_reset_postdata(); endif; ?>

            <?php if ( 'current' === $settings['user_source'] && is_user_logged_in() && !empty($settings['submission_page_url']) ) : ?>
                <div style="margin-top:20px; text-align:center;">
                    <a href="<?php echo esc_url( $settings['submission_page_url'] ); ?>" class="button button-primary"><?php _e( '+ Nouvelle Recette', 'bsc-brew-manager' ); ?></a>
                </div>
            <?php endif; ?>
		</div>
		<?php
	}
}
