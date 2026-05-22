# nexus-ai-content Examples

These examples are designed to run against real providers. Copy `.env.example` to `.env`, add the API keys you want to test, then run any script from the package root.

```bash
copy examples\.env.example examples\.env
php examples\00-openai-draft.php
```

## Files

| Example | What it shows |
|---------|---------------|
| `00-openai-draft.php` | OpenAI content generation with prompts, pricing, tokenizer-aware budget, and content result output |
| `01-anthropic-outline.php` | Anthropic provider configuration and a different workflow |
| `02-gemini-spanish.php` | Gemini provider, Spanish prompt resolution, and language selection |
| `03-deepseek-budget.php` | DeepSeek provider with a stricter pre-request budget |
| `04-tracking-jsonfile.php` | JSONL tracking store and how to inspect recorded events |
| `05-custom-openai-compatible.php` | Custom OpenAI-compatible provider using `NexusAI::registerDriver()` |
| `06-testing-fake-driver.php` | Local testing with `FakeDriver`, no network |

The production examples share `_common.php`, which loads `.env`, configures `NexusAI`, creates the prompt registry, creates a pricing engine, and prints result summaries.
