<?php

return [
    'slug'        => 'force-classic-editor',
    'title'       => __( 'Force Classic Editor', 'snippet-press' ),
    'description' => __( 'Disables the block editor and keeps the classic editor active.', 'snippet-press' ),
    'category'    => 'editor',
    'tags'        => [ 'editorial', 'workflows' ],
    'highlights'  => [
        __( 'Disables Gutenberg for all post types if present.', 'snippet-press' ),
        __( 'Keeps widget and post editing experiences consistent.', 'snippet-press' ),
    ],
    'code'        => <<<'PHP'
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function spx_disable_block_editor_for_post_type( bool $use_block_editor, string $post_type ): bool {
    return false;
}

function spx_disable_block_editor_for_post( bool $use_block_editor, $post ): bool {
    return false;
}

function spx_disable_gutenberg_post_type( bool $can_edit, string $post_type ): bool {
    return false;
}

function spx_disable_widgets_block_editor(): bool {
    return false;
}

add_filter( 'use_block_editor_for_post_type', 'spx_disable_block_editor_for_post_type', 100, 2 );
add_filter( 'use_block_editor_for_post', 'spx_disable_block_editor_for_post', 100, 2 );
add_filter( 'gutenberg_can_edit_post_type', 'spx_disable_gutenberg_post_type', 100, 2 );
add_filter( 'use_widgets_block_editor', 'spx_disable_widgets_block_editor' );
PHP,
    'type'        => 'php',
    'scopes'      => [ 'admin' ],
    'priority'    => 10,
    'status'      => 'disabled',
    'notes'       => __( 'Install the Classic Editor plugin if you also need its additional settings.', 'snippet-press' ),
];
