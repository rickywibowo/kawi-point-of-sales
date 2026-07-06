<x-filament-panels::page>
    <div class="space-y-6">
        <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <div class="space-y-2">
                <p class="text-sm font-semibold text-primary-600 dark:text-primary-400">KAWI POS Back Office</p>
                <h2 class="text-xl font-semibold text-gray-950 dark:text-white">Panduan cepat penggunaan Filament</h2>
                <p class="max-w-3xl text-sm leading-6 text-gray-600 dark:text-gray-300">
                    Halaman ini dipakai sebagai catatan operasional singkat untuk CRUD back office. Vue dashboard tetap
                    dipakai untuk layar kasir dan operasional harian, sedangkan Filament dipakai untuk master data,
                    setup POS, inventory, production, purchasing, dan administrasi data.
                </p>
            </div>
        </section>

        <div class="grid gap-6 lg:grid-cols-2">
            <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <h3 class="text-base font-semibold text-gray-950 dark:text-white">Login dan Akses</h3>
                <div class="mt-4 space-y-3 text-sm leading-6 text-gray-600 dark:text-gray-300">
                    <p>Buka back office di <span class="font-mono text-gray-950 dark:text-white">/admin</span>.</p>
                    <p>Login demo lokal:</p>
                    <div class="rounded-md bg-gray-50 p-3 font-mono text-xs text-gray-800 dark:bg-gray-800 dark:text-gray-100">
                        Email: owner@kawi.test<br>
                        Password: password
                    </div>
                    <p>
                        Kalau halaman tidak bisa dibuka, pastikan server lokal berjalan dan database sudah dimigrasi.
                    </p>
                </div>
            </section>

            <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <h3 class="text-base font-semibold text-gray-950 dark:text-white">Maintenance Lokal</h3>
                <div class="mt-4 space-y-3 text-sm leading-6 text-gray-600 dark:text-gray-300">
                    <p>Jalankan server lokal:</p>
                    <div class="rounded-md bg-gray-50 p-3 font-mono text-xs text-gray-800 dark:bg-gray-800 dark:text-gray-100">
                        php artisan serve
                    </div>
                    <p>Jalankan migration tanpa menghapus data:</p>
                    <div class="rounded-md bg-gray-50 p-3 font-mono text-xs text-gray-800 dark:bg-gray-800 dark:text-gray-100">
                        php artisan migrate
                    </div>
                    <p>Reset data demo dari awal:</p>
                    <div class="rounded-md bg-gray-50 p-3 font-mono text-xs text-gray-800 dark:bg-gray-800 dark:text-gray-100">
                        php artisan migrate:fresh --seed
                    </div>
                </div>
            </section>
        </div>

        <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <h3 class="text-base font-semibold text-gray-950 dark:text-white">Alur Master Data</h3>
            <div class="mt-4 grid gap-4 md:grid-cols-3">
                <div class="rounded-md bg-gray-50 p-4 dark:bg-gray-800">
                    <p class="font-semibold text-gray-950 dark:text-white">1. Buat setup dasar</p>
                    <p class="mt-2 text-sm leading-6 text-gray-600 dark:text-gray-300">
                        Isi Unit of Measures, Taxes, Categories, Suppliers, Customers, Warehouses, dan Kitchen Stations.
                    </p>
                </div>
                <div class="rounded-md bg-gray-50 p-4 dark:bg-gray-800">
                    <p class="font-semibold text-gray-950 dark:text-white">2. Buat produk</p>
                    <p class="mt-2 text-sm leading-6 text-gray-600 dark:text-gray-300">
                        Buka Products, pilih kategori, unit, tax, dan kitchen station bila produk perlu dikirim ke dapur.
                        Pastikan context Branch aktif sudah benar sebelum membuat Categories dan Products agar tiap cabang bisa punya katalog berbeda.
                    </p>
                </div>
                <div class="rounded-md bg-gray-50 p-4 dark:bg-gray-800">
                    <p class="font-semibold text-gray-950 dark:text-white">3. Setup POS</p>
                    <p class="mt-2 text-sm leading-6 text-gray-600 dark:text-gray-300">
                        Atur Dining Tables, Promotions, dan Kitchen Stations sebelum transaksi dine-in atau kitchen flow.
                    </p>
                </div>
            </div>
        </section>

        <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <h3 class="text-base font-semibold text-gray-950 dark:text-white">Menu Back Office</h3>
            <div class="mt-4 grid gap-4 lg:grid-cols-2">
                <div class="space-y-3">
                    <div>
                        <p class="font-semibold text-gray-950 dark:text-white">Master Data</p>
                        <p class="text-sm leading-6 text-gray-600 dark:text-gray-300">
                            Categories, Products, Suppliers, Customers, Unit of Measures, Taxes, dan Warehouses.
                        </p>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-950 dark:text-white">POS Setup</p>
                        <p class="text-sm leading-6 text-gray-600 dark:text-gray-300">
                            Dining Tables, Promotions, dan Kitchen Stations.
                        </p>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-950 dark:text-white">Inventory</p>
                        <p class="text-sm leading-6 text-gray-600 dark:text-gray-300">
                            Stock Balances, Stock Adjustments, Stock Opnames, dan Stock Transfers.
                        </p>
                    </div>
                </div>
                <div class="space-y-3">
                    <div>
                        <p class="font-semibold text-gray-950 dark:text-white">Production</p>
                        <p class="text-sm leading-6 text-gray-600 dark:text-gray-300">
                            Recipes dan Production Orders.
                        </p>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-950 dark:text-white">Purchasing</p>
                        <p class="text-sm leading-6 text-gray-600 dark:text-gray-300">
                            Purchase Orders, Goods Receipts, Purchase Returns, Supplier Payables, dan Supplier Payments.
                        </p>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-950 dark:text-white">Administration</p>
                        <p class="text-sm leading-6 text-gray-600 dark:text-gray-300">
                            Context, Businesses, dan Branches untuk memilih scope kerja dan mengatur perusahaan/cabang.
                        </p>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-950 dark:text-white">CRM</p>
                        <p class="text-sm leading-6 text-gray-600 dark:text-gray-300">
                            Customers dan data pendukung pelanggan.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <section class="rounded-lg border border-amber-200 bg-amber-50 p-6 dark:border-amber-700 dark:bg-amber-950/30">
            <h3 class="text-base font-semibold text-amber-900 dark:text-amber-100">Catatan Penting</h3>
            <ul class="mt-4 list-disc space-y-2 pl-5 text-sm leading-6 text-amber-900 dark:text-amber-100">
                <li>Jika dropdown relasi error tabel tidak ditemukan, jalankan <span class="font-mono">php artisan migrate</span>.</li>
                <li>Jika cabang berbeda jenis usaha, switch context Branch terlebih dulu, lalu buat Categories, Products, Warehouses, Dining Tables, Kitchen Stations, dan dokumen operasional.</li>
                <li>Jika ingin data demo lengkap dari awal, gunakan <span class="font-mono">php artisan migrate:fresh --seed</span>.</li>
                <li>Detail item dokumen masih dapat ditingkatkan dengan Relation Manager Filament pada tahap lanjutan.</li>
            </ul>
        </section>
    </div>
</x-filament-panels::page>
