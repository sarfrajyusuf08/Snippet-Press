<?php

namespace SnippetPress\Admin\Menu;

use SnippetPress\Admin\Pages\Abstract_Admin_Page;

/**
 * Registers top-level and submenu entries for the plugin.
 */
class Menu_Registrar {
    private $primary_page;
    private $pages;

    /**
     * @param Abstract_Admin_Page   $primary_page Primary page displayed as top-level menu.
     * @param Abstract_Admin_Page[] $pages        All registered pages including the primary.
     */
    public function __construct( Abstract_Admin_Page $primary_page, array $pages ) {
        $this->primary_page = $primary_page;
        $this->pages        = $pages;
    }

    public function register(): void {
        foreach ( $this->pages as $page ) {
            $page->register();
        }

        add_action( 'admin_menu', [ $this, 'register_menu' ] );
    }

    public function register_menu(): void {
        $capability = $this->resolve_capability( $this->primary_page->capability() );

        add_menu_page(
            $this->primary_page->title(),
            $this->primary_page->menu_title(),
            $capability,
            $this->primary_page->slug(),
            [ $this->primary_page, 'render' ],
            'dashicons-editor-code',
            55
        );

        add_submenu_page(
            $this->primary_page->slug(),
            $this->primary_page->title(),
            $this->primary_page->menu_title(),
            $capability,
            $this->primary_page->slug(),
            [ $this->primary_page, 'render' ]
        );

        foreach ( $this->pages as $page ) {
            if ( $page === $this->primary_page ) {
                continue;
            }

            add_submenu_page(
                $page->parent_slug(),
                $page->title(),
                $page->menu_title(),
                $this->resolve_capability( $page->capability() ),
                $page->slug(),
                [ $page, 'render' ]
            );
        }
    }

    protected function resolve_capability( string $capability ): string {
        return current_user_can( $capability ) ? $capability : 'manage_options';
    }
}