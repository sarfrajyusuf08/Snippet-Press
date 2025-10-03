<?php

namespace SnippetPress\Admin\Assets;

/**
 * Handles loading admin-side styles and scripts for plugin pages.
 */
class Admin_Assets {
    public function register(): void {
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue' ] );
    }

    public function enqueue( string $hook ): void {
        if ( false === strpos( $hook, 'sp-' ) ) {
            return;
        }

        wp_enqueue_style( 'snippet-press-admin', SNIPPET_PRESS_URL . 'assets/css/admin.css', [], SNIPPET_PRESS_VERSION );
        wp_enqueue_script( 'snippet-press-admin', SNIPPET_PRESS_URL . 'assets/js/admin.js', [ 'jquery' ], SNIPPET_PRESS_VERSION, true );
    }
}