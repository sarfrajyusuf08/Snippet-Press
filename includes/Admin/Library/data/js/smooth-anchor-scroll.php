<?php

return [
    'slug'        => 'smooth-anchor-scroll',
    'title'       => __( 'Enable Smooth Anchor Scrolling', 'snippet-press' ),
    'description' => __( 'Adds smooth scrolling behaviour for in-page anchor links on the front-end.', 'snippet-press' ),
    'category'    => 'content',
    'tags'        => [ 'ux' ],
    'highlights'  => [
        __( 'Improves perceived performance for readers navigating long pages.', 'snippet-press' ),
        __( 'Enhances accessibility by keeping the target element in focus.', 'snippet-press' ),
    ],
    'code'        => <<<'JS'
document.addEventListener('DOMContentLoaded', function () {
    var links = document.querySelectorAll("a[href*='#']:not([href='#'])");

    links.forEach(function (link) {
        link.addEventListener('click', function (event) {
            var hash = this.getAttribute('href').split('#')[1];
            if (!hash) {
                return;
            }

            var target = document.getElementById(hash);
            if (!target) {
                return;
            }

            event.preventDefault();
            target.scrollIntoView({ behavior: 'smooth' });
            if (typeof target.focus === 'function') {
                target.setAttribute('tabindex', '-1');
                target.focus({ preventScroll: true });
            }
        });
    });
});
JS,
    'type'        => 'js',
    'scopes'      => [ 'frontend' ],
    'priority'    => 10,
    'status'      => 'disabled',
    'notes'       => __( 'Install the snippet then adjust the selector if you only want to target specific anchor links.', 'snippet-press' ),
];
