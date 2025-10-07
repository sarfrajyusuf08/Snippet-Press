# Adding Snippets to the Library

Snippet Press discovers predefined snippets by loading the PHP files that live in:

- `includes/Admin/Library/data/php/`
- `includes/Admin/Library/data/js/`
- `includes/Admin/Library/data/css/`

Each file must return an array describing the snippet. You can copy any of the existing
files in those folders as a starting point. At a minimum the array should define the
`slug`, `title`, `description`, `tags`, `category`, `code`, and `type` keys. Example:

```php
<?php

return [
    'slug'        => 'my-custom-snippet',
    'title'       => __( 'My Custom Snippet', 'snippet-press' ),
    'description' => __( 'Short description of what the snippet does.', 'snippet-press' ),
    'category'    => 'admin',
    'tags'        => [ 'branding', 'ux' ],
    'highlights'  => [
        __( 'Mention important behaviour here.', 'snippet-press' ),
    ],
    'code'        => <<<'PHP'
// Your snippet code.
PHP,
    'type'        => 'php',
    'scopes'      => [ 'admin' ],
    'priority'    => 10,
    'status'      => 'disabled',
];
```

The snippet type (`php`, `js`, or `css`) should match the directory you place the file in.
Once the file is saved it will automatically appear in the Snippet Library list inside
the WordPress admin. Remove a file to hide it from the library.

* ## Best Practices
 * - Always write **eval-safe PHP**:
 *   - Do not close PHP tags (`?>`).
 *   - Do not place raw HTML or JavaScript directly inside PHP.
 *   - Use hooks (`add_action`, `add_filter`) and `echo` or `wp_add_inline_script/style`.
 * - Keep all strings translatable using WordPress i18n functions (`__()` or `_e()`).
 * - Use unique slugs to avoid conflicts.
 * - Keep inline code minimal; complex snippets should enqueue files or use functions.
 *

## Manual Snippet Settings

When you add a snippet directly from **Add New → Snippet**, use the **Snippet Settings** panel:

- **Snippet Type** decides how the runtime executes the code (PHP, JavaScript, CSS, or HTML).
- **Scopes** control where the snippet loads (Global, Frontend, Admin, Login, Editor).
- **Advanced targeting** accepts comma-separated values (e.g. Post IDs `12,45`, Post Types `post,page`, URL patterns `/shop/*`) and stores them as `_sp_scope_rules`.

Leave any targeting field blank to ignore it. Disabled snippets or missing rules continue to behave exactly as before, so existing snippets keep working without changes.
