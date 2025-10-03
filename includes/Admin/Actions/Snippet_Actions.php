<?php

namespace SnippetPress\Admin\Actions;

use SnippetPress\Admin\Notices;
use SnippetPress\Admin\Snippet_Service;

/**
 * Coordinates admin-post actions and bulk operations for snippets.
 */
class Snippet_Actions {
    private $list_page_slug;

    public function __construct( string $list_page_slug = 'sp-code-snippet' ) {
        $this->list_page_slug = $list_page_slug;
    }

    public function register(): void {
        add_action( 'admin_init', [ $this, 'maybe_handle_snippet_actions' ] );

        add_action( 'admin_post_sp_enable_snippet', [ $this, 'handle_toggle_snippet' ] );
        add_action( 'admin_post_sp_disable_snippet', [ $this, 'handle_toggle_snippet' ] );
        add_action( 'admin_post_sp_duplicate_snippet', [ $this, 'handle_duplicate_snippet' ] );
        add_action( 'admin_post_sp_delete_snippet', [ $this, 'handle_delete_snippet' ] );
        add_action( 'admin_post_sp_export_snippet', [ $this, 'handle_export_snippet' ] );
    }

    public function maybe_handle_snippet_actions(): void {
        $page = isset( $_REQUEST['page'] ) ? sanitize_key( wp_unslash( $_REQUEST['page'] ) ) : '';

        if ( $this->list_page_slug !== $page ) {
            return;
        }

        if ( empty( $_POST ) || ! isset( $_POST['snippet_ids'] ) ) {
            return;
        }

        $this->handle_bulk_action();
    }

    protected function handle_bulk_action(): void {
        $primary   = isset( $_POST['action'] ) ? sanitize_key( wp_unslash( $_POST['action'] ) ) : '';
        $secondary = isset( $_POST['action2'] ) ? sanitize_key( wp_unslash( $_POST['action2'] ) ) : '';
        $action    = '-1' !== $primary ? $primary : $secondary;

        if ( '' === $action || '-1' === $action ) {
            return;
        }

        if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['_wpnonce'] ), 'bulk-snippets' ) ) {
            Notices::add( __( 'Security check failed. Please try again.', 'snippet-press' ), 'error' );
            $this->redirect_to_listing();
        }

        $ids = isset( $_POST['snippet_ids'] ) ? array_map( 'absint', (array) $_POST['snippet_ids'] ) : [];

        if ( empty( $ids ) ) {
            Notices::add( __( 'No snippets were selected.', 'snippet-press' ), 'warning' );
            $this->redirect_to_listing();
        }

        switch ( $action ) {
            case 'enable':
                $this->handle_service_result( Snippet_Service::enable( $ids ), __( 'Snippets enabled.', 'snippet-press' ) );
                $this->redirect_to_listing();
                break;
            case 'disable':
                $this->handle_service_result( Snippet_Service::disable( $ids ), __( 'Snippets disabled.', 'snippet-press' ) );
                $this->redirect_to_listing();
                break;
            case 'delete':
                $this->handle_service_result( Snippet_Service::delete( $ids ), __( 'Snippets deleted.', 'snippet-press' ) );
                $this->redirect_to_listing();
                break;
            case 'export':
                wp_safe_redirect( $this->build_export_redirect( $ids ) );
                exit;
        }
    }

    protected function build_export_redirect( array $ids ): string {
        return add_query_arg(
            [
                'action'   => 'sp_export_snippet',
                'ids'      => implode( ',', array_map( 'absint', $ids ) ),
                '_wpnonce' => wp_create_nonce( 'sp_export_snippet' ),
            ],
            admin_url( 'admin-post.php' )
        );
    }

    protected function handle_service_result( array $result, string $success ): void {
        if ( ! empty( $result['processed'] ) ) {
            Notices::add( sprintf( '%s (%d)', $success, (int) $result['processed'] ), 'success' );
        }

        if ( ! empty( $result['errors'] ) ) {
            foreach ( $result['errors'] as $error ) {
                Notices::add( $error, 'error' );
            }
        }
    }

    protected function redirect_to_listing(): void {
        $referer = wp_get_referer();
        $target  = $referer && false !== strpos( $referer, $this->list_page_slug ) ? $referer : admin_url( 'admin.php?page=' . $this->list_page_slug );

        wp_safe_redirect( $target );
        exit;
    }

    public function handle_toggle_snippet(): void {
        $data   = $this->verify_admin_post_request();
        $status = 'admin_post_sp_enable_snippet' === current_action() ? 'enabled' : 'disabled';

        $result  = 'enabled' === $status ? Snippet_Service::enable( [ $data['id'] ] ) : Snippet_Service::disable( [ $data['id'] ] );
        $message = 'enabled' === $status ? __( 'Snippet enabled.', 'snippet-press' ) : __( 'Snippet disabled.', 'snippet-press' );

        $this->handle_service_result( $result, $message );
        $this->redirect_after_action( $data['redirect'] );
    }

    public function handle_duplicate_snippet(): void {
        $data = $this->verify_admin_post_request();
        $new  = Snippet_Service::duplicate( $data['id'] );

        if ( is_wp_error( $new ) ) {
            Notices::add( $new->get_error_message(), 'error' );
            $this->redirect_after_action( $data['redirect'] );
        }

        Notices::add( __( 'Snippet duplicated. Edit the new draft to begin customizing it.', 'snippet-press' ), 'success' );
        $this->redirect_after_action( get_edit_post_link( $new, 'url' ) );
    }

    public function handle_delete_snippet(): void {
        $data   = $this->verify_admin_post_request();
        $result = Snippet_Service::delete( [ $data['id'] ] );
        $this->handle_service_result( $result, __( 'Snippet deleted.', 'snippet-press' ) );
        $this->redirect_after_action( $data['redirect'] );
    }

    public function handle_export_snippet(): void {
        $nonce = isset( $_REQUEST['_wpnonce'] ) ? wp_unslash( $_REQUEST['_wpnonce'] ) : '';

        if ( ! wp_verify_nonce( $nonce, 'sp_export_snippet' ) ) {
            wp_die( esc_html__( 'Invalid export request.', 'snippet-press' ), 403 );
        }

        $ids_param = isset( $_REQUEST['ids'] ) ? wp_unslash( $_REQUEST['ids'] ) : '';
        $ids       = array_filter( array_map( 'absint', explode( ',', (string) $ids_param ) ) );

        if ( empty( $ids ) ) {
            wp_die( esc_html__( 'No snippets selected for export.', 'snippet-press' ), 400 );
        }

        $data = [
            'version'    => SNIPPET_PRESS_VERSION,
            'exportedAt' => gmdate( 'c' ),
            'snippets'   => Snippet_Service::prepare_export_data( $ids ),
        ];

        nocache_headers();
        header( 'Content-Type: application/json; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename=snippet-press-export-' . gmdate( 'Ymd-His' ) . '.json' );
        echo wp_json_encode( $data, JSON_PRETTY_PRINT );
        exit;
    }

    protected function verify_admin_post_request(): array {
        $id       = isset( $_REQUEST['snippet_id'] ) ? absint( $_REQUEST['snippet_id'] ) : 0;
        $nonce    = isset( $_REQUEST['_wpnonce'] ) ? wp_unslash( $_REQUEST['_wpnonce'] ) : '';
        $action   = current_action();
        $action   = str_replace( [ 'admin_post_', 'sp_', '_snippet' ], '', $action );
        $expected = 'sp_snippet_action_' . $action . '_' . $id;

        if ( ! $id || ! wp_verify_nonce( $nonce, $expected ) ) {
            wp_die( esc_html__( 'Invalid request.', 'snippet-press' ), 403 );
        }

        $redirect = isset( $_REQUEST['redirect'] ) ? esc_url_raw( wp_unslash( $_REQUEST['redirect'] ) ) : '';

        return [
            'id'       => $id,
            'redirect' => $redirect,
        ];
    }

    protected function redirect_after_action( string $redirect ): void {
        if ( ! $redirect ) {
            $redirect = admin_url( 'admin.php?page=' . $this->list_page_slug );
        }

        wp_safe_redirect( $redirect );
        exit;
    }
}