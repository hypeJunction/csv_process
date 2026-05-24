# csv_process — Architecture (Elgg 4.x)

## Summary

**Name**: csv_process
**Version**: 4.0.0 — migrated to Elgg 4.x on 2026-05-24 (from 3.x)
**Purpose**: Admin-only interface for uploading and processing CSV files with custom per-row callbacks contributed by other plugins.

The plugin exposes a single admin utility page that lets administrators select a
registered CSV processor, supply a CSV file (uploaded or referenced by path),
and stream live progress while each row is dispatched to the chosen callback.
Other plugins register processors via the `csv_process,callbacks` plugin hook,
returning a `callable-string => label-string` map.

## Directory Structure

```
csv_process/
├── elgg-plugin.php                 # 4.x declarative config (plugin/bootstrap/actions/hooks/menus)
├── composer.json                   # hypejunction/csv_process @ 4.0.0; psr-4 autoload of CsvProcess\
├── classes/
│   └── CsvProcess/
│       ├── Bootstrap.php           # extends \Elgg\DefaultPluginBootstrap; init() extends admin.css
│       ├── CsvProcessor.php        # shutdown-time CSV streamer (was csv_process\process_csv)
│       └── DemoHandler.php         # demo csv_process,callbacks registration + per-row handler
├── docker/                         # per-plugin elgg4 docker stack (Iron Law 12)
├── actions/
│   ├── csv_process.php             # admin: kicks off CSV processing (registers shutdown handler)
│   └── log_download.php            # admin: stream-download the per-run log
├── languages/
│   └── en.php                      # add_translation() based string registration
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
- **Iron Law 7 (4.x APIs only)**: satisfied — handlers use `\Elgg\Hook`, not
  `\Elgg\Event`; `elgg_register_plugin_hook_handler` callsite in the action
  validates user input against the same hook map.
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
| `csv_process`                | `actions/csv_process.php`     | Validate input, persist config, register the shutdown CSV processor, redirect with progress UI. |
| `csv_process/log_download`   | `actions/log_download.php`    | Stream the on-disk log file for a given run timestamp.    |

Both actions are declared with `access => admin` in `elgg-plugin.php` (`actions`
key), replacing the 3.x `elgg_register_action()` call in `start.php`.

## Hooks

| Hook            | Type        | Handler                                  | Purpose                                                                                                  |
|-----------------|-------------|------------------------------------------|----------------------------------------------------------------------------------------------------------|
| `csv_process`   | `callbacks` | `CsvProcess\DemoHandler::register`       | Plugin's own demo processor. Other plugins (e.g. `bodyology_csv`) register additional processors here.   |

The registrant signature uses `\Elgg\Hook` (4.x single-arg). The
**downstream contract** (what `$csv_callback($params)` receives in
`CsvProcessor::processCsv`) is preserved as the legacy 4-key array
`['data', 'line', 'last', 'time']` because consumer plugins such as
`bodyology_csv` register callable maps using the legacy 4-arg signature.
Unifying that contract to `\Elgg\Hook` is deferred to the 4.x → 5.x boundary
(`elgg-migrate-xk2ch`).

## Events

| Event      | Type     | Handler                                          | Purpose                                                              |
|------------|----------|--------------------------------------------------|----------------------------------------------------------------------|
| `shutdown` | `system` | `CsvProcess\CsvProcessor::processCsv`            | Registered dynamically inside `actions/csv_process.php` so processing happens after the HTTP response is flushed. |

No `init,system` registration — the only init-time work (CSS extension and
admin menu) lives in `Bootstrap::init()` and the declarative `menus` key.

## Menus

Declared in `elgg-plugin.php` (replaces the 3.x `elgg_register_admin_menu_item`
call which was removed without deprecation in 4.x):

| Menu   | Item          | Parent                | Target URL                                  |
|--------|---------------|-----------------------|---------------------------------------------|
| `page` | `csv_process` | `administer_utilities` | `admin/administer_utilities/csv_process`    |

## Views

Unchanged in structure from 3.x. JS modernised:

- `views/default/forms/csv_process.js` — AMD; uses `elgg/i18n` (was
  `elgg.echo`) and `elgg/spinner`. Still uses `jquery.form` (`ajaxSubmit`).
- `views/default/csv_process/ajax/progress.js` — AMD; rewritten to use
  `elgg/Ajax` (was `elgg.get`). `Function.prototype.bind` calls preserved
  (the AST rule's `.bind()` → `.on()` rewrite was a false positive on
  non-jQuery bind and was reverted).

All AMD modules will migrate to ES modules at the 5.x → 6.x boundary
(`elgg-migrate-jmw26`).

## Languages

- `languages/en.php` — `add_translation('en', [...])` covering form labels,
  error/help strings, and the demo handler label.

## Dependencies

`composer.json` requires:

- `php >=7.4`
- `elgg/elgg ^4.0`
- `composer/installers ^2.0`

**`vroom` plugin dependency dropped at this version step.** The 2.x/3.x
manifest required `vroom` (jumbojett/vroom) so that the HTTP response would
flush before the `shutdown,system` handler ran the long CSV processing.
Three reasons to drop it:

1. `vroom` is not published on Packagist — declaring it as a composer
   require fails to resolve unless the consumer site vendors it locally.
2. The 4.x action issues `forward('admin/.../csv_process?time=...')`, so the
   browser disconnects from the response stream before the shutdown handler
   runs; the user-visible delay is already eliminated by the redirect.
3. Operators who still want the original `vroom` behaviour can install it
   alongside `csv_process` — there is no hard runtime coupling, only a UX
   nicety.

## Seeding

This plugin has **no persisted entities of its own**, so a `Seed` subclass is
intentionally not provided. The in-flight processing state lives in Elgg config
keys and a per-run log file under `dataroot/csv_process_log/`, neither of which
benefits from fixture seeding. Acceptable per the skill's "no entity surface"
exemption.

## Migration Notes — 3.x → 4.x

Changes applied in this step:

- **`start.php` removed.** The 3.x closure pattern (returning `function () { … }`)
  was replaced by a `\CsvProcess\Bootstrap` class extending
  `\Elgg\DefaultPluginBootstrap`. Action/hook/menu registrations moved to the
  declarative `elgg-plugin.php` config (`actions`, `hooks`, `menus` keys).
- **`manifest.xml` removed.** 4.x reads all plugin metadata from
  `composer.json`. The `vroom` plugin dependency was dropped (see Dependencies
  above for rationale).
- **`composer.json`**: bumped `elgg/elgg` from `^3.0` → `^4.0`, `php` from
  `>=7.2` → `>=7.4`, `composer/installers` to `^2.0` only. Added `psr-4`
  autoload of `CsvProcess\` to `classes/CsvProcess/`. Added `extra.elgg-plugin.id`.
- **Class extraction**: the procedural functions in 3.x `start.php` became:
  - `csv_process\process_csv` → `CsvProcess\CsvProcessor::processCsv`
  - `csv_process\register_demo_handler` → `CsvProcess\DemoHandler::register`
    (now takes `\Elgg\Hook` and uses `$hook->getValue()` / array merge instead
    of the 3.x 4-arg signature)
  - `csv_process\demo_handler` → `CsvProcess\DemoHandler::handle`
  - `csv_process\log` → `CsvProcess\CsvProcessor::writeLog` (residual
    rename from the 3.x notes — `log` was a namespaced helper that risked
    future PHP/Elgg reserved-word friction)
- **`elgg_register_admin_menu_item()` removed in 4.x.** Replaced by the
  declarative `menus.page.csv_process` entry in `elgg-plugin.php`.
- **JS modernised:**
  - `forms/csv_process.js`: `elgg.echo()` → `i18n.echo()` (AMD rule
    `013b-amd-removed-apis`).
  - `csv_process/ajax/progress.js`: `elgg.get()` → `Ajax.view()` (AMD rule
    `009-js-ajax-helpers`, applied manually).
- **`callback` validation tightened** in `actions/csv_process.php`: input is
  cast to string before the `in_array` / `is_callable` checks so a malformed
  posted value can't bypass the dropdown allowlist.

### Known issues / carry-forward

- `forward()` is used in actions; the migration rule flags this as
  "removed in 4.0" but `forward()` actually lives in `engine/lib/deprecated-4.0.php`
  and still works. Refactor to `elgg_redirect_response()` /
  `elgg_error_response()` at the 4.x → 5.x boundary (`xk2ch`).
- `bodyology_csv` consumer plugin still registers its hook with the legacy
  4-arg signature on purpose; csv_process invokes the per-row callback
  directly so the consumer contract is the array `$params`, not `\Elgg\Hook`.
  Unify both at 4.x → 5.x.
- AMD modules will migrate to ES modules at 5.x → 6.x.

## Source

- Upstream (read-only): `arckinteractive/csv_process` — last commit 2015-12-29, abandoned.
- hypeJunction fork: `https://github.com/hypeJunction/csv_process` — base for the migration chain.
- Bodyology consumer: `bodyology_csv` registers `csv_process,callbacks` and uses the legacy 4-arg hook signature on purpose. Compatibility with that consumer is a hard constraint for every step on the way to 7.x.
