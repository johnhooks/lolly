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

## Local Development

Use Lando for local WordPress development:

```bash
lando start              # Start WordPress environment
lando install-wp         # Install WordPress (runs automatically on start)
lando xdebug-on          # Enable XDebug
lando xdebug-off         # Disable XDebug
lando composer install   # Install PHP dependencies
lando phpcs              # Run PHP code sniffer
lando phpstan            # Run static analysis
```

Site runs at https://lolly.lndo.site (admin/password).

Logs are written to `./tmp/logs/` for easy IDE access. WordPress debug log goes to `./tmp/logs/debug.log`, plugin logs to `./tmp/logs/lolly.log`.

## Commands

- **Lint PHP**: `composer run lint` or `vendor/bin/phpcs -s`
- **Fix PHP**: `composer run lint:fix` or `vendor/bin/phpcbf`
- **Analyze PHP**: `composer run analyse` or `vendor/bin/phpstan analyse --memory-limit=4G`
- **Tests**: `vendor/bin/codecept run`
- **Run specific suite**: `vendor/bin/codecept run Unit`
- **Run specific test**: `vendor/bin/codecept run Unit:TestName`

Pre-commit hooks run automatically via lint-staged: PHP syntax check, phpcs, phpstan for PHP files; wp-scripts for JS/TS/CSS.

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

Use Codeception with slic (Docker-based). Lando is for development; slic is for running tests.

Run from the parent directory (`~/Projects`):
```bash
slic here                        # Point slic at plugins directory
slic use lolly                   # Select this plugin
slic composer install            # Install dependencies
slic composer strauss            # Build prefixed dependencies
slic airplane-mode on            # Block external HTTP requests
slic run Wpunit                  # Run tests
slic run Wpunit:SettingsTest     # Run specific test

# Drop into slic shell for debugging
slic shell
cd /var/www/html/wp-content/plugins/lolly
vendor/bin/codecept run Wpunit
```

## Commits

Use the `/commit` slash command. We use Graphite (`gt create`) for stacked PRs. Conventional commits without emoji, 80 char body lines.

