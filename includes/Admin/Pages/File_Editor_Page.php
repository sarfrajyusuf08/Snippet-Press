<?php

namespace SnippetPress\Admin\Pages;

use SnippetPress\Admin\File_Editor\File_Manager;
use SnippetPress\Admin\Notices;
use SnippetPress\Infrastructure\Capabilities;
use SnippetPress\Infrastructure\Service_Container;
use WP_Error;

/**
 * Provides an editor experience for a curated list of public-facing files.
 */
class File_Editor_Page extends Abstract_Admin_Page {
    /**
     * File manager service instance.
     *
     * @var File_Manager
     */
    private $files;

    public function __construct( Service_Container $container ) {
        parent::__construct( $container );
        $this->files = new File_Manager();
    }

    public function register(): void {
        add_action( 'admin_post_sp_file_editor', [ $this, 'handle_post_action' ] );
        add_action( 'admin_init', [ $this, 'handle_download_request' ] );
    }

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

        $definitions = $this->files->all();

        if ( empty( $definitions ) ) {
            echo '<div class="wrap sp-file-editor-page">';
            echo '<h1 class="wp-heading-inline">' . esc_html( $this->title() ) . '</h1>';
            echo '<hr class="wp-header-end">';
            echo '<div class="notice notice-warning"><p>' . esc_html__( 'There are no managed files configured yet.', 'snippet-press' ) . '</p></div>';
            echo '</div>';
            return;
        }

        $requested = isset( $_GET['file'] ) ? sanitize_key( wp_unslash( $_GET['file'] ) ) : '';
        $active    = isset( $definitions[ $requested ] ) ? $requested : array_key_first( $definitions );

        $state = $this->files->read( $active );

        echo '<div class="wrap sp-file-editor-page">';
        echo '<h1 class="wp-heading-inline">' . esc_html( $this->title() ) . '</h1>';
        echo '<hr class="wp-header-end">';

        $this->render_tabs( $definitions, $active );
        $this->render_editor_panel( $active, $definitions[ $active ], $state );
        echo '</div>';
    }

    public function handle_post_action(): void {
        $this->assert_capability( $this->capability() );

        $definitions = $this->files->all();
        $slug        = isset( $_POST['sp_file_slug'] ) ? sanitize_key( wp_unslash( $_POST['sp_file_slug'] ) ) : '';
        $action      = isset( $_POST['sp_file_editor_action'] ) ? sanitize_key( wp_unslash( $_POST['sp_file_editor_action'] ) ) : 'save';

        if ( ! isset( $definitions[ $slug ] ) ) {
            Notices::add( __( 'Invalid file selection.', 'snippet-press' ), 'error' );
            $this->redirect_after_action();
        }

        if ( 'restore' === $action ) {
            check_admin_referer( 'sp_file_editor_restore', 'sp_file_editor_restore_nonce' );

            $default = $this->files->default_content( $slug );

            if ( null === $default || '' === trim( (string) $default ) ) {
                Notices::add( __( 'No default content is available for this file.', 'snippet-press' ), 'error' );
                $this->redirect_after_action( $slug );
            }

            $result = $this->files->save( $slug, (string) $default );

            if ( is_wp_error( $result ) ) {
                $this->handle_error_notice( $result );
            } else {
                Notices::add( __( 'Default content restored.', 'snippet-press' ), 'success' );
            }

            $this->redirect_after_action( $slug );
        }

        check_admin_referer( 'sp_file_editor_save', 'sp_file_editor_nonce' );

        $raw_content = isset( $_POST['sp_file_contents'] ) ? wp_unslash( $_POST['sp_file_contents'] ) : '';
        $result      = $this->files->save( $slug, $raw_content );

        if ( is_wp_error( $result ) ) {
            $this->handle_error_notice( $result );
        } else {
            Notices::add( __( 'File saved successfully.', 'snippet-press' ), 'success' );
        }

        $this->redirect_after_action( $slug );
    }

    public function handle_download_request(): void {
        if ( ! isset( $_GET['page'] ) || $this->slug() !== sanitize_key( wp_unslash( $_GET['page'] ) ) ) {
            return;
        }

        if ( ! isset( $_GET['sp_file_action'] ) || 'download' !== sanitize_key( wp_unslash( $_GET['sp_file_action'] ) ) ) {
            return;
        }

        $this->assert_capability( $this->capability() );

        $definitions = $this->files->all();
        $slug        = isset( $_GET['file'] ) ? sanitize_key( wp_unslash( $_GET['file'] ) ) : '';
        $nonce       = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';

        if ( ! isset( $definitions[ $slug ] ) ) {
            Notices::add( __( 'Invalid file selection.', 'snippet-press' ), 'error' );
            $this->redirect_after_action();
        }

        if ( ! wp_verify_nonce( $nonce, 'sp_file_editor_download_' . $slug ) ) {
            wp_die( esc_html__( 'Invalid download link.', 'snippet-press' ), 403 );
        }

        $state    = $this->files->read( $slug );
        $content  = (string) ( $state['content'] ?? '' );
        $fallback = (string) ( $state['default'] ?? '' );

        if ( '' === trim( $content ) && '' !== trim( $fallback ) ) {
            $content = $fallback;
        }

        if ( '' === trim( $content ) ) {
            Notices::add( __( 'No content is available for download.', 'snippet-press' ), 'error' );
            $this->redirect_after_action( $slug );
        }

        $label    = $definitions[ $slug ]['label'] ?? ( $slug . '.txt' );
        $filename = sanitize_file_name( $label );

        nocache_headers();
        header( 'Content-Type: text/plain; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
        header( 'Content-Length: ' . strlen( $content ) );
        echo $content;
        exit;
    }

    private function handle_error_notice( WP_Error $error ): void {
        $message = $error->get_error_message() ?: __( 'We could not save the file. Please try again.', 'snippet-press' );
        Notices::add( $message, 'error' );
    }

    private function redirect_after_action( string $slug = '' ): void {
        $args = [ 'page' => $this->slug() ];

        if ( '' !== $slug ) {
            $args['file'] = $slug;
        }

        $url = add_query_arg( $args, admin_url( 'admin.php' ) );

        if ( headers_sent() ) {
            printf( '<script>window.location.href = %s;</script>', wp_json_encode( $url ) );
            printf( '<noscript><meta http-equiv="refresh" content="0;url=%s"></noscript>', esc_url( $url ) );
            exit;
        }

        wp_safe_redirect( $url );
        exit;
    }

    /**
     * Render tab navigation.
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
     * Render the editor panel for the active file.
     *
     * @param string               $slug
     * @param array<string,string> $definition
     * @param array<string,mixed>  $state
     */
    protected function render_editor_panel( string $slug, array $definition, array $state ): void {
        $writable = ! empty( $state['writable'] );
        $path     = (string) ( $state['path'] ?? '' );
        $exists   = ! empty( $state['exists'] );
        $size     = $state['size'] ?? null;
        $modified = $state['modified'] ?? null;
        $content  = (string) ( $state['content'] ?? '' );

        echo '<section class="sp-file-editor__content">';
        echo '<div class="sp-panel sp-panel--editor">';
        echo '<header class="sp-panel__header sp-panel__header--file">';
        echo '<div class="sp-panel__title">';
        echo '<h2>' . esc_html( $definition['label'] ) . '</h2>';
        if ( ! $writable ) {
            echo '<span class="sp-badge sp-badge--soon">' . esc_html__( 'Read Only', 'snippet-press' ) . '</span>';
        }
        echo '</div>';
        echo $this->render_quick_actions( $slug, $state );
        echo '</header>';

        echo '<div class="sp-panel__body">';
        if ( ! empty( $definition['description'] ) ) {
            echo '<p>' . esc_html( $definition['description'] ) . '</p>';
        }

        echo '<ul class="sp-file-editor__meta">';
        echo '<li><strong>' . esc_html__( 'Path', 'snippet-press' ) . '</strong><span>' . esc_html( $path ) . '</span></li>';
        if ( $exists ) {
            $size_label = is_null( $size ) ? __( 'Unknown', 'snippet-press' ) : size_format( (float) $size );
            $time_label = is_null( $modified ) ? __( 'Unknown', 'snippet-press' ) : date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), (int) $modified );
            echo '<li><strong>' . esc_html__( 'Last modified', 'snippet-press' ) . '</strong><span>' . esc_html( $time_label ) . '</span></li>';
            echo '<li><strong>' . esc_html__( 'File size', 'snippet-press' ) . '</strong><span>' . esc_html( $size_label ) . '</span></li>';
        } else {
            echo '<li><strong>' . esc_html__( 'Status', 'snippet-press' ) . '</strong><span>' . esc_html__( 'This file will be created when you save changes.', 'snippet-press' ) . '</span></li>';
        }
        echo '</ul>';

        if ( ! $writable ) {
            echo '<div class="notice notice-warning inline"><p>' . esc_html__( 'This file is not currently writable. Update file permissions or contact your host to enable editing.', 'snippet-press' ) . '</p></div>';
        }

        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="sp-file-editor__form">';
        wp_nonce_field( 'sp_file_editor_save', 'sp_file_editor_nonce' );
        echo '<input type="hidden" name="action" value="sp_file_editor">';
        echo '<input type="hidden" name="sp_file_editor_action" value="save">';
        echo '<input type="hidden" name="sp_file_slug" value="' . esc_attr( $slug ) . '">';

        if ( '' === trim( $content ) && ! empty( $state['default'] ) ) {
            $content = (string) $state['default'];
        }

        echo '<textarea name="sp_file_contents" class="sp-code-editor" rows="18"' . ( $writable ? '' : ' readonly="readonly"' ) . '>' . esc_textarea( $content ) . '</textarea>';

        echo '<div class="sp-form-actions">';
        if ( $writable ) {
            submit_button( __( 'Save Changes', 'snippet-press' ), 'primary', 'sp_file_editor_save', false );
        } else {
            echo '<button type="button" class="button button-primary" disabled="disabled">' . esc_html__( 'Save Changes', 'snippet-press' ) . '</button>';
        }
        echo '</div>';
        echo '</form>';
        echo '</div>';

        echo '</div>';
        echo '</section>';
    }

    private function render_quick_actions( string $slug, array $state ): string {
        $exists           = ! empty( $state['exists'] );
        $download_enabled = $exists || '' !== trim( (string) ( $state['content'] ?? '' ) ) || '' !== trim( (string) ( $state['default'] ?? '' ) );
        $restore_enabled  = '' !== trim( (string) ( $state['default'] ?? '' ) ) && ! empty( $state['writable'] );

        $download_url = $download_enabled
            ? wp_nonce_url(
                add_query_arg(
                    [
                        'page'           => $this->slug(),
                        'file'           => $slug,
                        'sp_file_action' => 'download',
                    ],
                    admin_url( 'admin.php' )
                ),
                'sp_file_editor_download_' . $slug
            )
            : '';

        $confirm_message = esc_js( __( 'Restore the default content for this file? This will overwrite the current contents.', 'snippet-press' ) );

        ob_start();
        echo '<div class="sp-quick-actions-card">';
        echo '<h3>' . esc_html__( 'Quick Actions', 'snippet-press' ) . '</h3>';
        echo '<div class="sp-quick-actions-card__body">';

        if ( $download_enabled && $download_url ) {
            echo '<a class="button button-secondary" href="' . esc_url( $download_url ) . '">' . esc_html__( 'Download File', 'snippet-press' ) . '</a>';
        } else {
            echo '<button type="button" class="button button-secondary" disabled="disabled">' . esc_html__( 'Download File', 'snippet-press' ) . '</button>';
        }

        if ( $restore_enabled ) {
            echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="sp-quick-actions-card__form">';
            wp_nonce_field( 'sp_file_editor_restore', 'sp_file_editor_restore_nonce' );
            echo '<input type="hidden" name="action" value="sp_file_editor">';
            echo '<input type="hidden" name="sp_file_editor_action" value="restore">';
            echo '<input type="hidden" name="sp_file_slug" value="' . esc_attr( $slug ) . '">';
            echo '<button type="submit" class="button button-secondary" onclick="return confirm(\'' . $confirm_message . '\');">' . esc_html__( 'Restore Default', 'snippet-press' ) . '</button>';
            echo '</form>';
        } else {
            echo '<button type="button" class="button button-secondary" disabled="disabled">' . esc_html__( 'Restore Default', 'snippet-press' ) . '</button>';
        }

        echo '</div>';
        echo '</div>';

        return (string) ob_get_clean();
    }
}
