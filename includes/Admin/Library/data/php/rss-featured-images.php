<?php

return [
    'slug'        => 'rss-featured-images',
    'title'       => __( 'Add Featured Images to RSS Feed', 'snippet-press' ),
    'description' => __( 'Append the post thumbnail to RSS feed content so readers and email services see your images.', 'snippet-press' ),
    'category'    => 'rss',
    'tags'        => [ 'content' ],
    'highlights'  => [
        __( 'Adds responsive markup for feed readers.', 'snippet-press' ),
        __( 'Skips posts without a featured image automatically.', 'snippet-press' ),
    ],
    'code'        => <<<'PHP'
add_filter( 'the_content_feed', function ( $content ) {
    if ( ! has_post_thumbnail() ) {
        return $content;
    }

    $thumbnail = get_the_post_thumbnail( null, 'large', [ 'style' => 'margin-bottom:16px;' ] );

    if ( ! $thumbnail ) {
        return $content;
    }

    return $thumbnail . $content;
} );
PHP,
    'type'        => 'php',
    'scopes'      => [ 'frontend' ],
    'priority'    => 10,
    'status'      => 'disabled',
    'notes'       => __( 'After installing you can adjust the thumbnail size by editing the snippet.', 'snippet-press' ),
];
