{{--
Snippet ini akan terus memanggil endpoint status setiap 2 detik.
hx-target="this" berarti hasilnya akan menggantikan elemen ini.
hx-swap="outerHTML" berarti seluruh elemen <div...> akan diganti.
    --}}
    <div class="flex items-center justify-between bg-gray-100 p-2 rounded-md"
        hx-get="{{ route('report.product-bb-main.export.status', ['filename' => $filename]) }}" hx-trigger="every 10s"
        hx-target="this" hx-swap="outerHTML">

        <span class="text-sm text-gray-700">File: {{ $filename }}</span>
        <div class="flex items-center gap-2">
            <span class="text-sm font-medium text-blue-600">Processing...</span>
            @include('components.loading-spinner')
        </div>
    </div>