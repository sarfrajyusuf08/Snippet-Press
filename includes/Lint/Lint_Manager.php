<?php

namespace SnippetPress\Lint;

use SnippetPress\Infrastructure\Service_Provider;
use SnippetPress\Post_Types\Snippet_Post_Type;

/**
 * Performs lightweight linting for snippets upon save.
 */
class Lint_Manager extends Service_Provider {
    public function register(): void {
        add_action( 'save_post_' . Snippet_Post_Type::POST_TYPE, [ $this, 'lint_snippet' ], 20, 3 );
    }

    public function lint_snippet( int $post_id, \WP_Post $post, bool $update ): void {
        if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
            return;
        }

        $type    = get_post_meta( $post_id, '_sp_type', true ) ?: 'php';
        $content = $post->post_content;

        switch ( $type ) {
            case 'php':
                $this->run_php_lint( $post_id, $content );
                break;
            case 'js':
            case 'css':
                $this->run_static_lint( $post_id, $content, $type );
                break;
        }
    }

    protected function run_php_lint( int $post_id, string $content ): void {
        // Placeholder: in future call `php -l` via proc.
        do_action( 'snippet_press/php_lint_executed', $post_id, $content );
    }

    protected function run_static_lint( int $post_id, string $content, string $type ): void {
        // Placeholder for JS/CSS linting logic.
        do_action( 'snippet_press/static_lint_executed', $post_id, $type, $content );
    }
}

