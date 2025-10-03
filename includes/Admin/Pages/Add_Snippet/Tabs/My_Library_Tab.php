<?php

namespace SnippetPress\Admin\Pages\Add_Snippet\Tabs;

use SnippetPress\Admin\Pages\Add_Snippet\Components\Status_Badge_Renderer;
use WP_Query;

/**
 * Handles rendering of the "My Library" tab.
 */
class My_Library_Tab {
    /**
     * @var Status_Badge_Renderer
     */
    private $status_badge;

    /**
     * @var int
     */
    private $posts_per_page;

    public function __construct( Status_Badge_Renderer $status_badge, int $posts_per_page = 10 ) {
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
                'author'         => get_current_user_id(),
                'posts_per_page' => $this->posts_per_page,
                'orderby'        => 'modified',
                'order'          => 'DESC',
            ]
        );

        if ( ! $query->have_posts() ) {
            $this->render_empty_state();
            wp_reset_postdata();
            return;
        }

        echo '<div class="sp-template-grid">';

        foreach ( $query->posts as $post ) {
            $status    = get_post_meta( $post->ID, '_sp_status', true ) ?: 'disabled';
            $edit_link = get_edit_post_link( $post->ID );

            setup_postdata( $post );

            echo '<div class="sp-template-card">';
            echo '<div class="sp-template-card__header">';
            echo '<h3>' . esc_html( get_the_title( $post ) ) . '</h3>';
            echo $this->status_badge->render( strtolower( (string) $status ) );
            echo '</div>';
            echo '<p class="sp-template-meta">' . esc_html( get_post_modified_time( get_option( 'date_format' ), false, $post, true ) ) . '</p>';
            echo '<div class="sp-template-actions">';
            echo '<a class="button button-primary" href="' . esc_url( $edit_link ) . '">' . esc_html__( 'Edit Snippet', 'snippet-press' ) . '</a>';
            echo '</div>';
            echo '</div>';
        }

        echo '</div>';

        wp_reset_postdata();
    }

    /**
     * Render the empty state messaging when no snippets exist.
     */
    private function render_empty_state(): void {
        echo '<div class="sp-empty-panel">';
        echo '<p>' . esc_html__( 'You have not created any snippets yet.', 'snippet-press' ) . '</p>';
        echo '<p><a class="button button-primary" href="' . esc_url( admin_url( 'post-new.php?post_type=sp_snippet' ) ) . '">' . esc_html__( 'Create your first snippet', 'snippet-press' ) . '</a></p>';
        echo '</div>';
    }
}
