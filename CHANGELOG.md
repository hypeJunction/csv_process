# Changelog

## 7.0.0 — 2026-05-24

Migrated from Elgg 6.x to 7.x (`elgg-migrate-j74tg`). Final step in the
2.x → 7.x chain (umbrella `elgg-migrate-l0g46`).

### Changed

- `composer.json`: `elgg/elgg ~6.1.0` → `~7.0.0`, `php >=8.2` → `>=8.3`.
  Added `minimum-stability: dev`, `prefer-stable: true`, and the
  `asset-packagist.org` composer repository — required by the Elgg 7.x
  installer (Iron Law 11 / 7.x boundary).
- `docker/`: replaced with the elgg7 template (PHP 8.3 base image,
  Elgg 7.x installer, PHPUnit ^12.5).

### Carried forward unchanged

- All 7.x AST rules were no-ops for this plugin — no
  `elgg_reset_system_cache()` calls, no Redis/Memcached config, no
  Laminas\Mail, no Font Awesome icons, no notification handler
  classes, no `ajax_response` / `forward` event handlers, no removed
  CSS classes, no group `subpage` URLs, no `flush_cache` /
  `collection:user:user` references, no `recipients` form field,
  no external-pages usage, no password validators, no `\ElggObject`
  direct instantiations, no CKEditor customisation, no webservices /
  REST API hooks, no `elements/grid` CSS extensions.
- Event-handler shape in `elgg-plugin.php` is already the keyed
  `'FQCN::method' => spec` form (introduced earlier in the chain),
  so the 7.x rewrite of the legacy `[['handler' => [Class, 'method']]]`
  shape was not triggered.
- `elgg_register_external_file()` is not called by this plugin, so the
  "return-void in 7.x" change has no impact.
- No PHPUnit suite shipped (carried through every prior major); the
  `tests/playwright` and `tests/vitest` scaffolds remain.
- The plugin still owns no entity types/subtypes/relationships, so a
  `Seed` subclass is intentionally omitted (skill's "no entity surface"
  exemption).

### Notes

- All `elgg-migrate-verify` gates pass on the elgg7 docker stack:
  PHP syntax clean (excl. vendor/tests), homepage renders (14517 bytes),
  login renders (14609 bytes), no PHP Fatal/Error in Apache log,
  PHP_CodeSniffer (Elgg standard) clean, activation OK.
- Verified via `verify-fleet --version=elgg7 --only=csv_process`.

## 6.0.0 — 2026-05-24

Migrated from Elgg 5.x to 6.x (`elgg-migrate-jmw26`).

### Removed

- AMD module loader and `define(function (require) { ... })` wrapper —
  RequireJS/AMD removed in Elgg 6.0. Both plugin JS views (`forms/csv_process`
  and `csv_process/ajax/progress`) were converted to native ES modules.
- Inline `<script>require([...])</script>` blocks — replaced with
  `elgg_import_esm()` (form view) and `<script type="module">import ...</script>`
  (progress view, which needs per-run data inlined).

### Changed

- `composer.json`: `elgg/elgg ~5.1.0` → `~6.1.0`, `php >=8.1` → `>=8.2`.
  `ext-intl *` carried forward.
- `docker/`: replaced with the elgg6 template (PHP 8.2 base image, Elgg 6.x
  installer, PHPUnit ^10.5, MySQL 8.0 / MariaDB 10.6+).
- `views/default/forms/csv_process.js` → `views/default/forms/csv_process.mjs`.
  AMD `define(function (require) { var $ = require('jquery'); ... })` →
  top-level `import 'jquery'; import elgg from 'elgg'; import spinner from
  'elgg/spinner'; import i18n from 'elgg/i18n'; import 'jquery.form';`. Module
  body is side-effecting (binds a form submit handler at import time), no
  exports.
- `views/default/csv_process/ajax/progress.js` → `views/default/csv_process/ajax/progress.mjs`.
  AMD-wrapped `Progress` constructor → ESM with `import 'jquery'; import Ajax
  from 'elgg/Ajax'; ...; export default Progress;`.
- `views/default/forms/csv_process.php`: dropped
  `<script>require(['forms/csv_process']);</script>` in favour of
  `elgg_import_esm('forms/csv_process')`.
- `views/default/csv_process/ajax/progress.php`: dropped the AMD `require([...])`
  inline call and replaced with `<script type="module">import Progress from
  'csv_process/ajax/progress'; new Progress(<?= json_encode($time) ?>); ...</script>`.
  Mirrors Elgg core's `ckeditor/init.php` pattern for passing run-time data
  into a module.

### Notes

- No other 6.x removals triggered: the plugin doesn't touch annotations
  (`n_table`, enable/disable), entity icons (`icontime`, `x1/y1`),
  `elgg_strrchr` / `strripos`, `EntityIcon` interface, `elgg_set_view_location`,
  `elgg_get_entity_statistics()` / `elgg_get_simplecache_url()` 2-arg form, raw
  SQL with new MySQL 8 reserved words, `'hooks'` key, or grid CSS classes
  (`elgg-grid` / `elgg-col` / `elgg-row`).
- Verified on the elgg6 docker stack (project `csv-process-6x`): activation OK,
  homepage (13.8 KB) and login (13.9 KB) render, no PHP Fatal/Error in Apache
  log, PHP syntax clean, PHP_CodeSniffer (Elgg standard) clean. Post-migration
  verifier and security sweep both clean.

## 5.0.0 — 2026-05-24

Migrated from Elgg 4.x to 5.x (`elgg-migrate-xk2ch`).

### Removed

- `forward()` / `register_error()` — both functions removed in Elgg 5.0.
  Actions now `return elgg_redirect_response()` / `elgg_error_response()` /
  `elgg_ok_response()` per the 5.x action contract.
- `add_translation()` — removed in 5.0. `languages/en.php` rewritten to
  simply `return` the translations array.
- `'hooks'` key in `elgg-plugin.php` — merged into `'events'` (5.x unifies
  the plugin-hook and event APIs).
- `\Elgg\Hook` type hint — replaced with `\Elgg\Event` in
  `CsvProcess\DemoHandler::register()`.

### Changed

- `composer.json`: `elgg/elgg ^4.0` → `~5.1.0`, `php >=7.4` → `>=8.1`,
  added `ext-intl *` (required by Elgg 5.x — auto-applied by AST rule
  `update-manifest-version-5x`).
- `elgg-plugin.php`: top-level `'hooks'` key renamed to `'events'`. Handler
  string literal (`'CsvProcess\\DemoHandler::register'`) unchanged.
- `classes/CsvProcess/DemoHandler.php`: `use Elgg\Hook` → `use Elgg\Event`;
  `register(Hook $hook)` → `register(Event $event)`; `$hook->getValue()` →
  `$event->getValue()`. Docblock clarifies the per-row callback contract
  (positional `array $params`, not an Elgg event handler).
- `actions/csv_process.php`: `elgg_trigger_plugin_hook()` →
  `elgg_trigger_event_results()`; four `register_error() + forward(REFERRER)`
  blocks collapsed to `return elgg_error_response(...)`; final
  `forward('admin/...')` → `return elgg_redirect_response('admin/...')`;
  XHR branch `echo json_encode(...)` → `return elgg_ok_response([...])`.
- `actions/log_download.php`: `register_error() + forward(REFERRER)` →
  `return elgg_error_response(...)`.
- `views/default/forms/csv_process.php`: `elgg_trigger_plugin_hook()` →
  `elgg_trigger_event_results()`.
- `languages/en.php`: `add_translation('en', $arr)` → `return $arr` (5.x
  language file convention).
- `REFERER` constant references renamed to `REFERRER` (AST rule
  `removed-constants-5x`) — although the actions that used them were then
  refactored away from `forward()` entirely.
- `docker/`: replaced 4.x infra with elgg5 template (PHP 8.2, Elgg 5.x
  install script).

### Gates passed

- Elgg 5.x Docker stack activation: PASS
- Homepage renders (8888 bytes): PASS
- Login page renders (8975 bytes): PASS
- No PHP Fatal/Error in Apache log: PASS
- PHP syntax check: PASS
- PHP_CodeSniffer (Elgg standard): PASS
- PostMigrationVerifier (5.x boundary): PASS
- SecuritySweep: PASS

### Known carry-forward to 5.x → 6.x

- AMD modules in `views/` — convert to ES modules at 5.x → 6.x
  (`elgg-migrate-jmw26`).

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
