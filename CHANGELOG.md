# Changelog

## 4.0.0 — 2026-05-24

Migrated from Elgg 3.x to 4.x (`elgg-migrate-iv5j8`).

### Removed

- `start.php` — replaced by `\CsvProcess\Bootstrap` class extending
  `\Elgg\DefaultPluginBootstrap`. The 3.x closure pattern is gone.
- `manifest.xml` — 4.x reads all plugin metadata from `composer.json`.
- `vroom` plugin dependency dropped — the `forward()` redirect in the
  action already disconnects the browser before the shutdown handler runs,
  and `jumbojett/vroom` isn't published on Packagist. Operators who still
  want the original behaviour can install `vroom` alongside.
- `elgg_register_admin_menu_item()` call — function removed without
  deprecation in 4.x; replaced by declarative `menus.page.csv_process`
  entry in `elgg-plugin.php`.
- Namespaced helper `csv_process\log` — renamed to
  `CsvProcess\CsvProcessor::writeLog` to dodge future PHP/Elgg
  reserved-word friction (carry-forward from 2.x → 3.x notes).

### Added

- `classes/CsvProcess/Bootstrap.php` — minimal Bootstrap (CSS extension).
- `classes/CsvProcess/CsvProcessor.php` — extracted `process_csv` /
  `log` into a static utility class invoked as the shutdown handler.
- `classes/CsvProcess/DemoHandler.php` — extracted demo callback
  registration and per-row handler; registration uses the 4.x
  `\Elgg\Hook` single-arg signature.
- `elgg-plugin.php`: declarative `actions`, `hooks`, `menus`, and
  `bootstrap` keys (all class refs as string literals per cross-plugin
  learning rule).
- `composer.json`: `psr-4` autoload of `CsvProcess\`; `extra.elgg-plugin.id`.
- `docker/`: per-branch elgg4 Docker stack (Iron Law 12).

### Changed

- `composer.json`: `elgg/elgg ^3.0` → `^4.0`, `php >=7.2` → `>=7.4`,
  `composer/installers ^1.0 || ^2.0` → `^2.0`. Stripped `"version"` field.
- `actions/csv_process.php`: tightened `callback` input validation
  (string cast, expects callable-string from the hook map); registers the
  shutdown handler as `[CsvProcessor::class, 'processCsv']` instead of a
  namespaced function string.
- `views/default/forms/csv_process.js`: `elgg.echo()` → `i18n.echo()`
  (AMD rule `013b-amd-removed-apis`).
- `views/default/csv_process/ajax/progress.js`: rewritten to use
  `elgg/Ajax.view()` instead of the removed `elgg.get()` legacy helper.
  Preserved `Function.prototype.bind` (AST rule's `.bind()` → `.on()`
  rewrite was a false positive on non-jQuery bind).
- README.md: rewritten for 4.x layout, with hook registration shown via
  declarative `elgg-plugin.php` config.

### Gates passed

- Elgg 4.x Docker stack activation: PASS
- Homepage renders (7320 bytes): PASS
- Login page renders (7320 bytes): PASS
- No PHP Fatal/Error in Apache log: PASS
- PHP syntax check: PASS
- PHP_CodeSniffer (Elgg standard): PASS
- PostMigrationVerifier (4.x boundary): PASS
- SecuritySweep: PASS

### Known carry-forward to 4.x → 5.x

- `forward()` calls in actions: still functional via `deprecated-4.0.php`;
  refactor to `elgg_redirect_response()` / `elgg_error_response()` at the
  5.x boundary.
- `csv_process,callbacks` consumer contract is still the legacy 4-key
  array (`['data', 'line', 'last', 'time']`) because `bodyology_csv`
  registers callable maps using the legacy 4-arg signature. Unify both at
  4.x → 5.x.
- AMD modules in views/ — convert to ES modules at 5.x → 6.x.

## 3.0.0 — 2026-05-24

Migrated from Elgg 2.x to 3.x (`elgg-migrate-i4lb5`).

### Changed

- `manifest.xml`: bumped `elgg_release` requirement to `3.0`.
- `start.php`: converted top-level event registration into the Elgg 3.x
  closure pattern (`return function () { ... };`).
- `start.php`: removed redundant `elgg_register_ajax_view()` call — views
  are ajax-capable by default in 3.x.
- `start.php`: split assignment-in-`if` into a separate statement and
  filled in missing docblocks to satisfy the Elgg PHPCS standard.
- `composer.json`: rewritten as `hypejunction/csv_process` (fork
  ownership), bumped `php` to `>=7.2`, pinned `elgg/elgg ^3.0`,
  added `composer/installers ^1.0 || ^2.0` and the
  `config.allow-plugins.composer/installers: true` block required by
  composer 2.2+.

### Added

- `elgg-plugin.php`: minimal 3.x metadata stub (`plugin.name`).
- `ARCHITECTURE.md`: per-version architecture documentation.
- `CHANGELOG.md`: this file.

### Gates passed

- Elgg 3.x Docker stack activation: PASS
- Homepage renders (6993 bytes): PASS
- Login page renders (6993 bytes): PASS
- No PHP Fatal/Error in Apache log: PASS
- PHP syntax check: PASS
- PHP_CodeSniffer (Elgg standard): PASS
- PostMigrationVerifier (3.x boundary): PASS
- SecuritySweep: PASS

## 2.0.1 — 2015-12-29 (upstream)

Final upstream release from `arckinteractive/csv_process`.
