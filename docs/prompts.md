# Prompts

`nexus-ai-content` consumes `nexus-ai-prompts`. It does not own prompt storage, versioning, language fallback, or JSON schema.

## Prompt Layout

```text
resources/prompts/content/draft/v1.0.0/en.json
resources/prompts/content/draft/v1.0.0/es.json
```

Identifier:

```text
content/draft
```

Source:

```text
token27/nexus-ai-content
```

`identifier` points to the prompt namespace/type. `source` points to the package or registered origin that provides that prompt. For a normal Composer package, prefer the full package name (`vendor/package`) so tracking queries can filter by package.

## Prompt Example

```json
{
  "meta": {
    "version": "1.0.0",
    "prompt_type": "content-draft",
    "language": "en"
  },
  "blocks": [
    {
      "role": "system",
      "content": "You are a senior content strategist."
    },
    {
      "role": "user",
      "content": "Write about {{topic}} for {{audience}}."
    }
  ],
  "variables": {
    "topic": { "type": "string", "required": true },
    "audience": { "type": "string", "required": true }
  }
}
```

## Using ContentAINode

```php
new ContentAINode(
    promptIdentifier: 'content/draft',
    outputKey: 'content',
    promptVersion: '1.0.0',
    temperature: 0.35,
    maxTokens: 700,
)
```

The node:

- resolves the prompt from `_prompt_registry`
- renders it with the current workflow context
- sends the system and user messages to the selected provider
- stores generated text under `outputKey`
- stores the raw response under `{outputKey}_response`
- records tracking and pricing metadata when configured

## Language

```php
$result = $engine
    ->for('gemini', 'gemini-2.5-flash')
    ->withContentType('article')
    ->withLanguage('es')
    ->withVariable('topic', 'contenido de calidad con IA')
    ->withVariable('audience', 'editores tecnicos')
    ->generate();
```

The prompt registry handles fallback. For example, `es_AR` can fall back to `es`, then `en`.

---

Previous: [Workflows](workflows.md)  
Next: [Tracking and Pricing](tracking-pricing.md)
