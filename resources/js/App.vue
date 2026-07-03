<script setup>
import { onMounted, onUnmounted } from 'vue';
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

const quickStats = [
    { label: 'Penjualan Hari Ini', value: 'Rp 0', tone: 'emerald' },
    { label: 'Transaksi', value: '0', tone: 'sky' },
    { label: 'Produk Aktif', value: masterData.activeProductCount, tone: 'amber' },
    { label: 'Nilai Stok', value: `Rp ${inventory.totalStockValue.toLocaleString('id-ID')}`, tone: 'emerald' },
    { label: 'PO Aktif', value: purchasing.openOrderCount, tone: 'sky' },
    { label: 'Akun COA', value: accounting.accountCount, tone: 'amber' },
    { label: 'Laporan', value: reports.period, tone: 'emerald' },
    { label: 'Pelanggan', value: customers.customerCount, tone: 'sky' },
    { label: 'User', value: userAccess.userCount, tone: 'amber' },
    { label: 'Audit', value: audit.totalEvents, tone: 'emerald' },
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
const updateOfflineStatus = () => offline.setOnlineStatus(navigator.onLine);

onMounted(() => {
    window.addEventListener('online', updateOnlineStatus);
    window.addEventListener('offline', updateOnlineStatus);
    window.addEventListener('online', updateOfflineStatus);
    window.addEventListener('offline', updateOfflineStatus);
    offline.loadQueue();
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
