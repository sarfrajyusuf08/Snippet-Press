<?php

return [
    'slug'        => 'remove-asset-query-strings',
    'title'       => __( 'Remove Asset Query Strings', 'snippet-press' ),
    'description' => __( 'Strips ?ver parameters from enqueued CSS and JS for better caching.', 'snippet-press' ),
    'category'    => 'performance',
    'tags'        => [ 'caching', 'optimization' ],
    'highlights'  => [
        __( 'Improves CDN and proxy cache hit rates.', 'snippet-press' ),
        __( 'Skips admin pages so versioning continues when editing.', 'snippet-press' ),
    ],
    'code'        => <<<'PHP'
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function spx_remove_asset_version_query( string $src ): string {
    if ( is_admin() || strpos( $src, '?' ) === false ) {
        return $src;
    }

    $parts = wp_parse_url( $src );

    if ( ! is_array( $parts ) || empty( $parts['query'] ) ) {
        return $src;
    }

    parse_str( (string) $parts['query'], $query_vars );

    if ( ! isset( $query_vars['ver'] ) ) {
        return $src;
    }

    unset( $query_vars['ver'] );

    $base     = strtok( $src, '?' );
    $fragment = isset( $parts['fragment'] ) ? '#' . $parts['fragment'] : '';
    $query    = http_build_query( $query_vars, '', '&', PHP_QUERY_RFC3986 );

    if ( $query === '' ) {
        return $base . $fragment;
    }

    return $base . '?' . $query . $fragment;
}

add_filter( 'style_loader_src', 'spx_remove_asset_version_query', 15 );
add_filter( 'script_loader_src', 'spx_remove_asset_version_query', 15 );
PHP,
    'type'        => 'php',
    'scopes'      => [ 'frontend' ],
    'priority'    => 10,
    'status'      => 'disabled',
    'notes'       => __( 'Add explicit cache-busting when deploying assets because the automatic version parameter is removed.', 'snippet-press' ),
];
