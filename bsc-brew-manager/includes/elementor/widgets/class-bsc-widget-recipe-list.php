<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BSC_Widget_Recipe_List extends \Elementor\Widget_Base {

	public function get_name() {
		return 'bsc_recipe_list';
	}

	public function get_title() {
		return __( 'Liste des Recettes', 'bsc-brew-manager' );
	}

	public function get_icon() {
		return 'eicon-post-list';
	}

	public function get_categories() {
		return [ 'bsc-brew-manager' ];
	}

	protected function register_controls() {

		$this->start_controls_section(
			'content_section',
			[
				'label' => __( 'Contenu', 'bsc-brew-manager' ),
				'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'posts_per_page',
			[
				'label' => __( 'Nombre de recettes', 'bsc-brew-manager' ),
				'type' => \Elementor\Controls_Manager::NUMBER,
				'min' => 1,
				'max' => 100,
				'step' => 1,
				'default' => 6,
			]
		);

		$this->add_control(
			'orderby',
			[
				'label' => __( 'Trier par', 'bsc-brew-manager' ),
				'type' => \Elementor\Controls_Manager::SELECT,
				'default' => 'date',
				'options' => [
					'date' => __( 'Date', 'bsc-brew-manager' ),
					'rating' => __( 'Note', 'bsc-brew-manager' ),
					'rand' => __( 'Aléatoire', 'bsc-brew-manager' ),
				],
			]
		);

		$this->add_control(
			'show_filters',
			[
				'label' => __( 'Afficher les filtres', 'bsc-brew-manager' ),
				'type' => \Elementor\Controls_Manager::SWITCHER,
				'default' => 'yes',
			]
		);

		$this->end_controls_section();

        $this->start_controls_section(
			'style_section',
			[
				'label' => __( 'Style', 'bsc-brew-manager' ),
				'tab' => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

        $this->add_control(
			'layout_style',
			[
				'label' => __( 'Mise en page', 'bsc-brew-manager' ),
				'type' => \Elementor\Controls_Manager::SELECT,
				'default' => 'grid',
				'options' => [
					'grid' => __( 'Grille', 'bsc-brew-manager' ),
					'list' => __( 'Liste', 'bsc-brew-manager' ),
				],
			]
		);

        $this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();

		// Handle Frontend Filters
		$paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;
		$filter_search = isset( $_GET['bsc_search'] ) ? sanitize_text_field( $_GET['bsc_search'] ) : '';
		$filter_style = isset( $_GET['bsc_style'] ) ? intval( $_GET['bsc_style'] ) : '';
		$filter_level = isset( $_GET['bsc_level'] ) ? intval( $_GET['bsc_level'] ) : '';

		// Render Filter Form
		if ( 'yes' === $settings['show_filters'] ) {
			?>
			<form class="bsc-recipe-filters" method="get" style="background:#f9f9f9; padding:15px; border-radius:8px; margin-bottom:20px; display:flex; gap:10px; flex-wrap:wrap; align-items:flex-end;">
				<div style="flex:1; min-width:200px;">
					<label><?php _e( 'Rechercher', 'bsc-brew-manager' ); ?></label>
					<input type="text" name="bsc_search" value="<?php echo esc_attr( $filter_search ); ?>" placeholder="<?php _e( 'Nom de la recette...', 'bsc-brew-manager' ); ?>" style="width:100%;">
				</div>
				<div style="flex:1; min-width:150px;">
					<label><?php _e( 'Style', 'bsc-brew-manager' ); ?></label>
					<?php wp_dropdown_categories( array(
						'taxonomy' => 'bsc_style',
						'name' => 'bsc_style',
						'show_option_all' => __( 'Tous les styles', 'bsc-brew-manager' ),
						'selected' => $filter_style,
						'value_field' => 'term_id',
						'class' => '',
						'style' => 'width:100%',
					) ); ?>
				</div>
				<div style="flex:1; min-width:150px;">
					<label><?php _e( 'Niveau', 'bsc-brew-manager' ); ?></label>
					<?php wp_dropdown_categories( array(
						'taxonomy' => 'bsc_level',
						'name' => 'bsc_level',
						'show_option_all' => __( 'Tous les niveaux', 'bsc-brew-manager' ),
						'selected' => $filter_level,
						'value_field' => 'term_id',
						'class' => '',
						'style' => 'width:100%',
					) ); ?>
				</div>
				<button type="submit" class="button button-secondary"><?php _e( 'Filtrer', 'bsc-brew-manager' ); ?></button>
			</form>
			<?php
		}

		// Query Args
		$args = [
			'post_type' => 'bsc_recipe',
			'posts_per_page' => $settings['posts_per_page'],
			'post_status' => 'publish',
			'paged' => $paged,
		];

		// Apply Filters
		if ( ! empty( $filter_search ) ) {
			$args['s'] = $filter_search;
		}

		$tax_query = array();
		if ( ! empty( $filter_style ) ) {
			$tax_query[] = array(
				'taxonomy' => 'bsc_style',
				'field'    => 'term_id',
				'terms'    => $filter_style,
			);
		}
		if ( ! empty( $filter_level ) ) {
			$tax_query[] = array(
				'taxonomy' => 'bsc_level',
				'field'    => 'term_id',
				'terms'    => $filter_level,
			);
		}
		if ( count( $tax_query ) > 0 ) {
			$args['tax_query'] = $tax_query;
		}

		// Ordering
		if ( 'rating' === $settings['orderby'] ) {
			$args['meta_key'] = 'bsc_average_rating';
			$args['orderby'] = 'meta_value_num';
            $args['order'] = 'DESC';
		} else {
			$args['orderby'] = $settings['orderby'];
            $args['order'] = 'DESC';
		}

		$query = new \WP_Query( $args );

		if ( $query->have_posts() ) {
            $class = 'bsc-recipe-' . $settings['layout_style']; // grid or list
			echo '<div class="bsc-recipe-container ' . esc_attr( $class ) . '" style="display:grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap:20px;">';
			while ( $query->have_posts() ) {
				$query->the_post();
                $rating = get_post_meta( get_the_ID(), 'bsc_average_rating', true );
                $rating = $rating ? number_format($rating, 1) . '/5' : '-';
                $style_terms = get_the_terms( get_the_ID(), 'bsc_style' );
                $style_name = $style_terms ? $style_terms[0]->name : 'N/A';

				?>
				<div class="bsc-recipe-card" style="border:1px solid #ddd; padding:15px; border-radius:8px; background:#fff;">
                    <div class="bsc-card-img" style="height:150px; background:#eee; margin-bottom:10px; overflow:hidden;">
                        <?php if ( has_post_thumbnail() ) {
                            the_post_thumbnail('medium', ['style' => 'width:100%; height:100%; object-fit:cover;']);
                        } else {
                            echo '<div style="display:flex; align-items:center; justify-content:center; height:100%; color:#999;">No Image</div>';
                        } ?>
                    </div>
					<h3 class="bsc-card-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                    <div class="bsc-card-meta" style="font-size:0.9em; color:#666;">
                        <span>🍺 <?php echo esc_html( $style_name ); ?></span> |
                        <span>⭐ <?php echo esc_html( $rating ); ?></span>
                    </div>
                    <div class="bsc-card-excerpt" style="margin-top:10px; font-size:0.9em;">
                        <?php the_excerpt(); ?>
                    </div>
                    <a href="<?php the_permalink(); ?>" class="button" style="display:inline-block; margin-top:10px; text-decoration:none; background:#ffba00; color:#fff; padding:5px 10px; border-radius:4px;"><?php _e('Voir Recette', 'bsc-brew-manager'); ?></a>
				</div>
				<?php
			}
			echo '</div>';

            // Pagination
            $big = 999999999;
			echo '<div class="bsc-pagination" style="margin-top:20px; text-align:center;">';
			echo paginate_links( array(
				'base' => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
				'format' => '?paged=%#%',
				'current' => max( 1, get_query_var('paged') ),
				'total' => $query->max_num_pages
			) );
			echo '</div>';

			wp_reset_postdata();
		} else {
			echo '<p>' . __( 'Aucune recette trouvée.', 'bsc-brew-manager' ) . '</p>';
		}
	}
}
