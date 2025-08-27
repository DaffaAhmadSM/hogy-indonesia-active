@extends('main-layout')

@section('content')

	<style>
		[x-cloak] {
			display: none !important;
		}
	</style>

	<div class="flex flex-col gap-0 max-h-full">
		<div class="flex align-middle justify-center">
			<h1 class="text-2xl font-bold mb-4">Laporan Pemasukan Barang</h1>
		</div>
		<div class="flex flex-row px-6 py-2" method="POST" hx-target="#prod-receipt-table-body" hx-swap="innerHTML">
			@csrf
			<div class="antialiased font-sans">
				<div x-data="app()" x-init="[initDate(), getNoOfDays()]" x-cloak>
					<div class="container mx-auto px-4 py-2 md:py-10">
						<div class="mb-5 w-64">
							<label for="datepicker" class="font-bold mb-1 text-gray-700 block">from Date</label>
							<div class="relative">
								<input type="hidden" name="fromDate" x-ref="date" id="fromDate-data"
									value="{{ request('fromDate') }}">
								<input type="text" readonly x-model="datepickerValue" @click="showDatepicker = !showDatepicker"
									@keydown.escape="showDatepicker = false"
									class="w-full pl-4 pr-10 py-3 leading-none rounded-lg shadow-xs focus:outline-hidden use focus:ring-3 focus:ring-blue-500 text-gray-600 font-medium"
									placeholder="Select date">

								<div class="absolute top-0 right-0 px-3 py-2">
									<svg class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
										<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
											d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
									</svg>
								</div>
								<div class="bg-white mt-12 rounded-lg shadow-sm p-4 absolute top-0 left-0 z-10"
									style="width: 17rem" x-show.transition="showDatepicker" @click.away="showDatepicker = false">

									<div class="flex justify-between items-center mb-2">
										<div>
											<span x-text="MONTH_NAMES[month]" class="text-lg font-bold text-gray-800"></span>
											<span x-text="year" class="ml-1 text-lg text-gray-600 font-normal"></span>
										</div>
										<div>
											<button type="button"
												class="transition ease-in-out duration-100 inline-flex cursor-pointer hover:bg-gray-200 p-1 rounded-full"
												:class="{'cursor-not-allowed opacity-25': month == 0 }"
												:disabled="month == 0 ? true : false" @click="month--; getNoOfDays()">
												<svg class="h-6 w-6 text-gray-500 inline-flex" fill="none" viewBox="0 0 24 24"
													stroke="currentColor">
													<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
														d="M15 19l-7-7 7-7" />
												</svg>
											</button>
											<button type="button"
												class="transition ease-in-out duration-100 inline-flex cursor-pointer hover:bg-gray-200 p-1 rounded-full"
												:class="{'cursor-not-allowed opacity-25': month == 11 }"
												:disabled="month == 11 ? true : false" @click="month++; getNoOfDays()">
												<svg class="h-6 w-6 text-gray-500 inline-flex" fill="none" viewBox="0 0 24 24"
													stroke="currentColor">
													<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
														d="M9 5l7 7-7 7" />
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
											<div style="width: 14.28%" class="text-center border p-1 border-transparent text-sm">
											</div>
										</template>
										<template x-for="(date, dateIndex) in no_of_days" :key="dateIndex">
											<div style="width: 14.28%" class="px-1 mb-1">
												<div @click="getDateValue(date)" x-text="date"
													class="cursor-pointer text-center text-sm rounded-full leading-loose transition ease-in-out duration-100"
													:class="{'bg-blue-500 text-white': isToday(date) == true, 'text-gray-700 hover:bg-blue-200': isToday(date) == false }">
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
							<label for="datepicker" class="font-bold mb-1 text-gray-700 block">to Date</label>
							<div class="relative">
								<input type="hidden" name="toDate" x-ref="date" id="toDate-data" value="{{ request('toDate') }}">
								<input type="text" readonly x-model="datepickerValue" @click="showDatepicker = !showDatepicker"
									@keydown.escape="showDatepicker = false"
									class="w-full pl-4 pr-10 py-3 leading-none rounded-lg shadow-xs focus:outline-hidden use focus:ring-3 focus:ring-blue-500 text-gray-600 font-medium"
									placeholder="Select date">

								<div class="absolute top-0 right-0 px-3 py-2">
									<svg class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
										<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
											d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
									</svg>
								</div>
								<div class="bg-white mt-12 rounded-lg shadow-sm p-4 absolute top-0 left-0 z-10"
									style="width: 17rem" x-show.transition="showDatepicker" @click.away="showDatepicker = false">

									<div class="flex justify-between items-center mb-2">
										<div>
											<span x-text="MONTH_NAMES[month]" class="text-lg font-bold text-gray-800"></span>
											<span x-text="year" class="ml-1 text-lg text-gray-600 font-normal"></span>
										</div>
										<div>
											<button type="button"
												class="transition ease-in-out duration-100 inline-flex cursor-pointer hover:bg-gray-200 p-1 rounded-full"
												:class="{'cursor-not-allowed opacity-25': month == 0 }"
												:disabled="month == 0 ? true : false" @click="month--; getNoOfDays()">
												<svg class="h-6 w-6 text-gray-500 inline-flex" fill="none" viewBox="0 0 24 24"
													stroke="currentColor">
													<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
														d="M15 19l-7-7 7-7" />
												</svg>
											</button>
											<button type="button"
												class="transition ease-in-out duration-100 inline-flex cursor-pointer hover:bg-gray-200 p-1 rounded-full"
												:class="{'cursor-not-allowed opacity-25': month == 11 }"
												:disabled="month == 11 ? true : false" @click="month++; getNoOfDays()">
												<svg class="h-6 w-6 text-gray-500 inline-flex" fill="none" viewBox="0 0 24 24"
													stroke="currentColor">
													<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
														d="M9 5l7 7-7 7" />
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
											<div style="width: 14.28%" class="text-center border p-1 border-transparent text-sm">
											</div>
										</template>
										<template x-for="(date, dateIndex) in no_of_days" :key="dateIndex">
											<div style="width: 14.28%" class="px-1 mb-1">
												<div @click="getDateValue(date)" x-text="date"
													class="cursor-pointer text-center text-sm rounded-full leading-loose transition ease-in-out duration-100"
													:class="{'bg-blue-500 text-white': isToday(date) == true, 'text-gray-700 hover:bg-blue-200': isToday(date) == false }">
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



			<div class="flex w-full max-w-md flex-col gap-1 text-neutral-600 dark:text-neutral-300 bg-white rounded"
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
								class=" rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 disabled:opacity-50 disabled:cursor-not-allowed h-1/2">
								Search
							</button>
						</div>
					</div>

				</div>
			</div>
		</div>

		<div class="max-h-screen w-full max-w-screen overflow-y-auto overflow-x-hidden">
			<table class="w-full table-fixed divide-y-2 divide-gray-200">
				<thead class="sticky top-0 bg-white ltr:text-left rtl:text-right">
					<tr class="*:font-medium *:text-gray-900">
						<th class="px-3 py-2 whitespace-normal break-words">Jenis</th>
						<th class="px-3 py-2 whitespace-normal break-words">No Aju</th>
						<th class="px-3 py-2 whitespace-normal break-words">No Daftar</th>
						<th class="px-3 py-2 whitespace-normal break-words">Tgl Daftar</th>
						<th class="px-3 py-2 whitespace-normal break-words">Nomor</th>
						<th class="px-3 py-2 whitespace-normal break-words">Tgl Terima</th>
						<th class="px-3 py-2 whitespace-normal break-words">Pengirim</th>
						<th class="px-3 py-2 whitespace-normal break-words">Kode barang</th>
						<th class="px-3 py-2 whitespace-normal break-words">Nama barang</th>
						<th class="px-3 py-2 whitespace-normal break-words">QTY</th>
						<th class="px-3 py-2 whitespace-normal break-words">Satuan</th>
						<th class="px-3 py-2 whitespace-normal break-words">Harga</th>
						<th class="px-3 py-2 whitespace-normal break-words">Nilai</th>
						<th class="px-3 py-2 whitespace-normal break-words">Keterangan</th>
					</tr>
				</thead>

				<tbody class="divide-y divide-gray-200" id="prod-receipt-table-body"
					hx-get="{{ route('report.invent-in-main.search') }}" hx-trigger="load" hx-swap="innerHTML">
				</tbody>
			</table>
		</div>


	</div>

	<script>
		const MONTH_NAMES = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
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
					this.$refs.date.value = selectedDate.getFullYear() + "-" + ('0' + (selectedDate.getMonth() + 1)).slice(-2) + "-" + ('0' + selectedDate.getDate()).slice(-2);

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