<?php

declare(strict_types=1);

require __DIR__ . '/_common.php';

use Token27\NexusAI\Driver\Fake\FakeDriver;
use Token27\NexusAI\Enum\FinishReason;
use Token27\NexusAI\Response\TextResponse;

echo "=== nexus-ai-content: local testing with FakeDriver ===" . PHP_EOL;

$fake = new FakeDriver();
$fake->willReturn(new TextResponse(
    text: 'This is a local deterministic content result for tests.',
    finishReason: FinishReason::Stop,
));

$engine = makeContentEngine();
$engine->getRegistry()->register('article', draftWorkflow());

$result = $engine
    ->for('fake', 'test-model')
    ->withDriver($fake)
    ->withContentType('article')
    ->withVariable('topic', 'testing content workflows')
    ->withVariable('audience', 'library maintainers')
    ->generate();

printContentResult($result);
