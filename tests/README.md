# CommandJobs Tests

This directory contains unit and integration tests for all CLI commands in the CommandJobs microservice.

## Running Tests

### Run all tests
```bash
./vendor/bin/phpunit
```

### Run specific test file
```bash
./vendor/bin/phpunit tests/Commands/ActivatePlannedEntryTest.php
```

### Run tests with coverage
```bash
./vendor/bin/phpunit --coverage-html coverage/
```

### Run specific test method
```bash
./vendor/bin/phpunit --filter testCommandIsRegistered
```

## Test Structure

- **CommandTestCase.php**: Base test class that provides common functionality for all command tests
- **Commands/**: Contains individual test files for each CLI command

## Test Coverage

Each command test file includes the following test cases:

1. **testCommandIsRegistered**: Verifies the command is properly registered in the application
2. **testCommandConfiguration**: Checks command name, description, and help text
3. **testCommandExecutesSuccessfully**: Tests successful command execution
4. Additional specific tests based on command functionality

## Commands Tested

- ✅ ActivatePlannedEntry - `entry:activate-planned`
- ✅ AddPlannedEntry - `entry:add-planned`
- ✅ AlertBudget - `budget:alert`
- ✅ AlertBudgetNotification - `budget:notify-threshold`
- ✅ BillReminder - `entry:check-bill-reminder`
- ✅ BudgetPeriodChange - `budget:period-change`
- ✅ ManageCreditCardsWallet - `wallet:manage-credit-cards`
- ✅ TestMail - `test:mail`
- ✅ InstallDemoData - `install:demo-data`
- ✅ ClearDatabase - `database:clear`
- ✅ PrepareDatabase - `database:prepare`
- ✅ ExtractKeywordFromEntries - `entry:extract-keywords`

## Writing New Tests

To add tests for a new command:

1. Create a new test file in `tests/Commands/` with the format `{CommandName}Test.php`
2. Extend the `CommandTestCase` class
3. Add the command to the application in `setUp()`
4. Write test methods following the naming convention `test{Description}`

Example:
```php
<?php

namespace Budgetcontrol\jobs\Tests\Commands;

use Budgetcontrol\jobs\Cli\YourCommand;
use Symfony\Component\Console\Command\Command;

class YourCommandTest extends CommandTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->application->add(new YourCommand());
    }

    public function testCommandIsRegistered(): void
    {
        $this->assertTrue($this->application->has('your:command'));
    }
    
    // Add more tests...
}
```

## Notes

- Some tests (like database operations) may need to be adjusted for CI/CD environments
- Mock external dependencies when necessary
- Keep tests focused and independent
- Use descriptive test names that explain what is being tested
