{{-- 
    Snippet ini adalah hasil akhir.
    Karena tidak memiliki atribut hx-get, polling akan berhenti secara otomatis.
--}}
<div class="flex items-center justify-between bg-green-100 p-2 rounded-md">
    <span class="text-sm text-green-800">File: {{ $filename }}</span>
    <a href="{{ $fileUrl }}"
       download
       class="rounded-md bg-purple-600 px-3 py-1 text-sm font-semibold text-white shadow-sm hover:bg-purple-500">
        Download
    </a>
</div>
