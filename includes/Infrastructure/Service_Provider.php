<?php

namespace SnippetVault\Infrastructure;

/**
 * Contract for services managed by the container.
 */
abstract class Service_Provider {
    /**
     * Reference to the service container.
     *
     * @var Service_Container
     */
    protected $container;

    public function __construct( Service_Container $container ) {
        $this->container = $container;
    }

    /**
     * Register runtime hooks.
     */
    abstract public function register(): void;

    /**
     * Allow services to expose shared instances.
     */
    public function container(): Service_Container {
        return $this->container;
    }
}