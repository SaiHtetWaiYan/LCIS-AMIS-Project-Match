<?php

namespace Tests\Feature\Services\Lcis;

use App\Exceptions\SourceApi\SourceAuthenticationException;
use App\Exceptions\SourceApi\SourceNotFoundException;
use App\Exceptions\SourceApi\SourceRateLimitedException;
use App\Exceptions\SourceApi\SourceUnavailableException;
use App\Services\Lcis\LcisApiClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Tests\TestCase;

class LcisApiClientTest extends TestCase
{
    public function test_fetches_a_project_page_with_the_bearer_token_and_100_per_page(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'lcis.example.test/api/v1/projects*' => Http::response([
                'data' => [$this->fixture('lcis/project.json')],
                'meta' => ['total' => 101, 'per_page' => 100, 'current_page' => 2, 'last_page' => 2],
            ]),
        ]);

        $page = app(LcisApiClient::class)->projects(2);

        $this->assertSame(3101, $page->items[0]['id']);
        $this->assertSame(2, $page->page);
        $this->assertSame(101, $page->total);
        $this->assertTrue($page->isLast());
        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://lcis.example.test/api/v1/projects?page=2&per_page=100'
            && $request->hasHeader('Authorization', 'Bearer test-lcis-token'));
    }

    public function test_ping_needs_no_token(): void
    {
        config(['services.lcis.token' => null]);
        Http::preventStrayRequests();
        Http::fake(['lcis.example.test/api/v1/ping' => Http::response(['status' => 'ok'])]);

        $this->assertTrue(app(LcisApiClient::class)->ping());
        Http::assertSent(fn (Request $request): bool => ! $request->hasHeader('Authorization'));
    }

    public function test_throws_authentication_exception_with_the_message_from_a_401(): void
    {
        Http::preventStrayRequests();
        Http::fake(['lcis.example.test/api/v1/projects*' => Http::response(['message' => 'Invalid or inactive token'], 401)]);

        try {
            app(LcisApiClient::class)->projects(1);
            $this->fail('Expected SourceAuthenticationException.');
        } catch (SourceAuthenticationException $exception) {
            $this->assertSame('HTTP_401', $exception->errorCode);
            $this->assertSame('Invalid or inactive token', $exception->getMessage());
            $this->assertSame(401, $exception->httpStatus);
        }

        Http::assertSentCount(1);
    }

    public function test_reads_the_error_envelope_of_a_404(): void
    {
        Http::preventStrayRequests();
        Http::fake(['lcis.example.test/api/v1/projects/77' => Http::response(['error' => ['code' => 'NOT_FOUND', 'message' => 'Project with ID 77 not found', 'status' => 404]], 404)]);

        $this->expectException(SourceNotFoundException::class);
        $this->expectExceptionMessage('Project with ID 77 not found');

        app(LcisApiClient::class)->project(77);
    }

    public function test_does_not_call_the_api_when_no_token_is_configured(): void
    {
        config(['services.lcis.token' => '']);
        Http::preventStrayRequests();
        Http::fake();

        try {
            app(LcisApiClient::class)->projects(1);
            $this->fail('Expected SourceAuthenticationException.');
        } catch (SourceAuthenticationException $exception) {
            $this->assertSame('CREDENTIAL_MISSING', $exception->errorCode);
        }

        Http::assertNothingSent();
    }

    public function test_waits_for_retry_after_on_429_then_succeeds(): void
    {
        Sleep::fake();
        Http::preventStrayRequests();
        Http::fake([
            'lcis.example.test/api/v1/companies*' => Http::sequence()
                ->push(['error' => ['code' => 'TOO_MANY_REQUESTS', 'message' => 'Slow down', 'status' => 429]], 429, ['Retry-After' => '7'])
                ->push(['data' => [], 'meta' => ['total' => 0, 'per_page' => 100, 'current_page' => 1, 'last_page' => 1]]),
        ]);

        $page = app(LcisApiClient::class)->companies(1);

        $this->assertSame([], $page->items);
        Sleep::assertSequence([Sleep::for(7)->seconds()]);
        Http::assertSentCount(2);
    }

    public function test_gives_up_after_repeated_429_responses(): void
    {
        Sleep::fake();
        Http::preventStrayRequests();
        Http::fake(['lcis.example.test/api/v1/projects*' => Http::response(['error' => ['code' => 'TOO_MANY_REQUESTS', 'message' => 'Slow down', 'status' => 429]], 429)]);

        $this->expectException(SourceRateLimitedException::class);

        try {
            app(LcisApiClient::class)->projects(1);
        } finally {
            Http::assertSentCount(4);
        }
    }

    public function test_backs_off_on_server_errors_then_throws_unavailable(): void
    {
        Sleep::fake();
        Http::preventStrayRequests();
        Http::fake(['lcis.example.test/api/v1/projects*' => Http::response('Bad gateway', 502)]);

        try {
            app(LcisApiClient::class)->projects(1);
            $this->fail('Expected SourceUnavailableException.');
        } catch (SourceUnavailableException $exception) {
            $this->assertSame('HTTP_502', $exception->errorCode);
        }

        Http::assertSentCount(4);
        Sleep::assertSequence([Sleep::for(2)->seconds(), Sleep::for(5)->seconds(), Sleep::for(15)->seconds()]);
    }

    public function test_retries_connection_failures_then_throws_unavailable(): void
    {
        Sleep::fake();
        Http::preventStrayRequests();
        Http::fake(['lcis.example.test/api/v1/projects*' => Http::failedConnection()]);

        try {
            app(LcisApiClient::class)->projects(1);
            $this->fail('Expected SourceUnavailableException.');
        } catch (SourceUnavailableException $exception) {
            $this->assertSame('CONNECTION_FAILED', $exception->errorCode);
        }

        Sleep::assertSleptTimes(3);
    }

    public function test_spaces_consecutive_requests_to_respect_the_rate_limit(): void
    {
        config(['services.lcis.min_request_interval_ms' => 500]);
        Sleep::fake();
        Http::preventStrayRequests();
        Http::fake(['lcis.example.test/api/v1/companies*' => Http::response(['data' => [], 'meta' => ['total' => 0, 'per_page' => 100, 'current_page' => 1, 'last_page' => 1]])]);
        $client = app(LcisApiClient::class);

        $client->companies(1);
        $client->companies(2);

        Sleep::assertSleptTimes(1);
    }
}
