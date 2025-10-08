<?php
/**
 * Plugin Name:       Snippet Press
 * Plugin URI:        https://example.com/snippet-press
 * Description:       Manage and execute custom PHP, JS, and CSS snippets with advanced controls.
 * Version:           1.0.0
 * Author:            Snippet Press Team
 * Author URI:        https://example.com
 * Text Domain:       snippet-press
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'SNIPPET_PRESS_VERSION', '1.0.0' );
define( 'SNIPPET_PRESS_FILE', __FILE__ );
define( 'SNIPPET_PRESS_BASENAME', plugin_basename( __FILE__ ) );
define( 'SNIPPET_PRESS_DIR', plugin_dir_path( __FILE__ ) );
define( 'SNIPPET_PRESS_URL', plugin_dir_url( __FILE__ ) );

/**
 * Basic PSR-4 style autoloader for plugin classes.
 */
spl_autoload_register(
    static function ( $class ) {
        if ( 0 !== strpos( $class, 'SnippetPress\\' ) ) {
            return;
        }

        $path = str_replace( [ 'SnippetPress\\', '\\' ], [ '', DIRECTORY_SEPARATOR ], $class );
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
        $plugin = SnippetPress\Plugin::instance();
        $plugin->boot();
    },
    5
);


// Disable Visual code editor in the plugin

// Only on your plugin pages Visual editor OFF
add_action('current_screen', function($screen){
    if ( ! $screen ) return;

    // Add here you Screen id IDs/Slugs where you want to disable the Visual editor
    // You can find the Screen ID by uncommenting the debugging code below
    // Example: toplevel_page_sp-code-snippet, snippets_page_sp-code-snippet
    $my_screens = [
        'toplevel_page_sp-code-snippet',
        'sp_snippet'
    ];

    if ( in_array($screen->id, $my_screens, true) ) {
        // 1) Rich editor OFF
        add_filter('user_can_richedit', '__return_false', 99);

        // 2) TinyMCE auto-formatting OFF
        add_filter('tiny_mce_before_init', function($init){
            $init['wpautop']               = false;
            $init['forced_root_block']     = '';
            $init['force_br_newlines']     = false;
            $init['force_p_newlines']      = false;
            $init['convert_newlines_to_brs']= false;
            return $init;
        }, 99);

        // 3) wp_editor defaults override (If you use wp_editor)
        add_filter('wp_editor_settings', function($settings, $editor_id){
            $settings['tinymce']   = false;
            $settings['quicktags'] = true;
            $settings['wpautop']   = false;
            return $settings;
        }, 10, 2);
    }
});

// Debugging: Current Screen ID check
/*add_action('current_screen', function($screen){
     error_log( 'Current Screen ID: ' . $screen->id );
 });
*/



register_activation_hook( __FILE__, [ 'SnippetPress\Plugin', 'activate' ] );
register_deactivation_hook( __FILE__, [ 'SnippetPress\Plugin', 'deactivate' ] );
