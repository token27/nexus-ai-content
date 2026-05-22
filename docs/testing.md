# Testing

## Running Tests

```bash
vendor/bin/phpunit
vendor/bin/phpstan analyse --level=8 src tests
vendor/bin/php-cs-fixer fix --dry-run --diff
```

Or:

```bash
composer check
```

## Testing A Workflow With FakeDriver

Use `FakeDriver` for deterministic tests:

```php
$fake = new FakeDriver();
$fake->willReturn(new TextResponse(
    text: 'Generated text',
    finishReason: FinishReason::Stop,
));

$result = $engine
    ->for('fake', 'test-model')
    ->withDriver($fake)
    ->withContentType('article')
    ->withVariable('topic', 'testing')
    ->generate();

$this->assertSame('Generated text', $result->text);
$fake->assertPromptContains('testing');
```

## Testing Prompt Resolution

Use a real `PromptRegistry` or a small test registry. Assert that the final request contains rendered variables.

```php
$fake->assertPromptContains('workflow orchestration');
```

## Testing Tracking

Use the in-memory tracking store:

```php
$tracking = TrackingEngine::using('memory');

$engine = ContentEngine::create()
    ->withTracking($tracking);

$events = $tracking->findByRun($runId);
```

Assert event type, model, prompt identifier, and cost/tokens when available.

## Testing Budgets

Inject a pricing engine with a deterministic `TextEstimatorInterface`. Then call `withBudget()` and assert that high-cost estimates throw `ContentBudgetExceededException`.

---

Previous: [Extending](extending.md)  
Next: [Contributing](contributing.md)
