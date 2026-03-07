<?php

use App\Enums\SocialNetwork;
use App\Services\SocialMedia\Tiktok\Client;
use App\Services\SocialMedia\Tiktok\TikTokApiConnector;

it('maps tiktok user info payload to social account attributes', function (): void {
    $connector = \Mockery::mock(TikTokApiConnector::class);
    $connector->shouldReceive('get')
        ->once()
        ->with('/v2/user/info/', [
            'fields' => 'open_id,display_name,avatar_url,bio_description,follower_count,following_count,video_count',
        ])
        ->andReturn([
            'user' => [
                'open_id' => 'tt-open-1',
                'display_name' => 'creator_name',
                'avatar_url' => 'https://example.test/avatar.jpg',
                'bio_description' => 'Creator bio',
                'follower_count' => 451,
                'following_count' => 89,
                'video_count' => 24,
            ],
        ]);

    app()->bind(TikTokApiConnector::class, fn (): TikTokApiConnector => $connector);

    $account = (new Client(access_token: 'token-123'))->accounts()->first();

    expect($account)->toBeArray()
        ->and($account['social_network'])->toBe(SocialNetwork::Tiktok->value)
        ->and($account['social_network_user_id'])->toBe('tt-open-1')
        ->and($account['username'])->toBe('creator_name')
        ->and($account['followers_count'])->toBe(451)
        ->and($account['media_count'])->toBe(24)
        ->and($account['access_token'])->toBe('token-123');
});

it('returns no accounts when tiktok response has no identifier', function (): void {
    $connector = \Mockery::mock(TikTokApiConnector::class);
    $connector->shouldReceive('get')
        ->once()
        ->andReturn([
            'user' => [
                'display_name' => 'creator_name',
            ],
        ]);

    app()->bind(TikTokApiConnector::class, fn (): TikTokApiConnector => $connector);

    $accounts = (new Client(access_token: 'token-123'))->accounts();

    expect($accounts)->toHaveCount(0);
});
