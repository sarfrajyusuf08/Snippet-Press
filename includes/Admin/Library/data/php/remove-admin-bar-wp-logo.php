<?php

return [
    'slug'        => 'remove-admin-bar-wp-logo',
    'title'       => __( 'Remove Admin Bar WP Logo', 'snippet-press' ),
    'description' => __( 'Clears the WordPress logo from the admin toolbar for administrators.', 'snippet-press' ),
    'category'    => 'admin',
    'tags'        => [ 'toolbar', 'ux' ],
    'highlights'  => [
        __( 'Keeps the admin bar focused on your site tools.', 'snippet-press' ),
        __( 'Limits the removal to users who manage options.', 'snippet-press' ),
    ],
    'code'        => <<<'PHP'
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function spx_remove_wp_logo_from_admin_bar( WP_Admin_Bar $wp_admin_bar ): void {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $wp_admin_bar->remove_node( 'wp-logo' );
}

add_action( 'admin_bar_menu', 'spx_remove_wp_logo_from_admin_bar', 11 );
PHP,
    'type'        => 'php',
    'scopes'      => [ 'admin', 'frontend' ],
    'priority'    => 10,
    'status'      => 'disabled',
    'notes'       => __( 'The toolbar change applies to both front-end and dashboard views for administrators.', 'snippet-press' ),
];
