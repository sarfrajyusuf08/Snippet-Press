<?php

namespace SnippetVault\Runtime;

/**
 * Evaluates snippet conditions against current request.
 */
class Conditions_Evaluator {
    public function matches( array $snippet ): bool {
        $conditions = $snippet['conditions'];

        if ( empty( $conditions ) || empty( $conditions['mode'] ) || 'all' === $conditions['mode'] ) {
            return true;
        }

        switch ( $conditions['mode'] ) {
            case 'singular':
                return is_singular( $conditions['post_types'] ?? null );
            case 'archive':
                return is_archive();
            case 'home':
                return is_front_page() || is_home();
            default:
                return true;
        }
    }
}