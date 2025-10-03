<?php

namespace SnippetPress\Admin\Pages\Add_Snippet\Library;

use SnippetPress\Admin\Pages\Add_Snippet\Components\Status_Badge_Renderer;
use SnippetPress\Admin\Pages\Add_Snippet\Support\Slug_Humanizer;

/**
 * Responsible for outputting individual snippet cards.
 */
class Snippet_Card_Renderer {
    /**
     * @var Status_Badge_Renderer
     */
    private $status_badge;

    /**
     * @var Slug_Humanizer
     */
    private $humanizer;

    public function __construct( Status_Badge_Renderer $status_badge, Slug_Humanizer $humanizer ) {
        $this->status_badge = $status_badge;
        $this->humanizer    = $humanizer;
    }

    /**
     * Render the card markup for an individual library snippet definition.
     *
     * @param array<string, mixed> $snippet    Snippet metadata from the library.
     * @param array<string, string> $tag_labels Map of tag slug => label.
     */
    public function render( array $snippet, array $tag_labels ): void {
        $title = isset( $snippet['title'] ) ? wp_strip_all_tags( (string) $snippet['title'] ) : '';

        if ( '' === $title && isset( $snippet['name'] ) ) {
            $title = wp_strip_all_tags( (string) $snippet['name'] );
        }

        if ( '' === $title ) {
            return;
        }

        $description = isset( $snippet['description'] ) ? wp_strip_all_tags( (string) $snippet['description'] ) : '';
        $type        = isset( $snippet['type'] ) ? strtoupper( (string) $snippet['type'] ) : '';
        $scopes      = ! empty( $snippet['scopes'] ) ? array_map( [ $this->humanizer, 'from_slug' ], (array) $snippet['scopes'] ) : [];

        $meta_parts = array_filter(
            array_merge(
                $type ? [ $type ] : [],
                $scopes
            )
        );

        $status     = isset( $snippet['status'] ) ? (string) $snippet['status'] : 'available';
        $slug_value = isset( $snippet['slug'] ) ? (string) $snippet['slug'] : '';

        echo '<div class="sp-template-card" data-snippet-slug="' . esc_attr( $slug_value ) . '">';
        echo '<div class="sp-template-card__header">';
        echo '<h3>' . esc_html( $title ) . '</h3>';
        echo $this->status_badge->render( $status );
        echo '</div>';

        if ( ! empty( $meta_parts ) ) {
            echo '<p class="sp-template-meta">' . esc_html( implode( ' • ', $meta_parts ) ) . '</p>';
        }

        if ( '' !== $description ) {
            echo '<p>' . esc_html( $description ) . '</p>';
        }

        if ( ! empty( $snippet['highlights'] ) && is_array( $snippet['highlights'] ) ) {
            echo '<ul class="sp-template-highlights">';
            foreach ( $snippet['highlights'] as $highlight ) {
                echo '<li>' . esc_html( wp_strip_all_tags( (string) $highlight ) ) . '</li>';
            }
            echo '</ul>';
        }

        if ( ! empty( $snippet['tags'] ) ) {
            echo '<div class="sp-tags">';
            foreach ( (array) $snippet['tags'] as $tag_slug ) {
                $tag_label = isset( $tag_labels[ $tag_slug ] ) ? $tag_labels[ $tag_slug ] : $this->humanizer->from_slug( (string) $tag_slug );
                echo '<span class="sp-tag">' . esc_html( $tag_label ) . '</span>';
            }
            echo '</div>';
        }

        echo '<div class="sp-template-actions">';
        echo '<button type="button" class="button button-secondary sp-use-snippet">' . esc_html__( 'Use Snippet', 'snippet-press' ) . '</button>';
        echo '</div>';
        echo '</div>';
    }
}

