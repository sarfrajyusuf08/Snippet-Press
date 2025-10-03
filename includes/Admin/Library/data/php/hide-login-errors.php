<?php

return [
    'slug'        => 'hide-login-error-details',
    'title'       => __( 'Hide Login Error Details', 'snippet-press' ),
    'description' => __( 'Replaces detailed login errors with a hardened, generic message.', 'snippet-press' ),
    'category'    => 'security',
    'tags'        => [ 'login', 'hardening' ],
    'highlights'  => [
        __( 'Stops attackers from learning if a username exists.', 'snippet-press' ),
        __( 'Provides a translatable message for all failed attempts.', 'snippet-press' ),
    ],
    'code'        => <<<'PHP'
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function spx_hide_login_errors(): string {
    return __( 'Invalid login credentials. Please try again.', 'snippet-press' );
}

add_filter( 'login_errors', 'spx_hide_login_errors' );
PHP,
    'type'        => 'php',
    'scopes'      => [ 'frontend' ],
    'priority'    => 10,
    'status'      => 'disabled',
    'notes'       => __( 'Adjust the wording if you want the message to include brand tone or additional help.', 'snippet-press' ),
];
