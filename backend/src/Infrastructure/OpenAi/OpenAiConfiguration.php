<?php

declare(strict_types=1);

namespace App\Infrastructure\OpenAi;

final readonly class OpenAiConfiguration
{
    public const DEFAULT_REASONING_EFFORT = 'medium';
    public const DEFAULT_TEXT_VERBOSITY = 'medium';
    public const DEFAULT_MAX_OUTPUT_TOKENS = 4000;

    private const ALLOWED_REASONING_EFFORTS = [
        'minimal',
        'low',
        'medium',
        'high',
    ];

    private const ALLOWED_TEXT_VERBOSITIES = [
        'low',
        'medium',
        'high',
    ];

    public string $reasoningEffort;
    public string $textVerbosity;
    public int $maxOutputTokens;

    public function __construct(
        public string $apiKey,
        public string $model,
        public string $baseUrl,
        string $reasoningEffort = self::DEFAULT_REASONING_EFFORT,
        string $textVerbosity = self::DEFAULT_TEXT_VERBOSITY,
        string|int $maxOutputTokens = self::DEFAULT_MAX_OUTPUT_TOKENS,
    ) {
        $this->reasoningEffort = $this->validateAllowedValue(
            'OPENAI_REASONING_EFFORT',
            $reasoningEffort,
            self::ALLOWED_REASONING_EFFORTS,
        );
        $this->textVerbosity = $this->validateAllowedValue(
            'OPENAI_TEXT_VERBOSITY',
            $textVerbosity,
            self::ALLOWED_TEXT_VERBOSITIES,
        );
        $this->maxOutputTokens = $this->validatePositiveInteger('OPENAI_MAX_OUTPUT_TOKENS', $maxOutputTokens);
    }

    /**
     * @param list<string> $allowedValues
     */
    private function validateAllowedValue(string $name, string $value, array $allowedValues): string
    {
        $normalized = strtolower(trim($value));

        if (!in_array($normalized, $allowedValues, true)) {
            throw new \InvalidArgumentException(sprintf(
                '%s must be one of: %s.',
                $name,
                implode(', ', $allowedValues),
            ));
        }

        return $normalized;
    }

    private function validatePositiveInteger(string $name, string|int $value): int
    {
        if (is_int($value)) {
            $integer = $value;
        } else {
            $trimmed = trim($value);

            if ($trimmed === '' || preg_match('/^\d+$/', $trimmed) !== 1) {
                throw new \InvalidArgumentException(sprintf('%s must be a positive integer.', $name));
            }

            $integer = (int) $trimmed;
        }

        if ($integer <= 0) {
            throw new \InvalidArgumentException(sprintf('%s must be a positive integer.', $name));
        }

        return $integer;
    }
}
