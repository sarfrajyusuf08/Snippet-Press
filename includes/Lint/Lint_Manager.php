<?php

namespace SnippetPress\Lint;

use SnippetPress\Admin\Notices;
use SnippetPress\Infrastructure\Service_Container;
use SnippetPress\Infrastructure\Service_Provider;
use SnippetPress\Infrastructure\Settings;
use SnippetPress\Post_Types\Snippet_Post_Type;
use WP_Error;
use WP_Post;

/**
 * Performs lightweight linting for snippets upon save.
 */
class Lint_Manager extends Service_Provider {
    /**
     * Settings service.
     *
     * @var Settings|null
     */
    protected $settings;

    public function __construct( Service_Container $container ) {
        parent::__construct( $container );

        $service = $container->get( Settings::class );

        if ( $service instanceof Settings ) {
            $this->settings = $service;
        }
    }

    public function register(): void {
        add_action( 'save_post_' . Snippet_Post_Type::POST_TYPE, [ $this, 'lint_snippet' ], 20, 3 );
    }

    public function lint_snippet( int $post_id, WP_Post $post, bool $update ): void { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
        if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
            return;
        }

        $type    = get_post_meta( $post_id, '_sp_type', true ) ?: 'php';
        $content = $post->post_content;

        switch ( $type ) {
            case 'php':
                $this->run_php_lint( $post_id, $content );
                break;
            case 'js':
            case 'css':
                $this->run_static_lint( $post_id, $content, $type );
                break;
        }
    }

    protected function run_php_lint( int $post_id, string $content ): void {
        $prepared = $this->prepare_php_snippet( $content );

        if ( '' === $prepared ) {
            $this->clear_lint_error( $post_id );
            return;
        }

        $binary = $this->php_binary();

        if ( '' === $binary ) {
            $this->store_lint_error(
                $post_id,
                __( 'PHP lint skipped: no PHP binary configured.', 'snippet-press' ),
                'warning',
                'php'
            );
            return;
        }

        $temp_file = wp_tempnam( 'snippet-press-' . $post_id . '.php' );

        if ( ! $temp_file || ! file_put_contents( $temp_file, $prepared ) ) {
            $this->store_lint_error(
                $post_id,
                __( 'PHP lint skipped: unable to create a temporary file.', 'snippet-press' ),
                'warning',
                'php'
            );
            return;
        }

        $binary_escaped = escapeshellcmd( $binary );

        if ( false !== strpos( $binary, ' ' ) ) {
            $binary_escaped = '"' . str_replace( '"', '\"', $binary_escaped ) . '"';
        }

        $command = $binary_escaped . ' -l ' . escapeshellarg( $temp_file );
        $result  = $this->execute_command( $command );

        if ( file_exists( $temp_file ) ) {
            unlink( $temp_file ); // @codingStandardsIgnoreLine
        }

        if ( is_wp_error( $result ) ) {
            $this->store_lint_error( $post_id, $result->get_error_message(), 'warning', 'php' );
            return;
        }

        [ $exit_code, $stdout, $stderr ] = $result;

        if ( 0 === $exit_code ) {
            $this->clear_lint_error( $post_id );
            do_action( 'snippet_press/php_lint_executed', $post_id, $content, true );
            return;
        }

        $message = trim( $stderr ?: $stdout );

        if ( '' === $message ) {
            $message = __( 'Unknown linting error.', 'snippet-press' );
        }

        $this->store_lint_error( $post_id, $message, 'error', 'php' );
        do_action( 'snippet_press/php_lint_executed', $post_id, $content, false );
    }

    protected function run_static_lint( int $post_id, string $content, string $type ): void {
        $trimmed = trim( $content );

        if ( '' === $trimmed ) {
            $this->clear_lint_error( $post_id );
            do_action( 'snippet_press/static_lint_executed', $post_id, $type, $content, true );
            return;
        }

        if ( false === wp_check_invalid_utf8( $content, true ) ) {
            $message = sprintf(
                /* translators: %s is the snippet type label. */
                __( '%s snippet contains invalid UTF-8 characters.', 'snippet-press' ),
                strtoupper( $type )
            );
            $this->store_lint_error( $post_id, $message, 'warning', $type );
            do_action( 'snippet_press/static_lint_executed', $post_id, $type, $content, false );
            return;
        }

        if ( 'js' === $type && false !== stripos( $content, '<?php' ) ) {
            $message = __( 'JavaScript snippet contains PHP tags. Remove PHP code from JavaScript snippets.', 'snippet-press' );
            $this->store_lint_error( $post_id, $message, 'warning', $type );
            do_action( 'snippet_press/static_lint_executed', $post_id, $type, $content, false );
            return;
        }

        $this->clear_lint_error( $post_id );
        do_action( 'snippet_press/static_lint_executed', $post_id, $type, $content, true );
    }

    protected function prepare_php_snippet( string $content ): string {
        $code = html_entity_decode( $content, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
        $code = str_replace( [ "\r\n", "\r" ], "\n", $code );
        $code = (string) preg_replace( '/<br\s*\/?>/i', "\n", $code );
        $code = trim( $code );

        if ( '' === $code ) {
            return '';
        }

        if ( false === strpos( $code, '<?' ) ) {
            $code = "<?php\n" . $code;
        }

        return $code;
    }

    protected function php_binary(): string {
        if ( $this->settings instanceof Settings ) {
            $all = $this->settings->all();
            if ( ! empty( $all['php_binary_path'] ) ) {
                return (string) $all['php_binary_path'];
            }
        }

        return 'php';
    }

    /**
     * Execute a shell command and return the exit code and output.
     *
     * @return array<int,mixed>|WP_Error
     */
    protected function execute_command( string $command ) {
        if ( function_exists( 'proc_open' ) ) {
            $descriptor = [
                1 => [ 'pipe', 'w' ],
                2 => [ 'pipe', 'w' ],
            ];

            $process = proc_open( $command, $descriptor, $pipes );

            if ( ! is_resource( $process ) ) {
                return new WP_Error( 'snippet_press_lint_proc', __( 'Unable to start lint process.', 'snippet-press' ) );
            }

            $stdout = stream_get_contents( $pipes[1] );
            fclose( $pipes[1] );

            $stderr = stream_get_contents( $pipes[2] );
            fclose( $pipes[2] );

            $exit_code = proc_close( $process );

            return [ (int) $exit_code, (string) $stdout, (string) $stderr ];
        }

        if ( function_exists( 'exec' ) ) {
            $output    = [];
            $exit_code = 0;
            exec( $command . ' 2>&1', $output, $exit_code );

            $captured = implode( "\n", $output );

            return [ (int) $exit_code, '', $captured ];
        }

        return new WP_Error( 'snippet_press_lint_disabled', __( 'Unable to run lint command: shell execution is disabled.', 'snippet-press' ) );
    }

    protected function store_lint_error( int $post_id, string $message, string $level, string $type ): void {
        update_post_meta( $post_id, '_sp_lint_error', $message );

        $label = get_the_title( $post_id );

        if ( ! $label ) {
            $label = sprintf( __( 'Snippet #%d', 'snippet-press' ), $post_id );
        }

        $notice = sprintf(
            /* translators: 1: Snippet type. 2: Snippet label. 3: Lint message. */
            __( '%1$s lint check for "%2$s": %3$s', 'snippet-press' ),
            strtoupper( $type ),
            $label,
            $message
        );

        Notices::add( $notice, $level );
    }

    protected function clear_lint_error( int $post_id ): void {
        delete_post_meta( $post_id, '_sp_lint_error' );
    }
}
