# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Structure

- Create Jobs for encapsulating units of work.

## Commands

- **Lint PHP**: `composer run lint` or `@php vendor/bin/phpcs -s`
- **Fix PHP**: `composer run lint:fix` or `php vendor/bin/phpcbf`
- **Analyze PHP**: `composer run analyse` or `php vendor/bin/phpstan analyse --memory-limit=4G --no-progress --no-interaction --ansi`
- **Tests**: `php vendor/bin/codecept run`
- **Run specific suite**: `php vendor/bin/codecept run Unit`
- **Run specific test**: `php vendor/bin/codecept run Unit:TestName`

## Code Style Guidelines
- **Namespaces**: Use `Dozuki` namespace with subnamespaces for components (`Dozuki\Lib`, `Dozuki\Log`)
- **Class Names**: PascalCase (e.g., `Redactor`, `LogOnCentralVerbRequest`)
- **Method Names**: snake_case (e.g., `handle()`, `handle_request()`)
- **Variables**: snake_case (e.g., `$post_id`, `$tool_name`)
- **Returns**: WordPress conventions with `\WP_Error` for failures
- **Type Annotations**: Use PHPDoc for parameters and return types
- **Error Handling**: Return WordPress error objects with descriptive messages
- **Documentation**: Include docblocks for classes and methods
- **JS/TS**: WordPress ESLint plugin with custom import order rules
- **Imports**: Group imports (PHP: alphabetical, JS: grouped by builtin/external/parent/sibling)
- **Files**: One class per file, filename should match class name
- **Types**: Do not use strict typing in PHP.

## Testing

Run unit tests with `php vendor/bin/codecept run`. Use Codeception for all tests. Prefer
using `$I->see()` style of methods over asserts.

Commands:
```bash
# Run all tests
./vendor/bin/codecept run --no-ansi

# Run unit tests only
./vendor/bin/codecept run Wpunit --no-ansi

# Run end to end tests only
./vendor/bin/codecept run EndToEnd --no-ansi

# Run a specific test
./vendor/bin/codecept run Wpunit:SettingsTest --no-ansi
```

## Resources

WordPress is installed locally in the `tests/_wordpress` directory, it should be prioritized
when searching for APIs used by this project.

## Helpful Links

- **WordPress Plugin Handbook:** https://make.wordpress.org/core/handbook/plugins/
- **WordPress Coding Standards:** https://make.wordpress.org/core/handbook/best-practices/coding-standards/php/
- **WordPress REST API Handbook:** https://developer.wordpress.org/rest-api/
- **WP-CLI Documentation:** https://wp-cli.com/docs/
