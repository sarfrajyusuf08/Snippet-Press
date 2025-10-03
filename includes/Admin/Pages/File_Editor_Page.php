<?php

namespace SnippetPress\Admin\Pages;

use SnippetPress\Infrastructure\Capabilities;

/**
 * File editor screen styled similar to WPCode's UI with file tabs and editor placeholder.
 */
class File_Editor_Page extends Abstract_Admin_Page {
    public function slug(): string {
        return 'sp-file-editor';
    }

    public function title(): string {
        return __( 'File Editor', 'snippet-press' );
    }

    public function capability(): string {
        return Capabilities::ADMIN;
    }

    public function render(): void {
        $this->assert_capability( $this->capability() );

        $files = $this->files();
        $requested = isset( $_GET['file'] ) ? sanitize_key( wp_unslash( $_GET['file'] ) ) : '';
        $active    = isset( $files[ $requested ] ) ? $requested : array_keys( $files )[0];

        echo '<div class="wrap sp-file-editor-page">';
        echo '<h1 class="wp-heading-inline">' . esc_html__( 'File Editor', 'snippet-press' ) . '</h1>';
        echo '<hr class="wp-header-end">';

        $this->render_tabs( $files, $active );

        echo '<div class="sp-file-editor__body">';
        $this->render_sidebar( $files, $active );
        $this->render_editor_panel( $files[ $active ] );
        echo '</div>';
        echo '</div>';
    }

    /**
     * Provide the available managed files.
     *
     * @return array<string,array>
     */
    protected function files(): array {
        return [
            'robots-txt' => [
                'label'       => 'robots.txt',
                'description' => __( 'Control how search engines crawl your site while keeping access to critical admin endpoints.', 'snippet-press' ),
                'code'        => "User-agent: *\nDisallow: /wp-admin/\nAllow: /wp-admin/admin-ajax.php\n\n# This preview is read-only for now.",
            ],
            'ads-txt' => [
                'label'       => 'ads.txt',
                'description' => __( 'Declare the advertising systems that are authorized to sell your inventory.', 'snippet-press' ),
                'code'        => "example.com, 12345, DIRECT, f08c47fec0942fa0\n# Replace with your verified partners.",
            ],
            'sitemap-xml' => [
                'label'       => 'sitemap.xml',
                'description' => __( 'Expose your latest content to search engines with a dynamic sitemap.', 'snippet-press' ),
                'code'        => "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n  <url>\n    <loc>https://example.com/</loc>\n    <priority>1.0</priority>\n  </url>\n</urlset>",
            ],
            'htaccess' => [
                'label'       => '.htaccess',
                'description' => __( 'Manage redirects and server directives without leaving WordPress.', 'snippet-press' ),
                'code'        => "# BEGIN WordPress\n<IfModule mod_rewrite.c>\nRewriteEngine On\nRewriteBase /\nRewriteRule ^index\\.php$ - [L]\nRewriteCond %{REQUEST_FILENAME} !-f\nRewriteCond %{REQUEST_FILENAME} !-d\nRewriteRule . /index.php [L]\n</IfModule>\n# END WordPress",
            ],
        ];
    }

    /**
     * Render the primary file tabs.
     *
     * @param array<string,array> $files
     */
    protected function render_tabs( array $files, string $active ): void {
        echo '<nav class="nav-tab-wrapper sp-editor-tabs">';

        foreach ( $files as $slug => $file ) {
            $classes = 'nav-tab' . ( $slug === $active ? ' nav-tab-active' : '' );
            $url     = esc_url( add_query_arg( 'file', $slug ) );

            printf(
                '<a href="%1$s" class="%2$s">%3$s</a>',
                $url,
                esc_attr( $classes ),
                esc_html( $file['label'] )
            );
        }

        echo '</nav>';
    }

    /**
     * Render the left sidebar with quick actions and file tree.
     *
     * @param array<string,array> $files
     */
    protected function render_sidebar( array $files, string $active ): void {
        echo '<aside class="sp-file-editor__sidebar">';
        echo '<div class="sp-panel">';
        echo '<h2>' . esc_html__( 'Quick Actions', 'snippet-press' ) . '</h2>';
        echo '<ul class="sp-file-editor__quick-links">';
        echo '<li><a href="#" class="button button-secondary" disabled="disabled">' . esc_html__( 'Upload File (Coming Soon)', 'snippet-press' ) . '</a></li>';
        echo '<li><a href="#" class="button button-secondary" disabled="disabled">' . esc_html__( 'Create New File', 'snippet-press' ) . '</a></li>';
        echo '</ul>';
        echo '</div>';

        echo '<div class="sp-panel">';
        echo '<h2>' . esc_html__( 'Managed Files', 'snippet-press' ) . '</h2>';
        echo '<p class="description">' . esc_html__( 'Choose a file below to preview the upcoming editor experience.', 'snippet-press' ) . '</p>';
        echo '<ul class="sp-file-editor__tree">';
        foreach ( $files as $slug => $file ) {
            $url      = esc_url( add_query_arg( 'file', $slug ) );
            $classes  = 'sp-file-editor__tree-link' . ( $slug === $active ? ' is-active' : '' );
            $label    = esc_html( $file['label'] );
            printf( '<li><a href="%1$s" class="%2$s">%3$s</a></li>', $url, esc_attr( $classes ), $label );
        }
        echo '</ul>';
        echo '</div>';
        echo '</aside>';
    }

    /**
     * Render the editor preview panel.
     *
     * @param array<string,string> $file
     */
    protected function render_editor_panel( array $file ): void {
        echo '<section class="sp-file-editor__content">';
        echo '<div class="sp-panel sp-panel--editor">';
        echo '<header class="sp-panel__header">';
        echo '<h2>' . esc_html( $file['label'] ) . '</h2>';
        echo '<span class="sp-badge sp-badge--soon">' . esc_html__( 'Coming Soon', 'snippet-press' ) . '</span>';
        echo '</header>';

        echo '<div class="sp-panel__body">';
        if ( ! empty( $file['description'] ) ) {
            echo '<p>' . esc_html( $file['description'] ) . '</p>';
        }
        echo '<div class="sp-code-preview">';
        echo '<div class="sp-code-preview__header">';
        echo '<span class="dot dot--red"></span><span class="dot dot--yellow"></span><span class="dot dot--green"></span>';
        echo '<strong>' . esc_html( $file['label'] ) . '</strong>';
        echo '</div>';
        echo '<pre class="sp-code-preview__body">' . esc_html( $file['code'] ) . '</pre>';
        echo '</div>';
        echo '</div>';

        echo '<footer class="sp-panel__footer">';
        echo '<a href="#" class="button button-primary" disabled="disabled">' . esc_html__( 'Enable Editor', 'snippet-press' ) . '</a>';
        echo '</footer>';
        echo '</div>';
        echo '</section>';
    }
}