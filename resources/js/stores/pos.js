import { defineStore } from 'pinia';
import { apiGet } from '../services/api';

export const usePosStore = defineStore('pos', {
    state: () => ({
        shift: {
            number: 'SHIFT-DEMO',
            status: 'open',
            openingCash: 250000,
        },
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
        postSaleControls: {
            voidedToday: 1,
            refundedToday: 1,
        },
        receipts: [
            { number: 'SALE-DEMO-001', channel: 'digital', total: 60000 },
        ],
        diningTables: [
            { code: 'T-01', name: 'Table 01', status: 'available', capacity: 2 },
            { code: 'T-02', name: 'Table 02', status: 'cleaning', capacity: 4 },
            { code: 'VIP-01', name: 'VIP Room', status: 'reserved', capacity: 8 },
        ],
        tableReservations: [
            { number: 'RSV-DEMO-001', guest: 'Rina Member', table: 'VIP-01', status: 'booked', time: '19:00' },
        ],
        promotions: [
            { code: 'KAWI10', name: 'KAWI 10%', type: 'percent', value: 10 },
            { code: 'LUNCH15K', name: 'Lunch 15K', type: 'fixed', value: 15000 },
        ],
        kitchenTickets: [
            { number: 'KOT-SALE-DEMO-001', table: 'T-02', station: 'Hot Kitchen', status: 'preparing', itemCount: 2 },
            { number: 'KOT-SALE-DEMO-002', table: 'Takeaway', station: 'Bar', status: 'ready', itemCount: 1 },
        ],
        kitchenStations: [
            { code: 'HOT', name: 'Hot Kitchen', activeTickets: 1 },
            { code: 'BAR', name: 'Bar', activeTickets: 1 },
        ],
        deliveryOrders: [
            { number: 'DO-SALE-DEMO-003', recipient: 'Member KAWI', status: 'assigned', courier: 'Andi' },
            { number: 'DO-SALE-DEMO-004', recipient: 'Rina Member', status: 'picked_up', courier: 'Budi' },
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
            this.cart = response.today_sales?.[0]?.items?.map((item) => ({
                name: item.product_name,
                quantity: Number(item.quantity ?? 0),
                price: Number(item.unit_price ?? 0),
            })) ?? this.cart;
            this.payments = response.today_sales?.[0]?.payments?.map((payment) => ({
                method: payment.method,
                amount: Number(payment.amount ?? 0),
            })) ?? this.payments;
            this.diningTables = response.dining_tables?.map((table) => ({
                code: table.code,
                name: table.name,
                status: table.status,
                capacity: table.capacity,
            })) ?? this.diningTables;
            this.tableReservations = response.table_reservations?.map((reservation) => ({
                number: reservation.reservation_number,
                guest: reservation.guest_name,
                table: reservation.dining_table?.code,
                status: reservation.status,
                time: reservation.reserved_at?.slice(11, 16),
            })) ?? this.tableReservations;
            this.promotions = response.promotions?.map((promotion) => ({
                code: promotion.code,
                name: promotion.name,
                type: promotion.type,
                value: Number(promotion.value ?? 0),
            })) ?? this.promotions;
            this.kitchenTickets = response.kitchen_tickets?.map((ticket) => ({
                number: ticket.ticket_number,
                table: ticket.dining_table?.code ?? ticket.sale?.type ?? 'Takeaway',
                station: ticket.items?.[0]?.station_name ?? 'General',
                status: ticket.status,
                itemCount: ticket.items?.length ?? 0,
            })) ?? this.kitchenTickets;
            this.kitchenStations = response.kitchen_stations?.map((station) => ({
                code: station.code,
                name: station.name,
                activeTickets: this.kitchenTickets.filter((ticket) => ticket.station === station.name).length,
            })) ?? this.kitchenStations;
            this.deliveryOrders = response.delivery_orders?.map((order) => ({
                number: order.delivery_number,
                recipient: order.recipient_name,
                status: order.status,
                courier: order.courier_name,
            })) ?? this.deliveryOrders;
            this.heldTransactions = response.held_transactions?.length ?? this.heldTransactions;
            this.todayTransactions = response.today_sales?.length ?? this.todayTransactions;
        },
    },
});
