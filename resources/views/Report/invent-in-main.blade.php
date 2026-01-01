@extends('main-layout')

@section('content')
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>

    <div class="flex flex-col gap-0 max-h-full">
        <div class="flex align-middle justify-center">
            <h1 class="text-2xl font-bold mb-4">Laporan Pemasukan Barang {{ $state != 'active' ? $state : '' }}</h1>
        </div>
        <div class="flex flex-row px-6 py-2" method="POST" hx-target="#prod-receipt-table-body" hx-swap="innerHTML">
            @csrf
            <div class="antialiased font-sans">
                <div x-data="app()" x-init="[initDate(), getNoOfDays()]" x-cloak>
                    <div class="container mx-auto px-4 py-2 md:py-10">
                        <div class="mb-5 w-64">
                            <label for="datepicker" class="font-bold mb-1 text-gray-700 block">From date</label>
                            <div class="relative">
                                <input type="hidden" name="fromDate" x-ref="date" id="fromDate-data"
                                    value="{{ request('fromDate') }}">
                                <input type="text" readonly x-model="datepickerValue"
                                    @click="showDatepicker = !showDatepicker" @keydown.escape="showDatepicker = false"
                                    class="w-full pl-4 pr-10 py-3 leading-none rounded-lg shadow-xs focus:outline-hidden use focus:ring-3 focus:ring-blue-500 text-gray-600 font-medium"
                                    placeholder="Select date">

                                <div class="absolute top-0 right-0 px-3 py-2">
                                    <svg class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <div class="bg-white mt-12 rounded-lg shadow-sm p-4 absolute top-0 left-0 z-10"
                                    style="width: 17rem" x-show.transition="showDatepicker"
                                    @click.away="showDatepicker = false">

                                    <div class="flex justify-between items-center mb-2">
                                        <div>
                                            <span x-text="MONTH_NAMES[month]"
                                                class="text-lg font-bold text-gray-800"></span>
                                            <span x-text="year" class="ml-1 text-lg text-gray-600 font-normal"></span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <button type="button"
                                                class="transition ease-in-out duration-100 inline-flex cursor-pointer hover:bg-gray-200 p-1 rounded-full"
                                                @click="year--; getNoOfDays()" title="Previous Year">
                                                <svg class="h-6 w-6 text-gray-500 inline-flex" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
                                                </svg>
                                            </button>
                                            <button type="button"
                                                class="transition ease-in-out duration-100 inline-flex cursor-pointer hover:bg-gray-200 p-1 rounded-full"
                                                @click="if (month == 0) { month = 11; year--; } else { month--; } getNoOfDays()"
                                                title="Previous Month">
                                                <svg class="h-6 w-6 text-gray-500 inline-flex" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M15 19l-7-7 7-7" />
                                                </svg>
                                            </button>
                                            <button type="button"
                                                class="transition ease-in-out duration-100 inline-flex cursor-pointer hover:bg-gray-200 p-1 rounded-full"
                                                @click="if (month == 11) { month = 0; year++; } else { month++; } getNoOfDays()"
                                                title="Next Month">
                                                <svg class="h-6 w-6 text-gray-500 inline-flex" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M9 5l7 7-7 7" />
                                                </svg>
                                            </button>
                                            <button type="button"
                                                class="transition ease-in-out duration-100 inline-flex cursor-pointer hover:bg-gray-200 p-1 rounded-full"
                                                @click="year++; getNoOfDays()" title="Next Year">
                                                <svg class="h-6 w-6 text-gray-500 inline-flex" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M13 5l7 7-7 7M5 5l7 7-7 7" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="flex flex-wrap mb-3 -mx-1">
                                        <template x-for="(day, index) in DAYS" :key="index">
                                            <div style="width: 14.26%" class="px-1">
                                                <div x-text="day" class="text-gray-800 font-medium text-center text-xs">
                                                </div>
                                            </div>
                                        </template>
                                    </div>

                                    <div class="flex flex-wrap -mx-1">
                                        <template x-for="blankday in blankdays">
                                            <div style="width: 14.28%"
                                                class="text-center border p-1 border-transparent text-sm">
                                            </div>
                                        </template>
                                        <template x-for="(date, dateIndex) in no_of_days" :key="dateIndex">
                                            <div style="width: 14.28%" class="px-1 mb-1">
                                                <div @click="getDateValue(date)" x-text="date"
                                                    class="cursor-pointer text-center text-sm rounded-full leading-loose transition ease-in-out duration-100"
                                                    :class="{
                                                        'bg-blue-500 text-white': isToday(date) ==
                                                            true,
                                                        'text-gray-700 hover:bg-blue-200': isToday(date) ==
                                                            false
                                                    }">
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>

                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <div class="antialiased font-sans">
                <div x-data="app()" x-init="[initDate(), getNoOfDays()]" x-cloak>
                    <div class="container mx-auto px-4 py-2 md:py-10">
                        <div class="mb-5 w-64">
                            <label for="datepicker" class="font-bold mb-1 text-gray-700 block">To date</label>
                            <div class="relative">
                                <input type="hidden" name="toDate" x-ref="date" id="toDate-data"
                                    value="{{ request('toDate') }}">
                                <input type="text" readonly x-model="datepickerValue"
                                    @click="showDatepicker = !showDatepicker" @keydown.escape="showDatepicker = false"
                                    class="w-full pl-4 pr-10 py-3 leading-none rounded-lg shadow-xs focus:outline-hidden use focus:ring-3 focus:ring-blue-500 text-gray-600 font-medium"
                                    placeholder="Select date">

                                <div class="absolute top-0 right-0 px-3 py-2">
                                    <svg class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <div class="bg-white mt-12 rounded-lg shadow-sm p-4 absolute top-0 left-0 z-10"
                                    style="width: 17rem" x-show.transition="showDatepicker"
                                    @click.away="showDatepicker = false">

                                    <div class="flex justify-between items-center mb-2">
                                        <div>
                                            <span x-text="MONTH_NAMES[month]"
                                                class="text-lg font-bold text-gray-800"></span>
                                            <span x-text="year" class="ml-1 text-lg text-gray-600 font-normal"></span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <button type="button"
                                                class="transition ease-in-out duration-100 inline-flex cursor-pointer hover:bg-gray-200 p-1 rounded-full"
                                                @click="year--; getNoOfDays()" title="Previous Year">
                                                <svg class="h-6 w-6 text-gray-500 inline-flex" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
                                                </svg>
                                            </button>
                                            <button type="button"
                                                class="transition ease-in-out duration-100 inline-flex cursor-pointer hover:bg-gray-200 p-1 rounded-full"
                                                @click="if (month == 0) { month = 11; year--; } else { month--; } getNoOfDays()"
                                                title="Previous Month">
                                                <svg class="h-6 w-6 text-gray-500 inline-flex" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M15 19l-7-7 7-7" />
                                                </svg>
                                            </button>
                                            <button type="button"
                                                class="transition ease-in-out duration-100 inline-flex cursor-pointer hover:bg-gray-200 p-1 rounded-full"
                                                @click="if (month == 11) { month = 0; year++; } else { month++; } getNoOfDays()"
                                                title="Next Month">
                                                <svg class="h-6 w-6 text-gray-500 inline-flex" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M9 5l7 7-7 7" />
                                                </svg>
                                            </button>
                                            <button type="button"
                                                class="transition ease-in-out duration-100 inline-flex cursor-pointer hover:bg-gray-200 p-1 rounded-full"
                                                @click="year++; getNoOfDays()" title="Next Year">
                                                <svg class="h-6 w-6 text-gray-500 inline-flex" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M13 5l7 7-7 7M5 5l7 7-7 7" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="flex flex-wrap mb-3 -mx-1">
                                        <template x-for="(day, index) in DAYS" :key="index">
                                            <div style="width: 14.26%" class="px-1">
                                                <div x-text="day" class="text-gray-800 font-medium text-center text-xs">
                                                </div>
                                            </div>
                                        </template>
                                    </div>

                                    <div class="flex flex-wrap -mx-1">
                                        <template x-for="blankday in blankdays">
                                            <div style="width: 14.28%"
                                                class="text-center border p-1 border-transparent text-sm">
                                            </div>
                                        </template>
                                        <template x-for="(date, dateIndex) in no_of_days" :key="dateIndex">
                                            <div style="width: 14.28%" class="px-1 mb-1">
                                                <div @click="getDateValue(date)" x-text="date"
                                                    class="cursor-pointer text-center text-sm rounded-full leading-loose transition ease-in-out duration-100"
                                                    :class="{
                                                        'bg-blue-500 text-white': isToday(date) ==
                                                            true,
                                                        'text-gray-700 hover:bg-blue-200': isToday(date) ==
                                                            false
                                                    }">
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>

                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <div class="flex w-full max-w-md flex-col gap-1 text-neutral-600" x-data="{ search: '' }">
                <div class="container mx-auto px-4 py-2">
                    <div class="flex w-full max-w-md flex-col gap-1 text-neutral-600 px-4 py-2">
                        <label for="keyword" class="w-fit pl-0.5 text-sm">Search</label>

                        <!-- Baris untuk input dan tombol aksi -->
                        <!-- 'items-end' akan menyelaraskan semua item di bagian bawah -->
                        <div class="flex flex-row gap-2 w-full items-end">
                            <!-- Bungkus input agar bisa tumbuh mengisi ruang -->
                            <div class="flex-grow">
                                <input id="keyword" type="text"
                                    class="w-full rounded-sm border border-neutral-300 bg-neutral-50 px-2 py-2 text-sm focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-black disabled:cursor-not-allowed disabled:opacity-75"
                                    name="keyword" placeholder="search" x-model="search" />
                            </div>

                            <!-- Tombol Search -->
                            <button hx-get="{{ route('report.invent-in-main.search', ['state' => $state]) }}"
                                hx-include="#fromDate-data, #toDate-data, #keyword"
                                class="flex-shrink-0 rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 disabled:opacity-50 disabled:cursor-not-allowed">
                                Search
                            </button>

                            <!-- Tombol Export -->
                            <button hx-post="{{ route('report.invent-in-main.export', ['state' => $state]) }}"
                                hx-include="[name=_token], #fromDate-data, #toDate-data, #keyword"
                                hx-target="#export-area" hx-swap="innerHTML" hx-indicator="#export-spinner"
                                class="flex-shrink-0 rounded-md bg-green-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500">
                                Export
                            </button>
                        </div>

                        <!-- Area untuk status ekspor dan spinner, dipisahkan dari baris aksi -->
                        <div class="flex flex-row items-center gap-2 mt-2 min-h-[30px]">
                            <!-- min-h untuk mencegah layout shift -->
                            <div id="export-area" class="w-full max-w-lg space-y-2">
                                <div for="File"
                                    class="block rounded border border-gray-300 p-4 text-gray-900 shadow-sm sm:p-6">
                                    <div class="flex items-center justify-center gap-4">
                                        <span class="font-medium"> There are no export queues </span>

                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                            stroke-width="1.5" stroke="currentColor" class="size-6">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M7.5 7.5h-.75A2.25 2.25 0 0 0 4.5 9.75v7.5a2.25 2.25 0 0 0 2.25 2.25h7.5a2.25 2.25 0 0 0 2.25-2.25v-7.5a2.25 2.25 0 0 0-2.25-2.25h-.75m0-3-3-3m0 0-3 3m3-3v11.25m6-2.25h.75a2.25 2.25 0 0 1 2.25 2.25v7.5a2.25 2.25 0 0 1-2.25 2.25h-7.5a2.25 2.25 0 0 1-2.25-2.25v-.75" />
                                        </svg>
                                    </div>
                                </div>
                            </div>

                            <div class="htmx-indicator" id="export-spinner">
                                @include('components.loading-spinner')
                            </div>
                            <span id="search-spinner" class="htmx-indicator">
                                @include('components.loading-spinner')
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- <div class="flex w-full max-w-md flex-col gap-1 text-neutral-600 dark:text-neutral-300 bg-white rounded"
				x-data="{ search: '' }">
				<div class="container mx-auto px-4 py-2 md:py-10">
					<div
						class="flex w-full max-w-md flex-col gap-1 text-neutral-600 dark:text-neutral-300 bg-white px-4 py-2 rounded">
						<label for="keyword" class="w-fit pl-0.5 text-sm">Search</label>
						<div class="flex flex-row gap-2 w-full">
							<input id="keyword" type="text"
								class="w-full rounded-sm border border-neutral-300 bg-neutral-50 px-2 py-2 text-sm focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-black disabled:cursor-not-allowed disabled:opacity-75 dark:border-neutral-700 dark:bg-neutral-900/50 dark:focus-visible:outline-white"
								name="keyword" placeholder="search" x-model="search" />
							<button hx-get="{{ route('report.invent-in-main.search') }}"
								hx-include="#fromDate-data, #toDate-data, #keyword"
								class=" rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 disabled:opacity-50 disabled:cursor-not-allowed h-1/2">
								Search
							</button>

							<a href="{{ route('report.invent-in-main.export') }}" @click="
									 const from = document.getElementById('fromDate-data')?.value || '';
									 const to = document.getElementById('toDate-data')?.value || '';
									 const kw = document.getElementById('keyword')?.value || '';
									 $el.href = '{{ route('report.invent-in-main.export') }}'
									   + '?fromDate=' + encodeURIComponent(from)
									   + '&toDate=' + encodeURIComponent(to)
									   + '&keyword=' + encodeURIComponent(kw);
								   " download
								class=" rounded-md bg-green-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 disabled:opacity-50 disabled:cursor-not-allowed h-1/2">
								Export
							</a>

						</div>
					</div>

				</div>
			</div> --}}
        </div>

        <div class="max-h-screen w-full max-w-screen overflow-y-auto overflow-x-hidden">
            <div class="max-h-screen w-full max-w-screen overflow-y-auto overflow-x-hidden">
                <table class="w-full table-fixed border-separate border-spacing-0">
                    <thead class="sticky top-0 bg-white ltr:text-left rtl:text-right">
                        <tr class="text-center">
                            <th colspan="3"
                                class="px-3 py-2 whitespace-normal break-words text-center border border-gray-400 border-r-0">
                                Data Pemasukan Barang
                            </th>
                            <th colspan="2"
                                class="px-3 py-2 whitespace-normal break-words text-center border border-gray-400">
                                Bukti Pengiriman Barang
                            </th>

                            <th class="px-3 py-2 whitespace-normal break-words border border-gray-400 border-l-0"
                                rowspan="2">Pengirim Barang</th>
                            <th class="px-3 py-2 whitespace-normal break-words border border-gray-400 border-l-0"
                                rowspan="2">Kode barang</th>
                            <th class="px-3 py-2 whitespace-normal break-words border border-gray-400 border-l-0"
                                rowspan="2">Nama barang</th>
                            <th class="px-3 py-2 whitespace-normal break-words border border-gray-400 border-l-0"
                                rowspan="2">Jumlah</th>
                            <th class="px-3 py-2 whitespace-normal break-words border border-gray-400 border-l-0"
                                rowspan="2">Satuan</th>
                            <th class="px-3 py-2 whitespace-normal break-words border border-gray-400 border-l-0"
                                rowspan="2">Nilai</th>
                        </tr>
                        <tr class="text-center">
                            <th class="px-3 py-2 whitespace-normal break-words border border-gray-400 border-t-0">Jenis
                            </th>
                            <th
                                class="px-3 py-2 whitespace-normal break-words border border-gray-400 border-t-0 border-l-0">
                                Nomor</th>
                            <th
                                class="px-3 py-2 whitespace-normal break-words border border-gray-400 border-t-0 border-l-0 border-r-0">
                                Tanggal</th>
                            <th class="px-3 py-2 whitespace-normal break-words border border-gray-400 border-t-0">
                                nomor</th>
                            <th
                                class="px-3 py-2 whitespace-normal break-words border border-gray-400 border-t-0 border-l-0">
                                tanggal</th>
                        </tr>
                    </thead>

                    <tbody class="" id="prod-receipt-table-body">

                    <tbody class="divide-y divide-gray-200" id="prod-receipt-table-body">
                        <tr hx-get="{{ route('report.invent-in-main.search', ['state' => $state]) }}" hx-trigger="load"
                            hx-swap="outerHTML">
                            <td colspan="4" class="px-3 py-2 whitespace-normal break-words align-middle text-center">
                                @include('components.loading-spinner');
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>


        </div>

        @include('components.hx.toast')

        <script>
            const MONTH_NAMES = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September',
                'October', 'November', 'December'
            ];
            const DAYS = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

            function app() {
                return {
                    showDatepicker: false,
                    datepickerValue: '',

                    month: '',
                    year: '',
                    no_of_days: [],
                    blankdays: [],
                    days: ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],

                    initDate() {
                        let base;
                        const v = this.$refs?.date?.value;
                        if (v) {
                            const parts = v.split('-').map(Number);
                            const y = parts[0];
                            const m = (parts[1] || 1) - 1; // 0-based
                            const d = parts[2] || 1;
                            base = new Date(y, m, d);
                        } else {
                            base = new Date();
                        }
                        this.month = base.getMonth();
                        this.year = base.getFullYear();
                        this.datepickerValue = new Date(this.year, this.month, base.getDate()).toDateString();
                    },

                    isToday(date) {
                        const today = new Date();
                        const d = new Date(this.year, this.month, date);

                        return today.toDateString() === d.toDateString() ? true : false;
                    },

                    getDateValue(date) {
                        let selectedDate = new Date(this.year, this.month, date);
                        this.datepickerValue = selectedDate.toDateString();

                        // Month is zero-based in JS Date; add 1 for YYYY-MM-DD
                        this.$refs.date.value = selectedDate.getFullYear() + "-" + ('0' + (selectedDate.getMonth() + 1)).slice(-
                            2) + "-" + ('0' + selectedDate.getDate()).slice(-2);

                        console.log(this.$refs.date.value);

                        this.showDatepicker = false;
                    },

                    getNoOfDays() {
                        let daysInMonth = new Date(this.year, this.month + 1, 0).getDate();

                        // find where to start calendar day of week
                        let dayOfWeek = new Date(this.year, this.month).getDay();
                        let blankdaysArray = [];
                        for (var i = 1; i <= dayOfWeek; i++) {
                            blankdaysArray.push(i);
                        }

                        let daysArray = [];
                        for (var i = 1; i <= daysInMonth; i++) {
                            daysArray.push(i);
                        }

                        this.blankdays = blankdaysArray;
                        this.no_of_days = daysArray;
                    }
                }
            }
        </script>
    @endsection
