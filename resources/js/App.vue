<script setup>
import { computed, reactive, ref, onMounted, onUnmounted } from 'vue';
import { useAccountingStore } from './stores/accounting';
import { useAuditStore } from './stores/audit';
import { useCustomersStore } from './stores/customers';
import { useFoundationStore } from './stores/foundation';
import { useInventoryStore } from './stores/inventory';
import { useMasterDataStore } from './stores/masterData';
import { useOfflineStore } from './stores/offline';
import { usePosStore } from './stores/pos';
import { usePurchasingStore } from './stores/purchasing';
import { useReportsStore } from './stores/reports';
import { useUserAccessStore } from './stores/userAccess';

const accounting = useAccountingStore();
const audit = useAuditStore();
const customers = useCustomersStore();
const foundation = useFoundationStore();
const inventory = useInventoryStore();
const masterData = useMasterDataStore();
const offline = useOfflineStore();
const pos = usePosStore();
const purchasing = usePurchasingStore();
const reports = useReportsStore();
const userAccess = useUserAccessStore();

const loginForm = reactive({
    email: 'owner@kawi.test',
    password: 'password',
});
const loginPanelOpen = ref(false);
const dashboardLoading = ref(false);
const activeModule = ref('pos');
const moduleSearch = ref('');

const quickStats = computed(() => [
    { label: 'Penjualan Hari Ini', value: 'Rp 0', tone: 'emerald' },
    { label: 'Transaksi', value: String(pos.todayTransactions), tone: 'sky' },
    { label: 'Produk Aktif', value: masterData.activeProductCount, tone: 'amber' },
    { label: 'Nilai Stok', value: `Rp ${inventory.totalStockValue.toLocaleString('id-ID')}`, tone: 'emerald' },
    { label: 'PO Aktif', value: purchasing.openOrderCount, tone: 'sky' },
    { label: 'Akun COA', value: accounting.accountCount, tone: 'amber' },
    { label: 'Laporan', value: reports.period, tone: 'emerald' },
    { label: 'Pelanggan', value: customers.customerCount, tone: 'sky' },
    { label: 'User', value: userAccess.userCount, tone: 'amber' },
    { label: 'Audit', value: audit.totalEvents, tone: 'emerald' },
]);

const modules = [
    { id: 'pos', label: 'Kasir' },
    { id: 'products', label: 'Produk' },
    { id: 'inventory', label: 'Inventori' },
    { id: 'purchasing', label: 'Purchasing' },
    { id: 'accounting', label: 'Accounting' },
    { id: 'reports', label: 'Laporan' },
    { id: 'customers', label: 'Pelanggan' },
    { id: 'settings', label: 'Pengaturan' },
];

const activeModuleMeta = computed(() => modules.find((module) => module.id === activeModule.value) ?? modules[0]);
const moduleRows = computed(() => {
    const rowMaps = {
        pos: pos.kitchenTickets.map((ticket) => ({
            primary: ticket.number,
            secondary: ticket.station,
            value: ticket.status,
        })),
        products: masterData.products.map((product) => ({
            primary: product.name,
            secondary: product.sku,
            value: `Rp ${product.price.toLocaleString('id-ID')}`,
        })),
        inventory: inventory.stockBalances.map((stock) => ({
            primary: stock.product,
            secondary: `${stock.quantity} ${stock.unit}`,
            value: `Rp ${stock.value.toLocaleString('id-ID')}`,
        })),
        purchasing: purchasing.purchaseOrders.map((order) => ({
            primary: order.number,
            secondary: order.supplier,
            value: order.status,
        })),
        accounting: accounting.paymentSettlements.map((settlement) => ({
            primary: settlement.number,
            secondary: settlement.method,
            value: `Rp ${settlement.variance.toLocaleString('id-ID')}`,
        })),
        reports: reports.reportCards.map((card) => ({
            primary: card.label,
            secondary: reports.period,
            value: `Rp ${card.value.toLocaleString('id-ID')}`,
        })),
        customers: customers.customers.map((customer) => ({
            primary: customer.name,
            secondary: customer.phone,
            value: `${customer.loyaltyPoints} pts`,
        })),
        settings: userAccess.users.map((user) => ({
            primary: user.name,
            secondary: user.email,
            value: user.roles?.[0] ?? 'User',
        })),
    };

    return rowMaps[activeModule.value] ?? [];
});
const filteredModuleRows = computed(() => {
    const query = moduleSearch.value.trim().toLowerCase();

    if (!query) {
        return moduleRows.value;
    }

    return moduleRows.value.filter((row) => [row.primary, row.secondary, row.value]
        .some((value) => String(value ?? '').toLowerCase().includes(query)));
});

const moduleSummary = computed(() => {
    const summaries = {
        pos: `${pos.activeKitchenTicketCount} kitchen tickets / ${pos.activeDeliveryCount} delivery`,
        products: `${masterData.activeProductCount} produk / ${masterData.categoryCount} kategori`,
        inventory: `Rp ${inventory.totalStockValue.toLocaleString('id-ID')} nilai stok`,
        purchasing: `${purchasing.openOrderCount} PO aktif / Rp ${purchasing.payableRemaining.toLocaleString('id-ID')} payable`,
        accounting: `${accounting.trialBalanceStatus} / ${accounting.providerImportReviewCount} provider review`,
        reports: reports.period,
        customers: `${customers.customerCount} pelanggan / ${customers.loyaltyPointTotal} poin`,
        settings: `${userAccess.userCount} user / ${userAccess.permissionCount} permissions`,
    };

    return summaries[activeModule.value] ?? '';
});
const moduleActions = computed(() => {
    const actions = {
        pos: ['New Sale', 'Hold Cart', 'Open Shift'],
        products: ['New Product', 'Import CSV', 'Price Update'],
        inventory: ['Stock Opname', 'Transfer Stock', 'Production'],
        purchasing: ['New PO', 'Goods Receipt', 'Pay Supplier'],
        accounting: ['New Journal', 'Settlement', 'Import Provider'],
        reports: ['Refresh', 'Export', 'Print'],
        customers: ['New Customer', 'Loyalty', 'Segment'],
        settings: ['Invite User', 'Assign Role', 'Audit'],
    };

    return actions[activeModule.value] ?? [];
});
const selectModule = (moduleId) => {
    activeModule.value = moduleId;
    moduleSearch.value = '';
};

const updateOnlineStatus = () => foundation.setOnlineStatus(navigator.onLine);
const updateOfflineStatus = () => offline.setOnlineStatus(navigator.onLine);
const loadDashboard = async () => {
    dashboardLoading.value = true;
    await foundation.loadSession();

    if (foundation.apiStatus !== 'connected') {
        dashboardLoading.value = false;

        return;
    }

    await Promise.allSettled([
        masterData.loadFromApi(),
        inventory.loadFromApi(),
        pos.loadFromApi(),
        purchasing.loadFromApi(),
        accounting.loadFromApi(),
        reports.loadFromApi(),
        customers.loadFromApi(),
        userAccess.loadFromApi(),
        audit.loadFromApi(),
    ]);
    dashboardLoading.value = false;
};

const submitLogin = async () => {
    try {
        await foundation.login(loginForm.email, loginForm.password);
        loginPanelOpen.value = false;
        await loadDashboard();
    } catch (error) {
        loginPanelOpen.value = true;
    }
};

const logout = async () => {
    await foundation.logout();
};

onMounted(() => {
    window.addEventListener('online', updateOnlineStatus);
    window.addEventListener('offline', updateOnlineStatus);
    window.addEventListener('online', updateOfflineStatus);
    window.addEventListener('offline', updateOfflineStatus);
    offline.loadQueue();
    loadDashboard();
});

onUnmounted(() => {
    window.removeEventListener('online', updateOnlineStatus);
    window.removeEventListener('offline', updateOnlineStatus);
    window.removeEventListener('online', updateOfflineStatus);
    window.removeEventListener('offline', updateOfflineStatus);
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
                    <span
                        class="rounded-md border px-3 py-2 text-sm"
                        :class="foundation.apiStatus === 'connected' ? 'border-emerald-300/40 text-emerald-200' : 'border-sky-300/40 text-sky-200'"
                    >
                        {{ dashboardLoading || foundation.isLoadingSession ? 'Loading API' : foundation.apiMessage }}
                    </span>
                    <button
                        v-if="foundation.apiStatus !== 'connected'"
                        class="rounded-md border border-white/10 px-4 py-2 text-sm font-semibold text-zinc-100 transition hover:border-emerald-300/50 hover:bg-emerald-300/10"
                        @click="loginPanelOpen = !loginPanelOpen"
                    >
                        Login
                    </button>
                    <button
                        v-else
                        class="rounded-md border border-white/10 px-4 py-2 text-sm font-semibold text-zinc-100 transition hover:border-amber-300/50 hover:bg-amber-300/10"
                        @click="logout"
                    >
                        Logout
                    </button>
                    <button class="rounded-md bg-emerald-400 px-4 py-2 text-sm font-semibold text-zinc-950 transition hover:bg-emerald-300">
                        Mulai Transaksi
                    </button>
                </div>
            </header>

            <section
                v-if="loginPanelOpen && foundation.apiStatus !== 'connected'"
                class="border-b border-white/10 py-5"
            >
                <form class="grid gap-3 rounded-md border border-white/10 bg-zinc-900 p-4 sm:grid-cols-[1fr_1fr_auto]" @submit.prevent="submitLogin">
                    <label class="grid gap-1 text-sm">
                        <span class="text-zinc-400">Email</span>
                        <input
                            v-model="loginForm.email"
                            class="rounded-md border border-white/10 bg-zinc-950 px-3 py-2 text-zinc-100 outline-none transition focus:border-emerald-300/60"
                            type="email"
                            autocomplete="username"
                        >
                    </label>
                    <label class="grid gap-1 text-sm">
                        <span class="text-zinc-400">Password</span>
                        <input
                            v-model="loginForm.password"
                            class="rounded-md border border-white/10 bg-zinc-950 px-3 py-2 text-zinc-100 outline-none transition focus:border-emerald-300/60"
                            type="password"
                            autocomplete="current-password"
                        >
                    </label>
                    <div class="flex items-end">
                        <button
                            class="w-full rounded-md bg-emerald-400 px-4 py-2 text-sm font-semibold text-zinc-950 transition hover:bg-emerald-300 disabled:cursor-not-allowed disabled:opacity-60"
                            :disabled="foundation.isLoadingSession"
                            type="submit"
                        >
                            {{ foundation.isLoadingSession ? 'Connecting' : 'Connect' }}
                        </button>
                    </div>
                    <p v-if="foundation.loginError" class="text-sm text-amber-200 sm:col-span-3">
                        {{ foundation.loginError }}
                    </p>
                </form>
            </section>

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

                    <div class="mt-8 grid gap-4 sm:grid-cols-4">
                        <article
                            v-for="stat in quickStats"
                            :key="stat.label"
                            class="rounded-md border border-white/10 bg-zinc-900/80 p-4"
                        >
                            <p class="text-sm text-zinc-400">{{ stat.label }}</p>
                            <p class="mt-3 text-2xl font-semibold">{{ stat.value }}</p>
                        </article>
                    </div>

                    <div class="mt-6 rounded-md border border-white/10 bg-zinc-950/70 p-4">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <p class="text-sm text-zinc-400">Workspace</p>
                                <h3 class="mt-1 text-lg font-semibold text-zinc-100">{{ activeModuleMeta.label }}</h3>
                            </div>
                            <span class="rounded-md border border-white/10 px-3 py-1 text-xs text-zinc-300">
                                {{ moduleSummary }}
                            </span>
                        </div>
                        <div class="mt-4 grid gap-3 lg:grid-cols-[1fr_auto]">
                            <label class="grid gap-1 text-sm">
                                <span class="text-xs uppercase text-zinc-500">Cari data modul</span>
                                <input
                                    v-model="moduleSearch"
                                    class="rounded-md border border-white/10 bg-zinc-950 px-3 py-2 text-zinc-100 outline-none transition focus:border-emerald-300/60"
                                    type="search"
                                    placeholder="Cari nomor, nama, status"
                                >
                            </label>
                            <div class="flex flex-wrap items-end gap-2">
                                <button
                                    v-for="action in moduleActions"
                                    :key="action"
                                    class="rounded-md border border-white/10 bg-white/[0.03] px-3 py-2 text-sm font-medium text-zinc-100 transition hover:border-emerald-300/50 hover:bg-emerald-300/10"
                                    type="button"
                                >
                                    {{ action }}
                                </button>
                            </div>
                        </div>
                        <div class="mt-4 overflow-hidden rounded-md border border-white/10">
                            <div class="grid grid-cols-[1.2fr_1fr_auto] gap-3 border-b border-white/10 bg-white/[0.03] px-3 py-2 text-xs uppercase text-zinc-500">
                                <span>Data</span>
                                <span>Info</span>
                                <span>Status</span>
                            </div>
                            <div
                                v-for="row in filteredModuleRows.slice(0, 8)"
                                :key="`${activeModule}-${row.primary}-${row.secondary}`"
                                class="grid grid-cols-[1.2fr_1fr_auto] gap-3 border-b border-white/5 px-3 py-3 text-sm last:border-b-0"
                            >
                                <span class="font-medium text-zinc-100">{{ row.primary }}</span>
                                <span class="text-zinc-400">{{ row.secondary }}</span>
                                <span class="text-right text-emerald-200">{{ row.value }}</span>
                            </div>
                            <div v-if="filteredModuleRows.length === 0" class="px-3 py-6 text-sm text-zinc-400">
                                Belum ada data.
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 rounded-md border border-white/10 bg-zinc-950/70 p-4">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <h3 class="text-sm font-semibold text-zinc-300">Inventory Ledger</h3>
                            <span class="text-xs text-zinc-500">{{ inventory.warehouse }} / {{ inventory.controlDocumentCount }} kontrol / {{ inventory.productionCount }} produksi</span>
                        </div>
                        <div class="mt-4 grid gap-3 sm:grid-cols-2">
                            <article
                                v-for="stock in inventory.stockBalances"
                                :key="stock.product"
                                class="rounded-md border border-white/10 bg-white/[0.03] p-3"
                            >
                                <p class="text-sm font-medium">{{ stock.product }}</p>
                                <p class="mt-2 text-xl font-semibold">{{ stock.quantity }} {{ stock.unit }}</p>
                                <p class="mt-1 text-xs text-zinc-500">Rp {{ stock.value.toLocaleString('id-ID') }}</p>
                            </article>
                        </div>
                        <div class="mt-4 grid gap-3 sm:grid-cols-2">
                            <div class="rounded-md border border-white/10 bg-white/[0.03] p-3 text-sm">
                                <p class="text-zinc-400">Transfer Terakhir</p>
                                <p class="mt-1 font-semibold">{{ inventory.stockTransfers[0]?.number }}</p>
                            </div>
                            <div class="rounded-md border border-white/10 bg-white/[0.03] p-3 text-sm">
                                <p class="text-zinc-400">Opname Terakhir</p>
                                <p class="mt-1 font-semibold">{{ inventory.stockOpnames[0]?.number }}</p>
                            </div>
                            <div class="rounded-md border border-white/10 bg-white/[0.03] p-3 text-sm">
                                <p class="text-zinc-400">Produksi Terakhir</p>
                                <p class="mt-1 font-semibold">{{ inventory.productionOrders[0]?.number }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 rounded-md border border-white/10 bg-zinc-950/70 p-4">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <h3 class="text-sm font-semibold text-zinc-300">POS Cart</h3>
                            <span class="text-xs text-zinc-500">{{ pos.shift.number }} / {{ pos.activePromotionCount }} promo / {{ pos.activeReservationCount }} reservasi</span>
                        </div>
                        <div class="mt-4 grid gap-3 sm:grid-cols-[1fr_auto]">
                            <div class="space-y-2">
                                <div
                                    v-for="item in pos.cart"
                                    :key="item.name"
                                    class="flex items-center justify-between rounded-md border border-white/10 bg-white/[0.03] px-3 py-2 text-sm"
                                >
                                    <span>{{ item.quantity }}x {{ item.name }}</span>
                                    <span>Rp {{ (item.quantity * item.price).toLocaleString('id-ID') }}</span>
                                </div>
                            </div>
                            <div class="rounded-md border border-emerald-300/30 bg-emerald-300/10 p-3 text-right">
                                <p class="text-xs text-emerald-100">Subtotal</p>
                                <p class="mt-1 text-xl font-semibold text-emerald-100">Rp {{ pos.subtotal.toLocaleString('id-ID') }}</p>
                            </div>
                        </div>
                        <div class="mt-4 grid gap-2 sm:grid-cols-3">
                            <div
                                v-for="table in pos.diningTables"
                                :key="table.code"
                                class="rounded-md border border-white/10 bg-white/[0.03] px-3 py-2 text-sm"
                            >
                                <p class="font-medium">{{ table.code }} / {{ table.capacity }} pax</p>
                                <p class="mt-1 text-xs text-zinc-500">{{ table.status }}</p>
                            </div>
                        </div>
                        <div class="mt-4 rounded-md border border-white/10 bg-white/[0.03] p-3 text-sm">
                            <p class="text-zinc-400">Reservasi Berikutnya</p>
                            <p class="mt-1 font-semibold">
                                {{ pos.tableReservations[0]?.time }} / {{ pos.tableReservations[0]?.guest }} / {{ pos.tableReservations[0]?.table }}
                            </p>
                        </div>
                        <div class="mt-4 rounded-md border border-white/10 bg-white/[0.03] p-3 text-sm">
                            <p class="text-zinc-400">Promo Aktif</p>
                            <p class="mt-1 font-semibold">
                                {{ pos.promotions[0]?.code }} / {{ pos.promotions[0]?.name }}
                            </p>
                        </div>
                        <div class="mt-4 rounded-md border border-white/10 bg-white/[0.03] p-3 text-sm">
                            <p class="text-zinc-400">Kitchen Queue</p>
                            <p class="mt-1 font-semibold">
                                {{ pos.activeKitchenTicketCount }} ticket / {{ pos.kitchenTickets[0]?.station }}
                            </p>
                        </div>
                        <div class="mt-4 rounded-md border border-white/10 bg-white/[0.03] p-3 text-sm">
                            <p class="text-zinc-400">Kitchen Stations</p>
                            <p class="mt-1 font-semibold">
                                {{ pos.activeKitchenStationCount }} station / {{ pos.kitchenStations[0]?.name }}
                            </p>
                        </div>
                        <div class="mt-4 rounded-md border border-white/10 bg-white/[0.03] p-3 text-sm">
                            <p class="text-zinc-400">Delivery Queue</p>
                            <p class="mt-1 font-semibold">
                                {{ pos.activeDeliveryCount }} order / {{ pos.deliveryOrders[0]?.courier }}
                            </p>
                        </div>
                    </div>

                    <div class="mt-6 rounded-md border border-white/10 bg-zinc-950/70 p-4">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <h3 class="text-sm font-semibold text-zinc-300">Post-Sale Controls</h3>
                            <span class="text-xs text-zinc-500">Void / Refund / Cash Movement</span>
                        </div>
                        <div class="mt-4 grid gap-3 sm:grid-cols-3">
                            <div class="rounded-md border border-white/10 bg-white/[0.03] p-3 text-sm">
                                <p class="text-zinc-400">Net Cash Movement</p>
                                <p class="mt-1 font-semibold">Rp {{ pos.cashMovementNet.toLocaleString('id-ID') }}</p>
                            </div>
                            <div class="rounded-md border border-white/10 bg-white/[0.03] p-3 text-sm">
                                <p class="text-zinc-400">Void Hari Ini</p>
                                <p class="mt-1 font-semibold">{{ pos.postSaleControls.voidedToday }}</p>
                            </div>
                            <div class="rounded-md border border-white/10 bg-white/[0.03] p-3 text-sm">
                                <p class="text-zinc-400">Refund Hari Ini</p>
                                <p class="mt-1 font-semibold">{{ pos.postSaleControls.refundedToday }}</p>
                            </div>
                            <div class="rounded-md border border-white/10 bg-white/[0.03] p-3 text-sm">
                                <p class="text-zinc-400">Drawer Audit</p>
                                <p class="mt-1 font-semibold">{{ pos.drawerAudit.status }}</p>
                            </div>
                            <div class="rounded-md border border-white/10 bg-white/[0.03] p-3 text-sm">
                                <p class="text-zinc-400">Drawer Variance</p>
                                <p class="mt-1 font-semibold">Rp {{ pos.drawerAudit.variance.toLocaleString('id-ID') }}</p>
                            </div>
                            <div class="rounded-md border border-white/10 bg-white/[0.03] p-3 text-sm">
                                <p class="text-zinc-400">Denominasi</p>
                                <p class="mt-1 font-semibold">{{ pos.drawerDenominationCount }} lembar/koin</p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 rounded-md border border-white/10 bg-zinc-950/70 p-4">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <h3 class="text-sm font-semibold text-zinc-300">Purchasing</h3>
                            <span class="text-xs text-zinc-500">{{ purchasing.openOrderCount }} active PO / Rp {{ purchasing.payableRemaining.toLocaleString('id-ID') }} sisa utang</span>
                        </div>
                        <div class="mt-4 space-y-2">
                            <div
                                v-for="order in purchasing.purchaseOrders"
                                :key="order.number"
                                class="flex items-center justify-between rounded-md border border-white/10 bg-white/[0.03] px-3 py-2 text-sm"
                            >
                                <span>{{ order.number }} / {{ order.supplier }}</span>
                                <span>Rp {{ order.total.toLocaleString('id-ID') }}</span>
                            </div>
                        </div>
                        <div class="mt-4 grid gap-3 sm:grid-cols-2">
                            <div class="rounded-md border border-white/10 bg-white/[0.03] p-3 text-sm">
                                <p class="text-zinc-400">Supplier Payment</p>
                                <p class="mt-1 font-semibold">Rp {{ purchasing.supplierPaymentTotal.toLocaleString('id-ID') }}</p>
                            </div>
                            <div class="rounded-md border border-white/10 bg-white/[0.03] p-3 text-sm">
                                <p class="text-zinc-400">Purchase Return</p>
                                <p class="mt-1 font-semibold">Rp {{ purchasing.returnTotal.toLocaleString('id-ID') }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 rounded-md border border-white/10 bg-zinc-950/70 p-4">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <h3 class="text-sm font-semibold text-zinc-300">Accounting</h3>
                            <span class="text-xs text-emerald-200">{{ accounting.trialBalanceStatus }} / {{ accounting.statementStatus }}</span>
                        </div>
                        <div class="mt-4 grid gap-2 sm:grid-cols-2">
                            <div
                                v-for="account in accounting.accounts"
                                :key="account.code"
                                class="rounded-md border border-white/10 bg-white/[0.03] px-3 py-2 text-sm"
                            >
                                <p class="font-medium">{{ account.code }} - {{ account.name }}</p>
                                <p class="mt-1 text-xs text-zinc-500">{{ account.type }}</p>
                            </div>
                        </div>
                        <div class="mt-4 grid gap-3 sm:grid-cols-2">
                            <div class="rounded-md border border-white/10 bg-white/[0.03] p-3 text-sm">
                                <p class="text-zinc-400">Neraca</p>
                                <p class="mt-1 font-semibold">Rp {{ accounting.balanceSheet.assets.toLocaleString('id-ID') }}</p>
                            </div>
                            <div class="rounded-md border border-white/10 bg-white/[0.03] p-3 text-sm">
                                <p class="text-zinc-400">Cash Flow</p>
                                <p class="mt-1 font-semibold">Rp {{ accounting.cashFlow.netCashFlow.toLocaleString('id-ID') }}</p>
                            </div>
                            <div class="rounded-md border border-white/10 bg-white/[0.03] p-3 text-sm">
                                <p class="text-zinc-400">Operational Expense</p>
                                <p class="mt-1 font-semibold">Rp {{ accounting.expenseTotal.toLocaleString('id-ID') }}</p>
                            </div>
                            <div class="rounded-md border border-white/10 bg-white/[0.03] p-3 text-sm">
                                <p class="text-zinc-400">Expense Terakhir</p>
                                <p class="mt-1 font-semibold">{{ accounting.operationalExpenses[0]?.number }}</p>
                            </div>
                            <div class="rounded-md border border-white/10 bg-white/[0.03] p-3 text-sm">
                                <p class="text-zinc-400">Settlement</p>
                                <p class="mt-1 font-semibold">{{ accounting.paymentSettlements[0]?.number }}</p>
                            </div>
                            <div class="rounded-md border border-white/10 bg-white/[0.03] p-3 text-sm">
                                <p class="text-zinc-400">Variance</p>
                                <p class="mt-1 font-semibold">Rp {{ accounting.settlementVarianceTotal.toLocaleString('id-ID') }}</p>
                            </div>
                            <div class="rounded-md border border-white/10 bg-white/[0.03] p-3 text-sm">
                                <p class="text-zinc-400">Provider Import</p>
                                <p class="mt-1 font-semibold">{{ accounting.providerImports[0]?.number }}</p>
                            </div>
                            <div class="rounded-md border border-white/10 bg-white/[0.03] p-3 text-sm">
                                <p class="text-zinc-400">Provider Review</p>
                                <p class="mt-1 font-semibold">{{ accounting.providerImportReviewCount }} unmatched</p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 rounded-md border border-white/10 bg-zinc-950/70 p-4">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <h3 class="text-sm font-semibold text-zinc-300">Reports</h3>
                            <span class="text-xs text-zinc-500">{{ reports.period }}</span>
                        </div>
                        <div class="mt-4 grid gap-3 sm:grid-cols-3">
                            <div
                                v-for="card in reports.reportCards"
                                :key="card.label"
                                class="rounded-md border border-white/10 bg-white/[0.03] p-3 text-sm"
                            >
                                <p class="text-zinc-400">{{ card.label }}</p>
                                <p class="mt-1 font-semibold">Rp {{ card.value.toLocaleString('id-ID') }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 rounded-md border border-white/10 bg-zinc-950/70 p-4">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <h3 class="text-sm font-semibold text-zinc-300">Customer CRM</h3>
                            <span class="text-xs text-zinc-500">{{ customers.memberCount }} member / {{ customers.loyaltyPointTotal }} poin</span>
                        </div>
                        <div class="mt-4 grid gap-3 sm:grid-cols-2">
                            <article
                                v-for="customer in customers.customers"
                                :key="customer.phone"
                                class="rounded-md border border-white/10 bg-white/[0.03] p-3 text-sm"
                            >
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="font-medium">{{ customer.name }}</p>
                                        <p class="mt-1 text-xs text-zinc-500">{{ customer.phone }}</p>
                                    </div>
                                    <span class="text-xs text-emerald-200">{{ customer.loyaltyPoints }} pts</span>
                                </div>
                                <p class="mt-3 text-xs text-zinc-400">
                                    {{ customer.transactionCount }} transaksi / Rp {{ customer.lifetimeSpend.toLocaleString('id-ID') }}
                                </p>
                            </article>
                        </div>
                        <div class="mt-4 rounded-md border border-white/10 bg-white/[0.03] p-3 text-sm">
                            <p class="text-zinc-400">Loyalty Terakhir</p>
                            <p class="mt-1 font-semibold">
                                {{ customers.loyaltyTransactions[0]?.type }} / {{ customers.loyaltyTransactions[0]?.points }} poin
                            </p>
                        </div>
                    </div>

                    <div class="mt-6 rounded-md border border-white/10 bg-zinc-950/70 p-4">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <h3 class="text-sm font-semibold text-zinc-300">User Access</h3>
                            <span class="text-xs text-zinc-500">{{ userAccess.permissionCount }} permissions</span>
                        </div>
                        <div class="mt-4 grid gap-3 sm:grid-cols-3">
                            <div class="rounded-md border border-white/10 bg-white/[0.03] p-3 text-sm">
                                <p class="text-zinc-400">Users</p>
                                <p class="mt-1 font-semibold">{{ userAccess.userCount }}</p>
                            </div>
                            <div class="rounded-md border border-white/10 bg-white/[0.03] p-3 text-sm">
                                <p class="text-zinc-400">Roles</p>
                                <p class="mt-1 font-semibold">{{ userAccess.roleCount }}</p>
                            </div>
                            <div class="rounded-md border border-white/10 bg-white/[0.03] p-3 text-sm">
                                <p class="text-zinc-400">Owner</p>
                                <p class="mt-1 font-semibold">{{ userAccess.users[0]?.name }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 rounded-md border border-white/10 bg-zinc-950/70 p-4">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <h3 class="text-sm font-semibold text-zinc-300">Audit Review</h3>
                            <span class="text-xs text-zinc-500">{{ audit.securityEventCount }} security events</span>
                        </div>
                        <div class="mt-4 grid gap-3 sm:grid-cols-3">
                            <div class="rounded-md border border-white/10 bg-white/[0.03] p-3 text-sm">
                                <p class="text-zinc-400">Events</p>
                                <p class="mt-1 font-semibold">{{ audit.totalEvents }}</p>
                            </div>
                            <div class="rounded-md border border-white/10 bg-white/[0.03] p-3 text-sm">
                                <p class="text-zinc-400">Users</p>
                                <p class="mt-1 font-semibold">{{ audit.uniqueUsers }}</p>
                            </div>
                            <div class="rounded-md border border-white/10 bg-white/[0.03] p-3 text-sm">
                                <p class="text-zinc-400">Top Action</p>
                                <p class="mt-1 font-semibold">{{ audit.topActionLabel }}</p>
                            </div>
                        </div>
                    </div>
                </section>

                <aside class="rounded-lg border border-white/10 bg-zinc-900 p-5">
                    <div class="flex items-start justify-between gap-4">
                        <h2 class="text-lg font-semibold">Modul POS</h2>
                        <span class="rounded-md bg-zinc-800 px-2 py-1 text-xs text-zinc-300">
                            {{ offline.queuedCount }} unsynced
                        </span>
                    </div>
                    <div class="mt-5 grid grid-cols-2 gap-3">
                        <button
                            v-for="module in modules"
                            :key="module.id"
                            class="rounded-md border px-3 py-4 text-left text-sm font-medium transition hover:border-emerald-300/50 hover:bg-emerald-300/10"
                            :class="activeModule === module.id ? 'border-emerald-300/50 bg-emerald-300/10 text-emerald-100' : 'border-white/10 bg-white/[0.03] text-zinc-100'"
                            @click="selectModule(module.id)"
                        >
                            {{ module.label }}
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

                    <div class="mt-6 border-t border-white/10 pt-5">
                        <h3 class="text-sm font-semibold text-zinc-300">Offline Sync</h3>
                        <div class="mt-3 grid gap-3 text-sm">
                            <div class="rounded-md border border-white/10 bg-white/[0.03] p-3">
                                <p class="text-zinc-400">Status</p>
                                <p class="mt-1 font-medium" :class="offline.isOnline ? 'text-emerald-200' : 'text-amber-200'">
                                    {{ offline.isOnline ? 'Online' : 'Offline' }}
                                </p>
                            </div>
                            <div class="rounded-md border border-white/10 bg-white/[0.03] p-3">
                                <p class="text-zinc-400">Queue / Conflict</p>
                                <p class="mt-1 font-medium">{{ offline.queuedCount }} / {{ offline.conflictCount }}</p>
                            </div>
                        </div>
                    </div>
                </aside>
            </div>
        </section>
    </main>
</template>
