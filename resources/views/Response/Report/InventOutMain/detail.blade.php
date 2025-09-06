@foreach ($salesPicks as $item)
    <tr class="*:text-gray-900 *:first:font-medium">
        <td class="px-3 py-2 whitespace-normal break-words align-top">{{ $item->DocBc }}</td>
        <td class="px-3 py-2 whitespace-normal break-words align-top">{{ $item->RequestNo }}</td>
        <td class="px-3 py-2 whitespace-normal break-words align-top">{{ $item->RegistrationNo }}</td>
        <td class="px-3 py-2 whitespace-normal break-words align-top">{{ $item->RegistrationDate }}</td>
        <td class="px-3 py-2 whitespace-normal break-words align-top">{{ $item->InvoiceId }}</td>
        <td class="px-3 py-2 whitespace-normal break-words align-top">{{ $item->InvoiceDate }}</td>
        <td class="px-3 py-2 whitespace-normal break-words align-top">{{ $item->ItemId }}</td>
        <td class="px-3 py-2 whitespace-normal break-words align-top">{{ $item->ItemName }}</td>
        <td class="px-3 py-2 whitespace-normal break-words align-top">{{ $item->Qty }}</td>
        <td class="px-3 py-2 whitespace-normal break-words align-top">{{ $item->Unit }}</td>
        <td class="px-3 py-2 whitespace-normal break-words align-top">{{ $item->InvJournalId }}</td>
        <td class="px-3 py-2 whitespace-normal break-words align-top">{{ $item->WorksheetId }}</td>
    </tr>
@endforeach