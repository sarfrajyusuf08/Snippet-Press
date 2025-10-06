<?php
/**
 * Scroll Progress Bar (Top)
 * -------------------------
 * Displays a thin progress bar at the top of the page that fills as the user scrolls.
 * - Vanilla JavaScript (no dependencies)
 * - Lightweight and accessible
 * - File is PHP (returns array), but snippet type is "js"
 */

return [
    'slug'        => 'sp-scroll-progress',
    'title'       => __( 'Scroll Progress Bar', 'snippet-press' ),
    'description' => __( 'Shows a thin bar at the top that fills based on scroll position.', 'snippet-press' ),
    'category'    => 'frontend',
    'tags'        => [ 'ux', 'engagement', 'ui' ],
    'highlights'  => [
        __( 'No dependencies; pure JavaScript.', 'snippet-press' ),
        __( 'Adds a fixed, 3px progress bar at the top of the page.', 'snippet-press' ),
        __( 'Automatically updates on scroll and resize.', 'snippet-press' ),
    ],
    'code'        => <<<'JS'
/**
 * Scroll Progress Bar
 * Creates a fixed 3px bar at the top of the page and updates its width
 * to reflect how far the user has scrolled.
 */
(function(){
  // Avoid duplicates if re-initialized
  if (document.getElementById('sp-scroll-progress')) return;

  // Create the bar element
  var bar = document.createElement('div');
  bar.id = 'sp-scroll-progress';
  // Use a CSS custom property for width so we can update cheaply
  document.documentElement.style.setProperty('--sp-progress', '0%');
  bar.setAttribute(
    'style',
    [
      'position:fixed',
      'top:0',
      'left:0',
      'height:3px',
      'width:var(--sp-progress)',
      'background:#3b82f6',   // customize color as needed
      'z-index:99999',
      'transition:width .1s linear'
    ].join(';') + ';'
  );
  // Insert as first child of body to avoid layout shifts
  if (document.body.firstChild) {
    document.body.insertBefore(bar, document.body.firstChild);
  } else {
    document.body.appendChild(bar);
  }

  // Compute and set progress percentage
  function update(){
    var doc = document.documentElement;
    var scrollTop = doc.scrollTop || document.body.scrollTop;
    var scrollHeight = (doc.scrollHeight || 0) - (doc.clientHeight || 0);
    var pct = scrollHeight > 0 ? (scrollTop / scrollHeight) * 100 : 0;
    doc.style.setProperty('--sp-progress', pct.toFixed(2) + '%');
  }

  // Update on scroll and resize
  window.addEventListener('scroll', update, { passive: true });
  window.addEventListener('resize', update);
  // Initial paint
  update();
})();
JS,
    'type'        => 'js',
    'scopes'      => [ 'frontend' ],
    'priority'    => 10,
    'status'      => 'disabled',
];
