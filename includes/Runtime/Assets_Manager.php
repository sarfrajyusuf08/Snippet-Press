<?php

namespace SnippetVault\Runtime;

/**
 * Manages enqueuing JavaScript and CSS snippets across scopes.
 */
class Assets_Manager {
    public function enqueue_scripts( array $snippets, string $scope ): void {
        foreach ( $snippets as $snippet ) {
            if ( ! in_array( $scope, $snippet['scopes'], true ) ) {
                continue;
            }

            $handle = sprintf( 'snippet-press-js-%d', $snippet['id'] );
            wp_register_script( $handle, false, [], SNIPPET_PRESS_VERSION, true );
            wp_enqueue_script( $handle );
            wp_add_inline_script( $handle, $snippet['content'] );
        }
    }

    public function enqueue_styles( array $snippets, string $scope ): void {
        $handle = sprintf( 'snippet-press-css-%s', $scope );

        $css = '';
        foreach ( $snippets as $snippet ) {
            if ( ! in_array( $scope, $snippet['scopes'], true ) ) {
                continue;
            }

            $css .= "\n/* Snippet {$snippet['id']} */\n" . $snippet['content'];
        }

        if ( '' === trim( $css ) ) {
            return;
        }

        wp_register_style( $handle, false, [], SNIPPET_PRESS_VERSION );
        wp_enqueue_style( $handle );
        wp_add_inline_style( $handle, $css );
    }
}