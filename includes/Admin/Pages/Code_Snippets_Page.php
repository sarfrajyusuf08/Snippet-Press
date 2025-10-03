<?php

namespace SnippetPress\Admin\Pages;

use SnippetPress\Admin\List_Table;
use SnippetPress\Infrastructure\Capabilities;

/**
 * Renders the main snippets listing screen.
 */
class Code_Snippets_Page extends Abstract_Admin_Page {
    public function register(): void {
        add_action( 'load-toplevel_page_' . $this->slug(), [ $this, 'register_screen_options' ] );
    }

    public function slug(): string {
        return 'sp-code-snippet';
    }

    public function title(): string {
        return __( 'Code Snippets', 'snippet-press' );
    }

    public function menu_title(): string {
        return __( 'Code Snippet', 'snippet-press' );
    }

    public function capability(): string {
        return Capabilities::EDIT;
    }

    public function render(): void {
        $this->assert_capability( $this->capability() );

        $list_table = new List_Table();
        $list_table->prepare_items();

        echo '<div class="wrap">';
        echo '<h1 class="wp-heading-inline">' . esc_html__( 'Code Snippets', 'snippet-press' ) . '</h1>';
        echo ' <a href="' . esc_url( admin_url( 'admin.php?page=sp-add-snippet' ) ) . '" class="page-title-action">' . esc_html__( 'Add Snippet', 'snippet-press' ) . '</a>';
        echo '<hr class="wp-header-end" />';

        $list_table->render_type_tabs();
        $list_table->views();

        echo '<form method="post">';
        $list_table->search_box( __( 'Search Snippets', 'snippet-press' ), 'sp-snippets' );
        $list_table->display();
        echo '</form>';
        echo '</div>';
    }

    /**
     * Register per-page screen options for the list table.
     */
    public function register_screen_options(): void {
        add_screen_option(
            'per_page',
            [
                'label'   => __( 'Snippets per page', 'snippet-press' ),
                'default' => 20,
                'option'  => 'snippet_press_snippets_per_page',
            ]
        );
    }
}