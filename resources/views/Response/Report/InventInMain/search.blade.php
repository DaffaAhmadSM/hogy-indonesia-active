@foreach ($prod_receipt->items() as $item)
    <tr class="*:text-gray-900 *:first:font-medium">
        <td class="px-3 py-2 whitespace-normal break-words align-top">{{ $item->BC_CODE_NAME }}</td>
        <td class="px-3 py-2 whitespace-normal break-words align-top">{{ $item->NOMORDAFTAR }}</td>
        <td class="px-3 py-2 whitespace-normal break-words align-top">{{ $item->TANGGALDAFTAR }}</td>
        <td class="px-3 py-2 whitespace-normal break-words align-top">{{ $item->NOMORPENERIMAAN }}</td>
        <td class="px-3 py-2 whitespace-normal break-words align-top">{{ $item->TANGGALPENERIMAAN }}</td>
        <td class="px-3 py-2 whitespace-normal break-words align-top">{{ $item->PENGIRIM }}</td>
        <td class="px-3 py-2 whitespace-normal break-words align-top">{{ $item->KODEBARANG }}</td>
        <td class="px-3 py-2 whitespace-normal break-words align-top">{{ $item->NAMABARANG }}</td>
        <td class="px-3 py-2 whitespace-normal break-words align-top">{{ $item->JUMLAH }}</td>
        <td class="px-3 py-2 whitespace-normal break-words align-top">{{ $item->SATUAN }}</td>
        <td class="px-3 py-2 whitespace-normal break-words align-top">{{ $item->NILAI }}</td>
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
    </tr>
@endif
