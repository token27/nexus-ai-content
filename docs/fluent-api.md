# Fluent API

`nexus-ai-content` follows the ecosystem pattern: Engine, Builder, Registry.

## ContentEngine

The engine owns shared services and the workflow registry.

```php
$engine = ContentEngine::create()
    ->withPromptRegistry($promptRegistry)
    ->withTracking($tracking)
    ->withPricing($pricing)
    ->registerExtension(new ArticleExtension());
```

### Methods

| Method | Description |
|--------|-------------|
| `create()` | Creates an empty engine |
| `using(string $provider, string $model)` | Static shortcut that returns a builder |
| `for(string $provider, string $model)` | Creates a builder from an engine instance |
| `withPromptRegistry($registry)` | Shares prompt resolution with all builders |
| `withTracking($tracking)` | Shares tracking with all builders |
| `withPricing($pricing)` | Shares pricing and budget estimation with all builders |
| `withDriverRegistry($registry)` | Optional explicit driver registry |
| `registerExtension($extension)` | Lets domain packages register workflows |
| `getRegistry()` | Returns the content workflow registry |

## ContentBuilder

The builder represents one generation run.

```php
$result = $engine
    ->for('openai', 'gpt-4o-mini')
    ->withContentType('article', 'default')
    ->withLanguage('en')
    ->withRunId('article-123')
    ->withVariables([
        'topic' => 'AI publishing pipelines',
        'audience' => 'editors',
    ])
    ->withBudget(0.25)
    ->generate();
```

### Methods

| Method | Description |
|--------|-------------|
| `withContentType(string $type, string $workflowName = 'default')` | Chooses the registered workflow |
| `withVariables(array $variables)` | Merges variables into the workflow context |
| `withVariable(string $key, mixed $value)` | Adds one variable |
| `withLanguage(string $language)` | Sets `_content_language` for prompt resolution |
| `withRunId(string $runId)` | Sets a stable run id for tracking |
| `withDriver($driver)` | Injects a pre-built driver, useful for tests |
| `withDriverRegistry($registry)` | Uses an explicit registry |
| `withPromptRegistry($registry)` | Overrides engine prompt registry for this run |
| `withTracking($tracking)` | Overrides engine tracking for this run |
| `withPricing($pricing)` | Overrides engine pricing for this run |
| `withBudget(float $maxCostUsd)` | Enforces pre-request estimate when pricing supports it |
| `generate()` | Returns `ContentResult` |
| `generatePhased()` | Returns raw `WorkflowResult` |

## Driver Resolution

`ContentAINode` resolves execution in this order:

1. `_driver` from `withDriver()`.
2. `_driver_registry` from `withDriverRegistry()`.
3. `NexusAI::using($provider, $model)` using the globally configured `NexusAI` facade.

That means production examples can use normal provider configuration, while tests can inject `FakeDriver`.

---

Previous: [Quick Start](quick-start.md)  
Next: [Workflows](workflows.md)
