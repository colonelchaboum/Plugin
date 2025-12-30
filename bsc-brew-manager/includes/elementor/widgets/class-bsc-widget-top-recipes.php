<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BSC_Widget_Top_Recipes extends \Elementor\Widget_Base {

	public function get_name() {
		return 'bsc_top_recipes';
	}

	public function get_title() {
		return __( 'Top Recettes', 'bsc-brew-manager' );
	}

	public function get_icon() {
		return 'eicon-star';
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
			'posts_per_page',
			[
				'label' => __( 'Nombre de recettes', 'bsc-brew-manager' ),
				'type' => \Elementor\Controls_Manager::NUMBER,
				'default' => 3,
			]
		);

        $this->add_control(
			'period',
			[
				'label' => __( 'Période', 'bsc-brew-manager' ),
				'type' => \Elementor\Controls_Manager::SELECT,
				'default' => 'all_time',
				'options' => [
					'all_time' => __( 'Tout le temps', 'bsc-brew-manager' ),
                    // Date query implementation required for others, keeping simple for now
				],
			]
		);

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();

		$args = [
			'post_type' => 'bsc_recipe',
			'posts_per_page' => $settings['posts_per_page'],
			'post_status' => 'publish',
            'meta_key' => 'bsc_average_rating',
            'orderby' => 'meta_value_num',
            'order' => 'DESC',
		];

		$query = new \WP_Query( $args );

		echo '<div class="bsc-top-recipes-list">';
        if ( $query->have_posts() ) {
            echo '<ul style="list-style:none; padding:0; margin:0;">';
			while ( $query->have_posts() ) {
				$query->the_post();
                $rating = get_post_meta( get_the_ID(), 'bsc_average_rating', true );
                $rating = $rating ? number_format($rating, 1) : 'N/A';
				?>
				<li style="display:flex; align-items:center; gap:15px; margin-bottom:15px; padding-bottom:15px; border-bottom:1px solid #eee;">
                    <div style="width:60px; height:60px; border-radius:50%; overflow:hidden; background:#eee;">
                        <?php if ( has_post_thumbnail() ) { the_post_thumbnail('thumbnail', ['style'=>'width:100%; height:100%; object-fit:cover;']); } ?>
                    </div>
                    <div style="flex:1;">
                        <h4 style="margin:0;"><a href="<?php the_permalink(); ?>" style="text-decoration:none; color:inherit;"><?php the_title(); ?></a></h4>
                        <div style="font-size:0.9em; color:#666;">
                            <span style="color:#ffba00;">★ <?php echo $rating; ?></span> |
                            <?php echo get_the_author(); ?>
                        </div>
                    </div>
				</li>
				<?php
			}
            echo '</ul>';
			wp_reset_postdata();
		} else {
			echo '<p>' . __( 'Aucune recette notée pour le moment.', 'bsc-brew-manager' ) . '</p>';
		}
        echo '</div>';
	}
}
