<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BSC_Widget_Single_Recipe extends \Elementor\Widget_Base {

	public function get_name() {
		return 'bsc_single_recipe';
	}

	public function get_title() {
		return __( 'Fiche Recette Complète', 'bsc-brew-manager' );
	}

	public function get_icon() {
		return 'eicon-single-post';
	}

	public function get_categories() {
		return [ 'bsc-brew-manager' ];
	}

	protected function register_controls() {
		$this->start_controls_section(
			'content_section',
			[
				'label' => __( 'Affichage', 'bsc-brew-manager' ),
				'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
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
        if ( ! is_singular( 'bsc_recipe' ) ) {
            echo '<p>' . __( 'Ce widget ne fonctionne que sur une page de recette individuelle.', 'bsc-brew-manager' ) . '</p>';
            if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
                echo '<p><em>' . __( '(Mode Éditeur : Veuillez prévisualiser une recette pour voir le rendu)', 'bsc-brew-manager' ) . '</em></p>';
            }
            return;
        }

        $post_id = get_the_ID();
        $meta = get_post_custom( $post_id );
        $settings = $this->get_settings_for_display();

        // Helper to get meta safely
        $get_m = function($key) use ($meta) {
            return isset($meta[$key][0]) ? $meta[$key][0] : '';
        };

        ?>
        <div class="bsc-single-recipe">
            <!-- Header: Title and Meta -->
            <div class="bsc-recipe-header" style="display:flex; gap:20px; flex-wrap:wrap; margin-bottom:30px;">
                <div class="bsc-recipe-img" style="flex:1; min-width:300px;">
                    <?php if ( has_post_thumbnail() ) { the_post_thumbnail('large', ['style'=>'width:100%; border-radius:8px;']); } ?>
                </div>
                <div class="bsc-recipe-info" style="flex:1; min-width:300px;">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                        <h1><?php the_title(); ?></h1>
                        <?php if ( get_current_user_id() === (int) get_post_field( 'post_author', $post_id ) ) : ?>
                             <div class="bsc-actions">
                                <?php
                                $edit_url = !empty($settings['submission_page_url']) ? add_query_arg('edit_id', $post_id, $settings['submission_page_url']) : '#';
                                ?>
                                <?php if ( $edit_url !== '#' ) : ?>
                                    <a href="<?php echo esc_url($edit_url); ?>" class="button" style="margin-right:10px; font-size:0.9em;"><?php _e('Modifier', 'bsc-brew-manager'); ?></a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="bsc-badges" style="margin-bottom:15px;">
                        <?php
                        $styles = get_the_terms( $post_id, 'bsc_style' );
                        if ( $styles ) { foreach($styles as $s) echo '<span class="badge" style="background:#eee; padding:5px 10px; border-radius:15px; margin-right:5px;">' . esc_html($s->name) . '</span>'; }

                        $levels = get_the_terms( $post_id, 'bsc_level' );
                        if ( $levels ) { foreach($levels as $l) echo '<span class="badge" style="background:#ddd; padding:5px 10px; border-radius:15px; margin-right:5px;">' . esc_html($l->name) . '</span>'; }
                        ?>
                    </div>

                    <div class="bsc-specs" style="display:grid; grid-template-columns: 1fr 1fr; gap:10px; background:#f9f9f9; padding:15px; border-radius:8px;">
                        <div><strong>ABV:</strong> <?php echo esc_html($get_m('bsc_abv')); ?>%</div>
                        <div><strong>IBU:</strong> <?php echo esc_html($get_m('bsc_ibu')); ?></div>
                        <div><strong>OG:</strong> <?php echo esc_html($get_m('bsc_og')); ?></div>
                        <div><strong>FG:</strong> <?php echo esc_html($get_m('bsc_fg')); ?></div>
                        <div><strong>Volume:</strong> <?php echo esc_html($get_m('bsc_batch_volume')); ?>L</div>
                        <div><strong>Méthode:</strong> <?php echo esc_html($get_m('bsc_method')); ?></div>
                    </div>
                </div>
            </div>

            <!-- Ingredients -->
            <div class="bsc-ingredients-section" style="margin-bottom:30px;">
                <h3><?php _e('Ingrédients', 'bsc-brew-manager'); ?></h3>
                <table style="width:100%; border-collapse:collapse;">
                    <thead>
                        <tr style="background:#eee; text-align:left;">
                            <th style="padding:10px;">Type</th>
                            <th style="padding:10px;">Nom</th>
                            <th style="padding:10px;">Quantité</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $json_ing = $get_m('bsc_ingredients_list');
                        $ingredients = json_decode($json_ing, true);
                        if ( is_array($ingredients) ) {
                            foreach ( $ingredients as $ing ) {
                                echo '<tr style="border-bottom:1px solid #eee;">';
                                echo '<td style="padding:10px;">' . esc_html($ing['type']) . '</td>';
                                echo '<td style="padding:10px;">' . esc_html($ing['name']) . '</td>';
                                echo '<td style="padding:10px;">' . esc_html($ing['qty']) . '</td>';
                                echo '</tr>';
                            }
                        } else {
                            // Fallback for text
                            echo '<tr><td colspan="3" style="padding:10px;">' . nl2br(esc_html($json_ing)) . '</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>

            <!-- Steps -->
            <div class="bsc-steps-section" style="margin-bottom:30px;">
                <h3><?php _e('Instructions / Paliers', 'bsc-brew-manager'); ?></h3>
                <div class="bsc-steps-content" style="background:#fff; border:1px solid #ddd; padding:20px; border-radius:8px;">
                    <?php echo nl2br(esc_html($get_m('bsc_mash_schedule'))); ?>
                </div>
            </div>

            <!-- Equipment & Notes -->
            <?php if ( $get_m('bsc_equipment') || $get_m('bsc_taste_notes') ) : ?>
            <div class="bsc-notes-section" style="display:grid; grid-template-columns: 1fr 1fr; gap:20px; margin-bottom:30px;">
                <?php if ( $get_m('bsc_equipment') ) : ?>
                <div>
                    <h4><?php _e('Matériel', 'bsc-brew-manager'); ?></h4>
                    <p><?php echo nl2br(esc_html($get_m('bsc_equipment'))); ?></p>
                </div>
                <?php endif; ?>
                <?php if ( $get_m('bsc_taste_notes') ) : ?>
                <div>
                    <h4><?php _e('Notes de dégustation', 'bsc-brew-manager'); ?></h4>
                    <p><em><?php echo nl2br(esc_html($get_m('bsc_taste_notes'))); ?></em></p>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Comments / Ratings -->
            <div class="bsc-comments-section">
                <?php comments_template(); ?>
            </div>

        </div>
        <?php
	}
}
