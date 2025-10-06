<?php

namespace SnippetPress\Runtime;

/**
 * Manages enqueuing JavaScript and CSS snippets across scopes.
 */
class Assets_Manager {
    public function enqueue_scripts( array $snippets, string $scope ): void {
        foreach ( $snippets as $snippet ) {
            if ( ! in_array( $scope, $snippet['scopes'], true ) ) {
                continue;
            }

            $handle  = sprintf( 'snippet-press-js-%d', $snippet['id'] );
            $content = $this->prepare_js_content( $snippet['content'] );

            if ( '' === $content ) {
                continue;
            }

            wp_register_script( $handle, false, [], SNIPPET_PRESS_VERSION, true );
            wp_enqueue_script( $handle );
            wp_add_inline_script( $handle, $content );
        }
    }

    /**
     * Normalize snippet content for inline JavaScript output.
     */
    private function prepare_js_content( string $content ): string {
        $trimmed = trim( $content );

        if ( '' === $trimmed ) {
            return '';
        }

        if ( 0 === stripos( $trimmed, '<script' ) ) {
            $matches = [];
            preg_match_all( '#<script\\b[^>]*>(.*?)</script>#is', $trimmed, $matches );

            if ( ! empty( $matches[1] ) ) {
                $fragments = array_map(
                    static function ( string $fragment ): string {
                        return trim( $fragment );
                    },
                    $matches[1]
                );

                $trimmed = implode(
                    "\n",
                    array_filter(
                        $fragments,
                        static function ( string $fragment ): bool {
                            return '' !== $fragment;
                        }
                    )
                );
            } else {
                $trimmed = (string) preg_replace( '#<script\\b[^>]*>|</script>#i', '', $trimmed );
            }
        }

        return trim( $trimmed );
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