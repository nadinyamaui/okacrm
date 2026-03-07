<?php

namespace App\Livewire\Invoices;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Services\Invoices\InvoiceDeliveryService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class Show extends Component
{
    use AuthorizesRequests;

    public Invoice $invoice;

    public function mount(Invoice $invoice): void
    {
        $this->authorize('view', $invoice);

        $this->invoice = $invoice->load([
            'user:id,name,email',
            'client:id,name,company_name,email',
            'items:id,invoice_id,description,quantity,unit_price,total',
        ]);
    }

    public function send(InvoiceDeliveryService $invoiceDeliveryService): void
    {
        $this->authorize('send', $this->invoice);

        try {
            $this->invoice = $invoiceDeliveryService
                ->send(auth()->user(), $this->invoice)
                ->load(['client', 'user', 'items']);
        } catch (ValidationException $exception) {
            $this->setErrorBag($exception->validator->errors());

            return;
        }

        $this->resetErrorBag();
        session()->flash('status', 'Invoice sent to '.$this->invoice->client->name.'.');
    }

    public function resend(InvoiceDeliveryService $invoiceDeliveryService): void
    {
        $this->authorize('send', $this->invoice);

        try {
            $this->invoice = $invoiceDeliveryService
                ->resend(auth()->user(), $this->invoice)
                ->load(['client', 'user', 'items']);
        } catch (ValidationException $exception) {
            $this->setErrorBag($exception->validator->errors());

            return;
        }

        $this->resetErrorBag();
        session()->flash('status', 'Invoice re-sent to '.$this->invoice->client->name.'.');
    }

    public function delete()
    {
        $this->authorize('delete', $this->invoice);

        $invoiceNumber = $this->invoice->invoice_number;
        $this->invoice->delete();

        session()->flash('status', 'Invoice '.$invoiceNumber.' deleted.');

        return $this->redirectRoute('invoices.index', navigate: true);
    }

    public function canSend(): bool
    {
        return $this->invoice->status === InvoiceStatus::Draft;
    }

    public function canResend(): bool
    {
        return in_array($this->invoice->status, [InvoiceStatus::Sent, InvoiceStatus::Overdue], true);
    }

    public function render()
    {
        return view('pages.invoices.show', [
            'isDraft' => $this->invoice->status === InvoiceStatus::Draft,
            'isSent' => $this->invoice->status === InvoiceStatus::Sent,
            'isPaid' => $this->invoice->status === InvoiceStatus::Paid,
            'isOverdue' => $this->invoice->status === InvoiceStatus::Overdue,
        ])->layout('layouts.app', [
            'title' => __('Invoice Preview'),
        ]);
    }
}
