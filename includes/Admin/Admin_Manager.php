<?php

namespace SnippetPress\Admin;

use SnippetPress\Admin\Actions\Snippet_Actions;
use SnippetPress\Admin\Assets\Admin_Assets;
use SnippetPress\Admin\Menu\Menu_Registrar;
use SnippetPress\Admin\Pages\Add_Snippet_Page;
use SnippetPress\Admin\Pages\Code_Snippets_Page;
use SnippetPress\Admin\Pages\File_Editor_Page;
use SnippetPress\Admin\Pages\Header_Footer_Page;
use SnippetPress\Admin\Pages\Library_Page;
use SnippetPress\Admin\Pages\Search_Replace_Page;
use SnippetPress\Admin\Pages\Tools_Page;
use SnippetPress\Admin\Pages\Settings_Page;
use SnippetPress\Admin\Notices;
use SnippetPress\Infrastructure\Capabilities;
use SnippetPress\Infrastructure\Service_Container;
use SnippetPress\Infrastructure\Service_Provider;

/**
 * Coordinates admin UI registration and delegates to specialized components.
 */
class Admin_Manager extends Service_Provider {
    private $assets;
    private $actions;
    private $menu;

    public function __construct( Service_Container $container ) {
        parent::__construct( $container );

        $primary_page = new Code_Snippets_Page( $container );

        $pages = [
            $primary_page,
            new Add_Snippet_Page( $container ),
            new Header_Footer_Page( $container ),
            new Library_Page( $container ),
            new File_Editor_Page( $container ),
            new Search_Replace_Page( $container ),
            new Tools_Page( $container ),
            new Settings_Page( $container ),
        ];

        $this->assets  = new Admin_Assets();
        $this->actions = new Snippet_Actions( $primary_page->slug() );
        $this->menu    = new Menu_Registrar( $primary_page, $pages );
    }

    public function register(): void {
        if ( ! is_admin() ) {
            return;
        }

        $this->assets->register();
        $this->actions->register();
        $this->menu->register();

        add_action( 'admin_notices', [ Notices::class, 'render' ] );
    }
}
