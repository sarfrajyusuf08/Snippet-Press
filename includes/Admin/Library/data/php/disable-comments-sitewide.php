<?php

return [
    'slug'        => 'disable-comments-sitewide',
    'title'       => __( 'Disable Comments Site-wide', 'snippet-press' ),
    'description' => __( 'Shuts off comments and trackbacks everywhere and tidies the admin UI.', 'snippet-press' ),
    'category'    => 'governance',
    'tags'        => [ 'comments', 'moderation' ],
    'highlights'  => [
        __( 'Removes comment support from every registered post type.', 'snippet-press' ),
        __( 'Redirects legacy discussion screens back to the dashboard.', 'snippet-press' ),
    ],
    'code'        => <<<'PHP'
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function spx_disable_comments_support(): void {
    foreach ( get_post_types() as $post_type ) {
        if ( post_type_supports( $post_type, 'comments' ) ) {
            remove_post_type_support( $post_type, 'comments' );
        }

        if ( post_type_supports( $post_type, 'trackbacks' ) ) {
            remove_post_type_support( $post_type, 'trackbacks' );
        }
    }
}

function spx_disable_comments_status( bool $open, $post_id = null ): bool {
    return false;
}

function spx_disable_pings_status( bool $open, $post_id = null ): bool {
    return false;
}

function spx_disable_existing_comments( array $comments, $post_id ): array {
    return [];
}

function spx_disable_comments_admin_menu(): void {
    remove_menu_page( 'edit-comments.php' );
}

function spx_redirect_comments_screen(): void {
    if ( ! is_admin() ) {
        return;
    }

    $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

    if ( $screen && 'edit-comments' === $screen->id ) {
        wp_safe_redirect( admin_url() );
        exit;
    }
}

function spx_remove_dashboard_comments(): void {
    remove_meta_box( 'dashboard_recent_comments', 'dashboard', 'normal' );
}

add_action( 'init', 'spx_disable_comments_support' );
add_filter( 'comments_open', 'spx_disable_comments_status', 20, 2 );
add_filter( 'pings_open', 'spx_disable_pings_status', 20, 2 );
add_filter( 'comments_array', 'spx_disable_existing_comments', 20, 2 );
add_action( 'admin_menu', 'spx_disable_comments_admin_menu' );
add_action( 'current_screen', 'spx_redirect_comments_screen' );
add_action( 'wp_dashboard_setup', 'spx_remove_dashboard_comments' );
PHP,
    'type'        => 'php',
    'scopes'      => [ 'frontend', 'admin' ],
    'priority'    => 5,
    'status'      => 'disabled',
    'notes'       => __( 'If you reactivate comments later you may need to re-enable support for specific post types manually.', 'snippet-press' ),
];
