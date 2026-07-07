@php
    $user = auth()->user();
    $contexts = $user ? \App\Support\UserContextOptions::forUser($user) : [];
    $activeBusinessId = session('active_business_id');
    $activeOutletId = session('active_outlet_id');
    $activeBusiness = collect($contexts)->firstWhere('id', (int) $activeBusinessId);
    $outlets = $activeBusiness['branches'] ?? [];
    $activeOutlet = collect($outlets)->firstWhere('id', (int) $activeOutletId);
    $shortOutletName = function (array $outlet, ?array $business): string {
        $name = $outlet['name'];
        $businessName = $business['name'] ?? null;

        if ($businessName && str_starts_with($name, $businessName.' - ')) {
            return str($name)->after($businessName.' - ')->toString();
        }

        return $name;
    };
@endphp

@if ($user && count($contexts) > 0)
    <div class="hidden items-center gap-2 md:flex">
        <details class="group relative">
            <summary class="flex h-8 cursor-pointer list-none items-center gap-1 rounded-full border border-gray-200 bg-white px-3 text-xs font-semibold text-gray-800 shadow-sm transition hover:border-primary-300 hover:bg-primary-50 focus:outline-none focus:ring-2 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 dark:hover:border-primary-700 dark:hover:bg-primary-950/40">
                <span class="max-w-44 truncate">{{ $activeBusiness['name'] ?? 'Select' }}</span>
                <span class="text-gray-400 transition group-open:rotate-180">v</span>
            </summary>

            <div class="absolute right-0 z-50 mt-2 w-64 overflow-hidden rounded-lg border border-gray-200 bg-white p-1 shadow-lg dark:border-gray-700 dark:bg-gray-900">
                @foreach ($contexts as $business)
                    <form method="POST" action="{{ route('filament.active-context.header-switch') }}">
                        @csrf
                        <input type="hidden" name="business_id" value="{{ $business['id'] }}">
                        <button
                            type="submit"
                            class="flex w-full items-center justify-between rounded-md px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-gray-800"
                        >
                            <span class="truncate">{{ $business['name'] }}</span>
                            @if ((int) $activeBusinessId === (int) $business['id'])
                                <span class="h-2 w-2 rounded-full bg-primary-600 dark:bg-primary-400"></span>
                            @endif
                        </button>
                    </form>
                @endforeach
            </div>
        </details>

        <details class="group relative">
            <summary class="flex h-8 cursor-pointer list-none items-center gap-1 rounded-full border border-gray-200 bg-white px-3 text-xs font-semibold text-gray-800 shadow-sm transition hover:border-primary-300 hover:bg-primary-50 focus:outline-none focus:ring-2 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 dark:hover:border-primary-700 dark:hover:bg-primary-950/40">
                <span class="max-w-36 truncate">{{ $activeOutlet ? $shortOutletName($activeOutlet, $activeBusiness) : 'Select' }}</span>
                <span class="text-gray-400 transition group-open:rotate-180">v</span>
            </summary>

            <div class="absolute right-0 z-50 mt-2 w-56 overflow-hidden rounded-lg border border-gray-200 bg-white p-1 shadow-lg dark:border-gray-700 dark:bg-gray-900">
                @forelse ($outlets as $outlet)
                    <form method="POST" action="{{ route('filament.active-context.header-switch') }}">
                        @csrf
                        <input type="hidden" name="business_id" value="{{ $activeBusinessId }}">
                        <input type="hidden" name="outlet_id" value="{{ $outlet['id'] }}">
                        <button
                            type="submit"
                            class="flex w-full items-center justify-between rounded-md px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-gray-800"
                        >
                            <span class="truncate">{{ $shortOutletName($outlet, $activeBusiness) }}</span>
                            @if ((int) $activeOutletId === (int) $outlet['id'])
                                <span class="h-2 w-2 rounded-full bg-primary-600 dark:bg-primary-400"></span>
                            @endif
                        </button>
                    </form>
                @empty
                    <div class="px-3 py-2 text-sm text-gray-500 dark:text-gray-400">Select</div>
                @endforelse
            </div>
        </details>
    </div>
@endif
