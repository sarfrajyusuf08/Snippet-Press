<?php

namespace SnippetPress\Safety;

use SnippetPress\Admin\Notices;
use SnippetPress\Infrastructure\Capabilities;
use SnippetPress\Infrastructure\Service_Provider;
use WP_Post;

/**
 * Monitors snippet execution for fatal errors and manages safe mode state.
 */
class Safe_Mode_Manager extends Service_Provider {
    public const SAFE_MODE_OPTION = 'sp_safe_mode';
    public const SAFE_MODE_LOG_OPTION = 'sp_safe_mode_log';

    /**
     * ID of the snippet currently being executed.
     *
     * @var int
     */
    protected $last_snippet_id = 0;

    public function register(): void {
        add_action( 'init', [ $this, 'bootstrap_safe_mode' ] );
        add_action( 'admin_notices', [ $this, 'maybe_show_notice' ] );
        add_action( 'admin_post_sp_exit_safe_mode', [ $this, 'handle_exit_safe_mode' ] );
        add_action( 'admin_post_sp_enable_safe_mode', [ $this, 'handle_enable_safe_mode' ] );
    }

    /**
     * Prepare safe-mode option defaults and hook the fatal detector.
     */
    public function bootstrap_safe_mode(): void {
        $state = $this->get_state();

        if ( ! get_option( self::SAFE_MODE_OPTION ) ) {
            update_option( self::SAFE_MODE_OPTION, $state );
        }

        if ( false === get_option( self::SAFE_MODE_LOG_OPTION, false ) ) {
            add_option( self::SAFE_MODE_LOG_OPTION, [] );
        }

        add_action( 'shutdown', [ $this, 'detect_fatal_error' ] );
    }

    /**
     * Remember the snippet that is currently executing.
     */
    public function track_snippet_execution( int $snippet_id ): void {
        $this->last_snippet_id = $snippet_id;
    }

    /**
     * Clear the tracked snippet after a successful execution.
     */
    public function clear_tracked_snippet(): void {
        $this->last_snippet_id = 0;
    }

    /**
     * Return the current safe mode state with defaults applied.
     */
    protected function get_state(): array {
        $state = get_option( self::SAFE_MODE_OPTION, [] );

        return wp_parse_args(
            is_array( $state ) ? $state : [],
            [
                'enabled'    => false,
                'snippet_id' => 0,
                'error'      => '',
                'timestamp'  => 0,
            ]
        );
    }

    /**
     * Persist an updated safe mode state.
     */
    protected function update_state( array $state ): void {
        update_option( self::SAFE_MODE_OPTION, wp_parse_args( $state, $this->get_state() ) );
    }

    /**
     * Detect fatal errors triggered during shutdown.
     */
    public function detect_fatal_error(): void {
        $error = error_get_last();

        if ( null === $error || ! in_array( $error['type'], [ E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR ], true ) ) {
            $this->clear_tracked_snippet();
            return;
        }

        $error_message = isset( $error['message'] ) ? (string) $error['message'] : '';

        if ( $this->last_snippet_id ) {
            $this->enable_safe_mode( $this->last_snippet_id, $error_message );
            $this->quarantine_snippet( $this->last_snippet_id );
            $this->add_activation_notice( $this->last_snippet_id, $error_message );
            $this->record_log_entry( [
                'timestamp'   => time(),
                'trigger'     => 'fatal',
                'snippet_id'  => $this->last_snippet_id,
                'error'       => $error_message,
                'current_url' => isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( (string) $_SERVER['REQUEST_URI'] ) : '',
            ] );

        } else {
            $this->enable_safe_mode( 0, $error_message );
            $this->add_activation_notice( 0, $error_message );
            $this->record_log_entry( [
                'timestamp'   => time(),
                'trigger'     => 'fatal',
                'snippet_id'  => 0,
                'error'       => $error_message,
                'current_url' => isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( (string) $_SERVER['REQUEST_URI'] ) : '',
            ] );
        }

        $this->clear_tracked_snippet();
    }

    /**
     * Display an admin notice when safe mode is active.
     */
    public function maybe_show_notice(): void {
        $state = $this->get_state();

        if ( empty( $state['enabled'] ) ) {
            return;
        }

        $message    = __( 'Snippet Press safe mode is active. Review recent changes to re-enable snippets.', 'snippet-press' );
        $error_text = '';
        $snippet_id = isset( $state['snippet_id'] ) ? (int) $state['snippet_id'] : 0;

        if ( ! empty( $state['error'] ) ) {
            $error_text = wp_strip_all_tags( (string) $state['error'] );
        }

        if ( $snippet_id ) {
            $title = get_the_title( $snippet_id );

            if ( $title ) {
                $message = sprintf( __( 'Safe mode activated after snippet "%s" triggered a fatal error.', 'snippet-press' ), $title );
            }
        }

        $exit_url = wp_nonce_url( admin_url( 'admin-post.php?action=sp_exit_safe_mode' ), 'sp_exit_safe_mode' );
        $edit_url = $snippet_id ? get_edit_post_link( $snippet_id, 'url' ) : '';

        echo '<div class="notice notice-error">';
        echo '<p>' . esc_html( $message ) . '</p>';

        if ( $error_text ) {
            echo '<p><strong>' . esc_html__( 'Last error:', 'snippet-press' ) . '</strong> <code>' . esc_html( $error_text ) . '</code></p>';
        }

        if ( $edit_url ) {
            echo '<p><a href="' . esc_url( $edit_url ) . '" class="button button-primary">' . esc_html__( 'Review Snippet', 'snippet-press' ) . '</a></p>';
        }

        echo '<p><a href="' . esc_url( $exit_url ) . '" class="button button-secondary">' . esc_html__( 'Disable Safe Mode', 'snippet-press' ) . '</a></p>';
        echo '</div>';
    }

    /**
     * Handle requests to disable safe mode.
     */
    public function handle_exit_safe_mode(): void {
        if ( ! current_user_can( Capabilities::MANAGE ) ) {
            wp_die( esc_html__( 'You do not have permission to modify safe mode.', 'snippet-press' ), 403 );
        }

        $nonce = isset( $_REQUEST['_wpnonce'] ) ? wp_unslash( $_REQUEST['_wpnonce'] ) : '';

        if ( ! wp_verify_nonce( $nonce, 'sp_exit_safe_mode' ) ) {
            wp_die( esc_html__( 'Invalid request.', 'snippet-press' ), 403 );
        }

        $this->disable_safe_mode();
        Notices::add( __( 'Safe mode disabled. Snippets can now run normally.', 'snippet-press' ), 'success' );
        $this->record_log_entry(
            [
                'timestamp'  => time(),
                'trigger'    => 'manual_disable',
                'snippet_id' => 0,
                'message'    => __( 'Safe mode manually disabled.', 'snippet-press' ),
            ]
        );

        $redirect = isset( $_REQUEST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_REQUEST['redirect_to'] ) ) : '';
        if ( ! $redirect ) {
            $redirect = admin_url( 'admin.php?page=sp-code-snippet' );
        }

        wp_safe_redirect( $redirect );
        exit;
    }

    /**
     * Disable safe mode and reset state.
     */
    public function disable_safe_mode(): void {
        $this->update_state(
            [
                'enabled'    => false,
                'snippet_id' => 0,
                'error'      => '',
                'timestamp'  => 0,
            ]
        );
    }

    /**
     * Manually enable safe mode without quarantining a snippet.
     */
    public function handle_enable_safe_mode(): void {
        if ( ! current_user_can( Capabilities::MANAGE ) ) {
            wp_die( esc_html__( 'You do not have permission to modify safe mode.', 'snippet-press' ), 403 );
        }

        $nonce = isset( $_REQUEST['_wpnonce'] ) ? wp_unslash( $_REQUEST['_wpnonce'] ) : '';

        if ( ! wp_verify_nonce( $nonce, 'sp_enable_safe_mode' ) ) {
            wp_die( esc_html__( 'Invalid request.', 'snippet-press' ), 403 );
        }

        $this->enable_safe_mode();
        Notices::add( __( 'Safe mode enabled. Snippet execution is paused until you disable it.', 'snippet-press' ), 'warning' );
        $this->record_log_entry(
            [
                'timestamp'  => time(),
                'trigger'    => 'manual_enable',
                'snippet_id' => 0,
                'message'    => __( 'Safe mode manually enabled.', 'snippet-press' ),
            ]
        );

        $redirect = isset( $_REQUEST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_REQUEST['redirect_to'] ) ) : '';
        if ( ! $redirect ) {
            $redirect = admin_url( 'admin.php?page=sp-code-snippet' );
        }

        wp_safe_redirect( $redirect );
        exit;
    }

    /**
     * Quarantine a snippet that triggered a fatal error.
     */
    protected function quarantine_snippet( int $snippet_id ): void {
        $post = get_post( $snippet_id );

        if ( ! $post instanceof WP_Post || 'sp_snippet' !== $post->post_type ) {
            return;
        }

        update_post_meta( $snippet_id, '_sp_status', 'disabled' );
        update_post_meta( $snippet_id, '_sp_safe_mode_flag', true );
        wp_update_post(
            [
                'ID'          => $snippet_id,
                'post_status' => 'draft',
            ]
        );
    }

    /**
     * Queue a notice letting the current admin know why safe mode activated.
     */
    protected function add_activation_notice( int $snippet_id, string $error_message ): void {
        $title   = $snippet_id ? get_the_title( $snippet_id ) : '';
        $message = $title
            ? sprintf( __( 'Safe mode activated after snippet "%s" triggered a fatal error.', 'snippet-press' ), $title )
            : __( 'Safe mode activated after a snippet triggered a fatal error.', 'snippet-press' );

        if ( '' !== $error_message ) {
            $message .= ' ' . sprintf( __( 'Last error: %s', 'snippet-press' ), wp_strip_all_tags( $error_message ) );
        }

        Notices::add( $message, 'error' );
    }

    /**
     * Persist a safe mode state with optional metadata.
     */
    protected function enable_safe_mode( int $snippet_id = 0, string $error_message = '' ): void {
        $state = $this->get_state();

        $state['enabled']    = true;
        $state['snippet_id'] = $snippet_id;
        $state['error']      = $error_message;
        $state['timestamp']  = time();

        $this->update_state( $state );
    }

    /**
     * Return the safe mode event log.
     */
    public function get_log(): array {
        $log = get_option( self::SAFE_MODE_LOG_OPTION, [] );

        return is_array( $log ) ? $log : [];
    }

    /**
     * Append an entry to the safe mode activity log.
     *
     * @param array<string,mixed> $entry Log entry data.
     */
    protected function record_log_entry( array $entry ): void {
        $entry = wp_parse_args(
            $entry,
            [
                'timestamp'   => time(),
                'trigger'     => 'unknown',
                'snippet_id'  => 0,
                'error'       => '',
                'message'     => '',
                'current_url' => '',
            ]
        );

        $log = $this->get_log();

        array_unshift( $log, $entry );

        if ( count( $log ) > 20 ) {
            $log = array_slice( $log, 0, 20 );
        }

        update_option( self::SAFE_MODE_LOG_OPTION, $log );
    }
}

