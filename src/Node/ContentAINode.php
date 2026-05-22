<?php

declare(strict_types=1);

namespace Token27\NexusAI\Content\Node;

use Token27\NexusAI\Content\Exception\ContentBudgetExceededException;
use Token27\NexusAI\Content\Exception\MissingContentDependencyException;
use Token27\NexusAI\Content\ValueObject\ContentRunMetrics;
use Token27\NexusAI\Contract\DriverInterface;
use Token27\NexusAI\Driver\DriverRegistry;
use Token27\NexusAI\Message\UserMessage;
use Token27\NexusAI\NexusAI;
use Token27\NexusAI\Pricing\Contract\PricingEngineInterface;
use Token27\NexusAI\Pricing\Contract\PricingResultInterface;
use Token27\NexusAI\Pricing\Exception\EstimationNotAvailableException;
use Token27\NexusAI\Prompts\Contract\PromptRegistryInterface;
use Token27\NexusAI\Prompts\Engine\MustacheAdapter;
use Token27\NexusAI\Prompts\ValueObject\PromptMetadata;
use Token27\NexusAI\Prompts\ValueObject\RenderedPrompt;
use Token27\NexusAI\Request\StructuredRequest;
use Token27\NexusAI\Request\TextRequest;
use Token27\NexusAI\Response\StructuredResponse;
use Token27\NexusAI\Response\TextResponse;
use Token27\NexusAI\Tracking\Contract\TrackingBuilderInterface;
use Token27\NexusAI\Tracking\Enum\ExecutionEventType;
use Token27\NexusAI\Workflows\Contract\WorkflowContextInterface;
use Token27\NexusAI\Workflows\Enum\NodeType;
use Token27\NexusAI\Workflows\Node\AbstractNode;
use Token27\NexusAI\Workflows\ValueObject\StepResult;

final class ContentAINode extends AbstractNode
{
    public function __construct(
        private readonly string $promptIdentifier,
        private readonly string $outputKey = 'output',
        private readonly ?string $provider = null,
        private readonly ?string $model = null,
        private readonly ?string $promptVersion = null,
        private readonly ?string $language = null,
        private readonly ?string $source = null,
        private readonly ?string $outputClass = null,
        private readonly ?float $temperature = null,
        private readonly ?int $maxTokens = null,
        ?string $description = null,
        private readonly ?string $rawPrompt = null,
        private readonly ?string $rawSystemPrompt = null,
        private readonly string $rawVersion = '0.0.0',
        private readonly string $rawSource = 'runtime',
    ) {
        parent::__construct($description);
    }

    public static function rawPrompt(
        string $prompt,
        ?string $systemPrompt = null,
        string $identifier = 'runtime/raw',
        string $outputKey = 'output',
        ?string $provider = null,
        ?string $model = null,
        ?string $language = null,
        string $source = 'runtime',
        string $version = '0.0.0',
        ?string $outputClass = null,
        ?float $temperature = null,
        ?int $maxTokens = null,
        ?string $description = null,
    ): self {
        return new self(
            promptIdentifier: $identifier,
            outputKey: $outputKey,
            provider: $provider,
            model: $model,
            language: $language,
            outputClass: $outputClass,
            temperature: $temperature,
            maxTokens: $maxTokens,
            description: $description,
            rawPrompt: $prompt,
            rawSystemPrompt: $systemPrompt,
            rawVersion: $version,
            rawSource: $source,
        );
    }

    public function execute(WorkflowContextInterface $context): StepResult
    {
        $startedAt = microtime(true);
        $provider = $this->resolveProvider($context);
        $model = $this->resolveModel($context);
        $runId = $this->resolveRunId($context);
        $contentType = (string) $context->get('_content_type', 'content');
        $workflowName = (string) $context->get('_content_workflow', 'default');
        $language = $this->language ?? $this->nullableString($context->get('_content_language'));

        try {
            $promptIdentifier = $this->promptIdentifier;
            $promptVersion = $this->promptVersion ?? 'latest';
            $promptSource = $this->source ?? 'unknown';
            $rendered = $this->rawPrompt !== null
                ? $this->renderRawPrompt($context, $language)
                : $this->renderVersionedPrompt($context, $language, $promptVersion, $promptSource);
            $systemPrompt = $rendered->getSystemMessage();
            $promptText = $rendered->getUserMessage() ?? $rendered->asPlainString();
            $promptVersion = $rendered->version;
            $promptSource = $rendered->source;

            $this->enforceBudget($context, $promptText, $model);

            $response = $this->callAI($context, $provider, $model, $promptText, $systemPrompt);
            $pricingResult = $this->calculatePricing($context, $response, $model);
            $elapsedMs = (microtime(true) - $startedAt) * 1000;
            $tokens = $response->getUsage()->totalTokens();
            $costUsd = $pricingResult?->totalCostUsd() ?? 0.0;

            $newContext = $context
                ->with($this->outputKey, $response instanceof StructuredResponse ? $response->object : $response->text)
                ->with($this->outputKey . '_response', $response)
                ->with('_content_metrics', $this->updateMetrics($context, $costUsd, $tokens));

            $this->trackSuccess(
                context: $context,
                runId: $runId,
                provider: $provider,
                model: $model,
                contentType: $contentType,
                workflowName: $workflowName,
                promptIdentifier: $promptIdentifier,
                promptVersion: $promptVersion,
                promptSource: $promptSource,
                language: $rendered->language,
                costUsd: $costUsd,
                tokens: $tokens,
                elapsedMs: $elapsedMs,
            );

            return StepResult::success(
                context: $newContext,
                elapsedMs: $elapsedMs,
                metadata: [
                    'provider' => $provider,
                    'model' => $model,
                    'promptIdentifier' => $promptIdentifier,
                    'promptVersion' => $promptVersion,
                    'promptSource' => $promptSource,
                    'outputKey' => $this->outputKey,
                    'tokens' => $tokens,
                    'cost_usd' => $costUsd,
                ],
            );
        } catch (\Throwable $e) {
            $elapsedMs = (microtime(true) - $startedAt) * 1000;

            $this->trackFailure(
                context: $context,
                runId: $runId,
                provider: $provider,
                model: $model,
                contentType: $contentType,
                workflowName: $workflowName,
                elapsedMs: $elapsedMs,
                error: $e->getMessage(),
            );

            return StepResult::failure(
                context: $context,
                error: $e->getMessage(),
                elapsedMs: $elapsedMs,
                metadata: [
                    'provider' => $provider,
                    'model' => $model,
                    'promptIdentifier' => $this->promptIdentifier,
                    'outputKey' => $this->outputKey,
                ],
            );
        }
    }

    public function getType(): NodeType
    {
        return NodeType::AI;
    }

    private function renderVersionedPrompt(
        WorkflowContextInterface $context,
        ?string $language,
        string &$promptVersion,
        string &$promptSource,
    ): RenderedPrompt {
        $registry = $this->resolvePromptRegistry($context);
        $prompt = $registry->resolve($this->promptIdentifier, $this->promptVersion, $language, $this->source);

        $promptVersion = $prompt->getVersion();
        $promptSource = $prompt->getSource();

        return $prompt->render($context->all());
    }

    private function renderRawPrompt(WorkflowContextInterface $context, ?string $language): RenderedPrompt
    {
        $engine = new MustacheAdapter();
        $blocks = [];

        if ($this->rawSystemPrompt !== null && $this->rawSystemPrompt !== '') {
            $blocks[] = [
                'role' => 'system',
                'content' => $engine->render($this->rawSystemPrompt, $context->all()),
            ];
        }

        $blocks[] = [
            'role' => 'user',
            'content' => $engine->render((string) $this->rawPrompt, $context->all()),
        ];

        $resolvedLanguage = $language ?? 'en';

        return new RenderedPrompt(
            blocks: $blocks,
            metadata: new PromptMetadata(
                version: $this->rawVersion,
                promptType: 'raw',
                language: $resolvedLanguage,
            ),
            language: $resolvedLanguage,
            version: $this->rawVersion,
            source: $this->rawSource,
        );
    }

    private function callDriver(
        DriverInterface $driver,
        string $provider,
        string $model,
        string $promptText,
        ?string $systemPrompt,
    ): TextResponse|StructuredResponse {
        if ($this->outputClass !== null && class_exists($this->outputClass)) {
            return $driver->structured(new StructuredRequest(
                provider: $provider,
                model: $model,
                outputClass: $this->outputClass,
                messages: [new UserMessage($promptText)],
                systemPrompt: $systemPrompt,
                temperature: $this->temperature,
                maxTokens: $this->maxTokens,
            ));
        }

        return $driver->text(new TextRequest(
            provider: $provider,
            model: $model,
            messages: [new UserMessage($promptText)],
            systemPrompt: $systemPrompt,
            temperature: $this->temperature,
            maxTokens: $this->maxTokens,
        ));
    }

    private function callAI(
        WorkflowContextInterface $context,
        string $provider,
        string $model,
        string $promptText,
        ?string $systemPrompt,
    ): TextResponse|StructuredResponse {
        $driver = $this->resolveDriver($context, $provider);

        if ($driver instanceof DriverInterface) {
            return $this->callDriver($driver, $provider, $model, $promptText, $systemPrompt);
        }

        return $this->callNexusAI($provider, $model, $promptText, $systemPrompt);
    }

    private function callNexusAI(
        string $provider,
        string $model,
        string $promptText,
        ?string $systemPrompt,
    ): TextResponse|StructuredResponse {
        $request = NexusAI::using($provider, $model)
            ->withPrompt($promptText);

        if ($systemPrompt !== null && $systemPrompt !== '') {
            $request = $request->withSystemPrompt($systemPrompt);
        }

        if ($this->temperature !== null) {
            $request = $request->withTemperature($this->temperature);
        }

        if ($this->maxTokens !== null) {
            $request = $request->withMaxTokens($this->maxTokens);
        }

        if ($this->outputClass !== null && class_exists($this->outputClass)) {
            return $request->asStructured($this->outputClass);
        }

        return $request->asText();
    }

    private function enforceBudget(WorkflowContextInterface $context, string $promptText, string $model): void
    {
        $maxCostUsd = $context->get('_content_max_cost_usd');
        $pricing = $this->resolvePricing($context);

        if (!is_float($maxCostUsd) && !is_int($maxCostUsd)) {
            return;
        }

        if ($pricing === null) {
            return;
        }

        try {
            $estimate = $pricing->estimate($promptText, $model);
        } catch (EstimationNotAvailableException) {
            return;
        }

        if ($estimate->totalCostUsd() > (float) $maxCostUsd) {
            throw ContentBudgetExceededException::forEstimate($estimate->totalCostUsd(), (float) $maxCostUsd);
        }
    }

    private function calculatePricing(
        WorkflowContextInterface $context,
        TextResponse|StructuredResponse $response,
        string $model,
    ): ?PricingResultInterface {
        if ($response instanceof TextResponse && $response->pricingResult !== null) {
            return $response->pricingResult;
        }

        $pricing = $this->resolvePricing($context);

        if ($pricing === null) {
            return null;
        }

        return $pricing->calculateFromUsage($response->getUsage(), $model);
    }

    private function updateMetrics(WorkflowContextInterface $context, float $costUsd, int $tokens): ContentRunMetrics
    {
        $metrics = $context->get('_content_metrics');

        if (!$metrics instanceof ContentRunMetrics) {
            $metrics = new ContentRunMetrics();
        }

        return $metrics->add($costUsd, $tokens);
    }

    private function trackSuccess(
        WorkflowContextInterface $context,
        string $runId,
        string $provider,
        string $model,
        string $contentType,
        string $workflowName,
        string $promptIdentifier,
        string $promptVersion,
        string $promptSource,
        string $language,
        float $costUsd,
        int $tokens,
        float $elapsedMs,
    ): void {
        $tracking = $this->resolveTracking($context);

        if ($tracking === null) {
            return;
        }

        $tracking
            ->track(ExecutionEventType::PROMPT_EXECUTED, $runId)
            ->withProvider($provider)
            ->withModel($model)
            ->withContentType($contentType)
            ->withChainName($workflowName)
            ->withLanguage($language)
            ->withCostUsd($costUsd)
            ->withTokens($tokens)
            ->withLatencyMs($elapsedMs)
            ->with('type', 'content.llm_call')
            ->with('prompt', [
                'identifier' => $promptIdentifier,
                'version' => $promptVersion,
                'source' => $promptSource,
            ])
            ->record();
    }

    private function trackFailure(
        WorkflowContextInterface $context,
        string $runId,
        string $provider,
        string $model,
        string $contentType,
        string $workflowName,
        float $elapsedMs,
        string $error,
    ): void {
        $tracking = $this->resolveTracking($context);

        if ($tracking === null) {
            return;
        }

        $tracking
            ->track(ExecutionEventType::STEP_FAILED, $runId)
            ->withProvider($provider)
            ->withModel($model)
            ->withContentType($contentType)
            ->withChainName($workflowName)
            ->withLatencyMs($elapsedMs)
            ->with('type', 'content.llm_call')
            ->with('prompt', ['identifier' => $this->promptIdentifier])
            ->with('error', $error)
            ->record();
    }

    private function resolvePromptRegistry(WorkflowContextInterface $context): PromptRegistryInterface
    {
        $registry = $context->get('_prompt_registry');

        if ($registry instanceof PromptRegistryInterface) {
            return $registry;
        }

        throw MissingContentDependencyException::forService('prompt_registry');
    }

    private function resolveDriver(WorkflowContextInterface $context, string $provider): ?DriverInterface
    {
        $driver = $context->get('_driver');
        if ($driver instanceof DriverInterface) {
            return $driver;
        }

        $registry = $context->get('_driver_registry');
        if ($registry instanceof DriverRegistry) {
            try {
                return $registry->resolve($provider);
            } catch (\InvalidArgumentException | \RuntimeException) {
                return null;
            }
        }

        return null;
    }

    private function resolveTracking(WorkflowContextInterface $context): ?TrackingBuilderInterface
    {
        $tracking = $context->get('_tracking');

        return $tracking instanceof TrackingBuilderInterface ? $tracking : null;
    }

    private function resolvePricing(WorkflowContextInterface $context): ?PricingEngineInterface
    {
        $pricing = $context->get('_pricing_engine');

        return $pricing instanceof PricingEngineInterface ? $pricing : null;
    }

    private function resolveProvider(WorkflowContextInterface $context): string
    {
        return $this->provider ?? (string) $context->get('_content_provider', 'openai');
    }

    private function resolveModel(WorkflowContextInterface $context): string
    {
        return $this->model ?? (string) $context->get('_content_model', 'gpt-4o-mini');
    }

    private function resolveRunId(WorkflowContextInterface $context): string
    {
        return (string) $context->get('_content_run_id', uniqid('content_', true));
    }

    private function nullableString(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }
}
