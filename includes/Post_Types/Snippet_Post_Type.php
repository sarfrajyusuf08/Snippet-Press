<?php

namespace SnippetPress\Post_Types;

use SnippetPress\Infrastructure\Service_Provider;
use SnippetPress\Infrastructure\Settings;
use WP_Post;

/**
 * Registers the Snippet custom post type and related taxonomies/meta.
 */
class Snippet_Post_Type extends Service_Provider {
    public const POST_TYPE = 'sp_snippet';
    public const TAXONOMY = 'sp_type';

    /**
     * Register runtime hooks.
     */
    public function register(): void {
        add_action( 'init', [ $this, 'register_post_type' ] );
        add_action( 'init', [ $this, 'register_taxonomy' ] );
        add_action( 'init', [ $this, 'register_meta' ] );
        add_filter( 'manage_edit-' . self::POST_TYPE . '_columns', [ $this, 'register_columns' ] );
        add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', [ $this, 'render_column' ], 10, 2 );
        add_filter( 'post_updated_messages', [ $this, 'updated_messages' ] );
        add_action( 'transition_post_status', [ $this, 'sync_status_meta' ], 10, 3 );
        add_action( 'save_post_' . self::POST_TYPE, [ $this, 'sync_status_meta_on_save' ], 20, 3 );
    }

    /**
     * Activation tasks: register types then assign caps.
     */
    public function activate(): void {
        $this->register_post_type();
        $this->register_taxonomy();
        $this->register_meta();
        $this->add_capabilities();
        flush_rewrite_rules();
    }

    /**
     * Deactivation tasks.
     */
    public function deactivate(): void {
        flush_rewrite_rules();
    }

    /**
     * Register the snippets custom post type.
     */
    public function register_post_type(): void {
        $labels = [
            'name'               => __( 'Snippets', 'snippet-press' ),
            'singular_name'      => __( 'Snippet', 'snippet-press' ),
            'add_new'            => __( 'Add New', 'snippet-press' ),
            'add_new_item'       => __( 'Add New Snippet', 'snippet-press' ),
            'edit_item'          => __( 'Edit Snippet', 'snippet-press' ),
            'new_item'           => __( 'New Snippet', 'snippet-press' ),
            'view_item'          => __( 'View Snippet', 'snippet-press' ),
            'search_items'       => __( 'Search Snippets', 'snippet-press' ),
            'not_found'          => __( 'No snippets found.', 'snippet-press' ),
            'not_found_in_trash' => __( 'No snippets found in Trash.', 'snippet-press' ),
            'all_items'          => __( 'All Snippets', 'snippet-press' ),
            'menu_name'          => __( 'Snippet Press', 'snippet-press' ),
        ];

        $capabilities = [
            'edit_post'              => 'edit_snippet',
            'read_post'              => 'read_snippet',
            'delete_post'            => 'delete_snippet',
            'edit_posts'             => 'edit_snippets',
            'edit_others_posts'      => 'edit_others_snippets',
            'publish_posts'          => 'publish_snippets',
            'read_private_posts'     => 'read_private_snippets',
            'delete_posts'           => 'delete_snippets',
            'delete_private_posts'   => 'delete_private_snippets',
            'delete_published_posts' => 'delete_published_snippets',
            'delete_others_posts'    => 'delete_others_snippets',
            'edit_private_posts'     => 'edit_private_snippets',
            'edit_published_posts'   => 'edit_published_snippets',
        ];

        register_post_type(
            self::POST_TYPE,
            [
                'labels'          => $labels,
                'public'          => false,
                'show_ui'         => true,
                'show_in_menu'    => false,
                'supports'        => [ 'title', 'editor', 'excerpt', 'author', 'revisions' ],
                'capability_type' => [ 'snippet', 'snippets' ],
                'map_meta_cap'    => true,
                'capabilities'    => $capabilities,
                'rewrite'         => false,
                'menu_icon'       => 'dashicons-editor-code',
            ]
        );
    }

    /**
     * Register taxonomy for snippet types.
     */
    public function register_taxonomy(): void {
        $labels = [
            'name'          => __( 'Snippet Types', 'snippet-press' ),
            'singular_name' => __( 'Snippet Type', 'snippet-press' ),
            'search_items'  => __( 'Search Snippet Types', 'snippet-press' ),
            'all_items'     => __( 'All Snippet Types', 'snippet-press' ),
            'edit_item'     => __( 'Edit Snippet Type', 'snippet-press' ),
            'update_item'   => __( 'Update Snippet Type', 'snippet-press' ),
            'add_new_item'  => __( 'Add New Snippet Type', 'snippet-press' ),
            'new_item_name' => __( 'New Snippet Type Name', 'snippet-press' ),
            'menu_name'     => __( 'Snippet Types', 'snippet-press' ),
        ];

        register_taxonomy(
            self::TAXONOMY,
            self::POST_TYPE,
            [
                'labels'            => $labels,
                'public'            => false,
                'show_ui'           => true,
                'show_admin_column' => true,
                'hierarchical'      => false,
                'rewrite'           => false,
            ]
        );
    }

    /**
     * Register snippet metadata exposed via REST.
     */
    public function register_meta(): void {
        $string_meta = [
            'type'         => 'string',
            'single'       => true,
            'default'      => '',
            'show_in_rest' => true,
        ];

        register_post_meta( self::POST_TYPE, '_sp_type', $string_meta );
        register_post_meta( self::POST_TYPE, '_sp_status', $string_meta );
        register_post_meta( self::POST_TYPE, '_sp_notes', $string_meta );
        register_post_meta( self::POST_TYPE, '_sp_last_hash', $string_meta );

        register_post_meta( self::POST_TYPE, '_sp_safe_mode_flag', [
            'type'         => 'boolean',
            'single'       => true,
            'default'      => false,
            'show_in_rest' => true,
        ] );

        register_post_meta( self::POST_TYPE, '_sp_priority', [
            'type'         => 'integer',
            'single'       => true,
            'default'      => 10,
            'show_in_rest' => true,
        ] );

        register_post_meta( self::POST_TYPE, '_sp_scopes', [
            'type'         => 'array',
            'single'       => true,
            'default'      => [ 'frontend' ],
            'show_in_rest' => [
                'schema' => [
                    'type'  => 'array',
                    'items' => [ 'type' => 'string' ],
                ],
            ],
        ] );

        register_post_meta( self::POST_TYPE, '_sp_conditions', [
            'type'         => 'object',
            'single'       => true,
            'default'      => [],
            'show_in_rest' => [
                'schema' => [ 'type' => 'object' ],
            ],
        ] );

        register_post_meta( self::POST_TYPE, '_sp_variables', [
            'type'         => 'object',
            'single'       => true,
            'default'      => [],
            'show_in_rest' => [
                'schema' => [
                    'type'                 => 'object',
                    'additionalProperties' => [ 'type' => 'string' ],
                ],
            ],
        ] );

        register_post_meta( self::POST_TYPE, '_sp_favorite', [
            'type'         => 'boolean',
            'single'       => true,
            'default'      => false,
            'show_in_rest' => true,
        ] );

        register_post_meta( self::POST_TYPE, '_sp_pinned', [
            'type'         => 'boolean',
            'single'       => true,
            'default'      => false,
            'show_in_rest' => true,
        ] );
    }

    /**
     * Ensure status meta stays synced with WordPress post status transitions.
     */
    public function sync_status_meta( string $new_status, string $old_status, $post ): void {
        if ( ! $post instanceof WP_Post || self::POST_TYPE !== $post->post_type ) {
            return;
        }

        if ( wp_is_post_revision( $post->ID ) || wp_is_post_autosave( $post->ID ) ) {
            return;
        }

        $this->maybe_update_status_meta( $post, $new_status );
    }

    /**
     * Mirror status meta when snippets are saved directly.
     */
    public function sync_status_meta_on_save( int $post_id, $post, bool $update ): void {
        if ( ! $post instanceof WP_Post || self::POST_TYPE !== $post->post_type ) {
            return;
        }

        if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        $this->maybe_update_status_meta( $post, $post->post_status );
        $this->ensure_metadata_defaults( $post );
    }

    /**
     * Normalize the _sp_status meta field to match the post status.
     */
    private function maybe_update_status_meta( WP_Post $post, string $status ): void {
        if ( 'auto-draft' === $status ) {
            return;
        }

        $desired = 'publish' === $status ? 'enabled' : 'disabled';
        $current = get_post_meta( $post->ID, '_sp_status', true );

        if ( $current !== $desired ) {
            update_post_meta( $post->ID, '_sp_status', $desired );
        }
    }


    /**
     * Ensure newly saved snippets have required meta defaults.
     */
    private function ensure_metadata_defaults( WP_Post $post ): void {
        $post_id = $post->ID;

        if ( '' === get_post_meta( $post_id, '_sp_type', true ) ) {
            $detected = $this->detect_type_from_content( $post->post_content ?? '' );
            update_post_meta( $post_id, '_sp_type', $detected );
        }

        $scopes = get_post_meta( $post_id, '_sp_scopes', true );
        if ( empty( $scopes ) ) {
            $settings       = get_option( Settings::OPTION_KEY, [] );
            $default_scopes = isset( $settings['default_scopes'] ) && is_array( $settings['default_scopes'] ) && ! empty( $settings['default_scopes'] )
                ? array_values( array_filter( array_map( 'sanitize_key', (array) $settings['default_scopes'] ) ) )
                : [ 'frontend' ];

            update_post_meta( $post_id, '_sp_scopes', $default_scopes );
        }

        if ( '' === get_post_meta( $post_id, '_sp_priority', true ) ) {
            update_post_meta( $post_id, '_sp_priority', 10 );
        }
    }

    /**
     * Attempt to infer snippet type from its content when none supplied.
     */
    private function detect_type_from_content( string $content ): string {
        $trimmed = trim( $content );

        if ( '' === $trimmed ) {
            return 'php';
        }

        $lower = strtolower( $trimmed );

        if ( false !== strpos( $lower, '<?php' ) || false !== strpos( $lower, '?>' ) ) {
            return 'php';
        }

        if ( false !== strpos( $lower, '<script' ) || preg_match( '/(document|window)/i', $trimmed ) || preg_match( '/addEventListener/i', $trimmed ) ) {
            return 'js';
        }

        if ( false !== strpos( $lower, '<style' ) ) {
            return 'css';
        }

        if ( preg_match( '/[a-z0-9\.#_-]+\s*\{[^}]+\}/i', $trimmed ) && false === strpos( $lower, '; php' ) && false === strpos( $lower, '<?' ) ) {
            return 'css';
        }

        if ( false !== strpos( $trimmed, '<' ) && false !== strpos( $trimmed, '>' ) ) {
            return 'html';
        }

        return 'php';
    }

    public function register_columns( array $columns ): array {
        $columns['sp_type']     = __( 'Type', 'snippet-press' );
        $columns['sp_scopes']   = __( 'Scopes', 'snippet-press' );
        $columns['sp_priority'] = __( 'Priority', 'snippet-press' );

        return $columns;
    }

    public function render_column( string $column, int $post_id ): void {
        switch ( $column ) {
            case 'sp_type':
                echo esc_html( get_post_meta( $post_id, '_sp_type', true ) ?: 'php' );
                break;
            case 'sp_scopes':
                $scopes = (array) get_post_meta( $post_id, '_sp_scopes', true );
                echo esc_html( implode( ', ', $scopes ) ?: '-' );
                break;
            case 'sp_priority':
                $priority = (int) get_post_meta( $post_id, '_sp_priority', true );
                echo esc_html( $priority ?: 10 );
                break;
        }
    }

    public function updated_messages( array $messages ): array {
        $messages[ self::POST_TYPE ] = [
            0  => '',
            1  => __( 'Snippet updated.', 'snippet-press' ),
            6  => __( 'Snippet published.', 'snippet-press' ),
            7  => __( 'Snippet saved.', 'snippet-press' ),
            10 => __( 'Snippet draft updated.', 'snippet-press' ),
        ];

        return $messages;
    }

    /**
     * Assign snippet capabilities to administrator role.
     */
    protected function add_capabilities(): void {
        $caps = [
            'edit_snippet',
            'read_snippet',
            'delete_snippet',
            'edit_snippets',
            'edit_others_snippets',
            'publish_snippets',
            'read_private_snippets',
            'delete_snippets',
            'delete_private_snippets',
            'delete_published_snippets',
            'delete_others_snippets',
            'edit_private_snippets',
            'edit_published_snippets',
        ];

        $roles = [ 'administrator' ];

        foreach ( $roles as $role_slug ) {
            $role = get_role( $role_slug );

            if ( ! $role ) {
                continue;
            }

            foreach ( $caps as $cap ) {
                $role->add_cap( $cap );
            }
        }
    }
}

