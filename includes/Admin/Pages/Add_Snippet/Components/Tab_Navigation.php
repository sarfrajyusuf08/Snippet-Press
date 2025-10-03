<?php

namespace SnippetPress\Admin\Pages\Add_Snippet\Components;

/**
 * Handles rendering of top-level tab navigation.
 */
class Tab_Navigation {
    /**
     * Render the nav-tab wrapper with the provided tab definitions.
     *
     * @param array<string, string> $tabs   Map of tab slug => label.
     * @param string                $active Currently active tab slug.
     */
    public function render( array $tabs, string $active ): void {
        echo '<nav class="nav-tab-wrapper sp-add-snippet__tabs">';

        foreach ( $tabs as $key => $label ) {
            $classes = 'nav-tab' . ( $key === $active ? ' nav-tab-active' : '' );
            $url     = esc_url( add_query_arg( 'tab', $key ) );

            printf(
                '<a href="%1$s" class="%2$s">%3$s</a>',
                $url,
                esc_attr( $classes ),
                esc_html( $label )
            );
        }

        echo '</nav>';
    }
}
