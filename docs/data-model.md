# Snippet Press Data Model

## Storage Overview
- Custom post type `sv_snippet` with capability type `snippet`.
- Default post statuses: `publish` (enabled), `draft` (disabled), `pending` (needs review), `trash`.
- Revision support enabled for rollback history.

## Core Fields
| Field | Storage | Type | Notes |
| ----- | ------- | ---- | ----- |
| Snippet Name | `post_title` | string | Human-readable name. |
| Description | `post_excerpt` | string | Short summary shown in listings. |
| Code | `post_content` | string | Raw snippet code. |
| Snippet Type | meta `_sv_type` | enum | `php`, `js`, `css`. |
| Scopes | meta `_sv_scopes` | array<string> | `front`, `admin`, `login`, `rest`, `block`. Stored JSON-encoded. |
| Conditions | meta `_sv_conditions` | array | Normalized structure describing basic conditions (location, URL patterns). JSON. |
| Priority | meta `_sv_priority` | int | Execution ordering; lower numbers run first. Defaults to `10`. |
| Status | meta `_sv_status` | enum | `enabled`, `disabled`. Mirrored with post status for clarity. |
| Safe Mode Flag | meta `_sv_safe_mode_flag` | bool | Indicates snippet was auto-disabled due to fatal. |
| Last Execution Hash | meta `_sv_last_hash` | string | SHA1 of code + config for quick change detection. |
| Variables | meta `_sv_variables` | array | Associative array of key/value with placeholder preview. |
| Placeholder Usage | meta `_sv_placeholders` | array | Tracks referenced placeholders for validation. |
| Favorite | meta `_sv_favorite` | bool | User-specific favorites stored via user meta in addition. |
| Pinned | meta `_sv_pinned` | bool | Controls ordering in admin list table. |
| Tags | taxonomy `sv_snippet_tag` | string | Custom taxonomy for tags. |

## Auxiliary Storage
- User meta `sv_user_favorites` and `sv_user_pins` hold lists of snippet IDs for per-user convenience.
- Option `sv_settings` stores plugin-wide settings, including safe mode state, default scopes, guardrail thresholds, import/export settings.
- Option `sv_safe_mode_last_snippet` references snippet that triggered safe mode for quick rollback UI.
- Transient `sv_lint_results_{post_id}` caches lint outputs to avoid repeat checks.

## Import / Export Schema (v1)
```json
{
  "version": 1,
  "exported_at": "2025-09-27T22:17:00Z",
  "site_url": "https://example.com",
  "snippets": [
    {
      "id": 123,
      "name": "Example Snippet",
      "description": "Demo",
      "type": "php",
      "code": "<?php echo 'Hello'; ?>",
      "scopes": ["front", "admin"],
      "conditions": {
        "mode": "all_pages"
      },
      "priority": 10,
      "status": "enabled",
      "variables": {
        "SITE_URL": "https://example.com"
      },
      "placeholders": ["SITE_URL"],
      "meta": {
        "favorite": true,
        "pinned": false
      },
      "tags": ["demo", "general"],
      "created_at": "2025-09-20T18:00:00Z",
      "updated_at": "2025-09-25T21:00:00Z"
    }
  ]
}
```
- Import accepts Snippet Press bundle or Code Snippets export. Mapping stored via helper to convert metadata and scopes.

## Condition Schema
- `mode`: `all`, `singular`, `archive`, `home`.
- `include_urls`: array of glob-style patterns (e.g. `/shop/*`).
- `exclude_urls`: same as above.
- `post_types`: array (for singular).
- `taxonomies`: keyed arrays for term matching.
- `user_roles`: restrict execution to matching roles.

## Guardrails
- Option `max_php_snippet_size` default 20 KB, `max_js_snippet_size` 40 KB, `max_css_snippet_size` 20 KB.
- Lint results stored per snippet revision for audit.

## Safe Mode Flow
1. Store last modified snippet ID in `sv_safe_mode_last_snippet` option.
2. On fatal detection, flag snippet, disable status, enter safe mode.
3. Admin notice invites rollback; rollback restores previous revision or re-enables stable version.

## CLI Metadata
- `wp snippet-press list` uses WP_Query on CPT with filters by status/type/tag.
- `wp snippet-press enable <id>` flips status and meta.
- `wp snippet-press export --ids=1,2 --path=...` dumps bundle schema.