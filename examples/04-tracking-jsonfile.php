<?php

declare(strict_types=1);

require __DIR__ . '/_common.php';

echo "=== nexus-ai-content: JSON tracking ===" . PHP_EOL;

try {
    $apiKey = requireEnv('OPENAI_API_KEY');
    $model = envOrNull('OPENAI_TEXT_MODEL') ?? 'gpt-4o-mini';
    $pricing = makePricingEngine();
    $trackingPath = envOrNull('CONTENT_TRACKING_PATH') ?? (__DIR__ . '/var/tracking');
    $tracking = makeTracker($trackingPath);

    bootNexus([
        'openai' => ['api_key' => $apiKey],
    ], $pricing);

    $engine = makeContentEngine(defaultLanguage: 'en', pricing: $pricing, tracking: $tracking);
    $engine->getRegistry()->register('article', draftWorkflow());

    $runId = 'example-tracking-' . date('Ymd-His');
    $result = $engine
        ->for('openai', $model)
        ->withContentType('article')
        ->withRunId($runId)
        ->withLanguage('en')
        ->withVariable('topic', 'observability for AI generated content')
        ->withVariable('audience', 'platform engineers')
        ->generate();

    printContentResult($result);

    echo PHP_EOL . 'Tracking events for ' . $runId . ':' . PHP_EOL;
    foreach ($tracking->findByRun($runId) as $event) {
        echo '- ' . $event->eventType->value . ' ' . json_encode($event->data, JSON_UNESCAPED_SLASHES) . PHP_EOL;
    }

    $matchingPromptEvents = $tracking
        ->query()
        ->withData('prompt.identifier', EXAMPLE_DRAFT_PROMPT_IDENTIFIER)
        ->withData('prompt.source', EXAMPLE_PROMPT_SOURCE)
        ->search(limit: 5);

    echo PHP_EOL . 'Query by prompt.identifier + prompt.source:' . PHP_EOL;
    echo '  identifier: ' . EXAMPLE_DRAFT_PROMPT_IDENTIFIER . PHP_EOL;
    echo '  source: ' . EXAMPLE_PROMPT_SOURCE . PHP_EOL;
    echo '  matches: ' . count($matchingPromptEvents) . PHP_EOL;

    $lastEvent = $matchingPromptEvents[0] ?? null;
    if ($lastEvent !== null) {
        echo '  first match run: ' . $lastEvent->runId . PHP_EOL;
        echo '  first match prompt: ' . json_encode($lastEvent->data['prompt'] ?? [], JSON_UNESCAPED_SLASHES) . PHP_EOL;
    }

    echo PHP_EOL . 'Tracking path: ' . $trackingPath . PHP_EOL;
} catch (Throwable $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
