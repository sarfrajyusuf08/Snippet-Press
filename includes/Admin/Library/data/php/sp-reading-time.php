<?php
/**
 * Reading Time Badge
 * ------------------
 * Displays an estimated reading time (e.g. “~3 min read”) at the top of single posts.
 *
 * - Safe for eval: no closing PHP tag and no raw HTML outside echo.
 * - Uses `the_content` filter to prepend a small badge before the post content.
 * - Assumes an average reading speed of 200 words per minute.
 */

return [
    'slug'        => 'sp-reading-time',
    'title'       => __( 'Reading Time Badge', 'snippet-press' ),
    'description' => __( 'Displays an estimated reading time badge above each single post.', 'snippet-press' ),
    'category'    => 'frontend',
    'tags'        => [ 'reading time', 'ux', 'engagement', 'frontend' ],
    'highlights'  => [
        __( 'Calculates reading time based on word count.', 'snippet-press' ),
        __( 'Automatically adds a badge above post content.', 'snippet-press' ),
        __( 'Safe and lightweight; uses inline CSS only.', 'snippet-press' ),
    ],
    'code'        => <<<'PHP'
/**
 * Adds a reading time badge to single post content.
 */
if ( ! function_exists( 'sp_add_reading_time_to_content' ) ) {

    // Add badge before post content
    add_filter( 'the_content', 'sp_add_reading_time_to_content', 5 );

    function sp_add_reading_time_to_content( $content ) {
        // Run only on single posts
        if ( is_admin() || ! is_singular( 'post' ) ) {
            return $content;
        }

        // Count words and estimate reading time
        $text  = wp_strip_all_tags( $content );
        $words = str_word_count( $text );
        $mins  = max( 1, ceil( $words / 200 ) ); // 200 WPM average

        // Build badge HTML
        $badge = '<div class="sp-reading-time" aria-label="Estimated reading time">~' . esc_html( $mins ) . ' min read</div>';

        return $badge . $content;
    }

    // Add minimal CSS styling for the badge
    add_action( 'wp_enqueue_scripts', function() {
        $css = '
            .sp-reading-time {
                display:inline-block;
                margin:0 0 8px;
                padding:4px 8px;
                border:1px solid #e5e7eb;
                border-radius:6px;
                font-size:0.8rem;
                color:#374151;
                background:#f9fafb;
            }';
        wp_register_style( 'sp-reading-time', false, [], null );
        wp_enqueue_style( 'sp-reading-time' );
        wp_add_inline_style( 'sp-reading-time', $css );
    } );
}
PHP,
    'type'        => 'php',
    'scopes'      => [ 'frontend' ],
    'priority'    => 10,
    'status'      => 'disabled',
];
