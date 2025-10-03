<?php

namespace SnippetPress\Admin\Pages;

use SnippetPress\Admin\Notices;
use SnippetPress\Infrastructure\Capabilities;
use WP_Query;

/**
 * Provides a basic search & replace interface for snippet posts with history and settings tabs.
 */
class Search_Replace_Page extends Abstract_Admin_Page {
    private const HISTORY_OPTION  = 'snippet_press_search_replace_history';
    private const SETTINGS_OPTION = 'snippet_press_search_replace_settings';

    public function register(): void {
        add_action( 'admin_post_sp_search_replace_run', [ $this, 'handle_run' ] );
        add_action( 'admin_post_sp_search_replace_save_settings', [ $this, 'handle_save_settings' ] );
    }

    public function slug(): string {
        return 'sp-search-replace';
    }

    public function title(): string {
        return __( 'Search & Replace', 'snippet-press' );
    }

    public function capability(): string {
        return Capabilities::ADMIN;
    }

    public function render(): void {
        $this->assert_capability( $this->capability() );

        $tabs = [
            'search'  => __( 'Search & Replace', 'snippet-press' ),
            'history' => __( 'History', 'snippet-press' ),
            'settings'=> __( 'Settings', 'snippet-press' ),
        ];

        $active_tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'search';
        $active_tab = array_key_exists( $active_tab, $tabs ) ? $active_tab : 'search';

        echo '<div class="wrap sp-search-replace-page">';
        echo '<h1 class="wp-heading-inline">' . esc_html__( 'Search & Replace', 'snippet-press' ) . '</h1>';
        echo '<hr class="wp-header-end">';

        $this->render_tabs( $tabs, $active_tab );
        echo '<div class="sp-search-replace__panels">';

        switch ( $active_tab ) {
            case 'history':
                $this->render_history_tab();
                break;
            case 'settings':
                $this->render_settings_tab();
                break;
            case 'search':
            default:
                $this->render_search_tab();
                break;
        }

        echo '</div>';
        echo '</div>';
    }

    /**
     * Render the navigation tabs.
     */
    protected function render_tabs( array $tabs, string $active ): void {
        echo '<nav class="nav-tab-wrapper sp-editor-tabs">';
        foreach ( $tabs as $key => $label ) {
            $classes = 'nav-tab' . ( $key === $active ? ' nav-tab-active' : '' );
            $url     = esc_url( add_query_arg( 'tab', $key ) );
            printf( '<a href="%1$s" class="%2$s">%3$s</a>', $url, esc_attr( $classes ), esc_html( $label ) );
        }
        echo '</nav>';
    }

    /**
     * Render the search & replace form.
     */
    protected function render_search_tab(): void {
        $settings     = $this->get_settings();
        $default_case = ! empty( $settings['default_case_sensitive'] );

        echo '<div class="sp-panel">';
        echo '<h2>' . esc_html__( 'Search & Replace Snippets', 'snippet-press' ) . '</h2>';
        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="sp-search-replace__form">';
        wp_nonce_field( 'sp_search_replace_run' );
        echo '<input type="hidden" name="action" value="sp_search_replace_run" />';

        echo '<div class="field">';
        echo '<label for="sp-search-term">' . esc_html__( 'Search for', 'snippet-press' ) . '</label>';
        echo '<textarea id="sp-search-term" name="search_term" rows="3" class="sp-input" required></textarea>';
        echo '</div>';

        echo '<div class="field">';
        echo '<label for="sp-replace-term">' . esc_html__( 'Replace with', 'snippet-press' ) . '</label>';
        echo '<textarea id="sp-replace-term" name="replace_term" rows="3" class="sp-input"></textarea>';
        echo '</div>';

        echo '<div class="field">';
        echo $this->checkbox_input( 'case_sensitive', '1', $default_case, __( 'Match case', 'snippet-press' ) );
        echo '</div>';

        echo '<p class="description">' . esc_html__( 'This tool scans snippet titles and content. Always take a backup before making bulk edits.', 'snippet-press' ) . '</p>';

        echo '<div class="sp-form-actions">';
        submit_button( esc_html__( 'Run Search & Replace', 'snippet-press' ), 'primary', 'submit', false );
        echo '</div>';

        echo '</form>';
        echo '</div>';
    }

    /**
     * Render history of prior operations.
     */
    protected function render_history_tab(): void {
        $history = $this->get_history();

        echo '<div class="sp-panel">';
        echo '<h2>' . esc_html__( 'Recent Runs', 'snippet-press' ) . '</h2>';

        if ( empty( $history ) ) {
            echo '<p>' . esc_html__( 'No history recorded yet. Run your first search & replace to populate this list.', 'snippet-press' ) . '</p>';
            echo '</div>';
            return;
        }

        echo '<table class="wp-list-table widefat fixed striped sp-search-replace__history-table">';
        echo '<thead><tr>';
        echo '<th>' . esc_html__( 'Date', 'snippet-press' ) . '</th>';
        echo '<th>' . esc_html__( 'Search For', 'snippet-press' ) . '</th>';
        echo '<th>' . esc_html__( 'Replace With', 'snippet-press' ) . '</th>';
        echo '<th class="column-number">' . esc_html__( 'Changes', 'snippet-press' ) . '</th>';
        echo '<th>' . esc_html__( 'Case Sensitive', 'snippet-press' ) . '</th>';
        echo '</tr></thead>';
        echo '<tbody>';
        foreach ( $history as $entry ) {
            $date = isset( $entry['time'] ) ? wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), (int) $entry['time'] ) : '';
            echo '<tr>';
            echo '<td>' . esc_html( $date ) . '</td>';
            echo '<td><code>' . esc_html( $entry['search'] ?? '' ) . '</code></td>';
            echo '<td><code>' . esc_html( $entry['replace'] ?? '' ) . '</code></td>';
            echo '<td class="column-number">' . esc_html( (string) ( $entry['count'] ?? 0 ) ) . '</td>';
            echo '<td>' . ( ! empty( $entry['case_sensitive'] ) ? esc_html__( 'Yes', 'snippet-press' ) : esc_html__( 'No', 'snippet-press' ) ) . '</td>';
            echo '</tr>';
        }
        echo '</tbody>';
        echo '</table>';
        echo '</div>';
    }

    /**
     * Render settings form.
     */
    protected function render_settings_tab(): void {
        $settings = $this->get_settings();

        echo '<div class="sp-panel">';
        echo '<h2>' . esc_html__( 'Defaults', 'snippet-press' ) . '</h2>';
        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="sp-search-replace__form">';
        wp_nonce_field( 'sp_search_replace_save_settings' );
        echo '<input type="hidden" name="action" value="sp_search_replace_save_settings" />';

        echo '<div class="field">';
        echo $this->checkbox_input( 'default_case_sensitive', '1', ! empty( $settings['default_case_sensitive'] ), __( 'Match case by default', 'snippet-press' ) );
        echo '</div>';

        echo '<p class="description">' . esc_html__( 'Controls the default state of the “Match case” toggle on the main Search & Replace tab.', 'snippet-press' ) . '</p>';

        echo '<div class="sp-form-actions">';
        submit_button( esc_html__( 'Save Settings', 'snippet-press' ), 'primary', 'submit', false );
        echo '</div>';

        echo '</form>';
        echo '</div>';
    }

    /**
     * Handle a search & replace submission.
     */
    public function handle_run(): void {
        $this->assert_capability( $this->capability() );
        check_admin_referer( 'sp_search_replace_run' );

        $search  = isset( $_POST['search_term'] ) ? trim( (string) wp_unslash( $_POST['search_term'] ) ) : '';
        $replace = isset( $_POST['replace_term'] ) ? (string) wp_unslash( $_POST['replace_term'] ) : '';
        $case_sensitive = ! empty( $_POST['case_sensitive'] );

        if ( '' === $search ) {
            Notices::add( __( 'Please provide text to search for.', 'snippet-press' ), 'error' );
            $this->redirect_back();
        }

        $query = new WP_Query(
            [
                'post_type'      => 'sp_snippet',
                'post_status'    => [ 'draft', 'pending', 'publish' ],
                'posts_per_page' => -1,
                'fields'         => 'ids',
            ]
        );

        $total_changes = 0;

        foreach ( $query->posts as $post_id ) {
            $post = get_post( $post_id );
            if ( ! $post ) {
                continue;
            }

            $count_title   = 0;
            $count_content = 0;

            $new_title = $case_sensitive
                ? str_replace( $search, $replace, $post->post_title, $count_title )
                : str_ireplace( $search, $replace, $post->post_title, $count_title );

            $new_content = $case_sensitive
                ? str_replace( $search, $replace, $post->post_content, $count_content )
                : str_ireplace( $search, $replace, $post->post_content, $count_content );

            if ( $count_title || $count_content ) {
                $updated = [
                    'ID'           => $post_id,
                    'post_title'   => $new_title,
                    'post_content' => $new_content,
                ];

                $result = wp_update_post( $updated, true );

                if ( is_wp_error( $result ) ) {
                    Notices::add(
                        sprintf(
                            __( 'Failed to update snippet %1$d: %2$s', 'snippet-press' ),
                            $post_id,
                            $result->get_error_message()
                        ),
                        'error'
                    );
                    continue;
                }

                $total_changes += $count_title + $count_content;
            }
        }

        $this->record_history_entry(
            [
                'time'           => time(),
                'search'         => $search,
                'replace'        => $replace,
                'case_sensitive' => $case_sensitive,
                'count'          => $total_changes,
            ]
        );

        if ( $total_changes > 0 ) {
            Notices::add(
                sprintf(
                    __( 'Search & Replace completed. %d replacements were made.', 'snippet-press' ),
                    (int) $total_changes
                ),
                'success'
            );
        } else {
            Notices::add( __( 'No matches were found. Nothing was changed.', 'snippet-press' ), 'warning' );
        }

        wp_safe_redirect( $this->page_url( 'search' ) );
        exit;
    }

    /**
     * Persist settings changes.
     */
    public function handle_save_settings(): void {
        $this->assert_capability( $this->capability() );
        check_admin_referer( 'sp_search_replace_save_settings' );

        $settings = [
            'default_case_sensitive' => ! empty( $_POST['default_case_sensitive'] ),
        ];

        update_option( self::SETTINGS_OPTION, $settings );
        Notices::add( __( 'Search & Replace settings saved.', 'snippet-press' ) );

        wp_safe_redirect( $this->page_url( 'settings' ) );
        exit;
    }

    /**
     * Fetch saved settings.
     */
    protected function get_settings(): array {
        $defaults = [
            'default_case_sensitive' => false,
        ];

        $stored = get_option( self::SETTINGS_OPTION );
        return is_array( $stored ) ? wp_parse_args( $stored, $defaults ) : $defaults;
    }

    /**
     * Fetch history entries.
     *
     * @return array<int,array<string,mixed>>
     */
    protected function get_history(): array {
        $history = get_option( self::HISTORY_OPTION, [] );
        return is_array( $history ) ? $history : [];
    }

    /**
     * Record an entry into history keeping the list manageable.
     */
    protected function record_history_entry( array $entry ): void {
        $history = $this->get_history();
        array_unshift( $history, $entry );
        $history = array_slice( $history, 0, 20 );
        update_option( self::HISTORY_OPTION, $history );
    }

    /**
     * Redirect back to the main page and halt execution.
     */
    protected function redirect_back(): void {
        wp_safe_redirect( $this->page_url( 'search' ) );
        exit;
    }

    /**
     * Build a page URL with a tab parameter.
     */
    protected function page_url( string $tab ): string {
        return esc_url_raw(
            add_query_arg(
                [
                    'page' => $this->slug(),
                    'tab'  => $tab,
                ],
                admin_url( 'admin.php' )
            )
        );
    }
}