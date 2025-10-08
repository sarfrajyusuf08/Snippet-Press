<?php

namespace SnippetPress\Admin\Pages;

use SnippetPress\Admin\Notices;
use SnippetPress\Infrastructure\Capabilities;
use SnippetPress\Safety\Safe_Mode_Manager;

/**
 * Renders the Snippet Press settings dashboard with categorized tabs.
 */
class Settings_Page extends Abstract_Admin_Page {
    private const TAB_GENERAL  = 'general';
    private const TAB_SAFETY   = 'safety';
    private const TAB_LINTING  = 'linting';
    private const TAB_ADVANCED = 'advanced';
    private const TAB_LOGGING  = 'logging';

    public function register(): void {
        add_action( 'admin_post_sp_save_settings', [ $this, 'handle_save' ] );
        add_action( 'admin_post_sp_reset_settings', [ $this, 'handle_reset' ] );
    }

    public function slug(): string {
        return 'sp-settings';
    }

    public function title(): string {
        return __( 'Settings', 'snippet-press' );
    }

    public function capability(): string {
        return Capabilities::ADMIN;
    }

    public function render(): void {
        $this->assert_capability( $this->capability() );

        $settings = $this->settings()->all();
        $scopes   = $this->available_scopes();

        $tabs = [
            self::TAB_GENERAL  => __( 'General', 'snippet-press' ),
            self::TAB_SAFETY   => __( 'Safety', 'snippet-press' ),
            self::TAB_LINTING  => __( 'Linting', 'snippet-press' ),
            self::TAB_ADVANCED => __( 'Advanced', 'snippet-press' ),
            self::TAB_LOGGING  => __( 'Logging', 'snippet-press' ),
        ];

        $active = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : self::TAB_GENERAL;
        $active = $this->validate_tab( $active );

        echo '<div class="wrap sp-settings-page">';
        echo '<h1 class="wp-heading-inline">' . esc_html__( 'Snippet Press Settings', 'snippet-press' ) . '</h1>';
        echo '<hr class="wp-header-end">';

        $this->render_tabs( $tabs, $active );

        echo '<div class="sp-settings-tab-content">';

        switch ( $active ) {
            case self::TAB_SAFETY:
                $this->render_safety_tab( $settings, $active );
                break;
            case self::TAB_LINTING:
                $this->render_linting_tab( $settings, $active );
                break;
            case self::TAB_ADVANCED:
                $this->render_advanced_tab( $settings, $active );
                break;
            case self::TAB_LOGGING:
                $this->render_logging_tab( $settings, $active );
                break;
            case self::TAB_GENERAL:
            default:
                $this->render_general_tab( $settings, $scopes, $active );
                break;
        }

        echo '</div>';
        echo '</div>';
        echo '</div>';
    }

    protected function render_tabs( array $tabs, string $active ): void {
        echo '<nav class="nav-tab-wrapper sp-editor-tabs">';
        foreach ( $tabs as $key => $label ) {
            $classes = 'nav-tab' . ( $key === $active ? ' nav-tab-active' : '' );
            $url     = esc_url( $this->page_url( $key ) );
            printf( '<a href="%1$s" class="%2$s">%3$s</a>', $url, esc_attr( $classes ), esc_html( $label ) );
        }
        echo '</nav>';
    }

    protected function render_general_tab( array $settings, array $scopes, string $active ): void {
        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="sp-settings-form sp-search-replace__form">';
        wp_nonce_field( 'sp_save_settings' );
        echo '<input type="hidden" name="action" value="sp_save_settings" />';
        echo '<input type="hidden" name="settings_context" value="' . esc_attr( self::TAB_GENERAL ) . '" />';
        echo '<input type="hidden" name="redirect_tab" value="' . esc_attr( $active ) . '" />';

        echo '<div class="sp-panel">';
        echo '<h2>' . esc_html__( 'General Defaults', 'snippet-press' ) . '</h2>';
        echo '<p class="description">' . esc_html__( 'Control the initial state and visibility applied to every new snippet you create.', 'snippet-press' ) . '</p>';

        echo '<div class="field">';
        echo '<label for="sp-default-status">' . esc_html__( 'Default snippet status', 'snippet-press' ) . '</label>';
        echo '<select id="sp-default-status" name="default_status" class="sp-input">';
        $options = [
            'enabled'  => __( 'Enabled (publish immediately)', 'snippet-press' ),
            'disabled' => __( 'Disabled (save as draft)', 'snippet-press' ),
        ];
        foreach ( $options as $value => $label ) {
            printf( '<option value="%1$s" %2$s>%3$s</option>', esc_attr( $value ), selected( $settings['default_status'], $value, false ), esc_html( $label ) );
        }
        echo '</select>';
        echo '</div>';

        echo '<div class="field">';
        echo '<label>' . esc_html__( 'Default scopes', 'snippet-press' ) . '</label>';
        echo '<div class="sp-checkbox-grid">';
        foreach ( $scopes as $slug => $label ) {
            $checked = in_array( $slug, (array) $settings['default_scopes'], true );
            echo $this->checkbox_input( 'default_scopes[]', $slug, $checked, $label );
        }
        echo '</div>';
        echo '</div>';
        echo '</div>';

        echo '<div class="sp-form-actions">';
        submit_button( __( 'Save Changes', 'snippet-press' ), 'primary', 'submit', false );
        echo $this->render_reset_button( $active );
        echo '</div>';
        echo '</form>';
    }

    protected function render_safety_tab( array $settings, string $active ): void {
        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="sp-settings-form sp-search-replace__form">';
        wp_nonce_field( 'sp_save_settings' );
        echo '<input type="hidden" name="action" value="sp_save_settings" />';
        echo '<input type="hidden" name="settings_context" value="' . esc_attr( self::TAB_SAFETY ) . '" />';
        echo '<input type="hidden" name="redirect_tab" value="' . esc_attr( $active ) . '" />';

        echo '<div class="sp-panel">';
        echo '<h2>' . esc_html__( 'Safe Mode', 'snippet-press' ) . '</h2>';
        echo '<p class="description">' . esc_html__( 'Keep your site accessible if a snippet causes a fatal error. Safe mode pauses all snippets until you review them.', 'snippet-press' ) . '</p>';
        echo '<div class="field">';
        echo $this->checkbox_input( 'safe_mode_enabled', '1', ! empty( $settings['safe_mode_enabled'] ), __( 'Automatically enable safe mode after a crash', 'snippet-press' ) );
        echo '</div>';
        echo '</div>';

        echo '<div class="sp-form-actions">';
        submit_button( __( 'Save Changes', 'snippet-press' ), 'primary', 'submit', false );
        echo '</div>';
        echo '</form>';

        $state      = get_option( Safe_Mode_Manager::SAFE_MODE_OPTION, [] );
        $state      = is_array( $state ) ? $state : [];
        $is_active  = ! empty( $state['enabled'] );
        $snippet_id = isset( $state['snippet_id'] ) ? (int) $state['snippet_id'] : 0;
        $last_error = isset( $state['error'] ) ? wp_strip_all_tags( (string) $state['error'] ) : '';
        $timestamp  = isset( $state['timestamp'] ) ? (int) $state['timestamp'] : 0;
        $redirect   = $this->page_url( self::TAB_SAFETY );

        echo '<div class="sp-panel sp-safe-mode-controls">';
        echo '<h2>' . esc_html__( 'Safe Mode Controls', 'snippet-press' ) . '</h2>';
        echo '<p class="description">' . esc_html__( 'Toggle safe mode manually when you need to pause snippet execution before trying risky changes.', 'snippet-press' ) . '</p>';

        echo '<div class="field">';
        echo '<p><strong>' . esc_html__( 'Current status:', 'snippet-press' ) . '</strong> ' . esc_html( $is_active ? __( 'Active', 'snippet-press' ) : __( 'Inactive', 'snippet-press' ) ) . '</p>';

        if ( $is_active ) {
            echo '<p>' . esc_html__( 'Snippets are paused until you disable safe mode.', 'snippet-press' ) . '</p>';

            if ( $snippet_id ) {
                $title     = get_the_title( $snippet_id );
                $edit_link = get_edit_post_link( $snippet_id, 'display' );

                if ( $title ) {
                    echo '<p><strong>' . esc_html__( 'Affected snippet:', 'snippet-press' ) . '</strong> ';

                    if ( $edit_link ) {
                        printf( '<a href="%1$s">%2$s</a>', esc_url( $edit_link ), esc_html( $title ) );
                    } else {
                        echo esc_html( $title );
                    }

                    echo '</p>';
                }
            }

            if ( $last_error !== '' ) {
                echo '<p><strong>' . esc_html__( 'Last captured error:', 'snippet-press' ) . '</strong> <code>' . esc_html( $last_error ) . '</code></p>';
            }

            if ( $timestamp > 0 ) {
                $format = sprintf( '%s %s', get_option( 'date_format' ), get_option( 'time_format' ) );
                echo '<p><strong>' . esc_html__( 'Entered:', 'snippet-press' ) . '</strong> ' . esc_html( wp_date( $format, $timestamp ) ) . '</p>';
            }
        } else {
            echo '<p>' . esc_html__( 'Snippets run normally. Enable safe mode to pause execution before testing changes.', 'snippet-press' ) . '</p>';
        }
        echo '</div>';

        echo '<div class="sp-form-actions">';

        if ( $is_active ) {
            echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="sp-safe-mode-toggle">';
            wp_nonce_field( 'sp_exit_safe_mode' );
            echo '<input type="hidden" name="action" value="sp_exit_safe_mode" />';
            echo '<input type="hidden" name="redirect_to" value="' . esc_attr( $redirect ) . '" />';
            submit_button( __( 'Disable Safe Mode', 'snippet-press' ), 'primary', 'submit', false );
            echo '</form>';
        } else {
            echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="sp-safe-mode-toggle">';
            wp_nonce_field( 'sp_enable_safe_mode' );
            echo '<input type="hidden" name="action" value="sp_enable_safe_mode" />';
            echo '<input type="hidden" name="redirect_to" value="' . esc_attr( $redirect ) . '" />';
            submit_button( __( 'Enable Safe Mode', 'snippet-press' ), 'secondary', 'submit', false );
            echo '</form>';
        }

        echo '</div>';
        echo '</div>';
<<<<<<< HEAD

        $manager     = $this->container()->get( Safe_Mode_Manager::class );
        $log_entries = $manager instanceof Safe_Mode_Manager ? $manager->get_log() : [];

        echo '<div class="sp-panel sp-safe-mode-log">';
        echo '<h2>' . esc_html__( 'Recent Safe Mode Activity', 'snippet-press' ) . '</h2>';

        if ( empty( $log_entries ) ) {
            echo '<p class="description">' . esc_html__( 'Safe mode has not been triggered yet.', 'snippet-press' ) . '</p>';
        } else {
            echo '<p class="description">' . esc_html__( 'Track when safe mode was enabled or disabled and which snippet caused it.', 'snippet-press' ) . '</p>';
            echo '<div class="sp-table-wrapper">';
            echo '<table class="widefat striped sp-safe-mode-log__table">';
            echo '<thead><tr>';
            echo '<th scope="col">' . esc_html__( 'When', 'snippet-press' ) . '</th>';
            echo '<th scope="col">' . esc_html__( 'Trigger', 'snippet-press' ) . '</th>';
            echo '<th scope="col">' . esc_html__( 'Snippet', 'snippet-press' ) . '</th>';
            echo '<th scope="col">' . esc_html__( 'Details', 'snippet-press' ) . '</th>';
            echo '</tr></thead><tbody>';

            foreach ( $log_entries as $entry ) {
                $timestamp  = isset( $entry['timestamp'] ) ? (int) $entry['timestamp'] : 0;
                $trigger    = isset( $entry['trigger'] ) ? (string) $entry['trigger'] : '';
                $snippet_id = isset( $entry['snippet_id'] ) ? (int) $entry['snippet_id'] : 0;
                $error      = isset( $entry['error'] ) ? (string) $entry['error'] : '';
                $message    = isset( $entry['message'] ) ? (string) $entry['message'] : '';
                $url        = isset( $entry['current_url'] ) ? (string) $entry['current_url'] : '';

                $trigger_label = $this->log_trigger_label( $trigger );
                $time_display  = $timestamp ? esc_html( wp_date( sprintf( '%s %s', get_option( 'date_format' ), get_option( 'time_format' ) ), $timestamp ) ) : '&#8212;';

                $snippet_cell = '&#8212;';

                if ( $snippet_id ) {
                    $title = get_the_title( $snippet_id );

                    if ( $title ) {
                        $link = get_edit_post_link( $snippet_id, 'display' );
                        $snippet_cell = $link ? sprintf( '<a href="%1$s">%2$s</a>', esc_url( $link ), esc_html( $title ) ) : esc_html( $title );
                    }
                }

                $details = '';

                if ( '' !== $message ) {
                    $details .= esc_html( $message );
                }

                if ( '' !== $error ) {
                    if ( '' !== $details ) {
                        $details .= '<br>';
                    }

                    $details .= '<code>' . esc_html( $error ) . '</code>';
                }

                if ( '' !== $url ) {
                    if ( '' !== $details ) {
                        $details .= '<br>';
                    }

                    $details .= sprintf( '<span class="sp-safe-mode-log__url">%s</span>', esc_html( $url ) );
                }

                if ( '' === $details ) {
                    $details = '&#8212;';
                }

                echo '<tr>';
                echo '<td>' . $time_display . '</td>';
                echo '<td>' . esc_html( $trigger_label ) . '</td>';
                echo '<td>' . $snippet_cell . '</td>';
                echo '<td>' . $details . '</td>';
                echo '</tr>';
            }

            echo '</tbody></table>';
            echo '</div>';
        }

        echo '</div>';
    }


    /**
     * Convert a safe mode log trigger key into a human-readable label.
     */
    protected function log_trigger_label( string $trigger ): string {
        switch ( $trigger ) {
            case 'fatal':
                return __( 'Fatal error', 'snippet-press' );
            case 'manual_enable':
                return __( 'Manual enable', 'snippet-press' );
            case 'manual_disable':
                return __( 'Manual disable', 'snippet-press' );
        }

        return __( 'Unknown', 'snippet-press' );
=======
>>>>>>> e098c42ed0a01e1fb96106f989047a13409f3644
    }

    protected function render_linting_tab( array $settings, string $active ): void {
        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="sp-settings-form sp-search-replace__form">';
        wp_nonce_field( 'sp_save_settings' );
        echo '<input type="hidden" name="action" value="sp_save_settings" />';
        echo '<input type="hidden" name="settings_context" value="' . esc_attr( self::TAB_LINTING ) . '" />';
        echo '<input type="hidden" name="redirect_tab" value="' . esc_attr( $active ) . '" />';

        echo '<div class="sp-panel">';
        echo '<h2>' . esc_html__( 'Linting', 'snippet-press' ) . '</h2>';
        echo '<p class="description">' . esc_html__( 'Linting scans your code before saving to catch syntax errors and common mistakes.', 'snippet-press' ) . '</p>';
        echo '<div class="field">';
        echo $this->checkbox_input( 'lint_php', '1', ! empty( $settings['lint_php'] ), __( 'Lint PHP snippets', 'snippet-press' ) );
        echo $this->checkbox_input( 'lint_js', '1', ! empty( $settings['lint_js'] ), __( 'Lint JavaScript snippets', 'snippet-press' ) );
        echo $this->checkbox_input( 'lint_css', '1', ! empty( $settings['lint_css'] ), __( 'Lint CSS snippets', 'snippet-press' ) );
        echo '</div>';
        echo '</div>';

        echo '<div class="sp-form-actions">';
        submit_button( __( 'Save Changes', 'snippet-press' ), 'primary', 'submit', false );
        echo '</div>';
        echo '</form>';
    }

    protected function render_advanced_tab( array $settings, string $active ): void {
        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="sp-settings-form sp-search-replace__form">';
        wp_nonce_field( 'sp_save_settings' );
        echo '<input type="hidden" name="action" value="sp_save_settings" />';
        echo '<input type="hidden" name="settings_context" value="' . esc_attr( self::TAB_ADVANCED ) . '" />';
        echo '<input type="hidden" name="redirect_tab" value="' . esc_attr( $active ) . '" />';

        echo '<div class="sp-panel">';
        echo '<h2>' . esc_html__( 'Advanced', 'snippet-press' ) . '</h2>';

        echo '<div class="field">';
        echo '<label for="sp-php-binary">' . esc_html__( 'PHP binary path', 'snippet-press' ) . '</label>';
        printf( '<input type="text" id="sp-php-binary" class="sp-input regular-text" name="php_binary_path" value="%s" />', esc_attr( (string) $settings['php_binary_path'] ) );
        echo '<p class="description">' . esc_html__( 'Used when linting PHP via the command line. Leave as “php” unless your host requires a different path.', 'snippet-press' ) . '</p>';
        echo '</div>';

        echo '<div class="field trio">';
        echo '<label>' . esc_html__( 'Snippet size limits (bytes)', 'snippet-press' ) . '</label>';
        $limits = [
            'php_snippet_size_limit' => __( 'PHP', 'snippet-press' ),
            'js_snippet_size_limit'  => __( 'JavaScript', 'snippet-press' ),
            'css_snippet_size_limit' => __( 'CSS', 'snippet-press' ),
        ];
        foreach ( $limits as $key => $label ) {
            printf(
                '<div class="sp-settings-inline"><span>%1$s</span><input type="number" min="512" max="2097152" step="512" name="%2$s" value="%3$d" /></div>',
                esc_html( $label ),
                esc_attr( $key ),
                (int) $settings[ $key ]
            );
        }
        echo '<p class="description">' . esc_html__( 'Increase these limits if you need to store larger snippets without triggering warnings.', 'snippet-press' ) . '</p>';
        echo '</div>';
        echo '</div>';

        echo '<div class="sp-form-actions">';
        submit_button( __( 'Save Changes', 'snippet-press' ), 'primary', 'submit', false );
        echo '</div>';
        echo '</form>';
    }

    protected function render_logging_tab( array $settings, string $active ): void {
        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="sp-settings-form sp-search-replace__form">';
        wp_nonce_field( 'sp_save_settings' );
        echo '<input type="hidden" name="action" value="sp_save_settings" />';
        echo '<input type="hidden" name="settings_context" value="' . esc_attr( self::TAB_LOGGING ) . '" />';
        echo '<input type="hidden" name="redirect_tab" value="' . esc_attr( $active ) . '" />';

        echo '<div class="sp-panel">';
        echo '<h2>' . esc_html__( 'Activity Logging', 'snippet-press' ) . '</h2>';
        echo '<p class="description">' . esc_html__( 'Maintain a history of imports, exports, and other actions performed from the Tools screen.', 'snippet-press' ) . '</p>';
        echo '<div class="field">';
        echo $this->checkbox_input( 'logging_enabled', '1', ! empty( $settings['logging_enabled'] ), __( 'Enable Tools activity logging', 'snippet-press' ) );
        echo '</div>';
        echo '</div>';

        echo '<div class="sp-form-actions">';
        submit_button( __( 'Save Changes', 'snippet-press' ), 'primary', 'submit', false );
        echo '</div>';
        echo '</form>';
    }

    public function handle_save(): void {
        $this->assert_capability( $this->capability() );
        check_admin_referer( 'sp_save_settings' );

        $context      = isset( $_POST['settings_context'] ) ? sanitize_key( wp_unslash( $_POST['settings_context'] ) ) : self::TAB_GENERAL;
        $redirect_tab = isset( $_POST['redirect_tab'] ) ? sanitize_key( wp_unslash( $_POST['redirect_tab'] ) ) : self::TAB_GENERAL;
        $redirect_tab = $this->validate_tab( $redirect_tab );

        $current = $this->settings()->all();

        switch ( $this->validate_tab( $context ) ) {
            case self::TAB_GENERAL:
                $status = $this->post_value( 'default_status' );
                $current['default_status'] = in_array( $status, [ 'enabled', 'disabled' ], true ) ? $status : 'disabled';

                $allowed_scopes = array_keys( $this->available_scopes() );
                $scopes         = isset( $_POST['default_scopes'] ) ? array_map( 'sanitize_key', (array) $_POST['default_scopes'] ) : [];
                $filtered       = array_values( array_intersect( $allowed_scopes, $scopes ) );
                $current['default_scopes'] = ! empty( $filtered ) ? $filtered : [ 'frontend' ];
                break;

            case self::TAB_SAFETY:
                $current['safe_mode_enabled'] = $this->post_bool( 'safe_mode_enabled' );
                break;

            case self::TAB_LINTING:
                $current['lint_php'] = $this->post_bool( 'lint_php' );
                $current['lint_js']  = $this->post_bool( 'lint_js' );
                $current['lint_css'] = $this->post_bool( 'lint_css' );
                break;

            case self::TAB_ADVANCED:
                $current['php_binary_path'] = sanitize_text_field( $this->post_value( 'php_binary_path' ) ?: 'php' );

                foreach ( [
                    'php_snippet_size_limit' => 20480,
                    'js_snippet_size_limit'  => 40960,
                    'css_snippet_size_limit' => 20480,
                ] as $key => $default ) {
                    if ( isset( $_POST[ $key ] ) ) {
                        $value             = (int) $_POST[ $key ];
                        $current[ $key ] = max( 512, min( 2097152, $value ?: $default ) );
                    }
                }
                break;

            case self::TAB_LOGGING:
                $current['logging_enabled'] = $this->post_bool( 'logging_enabled' );
                break;
        }

        $this->settings()->save( $current );
        Notices::add( __( 'Settings saved.', 'snippet-press' ) );

        wp_safe_redirect( $this->page_url( $redirect_tab ) );
        exit;
    }

    public function handle_reset(): void {
        $this->assert_capability( $this->capability() );
        check_admin_referer( 'sp_reset_settings' );

        $redirect_tab = isset( $_REQUEST['redirect_tab'] ) ? sanitize_key( wp_unslash( $_REQUEST['redirect_tab'] ) ) : self::TAB_GENERAL;
        $redirect_tab = $this->validate_tab( $redirect_tab );

        $this->settings()->save( [] );
        Notices::add( __( 'Settings restored to defaults.', 'snippet-press' ) );

        wp_safe_redirect( $this->page_url( $redirect_tab ) );
        exit;
    }

    protected function post_value( string $key ): string {
        return isset( $_POST[ $key ] ) ? (string) wp_unslash( $_POST[ $key ] ) : '';
    }

    protected function post_bool( string $key ): bool {
        return ! empty( $_POST[ $key ] );
    }

    protected function available_scopes(): array {
        return [
            'frontend' => __( 'Frontend', 'snippet-press' ),
            'admin'    => __( 'Admin', 'snippet-press' ),
            'login'    => __( 'Login / Registration', 'snippet-press' ),
            'ajax'     => __( 'AJAX', 'snippet-press' ),
        ];
    }

    protected function page_url( string $tab = self::TAB_GENERAL ): string {
        return esc_url_raw( add_query_arg( [
            'page' => $this->slug(),
            'tab'  => $this->validate_tab( $tab ),
        ], admin_url( 'admin.php' ) ) );
    }

    protected function reset_url( string $tab ): string {
        return wp_nonce_url(
            add_query_arg(
                [
                    'action'       => 'sp_reset_settings',
                    'redirect_tab' => $this->validate_tab( $tab ),
                ],
                admin_url( 'admin-post.php' )
            ),
            'sp_reset_settings'
        );
    }

    protected function render_reset_button( string $tab ): string {
        return '<a href="' . esc_url( $this->reset_url( $tab ) ) . '" class="button button-secondary" onclick="return confirm(\'' . esc_js( __( 'Reset all Snippet Press settings to their defaults?', 'snippet-press' ) ) . '\');">' . esc_html__( 'Restore Defaults', 'snippet-press' ) . '</a>';
    }

    protected function validate_tab( string $tab ): string {
        $valid = [ self::TAB_GENERAL, self::TAB_SAFETY, self::TAB_LINTING, self::TAB_ADVANCED, self::TAB_LOGGING ];
        return in_array( $tab, $valid, true ) ? $tab : self::TAB_GENERAL;
    }
}
