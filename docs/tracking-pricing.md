# Tracking and Pricing

Tracking and pricing are optional but designed to be configured from day one.

## Tracking

Use `nexus-ai-tracking`:

```php
use Token27\NexusAI\Tracking\Engine\TrackingEngine;

$tracking = TrackingEngine::using('jsonfile', __DIR__ . '/var/tracking');

$engine = ContentEngine::create()
    ->withPromptRegistry($promptRegistry)
    ->withTracking($tracking);
```

Each successful `ContentAINode` records:

- provider
- model
- content type
- workflow name
- language
- prompt identifier and version
- tokens
- cost
- latency

Read events:

```php
$events = $tracking->findByRun($runId);
```

## Pricing

Use `nexus-ai-pricing`:

```php
use Token27\NexusAI\Pricing\Engine\PricingEngine;
use Token27\NexusAI\Pricing\Registry\PricingRegistry;

$pricing = PricingEngine::withRegistry(PricingRegistry::createDefault());

$engine = ContentEngine::create()
    ->withPricing($pricing);
```

Post-request cost calculation uses token usage returned by the provider.

## Tokenizer-Aware Budgets

Pre-request budgets need token estimation. Configure pricing with `token27/nexus-ai-tokenizer` when available:

```php
use Token27\NexusAI\Pricing\Engine\PricingEngine;
use Token27\Tokenizer\Registry\TokenizerRegistry;

$pricing = PricingEngine::withTokenizer(TokenizerRegistry::createDefault());

$result = $engine
    ->for('openai', 'gpt-4o-mini')
    ->withPricing($pricing)
    ->withBudget(0.05)
    ->generate();
```

If pricing cannot estimate tokens, `ContentAINode` lets the request continue and still calculates cost after the provider returns usage.

## Custom Token Counting

If a provider has custom token rules, implement `TextEstimatorInterface` in `nexus-ai-pricing` and pass the engine into content:

```php
$pricing = PricingEngine::withTextEstimator(new MyProviderEstimator());

$engine = ContentEngine::create()
    ->withPricing($pricing);
```

This keeps tokenizer logic in the pricing layer while making it discoverable from content generation.

---

Previous: [Prompts](prompts.md)  
Next: [Extending](extending.md)
