<?php
/**
 * External Links: Open in New Tab + Add rel Attributes
 * ----------------------------------------------------
 * This snippet automatically adds:
 * - target="_blank" to external links inside post content
 * - rel="noopener nofollow ugc" for security and SEO safety
 * Internal links (same domain) are not affected.
 *
 * Safe for eval: no closing PHP tag, no raw HTML blocks.
 */

return [
    'slug'        => 'sp-external-links-newtab',
    'title'       => __( 'External Links → New Tab + rel', 'snippet-press' ),
    'description' => __( 'Automatically opens external links in a new tab and adds rel="noopener nofollow ugc" to improve SEO and security.', 'snippet-press' ),
    'category'    => 'seo',
    'tags'        => [ 'links', 'seo', 'security' ],
    'highlights'  => [
        __( 'Adds security and SEO-friendly attributes to all external links.', 'snippet-press' ),
        __( 'Internal links remain unchanged.', 'snippet-press' ),
        __( 'Runs automatically on post content.', 'snippet-press' ),
    ],
    'code'        => <<<'PHP'
/**
 * Automatically adds target="_blank" and rel="noopener nofollow ugc"
 * to all external links found in post content.
 * Internal links (same domain) remain untouched.
 */
if ( ! function_exists( 'sp_filter_external_links_newtab' ) ) {
    add_filter( 'the_content', 'sp_filter_external_links_newtab', 20 );

    function sp_filter_external_links_newtab( $content ) {
        // Run only on frontend single views
        if ( is_admin() || empty( $content ) ) {
            return $content;
        }

        // Get current site domain to detect internal links
        $host = wp_parse_url( home_url(), PHP_URL_HOST );
        if ( ! $host ) {
            return $content;
        }

        // Use DOMDocument to safely parse and edit HTML
        libxml_use_internal_errors( true );
        $dom = new DOMDocument();
        $dom->loadHTML( '<?xml encoding="utf-8" ?>' . $content );

        foreach ( $dom->getElementsByTagName( 'a' ) as $a ) {
            $href = $a->getAttribute( 'href' );
            if ( ! $href || strpos( $href, '#' ) === 0 ) {
                continue; // Skip empty and anchor-only links
            }

            $link_host = wp_parse_url( $href, PHP_URL_HOST );
            // If link host exists and is different from our own domain → external
            if ( $link_host && $link_host !== $host ) {
                $a->setAttribute( 'target', '_blank' );

                // Merge any existing rel attributes
                $existing_rel = $a->getAttribute( 'rel' );
                $new_rel = trim( $existing_rel . ' noopener nofollow ugc' );
                $a->setAttribute( 'rel', preg_replace( '/\s+/', ' ', $new_rel ) );
            }
        }

        // Rebuild the cleaned HTML content
        $html = $dom->saveHTML();
        $html = preg_replace( '/^.*?<body>(.*)<\/body>.*$/si', '$1', $html );

        return $html ?: $content;
    }
}
PHP,
    'type'        => 'php',
    'scopes'      => [ 'frontend' ],
    'priority'    => 10,
    'status'      => 'disabled',
];
