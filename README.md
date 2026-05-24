CSV Process for Elgg
====================

![Elgg 6.x](https://img.shields.io/badge/Elgg-6.x-orange.svg?style=flat-square)

Admin tool that uploads a CSV file and dispatches each row to a custom
processing callback contributed by another plugin. Useful for one-off
data imports, bulk profile updates, and similar tasks.

The form for handling CSV processing is found at
**Admin → Utilities → CSV Processing**.

## Installation

```bash
composer require hypejunction/csv_process:~6.1.0
```

Then enable through Admin → Plugins.

## Compatibility

| Plugin version | Elgg version |
|---|---|
| current | 6.x |
| 5.x     | 5.x |
| 4.x     | 4.x |
| 3.x     | 3.x |
| 2.x     | 2.x |

## Integration

Other plugins register CSV processing callbacks by listening on the
`csv_process,callbacks` event and returning a `callable-string =>
label-string` map. Per-row callbacks (the values in that map) are
invoked positionally with a single `array $params` argument — they
are NOT Elgg event handlers, so their signature is independent of
the Elgg hook/event API.

Register your event handler (in your plugin's `elgg-plugin.php`):

```php
return [
    'events' => [
        'csv_process' => [
            'callbacks' => [
                'My\\Plugin\\Csv::register' => [],
            ],
        ],
    ],
];
```

Implement the event + per-row handler:

```php
namespace My\Plugin;

use Elgg\Event;

class Csv {
    public static function register(Event $event) {
        $return = (array) $event->getValue();
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

See [`ARCHITECTURE.md`](ARCHITECTURE.md) for the current 6.x layout.

## License

GPL-2.0-or-later
