<?php

namespace SnippetPress\Admin;

use SnippetPress\Infrastructure\Capabilities;
use SnippetPress\Post_Types\Snippet_Post_Type;
use WP_Error;
use WP_Post;

/**
 * Handles CRUD-style operations for snippets.
 */
class Snippet_Service {
    /**
     * Create a new snippet from the provided data.
     */
    public static function create( array $data ) {
        if ( ! current_user_can( Capabilities::EDIT ) ) {
            return new WP_Error( 'sp_no_permission', __( 'You do not have permission to create snippets.', 'snippet-press' ) );
        }

        $name = isset( $data['name'] ) ? sanitize_text_field( $data['name'] ) : '';
        $code = isset( $data['code'] ) ? (string) $data['code'] : '';

        if ( '' === $name || '' === $code ) {
            return new WP_Error( 'sp_invalid_snippet', __( 'A snippet name and code are required.', 'snippet-press' ) );
        }

        $description = isset( $data['description'] ) ? sanitize_textarea_field( $data['description'] ) : '';
        $status      = isset( $data['status'] ) ? sanitize_key( $data['status'] ) : 'disabled';
        $status      = in_array( $status, [ 'enabled', 'disabled' ], true ) ? $status : 'disabled';
        $post_status = 'enabled' === $status ? 'publish' : 'draft';

        $postarr = [
            'post_title'   => $name,
            'post_content' => $code,
            'post_status'  => $post_status,
            'post_type'    => Snippet_Post_Type::POST_TYPE,
            'post_author'  => get_current_user_id() ?: 0,
        ];

        if ( '' !== $description ) {
            $postarr['post_excerpt'] = $description;
        }

        $post_id = wp_insert_post( wp_slash( $postarr ), true );

        if ( is_wp_error( $post_id ) ) {
            return $post_id;
        }

        $type   = isset( $data['type'] ) ? sanitize_key( $data['type'] ) : 'php';
        $scopes = isset( $data['scopes'] ) ? self::sanitize_scopes( (array) $data['scopes'] ) : [];
        $priority = isset( $data['priority'] ) ? (int) $data['priority'] : 10;

        update_post_meta( $post_id, '_sp_type', $type );
        update_post_meta( $post_id, '_sp_status', $status );
        update_post_meta( $post_id, '_sp_scopes', $scopes );
        update_post_meta( $post_id, '_sp_priority', $priority );

        if ( isset( $data['notes'] ) ) {
            update_post_meta( $post_id, '_sp_notes', sanitize_textarea_field( $data['notes'] ) );
        }

        if ( isset( $data['variables'] ) ) {
            update_post_meta( $post_id, '_sp_variables', (array) $data['variables'] );
        }

        if ( isset( $data['conditions'] ) ) {
            update_post_meta( $post_id, '_sp_conditions', (array) $data['conditions'] );
        }

        return $post_id;
    }

    /**
     * Enable the provided snippets.
     */
    public static function enable( array $ids ): array {
        return self::set_status( $ids, 'enabled' );
    }

    /**
     * Disable the provided snippets.
     */
    public static function disable( array $ids ): array {
        return self::set_status( $ids, 'disabled' );
    }

    /**
     * Move snippets to the trash.
     */
    public static function delete( array $ids ): array {
        $result = [ 'processed' => 0, 'errors' => [] ];

        if ( ! current_user_can( Capabilities::MANAGE ) ) {
            return [
                'processed' => 0,
                'errors'    => [ __( 'You do not have permission to delete snippets.', 'snippet-press' ) ],
            ];
        }

        foreach ( self::sanitize_ids( $ids ) as $id ) {
            $post = get_post( $id );

            if ( ! self::is_snippet( $post ) ) {
                $result['errors'][] = sprintf( __( 'Snippet %d could not be found.', 'snippet-press' ), $id );
                continue;
            }

            $trashed = wp_trash_post( $id );

            if ( false === $trashed ) {
                $result['errors'][] = sprintf( __( 'Failed to delete snippet %d.', 'snippet-press' ), $id );
                continue;
            }

            $result['processed']++;
        }

        return $result;
    }

    /**
     * Duplicate a snippet. Returns new post ID or WP_Error.
     */
    public static function duplicate( int $id ) {
        if ( ! current_user_can( Capabilities::EDIT ) ) {
            return new WP_Error( 'sp_no_permission', __( 'You do not have permission to duplicate snippets.', 'snippet-press' ) );
        }

        $post = get_post( $id );

        if ( ! self::is_snippet( $post ) ) {
            return new WP_Error( 'sp_not_found', __( 'Snippet not found.', 'snippet-press' ) );
        }

        $new_post = [
            'post_title'   => sprintf( __( '%s (Copy)', 'snippet-press' ), $post->post_title ),
            'post_content' => $post->post_content,
            'post_status'  => 'draft',
            'post_type'    => Snippet_Post_Type::POST_TYPE,
            'post_author'  => get_current_user_id() ?: $post->post_author,
        ];

        $new_id = wp_insert_post( wp_slash( $new_post ), true );

        if ( is_wp_error( $new_id ) ) {
            return $new_id;
        }

        self::copy_meta( $id, $new_id );

        update_post_meta( $new_id, '_sp_status', 'disabled' );

        return $new_id;
    }

    /**
     * Generate export data for snippets.
     */
    public static function prepare_export_data( array $ids ): array {
        $data = [];

        foreach ( self::sanitize_ids( $ids ) as $id ) {
            $post = get_post( $id );

            if ( ! self::is_snippet( $post ) ) {
                continue;
            }

            $data[] = [
                'id'         => $post->ID,
                'name'       => $post->post_title,
                'description'=> $post->post_excerpt,
                'type'       => get_post_meta( $post->ID, '_sp_type', true ),
                'status'     => get_post_meta( $post->ID, '_sp_status', true ),
                'scopes'     => (array) get_post_meta( $post->ID, '_sp_scopes', true ),
                'priority'   => (int) get_post_meta( $post->ID, '_sp_priority', true ),
                'conditions' => (array) get_post_meta( $post->ID, '_sp_conditions', true ),
                'variables'  => (array) get_post_meta( $post->ID, '_sp_variables', true ),
                'notes'      => get_post_meta( $post->ID, '_sp_notes', true ),
                'code'       => $post->post_content,
                'updated_at' => get_post_modified_time( 'c', true, $post ),
            ];
        }

        return $data;
    }

    /**
     * Update snippet status.
     */
    protected static function set_status( array $ids, string $status ): array {
        $result = [ 'processed' => 0, 'errors' => [] ];

        if ( ! current_user_can( Capabilities::EDIT ) ) {
            return [
                'processed' => 0,
                'errors'    => [ __( 'You do not have permission to modify snippets.', 'snippet-press' ) ],
            ];
        }

        $status = 'enabled' === $status ? 'enabled' : 'disabled';
        $post_status = 'enabled' === $status ? 'publish' : 'draft';

        foreach ( self::sanitize_ids( $ids ) as $id ) {
            $post = get_post( $id );

            if ( ! self::is_snippet( $post ) ) {
                $result['errors'][] = sprintf( __( 'Snippet %d could not be found.', 'snippet-press' ), $id );
                continue;
            }

            $meta_updated = update_post_meta( $id, '_sp_status', $status );
            $post_updated = wp_update_post( [ 'ID' => $id, 'post_status' => $post_status ], true );

            if ( is_wp_error( $post_updated ) ) {
                $result['errors'][] = sprintf( __( 'Failed to update snippet %d: %s', 'snippet-press' ), $id, $post_updated->get_error_message() );
                continue;
            }

            if ( false === $meta_updated && ! is_wp_error( $post_updated ) ) {
                // Meta unchanged, still consider a success.
                $result['processed']++;
                continue;
            }

            $result['processed']++;
        }

        return $result;
    }

    /**
     * Verify post is a snippet.
     */
    protected static function is_snippet( $post ): bool {
        return $post instanceof WP_Post && Snippet_Post_Type::POST_TYPE === $post->post_type;
    }

    /**
     * Copy meta values from one snippet to another.
     */
    protected static function copy_meta( int $source_id, int $target_id ): void {
        $keys = [
            '_sp_type',
            '_sp_status',
            '_sp_notes',
            '_sp_safe_mode_flag',
            '_sp_priority',
            '_sp_scopes',
            '_sp_conditions',
            '_sp_variables',
            '_sp_last_hash',
            '_sp_favorite',
            '_sp_pinned',
        ];

        foreach ( $keys as $key ) {
            $value = get_post_meta( $source_id, $key, true );
            update_post_meta( $target_id, $key, $value );
        }
    }

    /**
     * Sanitize scope values.
     */
    protected static function sanitize_scopes( array $scopes ): array {
        $scopes = array_map( 'sanitize_key', $scopes );
        $scopes = array_filter( $scopes );
        return array_values( array_unique( $scopes ) );
    }

    /**
     * Sanitize IDs array.
     */
    protected static function sanitize_ids( array $ids ): array {
        $ids = array_map( 'absint', $ids );
        $ids = array_filter( $ids );
        return array_values( $ids );
    }
}
