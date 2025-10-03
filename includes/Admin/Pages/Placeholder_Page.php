<?php

namespace SnippetPress\Admin\Pages;

use SnippetPress\Infrastructure\Service_Container;

/**
 * Lightweight page that only needs to render a placeholder message.
 */
class Placeholder_Page extends Abstract_Admin_Page {
    protected $slug;
    protected $title;
    protected $menu_title;
    protected $capability;
    protected $message;

    public function __construct( Service_Container $container, string $slug, string $title, string $capability, string $message, ?string $menu_title = null ) {
        parent::__construct( $container );
        $this->slug       = $slug;
        $this->title      = $title;
        $this->capability = $capability;
        $this->menu_title = $menu_title ?: $title;
        $this->message    = $message;
    }

    public function slug(): string {
        return $this->slug;
    }

    public function title(): string {
        return $this->title;
    }

    public function menu_title(): string {
        return $this->menu_title;
    }

    public function capability(): string {
        return $this->capability;
    }

    public function render(): void {
        $this->assert_capability( $this->capability() );

        echo '<div class="wrap">';
        echo '<h1>' . esc_html( $this->title() ) . '</h1>';
        echo '<p>' . esc_html( $this->message ) . '</p>';
        echo '</div>';
    }
}