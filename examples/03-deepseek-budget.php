<?php

declare(strict_types=1);

require __DIR__ . '/_common.php';

echo "=== nexus-ai-content: DeepSeek with budget ===" . PHP_EOL;

try {
    $apiKey = requireEnv('DEEPSEEK_API_KEY');
    $model = envOrNull('DEEPSEEK_TEXT_MODEL') ?? 'deepseek-chat';
    $pricing = makePricingEngine();

    bootNexus([
        'deepseek' => ['api_key' => $apiKey],
    ], $pricing);

    $engine = makeContentEngine(defaultLanguage: 'en', pricing: $pricing);
    $engine->getRegistry()->register('article', draftWorkflow());

    $result = $engine
        ->for('deepseek', $model)
        ->withContentType('article')
        ->withLanguage('en')
        ->withVariable('topic', 'designing low-cost AI generation pipelines')
        ->withVariable('audience', 'indie developers')
        ->withBudget(0.01)
        ->generate();

    printContentResult($result);
} catch (Throwable $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
