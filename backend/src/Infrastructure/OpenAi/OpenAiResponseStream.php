<?php

declare(strict_types=1);

namespace App\Infrastructure\OpenAi;

/**
 * @phpstan-type DeltaHandler callable(string): void
 */
final readonly class OpenAiResponseStream
{
    /**
     * @param resource $stream
     */
    public function __construct(
        private mixed $stream,
    ) {
    }

    /**
     * @param callable(string): void $onDelta
     */
    public function forwardTextDeltas(callable $onDelta): void
    {
        $event = null;
        $dataLines = [];

        while (($line = fgets($this->stream)) !== false) {
            $line = rtrim($line, "\r\n");

            if ($line === '') {
                $this->dispatchEvent($event, $dataLines, $onDelta);
                $event = null;
                $dataLines = [];
                continue;
            }

            if (str_starts_with($line, 'event:')) {
                $event = trim(substr($line, 6));
                continue;
            }

            if (str_starts_with($line, 'data:')) {
                $dataLines[] = trim(substr($line, 5));
            }
        }

        if ($event !== null || $dataLines !== []) {
            $this->dispatchEvent($event, $dataLines, $onDelta);
        }

        if (!feof($this->stream)) {
            throw new OpenAiUpstreamException('OpenAI stream ended unexpectedly.', 503);
        }
    }

    /**
     * @param list<string> $dataLines
     * @param callable(string): void $onDelta
     */
    private function dispatchEvent(?string $event, array $dataLines, callable $onDelta): void
    {
        if ($dataLines === []) {
            return;
        }

        $payload = implode("\n", $dataLines);

        if ($payload === '[DONE]') {
            return;
        }

        $decoded = json_decode($payload, true);

        if (!is_array($decoded)) {
            throw new OpenAiUpstreamException('OpenAI stream emitted invalid JSON.', 503);
        }

        $type = is_string($decoded['type'] ?? null) ? $decoded['type'] : $event;

        if ($type === 'error') {
            throw new OpenAiUpstreamException('OpenAI stream emitted an error event.', 503);
        }

        if ($type !== 'response.output_text.delta') {
            return;
        }

        $delta = $decoded['delta'] ?? null;

        if (is_string($delta) && $delta !== '') {
            $onDelta($delta);
        }
    }
}
