# Integrations Outline

## Shortcode
- Register `[snippetvault id="123"]` via `add_shortcode`.
- Attributes: `id` (required), `fallback` (text), `context` (override scope), `variables` (JSON string).
- Renders snippet output if type PHP/JS/CSS; for JS/CSS returns wrapper with inline code block unless executed globally.

## Gutenberg Block
- Dynamic block `snippet-press/snippet`.
- Block attributes: `snippetId`, `displayTitle`, `showDescription`, `align`.
- Editor components fetch snippets via REST endpoint for searchable dropdown.
- Frontend uses same runtime renderer as shortcode.

## Conditions Engine
- Service class `ConditionsEvaluator` interprets stored condition schema.
- Hooks into `parse_request` to evaluate once per request and caches decisions per snippet ID.
- Provides helper `should_execute( $snippet_id, $context )` returning bool.

## Variables & Placeholders
- Placeholder registry with default tokens (`SITE_URL`, `HOME_URL`, `ADMIN_URL`, `THEME_DIR`, `USER_ID`, `USER_EMAIL`).
- Support for custom placeholders with filters `snippet_press_placeholder_{key}`.
- Resolver handles nested variables, ensures escaping per snippet type (e.g., JS escaping, CSS escaping).
- Preview service renders sample output for admin UI.

## REST API
- Namespace `snippet-press/v1`.
- Endpoints: `/snippets` (list/search), `/snippets/(?P<id>\d+)`, `/lint`, `/import`, `/export`, `/settings`.
- Permissions aligned with manage_snippets capability.

## CLI Integration
- Commands registered under `snippet-press` namespace.
- `list`, `enable`, `disable`, `export`, `import`, `safe-mode` operations.
- Shared services reused from runtime to avoid duplication.