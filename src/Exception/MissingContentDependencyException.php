<?php

declare(strict_types=1);

namespace Token27\NexusAI\Content\Exception;

final class MissingContentDependencyException extends ContentException
{
    public static function forService(string $service): self
    {
        return new self(sprintf('Missing required content service "%s".', $service));
    }
}
