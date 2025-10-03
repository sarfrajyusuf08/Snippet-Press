<?php

return [
    'slug'        => 'disable-oembed-js',
    'title'       => __( 'Disable oEmbed Scripts', 'snippet-press' ),
    'description' => __( 'Disables oEmbed discovery and removes the front-end embed script.', 'snippet-press' ),
    'category'    => 'performance',
    'tags'        => [ 'oembed', 'optimization' ],
    'highlights'  => [
        __( 'Removes oEmbed REST routes and discovery headers.', 'snippet-press' ),
        __( 'Deregisters wp-embed.js on the front-end.', 'snippet-press' ),
    ],
    'code'        => <<<'PHP'
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function spx_disable_oembed_features(): void {
    remove_action( 'rest_api_init', 'wp_oembed_register_route' );
    remove_filter( 'oembed_dataparse', 'wp_filter_oembed_result', 10 );
    remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
    remove_action( 'wp_head', 'wp_oembed_add_host_js' );
    add_filter( 'embed_oembed_discover', '__return_false' );
}

function spx_deregister_wp_embed(): void {
    if ( is_admin() ) {
        return;
    }

    wp_deregister_script( 'wp-embed' );
}

add_action( 'init', 'spx_disable_oembed_features' );
add_action( 'wp_enqueue_scripts', 'spx_deregister_wp_embed', 100 );
PHP,
    'type'        => 'php',
    'scopes'      => [ 'frontend' ],
    'priority'    => 5,
    'status'      => 'disabled',
    'notes'       => __( 'Leave this disabled if you do not rely on oEmbed previews from other sites.', 'snippet-press' ),
];
