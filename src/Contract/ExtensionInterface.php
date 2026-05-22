<?php

declare(strict_types=1);

namespace Token27\NexusAI\Content\Contract;

interface ExtensionInterface
{
    public function register(ContentEngineInterface $engine): void;

    /** @return list<string> */
    public function getContentTypes(): array;

    public function getName(): string;
}
