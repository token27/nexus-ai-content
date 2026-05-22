<?php

declare(strict_types=1);

namespace Token27\NexusAI\Content\Tests\Unit\Fixture;

use Token27\NexusAI\Prompts\Contract\PromptInterface;
use Token27\NexusAI\Prompts\Contract\PromptRegistryInterface;
use Token27\NexusAI\Prompts\Exception\PromptNotFoundException;

final class ArrayPromptRegistry implements PromptRegistryInterface
{
    /** @var array<string, PromptInterface> */
    private array $prompts = [];

    public function add(
        string $identifier,
        string $version,
        string $language,
        string $system,
        string $user,
    ): void {
        $prompt = new ArrayPrompt($identifier, $version, $language, $system, $user);
        $this->prompts[$this->key($identifier, $version, $language)] = $prompt;
    }

    public function resolve(
        string $identifier,
        ?string $version = null,
        ?string $language = null,
        ?string $source = null,
    ): PromptInterface {
        $key = $this->key($identifier, $version ?? '1.0.0', $language ?? 'en');

        return $this->prompts[$key] ?? throw PromptNotFoundException::forIdentifier($identifier, $version, $language);
    }

    public function register(PromptInterface $prompt): void
    {
        $this->prompts[$this->key($prompt->getIdentifier(), $prompt->getVersion(), $prompt->getLanguage())] = $prompt;
    }

    public function registerDirectory(string $path, string $namespace, string $source): void
    {
    }

    public function has(
        string $identifier,
        ?string $version = null,
        ?string $language = null,
        ?string $source = null,
    ): bool {
        return isset($this->prompts[$this->key($identifier, $version ?? '1.0.0', $language ?? 'en')]);
    }

    public function autoloadFrom(string $basePath, ?string $source = null): void
    {
    }

    public function listVersions(string $identifier, ?string $source = null): array
    {
        return ['1.0.0'];
    }

    public function listLanguages(string $identifier, string $version, ?string $source = null): array
    {
        return ['en'];
    }

    public function listIdentifiers(?string $source = null): array
    {
        return [];
    }

    public function listSources(): array
    {
        return ['test'];
    }

    public function getDefaultLanguage(): string
    {
        return 'en';
    }

    public function setDefaultLanguage(string $language): void
    {
    }

    public function getFallbackLanguage(): string
    {
        return 'en';
    }

    public function getDefaultVersion(): string
    {
        return '1.0.0';
    }

    public function setDefaultVersion(string $version): void
    {
    }

    private function key(string $identifier, string $version, string $language): string
    {
        return $identifier . ':' . $version . ':' . $language;
    }
}
