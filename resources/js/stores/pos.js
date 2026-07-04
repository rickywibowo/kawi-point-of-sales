import { defineStore } from 'pinia';
import { apiGet } from '../services/api';

export const usePosStore = defineStore('pos', {
    state: () => ({
        shift: {
            id: null,
            number: 'SHIFT-DEMO',
            status: 'open',
            openingCash: 250000,
            expectedCash: 250000,
        },
        products: [],
        warehouses: [],
        cart: [
            { name: 'KAWI Rice Bowl', quantity: 1, price: 35000 },
            { name: 'KAWI Iced Coffee', quantity: 1, price: 25000 },
        ],
        payments: [
            { method: 'cash', amount: 60000 },
        ],
        cashMovements: [
            { type: 'cash_in', amount: 50000, reason: 'Tambahan kas kecil' },
            { type: 'cash_out', amount: 25000, reason: 'Belanja operasional' },
        ],
        drawerAudit: {
            status: 'variance_approved',
            expectedCash: 275000,
            countedCash: 274950,
            variance: -50,
            denominations: [
                { denomination: 100000, quantity: 2 },
                { denomination: 50000, quantity: 1 },
                { denomination: 20000, quantity: 1 },
                { denomination: 5000, quantity: 1 },
            ],
        },
        drawerAudits: [
            { id: null, shift: 'SHIFT-DEMO', status: 'variance_approved', variance: -50, auditedAt: null },
        ],
        postSaleControls: {
            voidedToday: 1,
            refundedToday: 1,
        },
        receipts: [
            { number: 'SALE-DEMO-001', channel: 'digital', total: 60000 },
        ],
        sales: [
            { id: null, number: 'SALE-DEMO-001', status: 'completed', total: 60000 },
        ],
        diningTables: [
            { id: null, code: 'T-01', name: 'Table 01', status: 'available', capacity: 2 },
            { id: null, code: 'T-02', name: 'Table 02', status: 'cleaning', capacity: 4 },
            { id: null, code: 'VIP-01', name: 'VIP Room', status: 'reserved', capacity: 8 },
        ],
        tableReservations: [
            { id: null, number: 'RSV-DEMO-001', guest: 'Rina Member', table: 'VIP-01', status: 'booked', time: '19:00' },
        ],
        promotions: [
            { id: null, code: 'KAWI10', name: 'KAWI 10%', type: 'percent', value: 10 },
            { id: null, code: 'LUNCH15K', name: 'Lunch 15K', type: 'fixed', value: 15000 },
        ],
        kitchenTickets: [
            { id: null, number: 'KOT-SALE-DEMO-001', table: 'T-02', station: 'Hot Kitchen', status: 'preparing', itemCount: 2 },
            { id: null, number: 'KOT-SALE-DEMO-002', table: 'Takeaway', station: 'Bar', status: 'ready', itemCount: 1 },
        ],
        kitchenTicketItems: [
            { id: null, ticket: 'KOT-SALE-DEMO-001', product: 'KAWI Rice Bowl', station: 'Hot Kitchen', status: 'pending' },
        ],
        kitchenStations: [
            { id: null, code: 'HOT', name: 'Hot Kitchen', activeTickets: 1 },
            { id: null, code: 'BAR', name: 'Bar', activeTickets: 1 },
        ],
        deliveryOrders: [
            { id: null, number: 'DO-SALE-DEMO-003', recipient: 'Member KAWI', status: 'assigned', courier: 'Andi' },
            { id: null, number: 'DO-SALE-DEMO-004', recipient: 'Rina Member', status: 'picked_up', courier: 'Budi' },
        ],
        heldTransactions: 1,
        todayTransactions: 0,
    }),

    getters: {
        subtotal: (state) => state.cart.reduce((total, item) => total + item.quantity * item.price, 0),
        totalQuantity: (state) => state.cart.reduce((total, item) => total + item.quantity, 0),
        cashMovementNet: (state) => state.cashMovements.reduce((total, movement) => {
            return total + (movement.type === 'cash_in' ? movement.amount : -movement.amount);
        }, 0),
        receiptCount: (state) => state.receipts.length,
        availableTableCount: (state) => state.diningTables.filter((table) => table.status === 'available').length,
        activeReservationCount: (state) => state.tableReservations.filter((reservation) => reservation.status !== 'cancelled').length,
        activePromotionCount: (state) => state.promotions.length,
        activeKitchenTicketCount: (state) => state.kitchenTickets.filter((ticket) => ticket.status !== 'served').length,
        activeKitchenStationCount: (state) => state.kitchenStations.length,
        activeDeliveryCount: (state) => state.deliveryOrders.filter((order) => order.status !== 'delivered').length,
        drawerDenominationCount: (state) => state.drawerAudit.denominations.reduce((total, item) => total + item.quantity, 0),
    },

    actions: {
        async loadFromApi() {
            const response = await apiGet('/pos');
            this.shift = response.active_shift
                ? {
                    id: response.active_shift.id,
                    number: response.active_shift.shift_number,
                    status: response.active_shift.status,
                    openingCash: Number(response.active_shift.opening_cash ?? 0),
                    expectedCash: Number(response.active_shift.expected_cash ?? 0),
                }
                : {
                    ...this.shift,
                    id: null,
                    status: 'closed',
                };
            this.products = response.products?.map((product) => ({
                id: product.id,
                name: product.name,
                price: Number(product.branch_prices?.[0]?.price ?? product.base_price ?? 0),
            })) ?? this.products;
            this.warehouses = response.warehouses?.map((warehouse) => ({
                id: warehouse.id,
                name: warehouse.name,
                code: warehouse.code,
            })) ?? this.warehouses;
            this.cart = response.today_sales?.[0]?.items?.map((item) => ({
                productId: item.product_id,
                name: item.product_name,
                quantity: Number(item.quantity ?? 0),
                price: Number(item.unit_price ?? 0),
            })) ?? this.cart;
            this.payments = response.today_sales?.[0]?.payments?.map((payment) => ({
                method: payment.method,
                amount: Number(payment.amount ?? 0),
            })) ?? this.payments;
            this.receipts = response.today_sales?.map((sale) => ({
                number: sale.sale_number,
                channel: 'digital',
                total: Number(sale.grand_total ?? 0),
            })) ?? this.receipts;
            this.sales = response.today_sales?.map((sale) => ({
                id: sale.id,
                number: sale.sale_number,
                status: sale.status,
                total: Number(sale.grand_total ?? 0),
            })) ?? this.sales;
            this.postSaleControls = response.today_sales
                ? {
                    voidedToday: response.today_sales.filter((sale) => sale.status === 'voided').length,
                    refundedToday: response.today_sales.filter((sale) => sale.status === 'refunded').length,
                }
                : this.postSaleControls;
            this.diningTables = response.dining_tables?.map((table) => ({
                id: table.id,
                code: table.code,
                name: table.name,
                status: table.status,
                capacity: table.capacity,
            })) ?? this.diningTables;
            this.tableReservations = response.table_reservations?.map((reservation) => ({
                id: reservation.id,
                number: reservation.reservation_number,
                guest: reservation.guest_name,
                table: reservation.dining_table?.code,
                status: reservation.status,
                time: reservation.reserved_at?.slice(11, 16),
            })) ?? this.tableReservations;
            this.promotions = response.promotions?.map((promotion) => ({
                id: promotion.id,
                code: promotion.code,
                name: promotion.name,
                type: promotion.type,
                value: Number(promotion.value ?? 0),
            })) ?? this.promotions;
            this.kitchenTickets = response.kitchen_tickets?.map((ticket) => ({
                id: ticket.id,
                number: ticket.ticket_number,
                table: ticket.dining_table?.code ?? ticket.sale?.type ?? 'Takeaway',
                station: ticket.items?.[0]?.station_name ?? 'General',
                status: ticket.status,
                itemCount: ticket.items?.length ?? 0,
            })) ?? this.kitchenTickets;
            this.kitchenTicketItems = response.kitchen_tickets?.flatMap((ticket) => ticket.items?.map((item) => ({
                id: item.id,
                ticket: ticket.ticket_number,
                product: item.product_name,
                station: item.station_name ?? 'General',
                status: item.status,
            })) ?? []) ?? this.kitchenTicketItems;
            this.kitchenStations = response.kitchen_stations?.map((station) => ({
                id: station.id,
                code: station.code,
                name: station.name,
                activeTickets: this.kitchenTickets.filter((ticket) => ticket.station === station.name).length,
            })) ?? this.kitchenStations;
            this.deliveryOrders = response.delivery_orders?.map((order) => ({
                id: order.id,
                number: order.delivery_number,
                recipient: order.recipient_name,
                status: order.status,
                courier: order.courier_name,
            })) ?? this.deliveryOrders;
            this.drawerAudits = response.cash_drawer_audits?.map((audit) => ({
                id: audit.id,
                shift: audit.cashier_shift?.shift_number,
                status: audit.status,
                variance: Number(audit.variance_amount ?? 0),
                auditedAt: audit.audited_at,
            })) ?? this.drawerAudits;
            this.drawerAudit = response.cash_drawer_audits?.[0]
                ? {
                    status: response.cash_drawer_audits[0].status,
                    expectedCash: Number(response.cash_drawer_audits[0].expected_cash ?? 0),
                    countedCash: Number(response.cash_drawer_audits[0].counted_cash ?? 0),
                    variance: Number(response.cash_drawer_audits[0].variance_amount ?? 0),
                    denominations: response.cash_drawer_audits[0].denomination_breakdown ?? [],
                }
                : this.drawerAudit;
            this.heldTransactions = response.held_transactions?.length ?? this.heldTransactions;
            this.todayTransactions = response.today_sales?.length ?? this.todayTransactions;
        },
    },
});
