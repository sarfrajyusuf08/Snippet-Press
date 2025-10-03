<?php

return [
    'slug'        => 'redirect-author-archives',
    'title'       => __( 'Redirect Author Archives Home', 'snippet-press' ),
    'description' => __( 'Redirects author archive pages to the homepage to avoid thin content.', 'snippet-press' ),
    'category'    => 'seo',
    'tags'        => [ 'redirects', 'archives' ],
    'highlights'  => [
        __( 'Sends a 301 redirect from author archives to the site front page.', 'snippet-press' ),
        __( 'Skips feeds and admin screens to avoid interfering with editors.', 'snippet-press' ),
    ],
    'code'        => <<<'PHP'
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function spx_redirect_author_archives(): void {
    if ( ! is_author() || is_admin() || is_feed() ) {
        return;
    }

    $home_url = home_url( '/' );

    if ( $home_url === '' ) {
        return;
    }

    wp_safe_redirect( $home_url, 301 );
    exit;
}

add_action( 'template_redirect', 'spx_redirect_author_archives' );
PHP,
    'type'        => 'php',
    'scopes'      => [ 'frontend' ],
    'priority'    => 10,
    'status'      => 'disabled',
    'notes'       => __( 'Confirm search performance after enabling because author pages will no longer be indexable.', 'snippet-press' ),
];
