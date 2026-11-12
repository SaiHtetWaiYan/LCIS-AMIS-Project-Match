<?php

namespace App\Services\Lcis;

use App\Enums\SyncSource;
use App\Exceptions\SourceApi\SourceResponseException;
use App\Services\SourceApi\SourceApiClient;
use App\Services\SourceApi\SourcePage;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;

/**
 * LCIS read-only API (/api/v1). Bearer token auth; list envelope { data, meta }.
 * Errors are { error: { code, message, status } }, except auth failures, which are { message }.
 */
class LcisApiClient extends SourceApiClient
{
    public function source(): SyncSource
    {
        return SyncSource::Lcis;
    }

    public function ping(): bool
    {
        return ($this->get('/ping', authenticated: false)['status'] ?? null) === 'ok';
    }

    public function projects(int $page): SourcePage
    {
        return $this->page('/projects', $page);
    }

    /**
     * @return array<string, mixed>
     */
    public function project(int $id): array
    {
        return $this->data($this->get("/projects/{$id}"), "/projects/{$id}");
    }

    public function companies(int $page): SourcePage
    {
        return $this->page('/companies', $page);
    }

    /**
     * @return array<string, mixed>
     */
    public function company(int $id): array
    {
        return $this->data($this->get("/companies/{$id}"), "/companies/{$id}");
    }

    protected function authenticatedRequest(PendingRequest $request): PendingRequest
    {
        $token = $this->config['token'] ?? null;

        if (blank($token)) {
            throw $this->missingCredential('LCIS_API_TOKEN');
        }

        return $request->withToken($token);
    }

    protected function errorDetails(Response $response): array
    {
        $error = $response->json('error');

        return [
            'code' => is_array($error) ? ($error['code'] ?? null) : null,
            'message' => is_array($error) ? ($error['message'] ?? null) : $response->json('message'),
            'detail' => null,
        ];
    }

    private function page(string $path, int $page): SourcePage
    {
        $body = $this->get($path, ['page' => $page, 'per_page' => $this->pageSize()]);
        $items = $body['data'] ?? null;
        $meta = $body['meta'] ?? null;

        if (! is_array($items) || ! is_array($meta) || ! isset($meta['last_page'], $meta['total'])) {
            throw new SourceResponseException(SyncSource::Lcis, 'UNEXPECTED_SHAPE', "LCIS {$path} did not return data and meta.");
        }

        return new SourcePage(array_values($items), (int) ($meta['current_page'] ?? $page), (int) $meta['last_page'], (int) $meta['total']);
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    private function data(array $body, string $path): array
    {
        if (! is_array($body['data'] ?? null)) {
            throw new SourceResponseException(SyncSource::Lcis, 'UNEXPECTED_SHAPE', "LCIS {$path} did not return data.");
        }

        return $body['data'];
    }
}
