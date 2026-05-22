<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Token27\NexusAI\Content\ContentEngine;
use Token27\NexusAI\Content\Node\ContentAINode;
use Token27\NexusAI\Content\ValueObject\ContentResult;
use Token27\NexusAI\Driver\OpenAI\OpenAIDriver;
use Token27\NexusAI\NexusAI;
use Token27\NexusAI\Pricing\Contract\PricingEngineInterface;
use Token27\NexusAI\Pricing\Engine\PricingEngine;
use Token27\NexusAI\Pricing\Registry\PricingRegistry;
use Token27\NexusAI\Prompts\Engine\MustacheAdapter;
use Token27\NexusAI\Prompts\Loader\PromptLoader;
use Token27\NexusAI\Prompts\Loader\PromptSchemaValidator;
use Token27\NexusAI\Prompts\PromptRegistry;
use Token27\NexusAI\Prompts\Storage\LocalFilesystemStorage;
use Token27\NexusAI\Tracking\Contract\TrackingBuilderInterface;
use Token27\NexusAI\Tracking\Engine\TrackingEngine;
use Token27\NexusAI\Workflows\Contract\WorkflowInterface;
use Token27\NexusAI\Workflows\Engine\WorkflowBuilder;

const EXAMPLE_PROMPT_SOURCE = 'token27/nexus-ai-content';
const EXAMPLE_DRAFT_PROMPT_IDENTIFIER = 'content/draft';

loadDotEnvIfPresent();

function loadDotEnvIfPresent(): void
{
    static $loaded = false;
    if ($loaded) {
        return;
    }

    foreach ([__DIR__ . '/../.env', __DIR__ . '/.env'] as $path) {
        if (!is_file($path)) {
            continue;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            continue;
        }

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            $parts = explode('=', $line, 2);
            if (count($parts) !== 2) {
                continue;
            }

            $key = trim($parts[0]);
            $value = trim($parts[1], " \t\n\r\0\x0B\"'");

            if ($key !== '' && getenv($key) === false) {
                putenv($key . '=' . $value);
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }

        $loaded = true;

        return;
    }
}

function envOrNull(string $key): ?string
{
    $value = getenv($key);

    return $value === false || trim($value) === '' ? null : $value;
}

function requireEnv(string $key): string
{
    return envOrNull($key) ?? throw new RuntimeException("Missing environment variable: {$key}");
}

/**
 * @param array<string, array<string, mixed>> $providers
 */
function bootNexus(array $providers, ?PricingEngineInterface $pricing = null): void
{
    $factory = new HttpFactory();

    NexusAI::reset();
    NexusAI::setHttpClient(new Client([
        'timeout' => 90,
        'verify' => false,
    ]));
    NexusAI::setFactories($factory, $factory);
    NexusAI::configure($providers);
    NexusAI::withPricing($pricing ?? makePricingEngine());
}

function registerOpenAICompatibleProvider(string $provider, string $baseUrl): void
{
    NexusAI::registerDriver($provider, static function (
        array $config,
        ClientInterface $httpClient,
        RequestFactoryInterface $requestFactory,
        StreamFactoryInterface $streamFactory,
    ) use ($baseUrl): OpenAIDriver {
        return new OpenAIDriver(
            httpClient: $httpClient,
            requestFactory: $requestFactory,
            streamFactory: $streamFactory,
            apiKey: $config['api_key'] ?? '',
            baseUrl: $config['base_url'] ?? $baseUrl,
            options: $config['options'] ?? [],
        );
    });
}

function makePricingEngine(): PricingEngineInterface
{
    if (class_exists('Token27\\Tokenizer\\Registry\\TokenizerRegistry')) {
        /** @var class-string $class */
        $class = 'Token27\\Tokenizer\\Registry\\TokenizerRegistry';

        return PricingEngine::withTokenizer($class::createDefault());
    }

    return PricingEngine::withRegistry(PricingRegistry::createDefault());
}

function makePromptRegistry(string $defaultLanguage = 'en'): PromptRegistry
{
    $registry = new PromptRegistry(
        loader: new PromptLoader(
            validator: new PromptSchemaValidator(),
            engine: new MustacheAdapter(),
        ),
        defaultStorage: new LocalFilesystemStorage(''),
        defaultLanguage: $defaultLanguage,
        fallbackLanguage: 'en',
    );

    $registry->autoloadFrom(__DIR__, EXAMPLE_PROMPT_SOURCE);

    return $registry;
}

function makeTracker(?string $path = null): TrackingBuilderInterface
{
    if ($path === null) {
        return TrackingEngine::using('memory');
    }

    if (!is_dir($path)) {
        mkdir($path, 0777, true);
    }

    return TrackingEngine::using('jsonfile', $path);
}

function makeContentEngine(
    string $defaultLanguage = 'en',
    ?PricingEngineInterface $pricing = null,
    ?TrackingBuilderInterface $tracking = null,
): ContentEngine {
    $pricing ??= makePricingEngine();
    $tracking ??= makeTracker();

    return ContentEngine::create()
        ->withPromptRegistry(makePromptRegistry($defaultLanguage))
        ->withPricing($pricing)
        ->withTracking($tracking);
}

function draftWorkflow(string $prompt = EXAMPLE_DRAFT_PROMPT_IDENTIFIER, string $version = '1.0.0'): WorkflowInterface
{
    return WorkflowBuilder::named('draft')
        ->addNode('draft', new ContentAINode(
            promptIdentifier: $prompt,
            outputKey: 'content',
            promptVersion: $version,
            temperature: 0.35,
            maxTokens: 700,
        ))
        ->build();
}

function outlineWorkflow(): WorkflowInterface
{
    return WorkflowBuilder::named('outline')
        ->addNode('outline', new ContentAINode(
            promptIdentifier: 'content/outline',
            outputKey: 'content',
            promptVersion: '1.0.0',
            temperature: 0.2,
            maxTokens: 500,
        ))
        ->build();
}

function printContentResult(ContentResult $result): void
{
    echo 'Run: ' . $result->runId . PHP_EOL;
    echo 'Content type: ' . $result->contentType . PHP_EOL;
    echo 'Workflow: ' . $result->workflowName . PHP_EOL;
    echo 'Success: ' . ($result->workflowResult->isSuccess() ? 'yes' : 'no') . PHP_EOL;
    echo 'Tokens: ' . $result->totalTokens . PHP_EOL;
    echo 'Cost USD: ' . $result->costUsd . PHP_EOL;
    echo 'Elapsed ms: ' . round($result->elapsedMs, 2) . PHP_EOL;
    echo PHP_EOL;
    echo trim($result->text) . PHP_EOL;
}
