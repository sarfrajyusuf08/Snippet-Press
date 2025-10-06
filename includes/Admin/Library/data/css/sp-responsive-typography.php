<?php
/**
 * Responsive Tables & Images
 * --------------------------
 * Ensures that tables scroll horizontally on small screens,
 * images scale correctly within content, and paragraphs have better readability.
 *
 * - Safe CSS-only snippet
 * - Non-destructive: does not override theme colors
 * - Improves mobile UX and typography consistency
 */

return [
    'slug'        => 'sp-responsive-typography',
    'title'       => __( 'Responsive Tables & Images', 'snippet-press' ),
    'description' => __( 'Makes tables scrollable on small screens and ensures images and text scale properly.', 'snippet-press' ),
    'category'    => 'frontend',
    'tags'        => [ 'css', 'responsive', 'ux', 'readability' ],
    'highlights'  => [
        __( 'Tables become horizontally scrollable on mobile.', 'snippet-press' ),
        __( 'Images automatically resize to fit content width.', 'snippet-press' ),
        __( 'Improves typography and spacing for better readability.', 'snippet-press' ),
    ],
    'code'        => <<<'CSS'
/* Responsive Images */
.entry-content img,
.post-content img {
  max-width: 100%;
  height: auto;
  display: block;
  margin: 1rem auto;
}

/* Responsive Tables */
.entry-content table,
.post-content table {
  width: 100%;
  border-collapse: collapse;
  display: block;
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
}

.entry-content table th,
.entry-content table td,
.post-content table th,
.post-content table td {
  border: 1px solid #e5e7eb;
  padding: 0.5rem;
  text-align: left;
  vertical-align: top;
}

/* Better Readability */
.entry-content,
.post-content {
  line-height: 1.7;
  font-size: 1rem;
  color: #111827;
}

.entry-content p,
.post-content p {
  margin-bottom: 1.2rem;
}

/* Optional: Style code blocks and quotes */
.entry-content pre,
.post-content pre {
  background: #f9fafb;
  padding: 1rem;
  border-radius: 6px;
  overflow-x: auto;
}

.entry-content blockquote,
.post-content blockquote {
  border-left: 3px solid #e5e7eb;
  padding-left: 1rem;
  color: #374151;
  font-style: italic;
}
CSS,
    'type'        => 'css',
    'scopes'      => [ 'frontend' ],
    'priority'    => 10,
    'status'      => 'disabled',
];
