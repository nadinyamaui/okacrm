<x-mail::message>
# Hello,

{{ $influencerName }} sent invoice **#{{ $invoiceNumber }}**.

- Amount: **${{ $invoiceTotal }}**
- Due date: **{{ $invoiceDueDate }}**

@if ($lineItems->isNotEmpty())
## Line items

@foreach ($lineItems as $item)
- {{ $item->description }} ({{ number_format((float) $item->quantity, 2) }} x ${{ number_format((float) $item->unit_price, 2) }}) = ${{ number_format((float) $item->total, 2) }}
@endforeach
@endif

<x-mail::button :url="$invoiceUrl">
View Invoice
</x-mail::button>

If you are not logged in yet, use the client portal first:
{{ $portalLoginUrl }}
</x-mail::message>
