<?php

declare(strict_types=1);

namespace Token27\NexusAI\Content\Contract;

interface ContentEngineInterface
{
    public static function create(): static;

    public static function using(string $provider, string $model): ContentBuilderInterface;

    public function for(string $provider, string $model): ContentBuilderInterface;

    public function registerExtension(ExtensionInterface $extension): static;

    public function getRegistry(): ContentRegistryInterface;
}
