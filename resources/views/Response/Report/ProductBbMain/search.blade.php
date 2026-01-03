@foreach ($products->items() as $item)
    <tr class="*:text-gray-900 *:first:font-medium">
        <td class="px-3 py-2 whitespace-normal break-words align-top">{{ $item->KODEBARANG }}</td>
        <td class="px-3 py-2 whitespace-normal break-words align-top">{{ $item->NAMABARANG }}</td>
        <td class="px-3 py-2 whitespace-normal break-words align-top">{{ $item->SATUAN }}</td>
        <td class="px-3 py-2 whitespace-normal break-words align-top">{{ $item->SALDOAWAL }}</td>
        <td class="px-3 py-2 whitespace-normal break-words align-top">{{ $item->PEMASUKAN }}</td>
        <td class="px-3 py-2 whitespace-normal break-words align-top">{{ $item->PENGELUARAN }}</td>
        <td class="px-3 py-2 whitespace-normal break-words align-top">{{ $item->PENYESUAIAN }}</td>
        <td class="px-3 py-2 whitespace-normal break-words align-top">{{ $item->SALDOBUKU }}</td>
        <td class="px-3 py-2 whitespace-normal break-words align-top">{{ $item->STOCKOPNAME }}</td>
        <td class="px-3 py-2 whitespace-normal break-words align-top">{{ $item->SELISIH }}</td>
        <td class="px-3 py-2 whitespace-normal break-words align-top">{{ $item->KETERANGAN }}</td>
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
