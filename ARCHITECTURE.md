# csv_process — Architecture (Elgg 3.x)

## Summary

**Name**: csv_process
**Version**: 3.0.0 — migrated to Elgg 3.x on 2026-05-24 (from 2.x)
**Purpose**: Provides an admin-only interface for uploading and processing CSV files with custom per-row callbacks contributed by other plugins.

The plugin exposes a single admin utility page that lets administrators select a
registered CSV processor, supply a CSV file (uploaded or referenced by path),
and stream live progress while each row is dispatched to the chosen callback.
Other plugins register processors via the `csv_process`/`callbacks` plugin
hook, returning a `handler => label` map.

## Directory Structure

```
csv_process/
├── start.php                       # 3.x closure: registers init handler + helper functions
├── elgg-plugin.php                 # Minimal 3.x metadata (plugin display name)
├── manifest.xml                    # 3.x requires-elgg-3.0 + vroom dep
├── composer.json                   # hypejunction/csv_process @ 3.0.0
├── README.md
├── ARCHITECTURE.md                 # this file
├── CHANGELOG.md
├── actions/
│   ├── csv_process.php             # admin: kicks off CSV processing (registers shutdown handler)
│   └── log_download.php            # admin: stream-download the per-run log
├── languages/
│   └── en.php                      # add_translation() based string registration
└── views/default/
    ├── csv_process.css             # admin CSS extension
    ├── admin/administer_utilities/
    │   └── csv_process.php         # admin page (form + progress placeholder)
    ├── forms/
    │   ├── csv_process.php         # multipart form (callback select + file + delimiter/enclosure/escape)
    │   └── csv_process.js          # AMD module: ajax form submit + spinner
    └── csv_process/ajax/
        ├── progress.php            # ajax/admin view: tail of the run log
        └── progress.js             # AMD module: polls progress every 2s
```

No `classes/` directory — the plugin remains in the legacy 1.8-era procedural
layout (with a `namespace csv_process;` declaration in `start.php`). Migrating
to namespaced classes is deferred to the 3.x → 4.x step (`elgg-migrate-iv5j8`).

## Entities

This plugin owns no entity types or subtypes. All state for an in-flight run
lives in `elgg_set_config()` keys (`csv_process_time`, `csv_process_location`,
`csv_process_callback`, `csv_process_delimiter`, `csv_process_enclosure`,
`csv_process_escape`) and a per-run log file under
`{dataroot}/csv_process_log/{timestamp}log.txt`.

## Routes

None registered. The admin utility is reached via the admin menu item
`administer_utilities/csv_process`.

## Actions (admin-only)

| Action                       | File                          | Purpose                                                   |
|------------------------------|-------------------------------|-----------------------------------------------------------|
| `csv_process`                | `actions/csv_process.php`     | Validate input, persist config, register the shutdown CSV processor, redirect with progress UI. |
| `csv_process/log_download`   | `actions/log_download.php`    | Stream the on-disk log file for a given run timestamp.    |

Both actions are registered with the `admin` access level via
`elgg_register_action()` in `csv_process\init()`.

## Hooks

| Hook            | Type        | Handler                                  | Purpose                                                                                                  |
|-----------------|-------------|------------------------------------------|----------------------------------------------------------------------------------------------------------|
| `csv_process`   | `callbacks` | `csv_process\register_demo_handler`      | Plugin's own demo processor. Other plugins (e.g. `bodyology_csv`) register additional processors here.   |

The 3.x signature `function ($hook, $type, $return, $params)` is preserved.
The hook contract is `handler-string => label-string`, and consumers select a
handler in the admin form. Modernising to `\Elgg\Hook` is deferred to the
3.x → 4.x step.

## Events

| Event      | Type     | Handler                       | Purpose                                                              |
|------------|----------|-------------------------------|----------------------------------------------------------------------|
| `init`     | `system` | `csv_process\init`            | Register the admin menu item, action, ajax view, and demo callback.  |
| `shutdown` | `system` | `csv_process\process_csv`     | Registered dynamically inside `actions/csv_process.php` so processing happens after the HTTP response is flushed. |

## Views

- `views/default/admin/administer_utilities/csv_process.php` — admin page; renders the form and a progress placeholder. Picks up `time` from query string to re-render an in-progress run.
- `views/default/forms/csv_process.php` — form fields: callback dropdown (populated via the `csv_process/callbacks` hook), file upload, server path, delimiter, enclosure, escape. Submits via the AMD module.
- `views/default/forms/csv_process.js` — AMD module that intercepts the submit, confirms with the user, and posts via `$.ajaxSubmit`. Re-renders the progress placeholder from the JSON response.
- `views/default/csv_process/ajax/progress.php` — reads the last line of the per-run log file. Returns just the line for ajax polls, or a wrapped view for the initial render.
- `views/default/csv_process/ajax/progress.js` — AMD module that polls the ajax view every 2 seconds and appends new lines.

All AMD modules predate this migration. The 5.x → 6.x step (`elgg-migrate-jmw26`)
is the right point to convert these to ES modules.

## Languages

- `languages/en.php` — `add_translation('en', [...])` covering form labels, error/help strings, and the demo handler label.

## Dependencies

Declared in `manifest.xml`:

| Plugin   | Notes                                                                                                  |
|----------|--------------------------------------------------------------------------------------------------------|
| `vroom`  | Carried forward from the upstream 2.x manifest. Re-evaluate during 3.x → 4.x (`elgg-migrate-iv5j8`): modern shutdown handling may obsolete the dependency. |

`composer.json` declares:

- `php >=7.2`
- `elgg/elgg ^3.0`
- `composer/installers ^1.0 || ^2.0`

## Seeding

This plugin has **no persisted entities of its own**, so a `Seed` subclass is
intentionally not provided. The in-flight processing state lives in Elgg config
keys and a per-run log file under `dataroot/csv_process_log/`, neither of which
benefits from fixture seeding. Acceptable per the skill's "no entity surface"
exemption.

## Migration Notes — 2.x → 3.x

Changes applied in this step:

- `manifest.xml`: `elgg_release` requirement bumped from `2.0` → `3.0`.
- `start.php`: top-level `elgg_register_event_handler('init', 'system', ...)` was moved into the returned closure required by Elgg 3.x; helper functions remain at file scope.
- `start.php`: removed `elgg_register_ajax_view('csv_process/ajax/progress')` — Elgg 3.x serves any view via the ajax endpoint without registration.
- `start.php`: split the `if (($handle = fopen(...)) !== false)` assignment-in-condition into separate statements (Elgg PHPCS sniff) and added missing docblocks (`log()`, refined `register_demo_handler()` and `demo_handler()`).
- `composer.json`: re-issued as `hypejunction/csv_process @ 3.0.0` with `elgg/elgg ^3.0`, `php >=7.2`, dual `composer/installers` constraint, and the `config.allow-plugins.composer/installers` block required by composer 2.2+.
- `elgg-plugin.php`: added — minimal metadata only (display name `CSV Process`). No declarative routes/actions/entities since the plugin has none.

The procedural namespace layout (`namespace csv_process;` in `start.php`,
no `classes/` directory) is **kept** for 3.x. Modernising into a Bootstrap
class with namespaced helpers is the natural shape for the 3.x → 4.x step,
where `start.php` and `manifest.xml` are both removed.

### Known issues / carry-forward

- The `log` helper function uses the global PHP name. PHP resolves it as
  `csv_process\log` inside the same namespace, so it does not clash with the
  PHP `log()` math function — but during the 3.x → 4.x step (`iv5j8`) this
  function should be renamed (e.g. `write_log`) to avoid any future PHP/Elgg
  reserved-word conflicts.
- `vroom` dependency is still declared. Re-evaluate at the 3.x → 4.x boundary.
- The AMD JS will need to migrate to ES modules at the 5.x → 6.x boundary.
- Plugin hook signature is still 4-arg legacy; modernise to `\Elgg\Hook` at
  the 4.x → 5.x boundary.

## Source

- Upstream (read-only): `arckinteractive/csv_process` — last commit 2015-12-29, abandoned.
- hypeJunction fork: `https://github.com/hypeJunction/csv_process` — base for the migration chain.
- Bodyology consumer: `bodyology_csv` registers `csv_process,callbacks` and uses the legacy 4-arg hook signature on purpose. Compatibility with that consumer is a hard constraint for every step on the way to 7.x.
