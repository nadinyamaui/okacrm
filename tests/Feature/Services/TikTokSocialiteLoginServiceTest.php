<?php

use App\Enums\SocialNetwork;
use App\Exceptions\Auth\SocialAuthenticationException;
use App\Models\SocialAccount;
use App\Models\User;
use App\Services\Auth\SocialiteLoginService;
use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Facades\Socialite;

it('redirects to tiktok provider with read-only account scopes', function (): void {
    $expectedRedirect = redirect('https://www.tiktok.com/v2/auth/authorize');

    Socialite::shouldReceive('driver')
        ->once()
        ->with('tiktok')
        ->andReturnSelf();
    Socialite::shouldReceive('scopes')
        ->once()
        ->with([
            'user.info.basic',
            'user.info.profile',
            'user.info.stats',
        ])
        ->andReturnSelf();
    Socialite::shouldReceive('redirect')
        ->once()
        ->andReturn($expectedRedirect);

    $response = app(SocialiteLoginService::class)
        ->usingDriver(SocialNetwork::Tiktok)
        ->redirectToProvider();

    expect($response)->toBeInstanceOf(RedirectResponse::class)
        ->and($response->getTargetUrl())->toBe('https://www.tiktok.com/v2/auth/authorize');
});

it('creates user and tiktok social account when provider email is unavailable', function (): void {
    $socialiteUser = new class
    {
        public string $token = 'tiktok-short-token';

        public function getId(): string
        {
            return 'tt-user-1';
        }

        public function getName(): ?string
        {
            return null;
        }

        public function getEmail(): ?string
        {
            return null;
        }
    };

    Socialite::shouldReceive('driver')
        ->once()
        ->with('tiktok')
        ->andReturnSelf();
    Socialite::shouldReceive('user')
        ->once()
        ->andReturn($socialiteUser);

    $service = \Mockery::mock(SocialiteLoginService::class)
        ->makePartial()
        ->shouldAllowMockingProtectedMethods();
    $service->shouldReceive('exchangeToken')
        ->once()
        ->with($socialiteUser)
        ->andReturn(['access_token' => 'tiktok-long-token']);
    $service->shouldReceive('getAccounts')
        ->once()
        ->with('tt-user-1', 'tiktok-long-token')
        ->andReturn(collect([
            [
                'social_network' => SocialNetwork::Tiktok->value,
                'social_network_user_id' => 'tt-open-1',
                'username' => 'tt_creator',
                'name' => 'TT Creator',
                'biography' => 'TikTok bio',
                'profile_picture_url' => 'https://example.test/tt.jpg',
                'followers_count' => 321,
                'following_count' => 123,
                'media_count' => 56,
                'access_token' => 'tiktok-long-token',
            ],
        ]));

    $resolvedUser = $service->usingDriver(SocialNetwork::Tiktok)->createUserAndAccounts();

    expect($resolvedUser->socialite_user_type)->toBe('tiktok')
        ->and($resolvedUser->socialite_user_id)->toBe('tt-user-1')
        ->and($resolvedUser->name)->toBe('TikTok User')
        ->and($resolvedUser->email)->toBe('tiktok-tt-user-1@okacrm.local');
    $this->assertAuthenticatedAs($resolvedUser);
    $this->assertDatabaseHas('social_accounts', [
        'user_id' => $resolvedUser->id,
        'social_network' => SocialNetwork::Tiktok->value,
        'social_network_user_id' => 'tt-open-1',
        'username' => 'tt_creator',
    ]);
});

it('prevents linking tiktok accounts that belong to another user', function (): void {
    $conflictingUser = User::factory()->create();
    SocialAccount::factory()->create([
        'user_id' => $conflictingUser->id,
        'social_network' => SocialNetwork::Tiktok,
        'social_network_user_id' => 'tt-open-1',
    ]);

    $socialiteUser = new class
    {
        public string $token = 'tiktok-short-token';

        public function getId(): string
        {
            return 'tt-user-1';
        }

        public function getName(): string
        {
            return 'TikTok User';
        }

        public function getEmail(): ?string
        {
            return null;
        }
    };

    Socialite::shouldReceive('driver')
        ->once()
        ->with('tiktok')
        ->andReturnSelf();
    Socialite::shouldReceive('user')
        ->once()
        ->andReturn($socialiteUser);

    $service = \Mockery::mock(SocialiteLoginService::class)
        ->makePartial()
        ->shouldAllowMockingProtectedMethods();
    $service->shouldReceive('exchangeToken')
        ->once()
        ->with($socialiteUser)
        ->andReturn(['access_token' => 'tiktok-long-token']);
    $service->shouldReceive('getAccounts')
        ->once()
        ->with('tt-user-1', 'tiktok-long-token')
        ->andReturn(collect([
            [
                'social_network' => SocialNetwork::Tiktok->value,
                'social_network_user_id' => 'tt-open-1',
                'username' => 'tt_creator',
                'name' => 'TT Creator',
                'biography' => 'TikTok bio',
                'profile_picture_url' => 'https://example.test/tt.jpg',
                'followers_count' => 321,
                'following_count' => 123,
                'media_count' => 56,
                'access_token' => 'tiktok-long-token',
            ],
        ]));

    expect(fn () => $service->usingDriver(SocialNetwork::Tiktok)->createUserAndAccounts())
        ->toThrow(SocialAuthenticationException::class, 'One or more TikTok accounts are linked to a different user.');
});
