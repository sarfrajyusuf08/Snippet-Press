<?php
/**
 * Plugin Name:       Snippet Press
 * Plugin URI:        https://example.com/snippet-press
 * Description:       Manage and execute custom PHP, JS, and CSS snippets with advanced controls.
 * Version:           0.1.0
 * Author:            Snippet Press Team
 * Author URI:        https://example.com
 * Text Domain:       snippet-press
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'SNIPPET_PRESS_VERSION', '0.1.0' );
define( 'SNIPPET_PRESS_FILE', __FILE__ );
define( 'SNIPPET_PRESS_BASENAME', plugin_basename( __FILE__ ) );
define( 'SNIPPET_PRESS_DIR', plugin_dir_path( __FILE__ ) );
define( 'SNIPPET_PRESS_URL', plugin_dir_url( __FILE__ ) );

/**
 * Basic PSR-4 style autoloader for plugin classes.
 */
spl_autoload_register(
    static function ( $class ) {
        if ( 0 !== strpos( $class, 'SnippetVault\\' ) ) {
            return;
        }

        $path = str_replace( [ 'SnippetVault\\', '\\' ], [ '', DIRECTORY_SEPARATOR ], $class );
        $file = SNIPPET_PRESS_DIR . 'includes' . DIRECTORY_SEPARATOR . $path . '.php';

        if ( file_exists( $file ) ) {
            require_once $file;
        }
    }
);

require_once SNIPPET_PRESS_DIR . 'includes' . DIRECTORY_SEPARATOR . 'Plugin.php';

/**
 * Bootstrap the plugin once plugins are loaded.
 */
add_action(
    'plugins_loaded',
    static function () {
        $plugin = SnippetVault\Plugin::instance();
        $plugin->boot();
    },
    5
);

register_activation_hook( __FILE__, [ 'SnippetVault\\Plugin', 'activate' ] );
register_deactivation_hook( __FILE__, [ 'SnippetVault\\Plugin', 'deactivate' ] );