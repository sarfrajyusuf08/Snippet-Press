<?php

namespace SnippetPress\Admin\Pages\Add_Snippet\Tabs;

use SnippetPress\Admin\Pages\Add_Snippet\Components\Status_Badge_Renderer;
use WP_Query;

/**
 * Displays snippets that have been marked as favorites.
 */
class Favorites_Tab {
    /**
     * @var Status_Badge_Renderer
     */
    private $status_badge;

    /**
     * @var int
     */
    private $posts_per_page;

    public function __construct( Status_Badge_Renderer $status_badge, int $posts_per_page = 12 ) {
        $this->status_badge   = $status_badge;
        $this->posts_per_page = $posts_per_page;
    }

    /**
     * Render the tab contents.
     */
    public function render(): void {
        $query = new WP_Query(
            [
                'post_type'      => 'sp_snippet',
                'post_status'    => [ 'draft', 'pending', 'publish' ],
                'posts_per_page' => $this->posts_per_page,
                'orderby'        => 'modified',
                'order'          => 'DESC',
                'meta_query'     => [
                    [
                        'key'   => '_sp_favorite',
                        'value' => '1',
                    ],
                ],
            ]
        );

        if ( ! $query->have_posts() ) {
            $this->render_empty_state();
            wp_reset_postdata();
            return;
        }

        echo '<div class="sp-template-grid">';
        global $post;

        foreach ( $query->posts as $post ) {
            $status   = get_post_meta( $post->ID, '_sp_status', true ) ?: 'disabled';
            $view_url = get_edit_post_link( $post->ID );

            setup_postdata( $post );

            echo '<div class="sp-template-card">';
            echo '<div class="sp-template-card__header">';
            echo '<h3>' . esc_html( get_the_title( $post ) ) . '</h3>';
            echo $this->status_badge->render( strtolower( (string) $status ) );
            echo '</div>';
            echo '<p class="sp-template-meta">' . esc_html( get_post_modified_time( get_option( 'date_format' ), false, $post, true ) ) . '</p>';
            echo '<div class="sp-template-actions">';
            echo '<a class="button button-secondary" href="' . esc_url( $view_url ) . '">' . esc_html__( 'Edit Snippet', 'snippet-press' ) . '</a>';
            echo '</div>';
            echo '</div>';
        }

        echo '</div>';

        wp_reset_postdata();
    }

    /**
     * Show an empty-state message when no favorites exist.
     */
    private function render_empty_state(): void {
        echo '<div class="sp-empty-panel">';
        echo '<p>' . esc_html__( 'You have not marked any snippets as favorites yet.', 'snippet-press' ) . '</p>';
        echo '<p>' . esc_html__( 'Browse the Snippets tab and click the favorite toggle to curate your shortlist.', 'snippet-press' ) . '</p>';
        echo '</div>';
    }
}
