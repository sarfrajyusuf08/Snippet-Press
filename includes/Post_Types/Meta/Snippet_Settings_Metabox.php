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
        add_action( 'edit_form_after_title', [ $this, 'render_editor_panel' ] );
        add_action( 'add_meta_boxes', [ $this, 'customize_meta_boxes' ], 20 );
        add_action( 'save_post_' . Snippet_Post_Type::POST_TYPE, [ $this, 'save' ], 10, 3 );
    }

    public function render_editor_panel( WP_Post $post ): void {
        if ( Snippet_Post_Type::POST_TYPE !== $post->post_type || ! current_user_can( 'edit_post', $post->ID ) ) {
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
        <div class="sp-snippet-editor">
            <div class="sp-snippet-toolbar">
                <div class="sp-snippet-toolbar__status">
                    <span class="sp-snippet-toolbar__status-label"><?php esc_html_e( 'Snippet Status', 'snippet-press' ); ?></span>
                    <input type="hidden" name="sp_snippet_status" value="disabled" />
                    <label class="sp-switch">
                        <input type="checkbox" class="sp-snippet-status-toggle" name="sp_snippet_status" value="enabled" <?php checked( $is_enabled, true ); ?> />
                        <span class="sp-switch__track" aria-hidden="true"></span>
                        <span class="sp-switch__text sp-snippet-status-toggle__text" data-active="<?php echo esc_attr( $status_active_label ); ?>" data-inactive="<?php echo esc_attr( $status_inactive_label ); ?>">
                            <?php echo esc_html( $is_enabled ? $status_active_label : $status_inactive_label ); ?>
                        </span>
                    </label>
                </div>
                <button type="button" class="button button-primary sp-snippet-toolbar__save"><?php esc_html_e( 'Save Snippet', 'snippet-press' ); ?></button>
            </div>

            <div class="sp-snippet-editor__panel">
                <section class="sp-snippet-editor__section">
                    <h3 class="sp-snippet-editor__section-title"><?php esc_html_e( 'Code Controls', 'snippet-press' ); ?></h3>
                    <div class="sp-field">
                        <label class="sp-field__label" for="sp-snippet-type"><?php esc_html_e( 'Snippet Type', 'snippet-press' ); ?></label>
                        <select id="sp-snippet-type" name="sp_snippet_type" class="sp-field__control">
                            <?php foreach ( $type_options as $value => $label ) : ?>
                                <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $type, $value ); ?>>
                                    <?php echo esc_html( $label ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="sp-field">
                        <span class="sp-field__label"><?php esc_html_e( 'Execution Scope', 'snippet-press' ); ?></span>
                        <p class="sp-field__description"><?php esc_html_e( 'Choose where this snippet should run. You can combine scopes.', 'snippet-press' ); ?></p>
                        <div class="sp-snippet-scopes">
                            <?php foreach ( $scope_options as $value => $label ) : ?>
                                <label class="sp-snippet-scope-option">
                                    <input type="checkbox" name="sp_snippet_scopes[]" value="<?php echo esc_attr( $value ); ?>" <?php checked( in_array( $value, $selected_scopes, true ), true ); ?> />
                                    <span><?php echo esc_html( $label ); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>

                <section class="sp-snippet-editor__section sp-snippet-editor__section--advanced">
                    <details class="sp-snippet-advanced"<?php echo ! empty( $rules ) ? ' open' : ''; ?>>
                        <summary><?php esc_html_e( 'Advanced Targeting', 'snippet-press' ); ?></summary>
                        <p class="sp-field__description"><?php esc_html_e( 'Optional rules to target or exclude specific locations. Leave blank to ignore.', 'snippet-press' ); ?></p>

                        <div class="sp-snippet-advanced__grid">
                            <div class="sp-field">
                                <label class="sp-field__label" for="sp-scope-include-ids"><?php esc_html_e( 'Include Post IDs', 'snippet-press' ); ?></label>
                                <input type="text" id="sp-scope-include-ids" class="sp-field__control" name="sp_scope_rules[include_post_ids]" placeholder="<?php esc_attr_e( '12,45,78', 'snippet-press' ); ?>"
                                    value="<?php echo esc_attr( $include_post_ids_value ); ?>" />
                            </div>

                            <div class="sp-field">
                                <label class="sp-field__label" for="sp-scope-exclude-ids"><?php esc_html_e( 'Exclude Post IDs', 'snippet-press' ); ?></label>
                                <input type="text" id="sp-scope-exclude-ids" class="sp-field__control" name="sp_scope_rules[exclude_post_ids]" placeholder="<?php esc_attr_e( '21,34', 'snippet-press' ); ?>"
                                    value="<?php echo esc_attr( $exclude_post_ids_value ); ?>" />
                            </div>

                            <div class="sp-field">
                                <label class="sp-field__label" for="sp-scope-post-types"><?php esc_html_e( 'Allowed Post Types', 'snippet-press' ); ?></label>
                                <input type="text" id="sp-scope-post-types" class="sp-field__control" name="sp_scope_rules[include_post_types]" placeholder="<?php esc_attr_e( 'post,page,product', 'snippet-press' ); ?>"
                                    value="<?php echo esc_attr( $include_post_types ); ?>" />
                                <p class="sp-field__description"><?php esc_html_e( 'Comma-separated list. Leave empty to run on all types.', 'snippet-press' ); ?></p>
                            </div>

                            <div class="sp-field">
                                <label class="sp-field__label" for="sp-scope-tax-terms"><?php esc_html_e( 'Taxonomy & Terms', 'snippet-press' ); ?></label>
                                <textarea id="sp-scope-tax-terms" class="sp-field__control" rows="3" name="sp_scope_rules[include_tax_terms]" placeholder="<?php echo esc_attr( $tax_placeholder ); ?>"><?php echo esc_textarea( $tax_terms_value ); ?></textarea>
                                <p class="sp-field__description"><?php esc_html_e( 'Format each line as taxonomy:term_id,term_id', 'snippet-press' ); ?></p>
                            </div>

                            <div class="sp-field">
                                <label class="sp-field__label" for="sp-scope-url-patterns"><?php esc_html_e( 'URL Patterns', 'snippet-press' ); ?></label>
                                <textarea id="sp-scope-url-patterns" class="sp-field__control" rows="3" name="sp_scope_rules[url_patterns]" placeholder="<?php echo esc_attr( $url_placeholder ); ?>"><?php echo esc_textarea( $url_patterns_value ); ?></textarea>
                                <p class="sp-field__description"><?php esc_html_e( 'One pattern per line. Use * as a wildcard (e.g. /blog/*).', 'snippet-press' ); ?></p>
                            </div>
                        </div>
                    </details>
                </section>
            </div>
        </div>
        <?php

    }

    public function customize_meta_boxes( string $post_type ): void {
        if ( Snippet_Post_Type::POST_TYPE !== $post_type ) {
            return;
        }

        remove_meta_box( 'slugdiv', Snippet_Post_Type::POST_TYPE, 'normal' );
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

        if ( isset( $_POST['sp_snippet_status'] ) ) {
            $raw_status = wp_unslash( $_POST['sp_snippet_status'] );
            if ( is_array( $raw_status ) ) {
                $raw_status = end( $raw_status );
            }

            $normalized_status = 'enabled' === $raw_status ? 'enabled' : 'disabled';
            update_post_meta( $post_id, '_sp_status', $normalized_status );

            $desired_post_status = 'enabled' === $normalized_status ? 'publish' : 'draft';

            if ( $post->post_status !== $desired_post_status ) {
                remove_action( 'save_post_' . Snippet_Post_Type::POST_TYPE, [ $this, 'save' ], 10 );
                wp_update_post(
                    [
                        'ID'          => $post_id,
                        'post_status' => $desired_post_status,
                    ]
                );
                add_action( 'save_post_' . Snippet_Post_Type::POST_TYPE, [ $this, 'save' ], 10, 3 );
            }
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


