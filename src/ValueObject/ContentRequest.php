<?php

declare(strict_types=1);

namespace Token27\NexusAI\Content\ValueObject;

final readonly class ContentRequest
{
    /**
     * @param array<string, mixed> $variables
     */
    public function __construct(
        public string $provider,
        public string $model,
        public string $contentType,
        public string $workflowName = 'default',
        public array $variables = [],
        public ?string $language = null,
        public ?string $runId = null,
        public ?float $maxCostUsd = null,
    ) {
    }
}
