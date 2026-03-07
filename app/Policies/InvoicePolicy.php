<?php

namespace App\Policies;

use App\Enums\InvoiceStatus;
use App\Models\ClientUser;
use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    public function viewAny(User|ClientUser $user): bool
    {
        return true;
    }

    public function view(User|ClientUser $user, Invoice $invoice): bool
    {
        if ($user instanceof ClientUser) {
            return $user->client_id === $invoice->client_id;
        }

        return $user->id === $invoice->user_id;
    }

    public function create(User|ClientUser $user): bool
    {
        return $user instanceof User;
    }

    public function update(User|ClientUser $user, Invoice $invoice): bool
    {
        return $user instanceof User
            && $user->id === $invoice->user_id
            && $invoice->status === InvoiceStatus::Draft;
    }

    public function delete(User|ClientUser $user, Invoice $invoice): bool
    {
        return $user instanceof User
            && $user->id === $invoice->user_id
            && $invoice->status === InvoiceStatus::Draft;
    }

    public function send(User|ClientUser $user, Invoice $invoice): bool
    {
        return $user instanceof User
            && $user->id === $invoice->user_id
            && in_array($invoice->status, [InvoiceStatus::Draft, InvoiceStatus::Sent, InvoiceStatus::Overdue], true);
    }
}
