@php
    $user = auth()->user();
    $contexts = $user ? \App\Support\UserContextOptions::forUser($user) : [];
    $activeBusinessId = session('active_business_id');
    $activeOutletId = session('active_outlet_id');
    $activeBusiness = collect($contexts)->firstWhere('id', (int) $activeBusinessId);
    $outlets = $activeBusiness['branches'] ?? [];
@endphp

@if ($user && count($contexts) > 0)
    <div class="hidden items-center gap-2 md:flex">
        <form method="POST" action="{{ route('filament.active-context.header-switch') }}">
            @csrf
            <label class="sr-only" for="header-active-business">Business</label>
            <select
                id="header-active-business"
                name="business_id"
                class="block h-9 w-44 rounded-lg border-gray-300 bg-white text-xs shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                onchange="this.form.submit()"
            >
                <option value="">Business</option>
                @foreach ($contexts as $business)
                    <option value="{{ $business['id'] }}" @selected((int) $activeBusinessId === (int) $business['id'])>
                        {{ $business['name'] }}
                    </option>
                @endforeach
            </select>
        </form>

        <form method="POST" action="{{ route('filament.active-context.header-switch') }}">
            @csrf
            <input type="hidden" name="business_id" value="{{ $activeBusinessId }}">

            <label class="sr-only" for="header-active-outlet">Outlet</label>
            <select
                id="header-active-outlet"
                name="outlet_id"
                class="block h-9 w-44 rounded-lg border-gray-300 bg-white text-xs shadow-sm focus:border-primary-500 focus:ring-primary-500 disabled:opacity-60 dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                @disabled(! $activeBusinessId)
                onchange="this.form.submit()"
            >
                <option value="">Outlet</option>
                @foreach ($outlets as $outlet)
                    <option value="{{ $outlet['id'] }}" @selected((int) $activeOutletId === (int) $outlet['id'])>
                        {{ $outlet['name'] }} ({{ $outlet['code'] }})
                    </option>
                @endforeach
            </select>
        </form>
    </div>
@endif
