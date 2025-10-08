<?php

namespace SnippetPress\Runtime;

/**
 * Executes PHP snippets within an isolated scope.
 */
class Php_Executor {
    /**
     * Execute PHP code for a snippet.
     *
     * @return float|null Execution duration in milliseconds when $capture_timing is true.
     */
    public function execute( array $snippet, bool $capture_timing = false ): ?float {
        $code = ltrim( $snippet['content'] );

        if ( '' === $code ) {
            return $capture_timing ? 0.0 : null;
        }

        $code = html_entity_decode( $code, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
        $code = str_replace( [ "\r\n", "\r" ], "\n", $code );
        $code = str_replace( [ "`n", "`r", "`t" ], [ "\n", '', '' ], $code );
        $code = str_replace( [ '\n', '\r', '\t' ], [ "\n", '', '' ], $code );
        $code = preg_replace( '/<!--\?php\s*/i', '<?php ', $code );
        $code = preg_replace( '/\?-->/i', '?>', $code );
        $code = preg_replace( '/<br\s*\/?>\s*/i', "\n", $code );
        $code = trim( $code );

        if ( '' === $code ) {
            return $capture_timing ? 0.0 : null;
        }

        if ( false === strpos( $code, '<?' ) ) {
            $code = "<?php\n" . $code;
        }

        $wrapper = static function () use ( $code ) {
            eval( '?>' . $code ); // phpcs:ignore Squiz.PHP.Eval.Discouraged
        };

        if ( ! $capture_timing ) {
            $wrapper();
            return null;
        }

        $start    = microtime( true );
        $duration = 0.0;

        try {
            $wrapper();
        } finally {
            $duration = ( microtime( true ) - $start ) * 1000;
        }

        return max( 0.0, $duration );
    }
}
