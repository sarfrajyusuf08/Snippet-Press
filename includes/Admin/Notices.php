<?php

namespace SnippetPress\Admin;

/**
 * Lightweight admin notice helper scoped to the current user.
 */
class Notices {
    private const TRANSIENT_PREFIX = 'sp_admin_notices_';

    /**
     * Queue a notice for the current user to be shown on the next page load.
     */
    public static function add( string $message, string $type = 'success' ): void {
        $user_id = get_current_user_id();

        if ( ! $user_id ) {
            return;
        }

        $key     = self::TRANSIENT_PREFIX . $user_id;
        $notices = get_transient( $key );

        if ( ! is_array( $notices ) ) {
            $notices = [];
        }

        $notices[] = [
            'message' => $message,
            'type'    => $type,
        ];

        set_transient( $key, $notices, 2 * MINUTE_IN_SECONDS );
    }

    /**
     * Render and clear queued notices.
     */
    public static function render(): void {
        $user_id = get_current_user_id();

        if ( ! $user_id ) {
            return;
        }

        $key     = self::TRANSIENT_PREFIX . $user_id;
        $notices = get_transient( $key );

        if ( ! is_array( $notices ) || empty( $notices ) ) {
            return;
        }

        delete_transient( $key );

        foreach ( $notices as $notice ) {
            $type    = ! empty( $notice['type'] ) ? $notice['type'] : 'info';
            $message = ! empty( $notice['message'] ) ? $notice['message'] : '';

            if ( '' === $message ) {
                continue;
            }

            printf(
                '<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>',
                esc_attr( $type ),
                esc_html( $message )
            );
        }
    }
}

