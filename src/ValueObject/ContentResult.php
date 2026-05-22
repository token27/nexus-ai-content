<?php

declare(strict_types=1);

namespace Token27\NexusAI\Content\ValueObject;

use Token27\NexusAI\Workflows\ValueObject\WorkflowResult;

final readonly class ContentResult
{
    /**
     * @param array<string, mixed> $output
     */
    public function __construct(
        public string $runId,
        public string $contentType,
        public string $workflowName,
        public string $text,
        public array $output,
        public float $costUsd,
        public int $totalTokens,
        public float $elapsedMs,
        public WorkflowResult $workflowResult,
    ) {
    }
}
