@php
    $user = auth()->user();
    $options = $user ? \App\Support\UserContextOptions::selectOptions($user) : [];
    $currentValue = $user?->current_business_id && $user?->current_branch_id
        ? $user->current_business_id.':'.$user->current_branch_id
        : null;
@endphp

<x-filament-panels::page>
    <div class="space-y-6">
        <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <div class="space-y-2">
                <p class="text-sm font-semibold text-primary-600 dark:text-primary-400">KAWI POS Context</p>
                <h2 class="text-xl font-semibold text-gray-950 dark:text-white">Pilih business dan branch aktif</h2>
                <p class="max-w-3xl text-sm leading-6 text-gray-600 dark:text-gray-300">
                    Context ini dipakai oleh CRUD back office. Setelah branch aktif dipilih, Categories, Products,
                    Warehouses, Dining Tables, Kitchen Stations, dan dokumen operasional otomatis mengikuti branch tersebut.
                </p>
            </div>
        </section>

        <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            @if (session('status'))
                <div class="mb-4 rounded-md border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-200">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('filament.context.switch') }}" class="space-y-4">
                @csrf

                <label class="block">
                    <span class="text-sm font-medium text-gray-950 dark:text-white">Business / Branch</span>
                    <select
                        name="context"
                        class="mt-2 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                        required
                    >
                        <option value="">Pilih context</option>
                        @foreach ($options as $value => $label)
                            <option value="{{ $value }}" @selected($currentValue === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                @error('context')
                    <p class="text-sm text-danger-600 dark:text-danger-400">{{ $message }}</p>
                @enderror

                <button
                    type="submit"
                    class="inline-flex items-center rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
                >
                    Set Context
                </button>
            </form>
        </section>
    </div>
</x-filament-panels::page>
