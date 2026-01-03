@foreach ($prod_tr->items() as $item)
    <tr class="*:text-gray-900 *:first:font-medium">
        <td class="px-3 py-2 whitespace-normal break-words align-top">{{ $item->KODEBARANG }}</td>
        <td class="px-3 py-2 whitespace-normal break-words align-top">{{ $item->NAMABARANG }}</td>
        <td class="px-3 py-2 whitespace-normal break-words align-top">{{ $item->SATUAN }}</td>
        <td class="px-3 py-2 whitespace-normal break-words align-top">{{ number_format($item->SALDOAKHIR, 0, '.', '') }}
        </td>
    </tr>
@endforeach
@if ($prod_tr->hasMorePages())
    {{-- infinite scroll --}}
    <tr hx-get="{{ $prod_tr->nextPageUrl() }}" hx-trigger="intersect once" hx-swap="outerHTML">
        <td colspan="4" class="px-3 py-2 whitespace-normal break-words align-middle text-center">
            @include('components.loading-spinner');
        </td>
    </tr>
@endif
