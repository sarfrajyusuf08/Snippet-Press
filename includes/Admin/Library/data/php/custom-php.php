<?php

return [
    'slug'        => 'custom-php-template',
    'title'       => __( 'Add Your Custom Code', 'snippet-press' ),
    'description' => __( 'Start with a blank PHP snippet wired to a safe WordPress hook.', 'snippet-press' ),
    'category'    => 'getting-started',
    'tags'        => [ 'starter', 'custom' ],
    'highlights'  => [
        __( 'Uses the init hook so your code runs after WordPress loads.', 'snippet-press' ),
        __( 'Perfect starting point for bespoke functionality.', 'snippet-press' ),
    ],
    'code'        => <<<'PHP'
/**
 * Run custom logic for your site.
 */
add_action( 'init', function () {
    // Replace this with your PHP code.
} );
PHP,
    'type'        => 'php',
    'scopes'      => [ 'universal' ],
    'priority'    => 10,
    'status'      => 'disabled',
    'notes'       => __( 'Edit the snippet after installing to paste your own logic.', 'snippet-press' ),
];
