<?php

return [
    'slug'        => 'hide-login-errors',
    'title'       => __( 'Hide Login Error Details', 'snippet-press' ),
    'description' => __( 'Prevent the login screen from revealing whether a username or password is incorrect.', 'snippet-press' ),
    'category'    => 'login',
    'tags'        => [ 'security' ],
    'highlights'  => [
        __( 'Improves security by avoiding hints for attackers.', 'snippet-press' ),
        __( 'Supports custom messaging using the WordPress translation system.', 'snippet-press' ),
    ],
    'code'        => <<<'PHP'
add_filter( 'login_errors', function () {
    return __( 'Something went wrong. Please double-check your details.', 'snippet-press' );
} );
PHP,
    'type'        => 'php',
    'scopes'      => [ 'login' ],
    'priority'    => 10,
    'status'      => 'disabled',
    'notes'       => __( 'Tailor the login message after installing to match your brand voice.', 'snippet-press' ),
];
