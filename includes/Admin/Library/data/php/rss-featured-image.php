<?php

return [
    'slug'        => 'rss-featured-image',
    'title'       => __( 'RSS Featured Image Injection', 'snippet-press' ),
    'description' => __( 'Prepends the post thumbnail to RSS feed content for richer feed readers.', 'snippet-press' ),
    'category'    => 'content',
    'tags'        => [ 'rss', 'featured-image' ],
    'highlights'  => [
        __( 'Works for both full content and excerpt-based feeds.', 'snippet-press' ),
        __( 'Falls back gracefully when no thumbnail is set.', 'snippet-press' ),
    ],
    'code'        => <<<'PHP'
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function spx_add_featured_image_to_feed( string $content ): string {
    if ( ! is_feed() ) {
        return $content;
    }

    $post = get_post();

    if ( ! $post instanceof WP_Post || ! has_post_thumbnail( $post ) ) {
        return $content;
    }

    $thumbnail = get_the_post_thumbnail(
        $post,
        'large',
        [
            'style' => 'margin-bottom:15px;width:100%;height:auto;',
        ]
    );

    if ( empty( $thumbnail ) ) {
        return $content;
    }

    return $thumbnail . $content;
}

add_filter( 'the_content_feed', 'spx_add_featured_image_to_feed' );
add_filter( 'the_excerpt_rss', 'spx_add_featured_image_to_feed' );
PHP,
    'type'        => 'php',
    'scopes'      => [ 'frontend' ],
    'priority'    => 10,
    'status'      => 'disabled',
    'notes'       => __( 'Validate the feed with your aggregator to ensure the inline HTML renders as expected.', 'snippet-press' ),
];
