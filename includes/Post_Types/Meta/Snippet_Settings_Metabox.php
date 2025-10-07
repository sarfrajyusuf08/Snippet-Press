<?php

namespace SnippetPress\Post_Types\Meta;

use SnippetPress\Infrastructure\Settings;
use SnippetPress\Post_Types\Snippet_Post_Type;
use WP_Post;

/**
 * Handles the Snippet Settings metabox on the snippet edit screen.
 */
class Snippet_Settings_Metabox {
    /**
     * Settings service used for defaults.
     *
     * @var Settings
     */
    private $settings;

    public function __construct( ?Settings $settings = null ) {
        $this->settings = $settings;
    }

    public function register(): void {
        add_action( 'add_meta_boxes', [ $this, 'register_meta_box' ] );
        add_action( 'save_post_' . Snippet_Post_Type::POST_TYPE, [ $this, 'save' ], 10, 3 );
    }

    public function register_meta_box(): void {
        add_meta_box(
            'sp-snippet-settings',
            __( 'Snippet Settings', 'snippet-press' ),
            [ $this, 'render' ],
            Snippet_Post_Type::POST_TYPE,
            'side',
            'default'
        );
    }

    public function render( WP_Post $post ): void {
        if ( ! current_user_can( 'edit_post', $post->ID ) ) {
            return;
        }

        wp_nonce_field( 'sp_snippet_settings', 'sp_snippet_settings_nonce' );

        $type   = get_post_meta( $post->ID, '_sp_type', true ) ?: 'php';
        $scopes = self::sanitize_scopes( (array) get_post_meta( $post->ID, '_sp_scopes', true ) );
        if ( empty( $scopes ) ) {
            $scopes = self::sanitize_scopes( $this->default_scopes() );
        }

        $rules = [];
        $raw_rules = get_post_meta( $post->ID, '_sp_scope_rules', true );
        if ( is_string( $raw_rules ) && '' !== $raw_rules ) {
            $decoded = json_decode( $raw_rules, true );
            if ( is_array( $decoded ) ) {
                $rules = $decoded;
            }
        }

        $type_options = [
            'php'  => __( 'PHP', 'snippet-press' ),
            'js'   => __( 'JavaScript', 'snippet-press' ),
            'css'  => __( 'CSS', 'snippet-press' ),
            'html' => __( 'HTML', 'snippet-press' ),
        ];

        $scope_options = [
            'global'   => __( 'Global', 'snippet-press' ),
            'frontend' => __( 'Frontend', 'snippet-press' ),
            'admin'    => __( 'Admin', 'snippet-press' ),
            'login'    => __( 'Login', 'snippet-press' ),
            'editor'   => __( 'Block Editor', 'snippet-press' ),
        ];

        $selected_scopes = array_map(
            static function ( string $scope ): string {
                return 'universal' === $scope ? 'global' : $scope;
            },
            $scopes
        );

        $include_terms_lines = [];
        if ( ! empty( $rules['include_tax_terms'] ) && is_array( $rules['include_tax_terms'] ) ) {
            foreach ( $rules['include_tax_terms'] as $entry ) {
                if ( empty( $entry['taxonomy'] ) || empty( $entry['terms'] ) ) {
                    continue;
                }

                $include_terms_lines[] = sprintf(
                    '%s:%s',
                    sanitize_key( (string) $entry['taxonomy'] ),
                    implode( ',', array_map( 'absint', (array) $entry['terms'] ) )
                );
            }
        }

        $tax_placeholder = sprintf( 'category:12,45%sproduct_cat:21', PHP_EOL );
        $url_placeholder = sprintf( '/blog/*%s/shop/*', PHP_EOL );

        ?>
        <p>
            <label for="sp-snippet-type"><strong><?php esc_html_e( 'Snippet Type', 'snippet-press' ); ?></strong></label>
            <select id="sp-snippet-type" name="sp_snippet_type" class="widefat">
                <?php foreach ( $type_options as $value => $label ) : ?>
                    <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $type, $value ); ?>>
                        <?php echo esc_html( $label ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>

        <fieldset>
            <legend><strong><?php esc_html_e( 'Scopes', 'snippet-press' ); ?></strong></legend>
            <p class="description"><?php esc_html_e( 'Where should this snippet run?', 'snippet-press' ); ?></p>
            <?php foreach ( $scope_options as $value => $label ) : ?>
                <label style="display:block;margin-bottom:4px;">
                    <input type="checkbox" name="sp_snippet_scopes[]" value="<?php echo esc_attr( $value ); ?>" <?php checked( in_array( $value, $selected_scopes, true ), true ); ?> />
                    <?php echo esc_html( $label ); ?>
                </label>
            <?php endforeach; ?>
        </fieldset>

        <details style="margin-top:12px;">
            <summary><strong><?php esc_html_e( 'Advanced targeting', 'snippet-press' ); ?></strong></summary>
            <p class="description"><?php esc_html_e( 'Optional rules to target or exclude specific locations. Leave blank to ignore.', 'snippet-press' ); ?></p>

            <p>
                <label for="sp-scope-include-ids"><?php esc_html_e( 'Include Post IDs', 'snippet-press' ); ?></label>
                <input type="text" id="sp-scope-include-ids" class="widefat" name="sp_scope_rules[include_post_ids]" placeholder="<?php esc_attr_e( '12,45,78', 'snippet-press' ); ?>"
                    value="<?php echo esc_attr( isset( $rules['include_post_ids'] ) ? implode( ',', array_map( 'absint', (array) $rules['include_post_ids'] ) ) : '' ); ?>" />
            </p>

            <p>
                <label for="sp-scope-exclude-ids"><?php esc_html_e( 'Exclude Post IDs', 'snippet-press' ); ?></label>
                <input type="text" id="sp-scope-exclude-ids" class="widefat" name="sp_scope_rules[exclude_post_ids]" placeholder="<?php esc_attr_e( '21,34', 'snippet-press' ); ?>"
                    value="<?php echo esc_attr( isset( $rules['exclude_post_ids'] ) ? implode( ',', array_map( 'absint', (array) $rules['exclude_post_ids'] ) ) : '' ); ?>" />
            </p>

            <p>
                <label for="sp-scope-post-types"><?php esc_html_e( 'Post Types', 'snippet-press' ); ?></label>
                <input type="text" id="sp-scope-post-types" class="widefat" name="sp_scope_rules[include_post_types]" placeholder="<?php esc_attr_e( 'post,page,product', 'snippet-press' ); ?>"
                    value="<?php echo esc_attr( isset( $rules['include_post_types'] ) ? implode( ',', array_map( 'sanitize_key', (array) $rules['include_post_types'] ) ) : '' ); ?>" />
            </p>

            <p>
                <label for="sp-scope-tax-terms"><?php esc_html_e( 'Taxonomy Terms', 'snippet-press' ); ?></label>
                <textarea id="sp-scope-tax-terms" class="widefat" rows="3" name="sp_scope_rules[include_tax_terms]" placeholder="<?php echo esc_attr( $tax_placeholder ); ?>"><?php echo esc_textarea( implode( PHP_EOL, $include_terms_lines ) ); ?></textarea>
                <span class="description"><?php esc_html_e( 'One taxonomy per line. Example: category:12,45', 'snippet-press' ); ?></span>
            </p>

            <p>
                <label for="sp-scope-url-patterns"><?php esc_html_e( 'URL Patterns', 'snippet-press' ); ?></label>
                <textarea id="sp-scope-url-patterns" class="widefat" rows="3" name="sp_scope_rules[url_patterns]" placeholder="<?php echo esc_attr( $url_placeholder ); ?>"><?php
                    if ( isset( $rules['url_patterns'] ) && is_array( $rules['url_patterns'] ) ) {
                        echo esc_textarea( implode( PHP_EOL, array_map( 'sanitize_text_field', $rules['url_patterns'] ) ) );
                    }
                ?></textarea>
            </p>
        </details>
        <?php
    }

    public function save( int $post_id, WP_Post $post, bool $update ): void {
        if ( Snippet_Post_Type::POST_TYPE !== $post->post_type ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( ! isset( $_POST['sp_snippet_settings_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['sp_snippet_settings_nonce'] ) ), 'sp_snippet_settings' ) ) {
            return;
        }

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        if ( isset( $_POST['sp_snippet_type'] ) ) {
            $type         = sanitize_key( wp_unslash( $_POST['sp_snippet_type'] ) );
            $allowed_type = in_array( $type, [ 'php', 'js', 'css', 'html' ], true ) ? $type : 'php';
            update_post_meta( $post_id, '_sp_type', $allowed_type );
        }

        $scopes_input = isset( $_POST['sp_snippet_scopes'] ) ? (array) wp_unslash( $_POST['sp_snippet_scopes'] ) : [];
        $scopes       = self::sanitize_scopes( $scopes_input );
        if ( empty( $scopes ) ) {
            $scopes = self::sanitize_scopes( $this->default_scopes() );
        }
        update_post_meta( $post_id, '_sp_scopes', $scopes );

        if ( isset( $_POST['sp_scope_rules'] ) && is_array( $_POST['sp_scope_rules'] ) ) {
            $normalized = self::normalize_scope_rules( (array) wp_unslash( $_POST['sp_scope_rules'] ) );
            update_post_meta( $post_id, '_sp_scope_rules', empty( $normalized ) ? '' : wp_json_encode( $normalized ) );
        }
    }

    /**
     * Sanitize scope selections from the UI.
     *
     * @param array<int,string> $scopes Raw scope values.
     */
    public static function sanitize_scopes( array $scopes ): array {
        if ( empty( $scopes ) ) {
            return [];
        }

        $allowed = [ 'universal', 'frontend', 'admin', 'login', 'editor', 'rest' ];

        $normalized = array_map(
            static function ( $scope ) {
                $scope = sanitize_key( (string) $scope );
                if ( 'global' === $scope ) {
                    return 'universal';
                }

                return $scope;
            },
            $scopes
        );

        return array_values( array_unique( array_intersect( $normalized, $allowed ) ) );
    }

    /**
     * Normalise advanced targeting fields before saving.
     *
     * @param array<string,mixed> $raw Raw input array from $_POST.
     *
     * @return array<string,mixed>
     */
    public static function normalize_scope_rules( array $raw ): array {
        $normalized = [];

        if ( ! empty( $raw['include_post_ids'] ) ) {
            $ids = array_filter( array_map( 'absint', explode( ',', (string) $raw['include_post_ids'] ) ) );
            if ( ! empty( $ids ) ) {
                $normalized['include_post_ids'] = array_values( array_unique( $ids ) );
            }
        }

        if ( ! empty( $raw['exclude_post_ids'] ) ) {
            $ids = array_filter( array_map( 'absint', explode( ',', (string) $raw['exclude_post_ids'] ) ) );
            if ( ! empty( $ids ) ) {
                $normalized['exclude_post_ids'] = array_values( array_unique( $ids ) );
            }
        }

        if ( ! empty( $raw['include_post_types'] ) ) {
            $types = array_filter( array_map( 'sanitize_key', explode( ',', (string) $raw['include_post_types'] ) ) );
            if ( ! empty( $types ) ) {
                $normalized['include_post_types'] = array_values( array_unique( $types ) );
            }
        }

        if ( ! empty( $raw['include_tax_terms'] ) ) {
            $lines     = preg_split( '/\r?\n/', (string) $raw['include_tax_terms'] );
            $tax_terms = [];

            foreach ( $lines as $line ) {
                $line = trim( $line );
                if ( '' === $line || false === strpos( $line, ':' ) ) {
                    continue;
                }

                list( $taxonomy, $term_string ) = array_map( 'trim', explode( ':', $line, 2 ) );
                $taxonomy                       = sanitize_key( $taxonomy );

                if ( '' === $taxonomy ) {
                    continue;
                }

                $terms = array_filter( array_map( 'absint', explode( ',', $term_string ) ) );
                if ( empty( $terms ) ) {
                    continue;
                }

                $tax_terms[] = [
                    'taxonomy' => $taxonomy,
                    'terms'    => array_values( array_unique( $terms ) ),
                ];
            }

            if ( ! empty( $tax_terms ) ) {
                $normalized['include_tax_terms'] = $tax_terms;
            }
        }

        if ( ! empty( $raw['url_patterns'] ) ) {
            $patterns = preg_split( '/\r?\n/', (string) $raw['url_patterns'] );
            $patterns = array_filter(
                array_map(
                    static function ( $pattern ) {
                        $pattern = trim( (string) $pattern );
                        return '' === $pattern ? null : sanitize_text_field( $pattern );
                    },
                    $patterns
                )
            );

            if ( ! empty( $patterns ) ) {
                $normalized['url_patterns'] = array_values( array_unique( $patterns ) );
            }
        }

        return $normalized;
    }

    /**
     * Retrieve default scopes from plugin settings.
     *
     * @return array<int,string>
     */
    private function default_scopes(): array {
        if ( $this->settings instanceof Settings ) {
            $settings = $this->settings->all();

            if ( ! empty( $settings['default_scopes'] ) && is_array( $settings['default_scopes'] ) ) {
                return array_values( array_filter( array_map( 'sanitize_key', $settings['default_scopes'] ) ) );
            }
        }

        return [ 'frontend' ];
    }
}

