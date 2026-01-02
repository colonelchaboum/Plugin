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
		// Future hooks will go here
	}

	private function load_dependencies() {
		require_once BSC_BREW_MANAGER_PATH . 'includes/class-bsc-taxonomies.php';
		new BSC_Taxonomies();
		require_once BSC_BREW_MANAGER_PATH . 'includes/class-bsc-meta.php';
		new BSC_Meta();
		require_once BSC_BREW_MANAGER_PATH . 'includes/class-bsc-form-handler.php';
		new BSC_Form_Handler();
		require_once BSC_BREW_MANAGER_PATH . 'includes/class-bsc-display.php';
		new BSC_Frontend_Display();
		require_once BSC_BREW_MANAGER_PATH . 'includes/class-bsc-reviews.php';
		new BSC_Reviews();

		add_action( 'elementor/init', array( $this, 'load_elementor_support' ) );
	}

	public function load_elementor_support() {
		require_once BSC_BREW_MANAGER_PATH . 'includes/elementor/class-bsc-elementor.php';
		new BSC_Elementor();
	}

	public function register_cpt() {
		$labels = array(
			'name'                  => _x( 'Recettes', 'Post Type General Name', 'bsc-brew-manager' ),
			'singular_name'         => _x( 'Recette', 'Post Type Singular Name', 'bsc-brew-manager' ),
			'menu_name'             => __( 'Recettes Bière', 'bsc-brew-manager' ),
			'name_admin_bar'        => __( 'Recette Bière', 'bsc-brew-manager' ),
			'archives'              => __( 'Archives des recettes', 'bsc-brew-manager' ),
			'attributes'            => __( 'Attributs de recette', 'bsc-brew-manager' ),
			'parent_item_colon'     => __( 'Recette parente :', 'bsc-brew-manager' ),
			'all_items'             => __( 'Toutes les recettes', 'bsc-brew-manager' ),
			'add_new_item'          => __( 'Ajouter une nouvelle recette', 'bsc-brew-manager' ),
			'add_new'               => __( 'Ajouter', 'bsc-brew-manager' ),
			'new_item'              => __( 'Nouvelle recette', 'bsc-brew-manager' ),
			'edit_item'             => __( 'Modifier la recette', 'bsc-brew-manager' ),
			'update_item'           => __( 'Mettre à jour la recette', 'bsc-brew-manager' ),
			'view_item'             => __( 'Voir la recette', 'bsc-brew-manager' ),
			'view_items'            => __( 'Voir les recettes', 'bsc-brew-manager' ),
			'search_items'          => __( 'Rechercher une recette', 'bsc-brew-manager' ),
			'not_found'             => __( 'Aucune recette trouvée', 'bsc-brew-manager' ),
			'not_found_in_trash'    => __( 'Aucune recette trouvée dans la corbeille', 'bsc-brew-manager' ),
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
			'menu_position'         => 5,
			'menu_icon'             => 'dashicons-beer',
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
