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
        $status = get_post_meta( $post->ID, '_sp_status', true ) ?: 'disabled';
        $is_enabled = 'enabled' === $status;
        $status_active_label   = __( 'Enabled', 'snippet-press' );
        $status_inactive_label = __( 'Disabled', 'snippet-press' );

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

        $include_post_ids_value  = '';
        $exclude_post_ids_value  = '';
        $include_post_types      = '';
        $tax_terms_value         = '';
        $url_patterns_value      = '';

        if ( ! empty( $rules['include_post_ids'] ) && is_array( $rules['include_post_ids'] ) ) {
            $include_post_ids_value = implode( ',', array_map( 'absint', (array) $rules['include_post_ids'] ) );
        }

        if ( ! empty( $rules['exclude_post_ids'] ) && is_array( $rules['exclude_post_ids'] ) ) {
            $exclude_post_ids_value = implode( ',', array_map( 'absint', (array) $rules['exclude_post_ids'] ) );
        }

        if ( ! empty( $rules['include_post_types'] ) && is_array( $rules['include_post_types'] ) ) {
            $include_post_types = implode( ',', array_map( 'sanitize_key', (array) $rules['include_post_types'] ) );
        }

        $type_options = [
            'php'  => __( 'PHP', 'snippet-press' ),
            'js'   => __( 'JavaScript', 'snippet-press' ),
            'css'  => __( 'CSS', 'snippet-press' ),
            'html' => __( 'HTML', 'snippet-press' ),
        ];

        $scope_labels_map = $this->scope_label_map();
        $scope_options    = [
            'global'   => $scope_labels_map['global'],
            'frontend' => $scope_labels_map['frontend'],
            'admin'    => $scope_labels_map['admin'],
            'login'    => $scope_labels_map['login'],
            'editor'   => $scope_labels_map['editor'],
            'rest'     => $scope_labels_map['rest'],
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
        $tax_terms_value = implode( PHP_EOL, $include_terms_lines );

        if ( ! empty( $rules['url_patterns'] ) && is_array( $rules['url_patterns'] ) ) {
            $url_patterns_value = implode(
                PHP_EOL,
                array_map(
                    static function ( $pattern ) {
                        return sanitize_text_field( (string) $pattern );
                    },
                    (array) $rules['url_patterns']
                )
            );
        }

        $scope_badges_html = $this->render_scope_badges_html( $selected_scopes, $scope_labels_map );
        $rules_summary     = $this->format_scope_rules_summary( $rules );
        $preview_details   = $this->determine_preview_link( $selected_scopes, $rules );

        $tag_names      = wp_get_object_terms( $post->ID, Snippet_Post_Type::TAG_TAXONOMY, [ 'fields' => 'names' ] );
        $tags_value     = implode( ', ', array_map( 'sanitize_text_field', (array) $tag_names ) );
        $tag_terms      = get_terms(
            [
                'taxonomy'   => Snippet_Post_Type::TAG_TAXONOMY,
                'hide_empty' => false,
                'number'     => 20,
                'orderby'    => 'count',
                'order'      => 'DESC',
            ]
        );
        $tag_suggestions = [];

        if ( ! is_wp_error( $tag_terms ) ) {
            $tag_suggestions = array_map(
                static function ( $term ) {
                    return $term->name;
                },
                $tag_terms
            );
        }

        $tag_suggestions_json = wp_json_encode( $tag_suggestions );

        $preview_url     = $preview_details['url'];
        $preview_label   = $preview_details['label'];
        $preview_context = $preview_details['context'];
        $preview_locked  = $preview_details['locked'] ? '1' : '0';
        $preview_labels_map = $preview_details['labels'];

        $scope_labels_json  = wp_json_encode( $scope_labels_map );
        $preview_labels_json = wp_json_encode( $preview_labels_map );

        if ( false === $scope_labels_json ) {
            $scope_labels_json = '{}';
        }

        if ( false === $preview_labels_json ) {
            $preview_labels_json = '{}';
        }

        if ( false === $tag_suggestions_json ) {
            $tag_suggestions_json = '[]';
        }

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

            <div
                class="sp-snippet-scope-preview"
                data-scope-labels="<?php echo esc_attr( $scope_labels_json ); ?>"
                data-empty-label="<?php esc_attr_e( 'Select scopes to see where this snippet runs.', 'snippet-press' ); ?>"
                data-preview-locked="<?php echo esc_attr( $preview_locked ); ?>"
                data-preview-home="<?php echo esc_url( home_url( '/' ) ); ?>"
                data-preview-admin="<?php echo esc_url( admin_url() ); ?>"
                data-preview-login="<?php echo esc_url( wp_login_url() ); ?>"
                data-preview-editor="<?php echo esc_url( admin_url( 'post-new.php' ) ); ?>"
                data-preview-rest="<?php echo esc_url( rest_url() ); ?>"
                data-preview-labels="<?php echo esc_attr( $preview_labels_json ); ?>"
                data-preview-context="<?php echo esc_attr( $preview_context ); ?>"
            >
                <div class="sp-snippet-scope-preview__title"><?php esc_html_e( 'Scope Preview', 'snippet-press' ); ?></div>
                <div class="sp-snippet-scope-preview__badges"><?php echo wp_kses_post( $scope_badges_html ); ?></div>

                <?php if ( '' !== $rules_summary ) : ?>
                    <p class="sp-snippet-scope-preview__summary"><?php echo esc_html( $rules_summary ); ?></p>
                <?php endif; ?>

                <?php if ( $preview_url ) : ?>
                    <a class="button button-secondary sp-snippet-scope-preview__link" href="<?php echo esc_url( $preview_url ); ?>" target="_blank" rel="noopener">
                        <?php echo esc_html( $preview_label ); ?>
                    </a>
                <?php endif; ?>
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

                    <div class="sp-field sp-field--tags" data-tag-suggestions='<?php echo esc_attr( $tag_suggestions_json ); ?>'>
                        <label class="sp-field__label" for="sp-snippet-tags"><?php esc_html_e( 'Tags', 'snippet-press' ); ?></label>
                        <input type="text" id="sp-snippet-tags" class="sp-field__control sp-snippet-tags__input" name="sp_snippet_tags" value="<?php echo esc_attr( $tags_value ); ?>" placeholder="<?php esc_attr_e( 'analytics, footer, e-commerce', 'snippet-press' ); ?>" />
                        <p class="sp-field__description"><?php esc_html_e( 'Separate tags with commas to group related snippets.', 'snippet-press' ); ?></p>
                        <?php if ( ! empty( $tag_suggestions ) ) : ?>
                            <div class="sp-tag-suggestions">
                                <?php foreach ( $tag_suggestions as $suggestion ) : ?>
                                    <button type="button" class="sp-tag-suggestion" data-tag="<?php echo esc_attr( $suggestion ); ?>"><?php echo esc_html( $suggestion ); ?></button>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
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
        remove_meta_box( 'tagsdiv-' . Snippet_Post_Type::TAG_TAXONOMY, Snippet_Post_Type::POST_TYPE, 'side' );
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

        if ( isset( $_POST['sp_snippet_tags'] ) ) {
            $raw_tags = (string) wp_unslash( $_POST['sp_snippet_tags'] );
            $pieces   = preg_split( '/[,]+/', $raw_tags ) ?: [];

            $tags = array_filter(
                array_unique(
                    array_map(
                        static function ( $tag ) {
                            $tag = trim( (string) $tag );
                            return $tag === '' ? '' : sanitize_text_field( $tag );
                        },
                        $pieces
                    )
                ),
                static function ( $tag ) {
                    return '' !== $tag;
                }
            );

            wp_set_post_terms( $post_id, $tags, Snippet_Post_Type::TAG_TAXONOMY, false );
        }
    }

    /**
     * Provide translated labels for scope selections.
     */
    protected function scope_label_map(): array {
        return [
            'global'   => __( 'Global', 'snippet-press' ),
            'frontend' => __( 'Frontend', 'snippet-press' ),
            'admin'    => __( 'Admin', 'snippet-press' ),
            'login'    => __( 'Login', 'snippet-press' ),
            'editor'   => __( 'Block Editor', 'snippet-press' ),
            'rest'     => __( 'REST API', 'snippet-press' ),
        ];
    }

    /**
     * Build HTML badges for the selected scopes.
     *
     * @param array<int,string> $scopes Selected scopes.
     * @param array<string,string> $labels Scope label map.
     */
    protected function render_scope_badges_html( array $scopes, array $labels ): string {
        $badges = [];
        $seen   = [];

        foreach ( $scopes as $scope ) {
            $scope = sanitize_key( $scope );

            if ( isset( $seen[ $scope ] ) ) {
                continue;
            }

            $seen[ $scope ] = true;

            $label = $labels[ $scope ] ?? ucwords( str_replace( '_', ' ', $scope ) );

            $badges[] = sprintf(
                '<span class="sp-scope-badge sp-scope-badge--%1$s">%2$s</span>',
                esc_attr( $scope ),
                esc_html( $label )
            );
        }

        if ( empty( $badges ) ) {
            $badges[] = sprintf(
                '<span class="sp-scope-badge sp-scope-badge--empty">%s</span>',
                esc_html__( 'No scopes selected', 'snippet-press' )
            );
        }

        return implode( '', $badges );
    }

    /**
     * Summarise scope rules into a concise sentence.
     *
     * @param array<string,mixed> $rules Normalised scope rules.
     */
    protected function format_scope_rules_summary( array $rules ): string {
        $parts = [];

        if ( ! empty( $rules['include_post_ids'] ) && is_array( $rules['include_post_ids'] ) ) {
            $parts[] = sprintf(
                /* translators: %s is a list of post titles or IDs. */
                __( 'Includes posts: %s', 'snippet-press' ),
                $this->summarize_posts( $rules['include_post_ids'] )
            );
        }

        if ( ! empty( $rules['exclude_post_ids'] ) && is_array( $rules['exclude_post_ids'] ) ) {
            $parts[] = sprintf(
                __( 'Excludes posts: %s', 'snippet-press' ),
                $this->summarize_posts( $rules['exclude_post_ids'] )
            );
        }

        if ( ! empty( $rules['include_post_types'] ) && is_array( $rules['include_post_types'] ) ) {
            $types = array_map(
                static function ( $type ) {
                    $type = sanitize_key( (string) $type );
                    $object = get_post_type_object( $type );
                    if ( $object && isset( $object->labels->name ) ) {
                        return $object->labels->name;
                    }
                    return strtoupper( $type );
                },
                $rules['include_post_types']
            );

            if ( ! empty( $types ) ) {
                $parts[] = sprintf(
                    __( 'Allowed post types: %s', 'snippet-press' ),
                    implode( ', ', array_slice( $types, 0, 3 ) )
                );
            }
        }

        if ( ! empty( $rules['include_tax_terms'] ) && is_array( $rules['include_tax_terms'] ) ) {
            $parts[] = __( 'Requires specific taxonomy terms', 'snippet-press' );
        }

        if ( ! empty( $rules['url_patterns'] ) && is_array( $rules['url_patterns'] ) ) {
            $patterns = array_map(
                static function ( $pattern ) {
                    return trim( (string) $pattern );
                },
                $rules['url_patterns']
            );

            $patterns = array_filter( $patterns );

            if ( ! empty( $patterns ) ) {
                $parts[] = sprintf(
                    __( 'URL patterns: %s', 'snippet-press' ),
                    implode( ', ', array_slice( $patterns, 0, 3 ) )
                );
            }
        }

        return implode( ' • ', $parts );
    }

    /**
     * Determine a representative preview link for the current scope.
     *
     * @param array<int,string> $scopes Selected scopes.
     * @param array<string,mixed> $rules Scope rules.
     *
     * @return array<string,mixed>
     */
    protected function determine_preview_link( array $scopes, array $rules ): array {
        $labels = $this->preview_label_map();

        $result = [
            'url'     => '',
            'label'   => $labels['default'],
            'context' => 'home',
            'locked'  => false,
            'labels'  => $labels,
        ];

        if ( ! empty( $rules['include_post_ids'][0] ) ) {
            $id   = absint( $rules['include_post_ids'][0] );
            $link = $id ? get_permalink( $id ) : '';

            if ( $link ) {
                $title = get_the_title( $id );
                if ( '' === $title ) {
                    $title = sprintf( __( 'Post #%d', 'snippet-press' ), $id );
                }

                $label = sprintf( __( 'Preview "%s"', 'snippet-press' ), $title );

                $result['url']               = $link;
                $result['label']             = $label;
                $result['context']           = 'custom';
                $result['locked']            = true;
                $result['labels']['custom']  = $label;

                return $result;
            }
        }

        if ( ! empty( $rules['include_post_types'][0] ) ) {
            $type = sanitize_key( (string) $rules['include_post_types'][0] );

            if ( '' !== $type ) {
                $url = 'post' === $type ? admin_url( 'edit.php' ) : admin_url( 'edit.php?post_type=' . $type );
                $obj = get_post_type_object( $type );
                $label_name = $obj && isset( $obj->labels->name ) ? $obj->labels->name : strtoupper( $type );
                $label = sprintf( __( 'View %s list', 'snippet-press' ), $label_name );

                $result['url']              = $url;
                $result['label']            = $label;
                $result['context']          = 'custom';
                $result['locked']           = true;
                $result['labels']['custom'] = $label;

                return $result;
            }
        }

        if ( ! empty( $rules['url_patterns'][0] ) ) {
            $pattern = trim( (string) $rules['url_patterns'][0] );

            if ( '' !== $pattern ) {
                $normalized = ltrim( str_replace( '*', '', $pattern ), '/' );
                $url        = home_url( '/' . $normalized );

                $result['url']              = $url;
                $result['label']            = __( 'Preview matching URL', 'snippet-press' );
                $result['context']          = 'custom';
                $result['locked']           = true;
                $result['labels']['custom'] = $result['label'];

                return $result;
            }
        }

        $scopes = array_map( 'sanitize_key', $scopes );

        if ( in_array( 'admin', $scopes, true ) ) {
            $result['url']     = admin_url();
            $result['label']   = $labels['admin'];
            $result['context'] = 'admin';

            return $result;
        }

        if ( in_array( 'login', $scopes, true ) ) {
            $result['url']     = wp_login_url();
            $result['label']   = $labels['login'];
            $result['context'] = 'login';

            return $result;
        }

        if ( in_array( 'editor', $scopes, true ) ) {
            $result['url']     = admin_url( 'post-new.php' );
            $result['label']   = $labels['editor'];
            $result['context'] = 'editor';

            return $result;
        }

        if ( in_array( 'rest', $scopes, true ) ) {
            $result['url']     = rest_url();
            $result['label']   = $labels['rest'];
            $result['context'] = 'rest';

            return $result;
        }

        // Default to frontend preview.
        $result['url']     = home_url( '/' );
        $result['label']   = $labels['home'];
        $result['context'] = 'home';

        return $result;
    }

    /**
     * Provide default preview labels.
     */
    protected function preview_label_map(): array {
        return [
            'default' => __( 'Preview sample page', 'snippet-press' ),
            'home'    => __( 'Preview frontend', 'snippet-press' ),
            'admin'   => __( 'Open Dashboard', 'snippet-press' ),
            'login'   => __( 'Open login screen', 'snippet-press' ),
            'editor'  => __( 'Open Block Editor', 'snippet-press' ),
            'rest'    => __( 'Open REST API index', 'snippet-press' ),
        ];
    }

    /**
     * Summarise post IDs into human readable text.
     *
     * @param array<int,mixed> $ids List of IDs.
     */
    protected function summarize_posts( array $ids ): string {
        $ids = array_values(
            array_filter(
                array_map(
                    static function ( $id ) {
                        return absint( $id );
                    },
                    $ids
                )
            )
        );

        if ( empty( $ids ) ) {
            return '';
        }

        $labels = [];

        foreach ( $ids as $index => $id ) {
            if ( $index >= 3 ) {
                break;
            }

            $title = get_the_title( $id );

            if ( '' === $title ) {
                $title = sprintf( __( 'Post #%d', 'snippet-press' ), $id );
            }

            $labels[] = $title;
        }

        $remaining = count( $ids ) - count( $labels );

        if ( $remaining > 0 ) {
            $labels[] = sprintf( __( '%d more', 'snippet-press' ), $remaining );
        }

        return implode( ', ', $labels );
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
