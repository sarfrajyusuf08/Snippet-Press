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
