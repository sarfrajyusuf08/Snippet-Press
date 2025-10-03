<?php

namespace SnippetPress\Infrastructure;

use WP_Role;

/**
 * Registers custom capabilities for Snippet Press.
 */
class Capabilities extends Service_Provider {
    public const MANAGE = 'manage_snippet_press';
    public const EDIT = 'edit_snippet_press';
    public const EXECUTE = 'execute_snippet_press';
    public const BACKUP = 'backup_snippet_press';
    public const ADMIN = 'admin_snippet_press';

    /**
     * List of core capabilities exposed by the plugin.
     *
     * @var string[]
     */
    private $capabilities = [
        self::MANAGE,
        self::EDIT,
        self::EXECUTE,
        self::BACKUP,
        self::ADMIN,
    ];

    public function register(): void {
        add_action( 'init', [ $this, 'ensure_caps' ] );
    }

    public function activate(): void {
        $this->ensure_caps();
    }

    /**
     * Assign all capabilities to administrators by default.
     */
    public function ensure_caps(): void {
        $role = get_role( 'administrator' );

        if ( ! $role instanceof WP_Role ) {
            return;
        }

        foreach ( $this->capabilities as $capability ) {
            if ( ! $role->has_cap( $capability ) ) {
                $role->add_cap( $capability );
            }
        }
    }
}

