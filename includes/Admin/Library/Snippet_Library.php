<?php

namespace SnippetPress\Admin\Library;

use SnippetPress\Post_Types\Snippet_Post_Type;

/**
 * Loads predefined snippet templates for the Add Snippet screen.
 */
class Snippet_Library {
    /**
     * Base directory for snippet definition files.
     */
    protected $base_dir;

    /**
     * Cached snippets keyed by slug.
     *
     * @var array<string, array>
     */
    protected $snippets;

    public function __construct( string $base_dir = '' ) {
        $this->base_dir = $base_dir ?: SNIPPET_PRESS_DIR . 'includes/Admin/Library/data/';
    }

    /**
     * Retrieve all snippet definitions keyed by slug.
     *
     * @return array<string, array>
     */
    public function all(): array {
        if ( is_array( $this->snippets ) ) {
            return $this->snippets;
        }

        $snippets = [];
        $types    = $this->discover_types();

        foreach ( $types as $type => $directory ) {
            $files = glob( $directory . DIRECTORY_SEPARATOR . '*.php' );

            if ( empty( $files ) ) {
                continue;
            }

            foreach ( $files as $file ) {
                $definition = include $file;

                if ( ! is_array( $definition ) ) {
                    continue;
                }

                $slug                 = $this->determine_slug( $definition, $file );
                $snippets[ $slug ] = $this->normalize_definition( $definition, $type, $slug );
            }
        }

        ksort( $snippets );

        return $this->snippets = $snippets;
    }

    /**
     * Retrieve a snippet by slug.
     */
    public function get( string $slug ): ?array {
        $slug = sanitize_key( $slug );

        $snippets = $this->all();

        return $snippets[ $slug ] ?? null;
    }

    /**
     * Filter snippets by category slug.
     *
     * @return array<string, array>
     */
    public function filter( string $filter ): array {
        $filter   = sanitize_key( $filter );
        $snippets = $this->all();

        if ( '' === $filter || 'all' === $filter ) {
            return $snippets;
        }

        if ( 'available' === $filter ) {
            return array_filter(
                $snippets,
                static function ( array $snippet ): bool {
                    return 'disabled' === $snippet['status'];
                }
            );
        }

        if ( 0 === strpos( $filter, 'type-' ) ) {
            $type = substr( $filter, 5 );

            return array_filter(
                $snippets,
                static function ( array $snippet ) use ( $type ): bool {
                    return $snippet['type'] === $type;
                }
            );
        }

        if ( 0 === strpos( $filter, 'category-' ) ) {
            $category = substr( $filter, 9 );

            return array_filter(
                $snippets,
                static function ( array $snippet ) use ( $category ): bool {
                    return $snippet['category'] === $category;
                }
            );
        }

        if ( 0 === strpos( $filter, 'tag-' ) ) {
            $tag = substr( $filter, 4 );

            return array_filter(
                $snippets,
                static function ( array $snippet ) use ( $tag ): bool {
                    return in_array( $tag, (array) $snippet['tags'], true );
                }
            );
        }

        return array_filter(
            $snippets,
            static function ( array $snippet ) use ( $filter ): bool {
                return $snippet['category'] === $filter
                    || in_array( $filter, $snippet['tags'], true );
            }
        );
    }

    /**
     * Search snippets by title, description, or highlights.
     *
     * @param array<string, array> $snippets Optional pre-filtered set.
     * @return array<string, array>
     */
    public function search( string $term, array $snippets = [] ): array {
        $term = trim( $term );

        if ( '' === $term ) {
            return empty( $snippets ) ? $this->all() : $snippets;
        }

        if ( empty( $snippets ) ) {
            $snippets = $this->all();
        }

        $term = mb_strtolower( $term );

        return array_filter(
            $snippets,
            static function ( array $snippet ) use ( $term ): bool {
                $haystacks = [
                    $snippet['title'],
                    $snippet['description'],
                    implode( ' ', $snippet['highlights'] ),
                ];

                foreach ( $haystacks as $haystack ) {
                    if ( false !== mb_stripos( $haystack, $term ) ) {
                        return true;
                    }
                }

                return false;
            }
        );
    }

    /**
     * Build sidebar filters and counts.
     *
     * @return array<int, array{slug:string,label:string,count:int}>
     */
    public function filters(): array {
        $snippets  = $this->all();
        $total     = count( $snippets );
        $available = count(
            array_filter(
                $snippets,
                static function ( array $snippet ): bool {
                    return 'disabled' === $snippet['status'];
                }
            )
        );

        $filters = [
            [
                'slug'  => 'all',
                'label' => __( 'All Snippets', 'snippet-press' ),
                'count' => $total,
            ],
            [
                'slug'  => 'available',
                'label' => __( 'Available Snippets', 'snippet-press' ),
                'count' => $available,
            ],
        ];

        $types = [ 'php' => 0, 'js' => 0, 'css' => 0 ];
        $categories = [];

        foreach ( $snippets as $snippet ) {
            $types[ $snippet['type'] ] = isset( $types[ $snippet['type'] ] ) ? $types[ $snippet['type'] ] + 1 : 1;

            $category = $snippet['category'];

            if ( ! isset( $categories[ $category ] ) ) {
                $categories[ $category ] = 0;
            }

            $categories[ $category ]++;
        }

        foreach ( $types as $type => $count ) {
            if ( $count <= 0 ) {
                continue;
            }

            $filters[] = [
                'slug'  => 'type-' . $type,
                'label' => sprintf( __( '%s Snippets', 'snippet-press' ), strtoupper( $type ) ),
                'count' => $count,
            ];
        }

        foreach ( $categories as $slug => $count ) {
            $filters[] = [
                'slug'  => $slug,
                'label' => $this->label_for_category( $slug ),
                'count' => $count,
            ];
        }

        return $filters;
    }

    /**
     * Group snippets by category for sectioned layouts.
     *
     * @param array<string, array> $snippets
     * @return array<string, array<int, array>>
     */
    public function group_by_category( array $snippets ): array {
        $grouped = [];

        foreach ( $snippets as $snippet ) {
            $category = $snippet['category'];

            if ( ! isset( $grouped[ $category ] ) ) {
                $grouped[ $category ] = [];
            }

            $grouped[ $category ][ $snippet['slug'] ] = $snippet;
        }

        ksort( $grouped );

        return $grouped;
    }

    /**
     * Check if a snippet with the provided library slug is already installed.
     */
    public function slug_exists_in_posts( string $slug ): bool {
        $query = new \WP_Query(
            [
                'post_type'      => Snippet_Post_Type::POST_TYPE,
                'post_status'    => 'any',
                'posts_per_page' => 1,
                'meta_key'       => '_sp_library_slug',
                'meta_value'     => sanitize_key( $slug ),
                'fields'         => 'ids',
                'no_found_rows'  => true,
            ]
        );

        return ! empty( $query->posts );
    }

    /**
     * Get all unique category slugs and their labels.
     *
     * @return array<string, string>
     */
    public function get_categories(): array {
        $categories = [];
        $snippets   = $this->all();

        foreach ( $snippets as $snippet ) {
            $category = $snippet['category'];

            if ( ! isset( $categories[ $category ] ) ) {
                $categories[ $category ] = $this->label_for_category( $category );
            }
        }

        asort( $categories );

        return $categories;
    }

    /**
     * Get all unique tag slugs, labels, and usage counts.
     *
     * @return array<string, array{label:string,count:int}>
     */
    public function get_tags(): array {
        $tags     = [];
        $snippets = $this->all();

        foreach ( $snippets as $snippet ) {
            foreach ( (array) $snippet['tags'] as $tag ) {
                $tag = sanitize_key( (string) $tag );

                if ( '' === $tag ) {
                    continue;
                }

                if ( ! isset( $tags[ $tag ] ) ) {
                    $tags[ $tag ] = [
                        'label' => ucwords( str_replace( '-', ' ', $tag ) ),
                        'count' => 0,
                    ];
                }

                $tags[ $tag ]['count']++;
            }
        }

        uasort(
            $tags,
            static function ( array $a, array $b ): int {
                return strcmp( $a['label'], $b['label'] );
            }
        );

        return $tags;
    }

    /**
     * Determine the slug for a snippet definition.
     */
    protected function determine_slug( array $definition, string $file ): string {
        if ( ! empty( $definition['slug'] ) ) {
            return sanitize_key( (string) $definition['slug'] );
        }

        return sanitize_key( basename( $file, '.php' ) );
    }

    /**
     * Prepare a definition with defaults and normalized keys.
     */
    protected function normalize_definition( array $definition, string $type, string $slug ): array {
        $defaults = [
            'slug'        => $slug,
            'title'       => ucwords( str_replace( '-', ' ', $slug ) ),
            'description' => '',
            'excerpt'     => '',
            'category'    => 'general',
            'tags'        => [],
            'highlights'  => [],
            'code'        => '',
            'type'        => $type,
            'scopes'      => [ 'frontend' ],
            'priority'    => 10,
            'status'      => 'disabled',
            'notes'       => '',
            'doc_link'    => '',
        ];

        $definition = wp_parse_args( $definition, $defaults );
        $definition['slug']       = sanitize_key( $definition['slug'] );
        $definition['category']   = sanitize_key( $definition['category'] );
        $definition['tags']       = array_values( array_filter( array_map( 'sanitize_key', (array) $definition['tags'] ) ) );
        $definition['highlights'] = array_values( array_filter( array_map( 'wp_strip_all_tags', (array) $definition['highlights'] ) ) );
        $definition['title']      = wp_kses_post( $definition['title'] );
        $definition['description']= wp_kses_post( $definition['description'] );
        $definition['excerpt']    = wp_kses_post( $definition['excerpt'] );
        $definition['code']       = (string) $definition['code'];
        $definition['type']       = sanitize_key( $definition['type'] );
        $definition['scopes']     = array_values( array_filter( array_map( 'sanitize_key', (array) $definition['scopes'] ) ) );
        $definition['priority']   = (int) $definition['priority'];
        $definition['status']     = in_array( $definition['status'], [ 'enabled', 'disabled' ], true ) ? $definition['status'] : 'disabled';
        $definition['notes']      = wp_kses_post( $definition['notes'] );
        $definition['doc_link']   = esc_url_raw( $definition['doc_link'] );

        return $definition;
    }

    /**
     * Resolve library folders by snippet type.
     *
     * @return array<string,string>
     */
    protected function discover_types(): array {
        $types = [];

        if ( ! is_dir( $this->base_dir ) ) {
            return $types;
        }

        $directories = scandir( $this->base_dir );

        if ( false === $directories ) {
            return $types;
        }

        foreach ( $directories as $directory ) {
            if ( '.' === $directory || '..' === $directory ) {
                continue;
            }

            $path = $this->base_dir . $directory;

            if ( ! is_dir( $path ) ) {
                continue;
            }

            $types[ sanitize_key( $directory ) ] = $path;
        }

        return $types;
    }

    /**
     * Human readable label for category slug.
     */
    protected function label_for_category( string $slug ): string {
        $map = [
            'admin'          => __( 'Admin', 'snippet-press' ),
            'comments'       => __( 'Comments', 'snippet-press' ),
            'content'        => __( 'Content', 'snippet-press' ),
            'design'         => __( 'Design', 'snippet-press' ),
            'disable'        => __( 'Disable', 'snippet-press' ),
            'getting-started'=> __( 'Getting Started', 'snippet-press' ),
            'login'          => __( 'Login', 'snippet-press' ),
            'performance'    => __( 'Performance', 'snippet-press' ),
            'rss'            => __( 'RSS Feeds', 'snippet-press' ),
            'security'       => __( 'Security', 'snippet-press' ),
            'widgets'        => __( 'Widgets', 'snippet-press' ),
        ];

        if ( isset( $map[ $slug ] ) ) {
            return $map[ $slug ];
        }

        $slug = str_replace( [ '-', '_' ], ' ', $slug );

        return ucwords( $slug );
    }
}









