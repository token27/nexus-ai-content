<?php

declare(strict_types=1);

require __DIR__ . '/_common.php';

echo "=== nexus-ai-content: Gemini Spanish content ===" . PHP_EOL;

try {
    $apiKey = requireEnv('GEMINI_API_KEY');
    $model = envOrNull('GEMINI_TEXT_MODEL') ?? 'gemini-2.5-flash';
    $pricing = makePricingEngine();

    bootNexus([
        'gemini' => ['api_key' => $apiKey],
    ], $pricing);

    $engine = makeContentEngine(defaultLanguage: 'es', pricing: $pricing);
    $engine->getRegistry()->register('article', draftWorkflow());

    $result = $engine
        ->for('gemini', $model)
        ->withContentType('article')
        ->withLanguage('es')
        ->withVariable('topic', 'como crear contenido de calidad con inteligencia artificial')
        ->withVariable('audience', 'creadores de contenido tecnicos')
        ->withBudget(0.05)
        ->generate();

    printContentResult($result);
} catch (Throwable $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
