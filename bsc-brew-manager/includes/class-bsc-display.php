<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BSC_Frontend_Display {

	public function __construct() {
		add_filter( 'the_content', array( $this, 'display_recipe_details' ) );
		add_action( 'wp_footer', array( $this, 'render_tasting_modal' ) );
		add_action( 'init', array( $this, 'handle_tasting_request' ) );
	}

	public function display_recipe_details( $content ) {
		if ( ! is_singular( 'bsc_recipe' ) ) {
			return $content;
		}

		$post_id = get_the_ID();

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
