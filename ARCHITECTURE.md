# csv_process — Architecture (Elgg 7.x)

## Summary

**Name**: csv_process
**Version**: 7.0.0 — migrated to Elgg 7.x on 2026-05-24 (from 6.x). Final step in the 2.x → 7.x chain.
**Purpose**: Admin-only interface for uploading and processing CSV files with custom per-row callbacks contributed by other plugins.

The plugin exposes a single admin utility page that lets administrators select a
registered CSV processor, supply a CSV file (uploaded or referenced by path),
and stream live progress while each row is dispatched to the chosen callback.
Other plugins register processors via the `csv_process,callbacks` event,
returning a `callable-string => label-string` map.

## Directory Structure

```
csv_process/
├── elgg-plugin.php                 # 7.x declarative config (plugin/bootstrap/actions/events/menus)
├── composer.json                   # hypejunction/csv_process @ 7.0.0; psr-4 autoload of CsvProcess\
├── classes/
│   └── CsvProcess/
│       ├── Bootstrap.php           # extends \Elgg\DefaultPluginBootstrap; init() extends admin.css
│       ├── CsvProcessor.php        # shutdown-time CSV streamer
│       └── DemoHandler.php         # demo csv_process,callbacks registration + per-row handler (\Elgg\Event)
├── docker/                         # per-plugin elgg7 docker stack (Iron Law 12, PHP 8.3)
├── actions/
│   ├── csv_process.php             # admin: kicks off CSV processing (returns elgg_redirect_response)
│   └── log_download.php            # admin: stream-download the per-run log
├── languages/
│   └── en.php                      # returns array of translations
├── views/default/
│   ├── csv_process.css             # admin CSS extension
│   ├── admin/administer_utilities/
│   │   └── csv_process.php         # admin page (form + progress placeholder)
│   ├── forms/
│   │   ├── csv_process.php         # multipart form; calls elgg_import_esm('forms/csv_process')
│   │   └── csv_process.mjs         # ES module: ajax form submit + spinner (uses elgg/i18n.echo)
│   └── csv_process/ajax/
│       ├── progress.php            # ajax/admin view: tail of the run log; inline <script type='module'>
│       └── progress.mjs            # ES module: polls progress every 2s via elgg/Ajax (default export)
├── tests/                          # Playwright + Vitest scaffolds (no PHPUnit suite)
├── README.md
├── ARCHITECTURE.md                 # this file
└── CHANGELOG.md
```

## Bootstrap & Iron Laws

- **Iron Law 5 (no closures in elgg-plugin.php)**: satisfied — `elgg-plugin.php`
  is purely declarative. The single non-declarative bit (admin CSS extension)
  lives in `CsvProcess\Bootstrap::init()`.
- **Iron Law 6 (dir = composer name)**: satisfied — directory `csv_process`
  matches `hypejunction/csv_process` (lowercase).
- **Iron Law 7 (7.x APIs only)**: satisfied — handlers use `\Elgg\Event`;
  trigger calls use `elgg_trigger_event_results()`; actions use
  `elgg_redirect_response()` / `elgg_error_response()` / `elgg_ok_response()`;
  JS is native ESM with `elgg_import_esm()` (no `elgg_define_js` /
  `elgg_require_js` / AMD `define()/require()`); event-handler shape in
  `elgg-plugin.php` is the keyed `'FQCN::method' => spec` form (the 7.x
  preferred shape, not the legacy `[['handler' => ...]]` array). No
  references to removed-in-7.x APIs (`elgg_reset_system_cache`,
  Laminas\Mail, Redis/Memcached config, `\ElggObject` direct
  instantiation, CSS Crush, `ajax_response` event, `forward` event,
  `elgg-button-special`, `recipients` form field, etc.).
- **Cross-plugin learning**: class refs in `elgg-plugin.php` are written as
  string literals (`'CsvProcess\\Bootstrap'`, `'CsvProcess\\DemoHandler::register'`)
  rather than `::class`, since the plugin autoloader is not necessarily wired
  at `generateEntities()` / boot-handler time.

## Entities

This plugin owns no entity types or subtypes. All in-flight processing state
lives in `elgg_set_config()` keys (`csv_process_time`, `csv_process_location`,
`csv_process_callback`, `csv_process_delimiter`, `csv_process_enclosure`,
`csv_process_escape`) and a per-run log file under
`{dataroot}/csv_process_log/{timestamp}log.txt`.

## Routes

None registered. The admin utility is reached via the admin menu item
(declarative `menus.page.csv_process` in `elgg-plugin.php`) which points at
`admin/administer_utilities/csv_process`.

## Actions (admin-only)

| Action                       | File                          | Purpose                                                   |
|------------------------------|-------------------------------|-----------------------------------------------------------|
| `csv_process`                | `actions/csv_process.php`     | Validate input, persist config, register the shutdown CSV processor, redirect with progress UI. Returns `elgg_redirect_response()` / `elgg_error_response()` / `elgg_ok_response()`. |
| `csv_process/log_download`   | `actions/log_download.php`    | Stream the on-disk log file for a given run timestamp.    |

Both actions are declared with `access => admin` in `elgg-plugin.php` (`actions`
key).

## Events

| Event           | Type        | Handler                                  | Purpose                                                                                                  |
|-----------------|-------------|------------------------------------------|----------------------------------------------------------------------------------------------------------|
| `csv_process`   | `callbacks` | `CsvProcess\DemoHandler::register`       | Plugin's own demo processor. Other plugins (e.g. `bodyology_csv`) register additional processors here.   |
| `shutdown`      | `system`    | `CsvProcess\CsvProcessor::processCsv`    | Registered dynamically inside `actions/csv_process.php` so processing happens after the HTTP response is flushed. |

The registrant signature uses `\Elgg\Event` (single-arg). The **downstream
contract** (what `$csv_callback($params)` receives in `CsvProcessor::processCsv`)
is intentionally the 4-key positional array `['data', 'line', 'last', 'time']`
— it is NOT an Elgg event signature. Per-row callbacks are invoked directly by
`csv_process` (not as Elgg event handlers), so they keep their plain
`array $params` form independent of the hook/event API.

## Menus

Declared in `elgg-plugin.php`:

| Menu   | Item          | Parent                | Target URL                                  |
|--------|---------------|-----------------------|---------------------------------------------|
| `page` | `csv_process` | `administer_utilities` | `admin/administer_utilities/csv_process`    |

## Views

| View                                          | Type | Purpose                                                              |
|-----------------------------------------------|------|----------------------------------------------------------------------|
| `admin/administer_utilities/csv_process`      | PHP  | Admin page chrome: progress placeholder + form                       |
| `forms/csv_process`                           | PHP  | Multipart form; imports `forms/csv_process` as ESM at the end        |
| `forms/csv_process` (`.mjs`)                  | ESM  | jQuery init: hijacks form submit, posts JSON, swaps in progress HTML |
| `csv_process/ajax/progress`                   | PHP  | Admin/AJAX log tail; renders `<script type='module'>` with run-time data |
| `csv_process/ajax/progress` (`.mjs`)          | ESM  | Default-exports `Progress` constructor; polls log via `elgg/Ajax`    |
| `csv_process.css`                             | CSS  | Admin-only CSS, extended onto `admin.css` from `Bootstrap::init()`   |

### JavaScript loading model (6.x)

- `views/default/forms/csv_process.mjs` is loaded by the PHP form view via
  `elgg_import_esm('forms/csv_process')` at the bottom of the form. The module
  side-effects (binds a `.elgg-form-csv-process` submit handler on import) —
  no exports.
- `views/default/csv_process/ajax/progress.mjs` is `default-export`ed as a
  `Progress` constructor. The PHP view embeds an inline
  `<script type="module">import Progress from 'csv_process/ajax/progress'; ...</script>`
  block so it can pass per-run `time` / initial `line` values from PHP into
  `new Progress(...)`. The Elgg core CKEditor `init.php` view uses the same
  inline-module pattern.

## Languages

- `languages/en.php` — returns an array of translations.

## Dependencies

`composer.json` requires:

- `php >=8.3`
- `elgg/elgg ~7.0.0`
- `ext-intl *`
- `composer/installers ^2.0`

`minimum-stability: dev` + `prefer-stable: true` + `asset-packagist.org`
composer repository are declared, matching the Elgg 7.x site-install
contract.

## Seeding

This plugin has **no persisted entities of its own**, so a `Seed` subclass is
intentionally not provided. The in-flight processing state lives in Elgg config
keys and a per-run log file under `dataroot/csv_process_log/`, neither of which
benefits from fixture seeding. Acceptable per the skill's "no entity surface"
exemption.

## Migration Notes — 6.x → 7.x

Changes applied in this step:

- **`composer.json`**: bumped `elgg/elgg` from `~6.1.0` → `~7.0.0`,
  `php` from `>=8.2` → `>=8.3`. Added `minimum-stability: dev`,
  `prefer-stable: true`, and the `asset-packagist.org` composer
  repository — the Elgg 7.x install script depends on these.
- **Docker infra**: replaced `docker/` with the elgg7 template
  (PHP 8.3 base image, Elgg 7.x installer, PHPUnit ^12.5).
- **All 7.x AST rules were no-ops.** The plugin does not call
  `elgg_reset_system_cache()`, the composer stability block was
  introduced in the same change, and all functions/methods already
  carry docblocks (added during the 6.x doc pass).
- **All LLM-guided 7.x boundary checks were no-ops.** Scanned and
  confirmed absent: direct `\ElggObject` instantiation, CSS Crush
  syntax (`$(varname)`), `memcache` / `redis` config keys, Laminas
  Mail / `\Elgg\Email\Address` / `zend:message` event, Font Awesome
  icon usage, notification handler classes (`CreateCommentEventHandler`
  etc.), renamed forms (`blog/save`, `bookmarks/save`, `file/upload`),
  `ajax_response` / `forward` event handlers, removed button CSS
  classes (`elgg-button-special`, `elgg-button-action-done`),
  group `subpage` URLs, `flush_cache` action, `collection:user:user`
  / `search:user:user` routes, messages `recipients` parameter,
  `expages` / `external_page` references, `min_password_length`
  validators, `elgg_create_river_item`, `elgg_list_entities`
  limit handling, CKEditor customisation, likes visibility,
  webservices / REST API hooks, `elements/grid` view extensions.
- **Event-handler shape**: `elgg-plugin.php` already uses the keyed
  `'FQCN::method' => spec` form (introduced earlier in the chain),
  so the 7.x rewrite of the legacy `[['handler' => [Class, 'method']]]`
  shape was not triggered.
- **`elgg_register_external_file()`**: not called by this plugin, so
  the "return-void in 7.x" change has no impact.
- **PHPUnit**: still no plugin-owned suite. The `tests/playwright` and
  `tests/vitest` placeholder scaffolds remain.

### Gates (all green on the elgg7 docker stack, project `em-csv_process-afb15a4`)

- PHP syntax (excl. vendor / tests): PASS
- Homepage render (14517 bytes): PASS
- Login render (14609 bytes): PASS
- No PHP Fatal / Error in Apache log: PASS
- PHP_CodeSniffer (Elgg standard): PASS
- Activation: PASS (via `verify-fleet --version=elgg7 --only=csv_process`)
- Post-migration verifier (Iron Law 7): no version boundary violations
- Security sweep: clean

### Known issues / carry-forward

- No PHPUnit suite shipped (carried through every prior major). A
  `tests/` placeholder exists; a `Seed`-less plugin without entity
  surface could still benefit from action-level tests — open for a
  future iteration.
- The inline `<script type="module">` block in
  `csv_process/ajax/progress.php` embeds a `json_encode($time)` literal
  because the per-run timestamp has to travel from PHP into the
  constructor invocation. This is the same pattern used by Elgg core's
  `ckeditor/init.php` for similar runtime parameters (unchanged from 6.x).

## Migration Notes — 5.x → 6.x

Changes applied in this step:

- **AMD → ES modules (the headline 6.x change).** `elgg_define_js()` /
  `elgg_require_js()` / `elgg_unrequire_js()` and AMD `define()/require()` are
  removed in 6.x. Both JS views were converted:
  - `views/default/forms/csv_process.js` → `.mjs` — replaced `define(function (require) { ... })`
    with top-level `import` statements (`import 'jquery'; import elgg from 'elgg';
    import spinner from 'elgg/spinner'; import i18n from 'elgg/i18n'; import 'jquery.form';`).
    The module body is side-effecting (binds a submit handler at import time),
    no exports.
  - `views/default/csv_process/ajax/progress.js` → `.mjs` — converted constructor
    + prototype methods to `import 'jquery'; import Ajax from 'elgg/Ajax'; ...; export default Progress;`.
  - `views/default/forms/csv_process.php` — removed
    `<script>require(['forms/csv_process']);</script>` and replaced with
    `elgg_import_esm('forms/csv_process')`.
  - `views/default/csv_process/ajax/progress.php` — replaced
    `<script>require(['csv_process/ajax/progress'], function (Progress) {...})</script>`
    with `<script type="module">import Progress from 'csv_process/ajax/progress'; new Progress(<?= json_encode($time) ?>); ...</script>`
    so the run-specific `time` and initial `line` values can still be embedded
    inline (Elgg core's `ckeditor/init.php` uses the same pattern).
- **`composer.json`**: bumped `elgg/elgg` from `~5.1.0` → `~6.1.0`, `php` from
  `>=8.1` → `>=8.2`. `ext-intl *` carried forward.
- **Docker infra**: replaced `docker/` with the elgg6 template (PHP 8.2, Elgg
  6.x install script, PHPUnit ^10.5, MySQL 8.0 / MariaDB 10.6+).
- **No other 6.x removals triggered.** The plugin doesn't touch annotations
  (`n_table`, `enable/disable`), entity icons (`icontime`, `x1`/`y1`),
  `elgg_strrchr`/`strripos`, `EntityIcon` interface, `elgg_set_view_location`,
  `elgg_get_entity_statistics()` / `elgg_get_simplecache_url()` 2-arg form, raw
  SQL with new MySQL 8 reserved words, `'hooks'` key, or grid CSS classes
  (`elgg-grid` / `elgg-col` / `elgg-row`). All boundary searches returned zero.
- **PHPUnit**: still no plugin-owned suite. The `tests/playwright` /
  `tests/vitest` placeholder scaffolds remain.

### Gates (all green on the elgg6 docker stack, project `csv-process-6x`)

- PHP syntax (excl. vendor / tests): PASS
- Homepage render (13846 bytes): PASS
- Login render (13941 bytes): PASS
- No PHP Fatal / Error in Apache log: PASS
- PHP_CodeSniffer (Elgg standard): PASS
- Activation: PASS (`elgg_get_plugin_from_id('csv_process')->activate()`)
- Post-migration verifier (Iron Law 7): no version boundary violations
- Security sweep: clean
- Composer audit: no lockfile, skipped (single-direct-dep plugin)

### Known issues / carry-forward

- No PHPUnit suite shipped (carried forward through every prior major). A
  `tests/` placeholder exists; a `Seed`-less plugin without entity surface
  could still benefit from action-level tests — open for a future iteration.
- The inline `<script type="module">` block in `csv_process/ajax/progress.php`
  embeds a `json_encode($time)` literal because the per-run timestamp has to
  travel from PHP into the constructor invocation. This is the same pattern
  used by Elgg core's `ckeditor/init.php` for similar runtime parameters.

## Source

- Upstream (read-only): `arckinteractive/csv_process` — last commit 2015-12-29, abandoned.
- hypeJunction fork: `https://github.com/hypeJunction/csv_process` — base for the migration chain.
- Bodyology consumer: `bodyology_csv` registers `csv_process,callbacks` as an
  `\Elgg\Event` handler; per-row callbacks use the positional `array $params`
  shape that csv_process invokes directly.
