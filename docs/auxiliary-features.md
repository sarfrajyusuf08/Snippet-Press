# Auxiliary Features Plan

## Search & Discovery
- Build searchable index stored in transient `sv_search_index` (array of snippet IDs with weighted tokens).
- Rebuild index on snippet save/delete and via WP-CLI command `reindex`.
- Fuzzy search implemented in PHP using Levenshtein + partial matches.

## Favorites & Pinning
- Favorites stored per-user in user meta `sv_favorites` (array of IDs) managed via REST + AJAX.
- Pins stored globally per snippet meta `_sv_pinned`; pinned items float to top of list view.

## Import/Export
- JSON bundle schema (v1) defined in data model doc.
- Import validators ensure type, scopes, guardrail compliance.
- Code Snippets XML import: parse `<snippet>` nodes, map fields.
- Exports include settings when `includeSettings` flag true.

## Safe Mode Logs
- Optional log viewer in Safe Mode screen pulling entries from plugin log or WP debug log.

## Analytics (Optional)
- Track snippet execution count, last run timestamp via meta updated asynchronously (deferred action scheduler).

## Notices
- Dismissible admin pointers to highlight new features.

## Multi-site Support
- Network-level settings for enabling safe mode, default guardrails.
- Network admin list showing snippets across sites with ability to replicate.

## WP-CLI Commands (Extended)
- `snippet-press search <term>` to query index.
- `snippet-press import --format=code-snippets` to handle XML imports.
- `snippet-press safe-mode --status` to report current status.