<?php

namespace SnippetVault\Safety;

use SnippetVault\Infrastructure\Service_Provider;
use SnippetVault\Infrastructure\Settings;

/**
 * Monitors snippet execution for fatal errors and manages safe mode state.
 */
class Safe_Mode_Manager extends Service_Provider {
    public const SAFE_MODE_OPTION = 'sp_safe_mode';

    protected $settings;

    public function register(): void {
        $settings = $this->container->get( Settings::class );

        if ( $settings instanceof Settings ) {
            $this->settings = $settings;
        }

        add_action( 'init', [ $this, 'bootstrap_safe_mode' ] );
        add_action( 'admin_notices', [ $this, 'maybe_show_notice' ] );
    }

    public function bootstrap_safe_mode(): void {
        if ( ! get_option( self::SAFE_MODE_OPTION ) ) {
            update_option( self::SAFE_MODE_OPTION, [
                'enabled'     => false,
                'snippet_id'  => 0,
                'error'       => '',
                'timestamp'   => 0,
            ] );
        }

        add_action( 'shutdown', [ $this, 'detect_fatal_error' ] );
    }

    public function detect_fatal_error(): void {
        $error = error_get_last();

        if ( null === $error || ! in_array( $error['type'], [ E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR ], true ) ) {
            return;
        }

        $state = get_option( self::SAFE_MODE_OPTION, [] );
        $state['enabled']   = true;
        $state['error']     = $error['message'] ?? '';
        $state['timestamp'] = time();

        update_option( self::SAFE_MODE_OPTION, $state );
    }

    public function maybe_show_notice(): void {
        $state = get_option( self::SAFE_MODE_OPTION, [] );

        if ( empty( $state['enabled'] ) ) {
            return;
        }

        echo '<div class="notice notice-error"><p>' . esc_html__( 'Snippet Press safe mode is active. Review recent changes to re-enable snippets.', 'snippet-press' ) . '</p></div>';
    }

    public function disable_safe_mode(): void {
        update_option( self::SAFE_MODE_OPTION, [
            'enabled'     => false,
            'snippet_id'  => 0,
            'error'       => '',
            'timestamp'   => 0,
        ] );
    }
}