<?php
/**
 * Plugin Name: Brewers Social Club - Brew Manager
 * Plugin URI: https://brewerssocialclub.com/
 * Description: Plugin pour gérer les recettes de bière, les évaluations et les échanges entre brasseurs.
 * Version: 1.0.0
 * Author: Jules
 * Text Domain: bsc-brew-manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BSC_BREW_MANAGER_PATH', plugin_dir_path( __FILE__ ) );
define( 'BSC_BREW_MANAGER_URL', plugin_dir_url( __FILE__ ) );

class BSC_Brew_Manager {

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->load_dependencies();
		add_action( 'init', array( $this, 'register_cpt' ) );

		// Cron for Ratings Sync
		add_filter( 'cron_schedules', array( $this, 'add_cron_interval' ) );
		add_action( 'bsc_sync_ratings_event', array( $this, 'execute_rating_sync' ) );

		if ( ! wp_next_scheduled( 'bsc_sync_ratings_event' ) ) {
			wp_schedule_event( time(), 'hourly', 'bsc_sync_ratings_event' );
		}
	}

	public function add_cron_interval( $schedules ) {
		$schedules['hourly'] = array(
			'interval' => 3600,
			'display'  => __( 'Hourly' ),
		);
		return $schedules;
	}

	public function execute_rating_sync() {
		if ( class_exists( 'BSC_Supabase' ) ) {
			$supabase = new BSC_Supabase();
			$supabase->sync_ratings();
		}
	}

	private function load_dependencies() {
		require_once BSC_BREW_MANAGER_PATH . 'includes/class-bsc-supabase.php';
		// Initialized on demand or global if needed, but for now we just load class.

		require_once BSC_BREW_MANAGER_PATH . 'includes/class-bsc-settings.php';
		new BSC_Settings();

		require_once BSC_BREW_MANAGER_PATH . 'includes/class-bsc-meta.php';
		new BSC_Meta();
		require_once BSC_BREW_MANAGER_PATH . 'includes/class-bsc-form-handler.php';
		new BSC_Form_Handler();
		require_once BSC_BREW_MANAGER_PATH . 'includes/class-bsc-display.php';
		new BSC_Frontend_Display();
		require_once BSC_BREW_MANAGER_PATH . 'includes/class-bsc-review-handler.php';
		new BSC_Review_Handler();
		require_once BSC_BREW_MANAGER_PATH . 'includes/class-bsc-dashboard.php';
		new BSC_Dashboard();

		require_once BSC_BREW_MANAGER_PATH . 'includes/elementor/class-bsc-elementor.php';
		BSC_Elementor_Manager::init();
	}

	public function register_cpt() {
		// Breweries CPT
		$brewery_labels = array(
			'name'                  => _x( 'Brasseries', 'Post Type General Name', 'bsc-brew-manager' ),
			'singular_name'         => _x( 'Brasserie', 'Post Type Singular Name', 'bsc-brew-manager' ),
			'menu_name'             => __( 'Brasseries', 'bsc-brew-manager' ),
			'name_admin_bar'        => __( 'Brasserie', 'bsc-brew-manager' ),
			'archives'              => __( 'Archives des brasseries', 'bsc-brew-manager' ),
			'all_items'             => __( 'Toutes les brasseries', 'bsc-brew-manager' ),
			'add_new_item'          => __( 'Ajouter une nouvelle brasserie', 'bsc-brew-manager' ),
			'add_new'               => __( 'Ajouter', 'bsc-brew-manager' ),
			'new_item'              => __( 'Nouvelle brasserie', 'bsc-brew-manager' ),
			'edit_item'             => __( 'Modifier la brasserie', 'bsc-brew-manager' ),
			'update_item'           => __( 'Mettre à jour la brasserie', 'bsc-brew-manager' ),
			'view_item'             => __( 'Voir la brasserie', 'bsc-brew-manager' ),
			'view_items'            => __( 'Voir les brasseries', 'bsc-brew-manager' ),
			'search_items'          => __( 'Rechercher une brasserie', 'bsc-brew-manager' ),
			'not_found'             => __( 'Aucune brasserie trouvée', 'bsc-brew-manager' ),
			'not_found_in_trash'    => __( 'Aucune brasserie trouvée dans la corbeille', 'bsc-brew-manager' ),
			'featured_image'        => __( 'Logo/Photo', 'bsc-brew-manager' ),
			'set_featured_image'    => __( 'Définir le logo', 'bsc-brew-manager' ),
			'remove_featured_image' => __( 'Supprimer le logo', 'bsc-brew-manager' ),
			'use_featured_image'    => __( 'Utiliser comme logo', 'bsc-brew-manager' ),
		);
		$brewery_args = array(
			'label'                 => __( 'Brasserie', 'bsc-brew-manager' ),
			'description'           => __( 'Brasseries amateurs', 'bsc-brew-manager' ),
			'labels'                => $brewery_labels,
			'supports'              => array( 'title', 'editor', 'thumbnail', 'author', 'custom-fields' ),
			'hierarchical'          => false,
			'public'                => true,
			'show_ui'               => true,
			'show_in_menu'          => 'bsc-brew-manager',
			'menu_position'         => 20,
			'menu_icon'             => 'dashicons-store',
			'show_in_admin_bar'     => true,
			'show_in_nav_menus'     => true,
			'can_export'            => true,
			'has_archive'           => true,
			'exclude_from_search'   => false,
			'publicly_queryable'    => true,
			'capability_type'       => 'post',
		);
		register_post_type( 'bsc_brewery', $brewery_args );

		// Beers CPT (formerly Recipes)
		$labels = array(
			'name'                  => _x( 'Bières', 'Post Type General Name', 'bsc-brew-manager' ),
			'singular_name'         => _x( 'Bière', 'Post Type Singular Name', 'bsc-brew-manager' ),
			'menu_name'             => __( 'Bières', 'bsc-brew-manager' ),
			'name_admin_bar'        => __( 'Bière', 'bsc-brew-manager' ),
			'archives'              => __( 'Archives des bières', 'bsc-brew-manager' ),
			'attributes'            => __( 'Attributs de bière', 'bsc-brew-manager' ),
			'parent_item_colon'     => __( 'Bière parente :', 'bsc-brew-manager' ),
			'all_items'             => __( 'Toutes les bières', 'bsc-brew-manager' ),
			'add_new_item'          => __( 'Ajouter une nouvelle bière', 'bsc-brew-manager' ),
			'add_new'               => __( 'Ajouter', 'bsc-brew-manager' ),
			'new_item'              => __( 'Nouvelle bière', 'bsc-brew-manager' ),
			'edit_item'             => __( 'Modifier la bière', 'bsc-brew-manager' ),
			'update_item'           => __( 'Mettre à jour la bière', 'bsc-brew-manager' ),
			'view_item'             => __( 'Voir la bière', 'bsc-brew-manager' ),
			'view_items'            => __( 'Voir les bières', 'bsc-brew-manager' ),
			'search_items'          => __( 'Rechercher une bière', 'bsc-brew-manager' ),
			'not_found'             => __( 'Aucune bière trouvée', 'bsc-brew-manager' ),
			'not_found_in_trash'    => __( 'Aucune bière trouvée dans la corbeille', 'bsc-brew-manager' ),
			'featured_image'        => __( 'Photo de la bière', 'bsc-brew-manager' ),
			'set_featured_image'    => __( 'Définir la photo', 'bsc-brew-manager' ),
			'remove_featured_image' => __( 'Supprimer la photo', 'bsc-brew-manager' ),
			'use_featured_image'    => __( 'Utiliser comme photo', 'bsc-brew-manager' ),
		);
		$args = array(
			'label'                 => __( 'Recette', 'bsc-brew-manager' ),
			'description'           => __( 'Recettes de bière des utilisateurs', 'bsc-brew-manager' ),
			'labels'                => $labels,
			'supports'              => array( 'title', 'editor', 'thumbnail', 'comments', 'author', 'custom-fields' ),
			'taxonomies'            => array( 'category', 'post_tag' ), // Can add custom taxonomy later
			'hierarchical'          => false,
			'public'                => true,
			'show_ui'               => true,
			'show_in_menu'          => true,
			'menu_position'         => 21,
			'menu_icon'             => 'dashicons-beer',
			'show_in_menu'          => 'bsc-brew-manager',
			'show_in_admin_bar'     => true,
			'show_in_nav_menus'     => true,
			'can_export'            => true,
			'has_archive'           => true,
			'exclude_from_search'   => false,
			'publicly_queryable'    => true,
			'capability_type'       => 'post',
		);
		register_post_type( 'bsc_recipe', $args );
	}
}

// Initialize
add_action( 'plugins_loaded', array( 'BSC_Brew_Manager', 'get_instance' ) );
