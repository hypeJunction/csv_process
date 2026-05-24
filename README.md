CSV Process for Elgg
====================

![Elgg 4.x](https://img.shields.io/badge/Elgg-4.x-orange.svg?style=flat-square)

Admin tool that uploads a CSV file and dispatches each row to a custom
processing callback contributed by another plugin. Useful for one-off
data imports, bulk profile updates, and similar tasks.

The form for handling CSV processing is found at
**Admin → Utilities → CSV Processing**.

## Installation

```bash
composer require hypejunction/csv_process:^4.0
```

Then enable through Admin → Plugins.

## Compatibility

| Plugin version | Elgg version |
|---|---|
| current | 4.x |
| 3.x     | 3.x |
| 2.x     | 2.x |

## Integration

Other plugins register CSV processing callbacks by listening on the
`csv_process,callbacks` plugin hook and returning a `callable-string =>
label-string` map. The hook signature is the legacy 4-arg form for
compatibility with older registrants; this will be unified at the
4.x → 5.x boundary.

Register your hook handler (in your plugin's `elgg-plugin.php`):

```php
return [
    'hooks' => [
        'csv_process' => [
            'callbacks' => [
                'My\\Plugin\\Csv::register' => [],
            ],
        ],
    ],
];
```

Implement the hook + per-row handler:

```php
namespace My\Plugin;

use Elgg\Hook;

class Csv {
    public static function register(Hook $hook) {
        $return = (array) $hook->getValue();
        $return[self::class . '::handle'] = elgg_echo('myplugin:handler:label');
        return $return;
    }

    public static function handle(array $params) {
        // First line is column headers — skip.
        if ($params['line'] === 1) {
            return;
        }

        // Synthetic final invocation — emit a summary line.
        if ($params['last']) {
            return "{$params['line']} lines processed";
        }

        // Row data is in $params['data'].
        // Do work. Return a string to log; return false / null to skip logging.
        return "First cell: {$params['data'][0]}";
    }
}
```

The plugin exposes a single demo handler (`CsvProcess\DemoHandler::handle`)
that logs the first cell of each row — useful as a smoke test.

## Architecture

See [`ARCHITECTURE.md`](ARCHITECTURE.md) for the current 4.x layout.

## License

GPL-2.0-or-later
