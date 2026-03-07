# 055 - Send Invoice to Client

**Labels:** `feature`, `invoicing`
**Depends on:** #051

## Description

Implement the "Send Invoice" action that changes status to Sent and emails the invoice to the client.

## Implementation

### Livewire Action on Invoice Detail

```php
public function sendInvoice(): void
{
    $this->authorize('update', $this->invoice);

    if ($this->invoice->status !== InvoiceStatus::Draft) {
        return;
    }

    $this->invoice->update([
        'status' => InvoiceStatus::Sent,
    ]);

    Mail::to($this->invoice->client->email)
        ->send(new InvoiceSent($this->invoice));

    session()->flash('success', 'Invoice sent to ' . $this->invoice->client->name);
}
```

### Confirmation
"Send invoice #{number} ({total}) to {client name} at {client email}?"

### Create Mailable
`App\Mail\InvoiceSent`:
- To: client email
- Subject: "Invoice {invoice_number} from {influencer name}"
- Content:
  - Invoice number, amount, due date
  - Line items summary
  - "View Invoice" button (links to client portal invoice detail)
  - "View Invoice" link to client portal
- Reply-to: influencer email

### Resend
For Sent/Overdue invoices, show a "Resend" button that re-sends the email without changing status.

## Files to Create
- `app/Mail/InvoiceSent.php`
- `resources/views/mail/invoice-sent.blade.php`

## Files to Modify
- `resources/views/pages/invoices/show.blade.php` — add send and resend buttons

## Acceptance Criteria
- [ ] "Send Invoice" changes status to Sent
- [ ] Email sent to client with invoice details
- [ ] Invoice detail link included in email
- [ ] Confirmation shown before sending
- [ ] Cannot send if client has no email
- [ ] Resend works for Sent/Overdue invoices
- [ ] Feature tests cover sending and email dispatch
