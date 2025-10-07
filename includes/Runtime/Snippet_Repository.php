<?php

namespace SnippetPress\Runtime;

use SnippetPress\Infrastructure\Settings;
use SnippetPress\Post_Types\Snippet_Post_Type;

/**
 * Provides access to snippet data for runtime execution.
 */
class Snippet_Repository {
    /**
     * Cached snippets grouped by type.
     *
     * @var array<string, array<int, array>>
     */
    protected $cache = [];

    /**
     * Settings service.
     *
     * @var Settings
     */
    protected $settings;

    public function __construct( Settings $settings ) {
        $this->settings = $settings;
    }

    /**
     * Retrieve enabled snippets filtered by type.
     *
     * @return array<int, array>
     */
    public function get_snippets_by_type( string $type ): array {
        if ( isset( $this->cache[ $type ] ) ) {
            return $this->cache[ $type ];
        }

        $query = new \WP_Query(
            [
                'post_type'      => Snippet_Post_Type::POST_TYPE,
                'post_status'    => 'publish',
                'meta_key'       => '_sp_status',
                'meta_value'     => 'enabled',
                'no_found_rows'  => true,
                'posts_per_page' => -1,
            ]
        );

        $snippets = [];

        foreach ( $query->posts as $post ) {
            $snippet_type = get_post_meta( $post->ID, '_sp_type', true ) ?: 'php';

            if ( $snippet_type !== $type ) {
                continue;
            }

            $snippets[] = $this->normalize_snippet( $post->ID );
        }

        usort(
            $snippets,
            static function ( array $a, array $b ): int {
                return $a['priority'] <=> $b['priority'] ?: $a['id'] <=> $b['id'];
            }
        );

        return $this->cache[ $type ] = $snippets;
    }

    /**
     * Normalize snippet data into array structure.
     */
    protected function normalize_snippet( int $post_id ): array {
        $scopes         = (array) get_post_meta( $post_id, '_sp_scopes', true );
        $priority       = (int) get_post_meta( $post_id, '_sp_priority', true );
        $raw_content    = (string) get_post_field( 'post_content', $post_id );
        $type           = get_post_meta( $post_id, '_sp_type', true ) ?: 'php';
        $cleaned_content = $this->clean_content( $raw_content, $type );

        return [
            'id'           => $post_id,
            'title'        => get_the_title( $post_id ),
            'type'         => $type,
            'status'       => get_post_meta( $post_id, '_sp_status', true ) ?: 'disabled',
            'scopes'       => ! empty( $scopes ) ? $scopes : $this->settings->all()['default_scopes'],
            'priority'     => $priority ?: 10,
            'conditions'   => (array) get_post_meta( $post_id, '_sp_conditions', true ),
            'variables'    => (array) get_post_meta( $post_id, '_sp_variables', true ),
            'content'      => trim( $cleaned_content ),
            'safe_mode'    => (bool) get_post_meta( $post_id, '_sp_safe_mode_flag', true ),
            'last_hash'    => (string) get_post_meta( $post_id, '_sp_last_hash', true ),
            'modified_gmt' => get_post_modified_time( 'U', true, $post_id ),
        ];
    }

    /**
     * Remove editor artifacts before runtime usage.
     */
    private function clean_content( string $content, string $type ): string {
        $decoded    = html_entity_decode( $content, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
        $normalized = str_replace( [ "\r\n", "\r" ], "\n", $decoded );

        if ( 'php' === $type ) {
            return $normalized;
        }

        $normalized = (string) preg_replace( '/<br\s*\/?>/i', "\n", $normalized );
        $normalized = (string) preg_replace( '/<\/p>\s*<p>/i', "\n", $normalized );
        $normalized = str_replace( [ '<p>', '</p>' ], '', $normalized );
        $normalized = trim( $normalized );
        $normalized = (string) preg_replace( '/<\?php\s*/i', '', $normalized );
        $normalized = (string) preg_replace( '/\?>/i', '', $normalized );

        return $normalized;
    }
}
