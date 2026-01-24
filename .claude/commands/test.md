# Running Tests

This project uses Codeception with slic (Docker-based WordPress testing environment).

## Running Tests

Tests must be run from the parent directory (`~/Projects`) using slic:

```bash
# Point slic at the plugins directory (if not already done)
slic here

# Select this plugin
slic use lolly

# Run all tests
slic run Wpunit

# Run tests in a directory
slic run Wpunit "Listeners/"

# Run a specific test file
slic run Wpunit "Listeners/LogOnUserCreatedTest.php"

# Run a specific test method
slic run Wpunit "Listeners/LogOnUserCreatedTest.php:testLogsUserCreation"
```

## Debugging Tests

To debug tests, use `codecept_debug()` in your test code and run with the `--debug` flag:

```php
// In your test file
codecept_debug($variable);
codecept_debug($records[0]->context);
```

```bash
# Run with debug output
slic run Wpunit:TestName -- --debug
```

## Interactive Debugging

For more complex debugging, drop into the slic shell:

```bash
slic shell
cd /var/www/html/wp-content/plugins/lolly
vendor/bin/codecept run Wpunit --debug
```

## Test Helpers

The `WpunitTester` provides helper methods:

- `$this->tester->loginAsAdmin()` - Log in as administrator
- `$this->tester->loginAsRole('editor')` - Log in as specific role
- `$this->tester->logout()` - Log out current user
- `$this->tester->fakeLogger()` - Capture log records
- `$this->tester->updateSettings([...])` - Update plugin settings
- `$this->tester->seeLogMessage('message', 'level')` - Assert log message exists
- `$this->tester->seeLogCount(n)` - Assert number of log records
- `$this->tester->grabLogRecords()` - Get all captured log records
