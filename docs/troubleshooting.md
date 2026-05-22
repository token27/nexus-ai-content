# Troubleshooting

## Missing API Key

Error:

```text
Missing environment variable: OPENAI_API_KEY
```

Copy `examples/.env.example` to `examples/.env` and fill the provider key.

## HTTP Dependencies Not Configured

Real providers require `NexusAI` HTTP setup:

```php
NexusAI::setHttpClient($client);
NexusAI::setFactories($requestFactory, $streamFactory);
```

The examples do this in `examples/_common.php`.

## Prompt Not Found

Check:

- the registry called `autoloadFrom()` with the package root
- the prompt path is under `resources/prompts/{namespace}/{type}/v{version}/{language}.json`
- the `ContentAINode` identifier matches `{namespace}/{type}`
- the requested language exists or can fall back

## Budget Does Not Stop A Request

`withBudget()` depends on pre-request estimation. Configure pricing with tokenizer support:

```php
$pricing = PricingEngine::withTokenizer(TokenizerRegistry::createDefault());
```

Without estimation, the request is allowed and cost is calculated after usage is returned.

## Empty Content Result

If a workflow fails, inspect:

```php
$result->workflowResult->getError();
$result->workflowResult->steps;
```

Common causes are missing prompts, provider credentials, or an unconfigured `NexusAI` facade.

---

Previous: [Contributing](contributing.md)  
Next: [Documentation Home](README.md)
