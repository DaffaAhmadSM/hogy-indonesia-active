@foreach ($products->items() as $item)
    <tr class="*:text-gray-900 *:first:font-medium">
        <td class="px-3 py-2 whitespace-normal break-words align-top">{{ $item->productId }}</td>
        <td class="px-3 py-2 whitespace-normal break-words align-top">{{ $item->productName	 }}</td>
        <td class="px-3 py-2 whitespace-normal break-words align-top">{{ $item->unitId}}</td>
        <td class="px-3 py-2 whitespace-normal break-words align-top">{{ number_format($item->saldoAwal, 3, '.', ',') }}</td>
        <td class="px-3 py-2 whitespace-normal break-words align-top">{{ number_format($item->masuk, 3, '.', ',') }}</td>
        <td class="px-3 py-2 whitespace-normal break-words align-top">{{ number_format($item->keluar, 3, '.', ',') }}</td>
        <td class="px-3 py-2 whitespace-normal break-words align-top">0.000</td>
        <td class="px-3 py-2 whitespace-normal break-words align-top">{{ number_format($item->saldoBuku, 3, '.', ',') }}</td>
        <td class="px-3 py-2 whitespace-normal break-words align-top">{{ number_format($item->stockOphname, 3, '.', ',') }}</td>
        <td class="px-3 py-2 whitespace-normal break-words align-top">{{ number_format($item->selisih, 3, '.', ',') }}</td>

    </tr>
@endforeach
@if ($products->hasMorePages())
    {{-- infinite scroll --}}
    <tr hx-get="{{ $products->nextPageUrl() }}" hx-trigger="intersect once" hx-swap="outerHTML">
        <td colspan="10" class="px-3 py-2 whitespace-normal break-words align-middle text-center">
            @include('components.loading-spinner');
        </td>
    </tr>
@endif
