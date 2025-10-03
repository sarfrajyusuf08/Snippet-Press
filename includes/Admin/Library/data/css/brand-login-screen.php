<?php

return [
    'slug'        => 'brand-login-screen',
    'title'       => __( 'Brand the Login Screen', 'snippet-press' ),
    'description' => __( 'Apply custom colours and optional logo styling to the WordPress login form.', 'snippet-press' ),
    'category'    => 'design',
    'tags'        => [ 'login', 'branding' ],
    'highlights'  => [
        __( 'Refresh the background and form colours with modern styling.', 'snippet-press' ),
        __( 'Includes a placeholder logo rule you can update after installing.', 'snippet-press' ),
    ],
    'code'        => <<<'CSS'
body.login {
    background: linear-gradient(135deg, #312e81 0%, #4338ca 50%, #3730a3 100%);
    font-family: "Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
}

body.login #login {
    padding: 40px 30px;
    border-radius: 12px;
    box-shadow: 0 25px 50px -12px rgba(17, 24, 39, 0.45);
    background-color: rgba(255, 255, 255, 0.9);
}

body.login #login h1 a {
    background-image: url('https://wordpress.org/style/images/about/WordPress-logotype-wmark.png');
    background-size: contain;
    width: 280px;
    height: 80px;
}

body.login #loginform {
    border: none;
    box-shadow: none;
}

body.login .button-primary {
    background: #4338ca;
    border-color: #3730a3;
}

body.login .button-primary:hover,
body.login .button-primary:focus {
    background: #312e81;
    border-color: #1e1b4b;
}
CSS,
    'type'        => 'css',
    'scopes'      => [ 'login' ],
    'priority'    => 10,
    'status'      => 'disabled',
    'notes'       => __( 'Swap the logo URL for your own image after installing the snippet.', 'snippet-press' ),
];
