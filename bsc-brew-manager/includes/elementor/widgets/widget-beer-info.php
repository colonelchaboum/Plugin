<?php
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! class_exists( '\Elementor\Widget_Base' ) ) return;

class BSC_Elementor_Beer_Info_Widget extends \Elementor\Widget_Base {

	public function get_name() { return 'bsc_beer_info'; }
	public function get_title() { return __( 'Infos Bière', 'bsc-brew-manager' ); }
	public function get_icon() { return 'eicon-product-title'; }
	public function get_categories() { return array( 'general' ); }

	protected function register_controls() {
		$this->start_controls_section(
			'content_section',
			array(
				'label' => __( 'Contenu', 'bsc-brew-manager' ),
				'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'beer_id',
			array(
				'label' => __( 'ID Bière (Laisser vide pour auto)', 'bsc-brew-manager' ),
				'type' => \Elementor\Controls_Manager::TEXT,
			)
		);

        $this->add_control(
			'show_timeline',
			array(
				'label' => __( 'Afficher la Timeline', 'bsc-brew-manager' ),
				'type' => \Elementor\Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->end_controls_section();

		// Style Title
		$this->start_controls_section(
			'section_style_title',
			array(
				'label' => __( 'Titre', 'bsc-brew-manager' ),
				'tab' => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);
		$this->add_control(
			'title_color',
			array(
				'label' => __( 'Couleur', 'bsc-brew-manager' ),
				'type' => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .bsc-beer-info h3' => 'color: {{VALUE}};' ),
			)
		);
		$this->end_controls_section();

        // Style Timeline
        $this->start_controls_section(
			'section_style_timeline',
			array(
				'label' => __( 'Timeline Style', 'bsc-brew-manager' ),
				'tab' => \Elementor\Controls_Manager::TAB_STYLE,
                'condition' => ['show_timeline' => 'yes'],
			)
		);

        $this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name' => 'timeline_typography',
				'label' => __( 'Typography', 'bsc-brew-manager' ),
				'selector' => '{{WRAPPER}} .bsc-recipe-builder, {{WRAPPER}} .bsc-recipe-builder h4, {{WRAPPER}} .bsc-step-title-input',
			)
		);

        $colors = [
            'mash' => 'Mash (Blue)',
            'boil' => 'Boil (Red)',
            'ferment' => 'Ferment (Purple)',
            'dryhop' => 'Dry Hop (Green)',
            'aging' => 'Aging (Brown)',
            'package' => 'Package (Green)',
            'whirlpool' => 'Whirlpool (Orange)',
            'chill' => 'Chill (Cyan)',
            'sparge' => 'Sparge (Light Blue)',
            'carb' => 'Carb (Grey)',
            'prep' => 'Prep (Grey)',
        ];

        foreach($colors as $key => $label) {
            $this->add_control(
                'color_' . $key,
                array(
                    'label' => __( $label, 'bsc-brew-manager' ),
                    'type' => \Elementor\Controls_Manager::COLOR,
                    'selectors' => array(
                        '{{WRAPPER}} .bsc-bg-' . $key => 'background-color: {{VALUE}} !important;',
                    ),
                )
            );
        }

        $this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$beer_id = $settings['beer_id'];

		if ( empty( $beer_id ) ) {
			$beer_id = get_the_ID();
		}

		if ( get_post_type( $beer_id ) !== 'bsc_recipe' ) {
			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
                echo 'Bière non trouvée (ou contexte invalide).';
            }
			return;
		}

		$avg_rating = get_post_meta( $beer_id, 'bsc_average_rating', true );
		$abv = get_post_meta( $beer_id, 'bsc_abv', true );
		$style = get_post_meta( $beer_id, 'bsc_style', true );
		$brewery_id = get_post_meta( $beer_id, 'bsc_brewery_id', true );
		$brewery_name = $brewery_id ? get_the_title( $brewery_id ) : '';

		echo '<div class="bsc-beer-info">';
		echo '<h3>' . get_the_title( $beer_id ) . '</h3>';
		if ( $brewery_name ) {
			echo '<h4>Brasserie: ' . esc_html( $brewery_name ) . '</h4>';
		}
		echo '<p>Style: ' . esc_html( $style ) . ' | ABV: ' . esc_html( $abv ) . '%</p>';
		echo '<div class="bsc-rating">Note: ' . esc_html( $avg_rating ) . '/5</div>';

        // Timeline
        if ( 'yes' === $settings['show_timeline'] ) {
            $steps_json = get_post_meta( $beer_id, 'bsc_recipe_steps', true );
            $steps = $steps_json ? json_decode( $steps_json, true ) : null;
            if ( $steps ) {
                if ( class_exists( 'BSC_Frontend_Display' ) ) {
                    $display = new \BSC_Frontend_Display();
                    echo $display->render_timeline_html( $steps );
                }
            }
        } else {
            // Fallback ingredients list if timeline hidden?
            $ingredients = get_post_meta( $beer_id, 'bsc_ingredients_list', true );
            if ( $ingredients ) {
                echo '<div class="bsc-ingredients"><strong>Ingrédients:</strong><br>' . nl2br( esc_html( $ingredients ) ) . '</div>';
            }
        }

		echo '</div>';
	}
}
