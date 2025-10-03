<?php

return [
    'slug'        => 'defer-enqueued-js',
    'title'       => __( 'Defer Enqueued JavaScript', 'snippet-press' ),
    'description' => __( 'Adds defer to eligible scripts so rendering is not blocked.', 'snippet-press' ),
    'category'    => 'performance',
    'tags'        => [ 'javascript', 'optimization' ],
    'highlights'  => [
        __( 'Skips admin requests and critical dependencies like jQuery.', 'snippet-press' ),
        __( 'Exposes a filter so additional handles can be excluded.', 'snippet-press' ),
    ],
    'code'        => <<<'PHP'
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function spx_defer_enqueued_scripts( string $tag, string $handle, string $src ): string {
    if ( is_admin() || strpos( $tag, '<script' ) === false || $src === '' ) {
        return $tag;
    }

    $excluded = apply_filters(
        'spx_defer_scripts_exclusions',
        [ 'jquery', 'jquery-core', 'jquery-migrate' ]
    );

    if ( in_array( $handle, $excluded, true ) ) {
        return $tag;
    }

    if ( strpos( $tag, ' defer' ) !== false || strpos( $tag, ' async' ) !== false ) {
        return $tag;
    }

    return str_replace( '<script ', '<script defer="defer" ', $tag );
}

add_filter( 'script_loader_tag', 'spx_defer_enqueued_scripts', 10, 3 );
PHP,
    'type'        => 'php',
    'scopes'      => [ 'frontend' ],
    'priority'    => 10,
    'status'      => 'disabled',
    'notes'       => __( 'Use the spx_defer_scripts_exclusions filter to avoid deferring critical inline-dependent scripts.', 'snippet-press' ),
];
