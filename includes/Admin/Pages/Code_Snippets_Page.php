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
        return __( 'Snippet Press', 'snippet-press' );
    }

    public function menu_title(): string {
        return __( 'Snippet Press', 'snippet-press' );
    }

    public function capability(): string {
        return Capabilities::EDIT;
    }

    public function render(): void {
        $this->assert_capability( $this->capability() );

        $list_table = new List_Table();
        $list_table->prepare_items();

        echo '<div class="wrap">';
        echo '<h1 class="wp-heading-inline">' . esc_html__( 'Snippet Press', 'snippet-press' ) . '</h1>';
        echo ' <a href="' . esc_url( admin_url( 'admin.php?page=sp-add-snippet' ) ) . '" class="page-title-action">' . esc_html__( 'Add Snippet', 'snippet-press' ) . '</a>';
        echo '<hr class="wp-header-end" />';

        $list_table->render_type_tabs();
        $list_table->render_tag_filters();
        $list_table->views();

        echo '<form method="get">';
        echo '<input type="hidden" name="page" value="' . esc_attr( $this->slug() ) . '" />';
        $list_table->search_box( __( 'Search Snippets', 'snippet-press' ), 'sp-snippets' );
        echo '<input type="hidden" name="sp_type" value="' . esc_attr( $list_table->current_type() ) . '" />';
        echo '<input type="hidden" name="sp_status" value="' . esc_attr( $list_table->current_status() ) . '" />';
        $current_tags = $list_table->current_tags();
        if ( ! empty( $current_tags ) ) {
            echo '<input type="hidden" name="sp_tags" value="' . esc_attr( implode( ',', $current_tags ) ) . '" />';
        }
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
