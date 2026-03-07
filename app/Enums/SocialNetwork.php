<?php

namespace App\Enums;

use App\Services\Auth\SocialiteClient;
use App\Services\SocialMedia\Instagram\Client as InstagramClient;
use App\Services\SocialMedia\Tiktok\Client as TikTokClient;
use LogicException;

enum SocialNetwork: string
{
    case Tiktok = 'tiktok';
    case Instagram = 'instagram';
    case Youtube = 'youtube';
    case Twitch = 'twitch';

    public function oauthScopes(): array
    {
        return match ($this) {
            self::Tiktok => [
                'user.info.basic',
                'user.info.profile',
                'user.info.stats',
            ],
            self::Instagram => [
                'instagram_basic',
                'instagram_manage_insights',
                'pages_show_list',
                'pages_read_engagement',
            ],
            default => [],
        };
    }

    public function socialiteDriver(): string
    {
        return match ($this) {
            self::Instagram => 'facebook',
            default => $this->value,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Tiktok => 'TikTok',
            self::Instagram => 'Instagram',
            self::Youtube => 'YouTube',
            self::Twitch => 'Twitch',
        };
    }

    public function accountConnectionRedirectRoute(): string
    {
        return match ($this) {
            self::Instagram => 'instagram-accounts.index',
            default => 'dashboard',
        };
    }

    public function socialiteClient(string $accessToken, ?string $userId = null): SocialiteClient
    {
        return match ($this) {
            self::Instagram => app()->make(InstagramClient::class, [
                'access_token' => $accessToken,
                'user_id' => $userId,
            ]),
            self::Tiktok => app()->make(TikTokClient::class, [
                'access_token' => $accessToken,
                'user_id' => $userId,
            ]),
            default => throw new LogicException("{$this->label()} social login client is not configured."),
        };
    }
}
