<?php

declare(strict_types=1);

namespace App\Tests;

use App\Infrastructure\Session\SessionConfiguration;
use App\Infrastructure\Session\SessionManager;
use App\Infrastructure\Session\SessionRepository;
use App\Infrastructure\Session\SqliteConnectionFactory;
use App\Infrastructure\OpenAi\HttpOpenAiClient;
use App\Infrastructure\OpenAi\OpenAiConfiguration;
use App\Tests\Support\DummyKernel;
use App\Tests\Support\MutableClock;
use App\UI\EventSubscriber\DatabaseSessionSubscriber;
use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;
use ReflectionMethod;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Throwable;

require dirname(__DIR__) . '/vendor/autoload.php';

/**
 * @param callable(): void $test
 */
function test(string $name, callable $test): void
{
    try {
        $test();
        echo sprintf("[PASS] %s\n", $name);
    } catch (Throwable $exception) {
        echo sprintf("[FAIL] %s: %s\n", $name, $exception->getMessage());
        exit(1);
    }
}

function assertSameValue(mixed $expected, mixed $actual, string $message): void
{
    if ($expected !== $actual) {
        throw new RuntimeException(sprintf('%s Expected %s, got %s.', $message, var_export($expected, true), var_export($actual, true)));
    }
}

function assertTrueValue(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

/**
 * @param callable(): void $callback
 */
function assertThrows(string $expectedClass, callable $callback, string $message): void
{
    try {
        $callback();
    } catch (Throwable $exception) {
        if ($exception instanceof $expectedClass) {
            return;
        }

        throw new RuntimeException(sprintf(
            '%s Expected %s, got %s.',
            $message,
            $expectedClass,
            $exception::class,
        ));
    }

    throw new RuntimeException(sprintf('%s Expected %s to be thrown.', $message, $expectedClass));
}

/**
 * @return array<string, mixed>
 */
function streamingResponseBody(HttpOpenAiClient $client, string $conversationId, string $instructions, string $message): array
{
    $method = new ReflectionMethod(HttpOpenAiClient::class, 'streamingResponseBody');

    return $method->invoke($client, $conversationId, $instructions, $message);
}

function repository(string $databasePath, int $lifetimeSeconds = 60): SessionRepository
{
    $configuration = new SessionConfiguration($lifetimeSeconds, 'sqlite:///' . $databasePath);

    return new SessionRepository(
        new SqliteConnectionFactory($configuration),
        dirname(__DIR__) . '/migrations/001_create_sessions.sql',
    );
}

function manager(string $databasePath, MutableClock $clock, int $lifetimeSeconds = 60): SessionManager
{
    $configuration = new SessionConfiguration($lifetimeSeconds, 'sqlite:///' . $databasePath);

    return new SessionManager(
        new SessionRepository(
            new SqliteConnectionFactory($configuration),
            dirname(__DIR__) . '/migrations/001_create_sessions.sql',
        ),
        $configuration,
        $clock,
    );
}

function tempDatabasePath(): string
{
    $path = tempnam(sys_get_temp_dir(), 'grocery-session-');

    if ($path === false) {
        throw new RuntimeException('Unable to create temporary database path.');
    }

    return $path;
}

test('session is created on the first request and stored with expiration', function (): void {
    $databasePath = tempDatabasePath();
    $clock = new MutableClock(new DateTimeImmutable('2026-06-08T10:00:00+00:00'));
    $session = manager($databasePath, $clock, 120)->resolve(null);
    $sessions = repository($databasePath)->all();

    assertSameValue(1, count($sessions), 'Exactly one session should be stored.');
    assertSameValue($session->id, $sessions[0]->id, 'Stored session id should match created session.');
    assertSameValue('2026-06-08T10:02:00+00:00', $sessions[0]->expiredAt->format(DateTimeInterface::ATOM), 'Expiration should use SESSION_LIFETIME.');
});

test('request lifecycle creates a database session and response cookie', function (): void {
    $databasePath = tempDatabasePath();
    $clock = new MutableClock(new DateTimeImmutable('2026-06-08T10:00:00+00:00'));
    $configuration = new SessionConfiguration(90, 'sqlite:///' . $databasePath);
    $subscriber = new DatabaseSessionSubscriber(
        new SessionManager(
            new SessionRepository(
                new SqliteConnectionFactory($configuration),
                dirname(__DIR__) . '/migrations/001_create_sessions.sql',
            ),
            $configuration,
            $clock,
        ),
    );
    $kernel = new DummyKernel();
    $request = Request::create('/api/chat', 'POST');

    $subscriber->onKernelRequest(new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST));
    $response = new Response();
    $subscriber->onKernelResponse(new ResponseEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response));

    $sessions = repository($databasePath)->all();
    $cookie = $response->headers->getCookies()[0] ?? null;

    assertSameValue(1, count($sessions), 'Request should create exactly one database session.');
    assertTrueValue($cookie !== null, 'Response should include a session cookie.');
    assertSameValue(DatabaseSessionSubscriber::COOKIE_NAME, $cookie->getName(), 'Cookie name should match the subscriber contract.');
    assertSameValue($sessions[0]->id, $cookie->getValue(), 'Cookie should carry the stored session id.');
});

test('active session is reused without duplicate records', function (): void {
    $databasePath = tempDatabasePath();
    $clock = new MutableClock(new DateTimeImmutable('2026-06-08T10:00:00+00:00'));
    $sessionManager = manager($databasePath, $clock, 60);

    $first = $sessionManager->resolve(null);
    $clock->advance(30);
    $second = $sessionManager->resolve($first->id);

    assertSameValue($first->id, $second->id, 'Active session should be reused.');
    assertSameValue(1, count(repository($databasePath)->all()), 'Active reuse must not create duplicates.');
});

test('expired session is detected, removed, and replaced', function (): void {
    $databasePath = tempDatabasePath();
    $clock = new MutableClock(new DateTimeImmutable('2026-06-08T10:00:00+00:00'));
    $sessionManager = manager($databasePath, $clock, 10);

    $expired = $sessionManager->resolve(null);
    $clock->advance(11);
    $replacement = $sessionManager->resolve($expired->id);
    $sessionRepository = repository($databasePath);

    assertTrueValue($replacement->id !== $expired->id, 'Expired session should be replaced with a new id.');
    assertSameValue(null, $sessionRepository->find($expired->id), 'Expired session record should be removed.');
    assertSameValue(1, count($sessionRepository->all()), 'Only the replacement session should remain.');
});

test('request with expired session cookie deletes old record and returns replacement cookie', function (): void {
    $databasePath = tempDatabasePath();
    $clock = new MutableClock(new DateTimeImmutable('2026-06-08T10:00:00+00:00'));
    $configuration = new SessionConfiguration(10, 'sqlite:///' . $databasePath);
    $sessionManager = new SessionManager(
        new SessionRepository(
            new SqliteConnectionFactory($configuration),
            dirname(__DIR__) . '/migrations/001_create_sessions.sql',
        ),
        $configuration,
        $clock,
    );
    $subscriber = new DatabaseSessionSubscriber($sessionManager);
    $kernel = new DummyKernel();

    $expired = $sessionManager->resolve(null);
    $clock->advance(11);
    $request = Request::create('/api/chat', 'POST', [], [
        DatabaseSessionSubscriber::COOKIE_NAME => $expired->id,
    ]);

    $subscriber->onKernelRequest(new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST));
    $response = new Response();
    $subscriber->onKernelResponse(new ResponseEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response));

    $sessionRepository = repository($databasePath);
    $sessions = $sessionRepository->all();
    $cookie = $response->headers->getCookies()[0] ?? null;

    assertSameValue(null, $sessionRepository->find($expired->id), 'Expired cookie session should be deleted during request handling.');
    assertSameValue(1, count($sessions), 'Expired request should leave only the replacement session.');
    assertTrueValue($cookie !== null, 'Response should include the replacement session cookie.');
    assertTrueValue($cookie->getValue() !== $expired->id, 'Replacement cookie must not reuse the expired session id.');
    assertSameValue($sessions[0]->id, $cookie->getValue(), 'Replacement cookie should point to the stored replacement session.');
});

test('missing session id creates a new session', function (): void {
    $databasePath = tempDatabasePath();
    $clock = new MutableClock(new DateTimeImmutable('2026-06-08T10:00:00+00:00'));
    $created = manager($databasePath, $clock, 60)->resolve('missing-session-id');

    assertSameValue($created->id, repository($databasePath)->all()[0]->id, 'Missing session id should create and store a new session.');
});

test('session lifetime is read from environment-backed configuration value', function (): void {
    putenv('SESSION_LIFETIME=45');
    $configuration = new SessionConfiguration((int) getenv('SESSION_LIFETIME'), 'sqlite:///:memory:');

    assertSameValue(45, $configuration->lifetimeSeconds, 'Configuration should expose SESSION_LIFETIME.');
});

test('OpenAI request configuration defaults are applied when omitted', function (): void {
    $configuration = new OpenAiConfiguration('test-key', 'gpt-5.5', 'https://api.openai.com/v1');

    assertSameValue('medium', $configuration->reasoningEffort, 'Default reasoning effort should be medium.');
    assertSameValue('medium', $configuration->textVerbosity, 'Default text verbosity should be medium.');
    assertSameValue(4000, $configuration->maxOutputTokens, 'Default max output tokens should be 4000.');
});

test('OpenAI request configuration accepts valid configured values', function (): void {
    $configuration = new OpenAiConfiguration(
        'test-key',
        'gpt-5.5',
        'https://api.openai.com/v1',
        'high',
        'low',
        '1200',
    );

    assertSameValue('high', $configuration->reasoningEffort, 'Configured reasoning effort should be exposed.');
    assertSameValue('low', $configuration->textVerbosity, 'Configured text verbosity should be exposed.');
    assertSameValue(1200, $configuration->maxOutputTokens, 'Configured max output tokens should be exposed as an integer.');
});

test('OpenAI request configuration rejects invalid values clearly', function (): void {
    assertThrows(InvalidArgumentException::class, function (): void {
        new OpenAiConfiguration('test-key', 'gpt-5.5', 'https://api.openai.com/v1', 'extreme');
    }, 'Invalid reasoning effort should fail configuration.');

    assertThrows(InvalidArgumentException::class, function (): void {
        new OpenAiConfiguration('test-key', 'gpt-5.5', 'https://api.openai.com/v1', 'medium', 'verbose');
    }, 'Invalid text verbosity should fail configuration.');

    assertThrows(InvalidArgumentException::class, function (): void {
        new OpenAiConfiguration('test-key', 'gpt-5.5', 'https://api.openai.com/v1', 'medium', 'medium', '0');
    }, 'Non-positive max output tokens should fail configuration.');
});

test('OpenAI Responses API request body includes configured GPT-5.5 parameters', function (): void {
    $client = new HttpOpenAiClient(new OpenAiConfiguration(
        'test-key',
        'gpt-5.5',
        'https://api.openai.com/v1',
        'minimal',
        'high',
        900,
    ));

    $body = streamingResponseBody($client, 'conv_123', 'Instructions', 'Message');

    assertSameValue('gpt-5.5', $body['model'] ?? null, 'Response body should include configured model.');
    assertSameValue(['effort' => 'minimal'], $body['reasoning'] ?? null, 'Response body should include configured reasoning effort.');
    assertSameValue(['verbosity' => 'high'], $body['text'] ?? null, 'Response body should include configured text verbosity.');
    assertSameValue(900, $body['max_output_tokens'] ?? null, 'Response body should include configured max output tokens.');
    assertSameValue(true, $body['stream'] ?? null, 'Response body should keep streaming enabled.');
});
