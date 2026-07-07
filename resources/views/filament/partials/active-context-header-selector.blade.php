@php
    $user = auth()->user();
    $contexts = $user ? \App\Support\UserContextOptions::forUser($user) : [];
    $activeBusinessId = session('active_business_id');
    $activeOutletId = session('active_outlet_id');
    $activeBusiness = collect($contexts)->firstWhere('id', (int) $activeBusinessId);
    $outlets = $activeBusiness['branches'] ?? [];
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
        <form method="POST" action="{{ route('filament.active-context.header-switch') }}">
            @csrf
            <label class="sr-only" for="header-active-business">Business</label>
            <select
                id="header-active-business"
                name="business_id"
                class="block h-8 max-w-52 rounded-full border-gray-200 bg-gray-50 px-3 text-xs font-medium text-gray-800 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                onchange="this.form.submit()"
            >
                <option value="">Select</option>
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
                class="block h-8 max-w-44 rounded-full border-gray-200 bg-gray-50 px-3 text-xs font-medium text-gray-800 shadow-sm focus:border-primary-500 focus:ring-primary-500 disabled:opacity-60 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                @disabled(! $activeBusinessId)
                onchange="this.form.submit()"
            >
                <option value="">Select</option>
                @foreach ($outlets as $outlet)
                    <option value="{{ $outlet['id'] }}" @selected((int) $activeOutletId === (int) $outlet['id'])>
                        {{ $shortOutletName($outlet, $activeBusiness) }}
                    </option>
                @endforeach
            </select>
        </form>
    </div>
@endif
