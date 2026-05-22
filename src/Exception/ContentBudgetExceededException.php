<?php

declare(strict_types=1);

namespace Token27\NexusAI\Content\Exception;

final class ContentBudgetExceededException extends ContentException
{
    public static function forEstimate(float $estimatedCostUsd, float $maxCostUsd): self
    {
        return new self(sprintf(
            'Estimated content generation cost %.8f USD exceeds configured budget %.8f USD.',
            $estimatedCostUsd,
            $maxCostUsd,
        ));
    }
}
