<?php

namespace SnippetPress\Admin;

use SnippetPress\Infrastructure\Capabilities;
use SnippetPress\Infrastructure\Settings;
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
    protected array $tag_filter = [];
    protected bool $profiling_enabled = false;
    protected int $profiling_threshold_ms = 250;

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
        $tag_param    = isset( $_GET['sp_tags'] ) ? sanitize_text_field( wp_unslash( $_GET['sp_tags'] ) ) : '';

        $this->status_filter = $this->sanitize_status( $status_param );
        $this->type_filter   = $this->sanitize_type( $type_param );
        $this->tag_filter    = $this->sanitize_tags( $tag_param );
        $this->search_term   = isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : '';
        $this->base_url      = $this->build_base_url();

        $options = get_option( Settings::OPTION_KEY, [] );
        if ( ! is_array( $options ) ) {
            $options = [];
        }

        $this->profiling_enabled      = ! empty( $options['profiling_enabled'] );
        $this->profiling_threshold_ms = isset( $options['profiling_slow_threshold_ms'] ) ? max( 10, (int) $options['profiling_slow_threshold_ms'] ) : 250;
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

    public function current_tags(): array {
        return $this->tag_filter;
    }

    /**
     * Render tabs for snippet types.
     */
    public function render_type_tabs(): void {
        echo '<h2 class="nav-tab-wrapper sp-type-tabs">';

        foreach ( self::TYPE_FILTERS as $slug => $label ) {
            $args = [
                'page'      => 'sp-code-snippet',
                'sp_type'   => $slug,
                'sp_status' => $this->status_filter,
                's'         => $this->search_term,
            ];

            if ( ! empty( $this->tag_filter ) ) {
                $args['sp_tags'] = implode( ',', $this->tag_filter );
            }

            $url = add_query_arg( $args, $this->base_url );
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

    public function render_tag_filters(): void {
        $available_tags = get_terms(
            [
                'taxonomy'   => Snippet_Post_Type::TAG_TAXONOMY,
                'hide_empty' => false,
                'number'     => 20,
                'orderby'    => 'count',
                'order'      => 'DESC',
            ]
        );

        if ( is_wp_error( $available_tags ) && empty( $this->tag_filter ) ) {
            return;
        }

        $active_tags = $this->tag_filter;

        $terms_by_slug = [];

        if ( ! is_wp_error( $available_tags ) ) {
            foreach ( $available_tags as $term ) {
                $terms_by_slug[ $term->slug ] = $term;
            }
        }

        foreach ( $active_tags as $slug ) {
            if ( isset( $terms_by_slug[ $slug ] ) ) {
                continue;
            }

            $term = get_term_by( 'slug', $slug, Snippet_Post_Type::TAG_TAXONOMY );

            if ( $term && ! is_wp_error( $term ) ) {
                $terms_by_slug[ $slug ] = $term;
            }
        }

        if ( empty( $terms_by_slug ) ) {
            return;
        }

        $available_tags = array_values( $terms_by_slug );

        echo '<div class="sp-tag-filter-bar">';
        echo '<span class="sp-tag-filter-label">' . esc_html__( 'Filter by tag:', 'snippet-press' ) . '</span>';

        $base_args = [
            'page'      => 'sp-code-snippet',
            'sp_type'   => $this->type_filter,
            'sp_status' => $this->status_filter,
        ];

        if ( '' !== $this->search_term ) {
            $base_args['s'] = $this->search_term;
        }

        $all_url   = esc_url( add_query_arg( $base_args, $this->base_url ) );
        $all_class = empty( $active_tags ) ? ' sp-tag-filter__chip--active' : '';

        printf( '<a href="%1$s" class="sp-tag-filter__chip%2$s">%3$s</a>', $all_url, esc_attr( $all_class ), esc_html__( 'All', 'snippet-press' ) );

        foreach ( $available_tags as $term ) {
            $slug   = $term->slug;
            $active = in_array( $slug, $active_tags, true );

            $args = $base_args;

            if ( $active ) {
                $remaining = array_filter(
                    $active_tags,
                    static function ( $tag ) use ( $slug ) {
                        return $tag !== $slug;
                    }
                );

                if ( ! empty( $remaining ) ) {
                    $args['sp_tags'] = implode( ',', $remaining );
                }
            } else {
                $updated        = $active_tags;
                $updated[]      = $slug;
                $args['sp_tags'] = implode( ',', array_unique( $updated ) );
            }

            $url = esc_url( add_query_arg( $args, $this->base_url ) );

            printf(
                '<a href="%1$s" class="sp-tag-filter__chip%2$s">%3$s</a>',
                $url,
                $active ? ' sp-tag-filter__chip--active' : '',
                esc_html( $term->name )
            );
        }

        echo '</div>';
    }

    public function get_views(): array {
        $views = [];

        foreach ( self::STATUS_FILTERS as $slug => $label ) {
            $args = [
                'page'      => 'sp-code-snippet',
                'sp_status' => $slug,
                'sp_type'   => $this->type_filter,
                's'         => $this->search_term,
            ];

            if ( ! empty( $this->tag_filter ) ) {
                $args['sp_tags'] = implode( ',', $this->tag_filter );
            }

            $url = add_query_arg( $args, $this->base_url );
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
            'sp_tags'     => __( 'Tags', 'snippet-press' ),
            'sp_performance' => __( 'Performance', 'snippet-press' ),
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

        if ( ! empty( $this->tag_filter ) ) {
            $args['tax_query'] = [
                [
                    'taxonomy' => Snippet_Post_Type::TAG_TAXONOMY,
                    'field'    => 'slug',
                    'terms'    => $this->tag_filter,
                    'operator' => 'AND',
                ],
            ];
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

        $tag_list = wp_get_object_terms(
            $post->ID,
            Snippet_Post_Type::TAG_TAXONOMY,
            [
                'fields' => 'names',
            ]
        );

        if ( is_wp_error( $tag_list ) ) {
            $tag_list = [];
        }

        $duration_meta = get_post_meta( $post->ID, '_sp_last_exec_ms', true );
        $duration      = is_numeric( $duration_meta ) ? round( (float) $duration_meta, 2 ) : null;
        $profiled_at   = (int) get_post_meta( $post->ID, '_sp_last_exec_at', true );

        return [
            'ID'          => $post->ID,
            'title'       => $post->post_title,
            'sp_type'     => get_post_meta( $post->ID, '_sp_type', true ) ?: 'php',
            'status'      => $status,
            'sp_scopes'   => (array) get_post_meta( $post->ID, '_sp_scopes', true ),
            'sp_priority' => (int) get_post_meta( $post->ID, '_sp_priority', true ) ?: 10,
            'modified'    => get_post_modified_time( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), false, $post, true ),
            'author'      => $post->post_author,
            'sp_tags'     => $tag_list,
            'sp_last_exec_ms'  => $duration,
            'sp_last_exec_at'  => $profiled_at,
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
            'meta_query'     => [],
            'tax_query'      => [],
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

        if ( ! empty( $this->tag_filter ) ) {
            $args['tax_query'][] = [
                'taxonomy' => Snippet_Post_Type::TAG_TAXONOMY,
                'field'    => 'slug',
                'terms'    => $this->tag_filter,
                'operator' => 'AND',
            ];
        }

        if ( empty( $args['meta_query'] ) ) {
            unset( $args['meta_query'] );
        }

        if ( empty( $args['tax_query'] ) ) {
            unset( $args['tax_query'] );
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

        return remove_query_arg( [ 'paged', 'sp_status', 'sp_type', 'orderby', 'order', 's', 'sp_tags' ], $url );
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

        if ( ! empty( $this->tag_filter ) ) {
            $url = add_query_arg( 'sp_tags', implode( ',', $this->tag_filter ), $url );
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

    protected function sanitize_tags( string $raw ): array {
        if ( '' === $raw ) {
            return [];
        }

        $parts = array_map(
            static function ( $slug ) {
                return sanitize_title( (string) $slug );
            },
            explode( ',', $raw )
        );

        $parts = array_filter(
            array_unique( $parts ),
            static function ( $slug ) {
                return '' !== $slug;
            }
        );

        return array_values( $parts );
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

    protected function format_tags( array $tags ): string {
        $all_tags = array_values(
            array_filter(
                array_map(
                    static function ( $tag ) {
                        return is_string( $tag ) ? trim( $tag ) : '';
                    },
                    $tags
                )
            )
        );

        if ( empty( $all_tags ) ) {
            return '—';
        }

        $display = array_map(
            static function ( $tag ) {
                return esc_html( $tag );
            },
            array_slice( $all_tags, 0, 5 )
        );

        if ( count( $all_tags ) > 5 ) {
            $display[] = '…';
        }

        return implode( ', ', $display );
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

    protected function column_sp_tags( $item ): string {
        return $this->format_tags( (array) ( $item['sp_tags'] ?? [] ) );
    }

    protected function column_sp_performance( $item ): string {
        if ( ! $this->profiling_enabled ) {
            return '<span class="sp-performance-meta">' . esc_html__( 'Profiling disabled', 'snippet-press' ) . '</span>';
        }

        if ( isset( $item['sp_type'] ) && 'php' !== $item['sp_type'] ) {
            return '<span class="sp-performance-meta">' . esc_html__( 'Not applicable', 'snippet-press' ) . '</span>';
        }

        $duration = $item['sp_last_exec_ms'] ?? null;

        if ( null === $duration ) {
            return '<span class="sp-performance-meta">' . esc_html__( 'No data yet', 'snippet-press' ) . '</span>';
        }

        $duration = (float) $duration;
        $is_slow  = $duration >= $this->profiling_threshold_ms;

        $status_label = $is_slow ? __( 'Slow', 'snippet-press' ) : __( 'OK', 'snippet-press' );
        $class        = $is_slow ? 'sp-performance-slow' : 'sp-performance-ok';

        $output = sprintf(
            '<span class="sp-performance-metric %1$s">%2$s · %3$s</span>',
            esc_attr( $class ),
            esc_html( $status_label ),
            esc_html( $this->format_duration( $duration ) )
        );

        $timestamp = isset( $item['sp_last_exec_at'] ) ? (int) $item['sp_last_exec_at'] : 0;

        if ( $timestamp > 0 ) {
            $format   = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
            $datetime = wp_date( $format, $timestamp );

            if ( false !== $datetime ) {
                $output .= sprintf(
                    '<span class="sp-performance-meta">%s</span>',
                    esc_html( sprintf( __( 'Profiled %s', 'snippet-press' ), $datetime ) )
                );
            }
        }

        return $output;
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

    protected function format_duration( float $duration_ms ): string {
        $precision = $duration_ms >= 100 ? 0 : 1;
        $rounded   = round( $duration_ms, $precision );

        return sprintf( '%s ms', number_format_i18n( $rounded, $precision ) );
    }

    protected function column_default( $item, $column_name ) {
        return esc_html( $item[ $column_name ] ?? '' );
    }
}
