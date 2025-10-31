<div>
    <!-- Happiness is not something readymade. It comes from your own actions. - Dalai Lama -->
</div>
<table>
    <thead>
        <tr>
            <th>Jenis</th>
            <th>No Aju</th>
            <th>No Daftar</th>
            <th>Tgl Daftar</th>
            <th>Nomor</th>
            <th>Tgl Terima</th>
            <th>Penerima</th>
            <th>Kode barang</th>
            <th>Nama barang</th>
            <th>QTY</th>
            <th>Satuan</th>
            <th>Harga</th>
            <th>Nilai</th>
            <th>Keterangan</th>
        </tr>
    </thead>

    <tbody>
        @foreach ($prod_receipt as $item)
            <tr>

                <td>{{ $item->docBc }}</td>
                <td>{{ $item->requestNo }}</td>
                <td>{{ $item->registrationNo }}</td>
                <td>{{ $item->registrationDate }}</td>
                <td>{{ $item->PickCode }}</td>
                <td>{{ $item->transDate }}</td>
                <td>{{ $item->custName }}</td>
                <td>{{ $item->ItemId }}</td>
                <td>{{ $item->ItemName }}</td>
                <td>{{ $item->qty }}</td>
                <td>{{ $item->unit }}</td>
                <td>{{ $item->price }}</td>
                <td>{{ $item->amount }}</td>
                <td>{{ $item->notes }}</td>
            </tr>
        @endforeach
    </tbody>
</table>