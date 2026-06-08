<?php

declare(strict_types=1);

namespace App\Infrastructure\OpenAi;

final readonly class HttpOpenAiClient implements OpenAiClientInterface
{
    public function __construct(
        private OpenAiConfiguration $configuration,
    ) {
    }

    public function createConversation(): string
    {
        $response = $this->requestJson('/conversations', []);
        $conversationId = $response['id'] ?? null;

        if (!is_string($conversationId) || $conversationId === '') {
            throw new OpenAiUpstreamException('OpenAI conversation response did not include an id.');
        }

        return $conversationId;
    }

    public function createStreamingResponse(string $conversationId, string $instructions, string $message): OpenAiResponseStream
    {
        $body = [
            'model' => $this->configuration->model,
            'conversation' => $conversationId,
            'instructions' => $instructions,
            'input' => [
                [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'input_text',
                            'text' => $message,
                        ],
                    ],
                ],
            ],
            'stream' => true,
            'stream_options' => [
                'include_obfuscation' => false,
            ],
        ];

        $stream = $this->openStream('/responses', $body);

        return new OpenAiResponseStream($stream);
    }

    /**
     * @return array<string, mixed>
     */
    private function requestJson(string $path, array $body): array
    {
        $stream = $this->openStream($path, $body);
        $contents = stream_get_contents($stream);

        if ($contents === false) {
            throw new OpenAiUpstreamException('Unable to read OpenAI response.');
        }

        $decoded = json_decode($contents, true);

        if (!is_array($decoded)) {
            throw new OpenAiUpstreamException('OpenAI returned invalid JSON.');
        }

        return $decoded;
    }

    /**
     * @return resource
     */
    private function openStream(string $path, array $body): mixed
    {
        if (trim($this->configuration->apiKey) === '') {
            throw new \RuntimeException('OPENAI_API_KEY is not configured.');
        }

        $payload = json_encode($body, JSON_THROW_ON_ERROR);
        $headers = [
            'Authorization: Bearer ' . $this->configuration->apiKey,
            'Content-Type: application/json',
            'Accept: text/event-stream',
        ];

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => implode("\r\n", $headers),
                'content' => $payload,
                'ignore_errors' => true,
                'timeout' => 60,
            ],
        ]);

        $stream = @fopen($this->url($path), 'rb', false, $context);

        if ($stream === false) {
            throw new OpenAiUpstreamException('Unable to connect to OpenAI.', 503);
        }

        $metadata = stream_get_meta_data($stream);
        $headers = isset($metadata['wrapper_data']) && is_array($metadata['wrapper_data'])
            ? $metadata['wrapper_data']
            : [];
        $statusCode = $this->statusCode($headers);

        if ($statusCode < 200 || $statusCode >= 300) {
            $contents = stream_get_contents($stream);
            fclose($stream);

            throw new OpenAiUpstreamException(
                'OpenAI returned an upstream error.',
                $statusCode >= 500 ? 503 : 502,
                $statusCode,
                is_string($contents) ? $this->shorten($contents) : null,
            );
        }

        return $stream;
    }

    private function shorten(string $contents): string
    {
        $contents = trim($contents);

        if (strlen($contents) <= 1000) {
            return $contents;
        }

        return substr($contents, 0, 1000) . '...';
    }

    /**
     * @param list<string> $headers
     */
    private function statusCode(array $headers): int
    {
        foreach ($headers as $header) {
            if (preg_match('/^HTTP\/\S+\s+(\d{3})\b/', $header, $matches) === 1) {
                return (int) $matches[1];
            }
        }

        return 0;
    }

    private function url(string $path): string
    {
        return rtrim($this->configuration->baseUrl, '/') . '/' . ltrim($path, '/');
    }
}
