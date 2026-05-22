# Architecture Decision: Create `nexus-ai-content` Before Article Domains

## Status

Accepted.

## Context

The ecosystem goal is high-quality AI-assisted content generation across multiple domains: articles, social posts, image prompts, newsletters, scripts, and future formats.

The current uncertainty is whether to create:

- `nexus-ai-content`
- `nexus-ai-content-articles`
- or skip the generic package and let domain packages consume `nexus-ai`, `nexus-ai-prompts`, and `nexus-ai-workflows` directly

## Decision

Create `nexus-ai-content` first as the generic orchestration package.

Create `nexus-ai-content-articles` after the generic package has a working MVP.

## Rationale

Content generation has a shared workflow lifecycle independent of the domain:

- choose content type
- resolve workflow
- inject shared registries
- execute via workflow runner
- track events
- calculate costs
- return a consistent result

Article generation is only one domain. It should not define the shared orchestration conventions for every future content type.

## Consequences

Positive:

- articles, social media, and future domains share one fluent API
- prompt versioning stays in `nexus-ai-prompts`
- execution telemetry stays in `nexus-ai-tracking`
- content packages stay smaller and easier to test
- domain packages can focus on quality logic instead of infrastructure

Tradeoffs:

- one extra package must be implemented before articles
- `nexus-ai-workflows` resume semantics should be corrected first for reliable phased generation
- the first MVP must be disciplined and avoid becoming a domain package

## Non-Goals

`nexus-ai-content` will not:

- generate SEO articles by itself
- define social platform constraints
- own prompt file schemas
- require image generation
- require output formatters

## Rule Of Thumb

If a feature is useful for articles and social media, it probably belongs in `nexus-ai-content`.

If a feature only makes sense for articles, it belongs in `nexus-ai-content-articles`.

If a feature only changes presentation, it belongs in `nexus-ai-content-formatters` or in the final application layer.

---

Previous: [Tracking and Pricing](tracking-pricing.md)  
Next: [Extending](extending.md)
