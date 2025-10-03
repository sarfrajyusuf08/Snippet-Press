<?php

namespace SnippetPress\Admin\Pages\Add_Snippet\Support;

/**
 * Converts machine-readable slugs into human-friendly labels.
 */
class Slug_Humanizer {
    /**
     * Convert a kebab or snake case slug into title case words.
     */
    public function from_slug( string $slug ): string {
        return ucwords( str_replace( [ '-', '_' ], ' ', $slug ) );
    }
}
