<?php

namespace SnippetPress\Admin\File_Editor;

use WP_Error;

/**
 * Handles reading and writing of managed files within the File Editor UI.
 */
class File_Manager {
    /**
     * Definitions for managed files keyed by slug.
     *
     * @var array<string,array<string,mixed>>
     */
    private $files = [];

    public function __construct() {
        $root        = trailingslashit( ABSPATH );
        $uploads     = wp_get_upload_dir();
        $uploads_dir = trailingslashit( $uploads['basedir'] ?? WP_CONTENT_DIR . '/uploads' );

        $this->files = [
            'robots-txt' => [
                'label'       => 'robots.txt',
                'description' => __( 'Control how search engines crawl your site while keeping access to critical admin endpoints.', 'snippet-press' ),
                'path'        => $root . 'robots.txt',
                'default'     => "User-agent: *\nDisallow: /wp-admin/\nAllow: /wp-admin/admin-ajax.php\n",
            ],
            'ads-txt' => [
                'label'       => 'ads.txt',
                'description' => __( 'Declare the advertising systems that are authorized to sell your inventory.', 'snippet-press' ),
                'path'        => $root . 'ads.txt',
                'default'     => "example.com, 12345, DIRECT, f08c47fec0942fa0\n",
            ],
            'sitemap-xml' => [
                'label'       => 'sitemap.xml',
                'description' => __( 'Expose your latest content to search engines with a dynamic sitemap.', 'snippet-press' ),
                'path'        => $uploads_dir . 'snippet-press/sitemap.xml',
                'default'     => "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n  <url>\n    <loc>" . esc_url_raw( home_url( '/' ) ) . "</loc>\n    <priority>1.0</priority>\n  </url>\n</urlset>\n",
            ],
            'htaccess' => [
                'label'       => '.htaccess',
                'description' => __( 'Manage redirects and core WordPress rewrite rules without leaving WordPress.', 'snippet-press' ),
                'path'        => $root . '.htaccess',
                'default'     => "# BEGIN WordPress\n<IfModule mod_rewrite.c>\nRewriteEngine On\nRewriteBase /\nRewriteRule ^index\\.php$ - [L]\nRewriteCond %{REQUEST_FILENAME} !-f\nRewriteCond %{REQUEST_FILENAME} !-d\nRewriteRule . /index.php [L]\n</IfModule>\n# END WordPress\n",
            ],
        ];
    }

    /**
     * Return configured files.
     *
     * @return array<string,array>
     */
    public function all(): array {
        return $this->files;
    }

    /**
     * Retrieve metadata for a given file slug.
     */
    public function get( string $slug ): ?array {
        return $this->files[ $slug ] ?? null;
    }

    /**
     * Read file metadata/content.
     *
     * @return array<string,mixed>
     */
    public function read( string $slug, bool $include_content = true ): array {
        $file = $this->get( $slug );

        if ( ! is_array( $file ) ) {
            return [];
        }

        $path    = $file['path'];
        $exists  = file_exists( $path );
        $content = '';

        if ( $include_content ) {
            if ( $exists && is_readable( $path ) ) {
                $content = (string) file_get_contents( $path );
            } else {
                $content = (string) ( $file['default'] ?? '' );
            }
        }

        $writable = $this->is_writable( $path );
        $size     = $exists ? @filesize( $path ) : null;
        $modified = $exists ? @filemtime( $path ) : null;

        return [
            'path'       => $path,
            'exists'     => $exists,
            'writable'   => $writable,
            'size'       => $size ?: null,
            'modified'   => $modified ?: null,
            'content'    => $include_content ? (string) $content : null,
            'default'    => (string) ( $file['default'] ?? '' ),
        ];
    }

    /**
     * Retrieve the default content for a file, if defined.
     */
    public function default_content( string $slug ): ?string {
        $file = $this->get( $slug );

        if ( ! is_array( $file ) || ! array_key_exists( 'default', $file ) ) {
            return null;
        }

        return (string) $file['default'];
    }

    /**
     * Persist new content to disk.
     *
     * @return true|WP_Error
     */
    public function save( string $slug, string $content ) {
        $file = $this->get( $slug );

        if ( ! is_array( $file ) ) {
            return new WP_Error( 'sp_file_editor_invalid_file', __( 'Unknown file selection.', 'snippet-press' ) );
        }

        $path = $file['path'];

        if ( ! $this->is_writable( $path ) ) {
            return new WP_Error( 'sp_file_editor_unwritable', __( 'This file is not writable. Update file permissions and try again.', 'snippet-press' ) );
        }

        $target_dir = dirname( $path );
        if ( ! file_exists( $target_dir ) && ! wp_mkdir_p( $target_dir ) ) {
            return new WP_Error( 'sp_file_editor_dir_unwritable', __( 'Unable to create the directory for this file.', 'snippet-press' ) );
        }

        $normalized = str_replace( [ "\r\n", "\r" ], "\n", $content );

        $result = @file_put_contents( $path, $normalized, LOCK_EX );

        if ( false === $result ) {
            return new WP_Error( 'sp_file_editor_write_failed', __( 'We could not save the file. Check PHP file permissions.', 'snippet-press' ) );
        }

        return true;
    }

    private function is_writable( string $path ): bool {
        if ( file_exists( $path ) ) {
            return is_writable( $path );
        }

        $directory = dirname( $path );

        return wp_is_writable( $directory );
    }
}
