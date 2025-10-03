<?php

return [
    'slug'        => 'custom-admin-footer',
    'title'       => __( 'Change Admin Footer Text', 'snippet-press' ),
    'description' => __( 'Replace the default "Thank you" text in the WordPress admin footer.', 'snippet-press' ),
    'category'    => 'admin',
    'tags'        => [ 'admin', 'branding' ],
    'highlights'  => [
        __( 'Adds your site name to the footer credits.', 'snippet-press' ),
        __( 'Keeps the WordPress dashboard on-brand for your team.', 'snippet-press' ),
    ],
    'code'        => <<<'PHP'
add_filter( 'admin_footer_text', function () {
    return sprintf(
        /* translators: %s: Site name. */
        __( 'Maintained by %s with Snippet Press.', 'snippet-press' ),
        get_bloginfo( 'name' )
    );
} );
PHP,
    'type'        => 'php',
    'scopes'      => [ 'admin' ],
    'priority'    => 10,
    'status'      => 'disabled',
    'notes'       => __( 'Update the footer message after installing if you prefer a custom phrase.', 'snippet-press' ),
];
