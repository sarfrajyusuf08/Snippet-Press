# Admin UX Plan

## Navigation
- Custom top-level menu `Snippet Press` with submenus: `All Snippets`, `Add New`, `Safe Mode`, `Import/Export`, `Settings`.
- Snippet list uses WP_List_Table with columns: status toggle, name, type badge, scopes, priority, tags, modified, actions (edit, clone, export, favorite, pin).

## List Enhancements
- Filters for type, scope, status, tags, favorites, pinned.
- Search box with fuzzy matching backed by transient index of titles/descriptions/content snippets.
- Bulk actions: enable, disable, delete, export, add tag.
- Row actions include enable/disable toggle, clone, view revisions.

## Editor Screen
- Primary code editor panel with CodeMirror 6 instance and lint indicators; snippet type switcher (PHP/JS/CSS) updates syntax highlighting and enqueue defaults.
- Sidebar panels:
  - Scope toggles (front, admin, login, rest, block) with tooltip context.
  - Conditions builder with presets (All pages, Singular, Archive, Home) and advanced fields (URL include/exclude, post types, taxonomies, user roles).
  - Variables manager table (key/value, type selection, preview substitution using placeholders like `${SITE_URL}`).
  - Execution priority slider and safe-mode info.
  - Status toggle (enabled/disabled) with guardrail warnings.

## Safe Mode Screen
- Banner describing safe mode state and snippet that triggered it.
- Controls: view error log excerpt, revert to previous revision, re-enable snippet, dismiss safe mode.
- Quick links to open editor when safe mode off.

## Import / Export Screen
- Upload area with validation for JSON bundle / Code Snippets XML.
- Export table with checkboxes, type/scopes filters, options for include settings.
- Result summary with download link.

## Settings Screen
- General: default scopes, default status, safe mode toggle, size limits.
- Linting: enable PHP lint (php -l command path), JS/CSS lint thresholds, show inline results.
- Advanced: CLI connectivity, REST API base path, debug logging.

## Notices & Feedback
- Admin notices for safe mode activation, lint failures, guardrail violations.
- Snackbar-style toasts after saving snippet.

## Accessibility
- All controls keyboard accessible, ARIA labels for toggles, high-contrast badges for types/scopes.

## Block & Shortcode Discovery
- Tab in editor to preview shortcode `[snippetvault id="123"]` and register block with inserter description.