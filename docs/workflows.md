# Workflows

`nexus-ai-content` consumes `nexus-ai-workflows`.

The content registry maps:

```text
content type + workflow name -> WorkflowInterface
```

Examples:

```text
article/default
article/outline
article/phased
social-post/thread
newsletter/default
```

## Registering Workflows

```php
$engine->getRegistry()->register('article', $workflow);
$engine->getRegistry()->register('article', $outlineWorkflow, 'outline');
```

Then select a workflow:

```php
$result = $engine
    ->for('openai', 'gpt-4o-mini')
    ->withContentType('article', 'outline')
    ->withVariable('topic', 'workflow orchestration')
    ->generate();
```

## Workflow Context Keys

`ContentBuilder` injects these keys into the workflow context:

| Key | Meaning |
|-----|---------|
| `_content_run_id` | Stable id for tracking |
| `_content_type` | Selected content type |
| `_content_workflow` | Selected workflow name |
| `_content_provider` | Provider passed to `for()` |
| `_content_model` | Model passed to `for()` |
| `_content_language` | Language passed to `withLanguage()` |
| `_content_metrics` | Accumulated cost/tokens |
| `_content_max_cost_usd` | Optional budget limit |
| `_prompt_registry` | Prompt registry |
| `_tracking` | Tracking builder |
| `_execution_store` | Underlying tracking store |
| `_pricing_engine` | Pricing engine |

`WorkflowRunner` also injects `_driver_registry` and `_runner`.

## Phased Workflows

Use `generatePhased()` when you need raw workflow state, steps, transitions, and suspension data.

```php
$workflowResult = $engine
    ->for('openai', 'gpt-4o-mini')
    ->withContentType('article', 'phased')
    ->withVariable('topic', 'CakePHP task queues')
    ->generatePhased();
```

This is the right shape for CakePHP tasks where one task creates structure, another fills sections, and later tasks resume the workflow.

---

Previous: [Fluent API](fluent-api.md)  
Next: [Prompts](prompts.md)
