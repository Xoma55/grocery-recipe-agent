<?php

declare(strict_types=1);

namespace App\Infrastructure\SystemPrompt;

use App\Infrastructure\PilotConfiguration\PilotConfigurationService;
use JsonException;
use RuntimeException;

final class SystemPromptAssemblyService
{
    public function __construct(
        private readonly PilotConfigurationService $configurationService,
        private readonly string $basePromptPath,
        private readonly string $projectDir,
    ) {
    }

    public function getAssembledSystemPrompt(): string
    {
        $configuration = $this->configurationService->getConfiguration();
        $basePrompt = $this->readBasePrompt();

        return str_replace(
            [
                '{{ASSISTANT_NAME}}',
                '{{STORE_NAME}}',
                '{{EVENT_CONTEXT}}',
                '{{PROMOTED_SKUS}}',
                '{{DEFAULT_SERVINGS}}',
            ],
            [
                $this->stringValue($configuration, 'assistant_name'),
                $this->stringValue($configuration, 'store_name'),
                $this->jsonBlock($this->arrayValue($configuration, 'events')),
                $this->jsonBlock($this->arrayValue($configuration, 'promoted_skus')),
                (string) $this->intValue($configuration, 'default_servings'),
            ],
            $basePrompt,
        );
    }

    private function readBasePrompt(): string
    {
        $resolvedPath = $this->resolvePath($this->basePromptPath);
        if ($resolvedPath === null || !is_readable($resolvedPath)) {
            throw new RuntimeException('Base system prompt is not readable.');
        }

        $contents = file_get_contents($resolvedPath);
        if ($contents === false) {
            throw new RuntimeException('Base system prompt could not be read.');
        }

        return $contents;
    }

    private function resolvePath(string $path): ?string
    {
        if ($path === '') {
            return null;
        }

        if (str_starts_with($path, '/')) {
            return $path;
        }

        $projectRelative = $this->projectDir.'/'.$path;
        if (file_exists($projectRelative)) {
            return $projectRelative;
        }

        $repositoryRelative = dirname($this->projectDir).'/'.$path;
        if (file_exists($repositoryRelative)) {
            return $repositoryRelative;
        }

        return $projectRelative;
    }

    /**
     * @param array<string, mixed> $configuration
     */
    private function stringValue(array $configuration, string $key): string
    {
        $value = $configuration[$key] ?? '';

        return is_string($value) ? $value : '';
    }

    /**
     * @param array<string, mixed> $configuration
     */
    private function intValue(array $configuration, string $key): int
    {
        $value = $configuration[$key] ?? 0;

        return is_int($value) ? $value : 0;
    }

    /**
     * @param array<string, mixed> $configuration
     *
     * @return array<mixed>
     */
    private function arrayValue(array $configuration, string $key): array
    {
        $value = $configuration[$key] ?? [];

        return is_array($value) ? $value : [];
    }

    /**
     * @param array<mixed> $value
     */
    private function jsonBlock(array $value): string
    {
        try {
            return json_encode($value, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } catch (JsonException $exception) {
            throw new RuntimeException('System prompt configuration could not be encoded.', previous: $exception);
        }
    }
}
