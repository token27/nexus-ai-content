# Installation

## Requirements

| Requirement | Version |
|-------------|---------|
| PHP | 8.3 or higher |
| `token27/nexus-ai` | `^1.0` |
| `token27/nexus-ai-prompts` | `^1.0` |
| `token27/nexus-ai-workflows` | `^1.0` |
| `token27/nexus-ai-tracking` | `^1.0` |
| `token27/nexus-ai-pricing` | `^1.0` |

Optional:

| Package | Why |
|---------|-----|
| `token27/nexus-ai-tokenizer` | Tokenizer-aware pre-request cost estimation and budgets through `nexus-ai-pricing` |
| `token27/nexus-ai-images` | Later domain workflows that generate images |

## Composer Install

```bash
composer require token27/nexus-ai-content
```

For local development:

```bash
composer install
composer check
```

## Real Provider Examples

The examples use the same style as `nexus-ai`: copy `.env.example`, add API keys, and run scripts directly.

```bash
copy examples\.env.example examples\.env
php examples\00-openai-draft.php
```

Minimal `.env` for OpenAI:

```dotenv
OPENAI_API_KEY=sk-...
OPENAI_TEXT_MODEL=gpt-4o-mini
```

## HTTP Dependencies

When using real providers, configure `NexusAI` with a PSR-18 client and PSR-17 factories. The examples use Guzzle:

```php
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use Token27\NexusAI\NexusAI;

$factory = new HttpFactory();

NexusAI::reset();
NexusAI::setHttpClient(new Client(['timeout' => 90]));
NexusAI::setFactories($factory, $factory);
NexusAI::configure([
    'openai' => ['api_key' => $apiKey],
]);
```

`ContentAINode` can then call `NexusAI::using($provider, $model)` automatically when no explicit driver is injected.

## Quality Gates

```bash
composer validate --strict
vendor/bin/php-cs-fixer fix --dry-run --diff
vendor/bin/phpstan analyse --level=8 src tests
vendor/bin/phpunit
```

---

Previous: [Documentation Home](README.md)  
Next: [Quick Start](quick-start.md)
