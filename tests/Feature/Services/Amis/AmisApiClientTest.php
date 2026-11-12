<?php

namespace Tests\Feature\Services\Amis;

use App\Exceptions\SourceApi\SourceAuthenticationException;
use App\Exceptions\SourceApi\SourceResponseException;
use App\Services\Amis\AmisApiClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AmisApiClientTest extends TestCase
{
    public function test_fetches_rent_lands_with_the_api_key_header_and_page_size_100(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'amis.example.test/api/v1/public/income/rent-land?*' => Http::response([
                'message' => 'Rent land contracts retrieved successfully',
                'rent_lands' => [$this->fixture('amis/rent_land_legacy_partner.json')],
                'total' => 1,
                'page' => 1,
                'page_size' => 100,
                'total_pages' => 1,
            ]),
        ]);

        $page = app(AmisApiClient::class)->rentLands(1);

        $this->assertSame(252, $page->items[0]['contact_id']);
        $this->assertSame(1, $page->total);
        $this->assertTrue($page->isLast());
        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://amis.example.test/api/v1/public/income/rent-land?page=1&page_size=100'
            && $request->hasHeader('X-API-Key', 'test-amis-key')
            && ! $request->hasHeader('Authorization'));
    }

    public function test_reads_the_contract_quantity(): void
    {
        Http::preventStrayRequests();
        Http::fake(['amis.example.test/api/v1/public/income/rent-land/quantity' => Http::response(['message' => 'ok', 'data' => ['quantity' => 856]])]);

        $this->assertSame(856, app(AmisApiClient::class)->rentLandQuantity());
    }

    public function test_treats_an_empty_page_with_zero_total_pages_as_the_last_page(): void
    {
        Http::preventStrayRequests();
        Http::fake(['amis.example.test/api/v1/public/companies?*' => Http::response(['companies' => [], 'total' => 0, 'page' => 1, 'page_size' => 100, 'total_pages' => 0])]);

        $page = app(AmisApiClient::class)->companies(1);

        $this->assertSame([], $page->items);
        $this->assertTrue($page->isLast());
    }

    /**
     * @return array<string, array{int, string, string}>
     */
    public static function apiKeyErrors(): array
    {
        return [
            'invalid or wrong domain' => [401, 'API_KEY_INVALID', 'Invalid API key'],
            'inactive' => [403, 'API_KEY_INACTIVE', 'API key is inactive'],
            'expired' => [403, 'API_KEY_EXPIRED', 'API key has expired'],
        ];
    }

    #[DataProvider('apiKeyErrors')]
    public function test_reports_the_amis_api_key_error_code(int $status, string $code, string $message): void
    {
        Http::preventStrayRequests();
        Http::fake(['amis.example.test/*' => Http::response(['message' => $message, 'code' => $code, 'detail' => 'details'], $status)]);

        try {
            app(AmisApiClient::class)->rentLands(1);
            $this->fail('Expected SourceAuthenticationException.');
        } catch (SourceAuthenticationException $exception) {
            $this->assertSame($code, $exception->errorCode);
            $this->assertSame($message, $exception->getMessage());
            $this->assertSame('details', $exception->detail);
        }

        Http::assertSentCount(1);
    }

    public function test_rejects_a_list_response_without_the_expected_key(): void
    {
        Http::preventStrayRequests();
        Http::fake(['amis.example.test/api/v1/public/income/rent-land?*' => Http::response(['data' => [], 'total' => 0])]);

        try {
            app(AmisApiClient::class)->rentLands(1);
            $this->fail('Expected SourceResponseException.');
        } catch (SourceResponseException $exception) {
            $this->assertSame('UNEXPECTED_SHAPE', $exception->errorCode);
        }
    }
}
