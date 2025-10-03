<?php

namespace SnippetPress\Admin\Pages;

use SnippetPress\Infrastructure\Service_Container;
use SnippetPress\Infrastructure\Settings;

/**
 * Base class for admin pages that need container access and capability checks.
 */
abstract class Abstract_Admin_Page {
    /**
     * Shared service container instance.
     */
    protected $container;

    public function __construct( Service_Container $container ) {
        $this->container = $container;
    }

    /**
     * Menu slug for the page.
     */
    abstract public function slug(): string;

    /**
     * Menu label shown in the sidebar.
     */
    public function menu_title(): string {
        return $this->title();
    }

    /**
     * Human-readable page title.
     */
    abstract public function title(): string;

    /**
     * Capability required to access the page.
     */
    abstract public function capability(): string;

    /**
     * Parent slug for submenu registration.
     */
    public function parent_slug(): string {
        return 'sp-code-snippet';
    }

    /**
     * Allow pages to register additional hooks when the service boots.
     */
    public function register(): void {
        // Optional for concrete pages.
    }

    /**
     * Output the page contents.
     */
    abstract public function render(): void;

    /**
     * Convenient wrapper around the shared container.
     */
    protected function container(): Service_Container {
        return $this->container;
    }

    /**
     * Verify the current user can access the page.
     */
    protected function assert_capability( string $capability ): void {
        if ( current_user_can( $capability ) || current_user_can( 'manage_options' ) ) {
            return;
        }

        wp_die( esc_html__( 'You do not have permission to perform this action.', 'snippet-press' ), 403 );
    }

    /**
     * Retrieve the settings service from the container.
     */
    protected function settings(): Settings {
        $service = $this->container->get( Settings::class );

        if ( ! $service instanceof Settings ) {
            wp_die( esc_html__( 'The Snippet Press settings service is unavailable.', 'snippet-press' ), 500 );
        }

        return $service;
    }

    /**
     * Render a stylised checkbox used throughout the admin UI.
     */
    protected function checkbox_input( string $name, string $value, bool $checked, string $label, array $attributes = [] ): string {
        $id = $attributes['id'] ?? \wp_unique_id( 'sp-checkbox-' );
        unset( $attributes['id'] );

        $attribute_string = sprintf( ' id="%s"', esc_attr( $id ) );
        foreach ( $attributes as $attr => $attr_value ) {
            $attribute_string .= sprintf( ' %s="%s"', esc_attr( $attr ), esc_attr( $attr_value ) );
        }

        $checked_attribute = $checked ? ' checked="checked"' : '';

        return sprintf(
            '<label class="sp-checkbox" for="%5$s"><input type="checkbox" id="%5$s" name="%1$s" value="%2$s"%3$s%4$s /><span class="sp-checkbox__control" aria-hidden="true"></span><span class="sp-checkbox__label">%6$s</span></label>',
            esc_attr( $name ),
            esc_attr( $value ),
            $checked_attribute,
            $attribute_string,
            esc_attr( $id ),
            esc_html( $label )
        );
    }
}