<?php

declare(strict_types=1);

namespace Token27\NexusAI\Content\Contract;

use Token27\NexusAI\Workflows\Contract\WorkflowInterface;

interface ContentRegistryInterface
{
    public function register(string $contentType, WorkflowInterface $workflow, string $name = 'default'): void;

    public function resolve(string $contentType, string $name = 'default'): WorkflowInterface;

    public function has(string $contentType, string $name = 'default'): bool;

    public function hasContentType(string $contentType): bool;

    /** @return list<string> */
    public function listContentTypes(): array;

    /** @return list<string> */
    public function listWorkflowNames(string $contentType): array;
}
