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
        $code = ltrim( $snippet['content'] );

        if ( '' === $code ) {
            return;
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
            return;
        }

        if ( false === strpos( $code, '<?' ) ) {
            $code = "<?php\n" . $code;
        }

        $wrapper = static function () use ( $code ) {
            eval( '?>' . $code ); // phpcs:ignore Squiz.PHP.Eval.Discouraged
        };

        $wrapper();
    }
}