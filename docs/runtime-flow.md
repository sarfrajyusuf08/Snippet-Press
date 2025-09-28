# Runtime Execution Flow

## Bootstrapping
1. Plugin loads and registers autoloader.
2. Settings loaded from `sv_settings` option with defaults.
3. Snippet registry service queries enabled snippets grouped by type/scope with caching.
4. Safe mode monitor checks global flag and prevents execution of flagged snippet until resolved.

## PHP Snippets
- Loaded during `plugins_loaded` (priority 100) to respect dependency order.
- Conditions evaluated just-in-time using request context (is_singular, is_archive, matching URL pattern via `wp_match_url`).
- Snippets executed within isolated closure; output buffered. If fatal occurs, safe mode toggles snippet and records error.
- Optional hooks for before/after execution for logging.

## JS Snippets
- Enqueued via `wp_enqueue_scripts`, `admin_enqueue_scripts`, `login_enqueue_scripts`, `rest_api_init`, `enqueue_block_assets` depending on scopes.
- Each snippet registered as inline script attached to virtual handle `snippet-press-{ID}` with priority ordering via hook priority adjustments.

## CSS Snippets
- Similar to JS using `wp_add_inline_style` attached to base handles per scope (`snippet-press-front`, `snippet-press-admin`, etc.).
- For block scope, hook into `enqueue_block_editor_assets`.

## Prioritization
- Snippets sorted per scope by `_sv_priority`; duplicates maintain deterministic order by ID.
- Hook registrations use dynamic priority to reflect ordering.

## Safe Mode Recovery
- Fatal handler uses shutdown function + error_get_last to detect fatal from snippet context.
- On detection: marks snippet disabled, stores error in option/log, surfaces admin notice, toggles safe mode flag to prevent further snippet execution until resolved.

## Syntax Checks
- On save request, PHP lint executed via `php -l` (configurable path) against temp file.
- JS/CSS lint performed via lightweight regex-based guard (no network). Results stored in transient; critical errors block save; warnings show notice.
- File size thresholds validated before save.

## Conditions
- Basic contexts computed using WordPress conditionals.
- URL include/exclude patterns compiled into regex with caching; evaluated early to short-circuit.

## Variables & Placeholders
- Global placeholders: `${SITE_URL}`, `${THEME_DIR}`, `${USER_ID}`, etc. Replaced at runtime with sanitized values depending on scope.
- Custom variables defined per snippet replaced before execution/enqueue; missing variables trigger warning but proceed with fallback.

## Caching
- Enabled snippets cached via `wp_cache_set` with invalidation on snippet save/update/delete and settings changes.
- Runtime uses cached array to reduce DB queries.

## Logging
- Errors and lint results stored in custom table `wp_sv_logs` (optional) or fallback to file under `uploads/snippet-press/logs` when enabled.
- Safe mode entries include stack snippet ID, error message, timestamp.