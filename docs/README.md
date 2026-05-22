# nexus-ai-content - Documentation

Welcome to the full documentation for `token27/nexus-ai-content`.

This package sits above `nexus-ai`, `nexus-ai-prompts`, `nexus-ai-workflows`, `nexus-ai-tracking`, and `nexus-ai-pricing`. It gives domain packages a single orchestration layer for generating content with fluent APIs.

## Table of Contents

### Getting Started

| Guide | Description |
|-------|-------------|
| [Installation](installation.md) | Composer setup, requirements, `.env`, and quality commands |
| [Quick Start](quick-start.md) | First real OpenAI content workflow |
| [Fluent API](fluent-api.md) | `ContentEngine`, `ContentBuilder`, and terminal methods |

### Core Concepts

| Guide | Description |
|-------|-------------|
| [Workflows](workflows.md) | Registering workflows by content type and workflow name |
| [Prompts](prompts.md) | Resolving versioned prompts with `ContentAINode` |
| [Tracking and Pricing](tracking-pricing.md) | Telemetry, post-request cost, tokenizer-aware estimates, and budgets |
| [Architecture Decision](ARCHITECTURE_DECISION.md) | Why this package exists before article domains |

### Development

| Guide | Description |
|-------|-------------|
| [Extending](extending.md) | Creating domain extensions such as article packages |
| [Testing](testing.md) | Testing workflows, nodes, prompts, tracking, and budgets |
| [Contributing](contributing.md) | Repository workflow and code standards |
| [Troubleshooting](troubleshooting.md) | Common setup and runtime issues |

## At a Glance

```text
nexus-ai-content/
  src/
    Contract/          # Public interfaces
    Exception/         # Domain exceptions
    Node/              # Content-aware workflow nodes
    ValueObject/       # ContentRequest, ContentResult, metrics
    ContentEngine.php  # Engine entry point
    ContentBuilder.php # Fluent generation builder
    ContentRegistry.php
  examples/
    _common.php        # .env + provider + pricing + tracking bootstrap
    resources/prompts/ # Runnable example prompts
  docs/
```

## Ecosystem Position

`nexus-ai-content` should be consumed by domain packages:

- `nexus-ai-content-articles`
- `nexus-ai-content-social-media`
- future packages for newsletters, scripts, ecommerce, or editorial tools

It should not contain article-only rules. Those belong in the domain packages.

---

Next: [Installation](installation.md)
