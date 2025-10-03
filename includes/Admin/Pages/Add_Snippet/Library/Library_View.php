<?php

namespace SnippetPress\Admin\Pages\Add_Snippet\Library;

use SnippetPress\Admin\Library\Snippet_Library;

/**
 * Renders the Snippet Library tab layout.
 */
class Library_View {
    /**
     * @var Snippet_Library
     */
    private $library;

    /**
     * @var Snippet_Card_Renderer
     */
    private $card_renderer;

    public function __construct( Snippet_Library $library, Snippet_Card_Renderer $card_renderer ) {
        $this->library       = $library;
        $this->card_renderer = $card_renderer;
    }

    /**
     * Render the full library view including sidebar and catalog.
     *
     * @param array<int, array<string, mixed>> $snippets   Snippet definitions from the library.
     * @param array<string, string>            $categories Map of category slug => label.
     * @param array<string, string>            $tag_labels Map of tag slug => label.
     */
    public function render( array $snippets, array $categories, array $tag_labels ): void {
        $total_snippets     = count( $snippets );
        $available_snippets = count( $this->library->filter( 'available' ) );

        echo '<div class="sp-add-snippet__header">';
        echo '<h2>' . esc_html__( 'Start with a ready-made snippet', 'snippet-press' ) . '</h2>';
        printf(
            '<p>%s</p>',
            wp_kses_post(
                sprintf(
                    /* translators: 1: link to create blank snippet, 2: link to suggest snippet. */
                    __( 'Browse the curated library below or jump straight to %1$s. Have an idea we should add? %2$s.', 'snippet-press' ),
                    '<a href="' . esc_url( admin_url( 'post-new.php?post_type=sp_snippet' ) ) . '">' . esc_html__( 'creating a custom snippet', 'snippet-press' ) . '</a>',
                    '<a href="#">' . esc_html__( 'Let us know', 'snippet-press' ) . '</a>'
                )
            )
        );
        echo '</div>';

        echo '<div class="sp-library-layout">';
        $this->render_sidebar( $categories, $total_snippets, $available_snippets );
        $this->render_catalog( $snippets, $tag_labels );
        echo '</div>';
    }

    /**
     * Render the sidebar filters and search box.
     */
    private function render_sidebar( array $categories, int $total, int $available ): void {
        echo '<aside class="sp-library-sidebar">';
        echo '<input type="search" class="sp-library-search" placeholder="' . esc_attr__( 'Search snippets', 'snippet-press' ) . '" />';
        echo '<ul class="sp-library-filter-list">';

        $this->render_filter_link( 'all', __( 'All Snippets', 'snippet-press' ), $total, true );
        $this->render_filter_link( 'available', __( 'Available Snippets', 'snippet-press' ), $available );

        foreach ( $categories as $slug => $name ) {
            $count = count( $this->library->filter( 'category-' . $slug ) );
            $this->render_filter_link( 'category-' . $slug, $name, $count );
        }

        echo '</ul>';
        echo '</aside>';
    }

    /**
     * Helper to output individual filter links.
     */
    private function render_filter_link( string $slug, string $label, int $count, bool $active = false ): void {
        printf(
            '<li><a href="%1$s" class="sp-library-filter%2$s" data-filter="%3$s">%4$s<span class="sp-library-filter__count">%5$s</span></a></li>',
            esc_url( '#' ),
            $active ? ' is-active' : '',
            esc_attr( $slug ),
            esc_html( $label ),
            esc_html( number_format_i18n( $count ) )
        );
    }

    /**
     * Render the main catalog column.
     */
    private function render_catalog( array $snippets, array $tag_labels ): void {
        echo '<div class="sp-library-catalog">';
        $this->render_quick_actions();

        if ( empty( $snippets ) ) {
            echo '<div class="sp-empty-panel">';
            echo '<h2>' . esc_html__( 'Library coming soon', 'snippet-press' ) . '</h2>';
            echo '<p>' . esc_html__( 'We are preparing our first batch of ready-to-use snippets. Check back shortly!', 'snippet-press' ) . '</p>';
            echo '</div>';
            echo '</div>';
            return;
        }

        echo '<section class="sp-template-section">';
        echo '<h2 class="sp-template-group-heading">' . esc_html__( 'Browse Snippet Library', 'snippet-press' ) . '</h2>';
        echo '<div class="sp-template-grid">';

        foreach ( $snippets as $snippet ) {
            if ( ! is_array( $snippet ) ) {
                continue;
            }

            $this->card_renderer->render( $snippet, $tag_labels );
        }

        echo '</div>';
        echo '</section>';
        echo '</div>';
    }

    /**
     * Render quick action helper cards shown at the top of the catalog.
     */
    private function render_quick_actions(): void {
        echo '<section class="sp-template-section">';
        echo '<h2 class="sp-template-group-heading">' . esc_html__( 'Quick Actions', 'snippet-press' ) . '</h2>';
        echo '<div class="sp-template-grid">';

        echo '<div class="sp-template-card sp-template-card--cta">';
        echo '<h3>' . esc_html__( 'Create a Custom Snippet', 'snippet-press' ) . '</h3>';
        echo '<p class="sp-template-meta">' . esc_html__( 'Start from scratch with your own PHP, CSS, or JS.', 'snippet-press' ) . '</p>';
        echo '<div class="sp-template-actions">';
        echo '<a class="button button-primary" href="' . esc_url( admin_url( 'post-new.php?post_type=sp_snippet' ) ) . '">' . esc_html__( 'Create Snippet', 'snippet-press' ) . '</a>';
        echo '</div>';
        echo '</div>';

        echo '<div class="sp-template-card sp-template-card--cta">';
        echo '<h3>' . esc_html__( 'Generate with AI', 'snippet-press' ) . '</h3>';
        echo '<p class="sp-template-meta">' . esc_html__( 'Describe what you need and let our upcoming assistant draft the snippet for you.', 'snippet-press' ) . '</p>';
        echo '<div class="sp-template-actions">';
        echo '<button type="button" class="button" disabled="disabled">' . esc_html__( 'Coming Soon', 'snippet-press' ) . '</button>';
        echo '</div>';
        echo '</div>';

        echo '<div class="sp-template-card sp-template-card--cta">';
        echo '<h3>' . esc_html__( 'Import Existing Library', 'snippet-press' ) . '</h3>';
        echo '<p class="sp-template-meta">' . esc_html__( 'Bring in snippets from JSON exports or other safe sources.', 'snippet-press' ) . '</p>';
        echo '<div class="sp-template-actions">';
        echo '<a class="button" href="#">' . esc_html__( 'Upload JSON', 'snippet-press' ) . '</a>';
        echo '</div>';
        echo '</div>';

        echo '</div>';
        echo '</section>';
    }
}
