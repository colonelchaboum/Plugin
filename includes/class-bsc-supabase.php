<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BSC_Supabase {

	private $api_url;
	private $api_key;

	public function __construct() {
		$this->api_url    = get_option( 'bsc_supabase_url' );
		$this->api_key    = get_option( 'bsc_supabase_api_key' );
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
			'name'           => $post->post_title,
			'description_fr' => $post->post_content,
			'contact_name'   => get_post_meta( $post_id, 'bsc_contact_name', true ),
			'city'           => get_post_meta( $post_id, 'bsc_city', true ),
			'contry'         => get_post_meta( $post_id, 'bsc_country', true ), // User specified 'contry'
			'adress'         => get_post_meta( $post_id, 'bsc_address', true ), // User specified 'adress'
			'postcode'       => get_post_meta( $post_id, 'bsc_postcode', true ),
			'phone'          => get_post_meta( $post_id, 'bsc_phone', true ),
			'website'        => get_post_meta( $post_id, 'bsc_website', true ),
			// 'folowers' -> read only from WP usually? Or user can update?
			// If we sync TO supabase, we might overwrite followers if WP is master?
			// Usually followers are counted in App. I should probably NOT send followers unless I track them in WP.
			// I'll skip sending 'folowers' to Supabase to avoid zeroing it out.
		);

		// Image URL
		if ( has_post_thumbnail( $post_id ) ) {
			$data['image_url'] = get_the_post_thumbnail_url( $post_id, 'full' );
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

		$data = array(
			'name'           => $post->post_title,
			'description_fr' => $post->post_content,
			'style'          => get_post_meta( $post_id, 'bsc_style', true ),
			'abv'            => (float) get_post_meta( $post_id, 'bsc_abv', true ),
			'ibu'            => (int) get_post_meta( $post_id, 'bsc_ibu', true ),
			'hops'           => get_post_meta( $post_id, 'bsc_hops', true ),
			'malts'          => get_post_meta( $post_id, 'bsc_malts', true ),
			'brewery_id'     => (int) $brewery_supabase_id,
			// Skip rating/checkins sync TO Supabase as that's user generated data there?
			// Unless WP is master for creating rating? No, App users rate.
		);

		if ( has_post_thumbnail( $post_id ) ) {
			$data['image_url'] = get_the_post_thumbnail_url( $post_id, 'full' );
		}

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
					$existing = get_posts( array(
						'post_type' => 'bsc_brewery',
						'meta_key' => 'bsc_supabase_id',
						'meta_value' => $b['id'],
						'numberposts' => 1
					) );

					$args = array(
						'post_title' => $b['name'],
						'post_content' => isset($b['description_fr']) ? $b['description_fr'] : '',
						'post_status' => 'publish',
						'post_type' => 'bsc_brewery'
					);

					$pid = 0;
					if ( empty( $existing ) ) {
						$pid = wp_insert_post( $args );
						if ( ! is_wp_error( $pid ) ) {
							update_post_meta( $pid, 'bsc_supabase_id', $b['id'] );
						}
					} else {
						$pid = $existing[0]->ID;
						$args['ID'] = $pid;
						wp_update_post( $args );
					}

					if ( $pid && ! is_wp_error( $pid ) ) {
						// Update Meta
						update_post_meta( $pid, 'bsc_contact_name', isset($b['contact_name']) ? $b['contact_name'] : '' );
						update_post_meta( $pid, 'bsc_city', isset($b['city']) ? $b['city'] : '' );
						update_post_meta( $pid, 'bsc_country', isset($b['contry']) ? $b['contry'] : '' ); // Mapping 'contry' -> 'bsc_country'
						update_post_meta( $pid, 'bsc_address', isset($b['adress']) ? $b['adress'] : '' ); // Mapping 'adress' -> 'bsc_address'
						update_post_meta( $pid, 'bsc_postcode', isset($b['postcode']) ? $b['postcode'] : '' );
						update_post_meta( $pid, 'bsc_phone', isset($b['phone']) ? $b['phone'] : '' );
						update_post_meta( $pid, 'bsc_website', isset($b['website']) ? $b['website'] : '' );
						update_post_meta( $pid, 'bsc_followers', isset($b['folowers']) ? $b['folowers'] : 0 );
						update_post_meta( $pid, 'bsc_image_url', isset($b['image_url']) ? $b['image_url'] : '' );
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
						'post_content' => isset($beer['description_fr']) ? $beer['description_fr'] : '',
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
						update_post_meta( $pid, 'bsc_style', isset($beer['style']) ? $beer['style'] : '' );
						update_post_meta( $pid, 'bsc_abv', isset($beer['abv']) ? $beer['abv'] : '' );
						update_post_meta( $pid, 'bsc_ibu', isset($beer['ibu']) ? $beer['ibu'] : '' );
						update_post_meta( $pid, 'bsc_hops', isset($beer['hops']) ? $beer['hops'] : '' );
						update_post_meta( $pid, 'bsc_malts', isset($beer['malts']) ? $beer['malts'] : '' );
						update_post_meta( $pid, 'bsc_image_url', isset($beer['image_url']) ? $beer['image_url'] : '' );
						if ( $brewery_id ) {
							update_post_meta( $pid, 'bsc_brewery_id', $brewery_id );
						}
						if ( isset( $beer['average_rating'] ) ) {
							update_post_meta( $pid, 'bsc_average_rating', $beer['average_rating'] );
						}
						if ( isset( $beer['total_checkins'] ) ) {
							update_post_meta( $pid, 'bsc_total_checkins', $beer['total_checkins'] );
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
		// Use import to update everything (ratings + content) since inverse sync is requested?
		// But sync_ratings runs on cron. Import is heavy.
		// I will keep sync_ratings focused on ratings for now, or just rely on manual import.
		// The user said "comme les brasseries la mise à jour doit etre faite en import comme en export".
		// This implies automatic reverse sync might be desired, but for now I'll update sync_ratings to fetch the new rating fields.

		$beers = get_posts( array(
			'post_type' => 'bsc_recipe',
			'numberposts' => -1,
			'meta_query' => array( array( 'key' => 'bsc_supabase_id', 'compare' => 'EXISTS' ) ),
		) );

		foreach ( $beers as $beer ) {
			$supabase_id = get_post_meta( $beer->ID, 'bsc_supabase_id', true );
			if ( ! $supabase_id ) continue;

			// Fetch rating and checkins
			$response = $this->make_request( '/rest/v1/beers?id=eq.' . $supabase_id . '&select=average_rating,total_checkins' );
			if ( ! is_wp_error( $response ) ) {
				$body = json_decode( wp_remote_retrieve_body( $response ), true );
				if ( ! empty( $body ) ) {
					if ( isset( $body[0]['average_rating'] ) ) {
						update_post_meta( $beer->ID, 'bsc_average_rating', $body[0]['average_rating'] );
					}
					if ( isset( $body[0]['total_checkins'] ) ) {
						update_post_meta( $beer->ID, 'bsc_total_checkins', $body[0]['total_checkins'] );
					}
				}
			}
		}

		// Update Brewery Stats (from beers)
		// Or fetch 'folowers' from breweries table?
		$breweries = get_posts( array(
			'post_type' => 'bsc_brewery',
			'numberposts' => -1
		) );
		foreach ( $breweries as $brewery ) {
			$supabase_id = get_post_meta( $brewery->ID, 'bsc_supabase_id', true );
			if ( $supabase_id ) {
				$response = $this->make_request( '/rest/v1/breweries?id=eq.' . $supabase_id . '&select=folowers' );
				if ( ! is_wp_error( $response ) ) {
					$body = json_decode( wp_remote_retrieve_body( $response ), true );
					if ( ! empty( $body ) && isset( $body[0]['folowers'] ) ) {
						update_post_meta( $brewery->ID, 'bsc_followers', $body[0]['folowers'] );
					}
				}
			}

			// Recalculate average rating from beers
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
