<?php

namespace SnippetPress\Admin;

use SnippetPress\Infrastructure\Capabilities;
use SnippetPress\Post_Types\Snippet_Post_Type;
use WP_List_Table;
use WP_Post;
use WP_Query;

if ( ! class_exists( '\\WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Displays the snippets table inside the admin dashboard.
 */
class List_Table extends WP_List_Table {
    private const TYPE_FILTERS = [
        'all'        => 'All Snippets',
        'php'        => 'PHP',
        'javascript' => 'JavaScript',
        'css'        => 'CSS',
        'html'       => 'HTML',
        'text'       => 'Text',
        'universal'  => 'Universal',
        'blocks'     => 'Blocks',
        'scss'       => 'SCSS',
    ];

    private const STATUS_FILTERS = [
        'all'      => 'All',
        'active'   => 'Active',
        'disabled' => 'Disabled',
        'draft'    => 'Draft',
        'trash'    => 'Trash',
    ];

    private const SCOPE_LABELS = [
        'frontend'  => 'Frontend',
        'admin'     => 'Admin',
        'login'     => 'Login',
        'rest'      => 'REST API',
        'editor'    => 'Block Editor',
        'universal' => 'Universal',
    ];

    protected string $status_filter;
    protected string $type_filter;
    protected string $search_term = '';
    protected string $base_url;
    protected array $status_counts = [];

    /**
     * Retrieve available snippet types.
     */
    public static function get_types(): array {
        return self::TYPE_FILTERS;
    }

    public function __construct() {
        parent::__construct(
            [
                'singular' => 'snippet',
                'plural'   => 'snippets',
                'ajax'     => false,
            ]
        );

        $status_param = isset( $_GET['sp_status'] ) ? sanitize_key( wp_unslash( $_GET['sp_status'] ) ) : 'active';
        $type_param   = isset( $_GET['sp_type'] ) ? sanitize_key( wp_unslash( $_GET['sp_type'] ) ) : 'all';

        $this->status_filter = $this->sanitize_status( $status_param );
        $this->type_filter   = $this->sanitize_type( $type_param );
        $this->search_term   = isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : '';
        $this->base_url      = $this->build_base_url();
    }

    public function current_status(): string {
        return $this->status_filter;
    }

    public function current_type(): string {
        return $this->type_filter;
    }

    public function search_term(): string {
        return $this->search_term;
    }

    /**
     * Render tabs for snippet types.
     */
    public function render_type_tabs(): void {
        echo '<h2 class="nav-tab-wrapper sp-type-tabs">';

        foreach ( self::TYPE_FILTERS as $slug => $label ) {
            $url = add_query_arg(
                [
                    'page'      => 'sp-code-snippet',
                    'sp_type'   => $slug,
                    'sp_status' => $this->status_filter,
                    's'         => $this->search_term,
                ],
                $this->base_url
            );
            $class      = $slug === $this->type_filter ? ' nav-tab-active' : '';
            $label_text = __( $label, 'snippet-press' );

            printf(
                '<a href="%1$s" class="nav-tab%2$s">%3$s</a>',
                esc_url( $url ),
                esc_attr( $class ),
                esc_html( $label_text )
            );
        }

        echo '</h2>';
    }

    public function get_views(): array {
        $views = [];

        foreach ( self::STATUS_FILTERS as $slug => $label ) {
            $url = add_query_arg(
                [
                    'page'      => 'sp-code-snippet',
                    'sp_status' => $slug,
                    'sp_type'   => $this->type_filter,
                    's'         => $this->search_term,
                ],
                $this->base_url
            );
            $class      = $slug === $this->status_filter ? ' class="current"' : '';
            $count      = $this->status_counts[ $slug ] ?? 0;
            $label_text = __( $label, 'snippet-press' );

            $views[ $slug ] = sprintf(
                '<a href="%1$s"%2$s>%3$s <span class="count">(%4$d)</span></a>',
                esc_url( $url ),
                $class,
                esc_html( $label_text ),
                (int) $count
            );
        }

        return $views;
    }

    public function get_columns(): array {
        return [
            'cb'          => '<input type="checkbox" />',
            'title'       => __( 'Name', 'snippet-press' ),
            'sp_type'     => __( 'Type', 'snippet-press' ),
            'status'      => __( 'Status', 'snippet-press' ),
            'sp_scopes'   => __( 'Scope', 'snippet-press' ),
            'sp_priority' => __( 'Priority', 'snippet-press' ),
            'modified'    => __( 'Last Updated', 'snippet-press' ),
            'actions'     => __( 'Actions', 'snippet-press' ),
        ];
    }

    protected function get_sortable_columns(): array {
        return [
            'title'       => [ 'title', true ],
            'modified'    => [ 'modified', true ],
            'sp_priority' => [ 'sp_priority', false ],
        ];
    }

    protected function get_bulk_actions(): array {
        return [
            'enable'  => __( 'Enable', 'snippet-press' ),
            'disable' => __( 'Disable', 'snippet-press' ),
            'delete'  => __( 'Delete', 'snippet-press' ),
            'export'  => __( 'Export', 'snippet-press' ),
        ];
    }

    public function no_items(): void {
        esc_html_e( 'No snippets found for the current filters.', 'snippet-press' );
    }

    public function prepare_items(): void {
        $columns  = $this->get_columns();
        $hidden   = [];
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = [ $columns, $hidden, $sortable ];

        $per_page = $this->get_items_per_page( 'snippet_press_snippets_per_page', 20 );
        $paged    = max( 1, $this->get_pagenum() );
        $order    = isset( $_GET['order'] ) && 'desc' === strtolower( (string) $_GET['order'] ) ? 'DESC' : 'ASC';
        $orderby  = isset( $_GET['orderby'] ) ? sanitize_key( wp_unslash( $_GET['orderby'] ) ) : 'modified';

        $query_args = $this->build_query_args( $per_page, $paged, $orderby, $order );
        $query      = new WP_Query( $query_args );

        $this->items = array_map( [ $this, 'map_post_to_item' ], $query->posts );
        $this->status_counts = $this->calculate_status_counts();

        $this->set_pagination_args(
            [
                'total_items' => (int) $query->found_posts,
                'per_page'    => $per_page,
                'total_pages' => (int) max( 1, $query->max_num_pages ),
            ]
        );

        wp_reset_postdata();
    }

    protected function build_query_args( int $per_page, int $paged, string $orderby, string $order ): array {
        $orderby_map = [
            'title'       => 'title',
            'modified'    => 'modified',
            'sp_priority' => 'meta_value_num',
        ];

        $query_orderby = $orderby_map[ $orderby ] ?? 'modified';

        $args = [
            'post_type'      => Snippet_Post_Type::POST_TYPE,
            'posts_per_page' => $per_page,
            'paged'          => $paged,
            'orderby'        => $query_orderby,
            'order'          => $order,
            'post_status'    => $this->determine_post_statuses(),
            's'              => $this->search_term,
            'no_found_rows'  => false,
        ];

        if ( 'meta_value_num' === $query_orderby ) {
            $args['meta_key'] = '_sp_priority';
        }

        $meta_query = [];

        if ( 'all' !== $this->type_filter ) {
            $meta_query[] = [
                'key'   => '_sp_type',
                'value' => $this->type_filter,
            ];
        }

        if ( in_array( $this->status_filter, [ 'active', 'disabled' ], true ) ) {
            $meta_query[] = [
                'key'   => '_sp_status',
                'value' => 'active' === $this->status_filter ? 'enabled' : 'disabled',
            ];
        }

        if ( ! empty( $meta_query ) ) {
            $args['meta_query'] = $meta_query;
        }

        return $args;
    }

    protected function determine_post_statuses(): array {
        switch ( $this->status_filter ) {
            case 'draft':
                return [ 'draft', 'pending' ];
            case 'trash':
                return [ 'trash' ];
            default:
                return [ 'publish', 'draft', 'pending' ];
        }
    }

    protected function map_post_to_item( WP_Post $post ): array {
        $status_meta = get_post_meta( $post->ID, '_sp_status', true );
        $status      = $status_meta ?: ( 'publish' === $post->post_status ? 'enabled' : 'disabled' );

        return [
            'ID'          => $post->ID,
            'title'       => $post->post_title,
            'sp_type'     => get_post_meta( $post->ID, '_sp_type', true ) ?: 'php',
            'status'      => $status,
            'sp_scopes'   => (array) get_post_meta( $post->ID, '_sp_scopes', true ),
            'sp_priority' => (int) get_post_meta( $post->ID, '_sp_priority', true ) ?: 10,
            'modified'    => get_post_modified_time( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), false, $post, true ),
            'author'      => $post->post_author,
        ];
    }

    protected function calculate_status_counts(): array {
        $counts = [];

        foreach ( array_keys( self::STATUS_FILTERS ) as $status ) {
            $counts[ $status ] = $this->count_for_filter( $status );
        }

        return $counts;
    }

    protected function count_for_filter( string $status ): int {
        $args = [
            'post_type'      => Snippet_Post_Type::POST_TYPE,
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'no_found_rows'  => false,
        ];

        switch ( $status ) {
            case 'active':
                $args['post_status'] = [ 'publish' ];
                $args['meta_query']  = [
                    [
                        'key'   => '_sp_status',
                        'value' => 'enabled',
                    ],
                ];
                break;
            case 'disabled':
                $args['post_status'] = [ 'draft', 'pending' ];
                $args['meta_query']  = [
                    [
                        'key'   => '_sp_status',
                        'value' => 'disabled',
                    ],
                ];
                break;
            case 'draft':
                $args['post_status'] = [ 'draft', 'pending' ];
                break;
            case 'trash':
                $args['post_status'] = [ 'trash' ];
                break;
            default:
                $args['post_status'] = [ 'publish', 'draft', 'pending' ];
        }

        if ( 'all' !== $this->type_filter ) {
            $args['meta_query'][] = [
                'key'   => '_sp_type',
                'value' => $this->type_filter,
            ];
        }

        $query = new WP_Query( $args );
        $count = (int) $query->found_posts;
        wp_reset_postdata();

        return $count;
    }

    protected function build_base_url(): string {
        $url = menu_page_url( 'sp-code-snippet', false );

        if ( empty( $url ) ) {
            $url = admin_url( 'admin.php?page=sp-code-snippet' );
        }

        return remove_query_arg( [ 'paged', 'sp_status', 'sp_type', 'orderby', 'order', 's' ], $url );
    }

    protected function build_redirect_target(): string {
        $url = add_query_arg(
            [
                'sp_type'   => $this->type_filter,
                'sp_status' => $this->status_filter,
            ],
            $this->base_url
        );

        if ( '' !== $this->search_term ) {
            $url = add_query_arg( 's', $this->search_term, $url );
        }

        $paged = $this->get_pagenum();

        if ( $paged > 1 ) {
            $url = add_query_arg( 'paged', $paged, $url );
        }

        return $url;
    }

    protected function sanitize_status( string $status ): string {
        return array_key_exists( $status, self::STATUS_FILTERS ) ? $status : 'all';
    }

    protected function sanitize_type( string $type ): string {
        return array_key_exists( $type, self::TYPE_FILTERS ) ? $type : 'all';
    }

    protected function format_type_label( string $type ): string {
        $label = self::TYPE_FILTERS[ $type ] ?? ucfirst( $type );
        return __( $label, 'snippet-press' );
    }

    protected function format_status_label( string $status ): string {
        if ( 'enabled' === $status ) {
            return __( 'Enabled', 'snippet-press' );
        }

        if ( 'disabled' === $status ) {
            return __( 'Disabled', 'snippet-press' );
        }

        return ucfirst( $status );
    }

    protected function format_scopes( array $scopes ): string {
        if ( empty( $scopes ) ) {
            return '—';
        }

        $labels = array_map(
            function ( $scope ) {
                $scope = sanitize_key( $scope );
                $label = self::SCOPE_LABELS[ $scope ] ?? ucfirst( $scope );
                return __( $label, 'snippet-press' );
            },
            $scopes
        );

        $labels = array_map( 'esc_html', $labels );

        return implode( ', ', $labels );
    }

    protected function get_row_actions( array $item ): array {
        $actions    = [];
        $snippet_id = (int) $item['ID'];
        $redirect   = $this->build_redirect_target();
        $base       = admin_url( 'admin-post.php' );

        if ( current_user_can( Capabilities::EDIT ) ) {
            $actions['edit'] = sprintf( '<a href="%s">%s</a>', esc_url( get_edit_post_link( $snippet_id ) ), esc_html__( 'Edit', 'snippet-press' ) );

            $toggle_action = 'enabled' === $item['status'] ? 'disable' : 'enable';
            $toggle_url    = add_query_arg(
                [
                    'action'     => 'sp_' . $toggle_action . '_snippet',
                    'snippet_id' => $snippet_id,
                    'redirect'   => $redirect,
                ],
                $base
            );

            $actions[ $toggle_action ] = sprintf(
                '<a href="%1$s">%2$s</a>',
                esc_url( wp_nonce_url( $toggle_url, 'sp_snippet_action_' . $toggle_action . '_' . $snippet_id ) ),
                'enabled' === $item['status'] ? esc_html__( 'Disable', 'snippet-press' ) : esc_html__( 'Enable', 'snippet-press' )
            );

            $duplicate_url = add_query_arg(
                [
                    'action'     => 'sp_duplicate_snippet',
                    'snippet_id' => $snippet_id,
                    'redirect'   => $redirect,
                ],
                $base
            );

            $actions['duplicate'] = sprintf(
                '<a href="%1$s">%2$s</a>',
                esc_url( wp_nonce_url( $duplicate_url, 'sp_snippet_action_duplicate_' . $snippet_id ) ),
                esc_html__( 'Duplicate', 'snippet-press' )
            );
        }

        if ( current_user_can( Capabilities::MANAGE ) ) {
            $delete_url = add_query_arg(
                [
                    'action'     => 'sp_delete_snippet',
                    'snippet_id' => $snippet_id,
                    'redirect'   => $redirect,
                ],
                $base
            );

            $actions['delete'] = sprintf(
                '<a href="%1$s" class="submitdelete">%2$s</a>',
                esc_url( wp_nonce_url( $delete_url, 'sp_snippet_action_delete_' . $snippet_id ) ),
                esc_html__( 'Delete', 'snippet-press' )
            );
        }

        $export_url = add_query_arg(
            [
                'action' => 'sp_export_snippet',
                'ids'    => $snippet_id,
            ],
            $base
        );

        $actions['export'] = sprintf(
            '<a href="%1$s">%2$s</a>',
            esc_url( wp_nonce_url( $export_url, 'sp_export_snippet' ) ),
            esc_html__( 'Export', 'snippet-press' )
        );

        return $actions;
    }

    protected function column_cb( $item ) {
        return sprintf( '<input type="checkbox" name="snippet_ids[]" value="%d" />', (int) $item['ID'] );
    }

    protected function column_title( $item ) {
        return sprintf( '<strong><a href="%s">%s</a></strong>', esc_url( get_edit_post_link( $item['ID'] ) ), esc_html( $item['title'] ) );
    }

    protected function column_sp_type( $item ): string {
        return esc_html( $this->format_type_label( $item['sp_type'] ) );
    }

    protected function column_status( $item ): string {
        return esc_html( $this->format_status_label( $item['status'] ) );
    }

    protected function column_sp_scopes( $item ): string {
        return $this->format_scopes( (array) $item['sp_scopes'] );
    }

    protected function column_sp_priority( $item ): string {
        return (string) (int) $item['sp_priority'];
    }

    protected function column_modified( $item ): string {
        return esc_html( $item['modified'] );
    }

    protected function column_actions( $item ): string {
        $links = array_map( 'wp_kses_post', $this->get_row_actions( $item ) );
        return implode( ' | ', $links );
    }
    protected function column_default( $item, $column_name ) {
        return esc_html( $item[ $column_name ] ?? '' );
    }
}



