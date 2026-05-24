# Changelog

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
