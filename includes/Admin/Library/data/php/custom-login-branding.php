<?php

return [
    'slug'        => 'custom-login-branding',
    'title'       => __( 'Brand Login Logo and URL', 'snippet-press' ),
    'description' => __( 'Replaces the login screen logo, URL, and tooltip with your site branding.', 'snippet-press' ),
    'category'    => 'branding',
    'tags'        => [ 'login', 'ux' ],
    'highlights'  => [
        __( 'Points the login logo to the site homepage.', 'snippet-press' ),
        __( 'Uses the custom logo or site icon automatically.', 'snippet-press' ),
    ],
    'code'        => <<<'PHP'
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function spx_login_header_url( string $url ): string {
    return home_url( '/' );
}

function spx_login_header_title( string $title ): string {
    return wp_strip_all_tags( get_bloginfo( 'name', 'display' ) );
}

function spx_get_login_brand_image_url(): string {
    $logo_id = (int) get_theme_mod( 'custom_logo' );

    if ( $logo_id > 0 ) {
        $logo_url = wp_get_attachment_image_url( $logo_id, 'full' );

        if ( is_string( $logo_url ) && $logo_url !== '' ) {
            return $logo_url;
        }
    }

    $site_icon = get_site_icon_url( 192 );

    if ( is_string( $site_icon ) && $site_icon !== '' ) {
        return $site_icon;
    }

    return '';
}

function spx_customize_login_logo_styles(): void {
    $logo_url = spx_get_login_brand_image_url();

    if ( $logo_url === '' ) {
        return;
    }

    $css = sprintf(
        '.login h1 a{background-image:url(%1$s);background-size:contain;background-position:center;width:220px;height:90px;}body.login{background:#f0f4f8;}',
        esc_url_raw( $logo_url )
    );

    wp_enqueue_style( 'login' );
    wp_add_inline_style( 'login', $css );
}

add_filter( 'login_headerurl', 'spx_login_header_url' );
add_filter( 'login_headertext', 'spx_login_header_title' );
add_action( 'login_enqueue_scripts', 'spx_customize_login_logo_styles' );
PHP,
    'type'        => 'php',
    'scopes'      => [ 'frontend' ],
    'priority'    => 10,
    'status'      => 'disabled',
    'notes'       => __( 'Ensure a custom logo or site icon is set so the styling has an image to display.', 'snippet-press' ),
];
