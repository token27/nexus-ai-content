<?php

declare(strict_types=1);

namespace Token27\NexusAI\Content\Contract;

use Token27\NexusAI\Content\ValueObject\ContentResult;
use Token27\NexusAI\Contract\DriverInterface;
use Token27\NexusAI\Driver\DriverRegistry;
use Token27\NexusAI\Pricing\Contract\PricingEngineInterface;
use Token27\NexusAI\Prompts\Contract\PromptRegistryInterface;
use Token27\NexusAI\Tracking\Contract\ExecutionStoreInterface;
use Token27\NexusAI\Tracking\Contract\TrackingBuilderInterface;
use Token27\NexusAI\Workflows\ValueObject\WorkflowResult;

interface ContentBuilderInterface
{
    public function withContentType(string $type, string $workflowName = 'default'): static;

    /** @param array<string, mixed> $variables */
    public function withVariables(array $variables): static;

    public function withVariable(string $key, mixed $value): static;

    public function withLanguage(string $language): static;

    public function withRunId(string $runId): static;

    public function withDriver(DriverInterface $driver): static;

    public function withDriverRegistry(DriverRegistry $registry): static;

    public function withPromptRegistry(PromptRegistryInterface $registry): static;

    public function withTracking(TrackingBuilderInterface|ExecutionStoreInterface $tracking): static;

    public function withPricing(PricingEngineInterface $pricing): static;

    public function withBudget(float $maxCostUsd): static;

    public function generate(): ContentResult;

    public function generatePhased(): WorkflowResult;
}
