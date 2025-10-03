<?php

return [
    'slug'        => 'replace-admin-footer',
    'title'       => __( 'Customize Admin Footer Text', 'snippet-press' ),
    'description' => __( 'Replaces the default admin footer copy with a branded message.', 'snippet-press' ),
    'category'    => 'admin',
    'tags'        => [ 'branding', 'ux' ],
    'highlights'  => [
        __( 'Limits the message to administrators with manage_options capability.', 'snippet-press' ),
        __( 'Uses translation and escaping helpers for safety.', 'snippet-press' ),
    ],
    'code'        => <<<'PHP'
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function spx_replace_admin_footer( string $text ): string {
    if ( ! current_user_can( 'manage_options' ) ) {
        return $text;
    }

    $site_name = wp_strip_all_tags( get_bloginfo( 'name', 'display' ) );

    return sprintf(
        esc_html__( 'Thank you for managing %s with care.', 'snippet-press' ),
        esc_html( $site_name )
    );
}

add_filter( 'admin_footer_text', 'spx_replace_admin_footer' );
PHP,
    'type'        => 'php',
    'scopes'      => [ 'admin' ],
    'priority'    => 10,
    'status'      => 'disabled',
    'notes'       => __( 'Edit the message to match your organization’s voice if desired.', 'snippet-press' ),
];
