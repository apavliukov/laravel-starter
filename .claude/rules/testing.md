# Testing Guidelines

## Core Rules

- Every change must be programmatically tested. Write a new test or update an existing test, then run affected tests.
- All tests must be PHPUnit classes. If you see a test using "Pest", convert it to PHPUnit.
- Tests should cover all happy paths, failure paths, and weird paths.
- Do not remove any tests or test files from the tests directory without approval. These are core to the application.
- Do not create verification scripts or tinker when tests cover that functionality and prove it works.

## Base TestCase

The base `tests/TestCase.php` already includes `RefreshDatabase`, `AdditionalAssertions`, and `CanConfigureMigrationCommands`, and disables stray HTTP requests via `Http::preventStrayRequests()` in `setUp()`. Do NOT duplicate these traits in individual test classes — check `tests/TestCase.php` for any project-specific helpers before adding them yourself.

## Creating Tests

- Create feature tests: `vendor/bin/sail artisan make:test --phpunit {name}`
- Create unit tests: `vendor/bin/sail artisan make:test --phpunit --unit {name}`
- Most tests should be feature tests.
- Use model factories when creating models for tests.
- Check if the factory has custom states that can be used before manually setting up the model.
- Faker: follow existing conventions whether to use `$this->faker` or `fake()`.
- Use methods such as `$this->faker->word()` or `fake()->randomDigit()`.

## Running Tests

- Run minimal tests using appropriate filters before finalizing changes.
- Run all tests: `vendor/bin/sail artisan test`
- Run tests in a specific file: `vendor/bin/sail artisan test tests/Feature/ExampleTest.php`
- Filter by test name: `vendor/bin/sail artisan test --filter=testName` (recommended after changes)
- Every time a test is updated, run that singular test immediately.
- When the tests relating to your feature are passing, ask the user if they would like to also run the entire test suite to make sure everything is still passing.

## PHPUnit Attributes

Always add `#[Group]` and `#[CoversClass]` at the class level. Import from `PHPUnit\Framework\Attributes\`.

**`#[Group]`** — enables targeted runs (`vendor/bin/sail artisan test --group=livewire`). Use one group per dimension:

| Group | When to use |
|-------|-------------|
| `actions` | Unit tests for Action classes |
| `queries` | Unit tests for Query classes |
| `policies` | Unit tests for Policy classes |
| `dto` | Unit tests for DTO classes |
| `scopes` | Unit tests for model scopes |
| `livewire` | Feature tests using `Livewire::test()` |
| `admin` | Feature tests in the admin/platform area |
| `web` | Feature tests for public-facing web routes |
| `api` | Feature tests for API routes |
| `users` | Any test scoped to the User model/domain |

A test class typically has two groups: a type group (`actions`, `livewire`, etc.) and a domain group (`users`, etc.).

**`#[CoversClass(ClassName::class)]`** — declares the primary class under test. Required on every test class for accurate coverage reports.

**`#[DataProvider('methodName')]`** — use for parameterized tests instead of duplicating test methods. The provider method must be `public static` and return an iterable.

```php
#[Group('actions')]
#[Group('users')]
#[CoversClass(CreateUser::class)]
final class CreateUserTest extends TestCase { ... }
```

## Test Quality

- Unit and feature tests are more important than verification scripts.
- Ensure tests are self-contained and do not depend on external state.
- Follow existing test patterns in the codebase for consistency.