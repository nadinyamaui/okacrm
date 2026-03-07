<div class="mx-auto flex w-full max-w-6xl flex-1 flex-col gap-6">
    @if (session('status'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 dark:border-emerald-900/40 dark:bg-emerald-950/50 dark:text-emerald-200">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->has('send'))
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800 dark:border-rose-900/40 dark:bg-rose-950/50 dark:text-rose-200">
            {{ $errors->first('send') }}
        </div>
    @endif

    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold text-zinc-900 dark:text-zinc-100">Invoice {{ $invoice->invoice_number }}</h1>
            <div class="mt-2 flex flex-wrap items-center gap-2 text-sm text-zinc-600 dark:text-zinc-300">
                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $invoice->status->badgeClasses() }}">
                    {{ $invoice->status->label() }}
                </span>
                <span>Issued {{ $invoice->created_at->format('M j, Y') }}</span>
                <span>Due {{ $invoice->due_date->format('M j, Y') }}</span>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <flux:button :href="route('invoices.index')" variant="filled" title="Back to invoices" aria-label="Back to invoices" wire:navigate>
                <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
            </flux:button>

            @if ($isDraft)
                <flux:button :href="route('invoices.edit', $invoice)" variant="filled" title="Edit invoice" aria-label="Edit invoice" wire:navigate>
                    <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>
                </flux:button>
                <flux:button
                    type="button"
                    variant="primary"
                    title="Send invoice to client"
                    aria-label="Send invoice to client"
                    wire:click="send"
                    wire:confirm="Send invoice {{ $invoice->invoice_number }} (${{ number_format((float) $invoice->total, 2) }}) to {{ $invoice->client?->name ?? 'this client' }} at {{ $invoice->client?->email ?? 'no email' }}?"
                >
                    <i class="fa-solid fa-paper-plane" aria-hidden="true"></i>
                </flux:button>
                <flux:button
                    type="button"
                    variant="filled"
                    title="Delete invoice"
                    aria-label="Delete invoice"
                    class="border-rose-300 text-rose-700 hover:bg-rose-50 dark:border-rose-800 dark:text-rose-200 dark:hover:bg-rose-950/40"
                    wire:click="delete"
                    wire:confirm="Are you sure you want to delete invoice {{ $invoice->invoice_number }}?"
                >
                    <i class="fa-solid fa-trash" aria-hidden="true"></i>
                </flux:button>
            @elseif ($isSent)
                <flux:button type="button" variant="primary" title="Generate payment link" aria-label="Generate payment link" disabled>
                    <i class="fa-solid fa-link" aria-hidden="true"></i>
                </flux:button>
                <flux:button
                    type="button"
                    variant="filled"
                    title="Resend invoice"
                    aria-label="Resend invoice"
                    wire:click="resend"
                    wire:confirm="Resend invoice {{ $invoice->invoice_number }} (${{ number_format((float) $invoice->total, 2) }}) to {{ $invoice->client?->name ?? 'this client' }} at {{ $invoice->client?->email ?? 'no email' }}?"
                >
                    <i class="fa-solid fa-rotate-right" aria-hidden="true"></i>
                </flux:button>
            @elseif ($isPaid)
                <span class="inline-flex rounded-full bg-emerald-100 px-3 py-1.5 text-xs font-medium text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-200">
                    Paid {{ $invoice->paid_at?->format('M j, Y') ?? '' }}
                </span>
            @elseif ($isOverdue)
                <flux:button type="button" variant="filled" title="Send reminder" aria-label="Send reminder" disabled>
                    <i class="fa-solid fa-bell" aria-hidden="true"></i>
                </flux:button>
                <flux:button type="button" variant="primary" title="Generate payment link" aria-label="Generate payment link" disabled>
                    <i class="fa-solid fa-link" aria-hidden="true"></i>
                </flux:button>
                <flux:button
                    type="button"
                    variant="filled"
                    title="Resend invoice"
                    aria-label="Resend invoice"
                    wire:click="resend"
                    wire:confirm="Resend invoice {{ $invoice->invoice_number }} (${{ number_format((float) $invoice->total, 2) }}) to {{ $invoice->client?->name ?? 'this client' }} at {{ $invoice->client?->email ?? 'no email' }}?"
                >
                    <i class="fa-solid fa-rotate-right" aria-hidden="true"></i>
                </flux:button>
            @endif
        </div>
    </div>

    <section class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="grid gap-6 lg:grid-cols-2">
            <article>
                <h2 class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-300">From</h2>
                <div class="mt-2 space-y-1 text-sm text-zinc-700 dark:text-zinc-200">
                    <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ $invoice->user?->name ?? 'Influencer' }}</p>
                    <p>{{ $invoice->user?->email ?? '—' }}</p>
                </div>
            </article>

            <article>
                <h2 class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-300">To</h2>
                <div class="mt-2 space-y-1 text-sm text-zinc-700 dark:text-zinc-200">
                    <p class="font-medium text-zinc-900 dark:text-zinc-100">
                        @if ($invoice->client)
                            <a href="{{ route('clients.show', $invoice->client) }}" class="hover:underline" wire:navigate>
                                {{ $invoice->client->name }}
                            </a>
                        @else
                            —
                        @endif
                    </p>
                    <p>{{ $invoice->client?->company_name ?? '—' }}</p>
                    <p>{{ $invoice->client?->email ?? '—' }}</p>
                </div>
            </article>
        </div>

        <div class="mt-6 overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800/60">
                    <tr>
                        <th class="px-3 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">Description</th>
                        <th class="px-3 py-3 text-right font-medium text-zinc-600 dark:text-zinc-300">Qty</th>
                        <th class="px-3 py-3 text-right font-medium text-zinc-600 dark:text-zinc-300">Price</th>
                        <th class="px-3 py-3 text-right font-medium text-zinc-600 dark:text-zinc-300">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse ($invoice->items as $item)
                        <tr wire:key="invoice-preview-item-{{ $item->id }}">
                            <td class="px-3 py-3 text-zinc-900 dark:text-zinc-100">{{ $item->description }}</td>
                            <td class="px-3 py-3 text-right text-zinc-700 dark:text-zinc-200">{{ number_format((float) $item->quantity, 2) }}</td>
                            <td class="px-3 py-3 text-right text-zinc-700 dark:text-zinc-200">${{ number_format((float) $item->unit_price, 2) }}</td>
                            <td class="px-3 py-3 text-right text-zinc-900 dark:text-zinc-100">${{ number_format((float) $item->total, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-3 py-4 text-center text-zinc-600 dark:text-zinc-300">No line items.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6 grid gap-6 lg:grid-cols-2">
            <article class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                <h2 class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-300">Notes</h2>
                <p class="mt-2 whitespace-pre-wrap text-sm text-zinc-700 dark:text-zinc-200">{{ $invoice->notes ?: 'No notes provided.' }}</p>
                <div class="mt-4 border-t border-zinc-200 pt-4 text-sm dark:border-zinc-700">
                    <p class="font-medium text-zinc-900 dark:text-zinc-100">Payment Link</p>
                    <p class="mt-1 text-zinc-600 dark:text-zinc-300">Not generated yet.</p>
                </div>
            </article>

            <article class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                <h2 class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-300">Totals</h2>
                <dl class="mt-3 space-y-2 text-sm">
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-zinc-600 dark:text-zinc-300">Subtotal</dt>
                        <dd class="font-medium text-zinc-900 dark:text-zinc-100">${{ number_format((float) $invoice->subtotal, 2) }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-zinc-600 dark:text-zinc-300">Tax ({{ number_format((float) $invoice->tax_rate, 2) }}%)</dt>
                        <dd class="font-medium text-zinc-900 dark:text-zinc-100">${{ number_format((float) $invoice->tax_amount, 2) }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3 border-t border-zinc-200 pt-2 text-base dark:border-zinc-700">
                        <dt class="font-semibold text-zinc-900 dark:text-zinc-100">Total</dt>
                        <dd class="font-semibold text-zinc-900 dark:text-zinc-100">${{ number_format((float) $invoice->total, 2) }}</dd>
                    </div>
                </dl>
            </article>
        </div>
    </section>
</div>
