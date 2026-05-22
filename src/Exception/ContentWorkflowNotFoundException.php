<?php

declare(strict_types=1);

namespace Token27\NexusAI\Content\Exception;

final class ContentWorkflowNotFoundException extends ContentException
{
    public static function forContentType(string $contentType, string $workflowName): self
    {
        return new self(sprintf(
            'No workflow "%s" registered for content type "%s".',
            $workflowName,
            $contentType,
        ));
    }
}
