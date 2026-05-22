<?php

declare(strict_types=1);

require __DIR__ . '/_common.php';

echo "=== nexus-ai-content: OpenAI draft ===" . PHP_EOL;

try {
    $apiKey = requireEnv('OPENAI_API_KEY');
    $model = envOrNull('OPENAI_TEXT_MODEL') ?? 'gpt-4o-mini';
    $pricing = makePricingEngine();

    bootNexus([
        'openai' => ['api_key' => $apiKey],
    ], $pricing);

    $engine = makeContentEngine(defaultLanguage: 'en', pricing: $pricing);
    $engine->getRegistry()->register('article', draftWorkflow());

    $result = $engine
        ->for('openai', $model)
        ->withContentType('article', 'default')
        ->withRunId('example-openai-' . date('Ymd-His'))
        ->withLanguage('en')
        ->withVariable('topic', 'building maintainable AI content workflows in PHP')
        ->withVariable('audience', 'senior PHP developers')
        ->withBudget(0.05)
        ->generate();

    printContentResult($result);
} catch (Throwable $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
