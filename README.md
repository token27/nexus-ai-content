# nexus-ai-content

[![CI](https://github.com/token27/nexus-ai-content/actions/workflows/ci.yml/badge.svg)](https://github.com/token27/nexus-ai-content/actions)
[![PHPStan Level 8](https://img.shields.io/badge/PHPStan-Level%208-1f6feb)](https://phpstan.org/)
[![Latest Version](https://img.shields.io/packagist/v/token27/nexus-ai-content.svg?style=flat-square)](https://packagist.org/packages/token27/nexus-ai-content)
[![PHP 8.3+](https://img.shields.io/badge/PHP-8.3%2B-777BB4?logo=php&logoColor=white)](https://php.net)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)
[![Tests](https://img.shields.io/badge/Tests-5%20passing-brightgreen)](docs/testing.md)

A **framework-agnostic, workflow-first** PHP 8.3+ content orchestration layer for the NexusAI ecosystem. Compose versioned prompts, AI drivers, resumable workflows, tracking, pricing, and domain extensions behind one fluent API.

## Why nexus-ai-content?

Article generation, social posts, newsletters, scripts, product descriptions, and future content types all need the same foundation:

- choose a content type and workflow
- resolve versioned prompts from `nexus-ai-prompts`
- execute nodes through `nexus-ai-workflows`
- call providers through `nexus-ai`
- track every run through `nexus-ai-tracking`
- calculate cost and enforce budgets through `nexus-ai-pricing`
- keep domain packages focused on their domain instead of infrastructure

`nexus-ai-content` is that shared layer. It is intentionally **not** an article generator. Article logic belongs in `nexus-ai-content-articles`.

## Features

- **Engine + Builder + Registry pattern** consistent with the NexusAI libraries.
- **Fluent content API** for provider/model selection, workflow selection, variables, language, run id, tracking, pricing, and budgets.
- **Workflow registry** keyed by `contentType/workflowName`.
- **Prompt-aware AI node** with `ContentAINode`.
- **Tracking-ready execution** with run id, prompt id, prompt version, content type, workflow, provider, model, tokens, cost, and latency.
- **Pricing-ready execution** with post-request cost calculation and optional pre-request budget checks.
- **Extension bootstrap** so packages like `nexus-ai-content-articles` can register workflows cleanly.
- **Test-friendly driver injection** through `withDriver()`.

## Installation

```bash
composer require token27/nexus-ai-content
```

**Requires:** PHP 8.3+ and the core NexusAI packages listed in `composer.json`.

## Quick Start

```php
use Token27\NexusAI\Content\ContentEngine;
use Token27\NexusAI\Content\Node\ContentAINode;
use Token27\NexusAI\Workflows\Engine\WorkflowBuilder;

$workflow = WorkflowBuilder::named('default')
    ->addNode('draft', new ContentAINode(
        promptIdentifier: 'content/draft',
        outputKey: 'content',
        promptVersion: '1.0.0',
    ))
    ->build();

$engine = ContentEngine::create()
    ->withPromptRegistry($promptRegistry)
    ->withTracking($tracking)
    ->withPricing($pricing);

$engine->getRegistry()->register('article', $workflow);

$result = $engine
    ->for('openai', 'gpt-4o-mini')
    ->withContentType('article')
    ->withLanguage('es')
    ->withVariable('topic', 'Calidad de contenido con IA')
    ->withBudget(0.25)
    ->generate();

echo $result->text;
```

For tests, inject a pre-built driver:

```php
$result = $engine
    ->for('fake', 'test-model')
    ->withContentType('article')
    ->withDriver($fakeDriver)
    ->generate();
```

## Documentation

| Guide | Description |
|-------|-------------|
| [Documentation Home](docs/README.md) | Full documentation map |
| [Installation](docs/installation.md) | Composer, dependencies, and first setup |
| [Quick Start](docs/quick-start.md) | First workflow with a fake driver |
| [Fluent API](docs/fluent-api.md) | Engine and builder methods |
| [Workflows](docs/workflows.md) | Registering and running content workflows |
| [Prompts](docs/prompts.md) | Using `nexus-ai-prompts` with `ContentAINode` |
| [Tracking and Pricing](docs/tracking-pricing.md) | Telemetry, costs, budgets, and tokenizer notes |
| [Extending](docs/extending.md) | Creating domain extensions |
| [Testing](docs/testing.md) | Testing content workflows and nodes |
| [Contributing](docs/contributing.md) | Development setup and collaboration rules |
| [Architecture](docs/ARCHITECTURE_DECISION.md) | Why this package exists before article domains |
| [Troubleshooting](docs/troubleshooting.md) | Common failures and fixes |

## Examples

The `examples/` directory contains runnable production-style examples that read `.env`, configure real providers through `NexusAI`, load prompts, enable pricing, and optionally write tracking events:

- [00-openai-draft.php](examples/00-openai-draft.php)
- [01-anthropic-outline.php](examples/01-anthropic-outline.php)
- [02-gemini-spanish.php](examples/02-gemini-spanish.php)
- [03-deepseek-budget.php](examples/03-deepseek-budget.php)
- [04-tracking-jsonfile.php](examples/04-tracking-jsonfile.php)
- [05-custom-openai-compatible.php](examples/05-custom-openai-compatible.php)
- [06-testing-fake-driver.php](examples/06-testing-fake-driver.php)

## Quality Gates

```bash
composer validate --strict
vendor/bin/php-cs-fixer fix --dry-run --diff
vendor/bin/phpstan analyse --level=8 src tests
vendor/bin/phpunit
```

## License

MIT. See [LICENSE](LICENSE).
