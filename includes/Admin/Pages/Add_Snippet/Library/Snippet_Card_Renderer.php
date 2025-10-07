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
     * @param array<string, mixed> $snippet Snippet metadata from the library.
     */
    public function render( array $snippet ): void {
        $title = isset( $snippet['title'] ) ? wp_strip_all_tags( (string) $snippet['title'] ) : '';

        if ( '' === $title && isset( $snippet['name'] ) ) {
            $title = wp_strip_all_tags( (string) $snippet['name'] );
        }

        if ( '' === $title ) {
            return;
        }

        $description = isset( $snippet['description'] ) ? wp_strip_all_tags( (string) $snippet['description'] ) : '';
        $type        = isset( $snippet['type'] ) ? strtoupper( (string) $snippet['type'] ) : '';
        $type_slug   = isset( $snippet['type'] ) ? sanitize_key( (string) $snippet['type'] ) : '';
        $scopes      = ! empty( $snippet['scopes'] ) ? array_map( [ $this->humanizer, 'from_slug' ], (array) $snippet['scopes'] ) : [];

        $meta_parts = array_filter(
            array_merge(
                $type ? [ $type ] : [],
                $scopes
            )
        );

        $status       = isset( $snippet['status'] ) ? sanitize_key( (string) $snippet['status'] ) : 'disabled';
        $status_label = $this->status_badge->render( $status );
        $slug_value   = isset( $snippet['slug'] ) ? (string) $snippet['slug'] : '';
        $category     = isset( $snippet['category'] ) ? sanitize_key( (string) $snippet['category'] ) : '';

        $search_pieces = [];
        $search_pieces[] = $title;
        $search_pieces[] = $description;

        if ( ! empty( $snippet['highlights'] ) && is_array( $snippet['highlights'] ) ) {
            foreach ( $snippet['highlights'] as $highlight ) {
                $search_pieces[] = wp_strip_all_tags( (string) $highlight );
            }
        }

        foreach ( (array) $scopes as $scope_label ) {
            $search_pieces[] = $scope_label;
        }

        if ( $type ) {
            $search_pieces[] = $type;
        }

        $search_blob = strtolower( trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( implode( ' ', array_filter( $search_pieces ) ) ) ) ) );

        echo '<div class="sp-template-card" data-snippet="true" data-snippet-slug="' . esc_attr( $slug_value ) . '" data-category="' . esc_attr( $category ) . '" data-type="' . esc_attr( $type_slug ) . '" data-status="' . esc_attr( $status ) . '" data-search="' . esc_attr( $search_blob ) . '">';
        echo '<div class="sp-template-card__header">';
        echo '<h3>' . esc_html( $title ) . '</h3>';
        echo $status_label;
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

        echo '<div class="sp-template-actions">';
        echo '<button type="button" class="button button-secondary sp-use-snippet">' . esc_html__( 'Use Snippet', 'snippet-press' ) . '</button>';
        echo '</div>';
        echo '</div>';
    }
}
