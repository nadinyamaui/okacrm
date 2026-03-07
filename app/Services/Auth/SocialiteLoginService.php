<?php

namespace App\Services\Auth;

use App\Enums\SocialNetwork;
use App\Exceptions\Auth\SocialAuthenticationException;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Laravel\Socialite\Facades\Socialite;

class SocialiteLoginService
{
    private SocialNetwork $driver = SocialNetwork::Instagram;

    public function usingDriver(SocialNetwork $driver): self
    {
        $this->driver = $driver;

        return $this;
    }

    public function driverLabel(): string
    {
        return $this->driver->label();
    }

    public function redirectToProvider(): RedirectResponse
    {
        return Socialite::driver($this->driver->socialiteDriver())
            ->scopes($this->driver->oauthScopes())
            ->redirect();
    }

    public function createUserAndAccounts(): User
    {
        $socialiteUser = Socialite::driver($this->driver->socialiteDriver())->user();
        if (! $socialiteUser->getId()) {
            throw new SocialAuthenticationException("{$this->driverLabel()} did not return required account information.");
        }
        $this->ensureNoConflictingEmailUser($socialiteUser);
        $existingUser = $this->findExistingSocialiteUser($socialiteUser);
        $token = $this->exchangeToken($socialiteUser);
        $accounts = $this->getAccounts($socialiteUser->getId(), $token['access_token']);
        $this->ensureSocialAccountsBelongToUser($existingUser, $accounts);
        $user = $this->createUpdateUser($socialiteUser);
        auth()->login($user);
        $this->upsertSocialAccounts($accounts, $user);

        return $user;
    }

    public function createSocialAccountsForLoggedUser(): User
    {
        $user = auth()->user();
        if (! $user instanceof User) {
            throw new SocialAuthenticationException("You must be logged in to link {$this->driverLabel()} accounts.");
        }

        $socialiteUser = Socialite::driver($this->driver->socialiteDriver())->user();
        if (! $socialiteUser->getId()) {
            throw new SocialAuthenticationException("{$this->driverLabel()} did not return required account information.");
        }

        $token = $this->exchangeToken($socialiteUser);
        $accounts = $this->getAccounts($socialiteUser->getId(), $token['access_token']);
        $this->ensureSocialAccountsBelongToUser($user, $accounts);
        $this->upsertSocialAccounts($accounts, $user);

        return $user;
    }

    protected function createUpdateUser($socialiteUser): User
    {
        $socialiteUserId = (string) $socialiteUser->getId();

        return User::updateOrCreate([
            'socialite_user_type' => $this->driver->socialiteDriver(),
            'socialite_user_id' => $socialiteUserId,
        ], [
            'name' => $socialiteUser->getName() ?? "{$this->driverLabel()} User",
            'email' => $socialiteUser->getEmail() ?? "{$this->driver->value}-{$socialiteUserId}@okacrm.local",
        ]);
    }

    protected function ensureNoConflictingEmailUser($socialiteUser): void
    {
        $email = $socialiteUser->getEmail() ?? "{$this->driver->value}-{$socialiteUser->getId()}@okacrm.local";

        $existingUserByEmail = User::query()
            ->where('email', $email)
            ->first();
        if (
            $existingUserByEmail !== null
            && (
                $existingUserByEmail->socialite_user_type !== $this->driver->socialiteDriver()
                || $existingUserByEmail->socialite_user_id !== $socialiteUser->getId()
            )
        ) {
            throw new SocialAuthenticationException('A user with this email already exists.');
        }
    }

    protected function findExistingSocialiteUser($socialiteUser): ?User
    {
        return User::query()
            ->where('socialite_user_type', $this->driver->socialiteDriver())
            ->where('socialite_user_id', $socialiteUser->getId())
            ->first();
    }

    protected function ensureSocialAccountsBelongToUser(?User $user, Collection $accounts): void
    {
        $socialNetworkUserIds = $accounts
            ->filter(
                fn (array $account): bool => ($account['social_network'] ?? $this->driver->value) === $this->driver->value
            )
            ->pluck('social_network_user_id')
            ->filter()
            ->values();
        if ($socialNetworkUserIds->isEmpty()) {
            return;
        }

        $conflictingAccount = SocialAccount::query()
            ->where('social_network', $this->driver->value)
            ->whereIn('social_network_user_id', $socialNetworkUserIds)
            ->when(
                $user,
                fn ($query) => $query->where('user_id', '!=', $user->id),
            )
            ->when(
                $user === null,
                fn ($query) => $query->whereNotNull('user_id'),
            )
            ->first();
        if ($conflictingAccount !== null) {
            throw new SocialAuthenticationException("One or more {$this->driverLabel()} accounts are linked to a different user.");
        }
    }

    protected function exchangeToken($socialiteUser): array
    {
        return $this->driver->socialiteClient($socialiteUser->token)->getLongLivedToken();
    }

    protected function getAccounts(string $id, string $token): Collection
    {
        return $this->driver->socialiteClient($token, $id)->accounts();
    }

    protected function upsertSocialAccounts($accounts, $user): void
    {
        $accounts->each(function ($account) use ($user) {
            $user->socialAccounts()->updateOrCreate([
                'social_network' => $account['social_network'] ?? $this->driver->value,
                'social_network_user_id' => $account['social_network_user_id'],
            ], $account);
        });
    }

}
