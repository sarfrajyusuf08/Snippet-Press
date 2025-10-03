<?php

namespace SnippetPress\Admin\Pages\Add_Snippet\Components;

/**
 * Renders status badges for snippet cards and lists.
 */
class Status_Badge_Renderer {
    /**
     * Render a badge for the provided status string.
     */
    public function render( string $status ): string {
        $status_key = sanitize_key( $status ?: 'available' );

        $labels = [
            'available'    => __( 'Available', 'snippet-press' ),
            'active'       => __( 'Active', 'snippet-press' ),
            'enabled'      => __( 'Enabled', 'snippet-press' ),
            'disabled'     => __( 'Disabled', 'snippet-press' ),
            'draft'        => __( 'Draft', 'snippet-press' ),
            'beta'         => __( 'Beta', 'snippet-press' ),
            'coming-soon'  => __( 'Coming Soon', 'snippet-press' ),
            'coming_soon'  => __( 'Coming Soon', 'snippet-press' ),
            'experimental' => __( 'Experimental', 'snippet-press' ),
        ];

        $label = $labels[ $status_key ] ?? ucwords( str_replace( [ '-', '_' ], ' ', $status_key ) );
        $css_class = 'sp-status-badge sp-status-badge--' . sanitize_html_class( $status_key ?: 'available' );

        return sprintf(
            '<span class="%1$s">%2$s</span>',
            esc_attr( $css_class ),
            esc_html( $label )
        );
    }
}
