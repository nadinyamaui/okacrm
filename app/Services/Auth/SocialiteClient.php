<?php

namespace App\Services\Auth;

use Illuminate\Support\Collection;

interface SocialiteClient
{
    public function getLongLivedToken(): array;

    public function accounts(): Collection;
}
