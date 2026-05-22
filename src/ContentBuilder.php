<?php

declare(strict_types=1);

namespace Token27\NexusAI\Content;

use Token27\NexusAI\Content\Contract\ContentBuilderInterface;
use Token27\NexusAI\Content\Contract\ContentInterface;
use Token27\NexusAI\Content\Contract\ContentRegistryInterface;
use Token27\NexusAI\Content\Exception\MissingContentDependencyException;
use Token27\NexusAI\Content\ValueObject\ContentResult;
use Token27\NexusAI\Content\ValueObject\ContentRunMetrics;
use Token27\NexusAI\Contract\DriverInterface;
use Token27\NexusAI\Driver\DriverRegistry;
use Token27\NexusAI\Pricing\Contract\PricingEngineInterface;
use Token27\NexusAI\Prompts\Contract\PromptRegistryInterface;
use Token27\NexusAI\Tracking\Contract\ExecutionStoreInterface;
use Token27\NexusAI\Tracking\Contract\TrackingBuilderInterface;
use Token27\NexusAI\Tracking\Engine\TrackingEngine;
use Token27\NexusAI\Workflows\Engine\WorkflowContext;
use Token27\NexusAI\Workflows\Runner\WorkflowRunner;
use Token27\NexusAI\Workflows\ValueObject\WorkflowResult;

final class ContentBuilder implements ContentBuilderInterface
{
    private string $contentType = 'content';

    private string $workflowName = 'default';

    /** @var array<string, mixed> */
    private array $variables = [];

    private ?string $language = null;

    private ?string $runId = null;

    private ?DriverInterface $driver = null;

    private ?DriverRegistry $driverRegistry = null;

    private ?PromptRegistryInterface $promptRegistry = null;

    private TrackingBuilderInterface|ExecutionStoreInterface|null $tracking = null;

    private ?PricingEngineInterface $pricing = null;

    private ?float $maxCostUsd = null;

    public function __construct(
        private readonly string $provider,
        private readonly string $model,
        private readonly ContentRegistryInterface $registry,
    ) {
    }

    public function withContentType(string $type, string $workflowName = 'default'): static
    {
        $this->contentType = $this->normalize($type);
        $this->workflowName = $this->normalize($workflowName);

        return $this;
    }

    public function withVariables(array $variables): static
    {
        $this->variables = array_merge($this->variables, $variables);

        return $this;
    }

    public function withVariable(string $key, mixed $value): static
    {
        $this->variables[$key] = $value;

        return $this;
    }

    public function withLanguage(string $language): static
    {
        $this->language = $language;

        return $this;
    }

    public function withRunId(string $runId): static
    {
        $this->runId = $runId;

        return $this;
    }

    public function withDriver(DriverInterface $driver): static
    {
        $this->driver = $driver;

        return $this;
    }

    public function withDriverRegistry(DriverRegistry $registry): static
    {
        $this->driverRegistry = $registry;

        return $this;
    }

    public function withPromptRegistry(PromptRegistryInterface $registry): static
    {
        $this->promptRegistry = $registry;

        return $this;
    }

    public function withTracking(TrackingBuilderInterface|ExecutionStoreInterface $tracking): static
    {
        $this->tracking = $tracking;

        return $this;
    }

    public function withPricing(PricingEngineInterface $pricing): static
    {
        $this->pricing = $pricing;

        return $this;
    }

    public function withBudget(float $maxCostUsd): static
    {
        $this->maxCostUsd = $maxCostUsd;

        return $this;
    }

    public function generate(): ContentResult
    {
        $workflowResult = $this->runWorkflow();
        $metrics = $this->extractMetrics($workflowResult);

        return new ContentResult(
            runId: (string) $workflowResult->output['_content_run_id'],
            contentType: $this->contentType,
            workflowName: $this->workflowName,
            text: $this->extractText($workflowResult->output),
            output: $workflowResult->output,
            costUsd: $metrics->costUsd,
            totalTokens: $metrics->totalTokens,
            elapsedMs: $workflowResult->elapsedMs,
            workflowResult: $workflowResult,
        );
    }

    public function generatePhased(): WorkflowResult
    {
        return $this->runWorkflow();
    }

    private function runWorkflow(): WorkflowResult
    {
        $workflow = $this->registry->resolve($this->contentType, $this->workflowName);
        $runner = new WorkflowRunner($this->driverRegistry ?? new DriverRegistry());

        return $runner->run($workflow, $this->buildContext());
    }

    private function buildContext(): WorkflowContext
    {
        $runId = $this->runId ?? $this->generateRunId();
        $tracking = $this->normalizeTracking();

        return WorkflowContext::from($this->variables)
            ->with('_content_run_id', $runId)
            ->with('_content_type', $this->contentType)
            ->with('_content_workflow', $this->workflowName)
            ->with('_content_provider', $this->provider)
            ->with('_content_model', $this->model)
            ->with('_content_language', $this->language)
            ->with('_driver', $this->driver)
            ->with('_content_metrics', new ContentRunMetrics())
            ->with('_content_max_cost_usd', $this->maxCostUsd)
            ->with('_prompt_registry', $this->promptRegistry)
            ->with('_tracking', $tracking)
            ->with('_execution_store', $tracking->getStore())
            ->with('_pricing_engine', $this->pricing);
    }

    private function normalizeTracking(): TrackingBuilderInterface
    {
        if ($this->tracking instanceof TrackingBuilderInterface) {
            return $this->tracking;
        }

        if ($this->tracking instanceof ExecutionStoreInterface) {
            return TrackingEngine::withStore($this->tracking);
        }

        return TrackingEngine::null();
    }

    /**
     * @param array<string, mixed> $output
     */
    private function extractText(array $output): string
    {
        $candidate = $output['content'] ?? $output['text'] ?? $output['output'] ?? null;

        if ($candidate instanceof ContentInterface) {
            return $candidate->toText();
        }

        if (is_string($candidate)) {
            return $candidate;
        }

        if (is_scalar($candidate)) {
            return (string) $candidate;
        }

        return '';
    }

    private function extractMetrics(WorkflowResult $result): ContentRunMetrics
    {
        $metrics = $result->output['_content_metrics'] ?? null;

        if ($metrics instanceof ContentRunMetrics) {
            return $metrics;
        }

        return new ContentRunMetrics();
    }

    private function generateRunId(): string
    {
        try {
            return 'content_' . bin2hex(random_bytes(8));
        } catch (\Throwable) {
            return uniqid('content_', true);
        }
    }

    private function normalize(string $value): string
    {
        $value = strtolower(trim($value));

        return $value !== '' ? $value : throw MissingContentDependencyException::forService('non-empty identifier');
    }
}
