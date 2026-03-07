<?php

namespace App\Services\SocialMedia\Instagram;

use App\Enums\MediaType;
use App\Enums\SocialNetwork;
use App\Services\Auth\SocialiteClient;
use FacebookAds\Api;
use FacebookAds\Object\IGMedia;
use FacebookAds\Object\IGUser;
use FacebookAds\Object\InstagramInsightsResult;
use FacebookAds\Object\Page;
use FacebookAds\Object\User;
use Illuminate\Support\Collection;

class Client implements SocialiteClient
{
    protected Api $api;

    public function __construct(protected string $access_token, protected ?string $user_id = null)
    {
        $this->api = Api::init(config('services.facebook.client_id'), config('services.facebook.client_secret'), $access_token);
        $this->api->setDefaultGraphVersion('24.0');
    }

    public function getLongLivedToken(): array
    {
        return $this->api->call('/oauth/access_token', params: [
            'client_id' => config('services.facebook.client_id'),
            'client_secret' => config('services.facebook.client_secret'),
            'grant_type' => 'fb_exchange_token',
            'fb_exchange_token' => $this->access_token,
        ])->getContent();
    }

    public function accounts(): Collection
    {
        $user = new User($this->user_id);
        $accounts = $user->getAccounts([
            'id',
            'name',
            'access_token',
            'category',
            'followers_count',
            'verification_status',
            'instagram_business_account{id,username,name,biography,profile_picture_url,followers_count,follows_count,media_count}',

        ]);

        return collect($accounts->getArrayCopy())
            ->filter(fn (Page $page) => isset($page->getData()['instagram_business_account']['id']))
            ->map(function (Page $page) {
                $account = $page->getData();
                $ig = $account['instagram_business_account'];

                return [
                    'social_network' => SocialNetwork::Instagram->value,
                    'social_network_user_id' => $ig['id'],
                    'name' => $ig['name'],
                    'username' => $ig['username'],
                    'biography' => trim($ig['biography'] ?? ''),
                    'profile_picture_url' => $ig['profile_picture_url'],
                    'followers_count' => $ig['followers_count'],
                    'following_count' => $ig['follows_count'],
                    'media_count' => $ig['media_count'],
                    'access_token' => $account['access_token'],
                ];
            });
    }

    public function getAllMedia(): array
    {
        $igUser = new IGUser($this->user_id);
        $cursor = $igUser->getMedia([
            'id',
            'caption',
            'media_type',
            'media_product_type',
            'media_url',
            'thumbnail_url',
            'permalink',
            'timestamp',
            'like_count',
            'comments_count',
        ]);
        $cursor->setUseImplicitFetch(true);
        $allMedia = [];
        foreach ($cursor as $media) {
            $allMedia[] = $media->exportAllData();
        }

        return $allMedia;
    }

    public function getMedia(int $media_id): array
    {
        $media = new IGMedia($media_id)->getSelf([
            'id',
            'caption',
            'media_type',
            'media_url',
            'thumbnail_url',
            'permalink',
            'timestamp',
            'like_count',
            'comments_count',
        ])->exportAllData();

        return [
            'id' => $media['id'],
            'caption' => $media['caption'],
            'media_type' => $media['media_type'],
            'media_url' => $media['media_url'],
            'thumbnail_url' => $media['thumbnail_url'] ?? null,
            'permalink' => $media['permalink'],
            'timestamp' => $media['timestamp'],
            'like_count' => $media['like_count'],
            'comments_count' => $media['comments_count'],
        ];
    }

    public function getProfile(): array
    {
        $igUser = new IGUser($this->user_id);
        $profile = $igUser->getSelf([
            'id',
            'username',
            'name',
            'biography',
            'profile_picture_url',
            'followers_count',
            'follows_count',
            'media_count',
        ])->exportAllData();

        return [
            'id' => $profile['id'],
            'username' => $profile['username'] ?? null,
            'name' => $profile['name'] ?? null,
            'biography' => $profile['biography'] ?? null,
            'profile_picture_url' => $profile['profile_picture_url'] ?? null,
            'followers_count' => $profile['followers_count'] ?? 0,
            'following_count' => $profile['follows_count'] ?? 0,
            'media_count' => $profile['media_count'] ?? 0,
        ];
    }

    public function getMediaInsights(string $id, MediaType $type): Collection
    {
        $media = new IGMedia($id)->getInsights(params: [
            'metric' => $type->metrics(),
        ]);

        return collect($media->getArrayCopy())
            ->map(fn (InstagramInsightsResult $metric) => [
                'name' => $metric->getData()['name'],
                'values' => $metric->getData()['values'][0]['value'] ?? 0,
            ])->pluck('values', 'name');
    }

    public function getAudienceDemographics(): array
    {
        $igUser = new IGUser($this->user_id);
        $result = [];
        foreach (['country', 'gender', 'age', 'city'] as $breakdown) {
            $cursor = $igUser->getInsights(params: [
                'metric' => [
                    'follower_demographics',
                ],
                'period' => 'lifetime',
                'metric_type' => 'total_value',
                'breakdown' => $breakdown,
            ]);

            foreach ($cursor as $insight) {
                $result[$breakdown] = collect($insight->getData()['total_value']['breakdowns'][0]['results'])
                    ->mapWithKeys(function ($point) {
                        return [
                            $point['dimension_values'][0] => $point['value'],
                        ];
                    })->all();
            }
        }

        return $result;
    }

    public function getStories(): Collection
    {
        $igUser = new IGUser($this->user_id);
        $cursor = $igUser->getStories([
            'id',
            'caption',
            'media_type',
            'media_url',
            'thumbnail_url',
            'permalink',
            'timestamp',
        ], [
            'limit' => 100,
        ]);

        return collect($cursor->getArrayCopy())->map(fn (IGMedia $media): array => $media->exportAllData())->values();
    }

    public function refreshLongLivedToken(): array
    {
        $response = $this->api->call(
            '/oauth/access_token',
            'GET',
            [
                'grant_type' => 'fb_exchange_token',
                'client_id' => config('services.facebook.client_id'),
                'client_secret' => config('services.facebook.client_secret'),
                'fb_exchange_token' => $this->access_token,
            ]
        );

        return $response->getContent();
    }
}
