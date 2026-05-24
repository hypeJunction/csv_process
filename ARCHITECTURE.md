# csv_process — Architecture (Elgg 5.x)

## Summary

**Name**: csv_process
**Version**: 5.0.0 — migrated to Elgg 5.x on 2026-05-24 (from 4.x)
**Purpose**: Admin-only interface for uploading and processing CSV files with custom per-row callbacks contributed by other plugins.

The plugin exposes a single admin utility page that lets administrators select a
registered CSV processor, supply a CSV file (uploaded or referenced by path),
and stream live progress while each row is dispatched to the chosen callback.
Other plugins register processors via the `csv_process,callbacks` event,
returning a `callable-string => label-string` map.

## Directory Structure

```
csv_process/
├── elgg-plugin.php                 # 5.x declarative config (plugin/bootstrap/actions/events/menus)
├── composer.json                   # hypejunction/csv_process @ 5.0.0; psr-4 autoload of CsvProcess\
├── classes/
│   └── CsvProcess/
│       ├── Bootstrap.php           # extends \Elgg\DefaultPluginBootstrap; init() extends admin.css
│       ├── CsvProcessor.php        # shutdown-time CSV streamer
│       └── DemoHandler.php         # demo csv_process,callbacks registration + per-row handler (uses \Elgg\Event)
├── docker/                         # per-plugin elgg5 docker stack (Iron Law 12)
├── actions/
│   ├── csv_process.php             # admin: kicks off CSV processing (returns elgg_redirect_response)
│   └── log_download.php            # admin: stream-download the per-run log
├── languages/
│   └── en.php                      # returns array of translations (5.x style — add_translation() removed)
├── views/default/
│   ├── csv_process.css             # admin CSS extension
│   ├── admin/administer_utilities/
│   │   └── csv_process.php         # admin page (form + progress placeholder)
│   ├── forms/
│   │   ├── csv_process.php         # multipart form (callback select + file + delimiter/enclosure/escape)
│   │   └── csv_process.js          # AMD: ajax form submit + spinner (uses elgg/i18n.echo)
│   └── csv_process/ajax/
│       ├── progress.php            # ajax/admin view: tail of the run log
│       └── progress.js             # AMD: polls progress every 2s via elgg/Ajax
├── tests/                          # placeholder (carried forward; PHPUnit suite to be added)
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
- **Iron Law 7 (5.x APIs only)**: satisfied — handlers use `\Elgg\Event`, not
  `\Elgg\Hook`; trigger calls use `elgg_trigger_event_results()`, not
  `elgg_trigger_plugin_hook()`; actions use `elgg_redirect_response()` /
  `elgg_error_response()`, not the removed `forward()` / `register_error()`;
  language files return arrays, not `add_translation()`.
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
| `csv_process`                | `actions/csv_process.php`     | Validate input, persist config, register the shutdown CSV processor, redirect with progress UI. Returns `elgg_redirect_response()` or `elgg_error_response()`. |
| `csv_process/log_download`   | `actions/log_download.php`    | Stream the on-disk log file for a given run timestamp.    |

Both actions are declared with `access => admin` in `elgg-plugin.php` (`actions`
key).

## Events

| Event           | Type        | Handler                                  | Purpose                                                                                                  |
|-----------------|-------------|------------------------------------------|----------------------------------------------------------------------------------------------------------|
| `csv_process`   | `callbacks` | `CsvProcess\DemoHandler::register`       | Plugin's own demo processor. Other plugins (e.g. `bodyology_csv`) register additional processors here.   |
| `shutdown`      | `system`    | `CsvProcess\CsvProcessor::processCsv`    | Registered dynamically inside `actions/csv_process.php` so processing happens after the HTTP response is flushed. |

The registrant signature uses `\Elgg\Event` (5.x single-arg). The **downstream
contract** (what `$csv_callback($params)` receives in `CsvProcessor::processCsv`)
is intentionally the 4-key positional array `['data', 'line', 'last', 'time']`
— it is NOT an Elgg event signature. Per-row callbacks are invoked directly by
`csv_process` (not as Elgg event handlers), so they keep their plain `array $params`
form independent of the hook/event API. The consumer plugin `bodyology_csv`
already registers its event handler as a 1-arg `\Elgg\Event` handler (matches
5.x), and its per-row callbacks (`bodyology_csv_import_users` etc.) keep their
positional `array $params` signature — both forms are mutually compatible.

No `init,system` registration — the only init-time work (CSS extension and
admin menu) lives in `Bootstrap::init()` and the declarative `menus` key.

## Menus

Declared in `elgg-plugin.php`:

| Menu   | Item          | Parent                | Target URL                                  |
|--------|---------------|-----------------------|---------------------------------------------|
| `page` | `csv_process` | `administer_utilities` | `admin/administer_utilities/csv_process`    |

## Views

Unchanged in structure from 4.x. JS is still AMD (`elgg/i18n`, `elgg/Ajax`,
`elgg/spinner`). All AMD modules will migrate to ES modules at the 5.x → 6.x
boundary (`elgg-migrate-jmw26`).

## Languages

- `languages/en.php` — returns an array of translations (5.x style;
  `add_translation()` was removed in 5.0).

## Dependencies

`composer.json` requires:

- `php >=8.1`
- `elgg/elgg ~5.1.0`
- `ext-intl *`
- `composer/installers ^2.0`

## Seeding

This plugin has **no persisted entities of its own**, so a `Seed` subclass is
intentionally not provided. The in-flight processing state lives in Elgg config
keys and a per-run log file under `dataroot/csv_process_log/`, neither of which
benefits from fixture seeding. Acceptable per the skill's "no entity surface"
exemption.

## Migration Notes — 4.x → 5.x

Changes applied in this step:

- **Hook → event unification (Iron Law 7 boundary work).** This was deferred
  from the 3→4 step because the consumer plugin `bodyology_csv` was holding
  the legacy 4-arg signature. At the 5.x boundary the plugin-hook API is
  unified into events:
  - `elgg-plugin.php`: top-level `'hooks'` key renamed to `'events'`.
  - `classes/CsvProcess/DemoHandler.php`: `use Elgg\Hook` →
    `use Elgg\Event`; `public static function register(Hook $hook)` →
    `public static function register(Event $event)`; `$hook->getValue()` →
    `$event->getValue()`.
  - `actions/csv_process.php` and `views/default/forms/csv_process.php`:
    `elgg_trigger_plugin_hook('csv_process', 'callbacks', ...)` →
    `elgg_trigger_event_results('csv_process', 'callbacks', ...)`.
  - The per-row callback contract — what `$csv_callback($params)` receives —
    is unchanged; it's a plain positional `array $params` invocation, not an
    Elgg event handler. The consumer plugin `bodyology_csv` registered its
    `csv_process,callbacks` handler as `Elgg\Event` already (as of its 4.x
    migration), so this step requires no consumer-side change.
- **`forward()` / `register_error()` removed in 5.x.** Both actions
  (`actions/csv_process.php`, `actions/log_download.php`) now return the
  appropriate 5.x response object: `elgg_redirect_response($url)` for success
  redirects, `elgg_error_response($msg)` for validation errors (which also
  registers the error message), and `elgg_ok_response([...])` for the XHR
  branch (replaces the manual `echo json_encode(...)`).
- **`REFERER` constant removed in 5.0.** Renamed to `REFERRER` (auto-rule
  `removed-constants-5x`). The remaining `forward(REFERRER)` calls were then
  replaced with `elgg_error_response()` per above.
- **`add_translation()` removed in 5.0.** `languages/en.php` rewritten to
  simply `return` the translations array.
- **`composer.json`**: bumped `elgg/elgg` from `^4.0` → `~5.1.0`, `php` from
  `>=7.4` → `>=8.1`, added `ext-intl *` (required by Elgg 5.x).
- **Docker infra**: replaced `docker/` with the elgg5 template (PHP 8.2,
  Elgg 5.x install script).

### Known issues / carry-forward

- AMD modules in `views/` — convert to ES modules at 5.x → 6.x
  (`elgg-migrate-jmw26`). `elgg_define_js()` / `elgg_require_js()` and AMD
  loaders are scheduled for removal in 6.x.
- No PHPUnit suite shipped (carried forward from 3.x/4.x). A `tests/`
  placeholder exists; a `Seed`-less plugin without entity surface still
  benefits from action-level tests — open for a future iteration.

## Source

- Upstream (read-only): `arckinteractive/csv_process` — last commit 2015-12-29, abandoned.
- hypeJunction fork: `https://github.com/hypeJunction/csv_process` — base for the migration chain.
- Bodyology consumer: `bodyology_csv` registers `csv_process,callbacks` as an
  `\Elgg\Event` handler; per-row callbacks use the positional `array $params`
  shape that csv_process invokes directly.
