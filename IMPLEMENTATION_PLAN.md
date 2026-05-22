# NexusAI Content Implementation Plan

## Current Assessment

The ecosystem direction is sound, but the next move should be the generic `nexus-ai-content` package, not `nexus-ai-content-articles`.

`nexus-ai-content-articles` should depend on `nexus-ai-content`. It should not directly coordinate `nexus-ai`, prompts, tracking, pricing, and workflows by itself.

## Why `nexus-ai-content` Comes First

Articles and social media are both content domains. They share orchestration needs but differ in domain rules.

The shared layer should answer:

- Which content type is requested?
- Which workflow handles that content type?
- Which provider/model should be used by default?
- Which prompt registry should nodes use?
- Which tracking store records the run?
- Which pricing engine calculates cost?
- How does a consumer run complete vs phased workflows?

The article layer should answer:

- What is an article?
- What article workflows exist?
- Which prompts produce research, outline, sections, SEO metadata, and final assembly?
- How are article sections represented?
- Should this article include generated images?

## Dependency Direction

Recommended dependency graph:

```text
nexus-ai
  <- nexus-ai-workflows

nexus-ai-prompts
nexus-ai-tracking
nexus-ai-pricing
nexus-ai-tokenizer optional via nexus-ai-pricing for pre-request estimation

nexus-ai-content
  -> nexus-ai
  -> nexus-ai-workflows
  -> nexus-ai-prompts
  -> nexus-ai-tracking
  -> nexus-ai-pricing
  -. suggests nexus-ai-tokenizer for tokenizer-aware pricing estimation

nexus-ai-images
  -> nexus-ai
  -> nexus-ai-prompts
  -> nexus-ai-tracking
  -> nexus-ai-workflows only if ImageNode is part of src

nexus-ai-content-articles
  -> nexus-ai-content
  -> nexus-ai-images as optional/suggested workflow capability
  -> nexus-ai-content-formatters as optional output capability

nexus-ai-content-social-media
  -> nexus-ai-content
  -> nexus-ai-content-formatters optional
  -> nexus-ai-images optional
```

## Important Findings Before Implementation

### 1. `nexus-ai-workflows` is close, but phased resume needs refactor

The library has the right shape: `WorkflowBuilder`, `WorkflowRunner`, `WorkflowRegistry`, `ActionNode`, `AINode`, `LoopNode`, `ParallelNode`, `WaitNode`, middleware, events, and stores.

The issue is `WorkflowRunner::resume()`: today it merges resume data and calls `run()`, which starts from the workflow entry node again. For expensive content workflows, this can repeat research and generation steps.

Before relying on phased article generation, add resume support that continues after the suspended `WaitNode`.

Recommended fix:

- persist suspended node name in `WorkflowResult` or output metadata
- allow runner execution from a specific node
- on resume, start from the suspended node, consume the signal, then transition to the next node
- add tests proving previous nodes are not re-executed

### 2. `nexus-ai-prompts` is ready for this role

It already has:

- `PromptEngine`
- `PromptBuilder`
- `PromptRegistry`
- version resolution with `latest`
- language fallback
- multi-source prompt loading
- rendered prompt output formats

`nexus-ai-content` should consume it. It should not own prompt versioning.

### 3. `nexus-ai-tracking` is ready

`nexus-ai-tracking` follows the ecosystem pattern with `TrackingEngine`, `TrackingRegistry`, and fluent builders. Its tests passed locally: 76 tests, 257 assertions.

`nexus-ai-content` should integrate tracking from day one, but as optional infrastructure:

- no tracking store means generation still works
- tracking store present means every LLM step records prompt, model, tokens, cost, latency, content type, workflow, and run id

### 4. `nexus-ai-images` is useful but should stay optional for content

Images should not be required by `nexus-ai-content`, because many content workflows are text-only.

For articles, images become a domain workflow feature:

- hero image
- inline illustration
- social preview image
- WordPress featured image

Finding: `nexus-ai-images` has an `ImageNode` in `src` that imports workflow classes, but `nexus-ai-workflows` is only in `suggest` and `require-dev`. If `ImageNode` stays in `src`, workflows should be a real dependency, or the node should move to a bridge package/optional namespace with clear installation rules.

### 5. `nexus-ai-content-formatters` belongs at the output boundary

Formatters are useful, but generation should return structured content first. Formatting should happen after generation or in final workflow nodes when explicitly requested.

For articles:

- workflow outputs `ArticleContent`
- publisher/integration formats it as Markdown, HTML, Gutenberg, AMP, or plain text

### 6. `nexus-ai-tokenizer` should be optional but visible

`nexus-ai-content` should not require `nexus-ai-tokenizer` directly. Token counting and model price calculation belong in `nexus-ai-pricing`.

However, content workflows often need pre-request estimates before calling the provider:

- reject a generation before it exceeds a budget
- choose a cheaper model for long prompts
- split sections before a context window is exceeded
- support custom providers with custom token counting rules

For that reason, `nexus-ai-content` should add `token27/nexus-ai-tokenizer` as `suggest` and expose docs/API hooks that accept a tokenizer-aware pricing engine. The consumer should discover from `nexus-ai-content` that tokenizer support exists, while the implementation remains delegated to `nexus-ai-pricing`.

## MVP Scope For `nexus-ai-content`

### Phase 1: Contracts and Registry

- `Contract/ContentEngineInterface.php`
- `Contract/ContentBuilderInterface.php`
- `Contract/ContentRegistryInterface.php`
- `Contract/ExtensionInterface.php`
- `Contract/ContentInterface.php`
- `ContentRegistry`
- typed exceptions

Acceptance:

- register workflow for `article/default`
- resolve workflow by content type and name
- reject missing content types clearly

### Phase 2: Fluent Engine and Builder

- `ContentEngine::create()`
- `ContentEngine::using(provider, model)`
- `ContentBuilder`
- `ContentRequest`
- `ContentResult`

Acceptance:

- run an already registered workflow through `WorkflowRunner`
- pass variables into `WorkflowContext`
- expose output, elapsed time, run id, content type, tokens, and cost

### Phase 3: Dependency Injection Into Workflow Context

Inject:

- `_prompt_registry`
- `_tracking`
- `_pricing_engine`
- `_token_budget` optional max budget configuration
- `_token_estimator` optional estimator service only if we decide not to hide it behind pricing
- `_content_run_id`
- `_content_type`
- `_content_provider`
- `_content_model`

Keep `_driver_registry` and `_runner` owned by `WorkflowRunner`.

Acceptance:

- workflow nodes can read shared services from context
- no domain package needs to manually wire those keys

### Phase 4: `ContentAINode`

Implement a node that:

- extends `AbstractNode`
- resolves prompt by identifier, version, language, and optional source
- renders with context variables
- calls `nexus-ai` text or structured output
- stores result in configured output key
- records tracking event if tracking is configured
- calculates pricing if pricing is configured and usage data exists
- optionally estimates prompt tokens before request when the configured pricing engine supports estimation

Acceptance:

- works with prompt registry
- supports structured output DTO
- records model, provider, content type, workflow, prompt version, tokens, cost, and latency
- can reject/short-circuit when pre-request budget estimation exceeds configured limits

### Phase 5: Extension Bootstrap

Allow domain packages to register:

- workflows
- prompt directories
- optional default content types

Acceptance:

- an `ArticleExtension` can register its workflows without modifying `nexus-ai-content`

## Then Create `nexus-ai-content-articles`

Only after the MVP above:

- `ArticleExtension`
- `ArticleRequest`
- `ArticleContent`
- `ArticleSkeleton`
- `ArticleSectionPlan`
- `ArticleSeoWorkflow`
- `ArticleQuickWorkflow`
- `ArticlePhasedWorkflow`
- bundled prompts under `resources/prompts/article/...`
- optional image workflow nodes
- optional formatter examples

## Immediate Technical Backlog

1. `nexus-ai-workflows` resume semantics are now fixed and validated.
2. Add tokenizer support as optional/suggested capability in `nexus-ai-content`.
3. Fix `nexus-ai-images` test configuration: it references a missing `tests/Integration` suite.
4. Fix `nexus-ai-images`/`nexus-ai` pricing class mismatch seen while running unit tests.
5. Decide whether `ImageNode` makes `nexus-ai-workflows` a hard dependency of `nexus-ai-images`.
6. Implement `nexus-ai-content` MVP.
7. Create `nexus-ai-content-articles`.

## Final Recommendation

Build the ecosystem in this order:

1. Implement `nexus-ai-content`.
2. Implement `nexus-ai-content-articles`.
3. Add optional article image workflows.
4. Implement `nexus-ai-content-social-media`.

This keeps the architecture flexible without turning the first article package into a giant all-purpose orchestrator.
