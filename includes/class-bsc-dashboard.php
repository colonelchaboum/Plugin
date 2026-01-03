<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BSC_Dashboard {

	public function __construct() {
		add_shortcode( 'bsc_my_breweries', array( $this, 'render_my_breweries' ) );
		add_shortcode( 'bsc_my_beers', array( $this, 'render_my_beers' ) );
		add_action( 'init', array( $this, 'handle_actions' ) );
	}

	public function handle_actions() {
		if ( ! is_user_logged_in() ) {
			return;
		}

		if ( isset( $_GET['bsc_action'], $_GET['post_id'], $_GET['_wpnonce'] ) ) {
			$post_id = intval( $_GET['post_id'] );
			if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'bsc_delete_' . $post_id ) ) {
				return;
			}

			$post = get_post( $post_id );
			if ( ! $post || $post->post_author != get_current_user_id() ) {
				return;
			}

			if ( $_GET['bsc_action'] === 'delete' ) {
				// Trigger Supabase Delete before WP Delete
				if ( class_exists( 'BSC_Supabase' ) ) {
					$supabase = new BSC_Supabase();
					if ( $post->post_type === 'bsc_brewery' ) {
						$supabase->delete_brewery( $post_id );
					} elseif ( $post->post_type === 'bsc_recipe' ) {
						$supabase->delete_beer( $post_id );
					}
				}

				wp_delete_post( $post_id, true );
				wp_redirect( remove_query_arg( array( 'bsc_action', 'post_id', '_wpnonce' ) ) );
				exit;
			}
		}
	}

	public function render_my_breweries() {
		if ( ! is_user_logged_in() ) {
			return '<p>' . __( 'Veuillez vous connecter.', 'bsc-brew-manager' ) . '</p>';
		}

		$user_id = get_current_user_id();
		$breweries = get_posts( array(
			'post_type'   => 'bsc_brewery',
			'author'      => $user_id,
			'post_status' => array( 'publish', 'pending', 'draft' ),
			'numberposts' => -1
		) );

		ob_start();
		echo '<div class="bsc-dashboard-breweries">';
		echo '<h2>' . __( 'Mes Brasseries', 'bsc-brew-manager' ) . '</h2>';
		echo '<a href="' . esc_url( add_query_arg( 'action', 'create_brewery' ) ) . '" class="button">' . __( 'Créer une Brasserie', 'bsc-brew-manager' ) . '</a>'; // Assuming a page structure

		if ( empty( $breweries ) ) {
			echo '<p>' . __( 'Aucune brasserie trouvée.', 'bsc-brew-manager' ) . '</p>';
		} else {
			echo '<table class="widefat fixed striped">';
			echo '<thead><tr><th style="width: 60px;">' . __( 'Logo', 'bsc-brew-manager' ) . '</th><th>Nom</th><th>État</th><th>Actions</th></tr></thead>';
			echo '<tbody>';
			foreach ( $breweries as $brewery ) {
				$edit_link = add_query_arg( array( 'edit_id' => $brewery->ID ) ); // Assuming current page handles edit
				$delete_link = wp_nonce_url( add_query_arg( array( 'bsc_action' => 'delete', 'post_id' => $brewery->ID ) ), 'bsc_delete_' . $brewery->ID );

				$logo_url = has_post_thumbnail( $brewery->ID ) ? get_the_post_thumbnail_url( $brewery->ID, 'thumbnail' ) : get_post_meta( $brewery->ID, 'bsc_image_url', true );
				$logo_html = $logo_url ? '<img src="' . esc_url( $logo_url ) . '" style="max-width:50px; height:auto; border-radius:4px;">' : '';

				echo '<tr>';
				echo '<td>' . $logo_html . '</td>';
				echo '<td>' . esc_html( $brewery->post_title ) . '</td>';
				echo '<td>' . esc_html( get_post_status_object( $brewery->post_status )->label ) . '</td>';
				echo '<td>';
				echo '<a href="' . esc_url( $edit_link ) . '">' . __( 'Modifier', 'bsc-brew-manager' ) . '</a> | ';
				echo '<a href="' . esc_url( $delete_link ) . '" onclick="return confirm(\'Êtes-vous sûr ?\');" style="color:red;">' . __( 'Supprimer', 'bsc-brew-manager' ) . '</a>';
				echo '</td>';
				echo '</tr>';
			}
			echo '</tbody></table>';
		}
		echo '</div>';

		return ob_get_clean();
	}

	public function render_my_beers() {
		if ( ! is_user_logged_in() ) {
			return '<p>' . __( 'Veuillez vous connecter.', 'bsc-brew-manager' ) . '</p>';
		}

		$user_id = get_current_user_id();
		$beers = get_posts( array(
			'post_type'   => 'bsc_recipe',
			'author'      => $user_id,
			'post_status' => array( 'publish', 'pending', 'draft' ),
			'numberposts' => -1
		) );

		ob_start();
		echo '<div class="bsc-dashboard-beers">';
		echo '<h2>' . __( 'Mes Bières', 'bsc-brew-manager' ) . '</h2>';

		if ( empty( $beers ) ) {
			echo '<p>' . __( 'Aucune bière trouvée.', 'bsc-brew-manager' ) . '</p>';
		} else {
			echo '<table class="widefat fixed striped">';
			echo '<thead><tr><th style="width: 60px;">' . __( 'Photo', 'bsc-brew-manager' ) . '</th><th>Nom</th><th>Brasserie</th><th>Style</th><th>Actions</th></tr></thead>';
			echo '<tbody>';
			foreach ( $beers as $beer ) {
				$brewery_id = get_post_meta( $beer->ID, 'bsc_brewery_id', true );
				$brewery_name = $brewery_id ? get_the_title( $brewery_id ) : '-';
				$style = get_post_meta( $beer->ID, 'bsc_style', true );

				$edit_link = add_query_arg( array( 'edit_id' => $beer->ID ) );
				$delete_link = wp_nonce_url( add_query_arg( array( 'bsc_action' => 'delete', 'post_id' => $beer->ID ) ), 'bsc_delete_' . $beer->ID );

				$logo_url = has_post_thumbnail( $beer->ID ) ? get_the_post_thumbnail_url( $beer->ID, 'thumbnail' ) : get_post_meta( $beer->ID, 'bsc_image_url', true );
				$logo_html = $logo_url ? '<img src="' . esc_url( $logo_url ) . '" style="max-width:50px; height:auto; border-radius:4px;">' : '';

				echo '<tr>';
				echo '<td>' . $logo_html . '</td>';
				echo '<td>' . esc_html( $beer->post_title ) . '</td>';
				echo '<td>' . esc_html( $brewery_name ) . '</td>';
				echo '<td>' . esc_html( $style ) . '</td>';
				echo '<td>';
				echo '<a href="' . esc_url( $edit_link ) . '">' . __( 'Modifier', 'bsc-brew-manager' ) . '</a> | ';
				echo '<a href="' . esc_url( $delete_link ) . '" onclick="return confirm(\'Êtes-vous sûr ?\');" style="color:red;">' . __( 'Supprimer', 'bsc-brew-manager' ) . '</a>';
				echo '</td>';
				echo '</tr>';
			}
			echo '</tbody></table>';
		}
		echo '</div>';

		return ob_get_clean();
	}
}
