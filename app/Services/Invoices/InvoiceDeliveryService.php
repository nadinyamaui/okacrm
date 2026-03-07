<?php

namespace App\Services\Invoices;

use App\Enums\InvoiceStatus;
use App\Mail\InvoiceSent;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class InvoiceDeliveryService
{
    public function send(User $user, Invoice $invoice): Invoice
    {
        $this->ensureOwner($user, $invoice);
        $this->assertDraft($invoice);
        $this->assertClientEmail($invoice);

        return DB::transaction(function () use ($invoice): Invoice {
            $invoice->update([
                'status' => InvoiceStatus::Sent,
            ]);

            $invoice->loadMissing(['client', 'user', 'items']);

            Mail::to($invoice->client->email)->send(new InvoiceSent($invoice));

            return $invoice->refresh();
        });
    }

    public function resend(User $user, Invoice $invoice): Invoice
    {
        $this->ensureOwner($user, $invoice);
        $this->assertResendable($invoice);
        $this->assertClientEmail($invoice);

        $invoice->loadMissing(['client', 'user', 'items']);

        Mail::to($invoice->client->email)->send(new InvoiceSent($invoice));

        return $invoice->refresh();
    }

    private function assertDraft(Invoice $invoice): void
    {
        if ($invoice->status !== InvoiceStatus::Draft) {
            throw ValidationException::withMessages([
                'send' => 'Only draft invoices can be sent.',
            ]);
        }
    }

    private function assertResendable(Invoice $invoice): void
    {
        if (! in_array($invoice->status, [InvoiceStatus::Sent, InvoiceStatus::Overdue], true)) {
            throw ValidationException::withMessages([
                'send' => 'Only sent or overdue invoices can be resent.',
            ]);
        }
    }

    private function assertClientEmail(Invoice $invoice): void
    {
        $invoice->loadMissing('client');

        if (blank($invoice->client?->email)) {
            throw ValidationException::withMessages([
                'send' => 'Add a client email before sending this invoice.',
            ]);
        }
    }

    private function ensureOwner(User $user, Invoice $invoice): void
    {
        if ($invoice->user_id !== $user->id) {
            throw new AuthorizationException('You are not authorized to send this invoice.');
        }
    }
}
