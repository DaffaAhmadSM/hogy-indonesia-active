@php
    use Illuminate\Support\Carbon;
@endphp

@foreach ($prod_receipt->items() as $item)
    <tr class="*:text-gray-900 *:first:font-medium"
        hx-get="{{ route('report.invent-out-main.detail', ['state' => 'archived', 'salesPickLineRecId' => $item->RECID]) }}"
        hx-target="#modal-table" hx-swap="innerHTML" hx-trigger="click">
        <td class="px-3 py-2 whitespace-normal break-words align-top">{{ $item->BC_CODE_NAME }}</td>
        <td class="px-3 py-2 whitespace-normal break-words align-top">{{ $item->NOMORDAFTAR }}</td>
        <td class="px-3 py-2 whitespace-normal break-words align-top">
            {{ Carbon::parse($item->TANGGALDAFTAR)->format('Y-m-d') }}</td>
        <td class="px-3 py-2 whitespace-normal break-words align-top">{{ $item->NOMORPENGIRIMAN }}</td>
        <td class="px-3 py-2 whitespace-normal break-words align-top">
            {{ Carbon::parse($item->TANGGALPENGIRIMAN)->format('Y-m-d') }}</td>
        <td class="px-3 py-2 whitespace-normal break-words align-top">{{ $item->PENERIMA }}</td>
        <td class="px-3 py-2 whitespace-normal break-words align-top">{{ $item->KODEBARANG }}</td>
        <td class="px-3 py-2 whitespace-normal break-words align-top">{{ $item->NAMABARANG }}</td>
        <td class="px-3 py-2 whitespace-normal break-words align-top">{{ number_format($item->JUMLAH, 2, ',', '.') }}
        </td>
        <td class="px-3 py-2 whitespace-normal break-words align-top">{{ $item->SATUAN }}</td>
        <td class="px-3 py-2 whitespace-normal break-words align-top">{{ number_format($item->NILAI, 2, ',', '.') }}
        </td>
    </tr>
@endforeach
@if ($prod_receipt->hasMorePages())
    {{-- infinite scroll --}}
    {{-- <tr hx-get="{{ $prod_receipt->nextPageUrl() }}" hx-trigger="intersect once" hx-swap="afterend"> --}}
    {{-- <td class="px-3 py-2 whitespace-normal break-words align-top"></td>
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
        <td class="px-3 py-2 whitespace-normal break-words align-top"></td> --}}
    {{-- </tr> --}}
@endif
