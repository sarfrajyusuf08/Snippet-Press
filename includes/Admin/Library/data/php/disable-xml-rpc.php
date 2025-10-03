<?php

return [
    'slug'        => 'disable-xml-rpc',
    'title'       => __( 'Disable XML-RPC Endpoint', 'snippet-press' ),
    'description' => __( 'Blocks the XML-RPC API and pingback methods to reduce brute-force attempts.', 'snippet-press' ),
    'category'    => 'security',
    'tags'        => [ 'xml-rpc', 'hardening' ],
    'highlights'  => [
        __( 'Stops remote XML-RPC authentication and pingbacks.', 'snippet-press' ),
        __( 'Removes the X-Pingback header so the endpoint stays hidden.', 'snippet-press' ),
    ],
    'code'        => <<<'PHP'
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function spx_disable_xmlrpc(): bool {
    return false;
}

function spx_remove_xmlrpc_pingback_method( array $methods ): array {
    if ( isset( $methods['pingback.ping'] ) ) {
        unset( $methods['pingback.ping'] );
    }

    return $methods;
}

function spx_remove_pingback_header( array $headers ): array {
    if ( isset( $headers['X-Pingback'] ) ) {
        unset( $headers['X-Pingback'] );
    }

    return $headers;
}

add_filter( 'xmlrpc_enabled', 'spx_disable_xmlrpc' );
add_filter( 'xmlrpc_methods', 'spx_remove_xmlrpc_pingback_method' );
add_filter( 'wp_headers', 'spx_remove_pingback_header' );
PHP,
    'type'        => 'php',
    'scopes'      => [ 'frontend', 'admin' ],
    'priority'    => 5,
    'status'      => 'disabled',
    'notes'       => __( 'Ensure no connected services depend on XML-RPC before enabling this snippet.', 'snippet-press' ),
];
