<?php

namespace SnippetPress\Admin\Pages;

use SnippetPress\Admin\Notices;
use SnippetPress\Infrastructure\Capabilities;

/**
 * Manage the header/footer injection settings screen.
 */
class Header_Footer_Page extends Abstract_Admin_Page {
    public function register(): void {
        add_action( 'admin_post_sp_save_header_footer', [ $this, 'handle_save' ] );
    }

    public function slug(): string {
        return 'sp-header-footer';
    }

    public function title(): string {
        return __( 'Header & Footer', 'snippet-press' );
    }

    public function capability(): string {
        return Capabilities::MANAGE;
    }

    public function render(): void {
        $this->assert_capability( $this->capability() );

        $settings = $this->settings()->all();
        $header   = isset( $settings['inject_header'] ) ? (string) $settings['inject_header'] : '';
        $body     = isset( $settings['inject_body'] ) ? (string) $settings['inject_body'] : '';
        $footer   = isset( $settings['inject_footer'] ) ? (string) $settings['inject_footer'] : '';

        echo '<div class="wrap sp-header-footer">';
        echo '<h1>' . esc_html__( 'Header & Footer Injection', 'snippet-press' ) . '</h1>';
        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="sp-header-footer__form">';
        wp_nonce_field( 'sp_save_header_footer' );
        echo '<input type="hidden" name="action" value="sp_save_header_footer" />';
        echo '<table class="form-table">';
        $this->render_injection_field( 'sp-header-code', 'sp_header_code', __( 'Header (<head>)', 'snippet-press' ), $header, __( 'Printed inside the <head> tag on the front-end.', 'snippet-press' ) );
        $this->render_injection_field( 'sp-body-code', 'sp_body_code', __( 'Body (after <body>)', 'snippet-press' ), $body, __( 'Output immediately after the opening <body> tag.', 'snippet-press' ) );
        $this->render_injection_field( 'sp-footer-code', 'sp_footer_code', __( 'Footer (before </body>)', 'snippet-press' ), $footer, __( 'Printed before the closing </body> tag.', 'snippet-press' ) );
        echo '</table>';
        submit_button( __( 'Save Changes', 'snippet-press' ) );
        echo '</form>';
        echo '</div>';
    }

    /**
     * Persist header/footer snippets.
     */
    public function handle_save(): void {
        $this->assert_capability( $this->capability() );
        check_admin_referer( 'sp_save_header_footer' );

        $settings         = $this->settings()->all();
        $allow_unfiltered = current_user_can( 'unfiltered_html' );

        $fields = [
            'inject_header' => isset( $_POST['sp_header_code'] ) ? (string) wp_unslash( $_POST['sp_header_code'] ) : '',
            'inject_body'   => isset( $_POST['sp_body_code'] ) ? (string) wp_unslash( $_POST['sp_body_code'] ) : '',
            'inject_footer' => isset( $_POST['sp_footer_code'] ) ? (string) wp_unslash( $_POST['sp_footer_code'] ) : '',
        ];

        foreach ( $fields as $key => $value ) {
            $settings[ $key ] = $allow_unfiltered ? $value : wp_kses_post( $value );
        }

        $this->settings()->save( $settings );
        Notices::add( __( 'Header & Footer code updated.', 'snippet-press' ) );

        wp_safe_redirect( admin_url( 'admin.php?page=sp-header-footer' ) );
        exit;
    }

    /**
     * Render an injection textarea row.
     */
    protected function render_injection_field( string $id, string $name, string $label, string $value, string $description ): void {
        echo '<tr>';
        echo '<th scope="row"><label for="' . esc_attr( $id ) . '">' . esc_html( $label ) . '</label></th>';
        echo '<td>';
        echo '<textarea id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" rows="6" class="large-text code">' . esc_textarea( $value ) . '</textarea>';
        echo '<p class="description">' . esc_html( $description ) . '</p>';
        echo '</td>';
        echo '</tr>';
    }
}
