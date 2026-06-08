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
        $response = $this->requestJson('POST', '/conversations', []);
        $conversationId = $response['id'] ?? null;

        if (!is_string($conversationId) || $conversationId === '') {
            throw new OpenAiUpstreamException('OpenAI conversation response did not include an id.');
        }

        return $conversationId;
    }

    public function createStreamingResponse(string $conversationId, string $instructions, string $message): OpenAiResponseStream
    {
        $stream = $this->openStream('POST', '/responses', $this->streamingResponseBody($conversationId, $instructions, $message), 'text/event-stream');

        return new OpenAiResponseStream($stream);
    }

    public function listConversationMessages(string $conversationId): array
    {
        $messages = [];
        $after = null;

        do {
            $path = '/conversations/' . rawurlencode($conversationId) . '/items';

            if ($after !== null) {
                $path .= '?after=' . rawurlencode($after);
            }

            $response = $this->requestJson('GET', $path);
            $data = $response['data'] ?? null;

            if (!is_array($data)) {
                throw new OpenAiUpstreamException('OpenAI conversation items response did not include a data array.');
            }

            foreach ($data as $item) {
                if (!is_array($item)) {
                    continue;
                }

                $message = $this->messageFromConversationItem($item);

                if ($message !== null) {
                    $messages[] = $message;
                }
            }

            $hasMore = $response['has_more'] ?? false;
            $lastId = $response['last_id'] ?? null;
            $after = $hasMore === true && is_string($lastId) && $lastId !== '' ? $lastId : null;
        } while ($after !== null);

        return $messages;
    }

    /**
     * @return array<string, mixed>
     */
    private function streamingResponseBody(string $conversationId, string $instructions, string $message): array
    {
        return [
            'model' => $this->configuration->model,
            'conversation' => $conversationId,
            'instructions' => $instructions,
            'reasoning' => [
                'effort' => $this->configuration->reasoningEffort,
            ],
            'text' => [
                'verbosity' => $this->configuration->textVerbosity,
            ],
            'max_output_tokens' => $this->configuration->maxOutputTokens,
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
    }

    /**
     * @return array<string, mixed>
     */
    private function requestJson(string $method, string $path, ?array $body = null): array
    {
        $stream = $this->openStream($method, $path, $body, 'application/json');
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
     * @param array<string, mixed> $item
     */
    private function messageFromConversationItem(array $item): ?OpenAiConversationMessage
    {
        if (($item['type'] ?? null) !== 'message') {
            return null;
        }

        $role = $item['role'] ?? null;

        if ($role !== 'user' && $role !== 'assistant') {
            return null;
        }

        $content = $this->textFromContent($item['content'] ?? null);

        if ($content === '') {
            return null;
        }

        return new OpenAiConversationMessage($role, $content);
    }

    private function textFromContent(mixed $content): string
    {
        if (is_string($content)) {
            return trim($content);
        }

        if (!is_array($content)) {
            return '';
        }

        $parts = [];

        foreach ($content as $part) {
            if (is_string($part)) {
                $parts[] = $part;
                continue;
            }

            if (!is_array($part)) {
                continue;
            }

            $type = $part['type'] ?? null;
            $text = $part['text'] ?? null;

            if (($type === 'input_text' || $type === 'output_text' || $type === 'text') && is_string($text)) {
                $parts[] = $text;
            }
        }

        return trim(implode("\n", $parts));
    }

    /**
     * @return resource
     */
    private function openStream(string $method, string $path, ?array $body, string $accept): mixed
    {
        if (trim($this->configuration->apiKey) === '') {
            throw new \RuntimeException('OPENAI_API_KEY is not configured.');
        }

        $headers = [
            'Authorization: Bearer ' . $this->configuration->apiKey,
            'Accept: ' . $accept,
        ];
        $httpOptions = [
            'method' => $method,
            'ignore_errors' => true,
            'timeout' => 60,
        ];

        if ($body !== null) {
            $payload = json_encode($body, JSON_THROW_ON_ERROR);
            $headers[] = 'Content-Type: application/json';
            $httpOptions['content'] = $payload;
        }

        $httpOptions['header'] = implode("\r\n", $headers);

        $context = stream_context_create([
            'http' => $httpOptions,
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
