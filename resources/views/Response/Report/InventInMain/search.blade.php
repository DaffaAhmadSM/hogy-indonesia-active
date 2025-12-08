@foreach ($prod_receipt->items() as $item)
    <tr class="*:text-gray-900 *:first:font-medium">
        <td class="px-3 py-2 whitespace-normal break-words align-top">{{ $item->docBc }}</td>
        <td class="px-3 py-2 whitespace-normal break-words align-top">{{ $item->requestNo }}</td>
        <td class="px-3 py-2 whitespace-normal break-words align-top">{{ $item->registrationNo }}</td>
        <td class="px-3 py-2 whitespace-normal break-words align-top">{{ $item->registrationDate }}</td>
        <td class="px-3 py-2 whitespace-normal break-words align-top">{{ $item->PackCode }}</td>
        <td class="px-3 py-2 whitespace-normal break-words align-top">{{ $item->transDate }}</td>
        <td class="px-3 py-2 whitespace-normal break-words align-top">{{ $item->VendName }}</td>
        <td class="px-3 py-2 whitespace-normal break-words align-top">{{ $item->ItemId }}</td>
        <td class="px-3 py-2 whitespace-normal break-words align-top">{{ $item->ItemName }}</td>
        <td class="px-3 py-2 whitespace-normal break-words align-top">{{ $item->qty }}</td>
        <td class="px-3 py-2 whitespace-normal break-words align-top">{{ $item->unit }}</td>
        <td class="px-3 py-2 whitespace-normal break-words align-top">{{ number_format($item->price, 4, '.', '') }}</td>
        <td class="px-3 py-2 whitespace-normal break-words align-top">{{ number_format($item->amount, 4, '.', '') }}</td>
        <td class="px-3 py-2 whitespace-normal break-words align-top">{{ $item->Notes }}</td>
    </tr>
@endforeach
@if ($prod_receipt->hasMorePages())
    {{-- infinite scroll --}}
    <tr hx-get="{{ $prod_receipt->nextPageUrl() }}" hx-trigger="intersect once" hx-swap="afterend">
        <td class="px-3 py-2 whitespace-normal break-words align-top"></td>
        <td class="px-3 py-2 whitespace-normal break-words align-top"></td>
        <td class="px-3 py-2 whitespace-normal break-words align-top"></td>
        <td class="px-3 py-2 whitespace-normal break-words align-top"></td>
        <td class="px-3 py-2 whitespace-normal break-words align-top"></td>
        <td class="px-3 py-2 whitespace-normal break-words align-top"></td>
        <td class="px-3 py-2 whitespace-normal break-words align-top"></td>
        <td class="px-3 py-2 whitespace-normal break-words align-top"></td>
        <td class="px-3 py-2 whitespace-normal break-words align-top"></td>
        <td class="px-3 py-2 whitespace-normal break-words align-top"></td>
        <td class="px-3 py-2 whitespace-normal break-words align-top"></td>
        <td class="px-3 py-2 whitespace-normal break-words align-top"></td>
        <td class="px-3 py-2 whitespace-normal break-words align-top"></td>
        <td class="px-3 py-2 whitespace-normal break-words align-top"></td>
    </tr>
@endif
