<?php

namespace App\Services\Amis;

use App\Enums\SyncSource;
use App\Exceptions\SourceApi\SourceResponseException;
use App\Services\SourceApi\SourceApiClient;
use App\Services\SourceApi\SourcePage;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;

/**
 * AMIS public API (/api/v1/public). X-API-Key auth; lists return total/page/page_size/total_pages
 * at the top level with an endpoint-specific list key. Errors are { message, code, detail }.
 */
class AmisApiClient extends SourceApiClient
{
    public function source(): SyncSource
    {
        return SyncSource::Amis;
    }

    /**
     * Full contract objects (with items[]), newest contact_id first.
     */
    public function rentLands(int $page): SourcePage
    {
        return $this->page('/income/rent-land', 'rent_lands', $page);
    }

    /**
     * @return array<string, mixed>
     */
    public function rentLand(int $id): array
    {
        return $this->object("/income/rent-land/{$id}", 'rent_land');
    }

    public function rentLandQuantity(): int
    {
        $quantity = $this->get('/income/rent-land/quantity')['data']['quantity'] ?? null;

        if (! is_numeric($quantity)) {
            throw new SourceResponseException(SyncSource::Amis, 'UNEXPECTED_SHAPE', 'AMIS /income/rent-land/quantity did not return data.quantity.');
        }

        return (int) $quantity;
    }

    /**
     * Companies with nested rent_lands[] (contact_id, shareholding_pct) — the only contract → company link.
     */
    public function companies(int $page): SourcePage
    {
        return $this->page('/companies', 'companies', $page);
    }

    /**
     * @return array<string, mixed>
     */
    public function company(int $id): array
    {
        return $this->object("/companies/{$id}", 'company');
    }

    protected function authenticatedRequest(PendingRequest $request): PendingRequest
    {
        $key = $this->config['key'] ?? null;

        if (blank($key)) {
            throw $this->missingCredential('AMIS_API_KEY');
        }

        return $request->withHeaders(['X-API-Key' => $key]);
    }

    protected function errorDetails(Response $response): array
    {
        return [
            'code' => is_string($response->json('code')) ? $response->json('code') : null,
            'message' => is_string($response->json('message')) ? $response->json('message') : null,
            'detail' => is_string($response->json('detail')) ? $response->json('detail') : null,
        ];
    }

    private function page(string $path, string $listKey, int $page): SourcePage
    {
        $body = $this->get($path, ['page' => $page, 'page_size' => $this->pageSize()]);
        $items = $body[$listKey] ?? null;

        if (! is_array($items) || ! isset($body['total'], $body['total_pages'])) {
            throw new SourceResponseException(SyncSource::Amis, 'UNEXPECTED_SHAPE', "AMIS {$path} did not return {$listKey} with pagination.");
        }

        return new SourcePage(array_values($items), (int) ($body['page'] ?? $page), max(1, (int) $body['total_pages']), (int) $body['total']);
    }

    /**
     * @return array<string, mixed>
     */
    private function object(string $path, string $key): array
    {
        $object = $this->get($path)[$key] ?? null;

        if (! is_array($object)) {
            throw new SourceResponseException(SyncSource::Amis, 'UNEXPECTED_SHAPE', "AMIS {$path} did not return {$key}.");
        }

        return $object;
    }
}
