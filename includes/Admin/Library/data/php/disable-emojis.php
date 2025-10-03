<?php

return [
    'slug'        => 'disable-wp-emojis',
    'title'       => __( 'Disable Emoji Assets', 'snippet-press' ),
    'description' => __( 'Removes emoji scripts, styles, and filters to speed up responses.', 'snippet-press' ),
    'category'    => 'performance',
    'tags'        => [ 'optimization', 'cleanup' ],
    'highlights'  => [
        __( 'Unhooks emoji scripts and styles from front-end and admin.', 'snippet-press' ),
        __( 'Removes TinyMCE, email, and RSS emoji conversions.', 'snippet-press' ),
    ],
    'code'        => <<<'PHP'
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function spx_disable_wp_emojis(): void {
    remove_action( 'admin_print_styles', 'print_emoji_styles' );
    remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
    remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
    remove_action( 'wp_print_styles', 'print_emoji_styles' );
    remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
    remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
    remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
    add_filter( 'emoji_svg_url', '__return_false' );
}

function spx_disable_wp_emojis_tinymce( array $plugins ): array {
    $position = array_search( 'wpemoji', $plugins, true );

    if ( $position !== false ) {
        unset( $plugins[ $position ] );
    }

    return array_values( $plugins );
}

function spx_disable_wp_emojis_dns_prefetch( array $urls, string $relation_type ): array {
    if ( 'dns-prefetch' !== $relation_type ) {
        return $urls;
    }

    foreach ( $urls as $index => $url ) {
        if ( is_string( $url ) && strpos( $url, 'emoji' ) !== false ) {
            unset( $urls[ $index ] );
        }
    }

    return $urls;
}

add_action( 'init', 'spx_disable_wp_emojis' );
add_filter( 'tiny_mce_plugins', 'spx_disable_wp_emojis_tinymce' );
add_filter( 'wp_resource_hints', 'spx_disable_wp_emojis_dns_prefetch', 10, 2 );
PHP,
    'type'        => 'php',
    'scopes'      => [ 'frontend', 'admin' ],
    'priority'    => 5,
    'status'      => 'disabled',
    'notes'       => __( 'Clear browser caches to ensure visitors load pages without emoji assets.', 'snippet-press' ),
];
