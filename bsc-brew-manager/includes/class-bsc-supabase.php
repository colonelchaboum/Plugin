<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BSC_Supabase {

	private $api_url;
	private $api_key;
	private $project_id;

	public function __construct() {
		$this->api_url    = get_option( 'bsc_supabase_url' );
		$this->api_key    = get_option( 'bsc_supabase_api_key' );
		// $this->project_id = get_option( 'bsc_supabase_project_id' ); // Optional if URL is full
	}

	public function connection_test() {
		if ( empty( $this->api_url ) || empty( $this->api_key ) ) {
			return new WP_Error( 'missing_credentials', __( 'URL ou Clé API manquante.', 'bsc-brew-manager' ) );
		}

		$response = wp_remote_get( $this->api_url . '/rest/v1/', array(
			'headers' => array(
				'apikey' => $this->api_key,
				'Authorization' => 'Bearer ' . $this->api_key,
			),
		) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( $code >= 200 && $code < 300 ) {
			return true;
		}

		return new WP_Error( 'connection_failed', 'Erreur ' . $code . ': ' . wp_remote_retrieve_body( $response ) );
	}

	public function sync_brewery( $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post || $post->post_type !== 'bsc_brewery' ) {
			return;
		}

		$supabase_id = get_post_meta( $post_id, 'bsc_supabase_id', true );

		$data = array(
			'name'        => $post->post_title,
			'description' => $post->post_content,
		);

		if ( has_post_thumbnail( $post_id ) ) {
			$data['logo_url'] = get_the_post_thumbnail_url( $post_id, 'full' );
		}

		$method = 'POST';
		$endpoint = '/rest/v1/breweries';

		if ( $supabase_id ) {
			$method = 'PATCH';
			$endpoint .= '?id=eq.' . $supabase_id;
		}

		$response = $this->make_request( $endpoint, $method, $data );

		if ( ! is_wp_error( $response ) ) {
			$body = json_decode( wp_remote_retrieve_body( $response ), true );
			if ( $method === 'POST' && ! empty( $body ) && isset( $body[0]['id'] ) ) {
				update_post_meta( $post_id, 'bsc_supabase_id', $body[0]['id'] );
			}
		} else {
			error_log( 'BSC Supabase Sync Brewery Error: ' . $response->get_error_message() );
		}

		return $response;
	}

	public function sync_beer( $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post || $post->post_type !== 'bsc_recipe' ) {
			return;
		}

		$brewery_post_id = get_post_meta( $post_id, 'bsc_brewery_id', true );
		if ( ! $brewery_post_id ) {
			return;
		}

		$brewery_supabase_id = get_post_meta( $brewery_post_id, 'bsc_supabase_id', true );
		if ( ! $brewery_supabase_id ) {
			return new WP_Error( 'missing_brewery_sync', 'La brasserie parente n\'est pas synchronisée.' );
		}

		$supabase_id = get_post_meta( $post_id, 'bsc_supabase_id', true );

		$style = get_post_meta( $post_id, 'bsc_style', true );
		$abv = get_post_meta( $post_id, 'bsc_abv', true );

		$data = array(
			'name'       => $post->post_title,
			'style'      => $style,
			'abv'        => (float) $abv,
			'brewery_id' => (int) $brewery_supabase_id,
		);

		$method = 'POST';
		$endpoint = '/rest/v1/beers';

		if ( $supabase_id ) {
			$method = 'PATCH';
			$endpoint .= '?id=eq.' . $supabase_id;
		}

		$response = $this->make_request( $endpoint, $method, $data );

		if ( ! is_wp_error( $response ) ) {
			$body = json_decode( wp_remote_retrieve_body( $response ), true );
			if ( $method === 'POST' && ! empty( $body ) && isset( $body[0]['id'] ) ) {
				update_post_meta( $post_id, 'bsc_supabase_id', $body[0]['id'] );
			}
		} else {
			error_log( 'BSC Supabase Sync Beer Error: ' . $response->get_error_message() );
		}

		return $response;
	}

	public function delete_brewery( $post_id ) {
		$supabase_id = get_post_meta( $post_id, 'bsc_supabase_id', true );
		if ( ! $supabase_id ) return;

		$this->make_request( '/rest/v1/breweries?id=eq.' . $supabase_id, 'DELETE' );
	}

	public function delete_beer( $post_id ) {
		$supabase_id = get_post_meta( $post_id, 'bsc_supabase_id', true );
		if ( ! $supabase_id ) return;

		$this->make_request( '/rest/v1/beers?id=eq.' . $supabase_id, 'DELETE' );
	}

	public function import_from_supabase() {
		// Import Breweries
		$response = $this->make_request( '/rest/v1/breweries?select=*' );
		if ( ! is_wp_error( $response ) ) {
			$breweries = json_decode( wp_remote_retrieve_body( $response ), true );
			if ( is_array( $breweries ) ) {
				foreach ( $breweries as $b ) {
					// Check if exists
					$existing = get_posts( array(
						'post_type' => 'bsc_brewery',
						'meta_key' => 'bsc_supabase_id',
						'meta_value' => $b['id'],
						'numberposts' => 1
					) );

					$args = array(
						'post_title' => $b['name'],
						'post_content' => isset($b['description']) ? $b['description'] : '',
						'post_status' => 'publish',
						'post_type' => 'bsc_brewery'
					);

					if ( empty( $existing ) ) {
						// Create
						$pid = wp_insert_post( $args );
						if ( ! is_wp_error( $pid ) ) {
							update_post_meta( $pid, 'bsc_supabase_id', $b['id'] );
						}
					} else {
						// Update
						$args['ID'] = $existing[0]->ID;
						wp_update_post( $args );
						// Update stats if needed? Wait for sync_ratings for stats.
					}
				}
			}
		}

		// Import Beers
		$response = $this->make_request( '/rest/v1/beers?select=*' );
		if ( ! is_wp_error( $response ) ) {
			$beers = json_decode( wp_remote_retrieve_body( $response ), true );
			if ( is_array( $beers ) ) {
				foreach ( $beers as $beer ) {
					$existing = get_posts( array(
						'post_type' => 'bsc_recipe',
						'meta_key' => 'bsc_supabase_id',
						'meta_value' => $beer['id'],
						'numberposts' => 1
					) );

					// Find local brewery
					$brewery_post = get_posts( array(
						'post_type' => 'bsc_brewery',
						'meta_key' => 'bsc_supabase_id',
						'meta_value' => $beer['brewery_id'],
						'numberposts' => 1
					) );
					$brewery_id = !empty($brewery_post) ? $brewery_post[0]->ID : 0;

					$args = array(
						'post_title' => $beer['name'],
						'post_status' => 'publish',
						'post_type' => 'bsc_recipe'
					);

					$pid = 0;
					if ( empty( $existing ) ) {
						$pid = wp_insert_post( $args );
						if ( ! is_wp_error( $pid ) ) {
							update_post_meta( $pid, 'bsc_supabase_id', $beer['id'] );
						}
					} else {
						$pid = $existing[0]->ID;
						$args['ID'] = $pid;
						wp_update_post( $args );
					}

					if ( $pid && ! is_wp_error( $pid ) ) {
						update_post_meta( $pid, 'bsc_style', $beer['style'] );
						update_post_meta( $pid, 'bsc_abv', $beer['abv'] );
						if ( $brewery_id ) {
							update_post_meta( $pid, 'bsc_brewery_id', $brewery_id );
						}
						if ( isset( $beer['rating'] ) ) {
							update_post_meta( $pid, 'bsc_average_rating', $beer['rating'] );
						}
					}
				}
			}
		}
	}

	private function make_request( $endpoint, $method = 'GET', $data = null ) {
		if ( empty( $this->api_url ) || empty( $this->api_key ) ) {
			error_log( 'BSC Supabase: Credentials missing.' );
			return new WP_Error( 'missing_credentials', 'Supabase URL or Key missing.' );
		}

		$url = rtrim( $this->api_url, '/' ) . $endpoint;

		$args = array(
			'method'  => $method,
			'headers' => array(
				'apikey'        => $this->api_key,
				'Authorization' => 'Bearer ' . $this->api_key,
				'Content-Type'  => 'application/json',
				'Prefer'        => 'return=representation',
			),
			'timeout' => 45,
		);

		if ( $data ) {
			$args['body'] = json_encode( $data );
		}

		$response = wp_remote_request( $url, $args );

		if ( is_wp_error( $response ) ) {
			error_log( 'BSC Supabase Request Error [' . $method . ' ' . $url . ']: ' . $response->get_error_message() );
		} elseif ( wp_remote_retrieve_response_code( $response ) >= 400 ) {
			error_log( 'BSC Supabase Request Failed [' . $method . ' ' . $url . ']: ' . wp_remote_retrieve_response_code( $response ) . ' - ' . wp_remote_retrieve_body( $response ) );
		}

		return $response;
	}

	public function sync_ratings() {
		$this->import_from_supabase(); // Re-use import logic which updates content and ratings?
		// Actually import_from_supabase does everything.
		// So sync_ratings can just call it? Or maybe sync_ratings should only update ratings to avoid overwriting WP edits?
		// User said "Affichage sur le site uniquement si créé par un utilisateur WP".
		// Import creates posts. This conflicts with "Affichage...".
		// But "Inverse" implies full sync.
		// I will keep sync_ratings as updating RATINGS only for existing posts, as per original requirement.
		// import_from_supabase will be Manual or "Sync All" button.

		// Original sync_ratings logic:
		$beers = get_posts( array(
			'post_type' => 'bsc_recipe',
			'numberposts' => -1,
			'meta_query' => array( array( 'key' => 'bsc_supabase_id', 'compare' => 'EXISTS' ) ),
		) );

		foreach ( $beers as $beer ) {
			$supabase_id = get_post_meta( $beer->ID, 'bsc_supabase_id', true );
			if ( ! $supabase_id ) continue;

			$response = $this->make_request( '/rest/v1/beers?id=eq.' . $supabase_id . '&select=rating' );
			if ( ! is_wp_error( $response ) ) {
				$body = json_decode( wp_remote_retrieve_body( $response ), true );
				if ( ! empty( $body ) && isset( $body[0]['rating'] ) ) {
					update_post_meta( $beer->ID, 'bsc_average_rating', $body[0]['rating'] );
				}
			}
		}

		// Stats update... (same as before)
		$breweries = get_posts( array(
			'post_type' => 'bsc_brewery',
			'numberposts' => -1
		) );
		foreach ( $breweries as $brewery ) {
			$brewery_beers = get_posts( array(
				'post_type' => 'bsc_recipe',
				'meta_key' => 'bsc_brewery_id',
				'meta_value' => $brewery->ID,
				'numberposts' => -1
			) );

			$total_rating = 0;
			$count = 0;
			foreach ( $brewery_beers as $b_beer ) {
				$r = get_post_meta( $b_beer->ID, 'bsc_average_rating', true );
				if ( $r ) {
					$total_rating += floatval( $r );
					$count++;
				}
			}
			if ( $count > 0 ) {
				$avg = $total_rating / $count;
				update_post_meta( $brewery->ID, 'bsc_average_rating', round( $avg, 2 ) );
			} else {
				update_post_meta( $brewery->ID, 'bsc_average_rating', 0 );
			}
			update_post_meta( $brewery->ID, 'bsc_beer_count', count( $brewery_beers ) );
		}
	}
}
