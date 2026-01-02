<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BSC_Taxonomies {

	public function __construct() {
		add_action( 'init', array( $this, 'register_taxonomies' ) );
	}

	public function register_taxonomies() {
		// Style (BJCP or Free)
		$labels_style = array(
			'name'              => _x( 'Styles de Bière', 'taxonomy general name', 'bsc-brew-manager' ),
			'singular_name'     => _x( 'Style', 'taxonomy singular name', 'bsc-brew-manager' ),
			'search_items'      => __( 'Rechercher un style', 'bsc-brew-manager' ),
			'all_items'         => __( 'Tous les styles', 'bsc-brew-manager' ),
			'parent_item'       => __( 'Style parent', 'bsc-brew-manager' ),
			'parent_item_colon' => __( 'Style parent :', 'bsc-brew-manager' ),
			'edit_item'         => __( 'Modifier le style', 'bsc-brew-manager' ),
			'update_item'       => __( 'Mettre à jour le style', 'bsc-brew-manager' ),
			'add_new_item'      => __( 'Ajouter un nouveau style', 'bsc-brew-manager' ),
			'new_item_name'     => __( 'Nouveau nom de style', 'bsc-brew-manager' ),
			'menu_name'         => __( 'Styles', 'bsc-brew-manager' ),
		);

		register_taxonomy( 'bsc_style', array( 'bsc_recipe' ), array(
			'hierarchical'      => true,
			'labels'            => $labels_style,
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => true,
			'rewrite'           => array( 'slug' => 'style' ),
		) );

		// Level (Beginner, Intermediate, Expert)
		$labels_level = array(
			'name'              => _x( 'Niveaux', 'taxonomy general name', 'bsc-brew-manager' ),
			'singular_name'     => _x( 'Niveau', 'taxonomy singular name', 'bsc-brew-manager' ),
			'search_items'      => __( 'Rechercher un niveau', 'bsc-brew-manager' ),
			'all_items'         => __( 'Tous les niveaux', 'bsc-brew-manager' ),
			'edit_item'         => __( 'Modifier le niveau', 'bsc-brew-manager' ),
			'update_item'       => __( 'Mettre à jour le niveau', 'bsc-brew-manager' ),
			'add_new_item'      => __( 'Ajouter un nouveau niveau', 'bsc-brew-manager' ),
			'new_item_name'     => __( 'Nouveau nom de niveau', 'bsc-brew-manager' ),
			'menu_name'         => __( 'Niveaux', 'bsc-brew-manager' ),
		);

		register_taxonomy( 'bsc_level', array( 'bsc_recipe' ), array(
			'hierarchical'      => true, // Using hierarchical to act like categories (checklist)
			'labels'            => $labels_level,
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => true,
			'rewrite'           => array( 'slug' => 'niveau' ),
		) );
	}
}
