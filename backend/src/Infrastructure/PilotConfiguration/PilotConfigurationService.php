<?php

declare(strict_types=1);

namespace App\Infrastructure\PilotConfiguration;

use JsonException;

final class PilotConfigurationService
{
    private const DATE_FORMAT = 'Y-m-d';
    private const MAX_PROMOTED_SKUS = 30;
    private const EVENT_TYPES = ['holiday', 'sports', 'seasonal', 'local'];
    private const SECRET_KEYS = ['api_key', 'apikey', 'openai_api_key', 'secret', 'password', 'token'];

    /**
     * @var array<string, mixed>|null
     */
    private ?array $configuration = null;

    public function __construct(
        private readonly string $configPath,
        private readonly string $projectDir,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getConfiguration(): array
    {
        if ($this->configuration === null) {
            $this->configuration = $this->loadConfiguration();
        }

        return $this->configuration;
    }

    /**
     * @return array<string, mixed>
     */
    public function getSafeDefaultConfiguration(): array
    {
        return [
            'pilot_id' => 'default',
            'store_name' => 'Magasin pilote',
            'locale' => 'fr-FR',
            'currency' => 'EUR',
            'valid_from' => '1970-01-01',
            'valid_to' => '1970-01-01',
            'assistant_name' => 'Le Compagnon de courses',
            'default_servings' => 4,
            'branding' => [
                'primary_color' => '#000000',
                'logo_url' => '',
            ],
            'ui_labels' => [
                'chips' => [],
                'suggest_button' => 'Proposez-moi une recette',
                'basket_title' => 'À ajouter à votre panier',
            ],
            'events' => [],
            'promoted_skus' => [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function loadConfiguration(): array
    {
        $resolvedPath = $this->resolvePath($this->configPath);

        if ($resolvedPath === null || !is_readable($resolvedPath)) {
            return $this->getSafeDefaultConfiguration();
        }

        $contents = file_get_contents($resolvedPath);
        if ($contents === false) {
            return $this->getSafeDefaultConfiguration();
        }

        try {
            $decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return $this->getSafeDefaultConfiguration();
        }

        if (!is_array($decoded) || !$this->isValidConfiguration($decoded)) {
            return $this->getSafeDefaultConfiguration();
        }

        /** @var array<string, mixed> $decoded */
        return $decoded;
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
     * @param array<mixed> $configuration
     */
    private function isValidConfiguration(array $configuration): bool
    {
        if ($this->containsForbiddenSecretKey($configuration)) {
            return false;
        }

        foreach (['pilot_id', 'store_name', 'locale', 'currency', 'valid_from', 'valid_to'] as $field) {
            if (!$this->hasNonEmptyString($configuration, $field)) {
                return false;
            }
        }

        if (
            !$this->isPilotId((string) $configuration['pilot_id'])
            || !$this->isIsoDate((string) $configuration['valid_from'])
            || !$this->isIsoDate((string) $configuration['valid_to'])
            || strcmp((string) $configuration['valid_from'], (string) $configuration['valid_to']) > 0
        ) {
            return false;
        }

        if (!isset($configuration['events']) || !is_array($configuration['events'])) {
            return false;
        }

        if (!isset($configuration['promoted_skus']) || !is_array($configuration['promoted_skus'])) {
            return false;
        }

        return $this->hasValidOptionalFields($configuration)
            && $this->hasValidEvents($configuration['events'])
            && $this->hasValidPromotedSkus($configuration['promoted_skus']);
    }

    /**
     * @param array<mixed> $configuration
     */
    private function hasValidOptionalFields(array $configuration): bool
    {
        if (isset($configuration['branding'])) {
            if (!is_array($configuration['branding'])) {
                return false;
            }

            foreach (['primary_color', 'logo_url'] as $field) {
                if (isset($configuration['branding'][$field]) && !is_string($configuration['branding'][$field])) {
                    return false;
                }
            }
        }

        if (isset($configuration['assistant_name']) && !is_string($configuration['assistant_name'])) {
            return false;
        }

        if (isset($configuration['default_servings']) && (!is_int($configuration['default_servings']) || $configuration['default_servings'] < 1)) {
            return false;
        }

        if (isset($configuration['model']) && !is_string($configuration['model'])) {
            return false;
        }

        if (isset($configuration['temperature']) && !$this->isNumber($configuration['temperature'])) {
            return false;
        }

        if (isset($configuration['ui_labels'])) {
            if (!is_array($configuration['ui_labels'])) {
                return false;
            }

            foreach (['suggest_button', 'basket_title'] as $field) {
                if (isset($configuration['ui_labels'][$field]) && !is_string($configuration['ui_labels'][$field])) {
                    return false;
                }
            }

            if (isset($configuration['ui_labels']['chips'])) {
                if (!is_array($configuration['ui_labels']['chips'])) {
                    return false;
                }

                foreach ($configuration['ui_labels']['chips'] as $chip) {
                    if (!is_string($chip)) {
                        return false;
                    }
                }
            }
        }

        return true;
    }

    /**
     * @param array<mixed> $events
     */
    private function hasValidEvents(array $events): bool
    {
        foreach ($events as $event) {
            if (!is_array($event)) {
                return false;
            }

            foreach (['name', 'type', 'angle'] as $field) {
                if (!$this->hasNonEmptyString($event, $field)) {
                    return false;
                }
            }

            if (!in_array($event['type'], self::EVENT_TYPES, true)) {
                return false;
            }

            if (!$this->hasValidEventDate($event)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<mixed> $event
     */
    private function hasValidEventDate(array $event): bool
    {
        if (isset($event['date']) && is_string($event['date'])) {
            return $this->isIsoDate($event['date']);
        }

        if (!isset($event['date_range']) || !is_array($event['date_range'])) {
            return false;
        }

        $dateRange = $event['date_range'];
        if (!$this->hasNonEmptyString($dateRange, 'from') || !$this->hasNonEmptyString($dateRange, 'to')) {
            return false;
        }

        return $this->isIsoDate((string) $dateRange['from'])
            && $this->isIsoDate((string) $dateRange['to'])
            && strcmp((string) $dateRange['from'], (string) $dateRange['to']) <= 0;
    }

    /**
     * @param array<mixed> $promotedSkus
     */
    private function hasValidPromotedSkus(array $promotedSkus): bool
    {
        if (count($promotedSkus) > self::MAX_PROMOTED_SKUS) {
            return false;
        }

        $skuIds = [];

        foreach ($promotedSkus as $sku) {
            if (!is_array($sku)) {
                return false;
            }

            foreach (['sku_id', 'name', 'category', 'unit'] as $field) {
                if (!$this->hasNonEmptyString($sku, $field)) {
                    return false;
                }
            }

            if (!array_key_exists('price', $sku) || !$this->isNumber($sku['price']) || (float) $sku['price'] < 0) {
                return false;
            }

            if (!array_key_exists('on_promo', $sku) || !is_bool($sku['on_promo'])) {
                return false;
            }

            if (isset($sku['pairing_hint']) && !is_string($sku['pairing_hint'])) {
                return false;
            }

            if (isset($skuIds[$sku['sku_id']])) {
                return false;
            }

            $skuIds[$sku['sku_id']] = true;
        }

        return true;
    }

    /**
     * @param array<mixed> $data
     */
    private function hasNonEmptyString(array $data, string $field): bool
    {
        return isset($data[$field]) && is_string($data[$field]) && trim($data[$field]) !== '';
    }

    private function isPilotId(string $pilotId): bool
    {
        return preg_match('/^[a-z0-9-]+$/', $pilotId) === 1;
    }

    private function isNumber(mixed $value): bool
    {
        return is_int($value) || is_float($value);
    }

    private function isIsoDate(string $date): bool
    {
        $parsed = \DateTimeImmutable::createFromFormat('!'.self::DATE_FORMAT, $date);
        $errors = \DateTimeImmutable::getLastErrors();

        return $parsed !== false
            && $parsed->format(self::DATE_FORMAT) === $date
            && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0));
    }

    /**
     * @param array<mixed> $data
     */
    private function containsForbiddenSecretKey(array $data): bool
    {
        foreach ($data as $key => $value) {
            if (is_string($key) && in_array(strtolower($key), self::SECRET_KEYS, true)) {
                return true;
            }

            if (is_array($value) && $this->containsForbiddenSecretKey($value)) {
                return true;
            }
        }

        return false;
    }
}
