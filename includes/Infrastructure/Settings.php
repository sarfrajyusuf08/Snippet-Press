<?php

namespace SnippetVault\Infrastructure;

/**
 * Handles plugin settings storage and defaults.
 */
class Settings extends Service_Provider {
    /**
     * Option key for plugin settings.
     */
    public const OPTION_KEY = 'sp_settings';

    /**
     * Cached settings.
     *
     * @var array
     */
    protected $settings = [];

    /**
     * Register hooks.
     */
    public function register(): void {
        add_action( 'init', [ $this, 'register_settings' ] );
    }

    /**
     * Ensure settings option exists with defaults.
     */
    public function register_settings(): void {
        $defaults = $this->default_settings();
        $stored   = get_option( self::OPTION_KEY, [] );

        $this->settings = wp_parse_args( $stored, $defaults );

        if ( empty( $stored ) ) {
            update_option( self::OPTION_KEY, $this->settings );
        }
    }

    /**
     * Retrieve current settings array.
     */
    public function all(): array {
        if ( empty( $this->settings ) ) {
            $this->settings = wp_parse_args( get_option( self::OPTION_KEY, [] ), $this->default_settings() );
        }

        return $this->settings;
    }

    /**
     * Persist settings.
     */
    public function save( array $settings ): void {
        $this->settings = wp_parse_args( $settings, $this->default_settings() );
        update_option( self::OPTION_KEY, $this->settings );
    }

    /**
     * Provide default settings.
     */
    protected function default_settings(): array {
        return [
            'default_scopes'          => [ 'frontend' ],
            'default_status'          => 'disabled',
            'php_snippet_size_limit'  => 20480,
            'js_snippet_size_limit'   => 40960,
            'css_snippet_size_limit'  => 20480,
            'safe_mode_enabled'       => true,
            'safe_mode_last_snippet'  => 0,
            'lint_php'                => true,
            'lint_js'                 => true,
            'lint_css'                => true,
            'php_binary_path'         => 'php',
            'logging_enabled'         => false,
        ];
    }
}