<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BSC_Settings {

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	public function add_admin_menu() {
		add_menu_page(
			__( 'Brewers Social Club', 'bsc-brew-manager' ),
			__( 'Brewers SC', 'bsc-brew-manager' ),
			'manage_options',
			'bsc-brew-manager',
			array( $this, 'render_main_page' ),
			'dashicons-beer',
			55
		);

		add_submenu_page(
			'bsc-brew-manager',
			__( 'Paramètres API', 'bsc-brew-manager' ),
			__( 'Paramètres API', 'bsc-brew-manager' ),
			'manage_options',
			'bsc-settings',
			array( $this, 'render_settings_page' )
		);

		add_submenu_page(
			'bsc-brew-manager',
			__( 'Modération', 'bsc-brew-manager' ),
			__( 'Modération', 'bsc-brew-manager' ),
			'manage_options',
			'bsc-moderation',
			array( $this, 'render_moderation_page' )
		);
	}

	public function register_settings() {
		register_setting( 'bsc_settings_group', 'bsc_supabase_url' );
		register_setting( 'bsc_settings_group', 'bsc_supabase_api_key' );
		register_setting( 'bsc_settings_group', 'bsc_supabase_project_id' );
	}

	public function render_main_page() {
		?>
		<div class="wrap">
			<h1><?php _e( 'Brewers Social Club - Gestion', 'bsc-brew-manager' ); ?></h1>
			<p><?php _e( 'Bienvenue dans le gestionnaire de brasseries et recettes.', 'bsc-brew-manager' ); ?></p>
		</div>
		<?php
	}

	public function render_settings_page() {
		$connection_status = '';
		if ( isset( $_POST['bsc_test_connection'] ) ) {
			if ( ! class_exists( 'BSC_Supabase' ) ) {
				require_once BSC_BREW_MANAGER_PATH . 'includes/class-bsc-supabase.php';
			}
			$supabase = new BSC_Supabase();
			$test = $supabase->connection_test();
			if ( is_wp_error( $test ) ) {
				$connection_status = '<div class="notice notice-error"><p>' . $test->get_error_message() . '</p></div>';
			} else {
				$connection_status = '<div class="notice notice-success"><p>' . __( 'Connexion réussie à Supabase !', 'bsc-brew-manager' ) . '</p></div>';
			}
		}
		?>
		<div class="wrap">
			<h1><?php _e( 'Paramètres API Supabase', 'bsc-brew-manager' ); ?></h1>
			<?php echo $connection_status; ?>
			<form method="post" action="options.php">
				<?php settings_fields( 'bsc_settings_group' ); ?>
				<?php do_settings_sections( 'bsc_settings_group' ); ?>
				<table class="form-table">
					<tr valign="top">
						<th scope="row">Supabase URL</th>
						<td><input type="text" name="bsc_supabase_url" value="<?php echo esc_attr( get_option( 'bsc_supabase_url' ) ); ?>" class="regular-text" /></td>
					</tr>
					<tr valign="top">
						<th scope="row">Supabase API Key (service_role ou anon)</th>
						<td><input type="password" name="bsc_supabase_api_key" value="<?php echo esc_attr( get_option( 'bsc_supabase_api_key' ) ); ?>" class="regular-text" /></td>
					</tr>
					<tr valign="top">
						<th scope="row">Project ID (Optionnel)</th>
						<td><input type="text" name="bsc_supabase_project_id" value="<?php echo esc_attr( get_option( 'bsc_supabase_project_id' ) ); ?>" class="regular-text" /></td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>

			<hr>

			<form method="post" action="">
				<input type="submit" name="bsc_test_connection" class="button button-secondary" value="<?php _e( 'Tester la connexion', 'bsc-brew-manager' ); ?>">
			</form>
		</div>
		<?php
	}

	public function render_moderation_page() {
		// Handle Actions
		if ( isset( $_GET['action'], $_GET['post_id'], $_GET['_wpnonce'] ) ) {
			$action = $_GET['action'];
			$post_id = intval( $_GET['post_id'] );

			if ( wp_verify_nonce( $_GET['_wpnonce'], 'bsc_mod_' . $post_id ) ) {
				if ( $action === 'approve' ) {
					wp_update_post( array(
						'ID' => $post_id,
						'post_status' => 'publish'
					) );
					echo '<div class="notice notice-success"><p>' . __( 'Élément approuvé.', 'bsc-brew-manager' ) . '</p></div>';
				} elseif ( $action === 'reject' ) {
					// Delete from Supabase first if needed
					if ( class_exists( 'BSC_Supabase' ) ) {
						$supabase = new BSC_Supabase();
						if ( get_post_type( $post_id ) === 'bsc_brewery' ) {
							$supabase->delete_brewery( $post_id );
						} elseif ( get_post_type( $post_id ) === 'bsc_recipe' ) {
							$supabase->delete_beer( $post_id );
						}
					}
					wp_trash_post( $post_id );
					echo '<div class="notice notice-warning"><p>' . __( 'Élément rejeté et mis à la corbeille.', 'bsc-brew-manager' ) . '</p></div>';
				}
			}
		}

		$pending_breweries = get_posts( array(
			'post_type' => 'bsc_brewery',
			'post_status' => 'pending',
			'numberposts' => -1
		) );

		$pending_beers = get_posts( array(
			'post_type' => 'bsc_recipe',
			'post_status' => 'pending',
			'numberposts' => -1
		) );

		?>
		<div class="wrap">
			<h1><?php _e( 'Modération', 'bsc-brew-manager' ); ?></h1>

			<h2><?php _e( 'Brasseries en attente', 'bsc-brew-manager' ); ?></h2>
			<table class="widefat fixed striped">
				<thead><tr><th>Titre</th><th>Auteur</th><th>Date</th><th>Actions</th></tr></thead>
				<tbody>
				<?php if ( empty( $pending_breweries ) ) : ?>
					<tr><td colspan="4"><?php _e( 'Aucune brasserie en attente.', 'bsc-brew-manager' ); ?></td></tr>
				<?php else : ?>
					<?php foreach ( $pending_breweries as $post ) : ?>
						<tr>
							<td><a href="<?php echo get_edit_post_link( $post->ID ); ?>"><?php echo esc_html( $post->post_title ); ?></a></td>
							<td><?php echo get_the_author_meta( 'display_name', $post->post_author ); ?></td>
							<td><?php echo get_the_date( '', $post ); ?></td>
							<td>
								<a href="<?php echo wp_nonce_url( add_query_arg( array( 'action' => 'approve', 'post_id' => $post->ID ) ), 'bsc_mod_' . $post->ID ); ?>" class="button button-primary"><?php _e( 'Valider', 'bsc-brew-manager' ); ?></a>
								<a href="<?php echo wp_nonce_url( add_query_arg( array( 'action' => 'reject', 'post_id' => $post->ID ) ), 'bsc_mod_' . $post->ID ); ?>" class="button button-secondary" onclick="return confirm('Refuser et supprimer ?');" style="color: #d63638; border-color: #d63638;"><?php _e( 'Refuser', 'bsc-brew-manager' ); ?></a>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
				</tbody>
			</table>

			<br>

			<h2><?php _e( 'Bières en attente', 'bsc-brew-manager' ); ?></h2>
			<table class="widefat fixed striped">
				<thead><tr><th>Titre</th><th>Brasserie</th><th>Auteur</th><th>Actions</th></tr></thead>
				<tbody>
				<?php if ( empty( $pending_beers ) ) : ?>
					<tr><td colspan="4"><?php _e( 'Aucune bière en attente.', 'bsc-brew-manager' ); ?></td></tr>
				<?php else : ?>
					<?php foreach ( $pending_beers as $post ) :
						$brewery_id = get_post_meta( $post->ID, 'bsc_brewery_id', true );
						$brewery_name = $brewery_id ? get_the_title( $brewery_id ) : '-';
					?>
						<tr>
							<td><a href="<?php echo get_edit_post_link( $post->ID ); ?>"><?php echo esc_html( $post->post_title ); ?></a></td>
							<td><?php echo esc_html( $brewery_name ); ?></td>
							<td><?php echo get_the_author_meta( 'display_name', $post->post_author ); ?></td>
							<td>
								<a href="<?php echo wp_nonce_url( add_query_arg( array( 'action' => 'approve', 'post_id' => $post->ID ) ), 'bsc_mod_' . $post->ID ); ?>" class="button button-primary"><?php _e( 'Valider', 'bsc-brew-manager' ); ?></a>
								<a href="<?php echo wp_nonce_url( add_query_arg( array( 'action' => 'reject', 'post_id' => $post->ID ) ), 'bsc_mod_' . $post->ID ); ?>" class="button button-secondary" onclick="return confirm('Refuser et supprimer ?');" style="color: #d63638; border-color: #d63638;"><?php _e( 'Refuser', 'bsc-brew-manager' ); ?></a>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}
}
