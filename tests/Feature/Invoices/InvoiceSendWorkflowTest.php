<?php

use App\Enums\InvoiceStatus;
use App\Livewire\Invoices\Show as InvoiceShow;
use App\Mail\InvoiceSent;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

test('guests are redirected from invoice show page', function (): void {
    $invoice = Invoice::factory()->create();

    $this->get(route('invoices.show', $invoice))->assertRedirect(route('login'));
});

test('users cannot view invoices they do not own', function (): void {
    $owner = User::factory()->create();
    $outsider = User::factory()->create();
    $client = Client::factory()->for($owner)->create();
    $invoice = Invoice::factory()->for($owner)->for($client)->draft()->create();

    $this->actingAs($outsider)
        ->get(route('invoices.show', $invoice))
        ->assertForbidden();
});

test('influencer can send a draft invoice to a client email', function (): void {
    Mail::fake();

    $owner = User::factory()->create(['name' => 'Nadin Creator', 'email' => 'nadin@example.test']);
    $client = Client::factory()->for($owner)->create([
        'name' => 'Acme Client',
        'email' => 'acme@example.test',
    ]);
    $invoice = Invoice::factory()->for($owner)->for($client)->draft()->create([
        'invoice_number' => 'INV-2026-0001',
        'total' => 1500.00,
    ]);
    $invoice->items()->create([
        'description' => 'Campaign Package',
        'quantity' => 1,
        'unit_price' => 1500,
        'total' => 1500,
    ]);

    Livewire::actingAs($owner)
        ->test(InvoiceShow::class, ['invoice' => $invoice])
        ->call('send')
        ->assertHasNoErrors();

    expect($invoice->fresh()->status)->toBe(InvoiceStatus::Sent);

    Mail::assertSent(InvoiceSent::class, function (InvoiceSent $mail) use ($invoice): bool {
        $rendered = $mail->render();

        return $mail->hasTo('acme@example.test')
            && str_contains($mail->envelope()->subject, $invoice->invoice_number)
            && str_contains($rendered, '/portal/invoices/'.$invoice->id);
    });
});

test('invoice send fails when client email is missing', function (): void {
    Mail::fake();

    $owner = User::factory()->create();
    $client = Client::factory()->for($owner)->create(['email' => null]);
    $invoice = Invoice::factory()->for($owner)->for($client)->draft()->create();

    Livewire::actingAs($owner)
        ->test(InvoiceShow::class, ['invoice' => $invoice])
        ->call('send')
        ->assertHasErrors(['send']);

    expect($invoice->fresh()->status)->toBe(InvoiceStatus::Draft);
    Mail::assertNothingSent();
});

test('influencer can resend sent and overdue invoices without changing status', function (): void {
    Mail::fake();

    $owner = User::factory()->create();
    $client = Client::factory()->for($owner)->create(['email' => 'client@example.test']);
    $sentInvoice = Invoice::factory()->for($owner)->for($client)->sent()->create();
    $overdueInvoice = Invoice::factory()->for($owner)->for($client)->overdue()->create();

    Livewire::actingAs($owner)
        ->test(InvoiceShow::class, ['invoice' => $sentInvoice])
        ->call('resend')
        ->assertHasNoErrors();

    Livewire::actingAs($owner)
        ->test(InvoiceShow::class, ['invoice' => $overdueInvoice])
        ->call('resend')
        ->assertHasNoErrors();

    expect($sentInvoice->fresh()->status)->toBe(InvoiceStatus::Sent)
        ->and($overdueInvoice->fresh()->status)->toBe(InvoiceStatus::Overdue);

    Mail::assertSent(InvoiceSent::class, 2);
});
