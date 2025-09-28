<?php

namespace SnippetVault\CLI;

use SnippetVault\Infrastructure\Service_Provider;

/**
 * Registers WP-CLI commands.
 */
class CLI_Manager extends Service_Provider {
    public function register(): void {
        if ( defined( 'WP_CLI' ) && WP_CLI ) {
            \WP_CLI::add_command( 'snippet-press', Commands::class );
        }
    }
}