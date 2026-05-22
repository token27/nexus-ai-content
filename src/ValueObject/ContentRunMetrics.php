<?php

declare(strict_types=1);

namespace Token27\NexusAI\Content\ValueObject;

final readonly class ContentRunMetrics
{
    public function __construct(
        public float $costUsd = 0.0,
        public int $totalTokens = 0,
    ) {
    }

    public function add(float $costUsd, int $tokens): self
    {
        return new self(
            costUsd: $this->costUsd + $costUsd,
            totalTokens: $this->totalTokens + $tokens,
        );
    }
}
