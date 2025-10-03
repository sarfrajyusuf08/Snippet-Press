<?php

namespace SnippetPress\Infrastructure;

/**
 * Lightweight service container to manage plugin services.
 */
class Service_Container {
    /**
     * Registered services.
     *
     * @var Service_Provider[]
     */
    private $services = [];

    /**
     * Register a service provider.
     */
    public function register( Service_Provider $provider ): void {
        $this->services[ get_class( $provider ) ] = $provider;
    }

    /**
     * Boot registered services.
     */
    public function boot(): void {
        foreach ( $this->services as $service ) {
            $service->register();
        }
    }

    /**
     * Retrieve a service by class name.
     */
    public function get( string $class ): ?Service_Provider {
        return $this->services[ $class ] ?? null;
    }

    /**
     * Return all services.
     *
     * @return Service_Provider[]
     */
    public function all(): array {
        return $this->services;
    }
}

