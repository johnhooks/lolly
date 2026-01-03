# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Lolly Log is an ECS (Elastic Common Schema) compatible HTTP request/response logging plugin for WordPress. It logs WordPress REST API requests and WP HTTP Client outbound requests with configurable redaction to protect sensitive data.

## Architecture

```
src/Lolly/
├── Admin/           # Settings page UI
├── Config/          # Configuration loading, validation, settings registration
├── Lib/
│   ├── Contracts/   # Interfaces for redactors and whitelist
│   ├── Enums/       # HttpRedactionType enum
│   ├── Processors/  # ECS formatting, redaction processing
│   ├── Services/    # Redactor implementations
│   └── ValueObjects/# Data structures (RedactionItem, etc.)
├── Listeners/       # HTTP event listeners (REST API, HTTP Client)
├── Log/             # Logger factory (Monolog)
└── Processors/      # WordPress-specific processors
```

Key patterns:
- **Listeners** capture HTTP events and delegate to processors
- **Processors** transform log records (ECS format, redaction)
- **Config** manages settings via WordPress options with JSON Schema validation

## Commands

- **Lint PHP**: `composer run lint` or `@php vendor/bin/phpcs -s`
- **Fix PHP**: `composer run lint:fix` or `php vendor/bin/phpcbf`
- **Analyze PHP**: `composer run analyse` or `php vendor/bin/phpstan analyse --memory-limit=4G --no-progress --no-interaction --ansi`
- **Tests**: `php vendor/bin/codecept run`
- **Run specific suite**: `php vendor/bin/codecept run Unit`
- **Run specific test**: `php vendor/bin/codecept run Unit:TestName`

## Code Style Guidelines

### PHP
- **Namespaces**: `Lolly` with subnamespaces (`Lolly\Lib`, `Lolly\Log`)
- **Classes**: PascalCase (`Redactor`, `LogOnCentralVerbRequest`)
- **Methods/Variables**: snake_case (`handle_request()`, `$post_id`)
- **Returns**: `\WP_Error` for failures, follow WordPress conventions
- **Types**: PHPDoc annotations, no strict typing
- **Files**: One class per file, filename matches class name

### JS/TS
- **Variables/Functions**: camelCase (`handleChange`, `editSettings`)
- **Components**: PascalCase (`ConfigEditor`, `SettingsPage`)
- **Imports**: Grouped by builtin/external/parent/sibling with blank lines between
- **Linting**: WordPress ESLint plugin with Prettier

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

## Commits

Use the `/commit` slash command. We use Graphite (`gt create`) for stacked PRs. Conventional commits without emoji, 80 char body lines.

