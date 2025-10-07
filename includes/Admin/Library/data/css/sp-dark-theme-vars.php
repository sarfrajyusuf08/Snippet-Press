<?php
return [
  'slug' => 'sp-dark-hard-override',
  'title' => __( 'Dark Mode (Hard Override)', 'snippet-press' ),
  'type' => 'css',
  'scopes' => ['frontend'],
  'status' => 'enabled',
  'code' => <<<'CSS'
/* Scope all overrides to html.sp-dark */
html.sp-dark, html.sp-dark body {
  background-color:#131314 !important;
  color:#e9e9ea !important;
}

/* Site chrome & common containers */
html.sp-dark .site,
html.sp-dark .site-header,
html.sp-dark .site-footer,
html.sp-dark .content-area,
html.sp-dark .widget-area,
html.sp-dark .widget,
html.sp-dark .post,
html.sp-dark .page,
html.sp-dark article,
html.sp-dark section,
html.sp-dark aside,
html.sp-dark .wp-block-group,
html.sp-dark .entry-content,
html.sp-dark .entry-header,
html.sp-dark .entry-footer {
  background-color:#1e1f22 !important;
  color:#e9e9ea !important;
  border-color:#2f3033 !important;
}

/* Secondary surface (cards inside cards, tables zebra) */
html.sp-dark .wp-block-group.has-background,
html.sp-dark tbody tr:nth-child(even),
html.sp-dark pre, html.sp-dark code {
  background-color:#242526 !important;
  color:#e9e9ea !important;
  border-color:#2f3033 !important;
}

/* Typography */
html.sp-dark h1, html.sp-dark h2, html.sp-dark h3,
html.sp-dark h4, html.sp-dark h5, html.sp-dark h6 {
  color:#e9e9ea !important;
}
html.sp-dark .entry-meta,
html.sp-dark .posted-on,
html.sp-dark .byline,
html.sp-dark .cat-links,
html.sp-dark .tags-links {
  color:#a1a1aa !important;
}

/* Links */
html.sp-dark a { color:#93c5fd !important; }
html.sp-dark a:hover { color:#bfdbfe !important; }

/* Forms */
html.sp-dark input,
html.sp-dark textarea,
html.sp-dark select {
  background:#242526 !important;
  color:#e9e9ea !important;
  border:1px solid #2f3033 !important;
}
html.sp-dark input::placeholder,
html.sp-dark textarea::placeholder { color:#a1a1aa !important; }

/* Buttons */
html.sp-dark button,
html.sp-dark .button,
html.sp-dark input[type=submit],
html.sp-dark .wp-block-button__link {
  background:#4f9dff !important;
  color:#ffffff !important;
  border:none !important;
}
html.sp-dark button:hover,
html.sp-dark .button:hover,
html.sp-dark input[type=submit]:hover,
html.sp-dark .wp-block-button__link:hover {
  filter:brightness(1.1) !important;
}

/* Tables & quotes */
html.sp-dark table, html.sp-dark th, html.sp-dark td {
  border:1px solid #2f3033 !important;
  color:#e9e9ea !important;
}
html.sp-dark blockquote {
  border-left:3px solid #4f9dff !important;
  background:#242526 !important;
  color:#e9e9ea !important;
}

/* Your snippet badges/buttons */
html.sp-dark .sp-reading-time,
html.sp-dark .sp-share-btn {
  background:#242526 !important;
  color:#e9e9ea !important;
  border:1px solid #2f3033 !important;
}

/* Smoothen the switch */
html.sp-dark *, html.sp-dark body {
  transition: background-color .25s ease, color .25s ease, border-color .25s ease;
}
CSS
];
