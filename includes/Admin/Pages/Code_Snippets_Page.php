<?php

namespace SnippetPress\Admin\Pages;

use SnippetPress\Admin\List_Table;
use SnippetPress\Infrastructure\Capabilities;
use SnippetPress\Post_Types\Snippet_Post_Type;

/**
 * Renders the main snippets listing screen.
 */
class Code_Snippets_Page extends Abstract_Admin_Page {
    private const ONBOARDING_META_KEY = 'sp_onboarding_dismissed';

    public function register(): void {
        add_action( 'load-toplevel_page_' . $this->slug(), [ $this, 'register_screen_options' ] );
        add_action( 'admin_post_sp_dismiss_onboarding', [ $this, 'handle_dismiss_onboarding' ] );
    }

    public function slug(): string {
        return 'sp-code-snippet';
    }

    public function title(): string {
        return __( 'Snippet Press', 'snippet-press' );
    }

    public function menu_title(): string {
        return __( 'Snippet Press', 'snippet-press' );
    }

    public function capability(): string {
        return Capabilities::EDIT;
    }

    public function render(): void {
        $this->assert_capability( $this->capability() );

        $list_table = new List_Table();
        $list_table->prepare_items();

        echo '<div class="wrap">';
        echo '<h1 class="wp-heading-inline">' . esc_html__( 'Snippet Press', 'snippet-press' ) . '</h1>';
        echo ' <a href="' . esc_url( admin_url( 'admin.php?page=sp-add-snippet' ) ) . '" class="page-title-action">' . esc_html__( 'Add Snippet', 'snippet-press' ) . '</a>';
        echo '<hr class="wp-header-end" />';

        if ( $this->should_show_onboarding_card() ) {
            $this->render_onboarding_card();
        }

        $list_table->render_type_tabs();
        $list_table->render_tag_filters();
        $list_table->views();

        echo '<form method="get">';
        echo '<input type="hidden" name="page" value="' . esc_attr( $this->slug() ) . '" />';
        $list_table->search_box( __( 'Search Snippets', 'snippet-press' ), 'sp-snippets' );
        echo '<input type="hidden" name="sp_type" value="' . esc_attr( $list_table->current_type() ) . '" />';
        echo '<input type="hidden" name="sp_status" value="' . esc_attr( $list_table->current_status() ) . '" />';
        $current_tags = $list_table->current_tags();
        if ( ! empty( $current_tags ) ) {
            echo '<input type="hidden" name="sp_tags" value="' . esc_attr( implode( ',', $current_tags ) ) . '" />';
        }
        $list_table->display();
        echo '</form>';
        echo '</div>';
    }

    /**
     * Register per-page screen options for the list table.
     */
    public function register_screen_options(): void {
        add_screen_option(
            'per_page',
            [
                'label'   => __( 'Snippets per page', 'snippet-press' ),
                'default' => 20,
                'option'  => 'snippet_press_snippets_per_page',
            ]
        );
    }

    protected function page_url(): string {
        return esc_url_raw( add_query_arg( 'page', $this->slug(), admin_url( 'admin.php' ) ) );
    }

    protected function should_show_onboarding_card(): bool {
        if ( ! current_user_can( Capabilities::EDIT ) ) {
            return false;
        }

        $user_id = get_current_user_id();
        if ( ! $user_id ) {
            return false;
        }

        $dismissed = get_user_meta( $user_id, self::ONBOARDING_META_KEY, true );
        if ( $dismissed ) {
            return false;
        }

        $counts       = wp_count_posts( Snippet_Post_Type::POST_TYPE );
        $status_keys  = [ 'publish', 'draft', 'pending' ];
        $snippet_total = 0;

        foreach ( $status_keys as $key ) {
            if ( isset( $counts->{$key} ) ) {
                $snippet_total += (int) $counts->{$key};
            }
        }

        return $snippet_total === 0;
    }

    protected function render_onboarding_card(): void {
        $redirect = $this->page_url();

        echo '<div class="sp-onboarding-card" role="region" aria-label="' . esc_attr__( 'Getting started checklist', 'snippet-press' ) . '">';
        echo '<div class="sp-onboarding-card__header">';
        echo '<div>';
        echo '<h2>' . esc_html__( 'Welcome to Snippet Press', 'snippet-press' ) . '</h2>';
        echo '<p>' . esc_html__( 'Here are a few quick steps to help you launch your first snippets safely.', 'snippet-press' ) . '</p>';
        echo '</div>';
        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="sp-onboarding-card__dismiss">';
        wp_nonce_field( 'sp_dismiss_onboarding' );
        echo '<input type="hidden" name="action" value="sp_dismiss_onboarding" />';
        echo '<input type="hidden" name="redirect" value="' . esc_attr( $redirect ) . '" />';
        echo '<button type="submit" class="button-link sp-onboarding-card__dismiss-button" aria-label="' . esc_attr__( 'Dismiss getting started checklist', 'snippet-press' ) . '">&times;</button>';
        echo '</form>';
        echo '</div>';

        echo '<ol class="sp-onboarding-card__steps">';
        echo '<li>';
        echo '<span class="sp-onboarding-card__step-title">' . esc_html__( 'Create your first snippet', 'snippet-press' ) . '</span>';
        echo '<span class="sp-onboarding-card__step-desc">' . esc_html__( 'Add a PHP, CSS, or JS snippet and assign the scopes where it should run.', 'snippet-press' ) . '</span>';
        echo '<a class="button button-secondary" href="' . esc_url( admin_url( 'admin.php?page=sp-add-snippet' ) ) . '">' . esc_html__( 'Add Snippet', 'snippet-press' ) . '</a>';
        echo '</li>';

        echo '<li>';
        echo '<span class="sp-onboarding-card__step-title">' . esc_html__( 'Back up before you experiment', 'snippet-press' ) . '</span>';
        echo '<span class="sp-onboarding-card__step-desc">' . esc_html__( 'Export your snippets or run a full site backup so you can roll back quickly.', 'snippet-press' ) . '</span>';
        echo '<a class="button button-secondary" href="' . esc_url( admin_url( 'admin.php?page=sp-tools' ) ) . '">' . esc_html__( 'Open Tools', 'snippet-press' ) . '</a>';
        echo '</li>';

        echo '<li>';
        echo '<span class="sp-onboarding-card__step-title">' . esc_html__( 'Review Safe Mode', 'snippet-press' ) . '</span>';
        echo '<span class="sp-onboarding-card__step-desc">' . esc_html__( 'Safe Mode pauses snippets if something goes wrong. Learn how to toggle it on demand.', 'snippet-press' ) . '</span>';
        echo '<a class="button button-secondary" href="' . esc_url( admin_url( 'admin.php?page=sp-settings&tab=safety' ) ) . '">' . esc_html__( 'Safety Settings', 'snippet-press' ) . '</a>';
        echo '</li>';
        echo '</ol>';
        echo '</div>';
    }

    public function handle_dismiss_onboarding(): void {
        if ( ! current_user_can( Capabilities::EDIT ) ) {
            wp_die( esc_html__( 'You do not have permission to perform this action.', 'snippet-press' ), 403 );
        }

        check_admin_referer( 'sp_dismiss_onboarding' );

        $user_id = get_current_user_id();
        if ( $user_id ) {
            update_user_meta( $user_id, self::ONBOARDING_META_KEY, time() );
        }

        $redirect = isset( $_POST['redirect'] ) ? wp_unslash( (string) $_POST['redirect'] ) : '';
        $target   = $redirect ? $redirect : $this->page_url();

        wp_safe_redirect( $target );
        exit;
    }
}
