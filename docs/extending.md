# Extending

Domain packages should extend `nexus-ai-content` by registering workflows and prompts.

## Extension Contract

```php
use Token27\NexusAI\Content\Contract\ContentEngineInterface;
use Token27\NexusAI\Content\Contract\ExtensionInterface;

final class ArticleExtension implements ExtensionInterface
{
    public function register(ContentEngineInterface $engine): void
    {
        $engine->getRegistry()->register('article', ArticleWorkflowFactory::default());
        $engine->getRegistry()->register('article', ArticleWorkflowFactory::phased(), 'phased');
    }
}
```

Usage:

```php
$engine = ContentEngine::create()
    ->registerExtension(new ArticleExtension());
```

## What Belongs In A Domain Package

For `nexus-ai-content-articles`:

- article DTOs
- article section value objects
- article workflows
- article prompts
- SEO and metadata nodes
- final assembly nodes
- optional image workflow steps later

## What Does Not Belong In A Domain Package

Do not duplicate:

- provider/model selection
- prompt registry wiring
- tracking conventions
- pricing conventions
- content workflow registry
- generic content result shape

Those are already handled by `nexus-ai-content`.

## Prompt Autoloading

A domain extension may expose a helper to autoload its prompts:

```php
$promptRegistry->autoloadFrom(__DIR__ . '/../vendor/token27/nexus-ai-content-articles');
```

Then its workflows can reference identifiers such as:

```text
article/structure
article/section-body
article/metadata
```

---

Previous: [Tracking and Pricing](tracking-pricing.md)  
Next: [Testing](testing.md)
