# Contributing

Contributions are welcome. Please open an issue first for larger changes so the approach can be discussed before you spend time on it.

## Pull requests

- Branch from `main` and open the pull request against `main`.
- Add tests for any change in behaviour. Bug fixes should include a test that fails without the fix.
- Keep one change per pull request. Several unrelated fixes are easier to review as separate pull requests.
- Update `README.md` when you change public behaviour. `CHANGELOG.md` is updated automatically on release.
- Follow [SemVer](https://semver.org/). Don't break the public API without discussing it first.

## Local setup

```bash
composer install
```

## Checks

Run all three before pushing. CI runs the same checks on every pull request.

```bash
composer test      # Pest test suite
composer analyse   # PHPStan (level 6)
composer format    # Laravel Pint (code style)
```

The test matrix covers PHP 8.2 to 8.5 and Laravel 11 to 13, with both lowest and latest dependencies.
