<?php

declare(strict_types=1);

namespace Token27\NexusAI\Content;

use Token27\NexusAI\Content\Contract\ContentBuilderInterface;
use Token27\NexusAI\Content\Contract\ContentEngineInterface;
use Token27\NexusAI\Content\Contract\ContentRegistryInterface;
use Token27\NexusAI\Content\Contract\ExtensionInterface;
use Token27\NexusAI\Driver\DriverRegistry;
use Token27\NexusAI\Pricing\Contract\PricingEngineInterface;
use Token27\NexusAI\Prompts\Contract\PromptRegistryInterface;
use Token27\NexusAI\Tracking\Contract\ExecutionStoreInterface;
use Token27\NexusAI\Tracking\Contract\TrackingBuilderInterface;

final class ContentEngine implements ContentEngineInterface
{
    private ?DriverRegistry $driverRegistry = null;

    private ?PromptRegistryInterface $promptRegistry = null;

    private TrackingBuilderInterface|ExecutionStoreInterface|null $tracking = null;

    private ?PricingEngineInterface $pricing = null;

    private function __construct(
        private readonly ContentRegistryInterface $registry = new ContentRegistry(),
    ) {
    }

    public static function create(): static
    {
        return new self();
    }

    public static function using(string $provider, string $model): ContentBuilderInterface
    {
        return self::create()->for($provider, $model);
    }

    public function for(string $provider, string $model): ContentBuilderInterface
    {
        $builder = new ContentBuilder($provider, $model, $this->registry);

        if ($this->driverRegistry !== null) {
            $builder->withDriverRegistry($this->driverRegistry);
        }

        if ($this->promptRegistry !== null) {
            $builder->withPromptRegistry($this->promptRegistry);
        }

        if ($this->tracking !== null) {
            $builder->withTracking($this->tracking);
        }

        if ($this->pricing !== null) {
            $builder->withPricing($this->pricing);
        }

        return $builder;
    }

    public function registerExtension(ExtensionInterface $extension): static
    {
        $extension->register($this);

        return $this;
    }

    public function getRegistry(): ContentRegistryInterface
    {
        return $this->registry;
    }

    public function withDriverRegistry(DriverRegistry $registry): static
    {
        $this->driverRegistry = $registry;

        return $this;
    }

    public function withPromptRegistry(PromptRegistryInterface $registry): static
    {
        $this->promptRegistry = $registry;

        return $this;
    }

    public function withTracking(TrackingBuilderInterface|ExecutionStoreInterface $tracking): static
    {
        $this->tracking = $tracking;

        return $this;
    }

    public function withPricing(PricingEngineInterface $pricing): static
    {
        $this->pricing = $pricing;

        return $this;
    }
}
