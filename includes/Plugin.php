<?php

namespace SnippetPress;

use SnippetPress\Infrastructure\Service_Container;
use SnippetPress\Infrastructure\Service_Provider;

/**
 * Core plugin bootstrap.
 */
class Plugin {
    /**
     * Singleton instance.
     *
     * @var Plugin|null
     */
    private static $instance = null;

    /**
     * Service container instance.
     *
     * @var Service_Container
     */
    private $container;

    /**
     * Retrieve the singleton instance.
     */
    public static function instance(): Plugin {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Plugin constructor.
     */
    private function __construct() {
        $this->container = new Service_Container();
    }

    /**
     * Boot the plugin by registering services and loading text domain.
     */
    public function boot(): void {
        $this->load_text_domain();
        $this->register_services();
        $this->container->boot();
    }

    /**
     * Activation hook handler.
     */
    public static function activate( bool $network_wide = false ): void { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
        $instance = self::instance();
        $instance->boot();

        /** @var Service_Provider $provider */
        foreach ( $instance->container->all() as $provider ) {
            if ( method_exists( $provider, 'activate' ) ) {
                $provider->activate();
            }
        }
    }

    /**
     * Deactivation hook handler.
     */
    public static function deactivate( bool $network_wide = false ): void { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
        $instance = self::instance();

        /** @var Service_Provider $provider */
        foreach ( $instance->container->all() as $provider ) {
            if ( method_exists( $provider, 'deactivate' ) ) {
                $provider->deactivate();
            }
        }
    }

    /**
     * Register all plugin services with the container.
     */
    protected function register_services(): void {
        $services = [
            Infrastructure\Capabilities::class,
            Infrastructure\Settings::class,
            Post_Types\Snippet_Post_Type::class,
            Admin\Admin_Manager::class,
            Runtime\Runtime_Manager::class,
            Safety\Safe_Mode_Manager::class,
            Lint\Lint_Manager::class,
            CLI\CLI_Manager::class,
        ];

        foreach ( $services as $service_class ) {
            if ( class_exists( $service_class ) ) {
                $this->container->register( new $service_class( $this->container ) );
            }
        }
    }

    /**
     * Load plugin translations.
     */
    protected function load_text_domain(): void {
        load_plugin_textdomain( 'snippet-press', false, dirname( SNIPPET_PRESS_BASENAME ) . '/languages' );
    }

    /**
     * Access to the service container.
     */
    public function container(): Service_Container {
        return $this->container;
    }
}




