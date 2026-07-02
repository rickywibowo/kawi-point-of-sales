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
        heldTransactions: 1,
        todayTransactions: 0,
    }),

    getters: {
        subtotal: (state) => state.cart.reduce((total, item) => total + item.quantity * item.price, 0),
        totalQuantity: (state) => state.cart.reduce((total, item) => total + item.quantity, 0),
        cashMovementNet: (state) => state.cashMovements.reduce((total, movement) => {
            return total + (movement.type === 'cash_in' ? movement.amount : -movement.amount);
        }, 0),
    },
});
