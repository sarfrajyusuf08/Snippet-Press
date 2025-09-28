<?php

namespace SnippetVault\Admin;

use SnippetVault\Post_Types\Snippet_Post_Type;

if ( ! class_exists( '\\WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Placeholder list table for snippets.
 */
class List_Table extends \WP_List_Table {
    public function __construct() {
        parent::__construct(
            [
                'singular' => 'snippet',
                'plural'   => 'snippets',
                'ajax'     => true,
            ]
        );
    }

    public function prepare_items(): void {
        $per_page     = $this->get_items_per_page( 'snippet_press_snippets_per_page', 20 );
        $current_page = $this->get_pagenum();

        $query = new \WP_Query(
            [
                'post_type'      => Snippet_Post_Type::POST_TYPE,
                'post_status'    => [ 'publish', 'draft', 'pending' ],
                'posts_per_page' => $per_page,
                'paged'          => $current_page,
            ]
        );

        $items = [];
        foreach ( $query->posts as $post ) {
            $items[] = [
                'ID'          => $post->ID,
                'title'       => $post->post_title,
                'type'        => get_post_meta( $post->ID, '_sp_type', true ) ?: 'php',
                'scopes'      => implode( ', ', (array) get_post_meta( $post->ID, '_sp_scopes', true ) ),
                'priority'    => (int) get_post_meta( $post->ID, '_sp_priority', true ) ?: 10,
                'status'      => get_post_meta( $post->ID, '_sp_status', true ) ?: 'disabled',
                'modified'    => get_post_modified_time( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), false, $post, true ),
            ];
        }

        $this->items = $items;

        $this->set_pagination_args(
            [
                'total_items' => (int) $query->found_posts,
                'per_page'    => $per_page,
                'total_pages' => (int) $query->max_num_pages,
            ]
        );
    }

    public function get_columns(): array {
        return [
            'cb'       => '<input type="checkbox" />',
            'title'    => __( 'Name', 'snippet-press' ),
            'type'     => __( 'Type', 'snippet-press' ),
            'scopes'   => __( 'Scopes', 'snippet-press' ),
            'priority' => __( 'Priority', 'snippet-press' ),
            'status'   => __( 'Status', 'snippet-press' ),
            'modified' => __( 'Modified', 'snippet-press' ),
        ];
    }

    protected function column_cb( $item ) {
        return sprintf( '<input type="checkbox" name="snippet_ids[]" value="%d" />', (int) $item['ID'] );
    }

    protected function column_title( $item ) {
        $title = sprintf( '<strong><a href="%s">%s</a></strong>', esc_url( get_edit_post_link( $item['ID'] ) ), esc_html( $item['title'] ) );
        $actions = [
            'edit'   => sprintf( '<a href="%s">%s</a>', esc_url( get_edit_post_link( $item['ID'] ) ), esc_html__( 'Edit', 'snippet-press' ) ),
            'clone'  => sprintf( '<a href="#" data-action="clone" data-id="%d">%s</a>', (int) $item['ID'], esc_html__( 'Clone', 'snippet-press' ) ),
            'export' => sprintf( '<a href="#" data-action="export" data-id="%d">%s</a>', (int) $item['ID'], esc_html__( 'Export', 'snippet-press' ) ),
        ];

        return $title . $this->row_actions( $actions );
    }

    protected function column_default( $item, $column_name ) {
        return esc_html( $item[ $column_name ] ?? '' );
    }
}