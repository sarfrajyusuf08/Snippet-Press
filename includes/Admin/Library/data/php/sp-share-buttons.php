<?php
/**
 * File: includes/Admin/Library/data/php/sp-share-buttons.php
 * Purpose: Adds share buttons at the end of single posts (frontend).
 */

return [
    'slug'        => 'sp-share-buttons',
    'title'       => __( 'Share Buttons at Post End', 'snippet-press' ),
    'description' => __( 'Appends WhatsApp, Telegram, X (Twitter), Facebook, LinkedIn, Email, and Copy Link buttons to the end of single post content.', 'snippet-press' ),
    'category'    => 'frontend',
    'tags'        => [ 'share', 'social', 'buttons', 'ux' ],
    'highlights'  => [
        __( 'Outputs only on single post pages.', 'snippet-press' ),
        __( 'Eval-safe: no raw HTML blocks; everything printed via echo/hooks.', 'snippet-press' ),
        __( 'Includes minimal CSS and a copy-link helper.', 'snippet-press' ),
    ],
    'code'        => <<<'PHP'
/**
 * Share Buttons at the end of single posts (eval-safe)
 * - No closing PHP tag
 * - No raw <script>/<div> outside echo
 * - All output via hooks
 */

if ( ! defined( 'SP_SNIPPET_SHARE_BUTTONS_LOADED' ) ) {
    define( 'SP_SNIPPET_SHARE_BUTTONS_LOADED', true );

    // Append buttons to post content
    add_filter( 'the_content', 'sp_snippet_add_share_buttons_to_content', 99 );

    // Minimal CSS
    add_action( 'wp_enqueue_scripts', 'sp_snippet_share_buttons_assets' );

    // Copy-link helper script
    add_action( 'wp_footer', 'sp_snippet_share_buttons_inline_js' );
}

if ( ! function_exists( 'sp_snippet_add_share_buttons_to_content' ) ) {
    function sp_snippet_add_share_buttons_to_content( $content ) {
        if ( is_admin() || ! is_singular( 'post' ) ) {
            return $content;
        }

        $url         = get_permalink();
        $title       = get_the_title();
        $encoded_url = rawurlencode( $url );
        $encoded_ttl = rawurlencode( $title );

        $links = [
            [ 'label' => 'WhatsApp', 'href' => 'https://api.whatsapp.com/send?text=' . $encoded_ttl . '%20' . $encoded_url, 'aria' => 'Share on WhatsApp' ],
            [ 'label' => 'Telegram', 'href' => 'https://t.me/share/url?url=' . $encoded_url . '&text=' . $encoded_ttl, 'aria' => 'Share on Telegram' ],
            [ 'label' => 'X',        'href' => 'https://twitter.com/intent/tweet?url=' . $encoded_url . '&text=' . $encoded_ttl, 'aria' => 'Share on X' ],
            [ 'label' => 'Facebook', 'href' => 'https://www.facebook.com/sharer/sharer.php?u=' . $encoded_url, 'aria' => 'Share on Facebook' ],
            [ 'label' => 'LinkedIn', 'href' => 'https://www.linkedin.com/sharing/share-offsite/?url=' . $encoded_url, 'aria' => 'Share on LinkedIn' ],
            [ 'label' => 'Email',    'href' => 'mailto:?subject=' . $encoded_ttl . '&body=' . $encoded_url, 'aria' => 'Share via Email' ],
        ];

        $html  = '<div class="sp-share-wrap" role="region" aria-label="Share this post">';
        $html .= '<div class="sp-share-title">Share this post</div>';
        $html .= '<div class="sp-share-buttons">';

        foreach ( $links as $link ) {
            $html .= '<a class="sp-share-btn" target="_blank" rel="noopener" href="' . esc_url( $link['href'] ) . '" aria-label="' . esc_attr( $link['aria'] ) . '">'
                  .  esc_html( $link['label'] )
                  .  '</a>';
        }

        // Copy Link button (handled by JS)
        $html .= '<button type="button" class="sp-share-btn sp-copy-link" data-link="' . esc_attr( $url ) . '" aria-label="Copy link">Copy Link</button>';

        $html .= '</div></div>';

        return $content . $html;
    }
}

if ( ! function_exists( 'sp_snippet_share_buttons_assets' ) ) {
    function sp_snippet_share_buttons_assets() {
        // Inline CSS only (safe for all themes)
        $css = '
        .sp-share-wrap{margin:2rem 0 0;border-top:1px solid #e5e7eb;padding-top:1rem}
        .sp-share-title{font-weight:600;margin-bottom:.5rem}
        .sp-share-buttons{display:flex;flex-wrap:wrap;gap:.5rem}
        .sp-share-btn{display:inline-flex;align-items:center;justify-content:center;padding:.5rem .75rem;border:1px solid #e5e7eb;border-radius:.5rem;text-decoration:none;font-size:.875rem;line-height:1}
        .sp-share-btn:hover{background:#f3f4f6}
        .sp-copy-link{cursor:pointer}
        ';
        wp_register_style( 'sp-share-inline', false, [], null );
        wp_enqueue_style( 'sp-share-inline' );
        wp_add_inline_style( 'sp-share-inline', $css );
    }
}

if ( ! function_exists( 'sp_snippet_share_buttons_inline_js' ) ) {
    function sp_snippet_share_buttons_inline_js() {
        if ( ! is_singular( 'post' ) ) {
            return;
        }
        // Print JS via echo (no raw closing PHP tag)
        $js = "(function(){
            var btn = document.querySelector('.sp-copy-link');
            if(!btn) return;
            btn.addEventListener('click', function(){
                var url = this.getAttribute('data-link');
                function ok(){ btn.textContent = 'Copied!'; setTimeout(function(){ btn.textContent = 'Copy Link'; }, 1500); }
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(url).then(ok).catch(fallback);
                } else {
                    fallback();
                }
                function fallback(){
                    var ta = document.createElement('textarea');
                    ta.value = url; document.body.appendChild(ta); ta.select();
                    try { document.execCommand('copy'); } catch(e) {}
                    document.body.removeChild(ta); ok();
                }
            });
        })();";
        echo '<script>' . $js . '</script>';
    }
}
PHP,
    'type'        => 'php',
    'scopes'      => [ 'frontend' ],
    'priority'    => 10,
    'status'      => 'disabled',
];
