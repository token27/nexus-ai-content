<?php

declare(strict_types=1);

namespace Token27\NexusAI\Content\Tests\Unit\Fixture;

use Token27\NexusAI\Prompts\Contract\PromptInterface;
use Token27\NexusAI\Prompts\ValueObject\PromptMetadata;
use Token27\NexusAI\Prompts\ValueObject\RenderedPrompt;

final readonly class ArrayPrompt implements PromptInterface
{
    public function __construct(
        private string $identifier,
        private string $version,
        private string $language,
        private string $system,
        private string $user,
        private string $source = 'test',
    ) {
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function getMetadata(): PromptMetadata
    {
        return new PromptMetadata($this->version, 'test', $this->language);
    }

    public function getVariableDefs(): array
    {
        return [];
    }

    public function getBlocks(): array
    {
        return [
            ['role' => 'system', 'content' => $this->system],
            ['role' => 'user', 'content' => $this->user],
        ];
    }

    public function render(array $variables): RenderedPrompt
    {
        return new RenderedPrompt(
            blocks: [
                ['role' => 'system', 'content' => $this->renderString($this->system, $variables)],
                ['role' => 'user', 'content' => $this->renderString($this->user, $variables)],
            ],
            metadata: $this->getMetadata(),
            language: $this->language,
            version: $this->version,
            source: $this->source,
        );
    }

    /**
     * @param array<string, mixed> $variables
     */
    private function renderString(string $template, array $variables): string
    {
        foreach ($variables as $key => $value) {
            if (is_scalar($value)) {
                $template = str_replace('{{' . $key . '}}', (string) $value, $template);
            }
        }

        return $template;
    }
}
