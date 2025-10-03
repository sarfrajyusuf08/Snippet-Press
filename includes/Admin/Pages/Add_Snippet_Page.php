<?php

namespace SnippetPress\Admin\Pages;

use SnippetPress\Admin\Library\Snippet_Library;
use SnippetPress\Admin\Pages\Add_Snippet\Components\Status_Badge_Renderer;
use SnippetPress\Admin\Pages\Add_Snippet\Components\Tab_Navigation;
use SnippetPress\Admin\Pages\Add_Snippet\Library\Library_View;
use SnippetPress\Admin\Pages\Add_Snippet\Library\Snippet_Card_Renderer;
use SnippetPress\Admin\Pages\Add_Snippet\Support\Slug_Humanizer;
use SnippetPress\Admin\Pages\Add_Snippet\Tabs\My_Library_Tab;
use SnippetPress\Admin\Pages\Add_Snippet\Tabs\Placeholder_Tab;
use SnippetPress\Infrastructure\Capabilities;
use SnippetPress\Infrastructure\Service_Container;

/**
 * Renders the add snippet experience with library tabs.
 */
class Add_Snippet_Page extends Abstract_Admin_Page {
    /**
     * @var Status_Badge_Renderer
     */
    private $status_badge_renderer;

    /**
     * @var Slug_Humanizer
     */
    private $slug_humanizer;

    /**
     * @var Tab_Navigation
     */
    private $tab_navigation;

    public function __construct( Service_Container $container ) {
        parent::__construct( $container );

        $this->status_badge_renderer = new Status_Badge_Renderer();
        $this->slug_humanizer        = new Slug_Humanizer();
        $this->tab_navigation        = new Tab_Navigation();
    }

    public function slug(): string {
        return 'sp-add-snippet';
    }

    public function title(): string {
        return __( 'Add Snippet', 'snippet-press' );
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
            'library'   => __( 'Snippet Library', 'snippet-press' ),
            'generator' => __( 'Snippet Generators', 'snippet-press' ),
            'plugin'    => __( 'Plugin Snippets', 'snippet-press' ),
            'my'        => __( 'My Library', 'snippet-press' ),
        ];

        $active_tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'library';
        $active_tab = array_key_exists( $active_tab, $tabs ) ? $active_tab : 'library';

        echo '<div class="wrap sp-add-snippet-page">';
        echo '<h1 class="wp-heading-inline">' . esc_html__( 'Add Snippet', 'snippet-press' ) . '</h1>';
        echo '<hr class="wp-header-end">';

        $this->render_tabs( $tabs, $active_tab );

        echo '<div class="sp-add-snippet__body">';
        switch ( $active_tab ) {
            case 'library':
                $this->render_library_view( $library, $all_snippets, $categories, $tags );
                break;
            case 'generator':
                $this->render_generator_tab();
                break;
            case 'plugin':
                $this->render_plugin_tab();
                break;
            case 'my':
                $this->render_my_library_tab();
                break;
        }
        echo '</div>';
        echo '</div>';
    }

    /**
     * Render the primary tab navigation.
     */
    protected function render_tabs( array $tabs, string $active ): void {
        $this->tab_navigation->render( $tabs, $active );
    }

    /**
     * Render the snippet library tab.
     */
    protected function render_library_view( Snippet_Library $library, array $snippets, array $categories, array $tags ): void {
        $card_renderer = new Snippet_Card_Renderer( $this->status_badge_renderer, $this->slug_humanizer );
        $view          = new Library_View( $library, $card_renderer );
        $view->render( $snippets, $categories, $tags );
    }

    /**
     * Render the My Library tab.
     */
    protected function render_my_library_tab(): void {
        $tab = new My_Library_Tab( $this->status_badge_renderer );
        echo '<div class="sp-library-catalog">';
        echo '<section class="sp-template-section">';
        echo '<h2 class="sp-template-group-heading">' . esc_html__( 'My Snippets', 'snippet-press' ) . '</h2>';
        $tab->render();
        echo '</section>';
        echo '</div>';
    }

    /**
     * Render placeholder content for upcoming generator features.
     */
    protected function render_generator_tab(): void {
        $tab = new Placeholder_Tab(
            __( 'Snippet Generators', 'snippet-press' ),
            __( 'We are working on guided workflows and AI assistance so you can design snippets without writing code. Stay tuned!', 'snippet-press' )
        );
        $tab->render();
    }

    /**
     * Render placeholder content for plugin-specific snippets.
     */
    protected function render_plugin_tab(): void {
        $tab = new Placeholder_Tab(
            __( 'Plugin Snippets', 'snippet-press' ),
            __( 'Soon you will see curated snippets for popular plugins and themes in this tab.', 'snippet-press' )
        );
        $tab->render();
    }

    /**
     * Render a status badge for snippets.
     */
    protected function render_status_badge( string $status ): string {
        return $this->status_badge_renderer->render( $status );
    }

    /**
     * Convert a slug into a readable phrase.
     */
    protected function humanize_slug( string $slug ): string {
        return $this->slug_humanizer->from_slug( $slug );
    }
}
