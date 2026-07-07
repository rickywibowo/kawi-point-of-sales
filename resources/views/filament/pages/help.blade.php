<x-filament-panels::page>
    <div class="space-y-6">
        <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <div class="space-y-2">
                <p class="text-sm font-semibold text-primary-600 dark:text-primary-400">KAWI POS Back Office</p>
                <h2 class="text-xl font-semibold text-gray-950 dark:text-white">Panduan baseline Laravel + Filament</h2>
                <p class="max-w-3xl text-sm leading-6 text-gray-600 dark:text-gray-300">
                    KAWI POS sekarang difokuskan ke Laravel + Filament. Vue/Nuxt dan modul operasional lama sedang
                    dipause sampai baseline business, outlet, RBAC, context, dan accounting stabil.
                </p>
            </div>
        </section>

        <div class="grid gap-6 lg:grid-cols-2">
            <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <h3 class="text-base font-semibold text-gray-950 dark:text-white">Login dan Context</h3>
                <div class="mt-4 space-y-3 text-sm leading-6 text-gray-600 dark:text-gray-300">
                    <p>Buka back office di <span class="font-mono text-gray-950 dark:text-white">/admin</span>.</p>
                    <div class="rounded-md bg-gray-50 p-3 font-mono text-xs text-gray-800 dark:bg-gray-800 dark:text-gray-100">
                        Email: owner@kawipos.local<br>
                        Password: password
                    </div>
                    <p>Setelah login, pilih business dan outlet aktif melalui menu Active Business / Outlet.</p>
                </div>
            </section>

            <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <h3 class="text-base font-semibold text-gray-950 dark:text-white">Maintenance Lokal</h3>
                <div class="mt-4 space-y-3 text-sm leading-6 text-gray-600 dark:text-gray-300">
                    <p>Jalankan migration tanpa menghapus data:</p>
                    <div class="rounded-md bg-gray-50 p-3 font-mono text-xs text-gray-800 dark:bg-gray-800 dark:text-gray-100">
                        php artisan migrate
                    </div>
                    <p>Reset baseline data dari awal:</p>
                    <div class="rounded-md bg-gray-50 p-3 font-mono text-xs text-gray-800 dark:bg-gray-800 dark:text-gray-100">
                        php artisan migrate:fresh --seed
                    </div>
                </div>
            </section>
        </div>

        <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <h3 class="text-base font-semibold text-gray-950 dark:text-white">Menu Aktif</h3>
            <div class="mt-4 grid gap-4 lg:grid-cols-4">
                <div class="rounded-md bg-gray-50 p-4 dark:bg-gray-800">
                    <p class="font-semibold text-gray-950 dark:text-white">Administration</p>
                    <p class="mt-2 text-sm leading-6 text-gray-600 dark:text-gray-300">
                        Businesses, Branches, dan Active Business / Outlet.
                    </p>
                </div>
                <div class="rounded-md bg-gray-50 p-4 dark:bg-gray-800">
                    <p class="font-semibold text-gray-950 dark:text-white">Master Data</p>
                    <p class="mt-2 text-sm leading-6 text-gray-600 dark:text-gray-300">
                        Units untuk satuan produk dan bahan per business aktif.
                    </p>
                </div>
                <div class="rounded-md bg-gray-50 p-4 dark:bg-gray-800">
                    <p class="font-semibold text-gray-950 dark:text-white">Accounting</p>
                    <p class="mt-2 text-sm leading-6 text-gray-600 dark:text-gray-300">
                        Chart of Accounts, Outlet Account Mappings, dan Journal Entries.
                    </p>
                </div>
                <div class="rounded-md bg-gray-50 p-4 dark:bg-gray-800">
                    <p class="font-semibold text-gray-950 dark:text-white">Support</p>
                    <p class="mt-2 text-sm leading-6 text-gray-600 dark:text-gray-300">
                        Halaman Help ini untuk catatan penggunaan baseline.
                    </p>
                </div>
            </div>
        </section>

        <section class="rounded-lg border border-amber-200 bg-amber-50 p-6 dark:border-amber-700 dark:bg-amber-950/30">
            <h3 class="text-base font-semibold text-amber-900 dark:text-amber-100">Catatan Baseline</h3>
            <ul class="mt-4 list-disc space-y-2 pl-5 text-sm leading-6 text-amber-900 dark:text-amber-100">
                <li>COA dibuat per business melalui AccountSeeder.</li>
                <li>Unit dasar dibuat per business melalui UnitSeeder.</li>
                <li>Outlet dipakai sebagai dimensi accounting melalui journal entry dan outlet account mapping.</li>
                <li>Product, category, sales, inventory, recipe, purchasing, dan expense belum dibuat ulang pada baseline ini.</li>
                <li>Jika dropdown kosong, pastikan active context sudah dipilih dan seeder sudah dijalankan.</li>
            </ul>
        </section>
    </div>
</x-filament-panels::page>
