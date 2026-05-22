<?php

declare(strict_types=1);

require __DIR__ . '/_common.php';

echo "=== nexus-ai-content: Anthropic outline ===" . PHP_EOL;

try {
    $apiKey = requireEnv('ANTHROPIC_API_KEY');
    $model = envOrNull('ANTHROPIC_TEXT_MODEL') ?? 'claude-sonnet-4-6';
    $pricing = makePricingEngine();

    bootNexus([
        'anthropic' => ['api_key' => $apiKey],
    ], $pricing);

    $engine = makeContentEngine(defaultLanguage: 'en', pricing: $pricing);
    $engine->getRegistry()->register('article', outlineWorkflow(), 'outline');

    $result = $engine
        ->for('anthropic', $model)
        ->withContentType('article', 'outline')
        ->withLanguage('en')
        ->withVariable('topic', 'how editorial teams can review AI-assisted drafts')
        ->withVariable('audience', 'content editors')
        ->withBudget(0.08)
        ->generate();

    printContentResult($result);
} catch (Throwable $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
