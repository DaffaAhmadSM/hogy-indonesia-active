<table>
    <thead>
        <tr>
            <th>Jenis</th>
            <th>No Daftar</th>
            <th>Tgl Daftar</th>
            <th>Nomor Penerimaan</th>
            <th>Tgl Penerimaan</th>
            <th>Pengirim</th>
            <th>Kode Barang</th>
            <th>Nama Barang</th>
            <th>Jumlah</th>
            <th>Satuan</th>
            <th>Nilai</th>
        </tr>
    </thead>

    <tbody>
        @foreach ($prod_receipt as $item)
            <tr>
                <td>{{ $item->BC_CODE_NAME }}</td>
                <td>{{ $item->NOMORDAFTAR }}</td>
                <td>{{ $item->TANGGALDAFTAR }}</td>
                <td>{{ $item->NOMORPENERIMAAN }}</td>
                <td>{{ $item->TANGGALPENERIMAAN }}</td>
                <td>{{ $item->PENGIRIM }}</td>
                <td>{{ $item->KODEBARANG }}</td>
                <td>{{ $item->NAMABARANG }}</td>
                <td>{{ $item->JUMLAH }}</td>
                <td>{{ $item->SATUAN }}</td>
                <td>{{ $item->NILAI }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
