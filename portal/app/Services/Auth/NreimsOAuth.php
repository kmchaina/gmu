<?php

namespace App\Services\Auth;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * The NREIMS side of single sign-on: NREIMS runs Laravel Passport, so this is
 * the standard authorization-code grant against /oauth/authorize,
 * /oauth/token and /api/user.
 *
 * Done with Laravel's HTTP client rather than a Socialite driver: it is three
 * requests, and this way TLS verification stays on and nothing about the
 * token is ever logged.
 */
class NreimsOAuth
{
    public function enabled(): bool
    {
        return filled(config('services.nreims.base_url'))
            && filled(config('services.nreims.client_id'))
            && filled(config('services.nreims.client_secret'))
            && filled(config('services.nreims.redirect'));
    }

    public function authorizeUrl(string $state): string
    {
        return $this->url('/oauth/authorize') . '?' . http_build_query([
            'client_id'     => config('services.nreims.client_id'),
            'redirect_uri'  => config('services.nreims.redirect'),
            'response_type' => 'code',
            'scope'         => '',
            'state'         => $state,
        ]);
    }

    /** Exchange the authorization code for an access token. */
    public function accessToken(string $code): string
    {
        $response = Http::asForm()->acceptJson()->timeout(15)->post($this->url('/oauth/token'), [
            'grant_type'    => 'authorization_code',
            'client_id'     => config('services.nreims.client_id'),
            'client_secret' => config('services.nreims.client_secret'),
            'redirect_uri'  => config('services.nreims.redirect'),
            'code'          => $code,
        ]);

        $token = $response->successful() ? $response->json('access_token') : null;

        if (! is_string($token) || $token === '') {
            throw new RuntimeException('NREIMS did not issue an access token (HTTP ' . $response->status() . ').');
        }

        return $token;
    }

    /**
     * The signed-in NREIMS user, reduced to the fields this portal uses.
     *
     * @return array{id:int, name:string, email:?string, phone:?string}
     */
    public function user(string $token): array
    {
        $response = Http::withToken($token)->acceptJson()->timeout(15)->get($this->url('/api/user'));

        $raw = $response->successful() ? $response->json() : null;

        if (! is_array($raw) || empty($raw['id'])) {
            throw new RuntimeException('NREIMS did not return the signed-in user (HTTP ' . $response->status() . ').');
        }

        $name = trim(implode(' ', array_filter([
            $raw['first_name'] ?? null, $raw['middle_name'] ?? null, $raw['last_name'] ?? null,
        ])));

        return [
            'id'    => (int) $raw['id'],
            'name'  => $name !== '' ? $name : ($raw['name'] ?? ''),
            'email' => isset($raw['email']) ? mb_strtolower(trim($raw['email'])) : null,
            'phone' => $raw['phone'] ?? null,
        ];
    }

    private function url(string $path): string
    {
        return rtrim((string) config('services.nreims.base_url'), '/') . $path;
    }
}
