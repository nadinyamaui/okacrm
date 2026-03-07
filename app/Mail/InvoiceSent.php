<?php

namespace App\Mail;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoiceSent extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public Invoice $invoice,
    ) {
        $this->invoice->loadMissing(['user', 'client', 'items']);
    }

    public function envelope(): Envelope
    {
        $replyTo = [];

        if (filled($this->invoice->user?->email)) {
            $replyTo = [
                new Address(
                    $this->invoice->user->email,
                    $this->invoice->user?->name,
                ),
            ];
        }

        return new Envelope(
            subject: 'Invoice '.$this->invoice->invoice_number.' from '.($this->invoice->user?->name ?? 'Your influencer'),
            replyTo: $replyTo,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.invoice-sent',
            with: [
                'influencerName' => $this->invoice->user?->name ?? 'Your influencer',
                'invoiceNumber' => $this->invoice->invoice_number,
                'invoiceTotal' => number_format((float) $this->invoice->total, 2),
                'invoiceDueDate' => $this->invoice->due_date->format('M j, Y'),
                'lineItems' => $this->invoice->items,
                'invoiceUrl' => url('/portal/invoices/'.$this->invoice->id),
                'portalLoginUrl' => route('portal.login'),
            ],
        );
    }
}
