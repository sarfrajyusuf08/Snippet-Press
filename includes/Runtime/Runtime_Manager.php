<?php

namespace SnippetPress\Runtime;

use RuntimeException;
use SnippetPress\Infrastructure\Service_Container;
use SnippetPress\Infrastructure\Service_Provider;
use SnippetPress\Infrastructure\Settings;
use SnippetPress\Safety\Safe_Mode_Manager;

/**
 * Coordinates snippet execution across scopes.
 */
class Runtime_Manager extends Service_Provider {
    protected $repository;
    protected $php_executor;
    protected $assets_manager;
    protected $conditions;
    protected $settings;
    protected $body_injection_printed = false;

    public function __construct( Service_Container $container ) {
        parent::__construct( $container );

        $settings_service = $container->get( Settings::class );

        if ( ! $settings_service instanceof Settings ) {
            throw new RuntimeException( 'Snippet Press settings service not initialized.' );
        }

        $this->settings       = $settings_service;
        $this->repository     = new Snippet_Repository( $this->settings );
        $this->php_executor   = new Php_Executor();
        $this->assets_manager = new Assets_Manager();
        $this->conditions     = new Conditions_Evaluator();
    }

    public function register(): void {
        add_action( 'plugins_loaded', [ $this, 'execute_php_snippets' ], 100 );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_frontend_assets' ], 5 );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ], 5 );
        add_action( 'login_enqueue_scripts', [ $this, 'enqueue_login_assets' ], 5 );
        add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_editor_assets' ], 5 );
        add_action( 'wp_head', [ $this, 'output_header_injection' ], 1 );
        add_action( 'wp_footer', [ $this, 'execute_inline_snippets' ], 99 );


        if ( function_exists( 'wp_body_open' ) ) {
            add_action( 'wp_body_open', [ $this, 'output_body_injection' ], 5 );
        }

        add_action( 'wp_footer', [ $this, 'output_body_injection_fallback' ], 1 );
        add_action( 'wp_footer', [ $this, 'output_footer_injection' ], 100 );
    }

    public function execute_php_snippets(): void {
        if ( $this->is_safe_mode_active() ) {
            return;
        }

        $snippets = $this->repository->get_snippets_by_type( 'php' );

        foreach ( $snippets as $snippet ) {
            if ( ! $this->conditions->matches( $snippet ) ) {
                continue;
            }

            if ( ! $this->scope_allows( $snippet ) ) {
                continue;
            }

            $safe_mode = $this->safe_mode_manager();

            if ( $safe_mode ) {
                $safe_mode->track_snippet_execution( (int) $snippet['id'] );
            }

            $this->php_executor->execute( $snippet );

            if ( $safe_mode ) {
                $safe_mode->clear_tracked_snippet();
            }
        }
    }

    public function enqueue_frontend_assets(): void {
        $this->enqueue_assets_for_scope( 'frontend' );
    }

    public function enqueue_admin_assets(): void {
        $this->enqueue_assets_for_scope( 'admin' );
    }

    public function enqueue_login_assets(): void {
        $this->enqueue_assets_for_scope( 'login' );
    }

    public function enqueue_editor_assets(): void {
        $this->enqueue_assets_for_scope( 'editor' );
    }


    public function output_header_injection(): void {
        $this->emit_injection( 'inject_header' );
    }

    public function output_body_injection(): void {
        if ( $this->emit_injection( 'inject_body' ) ) {
            $this->body_injection_printed = true;
        }
    }

    public function output_body_injection_fallback(): void {
        if ( $this->body_injection_printed ) {
            return;
        }

        if ( $this->emit_injection( 'inject_body' ) ) {
            $this->body_injection_printed = true;
        }
    }

    public function output_footer_injection(): void {
        $this->emit_injection( 'inject_footer' );
    }

    protected function emit_injection( string $key ): bool {
        if ( $this->is_safe_mode_active() ) {
            return false;
        }

        $settings = $this->settings->all();
        $code     = isset( $settings[ $key ] ) ? (string) $settings[ $key ] : '';

        if ( trim( $code ) === '' ) {
            return false;
        }

        echo $code; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

        return true;
    }
    protected function enqueue_assets_for_scope( string $scope ): void {
        if ( $this->is_safe_mode_active() ) {
            return;
        }

        $js_snippets  = $this->repository->get_snippets_by_type( 'js' );
        $css_snippets = $this->repository->get_snippets_by_type( 'css' );

        $filtered_js = array_filter(
            $js_snippets,
            function ( array $snippet ) use ( $scope ) {
                return in_array( $scope, $snippet['scopes'], true ) && $this->conditions->matches( $snippet );
            }
        );

        $filtered_css = array_filter(
            $css_snippets,
            function ( array $snippet ) use ( $scope ) {
                return in_array( $scope, $snippet['scopes'], true ) && $this->conditions->matches( $snippet );
            }
        );

        if ( ! empty( $filtered_js ) ) {
            $this->assets_manager->enqueue_scripts( $filtered_js, $scope );
        }

        if ( ! empty( $filtered_css ) ) {
            $this->assets_manager->enqueue_styles( $filtered_css, $scope );
        }
    }

    protected function scope_allows( array $snippet ): bool {
        $scopes     = $snippet['scopes'];
        $doing_ajax = function_exists( 'wp_doing_ajax' ) ? wp_doing_ajax() : ( defined( 'DOING_AJAX' ) && DOING_AJAX );

        if ( in_array( 'universal', $scopes, true ) ) {
            return true;
        }

        if ( in_array( 'frontend', $scopes, true ) && ! is_admin() && ! $doing_ajax && ! $this->is_rest_request() ) {
            return true;
        }

        if ( in_array( 'admin', $scopes, true ) && is_admin() && ! $doing_ajax ) {
            return true;
        }

        if ( in_array( 'login', $scopes, true ) && 'login_enqueue_scripts' === current_action() ) {
            return true;
        }

        if ( in_array( 'rest', $scopes, true ) && $this->is_rest_request() ) {
            return true;
        }

        if ( in_array( 'editor', $scopes, true ) && 'enqueue_block_editor_assets' === current_action() ) {
            return true;
        }

        return false;
    }

    protected function is_rest_request(): bool {
        return defined( 'REST_REQUEST' ) && REST_REQUEST;
    }

    /**
     * Retrieve the safe mode manager from the service container.
     */
    protected function safe_mode_manager(): ?Safe_Mode_Manager {
        $service = $this->container->get( Safe_Mode_Manager::class );

        return $service instanceof Safe_Mode_Manager ? $service : null;
    }

    /**
     * Determine whether safe mode is active and should prevent execution.
     */
    protected function is_safe_mode_active(): bool {
        $settings = $this->settings->all();

        if ( isset( $settings['safe_mode_enabled'] ) && ! $settings['safe_mode_enabled'] ) {
            return false;
        }

        $state = get_option( Safe_Mode_Manager::SAFE_MODE_OPTION, [] );

        return is_array( $state ) && ! empty( $state['enabled'] );
    }

    /**
     * Execute inline snippets for JS and CSS types safely.
     * This ensures non-PHP snippets are not sent through Php_Executor.
     */
    public function execute_inline_snippets(): void {
        if ( $this->is_safe_mode_active() ) {
            return;
        }

        // Get enabled JS and CSS snippets
        $js_snippets  = $this->repository->get_snippets_by_type( 'js' );
        $css_snippets = $this->repository->get_snippets_by_type( 'css' );

        // Output JS snippets
        foreach ( $js_snippets as $snippet ) {
            if ( ! $this->conditions->matches( $snippet ) || ! $this->scope_allows( $snippet ) ) {
                continue;
            }

            $content = $this->prepare_inline_content( $snippet['content'] );

            if ( '' === $content ) {
                continue;
            }

            $slug = $snippet['slug'] ?? 'snippet-' . $snippet['id'];

            echo '<script id="snippet-press-js-' . esc_attr( $slug ) . '-after">';
            echo $content;
            echo '</script>';
        }

        // Output CSS snippets
        foreach ( $css_snippets as $snippet ) {
            if ( ! $this->conditions->matches( $snippet ) || ! $this->scope_allows( $snippet ) ) {
                continue;
            }

            $content = $this->prepare_inline_content( $snippet['content'] );

            if ( '' === $content ) {
                continue;
            }

            $slug = $snippet['slug'] ?? 'snippet-' . $snippet['id'];

            echo '<style id="snippet-press-css-' . esc_attr( $slug ) . '-after">';
            echo $content;
            echo '</style>';
        }
    }

    /**
     * Clean inline snippet content prior to output.
     */
    private function prepare_inline_content( string $content ): string {
        $content = html_entity_decode( $content, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
        $content = (string) preg_replace( '/<br\s*\/?>/i', "\n", $content );
        $content = (string) preg_replace( '/<\?php\s*/i', '', $content );
        $content = (string) preg_replace( '/\?>/i', '', $content );

        return trim( $content );
    }
}




