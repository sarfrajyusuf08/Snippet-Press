<?php

namespace SnippetPress\Admin\Pages;

use SnippetPress\Admin\Library\Snippet_Library;
use SnippetPress\Admin\Pages\Add_Snippet\Components\Status_Badge_Renderer;
use SnippetPress\Admin\Pages\Add_Snippet\Tabs\Favorites_Tab;
use SnippetPress\Infrastructure\Capabilities;
use SnippetPress\Infrastructure\Service_Container;

/**
 * Dedicated library screen with favorites and personal tabs.
 */
class Library_Page extends Add_Snippet_Page {
    /**
     * @var Favorites_Tab
     */
    private $favorites_tab;

    public function __construct( Service_Container $container ) {
        parent::__construct( $container );

        $this->favorites_tab = new Favorites_Tab( new Status_Badge_Renderer() );
    }

    public function slug(): string {
        return 'sp-library';
    }

    public function title(): string {
        return __( 'Snippet Library', 'snippet-press' );
    }

    public function menu_title(): string {
        return __( 'Library', 'snippet-press' );
    }

    public function capability(): string {
        return Capabilities::EDIT;
    }

    public function render(): void {
        $this->assert_capability( $this->capability() );

        $library      = new Snippet_Library();
        $all_snippets = $library->all();
        $categories   = $library->get_categories();
        $tags         = $library->get_tags();

        $tabs = [
            'snippets'  => __( 'Snippets', 'snippet-press' ),
            'my'        => __( 'My Library', 'snippet-press' ),
            'favorites' => __( 'My Favorites', 'snippet-press' ),
        ];

        $active_tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'snippets';
        $active_tab = array_key_exists( $active_tab, $tabs ) ? $active_tab : 'snippets';

        echo '<div class="wrap sp-library-page">';
        echo '<h1 class="wp-heading-inline">' . esc_html__( 'Snippet Library', 'snippet-press' ) . '</h1>';
        echo '<hr class="wp-header-end">';

        $this->render_tabs( $tabs, $active_tab );

        echo '<div class="sp-add-snippet__body">';
        switch ( $active_tab ) {
            case 'snippets':
                $this->render_library_view( $library, $all_snippets, $categories, $tags );
                break;
            case 'my':
                $this->render_my_library_tab();
                break;
            case 'favorites':
                $this->render_favorites_tab();
                break;
        }
        echo '</div>';
        echo '</div>';
    }

    /**
     * Render the favorites tab.
     */
    protected function render_favorites_tab(): void {
        echo '<div class="sp-library-catalog">';
        echo '<section class="sp-template-section">';
        echo '<h2 class="sp-template-group-heading">' . esc_html__( 'Favorite Snippets', 'snippet-press' ) . '</h2>';
        $this->favorites_tab->render();
        echo '</section>';
        echo '</div>';
    }
}
