<?php

return [
    'slug'        => 'strip-wp-version-meta',
    'title'       => __( 'Strip WordPress Version Meta', 'snippet-press' ),
    'description' => __( 'Removes WordPress version output from HTML and feeds to reduce fingerprinting.', 'snippet-press' ),
    'category'    => 'security',
    'tags'        => [ 'privacy', 'hardening' ],
    'highlights'  => [
        __( 'Filters generator tags so version numbers stay hidden.', 'snippet-press' ),
        __( 'Removes wp_generator from both front-end and admin screens.', 'snippet-press' ),
    ],
    'code'        => <<<'PHP'
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function spx_strip_wp_generator( string $generator = '' ): string {
    return '';
}

function spx_remove_version_meta(): void {
    remove_action( 'wp_head', 'wp_generator' );
    remove_action( 'admin_head', 'wp_generator' );
}

add_filter( 'the_generator', 'spx_strip_wp_generator' );
add_action( 'init', 'spx_remove_version_meta' );
PHP,
    'type'        => 'php',
    'scopes'      => [ 'frontend', 'admin' ],
    'priority'    => 5,
    'status'      => 'disabled',
    'notes'       => __( 'Purge caches after activation so the updated markup is delivered.', 'snippet-press' ),
];
