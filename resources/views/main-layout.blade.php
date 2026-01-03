<!DOCTYPE html>
<html lang="en" hx-headers='{"X-CSRF-TOKEN": @csrf_token()}'>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PT. Hogy Indonesia</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="h-screen md:h-[100dvh] overflow-hidden flex flex-col max-w-screen mx-auto px-4 py-2 max-h-screen">
    <header class="bg-white shrink-0">
        <div class="flex h-12 items-center justify-between">
            <div class="hidden md:block">
                <nav aria-label="Global">
                    <ul class="flex items-center gap-6 text-md">

                        <li>
                            <div x-data="{
                                open: false,
                                toggle() {
                                    if (this.open) {
                                        return this.close()
                                    }
                            
                                    this.$refs.button.focus()
                            
                                    this.open = true
                                },
                                close(focusAfter) {
                                    if (!this.open) return
                            
                                    this.open = false
                            
                                    focusAfter && focusAfter.focus()
                                }
                            }" x-on:keydown.escape.prevent.stop="close($refs.button)"
                                x-on:focusin.window="! $refs.panel.contains($event.target) && close()"
                                x-id="['dropdown-button']" class="relative">
                                <!-- Button -->
                                <button x-ref="button" x-on:click="toggle()" :aria-expanded="open"
                                    :aria-controls="$id('dropdown-button')" type="button"
                                    class="relative flex items-center whitespace-nowrap justify-center gap-2 py-2 rounded-lg shadow-sm bg-white hover:bg-gray-50 text-gray-800 border border-gray-200 hover:border-gray-200 px-4">
                                    <span>Laporan Archive</span>

                                    <!-- Heroicon: micro chevron-down -->
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor"
                                        class="size-4">
                                        <path fill-rule="evenodd"
                                            d="M4.22 6.22a.75.75 0 0 1 1.06 0L8 8.94l2.72-2.72a.75.75 0 1 1 1.06 1.06l-3.25 3.25a.75.75 0 0 1-1.06 0L4.22 7.28a.75.75 0 0 1 0-1.06Z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </button>

                                <!-- Panel -->
                                <div x-ref="panel" x-show="open" x-transition.origin.top.left
                                    x-on:click.outside="close($refs.button)" :id="$id('dropdown-button')" x-cloak
                                    class="absolute left-0 min-w-96 rounded-lg shadow-sm mt-2 z-10 origin-top-left bg-white p-1.5 outline-none border border-gray-200">
                                    <a href="{{ route('report.invent-in-main', ['state' => 'archive']) }}"
                                        class="px-2 lg:py-1.5 py-2 w-full flex items-center rounded-md transition-colors text-left text-gray-800 hover:bg-gray-50 focus-visible:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed border-b-2">
                                        1. Laporan Pemasukan Barang Per Dokumen Pabean
                                    </a>

                                    <a href="{{ route('report.invent-out-main', ['state' => 'archive']) }}"
                                        class="px-2 lg:py-1.5 py-2 w-full flex items-center rounded-md transition-colors text-left text-gray-800 hover:bg-gray-50 focus-visible:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed border-b-2">
                                        2. Laporan Pengeluaran Barang Per Dokumen Pabean
                                    </a>

                                    <a href="{{ route('report.product-wip-main', ['state' => 'archive']) }}"
                                        class="px-2 lg:py-1.5 py-2 w-full flex items-center rounded-md transition-colors text-left text-gray-800 hover:bg-red-50 hover:text-red-600 focus-visible:bg-red-50 focus-visible:text-red-600 disabled:opacity-50 disabled:cursor-not-allowed border-b-2">
                                        3. Laporan Posisi WIP
                                    </a>

                                    <a href="{{ route('report.product-bb-main', ['type' => 'BBP', 'state' => 'archive']) }}"
                                        class="px-2 lg:py-1.5 py-2 w-full flex items-center rounded-md transition-colors text-left text-gray-800 hover:bg-red-50 hover:text-red-600 focus-visible:bg-red-50 focus-visible:text-red-600 disabled:opacity-50 disabled:cursor-not-allowed border-b-2">
                                        4. Laporan Pertanggungjawaban Mutasi Bahan baku dan Penolong
                                    </a>

                                    <a href="{{ route('report.product-bb-main', ['type' => 'MP', 'state' => 'archive']) }}"
                                        class="px-2 lg:py-1.5 py-2 w-full flex items-center rounded-md transition-colors text-left text-gray-800 hover:bg-red-50 hover:text-red-600 focus-visible:bg-red-50 focus-visible:text-red-600 disabled:opacity-50 disabled:cursor-not-allowed border-b-2">
                                        5. Laporan Pertanggungjawaban Mutasi
                                        Mesin dan Peralatan
                                    </a>

                                    <a href="{{ route('report.product-bb-main', ['type' => 'BJ', 'state' => 'archive']) }}"
                                        class="px-2 lg:py-1.5 py-2 w-full flex items-center rounded-md transition-colors text-left text-gray-800 hover:bg-red-50 hover:text-red-600 focus-visible:bg-red-50 focus-visible:text-red-600 disabled:opacity-50 disabled:cursor-not-allowed border-b-2">
                                        6. Laporan Pertanggungjawaban Mutasi
                                        Bahan Jadi
                                    </a>
                                    <a href="{{ route('report.product-bb-main', ['type' => 'BS', 'state' => 'archive']) }}"
                                        class="px-2 lg:py-1.5 py-2 w-full flex items-center rounded-md transition-colors text-left text-gray-800 hover:bg-red-50 hover:text-red-600 focus-visible:bg-red-50 focus-visible:text-red-600 disabled:opacity-50 disabled:cursor-not-allowed border-b-2">
                                        7. Laporan Pertanggungjawaban Mutasi
                                        Barang Reject dan Scrap
                                    </a>
                                    {{-- <a href="{{ route('report.product-scrap-main', ['state' => 'archive']) }}"
                                        class="px-2 lg:py-1.5 py-2 w-full flex items-center rounded-md transition-colors text-left text-gray-800 hover:bg-red-50 hover:text-red-600 focus-visible:bg-red-50 focus-visible:text-red-600 disabled:opacity-50 disabled:cursor-not-allowed border-b-2">
                                        9. Laporan Pertanggungjawaban Scrap
                                    </a> --}}
                                </div>
                            </div>
                        </li>

                        {{-- <li>
                            <a class="text-gray-500 transition hover:text-gray-500/75" href="#"> Gudang </a>
                        </li>

                        <li>
                            <a class="text-gray-500 transition hover:text-gray-500/75" href="#"> Activity Log </a>
                        </li> --}}

                        <li>
                            <a class="text-gray-500 transition hover:text-gray-500/75" href="/logout"> Log Out </a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
    </header>

    <div class="border-b border-gray-200 py-2 px-4 shrink-0">
        <div class="text-xs text-gray-700">
            <div class="font-semibold">HOGY</div>
            <div>PT Hogy Indonesia</div>
            <div>MM 2100 Industrial Town, Blok M3-1 Cikarang Barat, Bekasi 17520</div>
            <div>P: +62 21 8980165, F: +62 21 8980166, E: purchasing@hogy.co.id</div>
        </div>
    </div>

    <main class="flex-1 overflow-y-auto flex flex-col items-center min-h-0">
        <p class="px-4 py-1.5 text-gray-600 text-sm shrink-0">Custom and Excise Reports</p>
        @yield('content')
    </main>

    <footer class="shrink-0 py-1.5">
        <p class="text-xs text-gray-600">Copyright PT Hogy Indonesia &copy;2026</p>
    </footer>
</body>

</html>
