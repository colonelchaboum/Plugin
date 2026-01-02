<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BSC_Reviews {

	public function __construct() {
		add_action( 'comment_form_logged_in_after', array( $this, 'add_rating_fields' ) );
		add_action( 'comment_post', array( $this, 'save_rating_fields' ) );
		add_filter( 'comment_text', array( $this, 'display_rating' ) );
	}

	public function add_rating_fields() {
		if ( ! is_singular( 'bsc_recipe' ) ) {
			return;
		}
		?>
		<div class="bsc-rating-fields" style="margin-bottom: 20px; border: 1px solid #ddd; padding: 15px;">
			<h4><?php _e( 'Évaluation Zythologue', 'bsc-brew-manager' ); ?></h4>
			<p><em><?php _e( 'Notez de 1 à 5', 'bsc-brew-manager' ); ?></em></p>

			<div style="display: flex; gap: 20px; flex-wrap: wrap;">
				<div>
					<label for="bsc_rating_appearance"><?php _e( 'Apparence', 'bsc-brew-manager' ); ?></label><br>
					<input type="number" name="bsc_rating_appearance" min="1" max="5" required>
				</div>
				<div>
					<label for="bsc_rating_aroma"><?php _e( 'Arôme', 'bsc-brew-manager' ); ?></label><br>
					<input type="number" name="bsc_rating_aroma" min="1" max="5" required>
				</div>
				<div>
					<label for="bsc_rating_taste"><?php _e( 'Goût', 'bsc-brew-manager' ); ?></label><br>
					<input type="number" name="bsc_rating_taste" min="1" max="5" required>
				</div>
				<div>
					<label for="bsc_rating_mouthfeel"><?php _e( 'Sensation (Corps)', 'bsc-brew-manager' ); ?></label><br>
					<input type="number" name="bsc_rating_mouthfeel" min="1" max="5" required>
				</div>
				<div>
					<label for="bsc_rating_overall"><?php _e( 'Note Globale', 'bsc-brew-manager' ); ?></label><br>
					<input type="number" name="bsc_rating_overall" min="1" max="5" required>
				</div>
			</div>
		</div>
		<?php
	}

	public function save_rating_fields( $comment_id ) {
		$fields = array( 'bsc_rating_appearance', 'bsc_rating_aroma', 'bsc_rating_taste', 'bsc_rating_mouthfeel', 'bsc_rating_overall' );

		foreach ( $fields as $field ) {
			if ( isset( $_POST[ $field ] ) ) {
				$val = intval( $_POST[ $field ] );
				if ( $val < 1 ) $val = 1;
				if ( $val > 5 ) $val = 5;
				add_comment_meta( $comment_id, $field, $val );
			}
		}

		// Calculate and update average rating for the post
		$comment = get_comment( $comment_id );
		$post_id = $comment->comment_post_ID;
		$this->update_post_average( $post_id );
	}

	public function update_post_average( $post_id ) {
		$args = array(
			'post_id' => $post_id,
			'status'  => 'approve',
			'meta_key' => 'bsc_rating_overall', // Ensure we only count comments with ratings
		);
		$comments = get_comments( $args );

		if ( empty( $comments ) ) {
			update_post_meta( $post_id, 'bsc_average_rating', 0 );
			update_post_meta( $post_id, 'bsc_vote_count', 0 );
			return;
		}

		$total = 0;
		$count = 0;
		foreach ( $comments as $comment ) {
			$rating = get_comment_meta( $comment->comment_ID, 'bsc_rating_overall', true );
			if ( $rating ) {
				$total += intval( $rating );
				$count++;
			}
		}

		if ( $count > 0 ) {
			$avg = round( $total / $count, 2 );
			update_post_meta( $post_id, 'bsc_average_rating', $avg );
			update_post_meta( $post_id, 'bsc_vote_count', $count );
		}
	}

	public function display_rating( $comment_text ) {
		$comment_id = get_comment_ID();
		// Check if this comment has our meta
		$overall = get_comment_meta( $comment_id, 'bsc_rating_overall', true );

		if ( ! $overall ) {
			return $comment_text;
		}

		$fields = array(
			'bsc_rating_appearance' => __( 'Apparence', 'bsc-brew-manager' ),
			'bsc_rating_aroma'      => __( 'Arôme', 'bsc-brew-manager' ),
			'bsc_rating_taste'      => __( 'Goût', 'bsc-brew-manager' ),
			'bsc_rating_mouthfeel'  => __( 'Corps', 'bsc-brew-manager' ),
			'bsc_rating_overall'    => __( 'Globale', 'bsc-brew-manager' ),
		);

		$html = '<div class="bsc-review-grid" style="background:#f9f9f9; padding:10px; margin-bottom:10px; border-left: 3px solid #ffba00;">';
		$html .= '<strong>' . __( 'Évaluation', 'bsc-brew-manager' ) . '</strong><br>';
		$html .= '<ul style="list-style:none; margin:0; padding:0; display:flex; gap:15px; flex-wrap:wrap;">';

		foreach ( $fields as $key => $label ) {
			$val = get_comment_meta( $comment_id, $key, true );
			if ( $val ) {
				$html .= "<li>$label: <strong>$val/5</strong></li>";
			}
		}

		$html .= '</ul></div>';

		return $html . $comment_text;
	}
}
