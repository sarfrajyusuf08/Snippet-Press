<?php

namespace SnippetVault\Admin;

use SnippetVault\Infrastructure\Capabilities;
use SnippetVault\Infrastructure\Service_Provider;
use SnippetVault\Post_Types\Snippet_Post_Type;

/**
 * Handles admin UI registration.
 */
class Admin_Manager extends Service_Provider {
    /**
     * Register admin hooks when inside the dashboard.
     */
    public function register(): void {
        if ( ! is_admin() ) {
            return;
        }

        add_action( 'admin_menu', [ $this, 'register_menu' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
        add_action( 'load-toplevel_page_sp-code-snippet', [ $this, 'register_code_snippet_screen_options' ] );
    }

    /**
     * Register menu structure for Snippet Press.
     */
    public function register_menu(): void {
        add_menu_page(
            __( 'Snippet Press', 'snippet-press' ),
            __( 'Snippet Press', 'snippet-press' ),
            Capabilities::MANAGE,
            'sp-code-snippet',
            [ $this, 'render_code_snippet_page' ],
            'dashicons-editor-code',
            55
        );

        add_submenu_page(
            'sp-code-snippet',
            __( 'Code Snippet', 'snippet-press' ),
            __( 'Code Snippet', 'snippet-press' ),
            Capabilities::EDIT,
            'sp-code-snippet',
            [ $this, 'render_code_snippet_page' ]
        );

        add_submenu_page(
            'sp-code-snippet',
            __( 'Add Snippet', 'snippet-press' ),
            __( 'Add Snippet', 'snippet-press' ),
            Capabilities::EDIT,
            'sp-add-snippet',
            [ $this, 'render_add_snippet_page' ]
        );

        add_submenu_page(
            'sp-code-snippet',
            __( 'Header & Footer', 'snippet-press' ),
            __( 'Header & Footer', 'snippet-press' ),
            Capabilities::MANAGE,
            'sp-header-footer',
            [ $this, 'render_header_footer_page' ]
        );

        add_submenu_page(
            'sp-code-snippet',
            __( 'Library', 'snippet-press' ),
            __( 'Library', 'snippet-press' ),
            Capabilities::EDIT,
            'sp-library',
            [ $this, 'render_library_page' ]
        );

        add_submenu_page(
            'sp-code-snippet',
            __( 'File Editor', 'snippet-press' ),
            __( 'File Editor', 'snippet-press' ),
            Capabilities::ADMIN,
            'sp-file-editor',
            [ $this, 'render_file_editor_page' ]
        );

        add_submenu_page(
            'sp-code-snippet',
            __( 'Search & Replace', 'snippet-press' ),
            __( 'Search & Replace', 'snippet-press' ),
            Capabilities::ADMIN,
            'sp-search-replace',
            [ $this, 'render_search_replace_page' ]
        );

        add_submenu_page(
            'sp-code-snippet',
            __( 'Backups', 'snippet-press' ),
            __( 'Backups', 'snippet-press' ),
            Capabilities::BACKUP,
            'sp-backups',
            [ $this, 'render_backups_page' ]
        );

        add_submenu_page(
            'sp-code-snippet',
            __( 'Tools', 'snippet-press' ),
            __( 'Tools', 'snippet-press' ),
            Capabilities::MANAGE,
            'sp-tools',
            [ $this, 'render_tools_page' ]
        );

        add_submenu_page(
            'sp-code-snippet',
            __( 'Settings', 'snippet-press' ),
            __( 'Settings', 'snippet-press' ),
            Capabilities::ADMIN,
            'sp-settings',
            [ $this, 'render_settings_page' ]
        );
    }

    /**
     * Screen options for the code snippet list.
     */
    public function register_code_snippet_screen_options(): void {
        $option = 'per_page';
        $args   = [
            'label'   => __( 'Snippets per page', 'snippet-press' ),
            'default' => 20,
            'option'  => 'snippet_press_snippets_per_page',
        ];

        add_screen_option( $option, $args );
    }

    /**
     * Enqueue admin assets on Snippet Press screens.
     */
    public function enqueue_assets( string $hook ): void {
        if ( false === strpos( $hook, 'sp-' ) ) {
            return;
        }

        wp_enqueue_style( 'snippet-press-admin', SNIPPET_PRESS_URL . 'assets/css/admin.css', [], SNIPPET_PRESS_VERSION );
        wp_enqueue_script( 'snippet-press-admin', SNIPPET_PRESS_URL . 'assets/js/admin.js', [ 'jquery' ], SNIPPET_PRESS_VERSION, true );

        wp_localize_script(
            'snippet-press-admin',
            'snippetPressAdmin',
            [
                'createUrl' => admin_url( 'admin.php?page=sp-add-snippet' ),
                'nonce'     => wp_create_nonce( 'snippet_press_admin' ),
                'strings'   => [
                    'cloneConfirm'  => __( 'Clone this snippet?', 'snippet-press' ),
                    'exportPending' => __( 'Preparing export?', 'snippet-press' ),
                ],
            ]
        );
    }

    /**
     * Render the Code Snippet listing page.
     */
    public function render_code_snippet_page(): void {
        if ( ! current_user_can( Capabilities::EDIT ) ) {
            wp_die( esc_html__( 'You do not have permission to view this page.', 'snippet-press' ) );
        }

        $list_table = new List_Table();
        $list_table->prepare_items();

        echo '<div class="wrap">';
        echo '<h1 class="wp-heading-inline">' . esc_html__( 'Code Snippets', 'snippet-press' ) . '</h1>';
        echo ' <a href="' . esc_url( admin_url( 'admin.php?page=sp-add-snippet' ) ) . '" class="page-title-action">' . esc_html__( 'Add Snippet', 'snippet-press' ) . '</a>';
        echo '<hr class="wp-header-end" />';

        echo '<form method="post">';
        $list_table->display();
        echo '</form>';
        echo '</div>';
    }

    /**
     * Render the Add Snippet workflow (tabs placeholder).
     */
    public function render_add_snippet_page(): void {
        if ( ! current_user_can( Capabilities::EDIT ) ) {
            wp_die( esc_html__( 'You do not have permission to add snippets.', 'snippet-press' ) );
        }

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__( 'Add Snippet', 'snippet-press' ) . '</h1>';
        echo '<h2 class="nav-tab-wrapper">';
        $tabs = [
            'library'   => __( 'Snippet Library', 'snippet-press' ),
            'generator' => __( 'Snippet Generator', 'snippet-press' ),
            'my'        => __( 'My Library', 'snippet-press' ),
        ];
        $active = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'library';
        foreach ( $tabs as $key => $label ) {
            $classes = 'nav-tab' . ( $active === $key ? ' nav-tab-active' : '' );
            printf( '<a href="%s" class="%s">%s</a>', esc_url( add_query_arg( 'tab', $key ) ), esc_attr( $classes ), esc_html( $label ) );
        }
        echo '</h2>';

        echo '<div class="snippet-press-tabbed">';
        echo '<p>' . esc_html__( 'The full snippet creation workflow is under construction. Use the WordPress post editor temporarily.', 'snippet-press' ) . '</p>';
        echo '</div>';
        echo '</div>';
    }

    public function render_header_footer_page(): void {
        if ( ! current_user_can( Capabilities::MANAGE ) ) {
            wp_die( esc_html__( 'You do not have permission to manage header and footer injections.', 'snippet-press' ) );
        }

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__( 'Header & Footer', 'snippet-press' ) . '</h1>';
        echo '<p>' . esc_html__( 'Header, body, and footer injection controls will appear here.', 'snippet-press' ) . '</p>';
        echo '</div>';
    }

    public function render_library_page(): void {
        if ( ! current_user_can( Capabilities::EDIT ) ) {
            wp_die( esc_html__( 'You do not have permission to view the library.', 'snippet-press' ) );
        }

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__( 'Snippet Library', 'snippet-press' ) . '</h1>';
        echo '<p>' . esc_html__( 'Curated snippets will be available here.', 'snippet-press' ) . '</p>';
        echo '</div>';
    }

    public function render_file_editor_page(): void {
        if ( ! current_user_can( Capabilities::ADMIN ) ) {
            wp_die( esc_html__( 'You do not have permission to edit files.', 'snippet-press' ) );
        }

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__( 'File Editor', 'snippet-press' ) . '</h1>';
        echo '<p>' . esc_html__( 'Manage whitelisted files (ads.txt, robots.txt, etc.) from this screen.', 'snippet-press' ) . '</p>';
        echo '</div>';
    }

    public function render_search_replace_page(): void {
        if ( ! current_user_can( Capabilities::ADMIN ) ) {
            wp_die( esc_html__( 'You do not have permission to use Search & Replace.', 'snippet-press' ) );
        }

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__( 'Search & Replace', 'snippet-press' ) . '</h1>';
        echo '<p>' . esc_html__( 'Database and file search & replace tools will live on this screen.', 'snippet-press' ) . '</p>';
        echo '</div>';
    }

    public function render_backups_page(): void {
        if ( ! current_user_can( Capabilities::BACKUP ) ) {
            wp_die( esc_html__( 'You do not have permission to manage backups.', 'snippet-press' ) );
        }

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__( 'Backups', 'snippet-press' ) . '</h1>';
        echo '<p>' . esc_html__( 'Create and restore Snippet Press backups from this interface.', 'snippet-press' ) . '</p>';
        echo '</div>';
    }

    public function render_tools_page(): void {
        if ( ! current_user_can( Capabilities::MANAGE ) ) {
            wp_die( esc_html__( 'You do not have permission to view tools.', 'snippet-press' ) );
        }

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__( 'Tools', 'snippet-press' ) . '</h1>';
        echo '<p>' . esc_html__( 'Import/export, logs, and diagnostics will be available here.', 'snippet-press' ) . '</p>';
        echo '</div>';
    }

    public function render_settings_page(): void {
        if ( ! current_user_can( Capabilities::ADMIN ) ) {
            wp_die( esc_html__( 'You do not have permission to change settings.', 'snippet-press' ) );
        }

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__( 'Settings', 'snippet-press' ) . '</h1>';
        echo '<p>' . esc_html__( 'Configure Snippet Press defaults and safe mode behavior here.', 'snippet-press' ) . '</p>';
        echo '</div>';
    }
}