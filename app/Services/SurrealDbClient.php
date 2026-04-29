<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class SurrealDbClient
{
    public function query(string $surql, array $params = []): array
    {
        $response = $this->http()
            ->withToken($this->token())
            ->withHeaders([
                'Surreal-NS' => config('surrealdb.namespace'),
                'Surreal-DB' => config('surrealdb.database'),
            ])
            ->withQueryParameters($params)
            ->withBody($surql, 'text/plain')
            ->post('/sql');

        if ($response->failed()) {
            throw new RuntimeException('SurrealDB query failed: '.$response->body());
        }

        $results = $response->json();
        
        // SurrealDB /sql returns an array of results for each statement
        return $results;
    }

    private function token(): string
    {
        return Cache::remember('surrealdb.token', now()->addMinutes(50), function (): string {
            $response = $this->http()->post('/signin', [
                'ns' => config('surrealdb.namespace'),
                'db' => config('surrealdb.database'),
                'user' => config('surrealdb.username'),
                'pass' => config('surrealdb.password'),
            ]);

            if ($response->failed()) {
                throw new RuntimeException('SurrealDB signin failed: '.$response->body());
            }

            $token = $response->json('token');

            if (! is_string($token) || $token === '') {
                throw new RuntimeException('SurrealDB signin did not return a token.');
            }

            return $token;
        });
    }

    private function http(): PendingRequest
    {
        return Http::baseUrl(config('surrealdb.endpoint'))
            ->acceptJson()
            ->timeout(config('surrealdb.timeout'));
    }
}
