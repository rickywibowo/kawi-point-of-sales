<script setup>
import { onMounted, onUnmounted } from 'vue';
import { useFoundationStore } from './stores/foundation';
import { useMasterDataStore } from './stores/masterData';

const foundation = useFoundationStore();
const masterData = useMasterDataStore();

const quickStats = [
    { label: 'Penjualan Hari Ini', value: 'Rp 0', tone: 'emerald' },
    { label: 'Transaksi', value: '0', tone: 'sky' },
    { label: 'Produk Aktif', value: masterData.activeProductCount, tone: 'amber' },
];

const modules = [
    'Kasir',
    'Produk',
    'Inventori',
    'Pelanggan',
    'Laporan',
    'Pengaturan',
];

const updateOnlineStatus = () => foundation.setOnlineStatus(navigator.onLine);

onMounted(() => {
    window.addEventListener('online', updateOnlineStatus);
    window.addEventListener('offline', updateOnlineStatus);
});

onUnmounted(() => {
    window.removeEventListener('online', updateOnlineStatus);
    window.removeEventListener('offline', updateOnlineStatus);
});
</script>

<template>
    <main class="min-h-screen bg-zinc-950 text-white">
        <section class="mx-auto flex min-h-screen w-full max-w-7xl flex-col px-5 py-6 sm:px-8 lg:px-10">
            <header class="flex flex-wrap items-center justify-between gap-4 border-b border-white/10 pb-5">
                <div>
                    <p class="text-sm font-medium uppercase text-emerald-300">KAWI</p>
                    <h1 class="mt-1 text-2xl font-semibold sm:text-3xl">Point of Sale</h1>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <span
                        class="rounded-md border px-3 py-2 text-sm"
                        :class="foundation.isOnline ? 'border-emerald-300/40 text-emerald-200' : 'border-amber-300/50 text-amber-200'"
                    >
                        {{ foundation.isOnline ? 'Online' : 'Offline' }}
                    </span>
                    <button class="rounded-md bg-emerald-400 px-4 py-2 text-sm font-semibold text-zinc-950 transition hover:bg-emerald-300">
                        Mulai Transaksi
                    </button>
                </div>
            </header>

            <div class="grid flex-1 gap-5 py-6 lg:grid-cols-[1.5fr_1fr]">
                <section class="rounded-lg border border-white/10 bg-white/[0.04] p-5 shadow-2xl shadow-black/20">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <p class="text-sm text-zinc-400">Dashboard awal</p>
                            <h2 class="mt-2 text-3xl font-semibold">Siap bangun sistem kasir KAWI.</h2>
                        </div>

                        <span class="rounded-md border border-emerald-300/40 px-3 py-1 text-sm text-emerald-200">
                            Laravel + Vue
                        </span>
                    </div>

                    <div class="mt-6 grid gap-3 rounded-md border border-white/10 bg-zinc-950/70 p-4 text-sm text-zinc-300 sm:grid-cols-4">
                        <p><span class="block text-zinc-500">Kasir</span>{{ foundation.cashier }}</p>
                        <p><span class="block text-zinc-500">Business</span>{{ foundation.business }}</p>
                        <p><span class="block text-zinc-500">Cabang</span>{{ foundation.branch }}</p>
                        <p><span class="block text-zinc-500">Shift</span>{{ foundation.shift }}</p>
                    </div>

                    <div class="mt-8 grid gap-4 sm:grid-cols-3">
                        <article
                            v-for="stat in quickStats"
                            :key="stat.label"
                            class="rounded-md border border-white/10 bg-zinc-900/80 p-4"
                        >
                            <p class="text-sm text-zinc-400">{{ stat.label }}</p>
                            <p class="mt-3 text-2xl font-semibold">{{ stat.value }}</p>
                        </article>
                    </div>
                </section>

                <aside class="rounded-lg border border-white/10 bg-zinc-900 p-5">
                    <div class="flex items-start justify-between gap-4">
                        <h2 class="text-lg font-semibold">Modul POS</h2>
                        <span class="rounded-md bg-zinc-800 px-2 py-1 text-xs text-zinc-300">
                            {{ foundation.unsyncedTransactions }} unsynced
                        </span>
                    </div>
                    <div class="mt-5 grid grid-cols-2 gap-3">
                        <button
                            v-for="module in modules"
                            :key="module"
                            class="rounded-md border border-white/10 bg-white/[0.03] px-3 py-4 text-left text-sm font-medium transition hover:border-emerald-300/50 hover:bg-emerald-300/10"
                        >
                            {{ module }}
                        </button>
                    </div>

                    <div class="mt-6 border-t border-white/10 pt-5">
                        <h3 class="text-sm font-semibold text-zinc-300">Master Data</h3>
                        <div class="mt-3 space-y-3">
                            <article
                                v-for="product in masterData.products"
                                :key="product.sku"
                                class="rounded-md border border-white/10 bg-white/[0.03] p-3"
                            >
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-medium">{{ product.name }}</p>
                                        <p class="mt-1 text-xs uppercase text-zinc-500">{{ product.sku }} / {{ product.type }}</p>
                                    </div>
                                    <p class="text-sm text-emerald-200">
                                        Rp {{ product.price.toLocaleString('id-ID') }}
                                    </p>
                                </div>
                            </article>
                        </div>
                    </div>
                </aside>
            </div>
        </section>
    </main>
</template>
