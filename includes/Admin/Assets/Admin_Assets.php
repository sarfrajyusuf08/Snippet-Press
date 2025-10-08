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
        $page     = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
        $hook_hit = strpos( $hook, 'sp-' ) !== false || strpos( $hook, 'snippetpress_page_' ) !== false;
        $page_hit = '' !== $page && strpos( $page, 'sp-' ) === 0;
        $screen   = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
        $post_hit = $screen && isset( $screen->post_type ) && 'sp_snippet' === $screen->post_type;

        if ( ! $hook_hit && ! $page_hit && ! $post_hit ) {
            return;
        }

        $style_path   = SNIPPET_PRESS_DIR . 'assets/css/admin.css';
        $partials_dir = SNIPPET_PRESS_DIR . 'assets/css/admin';
        $script_path  = SNIPPET_PRESS_DIR . 'assets/js/admin.js';

        $style_files = [ $style_path ];

        if ( is_dir( $partials_dir ) ) {
            $partial_files = glob( trailingslashit( $partials_dir ) . '*.css' );

            if ( is_array( $partial_files ) && ! empty( $partial_files ) ) {
                $style_files = array_merge( $style_files, $partial_files );
            }
        }

        $style_mtime = 0;

        foreach ( $style_files as $file ) {
            if ( file_exists( $file ) ) {
                $mtime = (int) filemtime( $file );

                if ( $mtime > $style_mtime ) {
                    $style_mtime = $mtime;
                }
            }
        }

        $style_version  = $style_mtime ? (string) $style_mtime : SNIPPET_PRESS_VERSION;
        $script_version = file_exists( $script_path ) ? (string) filemtime( $script_path ) : SNIPPET_PRESS_VERSION;

        wp_enqueue_style( 'snippet-press-admin', SNIPPET_PRESS_URL . 'assets/css/admin.css', [], $style_version );
        wp_enqueue_script( 'snippet-press-admin', SNIPPET_PRESS_URL . 'assets/js/admin.js', [ 'jquery' ], $script_version, true );

        wp_localize_script(
            'snippet-press-admin',
            'snippetPressLibrary',
            [
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                'nonce'   => wp_create_nonce( 'sp_use_library_snippet' ),
                'strings' => [
                    'error'   => __( 'We could not create that snippet. Please try again.', 'snippet-press' ),
                    'loading' => __( 'Creating...', 'snippet-press' ),
                ],
            ]
        );
    }
}
