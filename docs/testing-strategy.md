# Testing, Linting, and Deployment Strategy

## PHP Testing
- Use PHPUnit with WP test suite via wp-env or wp-browser.
- Unit tests for condition evaluator, variable resolver, safe mode handler.
- Integration tests covering snippet execution contexts using wp-env docker.

## JS/CSS Testing
- ESLint with recommended + WordPress config for admin scripts.
- Stylelint for CSS assets.
- Jest for React block components with @wordpress/scripts.

## Linting Hooks
- Composer scripts for `composer lint` (PHP_CodeSniffer with WordPress coding standards), `composer test`.
- npm scripts for JS lint/test/build.

## CI Pipeline
- GitHub Actions workflow: matrix for PHP versions (7.4, 8.0, 8.1, 8.2), runs `composer install`, `composer lint`, `composer test`.
- Node job runs `npm install`, `npm run lint`, `npm run build`.

## Local Development
- Provide `wp-env.json` for quick spin-up with plugin activated.
- Include VS Code devcontainer config optionally.

## Deployment
- `build` script produces production-ready zip: compiles assets, strips dev files, generates POT file.
- Tagging instructions in README for release process.

## Safe Mode Testing
- Automated scenario tests triggering simulated fatal error to verify rollback.
- Manual checklist documented in `/docs/testing-scenarios.md`.