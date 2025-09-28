<?php

namespace SnippetVault\Runtime;

use RuntimeException;
use SnippetVault\Infrastructure\Service_Container;
use SnippetVault\Infrastructure\Service_Provider;
use SnippetVault\Infrastructure\Settings;

/**
 * Coordinates snippet execution across scopes.
 */
class Runtime_Manager extends Service_Provider {
    protected $repository;
    protected $php_executor;
    protected $assets_manager;
    protected $conditions;
    protected $settings;

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
    }

    public function execute_php_snippets(): void {
        $snippets = $this->repository->get_snippets_by_type( 'php' );

        foreach ( $snippets as $snippet ) {
            if ( ! $this->conditions->matches( $snippet ) ) {
                continue;
            }

            if ( ! $this->scope_allows( $snippet ) ) {
                continue;
            }

            $this->php_executor->execute( $snippet );
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

    protected function enqueue_assets_for_scope( string $scope ): void {
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
}