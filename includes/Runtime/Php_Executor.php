<?php

namespace SnippetPress\Runtime;

/**
 * Executes PHP snippets within an isolated scope.
 */
class Php_Executor {
    /**
     * Execute PHP code for a snippet.
     */
    public function execute( array $snippet ): void {
        $code = trim( $snippet['content'] );

        if ( '' === $code ) {
            return;
        }

        $wrapper = static function () use ( $code ) {
            eval( '?>' . $code ); // phpcs:ignore Squiz.PHP.Eval.Discouraged
        };

        $wrapper();
    }
}

