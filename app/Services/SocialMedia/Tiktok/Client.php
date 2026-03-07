<?php

namespace App\Services\SocialMedia\Tiktok;

use App\Enums\SocialNetwork;
use App\Services\Auth\SocialiteClient;
use Illuminate\Support\Collection;

class Client implements SocialiteClient
{
    public function __construct(
        protected string $access_token,
        protected ?string $user_id = null,
    ) {}

    public function getLongLivedToken(): array
    {
        return [
            'access_token' => $this->access_token,
        ];
    }

    public function accounts(): Collection
    {
        $payload = $this->connector()->get('/v2/user/info/', [
            'fields' => implode(',', [
                'open_id',
                'display_name',
                'avatar_url',
                'bio_description',
                'follower_count',
                'following_count',
                'video_count',
            ]),
        ]);

        $user = $payload['user'] ?? [];
        $socialNetworkUserId = $user['open_id'] ?? $this->user_id;
        if (! is_string($socialNetworkUserId) || $socialNetworkUserId === '') {
            return collect();
        }

        $name = (string) ($user['display_name'] ?? '');
        $username = $name !== '' ? $name : $socialNetworkUserId;
        $biography = (string) ($user['bio_description'] ?? '');

        return collect([
            [
                'social_network' => SocialNetwork::Tiktok->value,
                'social_network_user_id' => $socialNetworkUserId,
                'name' => $name !== '' ? $name : null,
                'username' => $username,
                'biography' => $biography !== '' ? $biography : null,
                'profile_picture_url' => $user['avatar_url'] ?? null,
                'followers_count' => (int) ($user['follower_count'] ?? 0),
                'following_count' => (int) ($user['following_count'] ?? 0),
                'media_count' => (int) ($user['video_count'] ?? 0),
                'access_token' => $this->access_token,
            ],
        ]);
    }

    protected function connector(): TikTokApiConnector
    {
        return app()->make(TikTokApiConnector::class, [
            'accessToken' => $this->access_token,
        ]);
    }
}
