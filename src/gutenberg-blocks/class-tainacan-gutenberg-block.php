<?php

include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

tainacan_blocks_initialize();

function tainacan_blocks_initialize() {
	global $wp_version;

	if(is_plugin_active('gutenberg/gutenberg.php') ||  $wp_version >= '5') {
		tainacan_blocks_add_gutenberg_blocks_actions();
	}
}

function tainacan_blocks_add_gutenberg_blocks_actions() {
	//add_action('init', 'tainacan_blocks_register_tainacan_collections_carousel');
	add_action('init', 'tainacan_blocks_register_tainacan_items_grid');

	add_action('init', 'tainacan_blocks_add_plugin_settings');

	add_action('wp_enqueue_scripts', 'tainacan_blocks_enqueue_on_theme');
	add_filter('block_categories', 'tainacan_blocks_register_tainacan_block_categories', 10, 2);
}

function tainacan_blocks_register_tainacan_block_categories($categories, $post){
	if ( $post->post_type !== 'post' ) {
		return $categories;
	}

	return array_merge(
		$categories,
		array(
			array(
				'slug' => 'tainacan-blocks',
				'title' => __( 'Tainacan', 'tainacan' ),
			),
		)
	);
}

function tainacan_blocks_enqueue_on_theme(){
	global $TAINACAN_BASE_URL;

	wp_enqueue_script(
		'items-grid-dynamic',
		$TAINACAN_BASE_URL . '/assets/gutenberg_items_grid_dynamic-components.js'
	);
}

function tainacan_blocks_register_tainacan_items_grid(){
	global $TAINACAN_BASE_URL;

	wp_register_script(
		'items-grid',
		$TAINACAN_BASE_URL . '/assets/gutenberg_items_grid-components.js',
		array('wp-blocks', 'wp-element', 'wp-components', 'wp-editor', 'underscore')
	);

	wp_register_style(
		'items-grid',
		$TAINACAN_BASE_URL . '/assets/css/tainacan-gutenberg-blocks-style.css',
		array('wp-edit-blocks')
	);

	if(function_exists('register_block_type')) {
		register_block_type( 'tainacan/items-grid', array(
			'editor_script' => 'items-grid',
			'style'         => 'items-grid',
			'render_callback' => 'dynamic_items_grid_render'
		) );
	}
}

function dynamic_items_grid_render($attributes) {
	return '<p id="tainacan-block-grid-item" collection-id="'. $attributes['URLCollectionID'] .'">{{ test }}</p>';
}

function tainacan_blocks_register_tainacan_collections_carousel(){
	global $TAINACAN_BASE_URL;

	wp_register_script(
		'collections-carousel',
			$TAINACAN_BASE_URL . '/assets/gutenberg_collections_carousel-components.js',
		array('wp-blocks', 'wp-element', 'wp-components', 'wp-editor')
	);

	wp_register_style(
		'collections-carousel',
		$TAINACAN_BASE_URL . '/assets/css/tainacan-gutenberg-blocks-style.css',
		array('wp-edit-blocks')
	);

	if(function_exists('register_block_type')) {
		register_block_type( 'tainacan/collections-carousel', array(
			'editor_script' => 'collections-carousel',
			'style'         => 'collections-carousel'
		) );
	}
}

function tainacan_blocks_get_plugin_js_settings(){
	global $TAINACAN_BASE_URL;

	$settings = [
		'root'     => esc_url_raw( rest_url() ) . 'tainacan/v2',
		'nonce'    => wp_create_nonce( 'wp_rest' ),
		'base_url' => $TAINACAN_BASE_URL
	];

	return $settings;
}

function tainacan_blocks_add_plugin_settings() {

	$settings = tainacan_blocks_get_plugin_js_settings();

	//wp_localize_script( 'collections-carousel', 'tainacan_plugin', $settings );
	wp_localize_script( 'items-grid', 'tainacan_plugin', $settings );
	wp_localize_script( 'items-grid-dynamic', 'tainacan_plugin', $settings );
}
