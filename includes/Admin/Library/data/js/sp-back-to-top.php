<?php
/**
 * Back to Top Button
 * ------------------
 * Adds a small floating "↑" button at the bottom-right corner of the screen.
 * It becomes visible after scrolling down 300px and smoothly scrolls to the top when clicked.
 *
 * - Pure vanilla JavaScript (no dependencies)
 * - Lightweight and accessible (uses ARIA label)
 * - Easy to restyle using inline or custom CSS
 */

return [
    'slug'        => 'sp-back-to-top',
    'title'       => __( 'Back to Top Button', 'snippet-press' ),
    'description' => __( 'Displays a floating button that scrolls smoothly to the top of the page.', 'snippet-press' ),
    'category'    => 'frontend',
    'tags'        => [ 'ux', 'utility', 'button', 'scroll' ],
    'highlights'  => [
        __( 'Adds an accessible, floating Back to Top button.', 'snippet-press' ),
        __( 'Smooth scroll behavior with no external libraries.', 'snippet-press' ),
        __( 'Easily customizable styling.', 'snippet-press' ),
    ],
    'code'        => <<<'JS'
/**
 * Back to Top Button
 * Appears after 300px of scrolling and smoothly scrolls to the top when clicked.
 */
(function(){
  // Avoid duplicates if snippet re-runs
  if (document.getElementById('sp-back-to-top')) return;

  // Create button element
  var btn = document.createElement('button');
  btn.id = 'sp-back-to-top';
  btn.setAttribute('aria-label','Back to top');
  btn.textContent = '↑';

  // Apply minimal styling
  btn.setAttribute('style',
    [
      'position:fixed',
      'right:16px',
      'bottom:16px',
      'padding:10px 12px',
      'border-radius:9999px',
      'border:1px solid #e5e7eb',
      'background:#fff',
      'box-shadow:0 2px 8px rgba(0,0,0,.08)',
      'cursor:pointer',
      'display:none',
      'z-index:99999',
      'font-size:1rem'
    ].join(';') + ';'
  );

  // Add button to page
  document.body.appendChild(btn);

  // Scroll to top on click
  btn.addEventListener('click', function(){
    window.scrollTo({top:0, behavior:"smooth"});
  });

  // Show/hide button based on scroll position
  function toggle(){
    btn.style.display = (window.scrollY > 300) ? 'inline-flex' : 'none';
  }

  // Bind events
  window.addEventListener('scroll', toggle, { passive: true });
  window.addEventListener('resize', toggle);
  // Initial check
  toggle();
})();
JS,
    'type'        => 'js',
    'scopes'      => [ 'frontend' ],
    'priority'    => 10,
    'status'      => 'disabled',
];
