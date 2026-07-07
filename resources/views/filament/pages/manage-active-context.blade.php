@php
    $user = auth()->user();
    $contexts = $user ? \App\Support\UserContextOptions::forUser($user) : [];
    $activeBusinessId = session('active_business_id');
    $activeOutletId = session('active_outlet_id');
@endphp

<x-filament-panels::page>
    <div class="space-y-6">
        <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <div class="space-y-2">
                <p class="text-sm font-semibold text-primary-600 dark:text-primary-400">KAWI POS</p>
                <h2 class="text-xl font-semibold text-gray-950 dark:text-white">Pilih business dan outlet aktif</h2>
                <p class="max-w-3xl text-sm leading-6 text-gray-600 dark:text-gray-300">
                    Context ini dipakai oleh semua resource Filament. Setelah dipilih, master data dan operasional otomatis memakai business/outlet aktif.
                </p>
            </div>
        </section>

        <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            @if (session('status'))
                <div class="mb-4 rounded-md border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-200">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('filament.active-context.switch') }}" class="grid gap-4 md:grid-cols-2">
                @csrf

                <label class="block">
                    <span class="text-sm font-medium text-gray-950 dark:text-white">Business</span>
                    <select
                        id="active-business-select"
                        name="business_id"
                        class="mt-2 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                        required
                    >
                        <option value="">Pilih business</option>
                        @foreach ($contexts as $business)
                            <option value="{{ $business['id'] }}" @selected((int) $activeBusinessId === (int) $business['id'])>{{ $business['name'] }}</option>
                        @endforeach
                    </select>
                    @error('business_id')
                        <p class="mt-2 text-sm text-danger-600 dark:text-danger-400">{{ $message }}</p>
                    @enderror
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-gray-950 dark:text-white">Outlet</span>
                    <select
                        id="active-outlet-select"
                        name="outlet_id"
                        class="mt-2 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                        required
                    >
                        <option value="">Pilih outlet</option>
                        @foreach ($contexts as $business)
                            @foreach ($business['branches'] as $outlet)
                                <option
                                    value="{{ $outlet['id'] }}"
                                    data-business-id="{{ $business['id'] }}"
                                    @selected((int) $activeOutletId === (int) $outlet['id'])
                                >
                                    {{ $outlet['name'] }} ({{ $outlet['code'] }})
                                </option>
                            @endforeach
                        @endforeach
                    </select>
                    @error('outlet_id')
                        <p class="mt-2 text-sm text-danger-600 dark:text-danger-400">{{ $message }}</p>
                    @enderror
                </label>

                <div class="md:col-span-2">
                    <button
                        type="submit"
                        class="inline-flex items-center rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
                    >
                        Set Active Context
                    </button>
                </div>
            </form>
        </section>
    </div>

    <script>
        (() => {
            const businessSelect = document.getElementById('active-business-select');
            const outletSelect = document.getElementById('active-outlet-select');

            const filterOutlets = () => {
                const businessId = businessSelect.value;

                [...outletSelect.options].forEach((option) => {
                    if (! option.value) {
                        option.hidden = false;
                        return;
                    }

                    option.hidden = option.dataset.businessId !== businessId;
                });

                const selected = outletSelect.selectedOptions[0];
                if (selected && selected.hidden) {
                    outletSelect.value = '';
                }
            };

            businessSelect.addEventListener('change', filterOutlets);
            filterOutlets();
        })();
    </script>
</x-filament-panels::page>
