<?php

declare(strict_types=1);

namespace Token27\NexusAI\Content;

use Token27\NexusAI\Content\Contract\ContentRegistryInterface;
use Token27\NexusAI\Content\Exception\ContentWorkflowNotFoundException;
use Token27\NexusAI\Workflows\Contract\WorkflowInterface;

final class ContentRegistry implements ContentRegistryInterface
{
    /** @var array<string, array<string, WorkflowInterface>> */
    private array $workflows = [];

    public function register(string $contentType, WorkflowInterface $workflow, string $name = 'default'): void
    {
        $contentType = $this->normalize($contentType);
        $name = $this->normalize($name);

        $this->workflows[$contentType][$name] = $workflow;
    }

    public function resolve(string $contentType, string $name = 'default'): WorkflowInterface
    {
        $contentType = $this->normalize($contentType);
        $name = $this->normalize($name);

        return $this->workflows[$contentType][$name]
            ?? throw ContentWorkflowNotFoundException::forContentType($contentType, $name);
    }

    public function has(string $contentType, string $name = 'default'): bool
    {
        return isset($this->workflows[$this->normalize($contentType)][$this->normalize($name)]);
    }

    public function hasContentType(string $contentType): bool
    {
        return isset($this->workflows[$this->normalize($contentType)]);
    }

    public function listContentTypes(): array
    {
        $types = array_keys($this->workflows);
        sort($types);

        return $types;
    }

    public function listWorkflowNames(string $contentType): array
    {
        $names = array_keys($this->workflows[$this->normalize($contentType)] ?? []);
        sort($names);

        return $names;
    }

    private function normalize(string $value): string
    {
        return strtolower(trim($value));
    }
}
