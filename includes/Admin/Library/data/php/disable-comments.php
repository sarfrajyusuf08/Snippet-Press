<?php

return [
    'slug'        => 'disable-comments-sitewide',
    'title'       => __( 'Disable Comments Everywhere', 'snippet-press' ),
    'description' => __( 'Remove comment support from every post type and hide discussion pages in the dashboard.', 'snippet-press' ),
    'category'    => 'comments',
    'tags'        => [ 'disable', 'clean-up' ],
    'highlights'  => [
        __( 'Turns off comments and pings across your entire site.', 'snippet-press' ),
        __( 'Hides the Comments menu from the WordPress dashboard.', 'snippet-press' ),
    ],
    'code'        => <<<'PHP'
add_action( 'admin_init', function () {
    foreach ( get_post_types() as $post_type ) {
        remove_post_type_support( $post_type, 'comments' );
        remove_post_type_support( $post_type, 'trackbacks' );
    }
} );

add_action( 'init', function () {
    add_filter( 'comments_open', '__return_false', 20, 2 );
    add_filter( 'pings_open', '__return_false', 20, 2 );
    add_filter( 'comments_array', '__return_empty_array', 10, 2 );
} );

add_action( 'admin_menu', function () {
    remove_menu_page( 'edit-comments.php' );
} );
PHP,
    'type'        => 'php',
    'scopes'      => [ 'frontend', 'admin' ],
    'priority'    => 5,
    'status'      => 'disabled',
    'notes'       => __( 'Reactivate comments later by deleting or disabling this snippet.', 'snippet-press' ),
];
