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
import { apiGet, apiPatch, apiPost } from './services/api';

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
const activeAction = ref(null);
const actionDraft = reactive({});
const actionFeedback = ref('');

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
        pos: ['New Sale', 'Void Sale', 'Refund Sale', 'View Receipt', 'Hold Cart', 'Open Shift', 'Cash Movement', 'Close Shift', 'New Promo', 'New Table', 'Table Status', 'Reserve Table', 'Seat Reservation', 'Cancel Reservation', 'Kitchen Station', 'Kitchen Status', 'Kitchen Item Status', 'Delivery Status'],
        products: ['New Product', 'Import CSV', 'Price Update'],
        inventory: ['Stock Opname', 'Transfer Stock', 'Production'],
        purchasing: ['New PO', 'Goods Receipt', 'Return Supplier', 'Pay Supplier'],
        accounting: ['New Journal', 'Settlement', 'Import Provider'],
        reports: ['Refresh', 'Export', 'Print'],
        customers: ['New Customer', 'Loyalty', 'Segment'],
        settings: ['Invite User', 'Assign Role', 'Audit'],
    };

    return actions[activeModule.value] ?? [];
});
const actionFields = computed(() => {
    const firstStock = inventory.stockBalances[0] ?? {};
    const firstRecipe = inventory.recipes[0] ?? {};
    const transferTarget = inventory.warehouses.find((warehouse) => warehouse.id !== inventory.warehouseId);
    const firstSupplier = masterData.suppliers[0] ?? {};
    const firstProduct = masterData.products[0] ?? {};
    const firstReceipt = purchasing.goodsReceipts[0] ?? {};
    const firstReceiptItem = firstReceipt.firstItem ?? {};
    const firstPayable = purchasing.payables.find((payable) => payable.status !== 'closed') ?? purchasing.payables[0] ?? {};
    const cashAccount = accounting.accounts.find((account) => account.code === '1100') ?? accounting.accounts[0] ?? {};
    const balancingAccount = accounting.accounts.find((account) => account.code === '3100') ?? accounting.accounts[1] ?? {};
    const providerSettlement = accounting.paymentSettlements.find((settlement) => ['card', 'transfer', 'qris'].includes(settlement.method)) ?? accounting.paymentSettlements[0] ?? {};
    const firstCustomer = customers.customers[0] ?? {};
    const firstUser = userAccess.users[0] ?? {};
    const firstRole = userAccess.roles.find((role) => role.name === 'Cashier') ?? userAccess.roles[0] ?? {};
    const firstBranch = userAccess.branches[0] ?? {};
    const saleProduct = pos.products[0] ?? masterData.products[0] ?? {};
    const saleWarehouse = pos.warehouses[0] ?? inventory.warehouses[0] ?? {};
    const firstCompletedSale = pos.sales.find((sale) => sale.status === 'completed') ?? pos.sales[0] ?? {};
    const firstTable = pos.diningTables.find((table) => table.status === 'available') ?? pos.diningTables[0] ?? {};
    const firstReservation = pos.tableReservations.find((reservation) => reservation.status === 'booked') ?? pos.tableReservations[0] ?? {};
    const firstKitchenTicket = pos.kitchenTickets.find((ticket) => ticket.status !== 'served') ?? pos.kitchenTickets[0] ?? {};
    const firstKitchenItem = pos.kitchenTicketItems.find((item) => !['served', 'cancelled'].includes(item.status)) ?? pos.kitchenTicketItems[0] ?? {};
    const firstDelivery = pos.deliveryOrders.find((order) => order.status !== 'delivered') ?? pos.deliveryOrders[0] ?? {};
    const fields = {
        'New Sale': [
            { key: 'sale_number', label: 'Sale Number', type: 'text', placeholder: 'SALE-001' },
            { key: 'cashier_shift_id', label: 'Shift ID', type: 'number', placeholder: String(pos.shift.id ?? '') },
            { key: 'warehouse_id', label: 'Warehouse ID', type: 'number', placeholder: String(saleWarehouse.id ?? '') },
            { key: 'product_id', label: 'Product ID', type: 'number', placeholder: String(saleProduct.id ?? '') },
            { key: 'quantity', label: 'Quantity', type: 'number', placeholder: '1' },
            { key: 'unit_price', label: 'Unit Price', type: 'number', placeholder: String(saleProduct.price ?? 0) },
            { key: 'payment_method', label: 'Payment Method', type: 'text', placeholder: 'cash' },
            { key: 'payment_amount', label: 'Payment Amount', type: 'number', placeholder: String(Math.ceil((saleProduct.price ?? 50000) * 1.2)) },
            { key: 'customer_id', label: 'Customer ID', type: 'number', placeholder: String(firstCustomer.id ?? '') },
            { key: 'note', label: 'Catatan', type: 'text', placeholder: 'Catatan transaksi' },
        ],
        'Hold Cart': [
            { key: 'hold_number', label: 'Hold Number', type: 'text', placeholder: 'HOLD-001' },
            { key: 'reason', label: 'Reason', type: 'text', placeholder: 'Customer kembali nanti' },
        ],
        'Void Sale': [
            { key: 'sale_id', label: 'Sale ID', type: 'number', placeholder: String(firstCompletedSale.id ?? '') },
            { key: 'reason', label: 'Reason', type: 'text', placeholder: 'Transaksi batal' },
        ],
        'Refund Sale': [
            { key: 'sale_id', label: 'Sale ID', type: 'number', placeholder: String(firstCompletedSale.id ?? '') },
            { key: 'reason', label: 'Reason', type: 'text', placeholder: 'Refund pelanggan' },
        ],
        'View Receipt': [
            { key: 'sale_id', label: 'Sale ID', type: 'number', placeholder: String(firstCompletedSale.id ?? '') },
        ],
        'Open Shift': [
            { key: 'shift_number', label: 'Shift Number', type: 'text', placeholder: 'SHIFT-001' },
            { key: 'opening_cash', label: 'Opening Cash', type: 'number', placeholder: '250000' },
        ],
        'Cash Movement': [
            { key: 'cashier_shift_id', label: 'Shift ID', type: 'number', placeholder: String(pos.shift.id ?? '') },
            { key: 'type', label: 'Type', type: 'text', placeholder: 'cash_in' },
            { key: 'amount', label: 'Amount', type: 'number', placeholder: '50000' },
            { key: 'reason', label: 'Reason', type: 'text', placeholder: 'Tambahan kas kecil' },
        ],
        'Close Shift': [
            { key: 'cashier_shift_id', label: 'Shift ID', type: 'number', placeholder: String(pos.shift.id ?? '') },
            { key: 'actual_cash', label: 'Actual Cash', type: 'number', placeholder: String(pos.shift.expectedCash ?? pos.shift.openingCash ?? 0) },
            { key: 'notes', label: 'Notes', type: 'text', placeholder: 'Tutup shift' },
        ],
        'New Promo': [
            { key: 'code', label: 'Code', type: 'text', placeholder: 'KAWI10' },
            { key: 'name', label: 'Name', type: 'text', placeholder: 'KAWI 10 Percent' },
            { key: 'type', label: 'Type', type: 'text', placeholder: 'percent' },
            { key: 'value', label: 'Value', type: 'number', placeholder: '10' },
            { key: 'maximum_discount', label: 'Maximum Discount', type: 'number', placeholder: '5000' },
        ],
        'New Table': [
            { key: 'code', label: 'Code', type: 'text', placeholder: 'T-03' },
            { key: 'name', label: 'Name', type: 'text', placeholder: 'Table 03' },
            { key: 'capacity', label: 'Capacity', type: 'number', placeholder: '4' },
            { key: 'section', label: 'Section', type: 'text', placeholder: 'Main' },
        ],
        'Table Status': [
            { key: 'table_id', label: 'Table ID', type: 'number', placeholder: String(firstTable.id ?? '') },
            { key: 'status', label: 'Status', type: 'text', placeholder: 'available' },
        ],
        'Reserve Table': [
            { key: 'table_id', label: 'Table ID', type: 'number', placeholder: String(firstTable.id ?? '') },
            { key: 'reservation_number', label: 'Reservation Number', type: 'text', placeholder: 'RSV-001' },
            { key: 'guest_name', label: 'Guest Name', type: 'text', placeholder: customers.customers[0]?.name ?? 'Guest KAWI' },
            { key: 'guest_phone', label: 'Guest Phone', type: 'text', placeholder: customers.customers[0]?.phone ?? '0812...' },
            { key: 'party_size', label: 'Party Size', type: 'number', placeholder: String(firstTable.capacity ?? 2) },
            { key: 'reserved_at', label: 'Reserved At', type: 'datetime-local', placeholder: reservationDateTime() },
        ],
        'Seat Reservation': [
            { key: 'reservation_id', label: 'Reservation ID', type: 'number', placeholder: String(firstReservation.id ?? '') },
        ],
        'Cancel Reservation': [
            { key: 'reservation_id', label: 'Reservation ID', type: 'number', placeholder: String(firstReservation.id ?? '') },
        ],
        'Kitchen Station': [
            { key: 'code', label: 'Code', type: 'text', placeholder: 'HOT' },
            { key: 'name', label: 'Name', type: 'text', placeholder: 'Hot Kitchen' },
            { key: 'sort_order', label: 'Sort Order', type: 'number', placeholder: '10' },
        ],
        'Kitchen Status': [
            { key: 'ticket_id', label: 'Ticket ID', type: 'number', placeholder: String(firstKitchenTicket.id ?? '') },
            { key: 'status', label: 'Status', type: 'text', placeholder: 'preparing' },
        ],
        'Kitchen Item Status': [
            { key: 'item_id', label: 'Item ID', type: 'number', placeholder: String(firstKitchenItem.id ?? '') },
            { key: 'status', label: 'Status', type: 'text', placeholder: 'preparing' },
        ],
        'Delivery Status': [
            { key: 'delivery_id', label: 'Delivery ID', type: 'number', placeholder: String(firstDelivery.id ?? '') },
            { key: 'status', label: 'Status', type: 'text', placeholder: 'assigned' },
            { key: 'courier_name', label: 'Courier Name', type: 'text', placeholder: firstDelivery.courier ?? 'Andi Courier' },
            { key: 'courier_phone', label: 'Courier Phone', type: 'text', placeholder: '081299900001' },
        ],
        'New Product': [
            { key: 'name', label: 'Product Name', type: 'text', placeholder: 'KAWI Menu Baru' },
            { key: 'type', label: 'Type', type: 'text', placeholder: 'food' },
            { key: 'price', label: 'Base Price', type: 'number', placeholder: '35000' },
        ],
        'Import CSV': [
            { key: 'source', label: 'Source', type: 'text', placeholder: 'products.csv' },
            { key: 'notes', label: 'Notes', type: 'text', placeholder: 'Import batch' },
        ],
        'Price Update': [
            { key: 'sku', label: 'SKU', type: 'text', placeholder: 'KAWI-RICE-001' },
            { key: 'price', label: 'New Price', type: 'number', placeholder: '38000' },
        ],
        'Stock Opname': [
            { key: 'opname_number', label: 'Opname Number', type: 'text', placeholder: 'OPN-001' },
            { key: 'warehouse_id', label: 'Warehouse ID', type: 'number', placeholder: String(inventory.warehouseId ?? '') },
            { key: 'product_id', label: 'Product ID', type: 'number', placeholder: String(firstStock.productId ?? '') },
            { key: 'counted_quantity', label: 'Counted Quantity', type: 'number', placeholder: String(firstStock.quantity ?? 0) },
        ],
        'Transfer Stock': [
            { key: 'transfer_number', label: 'Transfer Number', type: 'text', placeholder: 'TRF-001' },
            { key: 'from_warehouse_id', label: 'From Warehouse ID', type: 'number', placeholder: String(inventory.warehouseId ?? '') },
            { key: 'to_warehouse_id', label: 'To Warehouse ID', type: 'number', placeholder: String(transferTarget?.id ?? '') },
            { key: 'product_id', label: 'Product ID', type: 'number', placeholder: String(firstStock.productId ?? '') },
            { key: 'quantity', label: 'Quantity', type: 'number', placeholder: '1' },
        ],
        Production: [
            { key: 'production_number', label: 'Production Number', type: 'text', placeholder: 'PROD-001' },
            { key: 'warehouse_id', label: 'Warehouse ID', type: 'number', placeholder: String(inventory.warehouseId ?? '') },
            { key: 'recipe_id', label: 'Recipe ID', type: 'number', placeholder: String(firstRecipe.id ?? '') },
            { key: 'planned_quantity', label: 'Planned Quantity', type: 'number', placeholder: '10' },
            { key: 'actual_quantity', label: 'Actual Quantity', type: 'number', placeholder: '10' },
        ],
        'New PO': [
            { key: 'po_number', label: 'PO Number', type: 'text', placeholder: 'PO-001' },
            { key: 'supplier_id', label: 'Supplier ID', type: 'number', placeholder: String(firstSupplier.id ?? '') },
            { key: 'warehouse_id', label: 'Warehouse ID', type: 'number', placeholder: String(inventory.warehouseId ?? '') },
            { key: 'product_id', label: 'Product ID', type: 'number', placeholder: String(firstProduct.id ?? '') },
            { key: 'quantity_ordered', label: 'Quantity Ordered', type: 'number', placeholder: '5' },
            { key: 'unit_cost', label: 'Unit Cost', type: 'number', placeholder: String(firstProduct.cost ?? 0) },
        ],
        'Goods Receipt': [
            { key: 'receipt_number', label: 'Receipt Number', type: 'text', placeholder: 'GR-001' },
            { key: 'supplier_id', label: 'Supplier ID', type: 'number', placeholder: String(firstSupplier.id ?? '') },
            { key: 'warehouse_id', label: 'Warehouse ID', type: 'number', placeholder: String(inventory.warehouseId ?? '') },
            { key: 'product_id', label: 'Product ID', type: 'number', placeholder: String(firstProduct.id ?? '') },
            { key: 'quantity_received', label: 'Quantity Received', type: 'number', placeholder: '5' },
            { key: 'unit_cost', label: 'Unit Cost', type: 'number', placeholder: String(firstProduct.cost ?? 0) },
        ],
        'Return Supplier': [
            { key: 'return_number', label: 'Return Number', type: 'text', placeholder: 'PR-001' },
            { key: 'supplier_id', label: 'Supplier ID', type: 'number', placeholder: String(firstReceipt.supplierId ?? firstSupplier.id ?? '') },
            { key: 'goods_receipt_id', label: 'Goods Receipt ID', type: 'number', placeholder: String(firstReceipt.id ?? '') },
            { key: 'goods_receipt_item_id', label: 'Receipt Item ID', type: 'number', placeholder: String(firstReceiptItem.id ?? '') },
            { key: 'product_id', label: 'Product ID', type: 'number', placeholder: String(firstReceiptItem.productId ?? firstProduct.id ?? '') },
            { key: 'quantity_returned', label: 'Quantity Returned', type: 'number', placeholder: '1' },
            { key: 'unit_cost', label: 'Unit Cost', type: 'number', placeholder: String(firstReceiptItem.unitCost ?? firstProduct.cost ?? 0) },
            { key: 'reason', label: 'Reason', type: 'text', placeholder: 'Barang rusak' },
        ],
        'Pay Supplier': [
            { key: 'payable_id', label: 'Payable ID', type: 'number', placeholder: String(firstPayable.id ?? '') },
            { key: 'payment_number', label: 'Payment Number', type: 'text', placeholder: 'PAY-001' },
            { key: 'amount', label: 'Amount', type: 'number', placeholder: String(Math.max((firstPayable.amount ?? 0) - (firstPayable.paidAmount ?? 0), 0) || 50000) },
            { key: 'payment_method', label: 'Payment Method', type: 'text', placeholder: 'cash' },
        ],
        'New Journal': [
            { key: 'journal_number', label: 'Journal Number', type: 'text', placeholder: 'JE-001' },
            { key: 'description', label: 'Description', type: 'text', placeholder: 'Manual journal' },
            { key: 'debit_account_id', label: 'Debit Account ID', type: 'number', placeholder: String(cashAccount.id ?? '') },
            { key: 'credit_account_id', label: 'Credit Account ID', type: 'number', placeholder: String(balancingAccount.id ?? '') },
            { key: 'amount', label: 'Amount', type: 'number', placeholder: '100000' },
        ],
        Settlement: [
            { key: 'settlement_number', label: 'Settlement Number', type: 'text', placeholder: 'SETTLE-001' },
            { key: 'method', label: 'Method', type: 'text', placeholder: 'qris' },
            { key: 'date_from', label: 'Date From', type: 'date', placeholder: todayDate() },
            { key: 'date_to', label: 'Date To', type: 'date', placeholder: todayDate() },
            { key: 'reported_amount', label: 'Reported Amount', type: 'number', placeholder: '38850' },
        ],
        'Import Provider': [
            { key: 'payment_settlement_id', label: 'Settlement ID', type: 'number', placeholder: String(providerSettlement.id ?? '') },
            { key: 'import_number', label: 'Import Number', type: 'text', placeholder: 'IMP-001' },
            { key: 'provider', label: 'Provider', type: 'text', placeholder: 'QRIS Acquirer' },
            { key: 'method', label: 'Method', type: 'text', placeholder: providerSettlement.method || 'qris' },
            { key: 'reference', label: 'Reference', type: 'text', placeholder: 'QRIS-REF-001' },
            { key: 'amount', label: 'Amount', type: 'number', placeholder: String(providerSettlement.reported || 0) },
            { key: 'fee_amount', label: 'Fee Amount', type: 'number', placeholder: '0' },
        ],
        Refresh: [
            { key: 'period', label: 'Period', type: 'text', placeholder: reports.period },
        ],
        Export: [
            { key: 'format', label: 'Format', type: 'text', placeholder: 'xlsx' },
        ],
        Print: [
            { key: 'template', label: 'Template', type: 'text', placeholder: 'Summary' },
        ],
        'New Customer': [
            { key: 'name', label: 'Name', type: 'text', placeholder: 'Nama pelanggan' },
            { key: 'phone', label: 'Phone', type: 'text', placeholder: '0812...' },
        ],
        Loyalty: [
            { key: 'customer_id', label: 'Customer ID', type: 'number', placeholder: String(firstCustomer.id ?? '') },
            { key: 'type', label: 'Type', type: 'text', placeholder: 'manual_bonus' },
            { key: 'points_delta', label: 'Points Delta', type: 'number', placeholder: '10' },
            { key: 'notes', label: 'Notes', type: 'text', placeholder: 'Manual loyalty adjustment' },
        ],
        Segment: [
            { key: 'name', label: 'Segment Name', type: 'text', placeholder: 'VIP' },
        ],
        'Invite User': [
            { key: 'name', label: 'Name', type: 'text', placeholder: 'Kasir Baru' },
            { key: 'email', label: 'Email', type: 'email', placeholder: 'user@kawi.test' },
            { key: 'password', label: 'Password', type: 'password', placeholder: 'password123' },
            { key: 'role_id', label: 'Role ID', type: 'number', placeholder: String(firstRole.id ?? '') },
            { key: 'branch_id', label: 'Branch ID', type: 'number', placeholder: String(firstBranch.id ?? '') },
        ],
        'Assign Role': [
            { key: 'user_id', label: 'User ID', type: 'number', placeholder: String(firstUser.id ?? '') },
            { key: 'role_id', label: 'Role ID', type: 'number', placeholder: String(firstRole.id ?? '') },
            { key: 'branch_id', label: 'Branch ID', type: 'number', placeholder: String(firstBranch.id ?? '') },
        ],
        Audit: [
            { key: 'action', label: 'Action Filter', type: 'text', placeholder: 'sale.completed' },
        ],
    };

    return fields[activeAction.value] ?? [];
});
const selectModule = (moduleId) => {
    activeModule.value = moduleId;
    moduleSearch.value = '';
    activeAction.value = null;
    actionFeedback.value = '';
};
const openAction = (action) => {
    activeAction.value = action;
    actionFeedback.value = '';
    Object.keys(actionDraft).forEach((key) => delete actionDraft[key]);

    actionFields.value.forEach((field) => {
        actionDraft[field.key] = '';
    });
};
const closeAction = () => {
    activeAction.value = null;
    actionFeedback.value = '';
};
const draftNumber = (key, fallback = 0) => Number(actionDraft[key] || fallback || 0);
const todayDate = () => new Date().toISOString().slice(0, 10);
const firstStockBalance = () => inventory.stockBalances[0] ?? {};
const firstRecipe = () => inventory.recipes[0] ?? {};
const firstSupplier = () => masterData.suppliers[0] ?? {};
const firstProduct = () => masterData.products[0] ?? {};
const firstGoodsReceipt = () => purchasing.goodsReceipts[0] ?? {};
const firstGoodsReceiptItem = () => firstGoodsReceipt().firstItem ?? {};
const firstOpenPayable = () => purchasing.payables.find((payable) => payable.status !== 'closed') ?? purchasing.payables[0] ?? {};
const accountByCode = (code, fallbackIndex = 0) => accounting.accounts.find((account) => account.code === code) ?? accounting.accounts[fallbackIndex] ?? {};
const firstProviderSettlement = () => accounting.paymentSettlements.find((settlement) => ['card', 'transfer', 'qris'].includes(settlement.method)) ?? accounting.paymentSettlements[0] ?? {};
const firstCustomer = () => customers.customers[0] ?? {};
const firstUser = () => userAccess.users[0] ?? {};
const firstRole = () => userAccess.roles.find((role) => role.name === 'Cashier') ?? userAccess.roles[0] ?? {};
const firstBranch = () => userAccess.branches[0] ?? {};
const firstSaleProduct = () => pos.products[0] ?? masterData.products[0] ?? {};
const firstSaleWarehouse = () => pos.warehouses[0] ?? inventory.warehouses[0] ?? {};
const firstCompletedSale = () => pos.sales.find((sale) => sale.status === 'completed') ?? pos.sales[0] ?? {};
const firstAvailableTable = () => pos.diningTables.find((table) => table.status === 'available') ?? pos.diningTables[0] ?? {};
const firstBookedReservation = () => pos.tableReservations.find((reservation) => reservation.status === 'booked') ?? pos.tableReservations[0] ?? {};
const firstActiveKitchenTicket = () => pos.kitchenTickets.find((ticket) => ticket.status !== 'served') ?? pos.kitchenTickets[0] ?? {};
const firstActiveKitchenItem = () => pos.kitchenTicketItems.find((item) => !['served', 'cancelled'].includes(item.status)) ?? pos.kitchenTicketItems[0] ?? {};
const firstActiveDelivery = () => pos.deliveryOrders.find((order) => order.status !== 'delivered') ?? pos.deliveryOrders[0] ?? {};
const saleNumber = () => actionDraft.sale_number || `SALE-${Date.now()}`;
const reservationDateTime = () => {
    const date = new Date(Date.now() + 60 * 60 * 1000);

    return date.toISOString().slice(0, 16);
};
const actionPayload = () => {
    const payloads = {
        'New Sale': () => {
            const product = firstSaleProduct();
            const generatedSaleNumber = saleNumber();

            return {
                cashier_shift_id: draftNumber('cashier_shift_id', pos.shift.id),
                warehouse_id: draftNumber('warehouse_id', firstSaleWarehouse().id),
                customer_id: draftNumber('customer_id') || undefined,
                sale_number: generatedSaleNumber,
                idempotency_key: `frontend-${generatedSaleNumber}`,
                type: 'takeaway',
                notes: actionDraft.note,
                items: [
                    {
                        product_id: draftNumber('product_id', product.id),
                        quantity: draftNumber('quantity', 1),
                        unit_price: draftNumber('unit_price', product.price),
                    },
                ],
                payments: [
                    {
                        method: actionDraft.payment_method || 'cash',
                        amount: draftNumber('payment_amount', Math.ceil((product.price ?? 0) * draftNumber('quantity', 1) * 1.2)),
                    },
                ],
            };
        },
        'New Customer': () => ({
            name: actionDraft.name,
            phone: actionDraft.phone,
            is_active: true,
        }),
        'New Product': () => ({
            name: actionDraft.name,
            type: actionDraft.type || 'food',
            base_price: Number(actionDraft.price || 0),
            cost_price: 0,
            track_stock: false,
            is_active: true,
        }),
        'Open Shift': () => ({
            shift_number: actionDraft.shift_number,
            opening_cash: Number(actionDraft.opening_cash || 0),
        }),
        'Cash Movement': () => ({
            type: actionDraft.type || 'cash_in',
            amount: draftNumber('amount', 50000),
            reason: actionDraft.reason,
        }),
        'Close Shift': () => ({
            actual_cash: draftNumber('actual_cash', pos.shift.expectedCash ?? pos.shift.openingCash),
            notes: actionDraft.notes,
        }),
        'Void Sale': () => ({
            reason: actionDraft.reason || 'Transaksi batal',
        }),
        'Refund Sale': () => ({
            reason: actionDraft.reason || 'Refund pelanggan',
        }),
        'New Promo': () => ({
            code: actionDraft.code,
            name: actionDraft.name,
            type: actionDraft.type || 'percent',
            value: draftNumber('value', 10),
            maximum_discount: actionDraft.maximum_discount ? draftNumber('maximum_discount') : undefined,
            is_active: true,
        }),
        'New Table': () => ({
            code: actionDraft.code,
            name: actionDraft.name,
            capacity: draftNumber('capacity', 4),
            section: actionDraft.section || 'Main',
            status: 'available',
        }),
        'Table Status': () => ({
            status: actionDraft.status || 'available',
        }),
        'Reserve Table': () => ({
            reservation_number: actionDraft.reservation_number,
            customer_id: draftNumber('customer_id') || undefined,
            guest_name: actionDraft.guest_name,
            guest_phone: actionDraft.guest_phone,
            party_size: draftNumber('party_size', firstAvailableTable().capacity ?? 2),
            reserved_at: actionDraft.reserved_at || reservationDateTime(),
        }),
        'Seat Reservation': () => ({}),
        'Cancel Reservation': () => ({}),
        'Kitchen Station': () => ({
            code: actionDraft.code,
            name: actionDraft.name,
            sort_order: draftNumber('sort_order', 10),
            is_active: true,
        }),
        'Kitchen Status': () => ({
            status: actionDraft.status || 'preparing',
        }),
        'Kitchen Item Status': () => ({
            status: actionDraft.status || 'preparing',
        }),
        'Delivery Status': () => ({
            status: actionDraft.status || 'assigned',
            courier_name: actionDraft.courier_name || firstActiveDelivery().courier,
            courier_phone: actionDraft.courier_phone,
        }),
        'Hold Cart': () => ({
            hold_number: actionDraft.hold_number,
            payload: {
                note: actionDraft.reason,
                items: pos.cart.map((item) => ({
                    name: item.name,
                    quantity: Number(item.quantity || 0),
                    price: Number(item.price || 0),
                })),
            },
        }),
        'Stock Opname': () => {
            const stock = firstStockBalance();

            return {
                warehouse_id: draftNumber('warehouse_id', stock.warehouseId ?? inventory.warehouseId),
                opname_number: actionDraft.opname_number,
                items: [
                    {
                        product_id: draftNumber('product_id', stock.productId),
                        counted_quantity: draftNumber('counted_quantity', stock.quantity),
                    },
                ],
            };
        },
        'Transfer Stock': () => {
            const stock = firstStockBalance();

            return {
                from_warehouse_id: draftNumber('from_warehouse_id', stock.warehouseId ?? inventory.warehouseId),
                to_warehouse_id: draftNumber('to_warehouse_id'),
                transfer_number: actionDraft.transfer_number,
                items: [
                    {
                        product_id: draftNumber('product_id', stock.productId),
                        quantity: draftNumber('quantity', 1),
                    },
                ],
            };
        },
        Production: () => ({
            warehouse_id: draftNumber('warehouse_id', inventory.warehouseId),
            recipe_id: draftNumber('recipe_id', firstRecipe().id),
            production_number: actionDraft.production_number,
            planned_quantity: draftNumber('planned_quantity', 1),
            actual_quantity: actionDraft.actual_quantity ? draftNumber('actual_quantity') : undefined,
        }),
        'New PO': () => ({
            supplier_id: draftNumber('supplier_id', firstSupplier().id),
            warehouse_id: draftNumber('warehouse_id', inventory.warehouseId),
            po_number: actionDraft.po_number,
            items: [
                {
                    product_id: draftNumber('product_id', firstProduct().id),
                    quantity_ordered: draftNumber('quantity_ordered', 1),
                    unit_cost: draftNumber('unit_cost', firstProduct().cost),
                    tax_rate: 0,
                },
            ],
        }),
        'Goods Receipt': () => ({
            supplier_id: draftNumber('supplier_id', firstSupplier().id),
            warehouse_id: draftNumber('warehouse_id', inventory.warehouseId),
            receipt_number: actionDraft.receipt_number,
            items: [
                {
                    product_id: draftNumber('product_id', firstProduct().id),
                    quantity_received: draftNumber('quantity_received', 1),
                    unit_cost: draftNumber('unit_cost', firstProduct().cost),
                    tax_rate: 0,
                },
            ],
        }),
        'Return Supplier': () => ({
            supplier_id: draftNumber('supplier_id', firstGoodsReceipt().supplierId ?? firstSupplier().id),
            goods_receipt_id: draftNumber('goods_receipt_id', firstGoodsReceipt().id),
            return_number: actionDraft.return_number || `PR-${Date.now()}`,
            return_date: todayDate(),
            reason: actionDraft.reason,
            items: [
                {
                    goods_receipt_item_id: draftNumber('goods_receipt_item_id', firstGoodsReceiptItem().id) || undefined,
                    product_id: draftNumber('product_id', firstGoodsReceiptItem().productId ?? firstProduct().id),
                    quantity_returned: draftNumber('quantity_returned', 1),
                    unit_cost: draftNumber('unit_cost', firstGoodsReceiptItem().unitCost ?? firstProduct().cost),
                    tax_rate: draftNumber('tax_rate', firstGoodsReceiptItem().taxRate ?? 0),
                    reason: actionDraft.reason,
                },
            ],
        }),
        'Pay Supplier': () => ({
            payment_number: actionDraft.payment_number,
            amount: draftNumber('amount', Math.max((firstOpenPayable().amount ?? 0) - (firstOpenPayable().paidAmount ?? 0), 0)),
            payment_method: actionDraft.payment_method || 'cash',
        }),
        'New Journal': () => ({
            journal_number: actionDraft.journal_number,
            description: actionDraft.description,
            lines: [
                {
                    account_id: draftNumber('debit_account_id', accountByCode('1100').id),
                    debit: draftNumber('amount', 100000),
                    credit: 0,
                    description: actionDraft.description,
                },
                {
                    account_id: draftNumber('credit_account_id', accountByCode('3100', 1).id),
                    debit: 0,
                    credit: draftNumber('amount', 100000),
                    description: actionDraft.description,
                },
            ],
        }),
        Settlement: () => ({
            settlement_number: actionDraft.settlement_number,
            method: actionDraft.method || 'qris',
            date_from: actionDraft.date_from || todayDate(),
            date_to: actionDraft.date_to || todayDate(),
            reported_amount: draftNumber('reported_amount'),
        }),
        'Import Provider': () => {
            const settlement = firstProviderSettlement();

            return {
                payment_settlement_id: draftNumber('payment_settlement_id', settlement.id),
                import_number: actionDraft.import_number,
                provider: actionDraft.provider || 'QRIS Acquirer',
                method: actionDraft.method || settlement.method || 'qris',
                rows: [
                    {
                        reference: actionDraft.reference,
                        amount: draftNumber('amount', settlement.reported),
                        fee_amount: draftNumber('fee_amount'),
                        settled_at: todayDate(),
                    },
                ],
            };
        },
        Loyalty: () => ({
            type: actionDraft.type || 'manual_bonus',
            points_delta: draftNumber('points_delta', 10),
            notes: actionDraft.notes,
        }),
        'Invite User': () => {
            const roleId = draftNumber('role_id', firstRole().id);
            const branchId = draftNumber('branch_id', firstBranch().id);

            return {
                name: actionDraft.name,
                email: actionDraft.email,
                password: actionDraft.password || 'password123',
                branch_id: branchId || undefined,
                roles: roleId ? [
                    {
                        role_id: roleId,
                        branch_id: branchId || undefined,
                    },
                ] : [],
            };
        },
        'Assign Role': () => ({
            role_id: draftNumber('role_id', firstRole().id),
            branch_id: draftNumber('branch_id', firstBranch().id) || undefined,
        }),
    };

    return payloads[activeAction.value]?.() ?? null;
};
const isApiSubmitAction = computed(() => [
    'New Sale',
    'Void Sale',
    'Refund Sale',
    'View Receipt',
    'New Customer',
    'New Product',
    'Open Shift',
    'Cash Movement',
    'Close Shift',
    'New Promo',
    'New Table',
    'Table Status',
    'Reserve Table',
    'Seat Reservation',
    'Cancel Reservation',
    'Kitchen Station',
    'Kitchen Status',
    'Kitchen Item Status',
    'Delivery Status',
    'Hold Cart',
    'Stock Opname',
    'Transfer Stock',
    'Production',
    'New PO',
    'Goods Receipt',
    'Return Supplier',
    'Pay Supplier',
    'New Journal',
    'Settlement',
    'Import Provider',
    'Loyalty',
    'Invite User',
    'Assign Role',
].includes(activeAction.value));
const saveActionDraft = async () => {
    const endpoints = {
        'New Sale': '/sales',
        'Void Sale': () => `/sales/${draftNumber('sale_id', firstCompletedSale().id)}/void`,
        'Refund Sale': () => `/sales/${draftNumber('sale_id', firstCompletedSale().id)}/refund`,
        'View Receipt': () => `/sales/${draftNumber('sale_id', firstCompletedSale().id)}/receipt`,
        'New Customer': '/customers',
        'New Product': '/products',
        'Open Shift': '/cashier-shifts',
        'Cash Movement': () => `/cashier-shifts/${draftNumber('cashier_shift_id', pos.shift.id)}/cash-movements`,
        'Close Shift': () => `/cashier-shifts/${draftNumber('cashier_shift_id', pos.shift.id)}/close`,
        'New Promo': '/promotions',
        'New Table': '/dining-tables',
        'Table Status': () => `/dining-tables/${draftNumber('table_id', firstAvailableTable().id)}/status`,
        'Reserve Table': () => `/dining-tables/${draftNumber('table_id', firstAvailableTable().id)}/reservations`,
        'Seat Reservation': () => `/table-reservations/${draftNumber('reservation_id', firstBookedReservation().id)}/seat`,
        'Cancel Reservation': () => `/table-reservations/${draftNumber('reservation_id', firstBookedReservation().id)}/cancel`,
        'Kitchen Station': '/kitchen-stations',
        'Kitchen Status': () => `/kitchen-tickets/${draftNumber('ticket_id', firstActiveKitchenTicket().id)}/status`,
        'Kitchen Item Status': () => `/kitchen-ticket-items/${draftNumber('item_id', firstActiveKitchenItem().id)}/status`,
        'Delivery Status': () => `/delivery-orders/${draftNumber('delivery_id', firstActiveDelivery().id)}/status`,
        'Hold Cart': '/held-transactions',
        'Stock Opname': '/stock-opnames',
        'Transfer Stock': '/stock-transfers',
        Production: '/production-orders',
        'New PO': '/purchase-orders',
        'Goods Receipt': '/goods-receipts',
        'Return Supplier': '/purchase-returns',
        'Pay Supplier': () => `/supplier-payables/${draftNumber('payable_id', firstOpenPayable().id)}/payments`,
        'New Journal': '/journal-entries',
        Settlement: '/payment-settlements',
        'Import Provider': '/payment-provider-imports',
        Loyalty: () => `/customers/${draftNumber('customer_id', firstCustomer().id)}/loyalty-transactions`,
        'Invite User': '/user-access/users',
        'Assign Role': () => `/user-access/users/${draftNumber('user_id', firstUser().id)}/roles`,
    };
    const endpointConfig = endpoints[activeAction.value];
    const endpoint = typeof endpointConfig === 'function' ? endpointConfig() : endpointConfig;
    const getActions = ['View Receipt'];
    const patchActions = ['Table Status', 'Seat Reservation', 'Cancel Reservation', 'Kitchen Status', 'Kitchen Item Status', 'Delivery Status'];

    if (!endpoint || foundation.apiStatus !== 'connected') {
        actionFeedback.value = `${activeAction.value} draft siap disambungkan ke API.`;

        return;
    }

    try {
        let response = null;

        if (getActions.includes(activeAction.value)) {
            response = await apiGet(endpoint);
        } else if (patchActions.includes(activeAction.value)) {
            await apiPatch(endpoint, actionPayload());
        } else {
            await apiPost(endpoint, actionPayload());
        }

        if (activeAction.value === 'View Receipt') {
            const receipt = response?.receipt;
            const saleNumber = receipt?.sale?.sale_number ?? 'Receipt';
            const grandTotal = Number(receipt?.totals?.grand_total ?? 0).toLocaleString('id-ID');
            actionFeedback.value = `${saleNumber} siap ditampilkan. Total Rp ${grandTotal}.`;

            return;
        }

        if (activeAction.value === 'New Customer') {
            await customers.loadFromApi();
        }

        if (activeAction.value === 'Loyalty') {
            await customers.loadFromApi();
        }

        if (activeAction.value === 'New Product') {
            await masterData.loadFromApi();
        }

        if ([
            'New Sale',
            'Void Sale',
            'Refund Sale',
            'Open Shift',
            'Cash Movement',
            'Close Shift',
            'New Promo',
            'New Table',
            'Table Status',
            'Reserve Table',
            'Seat Reservation',
            'Cancel Reservation',
            'Kitchen Station',
            'Kitchen Status',
            'Kitchen Item Status',
            'Delivery Status',
            'Hold Cart',
        ].includes(activeAction.value)) {
            await pos.loadFromApi();
        }

        if (['Stock Opname', 'Transfer Stock', 'Production'].includes(activeAction.value)) {
            await inventory.loadFromApi();
        }

        if (['New PO', 'Goods Receipt', 'Return Supplier', 'Pay Supplier'].includes(activeAction.value)) {
            await purchasing.loadFromApi();
            await inventory.loadFromApi();
        }

        if (['New Journal', 'Settlement', 'Import Provider'].includes(activeAction.value)) {
            await accounting.loadFromApi();
        }

        if (['Invite User', 'Assign Role'].includes(activeAction.value)) {
            await userAccess.loadFromApi();
        }

        actionFeedback.value = `${activeAction.value} berhasil disimpan ke API.`;
    } catch (error) {
        actionFeedback.value = error?.payload?.message ?? error?.message ?? 'Submit API gagal.';
    }
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
                                    @click="openAction(action)"
                                >
                                    {{ action }}
                                </button>
                            </div>
                        </div>
                        <div
                            v-if="activeAction"
                            class="mt-4 rounded-md border border-emerald-300/30 bg-emerald-300/10 p-4"
                        >
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div>
                                    <p class="text-xs uppercase text-emerald-100">Action Draft</p>
                                    <h4 class="mt-1 text-base font-semibold text-emerald-50">{{ activeAction }}</h4>
                                </div>
                                <span class="rounded-md border border-white/10 px-3 py-1 text-xs text-emerald-50">
                                    {{ isApiSubmitAction && foundation.apiStatus === 'connected' ? 'API submit ready' : 'Draft only' }}
                                </span>
                                <button
                                    class="rounded-md border border-white/10 px-3 py-2 text-sm text-zinc-100 transition hover:border-amber-300/50 hover:bg-amber-300/10"
                                    type="button"
                                    @click="closeAction"
                                >
                                    Close
                                </button>
                            </div>
                            <form class="mt-4 grid gap-3 sm:grid-cols-2" @submit.prevent="saveActionDraft">
                                <label
                                    v-for="field in actionFields"
                                    :key="field.key"
                                    class="grid gap-1 text-sm"
                                >
                                    <span class="text-zinc-300">{{ field.label }}</span>
                                    <input
                                        v-model="actionDraft[field.key]"
                                        class="rounded-md border border-white/10 bg-zinc-950/80 px-3 py-2 text-zinc-100 outline-none transition focus:border-emerald-300/60"
                                        :placeholder="field.placeholder"
                                        :type="field.type"
                                    >
                                </label>
                                <div class="flex items-end gap-2 sm:col-span-2">
                                    <button
                                        class="rounded-md bg-emerald-400 px-4 py-2 text-sm font-semibold text-zinc-950 transition hover:bg-emerald-300"
                                        type="submit"
                                    >
                                        Save Draft
                                    </button>
                                    <span v-if="actionFeedback" class="text-sm text-emerald-100">{{ actionFeedback }}</span>
                                </div>
                            </form>
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
