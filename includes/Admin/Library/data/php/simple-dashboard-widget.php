<?php

return [
    'slug'        => 'simple-dashboard-widget',
    'title'       => __( 'Add Maintenance Dashboard Widget', 'snippet-press' ),
    'description' => __( 'Adds a simple dashboard reminder widget for site administrators.', 'snippet-press' ),
    'category'    => 'admin',
    'tags'        => [ 'dashboard', 'ux' ],
    'highlights'  => [
        __( 'Displays only to users who can manage options.', 'snippet-press' ),
        __( 'Provides a translatable maintenance reminder.', 'snippet-press' ),
    ],
    'code'        => <<<'PHP'
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function spx_register_simple_dashboard_widget(): void {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    wp_add_dashboard_widget(
        'spx_simple_dashboard_widget',
        __( 'Site Health Reminder', 'snippet-press' ),
        'spx_render_simple_dashboard_widget'
    );
}

function spx_render_simple_dashboard_widget(): void {
    $site_name = wp_strip_all_tags( get_bloginfo( 'name', 'display' ) );

    echo '<p>' . esc_html__( 'Review updates and backups regularly to keep the site secure.', 'snippet-press' ) . '</p>';
    echo '<p><strong>' . esc_html( $site_name ) . '</strong></p>';
}

add_action( 'wp_dashboard_setup', 'spx_register_simple_dashboard_widget' );
PHP,
    'type'        => 'php',
    'scopes'      => [ 'admin' ],
    'priority'    => 10,
    'status'      => 'disabled',
    'notes'       => __( 'Customize the widget copy to highlight the maintenance tasks your team prioritizes.', 'snippet-press' ),
];
