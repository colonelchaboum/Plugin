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
			// 'logo' => ... handle image upload to Supabase storage or send URL?
			// For now, let's assume we send the URL if publicly accessible, or skip.
		);

		// If we have an image
		if ( has_post_thumbnail( $post_id ) ) {
			$data['logo_url'] = get_the_post_thumbnail_url( $post_id, 'full' );
		}

		$method = 'POST';
		$endpoint = '/rest/v1/breweries';

		if ( $supabase_id ) {
			// Update
			$method = 'PATCH';
			$endpoint .= '?id=eq.' . $supabase_id;
		}

		$response = $this->make_request( $endpoint, $method, $data );

		if ( ! is_wp_error( $response ) ) {
			$body = json_decode( wp_remote_retrieve_body( $response ), true );
			// If it was a create (POST), we expect the created object back if Prefer: return=representation header is sent
			if ( $method === 'POST' && ! empty( $body ) && isset( $body[0]['id'] ) ) {
				update_post_meta( $post_id, 'bsc_supabase_id', $body[0]['id'] );
			}
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
			return; // Must belong to a brewery
		}

		$brewery_supabase_id = get_post_meta( $brewery_post_id, 'bsc_supabase_id', true );
		if ( ! $brewery_supabase_id ) {
			// Try to sync brewery first? Or fail.
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

	private function make_request( $endpoint, $method = 'GET', $data = null ) {
		if ( empty( $this->api_url ) || empty( $this->api_key ) ) {
			return new WP_Error( 'missing_credentials', 'Supabase URL or Key missing.' );
		}

		$url = rtrim( $this->api_url, '/' ) . $endpoint;

		$args = array(
			'method'  => $method,
			'headers' => array(
				'apikey'        => $this->api_key,
				'Authorization' => 'Bearer ' . $this->api_key,
				'Content-Type'  => 'application/json',
				'Prefer'        => 'return=representation', // To get the ID back on POST
			),
			'timeout' => 45,
		);

		if ( $data ) {
			$args['body'] = json_encode( $data );
		}

		return wp_remote_request( $url, $args );
	}

	public function sync_ratings() {
		// Sync Beers Ratings
		$beers = get_posts( array(
			'post_type' => 'bsc_recipe',
			'numberposts' => -1,
			'meta_query' => array(
				array(
					'key' => 'bsc_supabase_id',
					'compare' => 'EXISTS',
				),
			),
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

		// Sync Breweries Ratings (Calculated from Supabase or stored there?
		// Prompt says "Note moyenne (calculée depuis les notes des bières via Supabase)".
		// If breweries table doesn't have rating, we might need to fetch all beers for brewery and avg.
		// But let's assume breweries table might have it or we calculate it here from WP data if sync is done.
		// Actually, if we just synced all beers ratings to WP, we can calculate brewery average from WP data!)

		$breweries = get_posts( array(
			'post_type' => 'bsc_brewery',
			'numberposts' => -1
		) );

		foreach ( $breweries as $brewery ) {
			// Calculate average from beers
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

			// Also update beer count
			update_post_meta( $brewery->ID, 'bsc_beer_count', count( $brewery_beers ) );
		}
	}
}
