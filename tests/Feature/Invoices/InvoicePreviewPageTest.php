<?php

use App\Livewire\Invoices\Show as InvoiceShow;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use Livewire\Livewire;

test('guests are redirected from invoice preview page', function (): void {
    $invoice = Invoice::factory()->create();

    $this->get(route('invoices.show', $invoice))
        ->assertRedirect(route('login'));
});

test('invoice preview enforces ownership authorization', function (): void {
    $owner = User::factory()->create();
    $outsider = User::factory()->create();
    $client = Client::factory()->for($owner)->create();
    $invoice = Invoice::factory()->for($owner)->for($client)->create();

    $this->actingAs($outsider)
        ->get(route('invoices.show', $invoice))
        ->assertForbidden();
});

test('owner can view invoice preview details line items and totals', function (): void {
    $user = User::factory()->create(['name' => 'Nadin Influencer', 'email' => 'nadin@example.com']);
    $client = Client::factory()->for($user)->create([
        'name' => 'Acme Brand',
        'company_name' => 'Acme Corporation',
        'email' => 'client@acme.test',
    ]);

    $invoice = Invoice::factory()->for($user)->for($client)->draft()->create([
        'invoice_number' => 'INV-2026-001',
        'subtotal' => 250,
        'tax_rate' => 10,
        'tax_amount' => 25,
        'total' => 275,
        'notes' => 'Net 7 days',
    ]);

    $invoice->items()->createMany([
        [
            'description' => 'Item 1',
            'quantity' => 2,
            'unit_price' => 100,
            'total' => 200,
        ],
        [
            'description' => 'Item 2',
            'quantity' => 1,
            'unit_price' => 50,
            'total' => 50,
        ],
    ]);

    $this->actingAs($user)
        ->get(route('invoices.show', $invoice))
        ->assertSuccessful()
        ->assertSee('Invoice INV-2026-001')
        ->assertSee('Nadin Influencer')
        ->assertSee('nadin@example.com')
        ->assertSee('Acme Brand')
        ->assertSee('Acme Corporation')
        ->assertSee('client@acme.test')
        ->assertSee('Item 1')
        ->assertSee('Item 2')
        ->assertSee('$250.00')
        ->assertSee('$25.00')
        ->assertSee('$275.00')
        ->assertSee('Net 7 days')
        ->assertSee('Payment Link')
        ->assertSee('Not generated yet.');
});

test('draft invoice preview shows draft actions', function (): void {
    $user = User::factory()->create();
    $client = Client::factory()->for($user)->create();
    $invoice = Invoice::factory()->for($user)->for($client)->draft()->create();

    $this->actingAs($user)
        ->get(route('invoices.show', $invoice))
        ->assertSuccessful()
        ->assertSee('title="Edit invoice"', false)
        ->assertSee('title="Send invoice to client"', false)
        ->assertSee('title="Delete invoice"', false);
});

test('sent invoice preview shows sent actions', function (): void {
    $user = User::factory()->create();
    $client = Client::factory()->for($user)->create();
    $invoice = Invoice::factory()->for($user)->for($client)->sent()->create();

    $this->actingAs($user)
        ->get(route('invoices.show', $invoice))
        ->assertSuccessful()
        ->assertSee('title="Generate payment link"', false)
        ->assertSee('title="Resend invoice"', false)
        ->assertDontSee('title="Delete invoice"', false);
});

test('paid invoice preview shows paid state with date', function (): void {
    $user = User::factory()->create();
    $client = Client::factory()->for($user)->create();
    $invoice = Invoice::factory()->for($user)->for($client)->paid()->create([
        'paid_at' => now()->subDays(2),
    ]);

    $this->actingAs($user)
        ->get(route('invoices.show', $invoice))
        ->assertSuccessful()
        ->assertSee('Paid')
        ->assertSee($invoice->paid_at?->format('M j, Y') ?? '');
});

test('overdue invoice preview shows overdue actions', function (): void {
    $user = User::factory()->create();
    $client = Client::factory()->for($user)->create();
    $invoice = Invoice::factory()->for($user)->for($client)->overdue()->create();

    $this->actingAs($user)
        ->get(route('invoices.show', $invoice))
        ->assertSuccessful()
        ->assertSee('title="Send reminder"', false)
        ->assertSee('title="Generate payment link"', false);
});

test('owner can delete draft invoice from preview page', function (): void {
    $user = User::factory()->create();
    $client = Client::factory()->for($user)->create();
    $invoice = Invoice::factory()->for($user)->for($client)->draft()->create([
        'invoice_number' => 'INV-DEL-1',
    ]);

    Livewire::actingAs($user)
        ->test(InvoiceShow::class, ['invoice' => $invoice])
        ->call('delete')
        ->assertRedirect(route('invoices.index'));

    $this->assertDatabaseMissing('invoices', ['id' => $invoice->id]);
});
