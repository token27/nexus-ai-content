# Quick Start

This guide shows the real usage path: configure a provider, load prompts, register a workflow, and generate content.

## 1. Configure NexusAI

```php
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use Token27\NexusAI\NexusAI;

$factory = new HttpFactory();

NexusAI::reset();
NexusAI::setHttpClient(new Client(['timeout' => 90]));
NexusAI::setFactories($factory, $factory);
NexusAI::configure([
    'openai' => ['api_key' => $_ENV['OPENAI_API_KEY']],
]);
```

## 2. Create a Prompt Registry

```php
use Token27\NexusAI\Prompts\Engine\MustacheAdapter;
use Token27\NexusAI\Prompts\Loader\PromptLoader;
use Token27\NexusAI\Prompts\Loader\PromptSchemaValidator;
use Token27\NexusAI\Prompts\PromptRegistry;
use Token27\NexusAI\Prompts\Storage\LocalFilesystemStorage;

$promptRegistry = new PromptRegistry(
    loader: new PromptLoader(new PromptSchemaValidator(), new MustacheAdapter()),
    defaultStorage: new LocalFilesystemStorage(''),
    defaultLanguage: 'en',
    fallbackLanguage: 'en',
);

$promptRegistry->autoloadFrom(__DIR__);
```

Prompt path:

```text
resources/prompts/content/draft/v1.0.0/en.json
```

Identifier:

```text
content/draft
```

## 3. Register a Workflow

```php
use Token27\NexusAI\Content\Node\ContentAINode;
use Token27\NexusAI\Workflows\Engine\WorkflowBuilder;

$workflow = WorkflowBuilder::named('draft')
    ->addNode('draft', new ContentAINode(
        promptIdentifier: 'content/draft',
        outputKey: 'content',
        promptVersion: '1.0.0',
        temperature: 0.35,
        maxTokens: 700,
    ))
    ->build();
```

## 4. Generate Content

```php
use Token27\NexusAI\Content\ContentEngine;
use Token27\NexusAI\Pricing\Engine\PricingEngine;
use Token27\NexusAI\Pricing\Registry\PricingRegistry;
use Token27\NexusAI\Tracking\Engine\TrackingEngine;

$pricing = PricingEngine::withRegistry(PricingRegistry::createDefault());

$engine = ContentEngine::create()
    ->withPromptRegistry($promptRegistry)
    ->withPricing($pricing)
    ->withTracking(TrackingEngine::using('memory'));

$engine->getRegistry()->register('article', $workflow);

$result = $engine
    ->for('openai', 'gpt-4o-mini')
    ->withContentType('article')
    ->withLanguage('en')
    ->withVariable('topic', 'maintainable AI content workflows')
    ->withVariable('audience', 'PHP developers')
    ->generate();

echo $result->text;
```

## Full Examples

See:

- `examples/00-openai-draft.php`
- `examples/01-anthropic-outline.php`
- `examples/02-gemini-spanish.php`
- `examples/03-deepseek-budget.php`

---

Previous: [Installation](installation.md)  
Next: [Fluent API](fluent-api.md)
