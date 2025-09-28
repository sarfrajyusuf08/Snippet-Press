<?php

namespace SnippetVault\CLI;

use WP_CLI\CommandWithUpgrade;
use WP_CLI\Utils;

/**
 * Placeholder command definitions.
 */
class Commands extends CommandWithUpgrade {
    /**
     * List available snippets.
     *
     * ## EXAMPLES
     *
     *     wp snippet-press list
     */
    public function list( array $args, array $assoc_args ): void { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
        \WP_CLI::log( 'Snippet Press CLI commands coming soon.' );
    }
}