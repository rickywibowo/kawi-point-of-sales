import { defineStore } from 'pinia';

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
            { number: 'KOT-SALE-DEMO-001', table: 'T-02', status: 'preparing', itemCount: 2 },
            { number: 'KOT-SALE-DEMO-002', table: 'Takeaway', status: 'ready', itemCount: 1 },
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
        activeDeliveryCount: (state) => state.deliveryOrders.filter((order) => order.status !== 'delivered').length,
    },
});
