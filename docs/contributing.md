# Contributing

## Development Setup

```bash
composer install
composer check
```

If your local PHP installation is missing optional extensions required by transitive dependencies, install the extension where possible. For local-only work you can use Composer platform ignores, but CI should run with the real platform.

## Code Standards

- PHP 8.3+.
- Strict types.
- PHPStan level 8.
- Small focused value objects.
- Fluent APIs for user-facing configuration.
- Engine + Builder + Registry pattern where it improves discoverability.
- Domain-specific logic belongs in domain packages, not this shared layer.

## Adding Features

Before adding a feature, decide where it belongs:

| Feature type | Package |
|--------------|---------|
| Shared orchestration | `nexus-ai-content` |
| Article rules | `nexus-ai-content-articles` |
| Prompt versioning | `nexus-ai-prompts` |
| Provider communication | `nexus-ai` |
| Cost calculation | `nexus-ai-pricing` |
| Token counting | `nexus-ai-tokenizer` through pricing |
| Execution telemetry | `nexus-ai-tracking` |

## Pull Request Checklist

- Add or update tests.
- Update docs when public behavior changes.
- Add an example when a feature changes how users configure providers, pricing, tracking, or workflows.
- Run `composer check`.

---

Previous: [Testing](testing.md)  
Next: [Troubleshooting](troubleshooting.md)
