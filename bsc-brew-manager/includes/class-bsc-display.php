<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BSC_Frontend_Display {

	public function __construct() {
		add_filter( 'the_content', array( $this, 'display_recipe_details' ) );
		add_action( 'wp_footer', array( $this, 'render_tasting_modal' ) );
		add_action( 'init', array( $this, 'handle_tasting_request' ) );

		add_shortcode( 'bsc_breweries_list', array( $this, 'render_breweries_list' ) );
		add_shortcode( 'bsc_beers_list', array( $this, 'render_beers_list' ) );
	}

	public function render_breweries_list( $atts ) {
		$sort = isset( $_GET['bsc_sort'] ) ? sanitize_text_field( $_GET['bsc_sort'] ) : 'name';

		$args = array(
			'post_type' => 'bsc_brewery',
			'post_status' => 'publish',
			'posts_per_page' => 12,
			'paged' => get_query_var('paged') ? get_query_var('paged') : 1,
		);

		switch ( $sort ) {
			case 'rating':
				$args['meta_key'] = 'bsc_average_rating';
				$args['orderby'] = 'meta_value_num';
				$args['order'] = 'DESC';
				break;
			case 'beers':
				$args['meta_key'] = 'bsc_beer_count';
				$args['orderby'] = 'meta_value_num';
				$args['order'] = 'DESC';
				break;
			case 'name':
			default:
				$args['orderby'] = 'title';
				$args['order'] = 'ASC';
				break;
		}

		$query = new WP_Query( $args );

		ob_start();
		echo '<div class="bsc-breweries-list">';
		if ( $query->have_posts() ) {
			echo '<div class="bsc-grid" style="display:grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap:20px;">';
			while ( $query->have_posts() ) {
				$query->the_post();
				$rating = get_post_meta( get_the_ID(), 'bsc_average_rating', true );
				$count = get_post_meta( get_the_ID(), 'bsc_beer_count', true );
				?>
				<div class="bsc-card" style="border:1px solid #ddd; padding:15px; border-radius:5px;">
					<?php if ( has_post_thumbnail() ) {
						echo '<div style="margin-bottom:10px;">' . get_the_post_thumbnail( get_the_ID(), 'medium' ) . '</div>';
					} ?>
					<h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
					<p><?php echo wp_trim_words( get_the_excerpt(), 10 ); ?></p>
					<div class="bsc-meta">
						<span>⭐ <?php echo esc_html( $rating ? $rating : '-' ); ?>/5</span> |
						<span>🍺 <?php echo esc_html( $count ? $count : 0 ); ?></span>
					</div>
				</div>
				<?php
			}
			echo '</div>';

			// Pagination
			echo '<div class="bsc-pagination" style="margin-top:20px;">';
			echo paginate_links( array( 'total' => $query->max_num_pages ) );
			echo '</div>';
		} else {
			echo '<p>' . __( 'Aucune brasserie trouvée.', 'bsc-brew-manager' ) . '</p>';
		}
		echo '</div>';
		wp_reset_postdata();

		return ob_get_clean();
	}

	public function render_beers_list( $atts ) {
		$sort = isset( $_GET['bsc_sort'] ) ? sanitize_text_field( $_GET['bsc_sort'] ) : 'date';
		$style = isset( $_GET['bsc_style'] ) ? sanitize_text_field( $_GET['bsc_style'] ) : '';

		$args = array(
			'post_type' => 'bsc_recipe',
			'post_status' => 'publish',
			'posts_per_page' => 12,
			'paged' => get_query_var('paged') ? get_query_var('paged') : 1,
		);

		if ( ! empty( $style ) ) {
			$args['meta_key'] = 'bsc_style';
			$args['meta_value'] = $style;
		}

		switch ( $sort ) {
			case 'rating':
				$args['meta_key'] = 'bsc_average_rating'; // Assuming beers have their own rating
				$args['orderby'] = 'meta_value_num';
				$args['order'] = 'DESC';
				break;
			case 'name':
				$args['orderby'] = 'title';
				$args['order'] = 'ASC';
				break;
			case 'date':
			default:
				$args['orderby'] = 'date';
				$args['order'] = 'DESC';
				break;
		}

		$query = new WP_Query( $args );

		ob_start();
		echo '<div class="bsc-beers-list">';
		if ( $query->have_posts() ) {
			echo '<div class="bsc-grid" style="display:grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap:20px;">';
			while ( $query->have_posts() ) {
				$query->the_post();
				$rating = get_post_meta( get_the_ID(), 'bsc_average_rating', true );
				$beer_style = get_post_meta( get_the_ID(), 'bsc_style', true );
				$abv = get_post_meta( get_the_ID(), 'bsc_abv', true );
				?>
				<div class="bsc-card" style="border:1px solid #ddd; padding:15px; border-radius:5px;">
					<?php if ( has_post_thumbnail() ) {
						echo '<div style="margin-bottom:10px;">' . get_the_post_thumbnail( get_the_ID(), 'medium' ) . '</div>';
					} ?>
					<h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
					<p><em><?php echo esc_html( $beer_style ); ?> - <?php echo esc_html( $abv ); ?>%</em></p>
					<div class="bsc-meta">
						<span>⭐ <?php echo esc_html( $rating ? $rating : '-' ); ?>/5</span>
					</div>
				</div>
				<?php
			}
			echo '</div>';

			// Pagination
			echo '<div class="bsc-pagination" style="margin-top:20px;">';
			echo paginate_links( array( 'total' => $query->max_num_pages ) );
			echo '</div>';
		} else {
			echo '<p>' . __( 'Aucune bière trouvée.', 'bsc-brew-manager' ) . '</p>';
		}
		echo '</div>';
		wp_reset_postdata();

		return ob_get_clean();
	}

	public function display_recipe_details( $content ) {
		if ( ! is_singular( 'bsc_recipe' ) ) {
			return $content;
		}

		$post_id = get_the_ID();

		// Enqueue our CSS
		wp_enqueue_style( 'bsc-form-css', BSC_BREW_MANAGER_URL . 'assets/css/bsc-form.css', array(), '1.2' );

		// Retrieve Meta
		$meta_fields = array(
			'Volume' => array( 'key' => 'bsc_batch_volume', 'unit' => 'L' ),
			'ABV' => array( 'key' => 'bsc_abv', 'unit' => '%' ),
			'Couleur' => array( 'key' => 'bsc_color', 'unit' => 'EBC' ),
			'Ebullition' => array( 'key' => 'bsc_boil_time', 'unit' => 'min' ),
			'Ferm. Temp' => array( 'key' => 'bsc_fermentation_temp', 'unit' => '°C' ),
		);

		ob_start();
		?>
		<div class="bsc-recipe-details">
			<h3><?php _e( 'Fiche Technique', 'bsc-brew-manager' ); ?></h3>
			<ul class="bsc-stats-list">
				<?php foreach ( $meta_fields as $label => $data ) :
					$val = get_post_meta( $post_id, $data['key'], true );
					if ( ! empty( $val ) ) : ?>
					<li><strong><?php echo esc_html( $label ); ?>:</strong> <?php echo esc_html( $val . ' ' . $data['unit'] ); ?></li>
				<?php endif; endforeach; ?>
			</ul>

			<?php
			// Try to get structured JSON first
			$steps_json = get_post_meta( $post_id, 'bsc_recipe_steps', true );
			$steps = $steps_json ? json_decode( $steps_json, true ) : null;

			if ( $steps && is_array( $steps ) ) :
				echo $this->render_timeline_html( $steps );
			else : ?>
				<!-- Fallback to Legacy Text Blobs if no JSON steps -->
				<?php
				$ingredients = get_post_meta( $post_id, 'bsc_ingredients_list', true );
				if ( $ingredients ) : ?>
					<div class="bsc-section">
						<h4><?php _e( 'Ingrédients', 'bsc-brew-manager' ); ?></h4>
						<div class="bsc-content"><?php echo wpautop( esc_html( $ingredients ) ); ?></div>
					</div>
				<?php endif; ?>

				<?php
				$mash = get_post_meta( $post_id, 'bsc_mash_schedule', true );
				if ( $mash ) : ?>
					<div class="bsc-section">
						<h4><?php _e( 'Processus / Paliers', 'bsc-brew-manager' ); ?></h4>
						<div class="bsc-content"><?php echo wpautop( esc_html( $mash ) ); ?></div>
					</div>
				<?php endif; ?>

				<?php
				$equip = get_post_meta( $post_id, 'bsc_equipment', true );
				if ( $equip ) : ?>
					<div class="bsc-section">
						<h4><?php _e( 'Matériel', 'bsc-brew-manager' ); ?></h4>
						<div class="bsc-content"><?php echo wpautop( esc_html( $equip ) ); ?></div>
					</div>
				<?php endif; ?>

			<?php endif; // End JSON check ?>

			<?php
			$taste = get_post_meta( $post_id, 'bsc_taste_notes', true );
			if ( $taste ) : ?>
				<div class="bsc-section">
					<h4><?php _e( 'Notes de dégustation', 'bsc-brew-manager' ); ?></h4>
					<div class="bsc-content"><?php echo wpautop( esc_html( $taste ) ); ?></div>
				</div>
			<?php endif; ?>

			<?php
			$label_img_id = get_post_meta( $post_id, 'bsc_label_image_id', true );
			if ( $label_img_id ) : ?>
				<div class="bsc-section">
					<h4><?php _e( 'Étiquette', 'bsc-brew-manager' ); ?></h4>
					<?php echo wp_get_attachment_image( $label_img_id, 'medium' ); ?>
				</div>
			<?php endif; ?>

			<?php if ( is_user_logged_in() && get_current_user_id() !== get_the_author_meta('ID') ) : ?>
				<div class="bsc-action-area" style="margin-top: 20px;">
					<button id="bsc-request-taste-btn" class="button button-primary"><?php _e( 'Demander à goûter', 'bsc-brew-manager' ); ?></button>
				</div>
			<?php endif; ?>

		</div>
		<script>
			document.addEventListener('DOMContentLoaded', function() {
				var btn = document.getElementById('bsc-request-taste-btn');
				var modal = document.getElementById('bsc-taste-modal');
				if(btn && modal) {
					btn.addEventListener('click', function() {
						modal.style.display = 'block';
					});
				}
				var close = document.getElementById('bsc-modal-close');
				if(close) {
					close.addEventListener('click', function() {
						modal.style.display = 'none';
					});
				}
			});
		</script>
		<?php
		$details = ob_get_clean();
		return $content . $details;
	}

	public function render_timeline_html( $steps ) {
		ob_start();
		?>
		<div class="bsc-section bsc-recipe-builder">
			<h4><?php _e( 'Processus de Brassage', 'bsc-brew-manager' ); ?></h4>
			<div id="bsc-recipe-builder-container">
				<?php foreach ( $steps as $step ) :
					$type = isset($step['type']) ? $step['type'] : 'prep';
					$icon = '⚙️';
					switch($type) {
						case 'mash': $icon = '🌾'; break;
						case 'sparge': $icon = '🚿'; break;
						case 'boil': $icon = '🔥'; break;
						case 'whirlpool': $icon = '🌀'; break;
						case 'chill': $icon = '❄️'; break;
						case 'ferment': $icon = '🦠'; break;
						case 'dryhop': $icon = '🌿'; break;
						case 'aging': $icon = '🕰️'; break;
						case 'package': $icon = '📦'; break;
						case 'carb': $icon = '🫧'; break;
					}

					$config = [
						'mash' => ['dur'=>true, 'temp'=>true],
						'sparge' => ['dur'=>true, 'temp'=>true],
						'boil' => ['dur'=>true, 'temp'=>false],
						'whirlpool' => ['dur'=>true, 'temp'=>true],
						'chill' => ['dur'=>false, 'temp'=>true],
						'ferment' => ['dur'=>true, 'temp'=>true],
						'dryhop' => ['dur'=>true, 'temp'=>false],
						'aging' => ['dur'=>true, 'temp'=>true],
						'package' => ['dur'=>false, 'temp'=>false],
						'carb' => ['dur'=>true, 'temp'=>true],
						'prep' => ['dur'=>false, 'temp'=>false],
					];
					$current_config = isset($config[$type]) ? $config[$type] : $config['prep'];
				?>
					<div class="bsc-step-wrapper">
						<div class="bsc-timeline-column">
							<div class="bsc-timeline-line"></div>
							<div class="bsc-timeline-icon bsc-bg-<?php echo esc_attr($type); ?>"><?php echo $icon; ?></div>
						</div>

						<div class="bsc-step-card bsc-type-<?php echo esc_attr($type); ?>">
							<div class="bsc-step-header">
								<div class="bsc-header-row">
									<span class="bsc-step-type-select" style="border:none; background:transparent;">
										<?php
											$labels = [
												'prep' => 'Préparation',
												'mash' => 'Empâtage',
												'sparge' => 'Rinçage',
												'boil' => 'Ébullition',
												'whirlpool' => 'Whirlpool',
												'chill' => 'Refroidissement',
												'ferment' => 'Fermentation',
												'dryhop' => 'Dry Hop',
												'aging' => 'Garde',
												'package' => 'Conditionnement',
												'carb' => 'Carbonatation'
											];
											echo isset($labels[$type]) ? $labels[$type] : 'Étape';
										?>
									</span>
									<strong class="bsc-step-title-input"><?php echo esc_html( $step['title'] ); ?></strong>
								</div>

								<?php if ( $current_config['dur'] || $current_config['temp'] ) : ?>
								<div class="bsc-step-metrics">
									<?php if ( $current_config['dur'] && ! empty( $step['duration'] ) ) : ?>
									<div class="bsc-metric">
										<span class="bsc-metric-label">Durée</span>
										<div class="bsc-metric-input-wrapper">
											<span class="bsc-metric-input"><?php echo esc_html( $step['duration'] ); ?></span>
											<span class="bsc-metric-unit">min</span>
										</div>
									</div>
									<?php endif; ?>

									<?php if ( $current_config['temp'] && ! empty( $step['temp'] ) ) : ?>
									<div class="bsc-metric">
										<span class="bsc-metric-label">Temp</span>
										<div class="bsc-metric-input-wrapper">
											<span class="bsc-metric-input"><?php echo esc_html( $step['temp'] ); ?></span>
											<span class="bsc-metric-unit">°C</span>
										</div>
									</div>
									<?php endif; ?>
								</div>
								<?php endif; ?>
							</div>

							<?php if ( ! empty( $step['comment'] ) ) : ?>
								<div class="bsc-step-extra" style="border-top:1px dashed #eee; margin-top:10px; padding-top:10px;">
									<div class="bsc-step-comment-display" style="background:#fafafa; padding:10px; border-radius:5px; font-style:italic;">
										<?php echo wpautop( esc_html( $step['comment'] ) ); ?>
									</div>
								</div>
							<?php endif; ?>

							<?php if ( ! empty( $step['image_url'] ) ) : ?>
								<div class="bsc-step-extra" style="margin-top:10px;">
									<img src="<?php echo esc_url( $step['image_url'] ); ?>" style="max-height:200px; border-radius:5px;">
								</div>
							<?php endif; ?>

							<div class="bsc-items-container">
								<?php if ( ! empty( $step['items'] ) ) : ?>
									<?php foreach ( $step['items'] as $item ) :
										$i_icon = '🔹';
										switch($item['type']) {
											case 'Malt': $i_icon = '🌾'; break;
											case 'Hop': $i_icon = '🌿'; break;
											case 'Yeast': $i_icon = '🦠'; break;
											case 'Adjunct': $i_icon = '🍬'; break;
											case 'Technique': $i_icon = '🛠️'; break;
											case 'Equipment': $i_icon = '⚙️'; break;
										}
									?>
										<div class="bsc-item-row" style="grid-template-columns: 50px 140px 2fr 1fr;">
											<div class="bsc-item-icon"><?php echo $i_icon; ?></div>
											<span class="bsc-item-select" style="border:none; background:#f9f9f9;"><?php echo esc_html( $item['type'] ); ?></span>
											<span class="bsc-item-input" style="border:none; font-weight:bold;"><?php echo esc_html( $item['name'] ); ?></span>
											<span class="bsc-item-input" style="border:none;"><?php echo esc_html( $item['qty'] ); ?></span>
										</div>
										<?php if ( $item['type'] === 'Malt' && ( ! empty($item['supplier']) || ! empty($item['ebc']) || ! empty($item['yield']) ) ) : ?>
											<div class="bsc-item-details" style="font-size:0.85em; color:#666; padding-left: 65px; margin-top:-5px; margin-bottom:10px;">
												<?php
													$details = [];
													if( ! empty($item['supplier']) ) $details[] = '🏭 ' . esc_html($item['supplier']);
													if( ! empty($item['ebc']) ) $details[] = '🎨 ' . esc_html($item['ebc']) . ' EBC';
													if( ! empty($item['yield']) ) $details[] = '📈 ' . esc_html($item['yield']) . '% Rendement';
													echo implode(' &nbsp;&bull;&nbsp; ', $details);
												?>
											</div>
										<?php endif; ?>
										<?php if ( $item['type'] === 'Hop' && ( ! empty($item['alpha']) || ! empty($item['form']) || ! empty($item['origin']) || ! empty($item['aromas']) ) ) : ?>
											<div class="bsc-item-details" style="font-size:0.85em; color:#2e7d32; padding-left: 65px; margin-top:-5px; margin-bottom:10px;">
												<?php
													$details = [];
													if( ! empty($item['alpha']) ) $details[] = '🧪 ' . esc_html($item['alpha']) . '% AA';
													if( ! empty($item['form']) ) $details[] = '📦 ' . esc_html($item['form']);
													if( ! empty($item['origin']) ) $details[] = '🌍 ' . esc_html($item['origin']);

													// Aromas (array or string)
													if( ! empty($item['aromas']) ) {
														$aromas = is_array($item['aromas']) ? implode(', ', $item['aromas']) : $item['aromas'];
														$details[] = '🌸 ' . esc_html($aromas);
													}

													echo implode(' &nbsp;&bull;&nbsp; ', $details);
												?>
											</div>
										<?php endif; ?>
										<?php if ( $item['type'] === 'Yeast' && ( ! empty($item['brand']) || ! empty($item['form']) || ! empty($item['attenuation']) || ! empty($item['temp_opt']) ) ) : ?>
											<div class="bsc-item-details" style="font-size:0.85em; color:#d35400; padding-left: 65px; margin-top:-5px; margin-bottom:10px;">
												<?php
													$details = [];
													if( ! empty($item['brand']) ) $details[] = '🏷️ ' . esc_html($item['brand']);
													if( ! empty($item['form']) ) $details[] = '📦 ' . esc_html($item['form']);
													if( ! empty($item['attenuation']) ) $details[] = '📉 ' . esc_html($item['attenuation']) . '% Att.';
													if( ! empty($item['temp_opt']) ) $details[] = '🌡️ ' . esc_html($item['temp_opt']);

													echo implode(' &nbsp;&bull;&nbsp; ', $details);
												?>
											</div>
										<?php endif; ?>
									<?php endforeach; ?>
								<?php endif; ?>
							</div>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	public function render_tasting_modal() {
		if ( ! is_singular( 'bsc_recipe' ) || ! is_user_logged_in() ) {
			return;
		}
		?>
		<div id="bsc-taste-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999;">
			<div style="background:#fff; width:400px; margin:100px auto; padding:20px; border-radius:5px;">
				<span id="bsc-modal-close" style="float:right; cursor:pointer; font-weight:bold;">&times;</span>
				<h3><?php _e( 'Envoyer une demande', 'bsc-brew-manager' ); ?></h3>
				<form method="post">
					<?php wp_nonce_field( 'bsc_request_taste_action', 'bsc_request_taste_nonce' ); ?>
					<input type="hidden" name="bsc_post_id" value="<?php echo get_the_ID(); ?>">
					<p>
						<textarea name="bsc_message" rows="4" style="width:100%" placeholder="<?php _e( 'Bonjour, je serais intéressé pour goûter ta bière...', 'bsc-brew-manager' ); ?>"></textarea>
					</p>
					<p>
						<input type="submit" name="bsc_send_request" value="<?php _e( 'Envoyer', 'bsc-brew-manager' ); ?>" class="button button-primary">
					</p>
				</form>
			</div>
		</div>
		<?php
	}

	public function handle_tasting_request() {
		if ( ! isset( $_POST['bsc_send_request'] ) ) {
			return;
		}
		if ( ! is_user_logged_in() ) {
			return;
		}
		if ( ! isset( $_POST['bsc_request_taste_nonce'] ) || ! wp_verify_nonce( $_POST['bsc_request_taste_nonce'], 'bsc_request_taste_action' ) ) {
			return;
		}

		$post_id = intval( $_POST['bsc_post_id'] );
		$author_id = get_post_field( 'post_author', $post_id );
		$author_email = get_the_author_meta( 'user_email', $author_id );

		$requester = wp_get_current_user();
		$message = sanitize_textarea_field( $_POST['bsc_message'] );

		$subject = sprintf( __( 'Nouvelle demande de dégustation pour : %s', 'bsc-brew-manager' ), get_the_title( $post_id ) );
		$body = sprintf(
			__( "Bonjour,\n\nL'utilisateur %s souhaite goûter votre bière \"%s\".\n\nMessage:\n%s\n\nVous pouvez le contacter à : %s", 'bsc-brew-manager' ),
			$requester->display_name,
			get_the_title( $post_id ),
			$message,
			$requester->user_email
		);

		$headers = array( 'Content-Type: text/plain; charset=UTF-8' );
		wp_mail( $author_email, $subject, $body, $headers );

		// Feedback message (simple JS alert for now, could be better)
		add_action( 'wp_footer', function() {
			echo '<script>alert("' . __( 'Votre demande a été envoyée !', 'bsc-brew-manager' ) . '");</script>';
		});
	}
}
