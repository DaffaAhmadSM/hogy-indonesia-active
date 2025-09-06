{{--
Snippet ini akan terus memanggil endpoint status setiap 2 detik.
hx-target="this" berarti hasilnya akan menggantikan elemen ini.
hx-swap="outerHTML" berarti seluruh elemen <div...> akan diganti.
    --}}
    <div class="block rounded border border-gray-300 p-4 text-gray-900 shadow-sm sm:p-6"
        hx-get="{{ route('report.product-bb-main.export.status', ['filename' => $filename]) }}" hx-trigger="every 6s"
        hx-target="this" hx-swap="outerHTML">
        <div class="flex items-center justify-center gap-4">
            <span class="font-medium"> Processing </span>

            @include('components.loading-spinner')
        </div>
    </div>