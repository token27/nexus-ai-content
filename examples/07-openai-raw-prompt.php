<?php

declare(strict_types=1);

require __DIR__ . '/_common.php';

use Token27\NexusAI\Content\ContentEngine;
use Token27\NexusAI\Content\Node\ContentAINode;
use Token27\NexusAI\Tracking\Engine\TrackingEngine;
use Token27\NexusAI\Workflows\Engine\WorkflowBuilder;

echo "=== nexus-ai-content: OpenAI raw prompt iteration ===" . PHP_EOL;

try {
    $apiKey = requireEnv('OPENAI_API_KEY');
    $model = envOrNull('OPENAI_TEXT_MODEL') ?? 'gpt-4o-mini';
    $pricing = makePricingEngine();
    $tracking = TrackingEngine::using('memory');

    bootNexus([
        'openai' => ['api_key' => $apiKey],
    ], $pricing);

    $workflow = WorkflowBuilder::named('raw-draft')
        ->addNode('draft', ContentAINode::rawPrompt(
            prompt: <<<'PROMPT'
Write a compact editorial draft about {{topic}} for {{audience}}.

Constraints:
- Start with a clear title.
- Use three short sections.
- Keep it practical and non-spammy.
- End with one useful takeaway.
PROMPT,
            systemPrompt: 'You are an expert editor testing a new prompt before it is versioned.',
            identifier: 'runtime/article-draft-experiment',
            outputKey: 'content',
            source: 'runtime',
            temperature: 0.35,
            maxTokens: 700,
        ))
        ->build();

    $engine = ContentEngine::create()
        ->withPricing($pricing)
        ->withTracking($tracking);
    $engine->getRegistry()->register('article', $workflow, 'raw');

    $runId = 'example-raw-prompt-' . date('Ymd-His');
    $result = $engine
        ->for('openai', $model)
        ->withContentType('article', 'raw')
        ->withRunId($runId)
        ->withLanguage('en')
        ->withVariable('topic', 'developing AI prompts before committing them to files')
        ->withVariable('audience', 'content engineers')
        ->withBudget(0.05)
        ->generate();

    printContentResult($result);

    echo PHP_EOL . 'Tracking prompt data:' . PHP_EOL;
    foreach ($tracking->findByRun($runId) as $event) {
        echo '- ' . json_encode($event->data['prompt'] ?? [], JSON_UNESCAPED_SLASHES) . PHP_EOL;
    }
} catch (Throwable $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
