<?php

return [
    'slug'        => 'disable-admin-file-editing',
    'title'       => __( 'Disable Admin File Editing', 'snippet-press' ),
    'description' => __( 'Prevents direct theme and plugin file edits from the WordPress dashboard.', 'snippet-press' ),
    'category'    => 'security',
    'tags'        => [ 'hardening', 'admin' ],
    'highlights'  => [
        __( 'Defines DISALLOW_FILE_EDIT to block core editors.', 'snippet-press' ),
        __( 'Reduces the chance of accidental or malicious code edits.', 'snippet-press' ),
    ],
    'code'        => <<<'PHP'
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! defined( 'DISALLOW_FILE_EDIT' ) ) {
    define( 'DISALLOW_FILE_EDIT', true );
}
PHP,
    'type'        => 'php',
    'scopes'      => [ 'admin' ],
    'priority'    => 1,
    'status'      => 'disabled',
    'notes'       => __( 'If wp-config.php already sets this constant the snippet will be a no-op.', 'snippet-press' ),
];
