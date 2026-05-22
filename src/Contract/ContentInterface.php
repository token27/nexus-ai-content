<?php

declare(strict_types=1);

namespace Token27\NexusAI\Content\Contract;

interface ContentInterface
{
    public function getContentType(): string;

    public function toText(): string;

    /** @return array<string, mixed> */
    public function toArray(): array;
}
