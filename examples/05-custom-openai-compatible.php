<?php

declare(strict_types=1);

require __DIR__ . '/_common.php';

echo "=== nexus-ai-content: custom OpenAI-compatible provider ===" . PHP_EOL;

try {
    $provider = envOrNull('CUSTOM_PROVIDER_NAME') ?? 'openrouter';
    $apiKey = requireEnv('CUSTOM_PROVIDER_API_KEY');
    $baseUrl = envOrNull('CUSTOM_PROVIDER_BASE_URL') ?? 'https://openrouter.ai/api/v1';
    $model = envOrNull('CUSTOM_PROVIDER_MODEL') ?? 'openai/gpt-4o-mini';
    $pricing = makePricingEngine();

    bootNexus([], $pricing);
    registerOpenAICompatibleProvider($provider, $baseUrl);
    NexusAI::configure([
        $provider => [
            'api_key' => $apiKey,
            'base_url' => $baseUrl,
        ],
    ]);

    $engine = makeContentEngine(defaultLanguage: 'en', pricing: $pricing);
    $engine->getRegistry()->register('article', draftWorkflow());

    $result = $engine
        ->for($provider, $model)
        ->withContentType('article')
        ->withLanguage('en')
        ->withVariable('topic', 'portable AI provider configuration')
        ->withVariable('audience', 'PHP application developers')
        ->generate();

    printContentResult($result);
} catch (Throwable $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
